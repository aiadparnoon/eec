<?php
/**
 * فیلترهای فهرست دانشپذیران.
 *
 * @var $this yii\web\View
 * @var $model app\models\UsersSearch
 * @var $colleges array [id => title]
 * @var $courses array [id => title]
 */
use yii\helpers\Html;
use yii\helpers\Url;

$advancedFields = ['first_name', 'last_name', 'username', 'national_code', 'course_id', 'course_status', 'registrant_type',
    'has_courses', 'has_national_code', 'gender', 'has_platform_account', 'role', 'created_from', 'created_to'];
$advancedOpen = false;
foreach ($advancedFields as $field)
    if ($model->$field !== null && $model->$field !== '')
        $advancedOpen = true;

$yesNo = ['1' => 'بله', '0' => 'خیر'];
$field = function ($attribute, $label, $input) {
    return '<div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="us-' . $attribute . '">' . $label . '</label>' . $input . '</div>';
};
$text = function ($attribute, $placeholder = '') use ($model) {
    return Html::textInput('UsersSearch[' . $attribute . ']', $model->$attribute, ['id' => 'us-' . $attribute, 'class' => 'form-control', 'placeholder' => $placeholder, 'autocomplete' => 'off']);
};
$select = function ($attribute, $items, $prompt = 'همه') use ($model) {
    return Html::dropDownList('UsersSearch[' . $attribute . ']', $model->$attribute, $items, ['id' => 'us-' . $attribute, 'class' => 'form-select', 'prompt' => $prompt]);
};

$this->registerCssFile('@web/assets/vendor/libs/select2/select2.css');
$this->registerJs(<<<JS
$('#us-course_id').select2({dropdownParent: $('#us-course_id').parent(), allowClear: true, placeholder: 'همه‌ی دوره‌ها', dir: 'rtl', width: '100%'});
$('#us-college_id').select2({dropdownParent: $('#us-college_id').parent(), allowClear: true, placeholder: 'همه‌ی دانشکده‌ها', dir: 'rtl', width: '100%'});
if ($.fn.flatpickr) {
    $('.us-date').flatpickr({locale: 'fa', dateFormat: 'Y/m/d', disableMobile: true, allowInput: true});
}
JS
);
?>
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= Url::to(['index']) ?>" method="get" id="users-filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="us-q">جست‌وجو</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <?= Html::textInput('UsersSearch[q]', $model->q, ['id' => 'us-q', 'class' => 'form-control', 'placeholder' => 'نام، نام خانوادگی، موبایل/ایمیل یا کد ملی', 'autocomplete' => 'off']) ?>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="us-college_id">دانشکده</label>
                    <?php
                    $collegeItems = $colleges;
                    if (\app\components\StudentAccess::isAdmin())
                        $collegeItems = ['none' => 'بدون دانشکده'] + $collegeItems;
                    echo Html::dropDownList('UsersSearch[college_id]', $model->college_id, $collegeItems, ['id' => 'us-college_id', 'class' => 'form-select', 'prompt' => '']);
                    ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label" for="us-status">وضعیت</label>
                    <?= $select('status', ['10' => 'فعال', '9' => 'غیرفعال']) ?>
                </div>
                <div class="col-12 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>اعمال فیلتر</button>
                    <button class="btn btn-label-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#users-advanced-filters" aria-expanded="<?= $advancedOpen ? 'true' : 'false' ?>" title="فیلترهای بیشتر">
                        <i class="bx bx-slider-alt"></i>
                    </button>
                </div>
            </div>

            <div class="collapse <?= $advancedOpen ? 'show' : '' ?>" id="users-advanced-filters">
                <hr class="my-4">
                <div class="row g-3">
                    <?= $field('first_name', 'نام', $text('first_name')) ?>
                    <?= $field('last_name', 'نام خانوادگی', $text('last_name')) ?>
                    <?= $field('username', 'نام کاربری', $text('username', 'موبایل یا ایمیل')) ?>
                    <?= $field('national_code', 'کد ملی', $text('national_code')) ?>
                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="us-course_id">دوره</label>
                        <?= Html::dropDownList('UsersSearch[course_id]', $model->course_id, $courses, ['id' => 'us-course_id', 'class' => 'form-select', 'prompt' => '']) ?>
                    </div>
                    <?= $field('course_status', 'وضعیت در دوره', $select('course_status', ['1' => 'فعال', '2' => 'غیرفعال', '0' => 'ثبت‌نشده در کلاس آنلاین'])) ?>
                    <?= $field('has_courses', 'دارای دوره', $select('has_courses', $yesNo)) ?>
                    <?= $field('registrant_type', 'ثبت کننده', $select('registrant_type', [
                        'me' => 'ثبت‌شده توسط من',
                        'emp' => 'کارشناس دانشکده',
                        'user' => 'مدیر سیستم',
                        'broker' => 'کارگزار',
                        'self' => 'ثبت‌نام اینترنتی (خود دانشپذیر)',
                        'unknown' => 'نامشخص',
                    ])) ?>
                    <?= $field('has_national_code', 'اطلاعات هویتی (کد ملی)', $select('has_national_code', ['1' => 'تکمیل شده', '0' => 'تکمیل نشده'])) ?>
                    <?= $field('gender', 'جنسیت', $select('gender', ['1' => 'مرد', '0' => 'زن'])) ?>
                    <?= $field('has_platform_account', 'حساب کلاس آنلاین', $select('has_platform_account', ['1' => 'دارد', '0' => 'ندارد'])) ?>
                    <?= $field('role', 'نقش', $select('role', ['user' => 'دانشپذیر', 'mentor' => 'دستیار استاد'])) ?>
                    <?= $field('created_from', 'تاریخ ثبت از', Html::textInput('UsersSearch[created_from]', $model->created_from, ['id' => 'us-created_from', 'class' => 'form-control us-date', 'placeholder' => '1404/01/01', 'autocomplete' => 'off'])) ?>
                    <?= $field('created_to', 'تاریخ ثبت تا', Html::textInput('UsersSearch[created_to]', $model->created_to, ['id' => 'us-created_to', 'class' => 'form-control us-date', 'placeholder' => '1404/12/29', 'autocomplete' => 'off'])) ?>
                    <?= $field('sort_by', 'مرتب‌سازی', Html::dropDownList('UsersSearch[sort_by]', $model->sort_by, ['newest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'name' => 'نام خانوادگی'], ['id' => 'us-sort_by', 'class' => 'form-select'])) ?>
                    <?= $field('per_page', 'تعداد در صفحه', Html::dropDownList('UsersSearch[per_page]', $model->pageSize(), array_combine($model::PAGE_SIZES, $model::PAGE_SIZES), ['id' => 'us-per_page', 'class' => 'form-select'])) ?>
                </div>
            </div>
        </form>
    </div>
</div>
