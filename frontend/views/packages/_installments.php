<?php
/**
 * تب «اقساط» دوره‌ی میان‌مدت: نمایش و ویرایش کامل شرایط اقساطی در یک فرم.
 *
 * قواعد (PackageForm::checkPlan، سمت سرور هم بررسی می‌شود):
 *  - مجموع پیش‌پرداخت و اقساط دقیقاً برابر شهریه‌ی قابل پرداخت دوره.
 *  - سررسید هر قسط بین تاریخ شروع و اتمام دوره و به ترتیب.
 *
 * @var $this yii\web\View
 * @var $model app\models\Courses
 * @var $allowEdit bool
 */
use app\components\CourseAccess;
use app\components\PackageForm;
use app\components\UsersImport;
use yii\helpers\Html;
use yii\helpers\Json;

\frontend\assets\FormValidateAsset::register($this);

$fa = function ($n) { return UsersImport::faDigits($n); };
$money = function ($n) { return UsersImport::faDigits(number_format((float) $n)); };
$plan = is_array($model->installments) ? array_values($model->installments) : [];
$prepayment = (string) $model->prepayment_installments;
$payable = PackageForm::payable($model);
$sum = ctype_digit($prepayment) ? (int) $prepayment : 0;
foreach ($plan as $row)
    if (is_array($row) && isset($row['amount']) && ctype_digit((string) $row['amount']))
        $sum += (int) $row['amount'];
$from = is_array($model->date) && isset($model->date['from']) ? (string) $model->date['from'] : '';
$to = is_array($model->date) && isset($model->date['to']) ? (string) $model->date['to'] : '';
$inUse = PackageForm::planInUse($model);
$canEditPlan = $allowEdit && CourseAccess::canManage($model) && (!$inUse || CourseAccess::isAdmin());

