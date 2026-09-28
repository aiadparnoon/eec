<?php
/**
 * تب «اعضا»ی دوره (کوتاه‌مدت و میان‌مدت): پیام نتیجه، آمار، جست‌وجو (نام، نام خانوادگی، نام کاربری، کد ملی،
 * وضعیت)، خروجی اکسل با همان فیلترها و افزودن از فایل اکسل با بررسی کامل (CourseMembersController).
 * مودال «افزودن با مشخصات» (کارتخوان) در صفحه‌ی هر نوع دوره جداگانه است.
 *
 * @var $this yii\web\View
 * @var $model app\models\CourseMembersSearch
 * @var $course app\models\Courses
 * @var $stats array CourseMembersSearch::stats()
 */
use app\components\CourseAccess;
use app\components\UsersImport;
use app\models\CourseMembersSearch;
use frontend\controllers\CourseMembersController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$packageDetail = $course;
$flash = Yii::$app->session->getFlash(CourseMembersController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $flashType = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$flashType'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 8000, escapeHtml: true});", \yii\web\View::POS_END);
}
$membersUrl = function (array $params = []) use ($course) {
    return Url::to(CourseMembersController::membersUrl($course, $params));
};
$editRoute = CourseMembersController::membersUrl($course)[0];

$fa = function ($n) { return UsersImport::faDigits($n); };
$courseId = (string) $packageDetail->_id;
$value = function ($attribute) use ($model) {
    return is_scalar($model->$attribute) ? (string) $model->$attribute : '';
};
$filterParams = array_filter([
    'first_name' => $value('first_name'), 'last_name' => $value('last_name'), 'username' => $value('username'),
    'national_code' => $value('national_code'), 'status' => $value('status'),
], 'strlen');
$checkUrl = Json::htmlEncode(Url::to(['course-members/check', '_id' => $courseId]));
$this->registerJs(<<<JS
$('#members-excel-check').on('click', function () {
    var btn = $(this), input = document.getElementById('members-excel-file');
    if (!input.files.length) { toastr.error('فایل اکسل را انتخاب کنید', '', {positionClass: 'toast-top-center'}); return; }
    var fd = new FormData();
    fd.append('file', input.files[0]);
    fd.append(yii.getCsrfParam(), yii.getCsrfToken());
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>در حال بررسی...');
    $('#members-excel-result').html('');
    $.ajax({url: $checkUrl, type: 'POST', data: fd, processData: false, contentType: false}).done(function (res) {
        $('#members-excel-result').html(res.html || '');
    }).fail(function () {
        $('#members-excel-result').html('<div class="alert alert-danger">خطا در بررسی فایل</div>');
    }).always(function () {
        btn.prop('disabled', false).html('<i class="bx bx-search-alt me-1"></i>بررسی فایل');
    });
});
$('#from-excel').on('hidden.bs.modal', function () { $('#members-excel-result').html(''); $('#members-excel-file').val(''); });
JS
, \yii\web\View::POS_END); // جدا از بلوک ready: خطای قدیمی yiiActiveForm در آن بلوک، اسکریپت‌های بعدی را متوقف می‌کند
?>
<div class="row g-3 mb-3">
    <?php foreach ([
        ['total', 'کل اعضا', 'bx-group', 'primary', ''],
        ['active', 'فعال', 'bx-check-circle', 'success', '1'],
        ['inactive', 'غیرفعال', 'bx-pause-circle', 'secondary', '2'],
        ['pending', 'ثبت‌نشده در کلاس', 'bx-time', 'warning', '0'],
        ['cancel', 'انصراف در انتظار', 'bx-log-out', 'danger', 'cancel'],
        ['certificateReady', 'اطلاعات گواهی کامل', 'bx-certification', 'info', null],
    ] as $card):
        $link = $card[4] === null ? null : $membersUrl($card[4] === '' ? [] : ['CM[status]' => $card[4]]);
        ?>
        <div class="col-6 col-md-4 col-xl-2">
            <<?= $link ? 'a href="' . Html::encode($link) . '"' : 'div' ?> class="card h-100 shadow-none border text-body">
                <div class="card-body d-flex align-items-center gap-2 py-3">
                    <span class="avatar-initial rounded bg-label-<?= $card[3] ?> p-2"><i class="bx <?= $card[2] ?> fs-4"></i></span>
                    <div class="text-start">
                        <div class="fw-semibold fs-5"><?= $fa($stats[$card[0]]) ?></div>
                        <small class="text-muted"><?= $card[1] ?></small>
                    </div>
                </div>
            </<?= $link ? 'a' : 'div' ?>>
        </div>
    <?php endforeach; ?>
