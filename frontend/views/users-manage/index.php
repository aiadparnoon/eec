<?php
/**
 * فهرست دانشپذیران — docs/specs/users-manage.md بند ۳
 *
 * @var $this yii\web\View
 * @var $searchModel app\models\UsersSearch
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $students app\models\Users[]
 * @var $registrants array
 * @var $stats array
 * @var $colleges array
 * @var $courses array
 */
use app\components\StudentAccess;
use app\components\UsersDirectory;
use app\components\UsersImport;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'مدیریت دانشپذیران';
echo $this->render('_flash');

$fa = function ($n) {
    return UsersImport::faDigits(number_format((int) $n));
};
$pagination = $dataProvider->getPagination();
$offset = $pagination ? $pagination->getOffset() : 0;
$total = $dataProvider->getTotalCount();
$collegeTitles = UsersDirectory::collegeTitles();
$importReport = Yii::$app->session->getFlash('users-import-report');

$this->registerCss(<<<CSS
.users-table tbody tr[data-href] { cursor: pointer; }
.users-table tbody tr[data-href]:hover { background: rgba(105, 108, 255, .04); }
.users-table td { vertical-align: middle; }
.users-table th, .users-table td { white-space: nowrap; padding-left: .6rem; padding-right: .6rem; }
.users-table td.wrap { white-space: normal; min-width: 120px; }
.stat-card .avatar-initial { font-size: 1.35rem; }
.college-bar { height: 6px; }
CSS
);
$this->registerJs(<<<JS
$(document).on('click', '.users-table tbody tr[data-href]', function (e) {
    if ($(e.target).closest('a, button, .dropdown-menu, form').length) return;
    window.location = $(this).data('href');
});
JS
);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">مدیریت دانشپذیران</h4>
            <p class="text-muted mb-0">
                <?php if (StudentAccess::isAdmin()): ?>
                    همه‌ی دانشپذیران سامانه
                <?php else: ?>
                    دانشپذیران <?= Html::encode(implode('، ', UsersDirectory::collegeNames(StudentAccess::staffColleges())) ?: 'ثبت‌شده توسط شما') ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-label-success" href="<?= Url::to(array_merge(['report'], Yii::$app->request->queryParams)) ?>">
                <i class="bx bx-export me-1"></i>خروجی اکسل
            </a>
            <div class="btn-group">
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bx bx-user-plus me-1"></i>افزودن دانشپذیر
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-user"><i class="bx bx-user me-2"></i>افزودن با مشخصات</a></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#from-excel"><i class="bx bx-spreadsheet me-2"></i>افزودن از فایل اکسل</a></li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (is_array($importReport)): ?>
        <?= $this->render('_import-report', ['report' => $importReport]) ?>
    <?php endif; ?>

    <!-- آمار -->
    <div class="row g-4 mb-4">
        <?php
        $cards = [
            ['کل دانشپذیران', $stats['total'], 'bx-group', 'primary', null],
            ['فعال', $stats['active'], 'bx-user-check', 'success', ['status' => '10']],
            ['غیرفعال', $stats['inactive'], 'bx-user-x', 'danger', ['status' => '9']],
            ['دارای دوره', $stats['withCourses'], 'bx-book-open', 'info', ['has_courses' => '1']],
            ['بدون دوره', $stats['withoutCourses'], 'bx-book', 'secondary', ['has_courses' => '0']],
            ['ثبت‌شده در ۳۰ روز اخیر', $stats['newThisMonth'], 'bx-calendar-plus', 'warning', null],
        ];
        foreach ($cards as $card):
            $url = $card[4] === null ? null : Url::to(['index', 'UsersSearch' => $card[4]]);
            ?>
            <div class="col-6 col-md-4 col-xl-2">
                <<?= $url ? 'a href="' . Html::encode($url) . '"' : 'div' ?> class="card stat-card h-100 text-body">
                    <div class="card-body">
                        <div class="avatar mb-3">
                            <span class="avatar-initial rounded bg-label-<?= $card[3] ?>"><i class="bx <?= $card[2] ?>"></i></span>
                        </div>
                        <span class="d-block text-muted mb-1"><?= Html::encode($card[0]) ?></span>
                        <h4 class="card-title mb-0"><?= $fa($card[1]) ?></h4>
                    </div>
                </<?= $url ? 'a' : 'div' ?>>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($stats['colleges']) || $stats['noCollege'] > 0 || $stats['pendingPlatform'] > 0 || $stats['withoutNationalCode'] > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">دانشپذیران به تفکیک دانشکده</h5>
                        <small class="text-muted">هر دانشپذیر ممکن است عضو چند دانشکده باشد</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php
                            $max = max(1, max(array_merge([0], array_values($stats['colleges']))), $stats['noCollege']);
                            foreach ($stats['colleges'] as $id => $count): ?>
                                <div class="col-12 col-md-6">
                                    <a class="d-flex justify-content-between mb-1 text-body" href="<?= Url::to(['index', 'UsersSearch' => ['college_id' => $id]]) ?>">
                                        <span><?= Html::encode(isset($collegeTitles[$id]) ? $collegeTitles[$id] : 'نامشخص') ?></span>
                                        <span class="fw-semibold"><?= $fa($count) ?></span>
                                    </a>
                                    <div class="progress college-bar"><div class="progress-bar" style="width: <?= round($count * 100 / $max) ?>%"></div></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (StudentAccess::isAdmin() && $stats['noCollege'] > 0): ?>
                                <div class="col-12 col-md-6">
                                    <a class="d-flex justify-content-between mb-1 text-body" href="<?= Url::to(['index', 'UsersSearch' => ['college_id' => 'none']]) ?>">
                                        <span class="text-muted">بدون دانشکده</span>
                                        <span class="fw-semibold"><?= $fa($stats['noCollege']) ?></span>
                                    </a>
                                    <div class="progress college-bar"><div class="progress-bar bg-secondary" style="width: <?= round($stats['noCollege'] * 100 / $max) ?>%"></div></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header"><h5 class="card-title mb-0">نیازمند پیگیری</h5></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-3">
                                <span class="badge bg-label-warning rounded p-2 me-3"><i class="bx bx-error"></i></span>
                                <a class="flex-grow-1 text-body" href="<?= Url::to(['index', 'UsersSearch' => ['course_status' => '0']]) ?>">ثبت‌نشده در کلاس آنلاین (حداقل یک دوره)</a>
                                <span class="fw-semibold"><?= $fa($stats['pendingPlatform']) ?></span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <span class="badge bg-label-info rounded p-2 me-3"><i class="bx bx-id-card"></i></span>
                                <a class="flex-grow-1 text-body" href="<?= Url::to(['index', 'UsersSearch' => ['has_national_code' => '0']]) ?>">اطلاعات هویتی تکمیل‌نشده</a>
                                <span class="fw-semibold"><?= $fa($stats['withoutNationalCode']) ?></span>
                            </li>
                            <li class="d-flex align-items-center">
                                <span class="badge bg-label-secondary rounded p-2 me-3"><i class="bx bx-user-x"></i></span>
                                <a class="flex-grow-1 text-body" href="<?= Url::to(['index', 'UsersSearch' => ['status' => '9']]) ?>">حساب غیرفعال</a>
                                <span class="fw-semibold"><?= $fa($stats['inactive']) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?= $this->render('_search', ['model' => $searchModel, 'colleges' => $colleges, 'courses' => $courses]) ?>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">
                فهرست دانشپذیران
                <span class="badge bg-label-primary ms-2"><?= $fa($total) ?> نفر</span>
            </h5>
            <?php if ($searchModel->hasFilters()): ?>
                <a href="<?= Url::to(['index']) ?>" class="btn btn-sm btn-label-secondary"><i class="bx bx-x me-1"></i>حذف فیلترها</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover users-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>دانشپذیر</th>
                    <th>کد ملی</th>
                    <th>دانشکده‌ها</th>
                    <th>ثبت کننده</th>
                    <th class="text-center">دوره‌ها</th>
                    <th>وضعیت / تاریخ ثبت</th>
                    <th class="text-center">عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php if (empty($students)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bx bx-search-alt d-block mb-2" style="font-size:2rem"></i>دانشپذیری با این مشخصات یافت نشد</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $i => $student):
                    $id = (string) $student->_id;
                    $profileUrl = Url::to(['profile', 'id' => $id]);
                    $info = is_array($student->issuance_certificate_information) ? $student->issuance_certificate_information : [];
                    $registrant = UsersDirectory::describeRegistrant($student, $registrants);
                    $collegeNames = UsersDirectory::collegeNames($student->college);
                    $courseCount = is_array($student->courses) ? count($student->courses) : 0;
                    $inactive = $student->status == \app\models\Users::STATUS_INACTIVE;
                    $fullName = trim($student->first_name . ' ' . $student->last_name);
                    ?>
                    <tr data-href="<?= Html::encode($profileUrl) ?>">
                        <td class="text-muted"><?= $fa($offset + $i + 1) ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded-circle bg-label-<?= $inactive ? 'secondary' : 'primary' ?>"><?= Html::encode(UsersDirectory::initials($student->first_name, $student->last_name)) ?></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <a href="<?= Html::encode($profileUrl) ?>" class="text-body fw-semibold"><?= Html::encode($fullName ?: '-') ?></a>
                                    <small class="text-muted" dir="ltr" style="text-align:right"><?= Html::encode($student->username) ?></small>
                                    <?php if ($student->role == 'mentor'): ?><small><span class="badge bg-label-info">دستیار استاد</span></small><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted"><?= !empty($info['id']) ? Html::encode($info['id']) : '<span class="badge bg-label-warning">ثبت نشده</span>' ?></td>
                        <td class="wrap">
                            <?php if (empty($collegeNames)): ?>
                                <span class="text-muted">—</span>
                            <?php else:
                                foreach (array_slice($collegeNames, 0, 2) as $name): ?>
                                    <span class="badge bg-label-primary"><?= Html::encode($name) ?></span>
                                <?php endforeach;
                                if (count($collegeNames) > 2): ?>
                                    <span class="badge bg-label-secondary" title="<?= Html::encode(implode('، ', array_slice($collegeNames, 2))) ?>">+<?= UsersImport::faDigits(count($collegeNames) - 2) ?></span>
                                <?php endif;
                            endif; ?>
                        </td>
                        <td class="wrap">
                            <div class="d-flex flex-column">
                                <span><?= Html::encode($registrant['name']) ?></span>
                                <?php if ($registrant['roleLabel'] !== ''): ?><small class="text-muted"><?= Html::encode($registrant['roleLabel']) ?></small><?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center"><span class="badge rounded-pill bg-label-<?= $courseCount ? 'info' : 'secondary' ?>"><?= UsersImport::faDigits($courseCount) ?></span></td>
                        <td>
                            <span class="badge bg-label-<?= $inactive ? 'danger' : 'success' ?>"><?= $inactive ? 'غیرفعال' : 'فعال' ?></span>
                            <small class="d-block text-muted mt-1"><?= UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))) ?></small>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="<?= Html::encode($profileUrl) ?>" class="btn btn-sm btn-label-primary px-2"><i class="bx bx-id-card me-1"></i>پروفایل</a>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="عملیات بیشتر">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item js-edit-user" href="#" data-bs-toggle="modal" data-bs-target="#edit-user" data-id="<?= $id ?>" data-first="<?= Html::encode($student->first_name) ?>" data-last="<?= Html::encode($student->last_name) ?>"><i class="bx bx-edit me-2"></i>ویرایش مشخصات</a></li>
                                        <li><a class="dropdown-item js-change-password" href="#" data-bs-toggle="modal" data-bs-target="#change-password" data-id="<?= $id ?>" data-name="<?= Html::encode($fullName) ?>"><i class="bx bx-lock-alt me-2"></i>تغییر رمز عبور</a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination && $pagination->getPageCount() > 1): ?>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    نمایش <?= $fa($offset + 1) ?> تا <?= $fa($offset + count($students)) ?> از <?= $fa($total) ?>
                </small>
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'maxButtonCount' => 7,
                    'options' => ['class' => 'pagination pagination-sm mb-0'],
                    'linkContainerOptions' => ['class' => 'page-item'],
                    'linkOptions' => ['class' => 'page-link'],
                    'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'page-link'],
                    'prevPageLabel' => '<i class="bx bx-chevron-right"></i>',
                    'nextPageLabel' => '<i class="bx bx-chevron-left"></i>',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->render('_modals', ['showAdd' => true]) ?>
