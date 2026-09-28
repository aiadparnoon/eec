<?php
/**
 * تب «اعضا»ی دوره‌ی کوتاه‌مدت: آمار، جست‌وجو (نام، نام خانوادگی، نام کاربری، کد ملی، وضعیت)، خروجی اکسل
 * با همان فیلترها، و افزودن عضو (با مشخصات از طریق کارتخوان، یا از فایل اکسل با بررسی کامل).
 *
 * @var $this yii\web\View
 * @var $model app\models\CourseMembersSearch
 * @var $packageDetail app\models\Courses
 * @var $stats array CourseMembersSearch::stats()
 */
use app\components\CourseAccess;
use app\components\CourseMembersImport;
use app\components\UsersImport;
use app\models\Colleges;
use app\models\CourseMembersSearch;
use app\models\Users;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

$fa = function ($n) { return UsersImport::faDigits($n); };
$courseId = (string) $packageDetail->_id;
$value = function ($attribute) use ($model) {
    return is_scalar($model->$attribute) ? (string) $model->$attribute : '';
};
$filterParams = array_filter([
    'first_name' => $value('first_name'), 'last_name' => $value('last_name'), 'username' => $value('username'),
    'national_code' => $value('national_code'), 'status' => $value('status'),
], 'strlen');
$checkUrl = Json::htmlEncode(Url::to(['courses/members-check', '_id' => $courseId]));
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
        $link = $card[4] === null ? null : Url::to(array_merge(['courses/edit-course', '_id' => $courseId, 'tab' => 'tab-id2'], $card[4] === '' ? [] : ['CM[status]' => $card[4]]));
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
    <form action="<?= Url::to(['courses/edit-course']) ?>" method="get">
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
                    <a class="btn btn-sm btn-label-secondary" href="<?= Url::to(['courses/edit-course', '_id' => $courseId, 'tab' => 'tab-id2']) ?>" title="حذف فیلترها"><i class="bx bx-x"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <a class="btn btn-sm btn-label-success" href="<?= Url::to(array_merge(['courses/members_report', '_id' => $courseId], empty($filterParams) ? [] : ['CM' => $filterParams])) ?>">
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
                    <a class="btn btn-label-secondary" href="<?= Url::to(['courses/members-template']) ?>"><i class="bx bx-download me-1"></i>دریافت فایل نمونه</a>
                </div>
                <div id="members-excel-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<?php
//$allowDeadlineDate = true;
//if($packageDetail->deadline_date != null)
//{
//    $deadlineDate = str_replace('-','', $packageDetail->deadline_date);
//    if($deadlineDate < jdate('Ymd'))
//        $allowDeadlineDate = false;
//}
$allowDeadlineDate = true;
if($packageDetail->date != null)
{
    if($packageDetail->lessons != null)
    {
        if(count($packageDetail->lessons) > 0)
        {
            if(array_key_exists('date', $packageDetail->lessons[0]))
            {
                if(array_key_exists('from', $packageDetail->lessons[0]['date']) && array_key_exists('to', $packageDetail->lessons[0]['date']))
                {
                    $cal_date = $this->context->check_date($packageDetail->lessons[0]['date']['from'], $packageDetail->lessons[0]['date']['to']);
                    $cal_date = json_decode($cal_date);
                    $allowDeadlineDate = $cal_date->allowDeadlineDate;
                    $deadLineDate = $cal_date->deadlineTimestamp;
                }
            }
        }
    }
}
if($allowDeadlineDate)
{
    ?>
    <div class="modal fade" id="new-user" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت عضو جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $college = Colleges::findOne($packageDetail->college);
                    ?>
                    <?php $newUser = new Users(); ?>
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['packages/new_user'],
                            "method" => "post",
                            'options' => [
                                'class' => '',
                                'id' => 'add-new-member',
                                'enctype' => 'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <input name="packageId" value="<?= (string) $packageDetail->_id ?>" type="hidden">
                    <div class="row">
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">نام *</label>
                            <?= $form->field($newUser, 'first_name')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'id' => 'student-first-name',
                                    'required' => true
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">نام خانوادگی *</label>
                            <?=
                            $form->field($newUser, 'last_name')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'id' => 'student-last-name',
                                    'required' => true
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 col-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">
                                نام کاربری * (شماره همراه یا ایمیل)
                                <svg id="check-username-btn" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="none" stroke="#872c02" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 15l1.5 1.5m.433 2.525a1.48 1.48 0 1 1 2.092-2.092l2.042 2.042a1.48 1.48 0 1 1-2.092 2.092zM16.5 9.5a7 7 0 1 0-14 0a7 7 0 0 0 14 0" />
                                </svg>
                            </label>
                            <?= $form->field($newUser, 'username')->textInput([
                                'class' => 'form-control text-start',
                                'id' => 'student-username',
                                'required' => true,
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46) || (event.charCode >= 32 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)"
                            ])->label(false); ?>
                            <div id="username-check-result" class="form-text"></div>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">رمز عبور * </label>
                            <?= $form->field($newUser, 'password_hash')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                    <p>نکته: پرداخت در این قسمت بین دانشکده و کارگزار تسهیم خواهد شد</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <?php
                    if(array_key_exists('pos_account_id', $college->financial_info))
                        echo '<button type="submit" class="btn btn-primary pos-submit">پرداخت و ثبت عضو</button>';
                    else
                        echo '<button type="button" class="btn btn-danger pos-submit">به دلیل نداشتن پی سی پوز مجاز به ثبت عضو نمی باشید</button>';
                    ?>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

    <?php
}
else
{
    ?>
    <div class="modal fade" id="new-user" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت عضو جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">متاسفانه به دلیل اتمام تاریخ ثبت عضو شما قادر به ثبت عضو نمی باشید <br>آخرین مهلت ثبت عضو <?= jdate('Y/m/d', $deadLineDate) ?> بوده است</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
}
?>