</div>

<div class="border rounded p-3 mb-3 text-start">
    <form action="<?= Url::to([$editRoute]) ?>" method="get">
        <input type="hidden" name="_id" value="<?= Html::encode($courseId) ?>">
        <input type="hidden" name="tab" value="tab-id2">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2"><label class="form-label small">نام</label><?= Html::textInput('CM[first_name]', $value('first_name'), ['class' => 'form-control form-control-sm', 'maxlength' => 60]) ?></div>
            <div class="col-6 col-md-2"><label class="form-label small">نام خانوادگی</label><?= Html::textInput('CM[last_name]', $value('last_name'), ['class' => 'form-control form-control-sm', 'maxlength' => 60]) ?></div>
            <div class="col-6 col-md-2"><label class="form-label small">نام کاربری</label><?= Html::textInput('CM[username]', $value('username'), ['class' => 'form-control form-control-sm', 'maxlength' => 60, 'dir' => 'ltr']) ?></div>
            <div class="col-6 col-md-2"><label class="form-label small">کد ملی</label><?= Html::textInput('CM[national_code]', $value('national_code'), ['class' => 'form-control form-control-sm', 'maxlength' => 10, 'dir' => 'ltr', 'inputmode' => 'numeric', 'data-input' => 'digits']) ?></div>
            <div class="col-6 col-md-2"><label class="form-label small">وضعیت</label><?= Html::dropDownList('CM[status]', $value('status'), CourseMembersSearch::STATUSES, ['class' => 'form-select form-select-sm', 'prompt' => 'همه']) ?></div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bx bx-search"></i> جست‌وجو</button>
                <?php if ($model->hasFilters()): ?>
                    <a class="btn btn-sm btn-label-secondary" href="<?= $membersUrl() ?>" title="حذف فیلترها"><i class="bx bx-x"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-sm btn-label-success" href="<?= Url::to(array_merge(['course-members/report', '_id' => $courseId], empty($filterParams) ? [] : ['CM' => $filterParams])) ?>">
            <i class="bx bx-spreadsheet me-1"></i>خروجی اکسل<?= $model->hasFilters() ? ' (نتایج جست‌وجو)' : '' ?>
        </a>
        <?php if (CourseAccess::canManage($packageDetail)): ?>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bx bx-user-plus me-1"></i>افزودن عضو</button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-user">افزودن با مشخصات</a>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#from-excel">افزودن از فایل اکسل</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="from-excel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن اعضا از فایل اکسل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body text-start">
                <div class="alert alert-info small">
                    <ul class="mb-0 ps-3">
                        <li>ستون‌ها به ترتیب: نام، نام خانوادگی (فارسی)، نام کاربری (موبایل ۰۹… یا ایمیل)، کد ملی، نام و نام خانوادگی انگلیسی، جنسیت (۱ مرد، ۲ زن). ردیف اول عنوان ستون‌هاست.</li>
                        <li>کد ملی رمز اولیه‌ی حساب‌های جدید است. کاربری که از قبل وجود دارد با همان حساب به دوره و واحد اضافه می‌شود.</li>
                        <li>اگر حتی یک ردیف خطا داشته باشد، هیچ‌کس ثبت نمی‌شود.</li>
                        <li>سهم واحد از شهریه برای هر نفر از کیف پول کارگزار دوره کسر می‌شود و موجودی باید کافی باشد.</li>
                    </ul>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <input type="file" id="members-excel-file" class="form-control" accept=".xlsx,.xls" style="max-width: 380px">
                    <button type="button" class="btn btn-primary" id="members-excel-check"><i class="bx bx-search-alt me-1"></i>بررسی فایل</button>
                    <a class="btn btn-label-secondary" href="<?= Url::to(['course-members/template']) ?>"><i class="bx bx-download me-1"></i>دریافت فایل نمونه</a>
                </div>
                <div id="members-excel-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>
