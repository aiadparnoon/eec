<?php
/**
 * تب «مشخصات».
 *
 * @var $student app\models\Users
 * @var $registrants array
 */
use app\components\UsersDirectory;
use app\models\Cities;
use app\models\Provinces;
use app\models\Users;
use yii\helpers\Html;

$info = is_array($student->issuance_certificate_information) ? $student->issuance_certificate_information : [];
$get = function ($key) use ($info) {
    return isset($info[$key]) && $info[$key] !== '' && $info[$key] !== null ? (string) $info[$key] : null;
};
$province = null;
$city = null;
if ($get('province') !== null) {
    $p = Provinces::find()->where(['id' => (int) $get('province')])->one();
    $province = $p !== null ? $p->name : $get('province');
}
if ($get('city') !== null) {
    $c = Cities::find()->where(['id' => (int) $get('city')])->one();
    $city = $c !== null ? $c->name : $get('city');
}
$gender = $get('gender');
$registrant = UsersDirectory::describeRegistrant($student, $registrants);

$groups = [
    'حساب کاربری' => [
        'نام کاربری' => ['<span dir="ltr">' . Html::encode($student->username) . '</span>', true],
        'وضعیت' => [$student->status == Users::STATUS_INACTIVE ? '<span class="badge bg-label-danger">غیرفعال</span>' : '<span class="badge bg-label-success">فعال</span>', true],
        'نقش' => [$student->role == 'mentor' ? 'دستیار استاد' : 'دانشپذیر', false],
        'واحدها' => [implode('، ', UsersDirectory::collegeNames($student->college)) ?: null, false],
        'ثبت کننده' => [$registrant['name'] . ($registrant['roleLabel'] !== '' ? ' (' . $registrant['roleLabel'] . ')' : ''), false],
        'تاریخ ثبت' => [UsersDirectory::jdate('Y/m/d H:i', hexdec(substr((string) $student->_id, 0, 8))), false],
        'آخرین به‌روزرسانی' => [$student->updated_at ? UsersDirectory::jdate('Y/m/d H:i', $student->updated_at) : null, false],
    ],
    'اطلاعات هویتی' => [
        'نام' => [$student->first_name, false],
        'نام خانوادگی' => [$student->last_name, false],
        'نام (انگلیسی)' => [$get('first_name_en'), false],
        'نام خانوادگی (انگلیسی)' => [$get('last_name_en'), false],
        'کد ملی' => [$get('id'), false],
        'شماره شناسنامه' => [$get('shsh'), false],
        'نام پدر' => [$get('father_name'), false],
        'تاریخ تولد' => [$get('birth_day'), false],
        'جنسیت' => [$gender === null ? null : ($gender === '0' ? 'زن' : 'مرد'), false],
        'استان' => [$province, false],
        'شهر' => [$city, false],
    ],
    'سایر اطلاعات' => [
        'تلفن' => [$get('phone'), false],
        'ایمیل' => [$get('email'), false],
        'شماره دانشجویی' => [$get('student_number'), false],
        'رشته' => [$get('field'), false],
        'شغل' => [$get('job'), false],
        'کد پستی' => [$get('zip_code'), false],
        'نشانی' => [$get('address'), false],
    ],
];
?>
<div class="card">
    <div class="card-body">
        <?php foreach ($groups as $title => $fields):
            $hasAny = false;
            foreach ($fields as $f) if ($f[0] !== null && $f[0] !== '') $hasAny = true;
            if (!$hasAny && $title === 'سایر اطلاعات') continue; ?>
            <h6 class="text-muted text-uppercase small mb-3 <?= $title !== 'حساب کاربری' ? 'mt-4' : '' ?>"><?= Html::encode($title) ?></h6>
            <div class="row g-3">
                <?php foreach ($fields as $label => $field): ?>
                    <div class="col-sm-6 col-xl-4">
                        <small class="text-muted d-block"><?= Html::encode($label) ?></small>
                        <span class="fw-semibold">
                            <?php if ($field[0] === null || $field[0] === ''): ?>
                                <span class="text-muted fw-normal">—</span>
                            <?php else: ?>
                                <?= $field[1] ? $field[0] : Html::encode($field[0]) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($info)): ?>
            <div class="alert alert-warning mt-4 mb-0"><i class="bx bx-info-circle me-1"></i>دانشپذیر هنوز اطلاعات هویتی (برای صدور گواهی) را تکمیل نکرده است.</div>
        <?php endif; ?>
    </div>
</div>