$config = Json::htmlEncode(['payable' => $payable, 'from' => $from, 'to' => $to]);
$this->registerJs(<<<JS
(function () {
    var cfg = $config, form = $('#installments-plan-form');
    if (!form.length) return;
    var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
    var money = function (n) { return fa(Number(n || 0).toLocaleString('en-US')); };
    var digits = function (v) { return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; };
    function picker(input) {
        if (!$.fn.flatpickr || input._flatpickr) return;
        // تاریخ سررسید فقط بین شروع و اتمام دوره قابل انتخاب است
        $(input).flatpickr({locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true, minDate: cfg.from || null, maxDate: cfg.to || null});
    }
    function total() {
        var sum = digits(form.find('[name="prepayment_installments"]').val());
        form.find('.js-amount').each(function () { sum += digits(this.value); });
        var diff = cfg.payable - sum, box = $('#installments-total');
        box.find('.js-sum').text(money(sum));
        box.find('.js-diff').text(diff === 0 ? 'برابر با شهریه' : (diff > 0 ? 'کسری ' + money(diff) : 'مازاد ' + money(-diff)) + ' تومان');
        box.removeClass('alert-success alert-danger').addClass(diff === 0 ? 'alert-success' : 'alert-danger');
        // مجموع نابرابر: پیام زیر فیلد پیش‌پرداخت (کسری/مازاد) و جلوگیری از ذخیره
        var pre = form.find('[name="prepayment_installments"]')[0], active = form.find('.js-row').length > 0;
        pre.required = active;
        pre.setCustomValidity(active && diff !== 0 ? 'مجموع پیش‌پرداخت و اقساط باید دقیقاً برابر شهریه باشد (' + (diff > 0 ? 'کسری ' + money(diff) : 'مازاد ' + money(-diff)) + ' تومان)' : '');
        if ($(pre).hasClass('is-invalid')) EecValidate.validateField(pre);
    }
    function renumber() {
        form.find('.js-row').each(function (i) {
            $(this).find('.js-number').text(fa(i + 1));
            $(this).find('[data-f="deadline"]').attr('name', 'installments[' + i + '][deadline]');
            $(this).find('.js-amount').attr('name', 'installments[' + i + '][amount]');
        });
    }
    form.on('click', '.js-add', function () {
        var row = $($('#installment-row-template').html());
        form.find('.js-rows').append(row);
        picker(row.find('[data-f="deadline"]')[0]);
        renumber(); total();
    });
    form.on('click', '.js-remove', function () { $(this).closest('.js-row').remove(); renumber(); total(); });
    form.on('input change', 'input', total);
    form.on('click', '.js-clear', function () {
        form.find('.js-rows').empty();
        form.find('[name="prepayment_installments"]').val('');
        renumber(); total();
    });
    form.find('[data-f="deadline"]').each(function () { picker(this); });
    renumber(); total();
    EecValidate.bind(form[0], {ajax: true});
})();
JS
, \yii\web\View::POS_END);
?>
<div class="text-start">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><small class="text-muted d-block">شهریه‌ی قابل پرداخت</small><b><?= $money($payable) ?></b> تومان</div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><small class="text-muted d-block">بازه‌ی دوره</small><b dir="ltr"><?= $fa(str_replace('-', '/', $from)) ?> — <?= $fa(str_replace('-', '/', $to)) ?></b></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><small class="text-muted d-block">نوع پرداخت</small><b><?= empty($plan) ? 'فقط نقدی' : 'نقدی یا اقساطی (' . $fa(count($plan)) . ' قسط)' ?></b></div></div>
        <div class="col-6 col-md-3"><div class="border rounded p-3 h-100 <?= !empty($plan) && $sum !== $payable ? 'border-danger' : '' ?>"><small class="text-muted d-block">مجموع پیش‌پرداخت و اقساط</small><b class="<?= !empty($plan) && $sum !== $payable ? 'text-danger' : '' ?>"><?= empty($plan) ? '—' : $money($sum) . ' تومان' ?></b></div></div>
    </div>

    <?php if (!empty($plan) && $sum !== $payable): ?>
        <div class="alert alert-danger"><i class="bx bx-error me-1"></i>شرایط اقساطی فعلی با شهریه‌ی دوره برابر نیست؛ لطفاً آن را اصلاح کنید.</div>
    <?php endif; ?>
    <?php if ($inUse): ?>
        <div class="alert alert-warning"><i class="bx bx-lock me-1"></i>دانشپذیرانی با همین شرایط اقساطی ثبت‌نام کرده‌اند<?= CourseAccess::isAdmin() ? '؛ تغییر شرایط فقط روی ثبت‌نام‌های بعدی اثر دارد.' : '؛ تغییر شرایط فقط توسط مدیر سیستم ممکن است.' ?></div>
    <?php endif; ?>

    <?php if (!$canEditPlan): ?>
        <?php if (!empty($plan)): ?>
            <table class="table table-sm">
                <thead><tr><th>#</th><th>سررسید</th><th>مبلغ (تومان)</th></tr></thead>
                <tbody>
                <tr><td>—</td><td>پیش‌پرداخت (هنگام ثبت‌نام)</td><td><?= $money($prepayment) ?></td></tr>
                <?php foreach ($plan as $i => $row): ?>
                    <tr><td><?= $fa($i + 1) ?></td><td><?= $fa(str_replace('-', '/', isset($row['deadline']) ? (string) $row['deadline'] : '')) ?></td><td><?= $money(isset($row['amount']) ? $row['amount'] : 0) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-muted">این دوره شرایط اقساطی ندارد.</div>
        <?php endif; ?>
    <?php else: ?>
        <?= Html::beginForm(['installments-plan', '_id' => (string) $model->_id], 'post', ['id' => 'installments-plan-form']) ?>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label">مبلغ پیش‌پرداخت (تومان)</label>
                    <input type="text" name="prepayment_installments" data-label="مبلغ پیش‌پرداخت" class="form-control" value="<?= Html::encode($prepayment) ?>" inputmode="numeric" data-input="digits" dir="ltr" maxlength="12">
                </div>
                <div class="col-md-8 d-flex gap-2">
                    <button type="button" class="btn btn-label-primary js-add"><i class="bx bx-plus me-1"></i>افزودن قسط</button>
                    <button type="button" class="btn btn-label-secondary js-clear"><i class="bx bx-x me-1"></i>حذف شرایط اقساطی (فقط نقدی)</button>
                </div>
            </div>
            <div class="js-rows">
                <?php foreach ($plan as $i => $row): ?>
                    <div class="row g-2 align-items-end mb-2 js-row">
                        <div class="col-auto"><span class="badge bg-label-primary">قسط <span class="js-number"><?= $fa($i + 1) ?></span></span></div>
                        <div class="col-md-4"><label class="form-label small">تاریخ سررسید</label><input type="text" class="form-control js-deadline" data-f="deadline" value="<?= Html::encode(isset($row['deadline']) ? (string) $row['deadline'] : '') ?>" autocomplete="off" required></div>
                        <div class="col-md-4"><label class="form-label small">مبلغ (تومان)</label><input type="text" class="form-control js-amount" value="<?= Html::encode(isset($row['amount']) ? (string) $row['amount'] : '') ?>" inputmode="numeric" data-input="digits" dir="ltr" maxlength="12" required></div>
                        <div class="col-auto"><button type="button" class="btn btn-icon btn-label-danger js-remove" title="حذف قسط"><i class="bx bx-trash"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="alert mt-3" id="installments-total" data-error-for="installments-total">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>مجموع پیش‌پرداخت و اقساط: <b class="js-sum"></b> تومان</span>
                    <span>شهریه: <b><?= $money($payable) ?></b> تومان — <b class="js-diff"></b></span>
                </div>
            </div>
            <small class="text-muted d-block mb-3">سررسید اقساط فقط بین تاریخ شروع و اتمام دوره و به ترتیب قابل انتخاب است. مجموع باید دقیقاً برابر شهریه باشد.</small>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>ذخیره‌ی شرایط اقساطی</button>
        <?= Html::endForm() ?>
        <template id="installment-row-template">
            <div class="row g-2 align-items-end mb-2 js-row">
                <div class="col-auto"><span class="badge bg-label-primary">قسط <span class="js-number"></span></span></div>
                <div class="col-md-4"><label class="form-label small">تاریخ سررسید</label><input type="text" class="form-control js-deadline" data-f="deadline" autocomplete="off" required></div>
                <div class="col-md-4"><label class="form-label small">مبلغ (تومان)</label><input type="text" class="form-control js-amount" inputmode="numeric" data-input="digits" dir="ltr" maxlength="12" required></div>
                <div class="col-auto"><button type="button" class="btn btn-icon btn-label-danger js-remove" title="حذف قسط"><i class="bx bx-trash"></i></button></div>
            </div>
        </template>
    <?php endif; ?>
</div>
