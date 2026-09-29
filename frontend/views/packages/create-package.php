<?php
/**
 * ثبت دوره‌ی میان‌مدت. همه‌ی قواعد سمت سرور در PackageForm تکرار می‌شوند؛ این‌جا برای راهنمایی کاربر است.
 *
 * @var $this yii\web\View
 * @var $units array واحدهای قابل انتخاب (مدیر سیستم: همه؛ بقیه: واحد خودشان)
 * @var $servers array سرورهای فعال کلاس آنلاین
 * @var $capacityTypes array
 */
use app\components\CourseAccess;
use app\components\PackageForm;
use app\components\ShortCourseForm;
use frontend\controllers\CourseMembersController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'ثبت دوره‌ی میان‌مدت';
\frontend\assets\CourseFormAsset::register($this);

$flash = Yii::$app->session->getFlash(CourseMembersController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$type'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 9000, escapeHtml: true});", \yii\web\View::POS_END);
}

$isAdmin = CourseAccess::isAdmin();
$config = Json::htmlEncode([
    'optionsUrl' => Url::to(['unit-options']),
    'minHours' => PackageForm::MIN_HOURS,
    'maxInstallments' => PackageForm::MAX_INSTALLMENTS,
]);
$this->registerJs(<<<JS
(function () {
    var cfg = $config, form = $('#package-form'), unitData = {brokers: [], teachers: [], lessons: []};
    var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
    var money = function (n) { return fa(Number(n || 0).toLocaleString('en-US')); };
    var digits = function (v) { return parseInt(String(v || '').replace(/[^\d]/g, ''), 10) || 0; };
    var esc = function (t) { return $('<div>').text(t).html(); };
    function fill(select, items, prompt) {
        select.empty().append($('<option>').val('').text(prompt));
        $.each(items, function (i, item) { select.append($('<option>').val(item.id).text(item.name)); });
        select.prop('disabled', items.length === 0).trigger('change.select2');
    }
    function courseRange() { return {from: $('#pkg-start').val(), to: $('#pkg-end').val()}; }
    function datePicker(input, bounds) {
        if (!$.fn.flatpickr) return;
        if (input._flatpickr) input._flatpickr.destroy();
        $(input).flatpickr({locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true, minDate: bounds.from || null, maxDate: bounds.to || null});
    }

    // --- واحد، کارگزار، قرارداد، ظرفیت، مدت و تاریخ‌ها: رفتار مشترک با صفحه‌ی ویرایش (eec-course-form.js)
    form.on('eec:unit-options', function (e, data) {
        unitData = data;
        var lessons = $('#pkg-lessons').empty();
        $.each(data.lessons, function (i, item) { lessons.append($('<option>').val(item.id).text(item.name)); });
        lessons.prop('disabled', data.lessons.length === 0).trigger('change');
    });
    form.on('eec:range', onRange);

    // --- دروس انتخاب‌شده → کارت هر درس
    form.on('change', '#pkg-lessons', function () {
        var selected = $(this).val() || [], box = $('#pkg-lesson-cards');
        box.find('.js-lesson').each(function () { if (selected.indexOf($(this).data('id')) < 0) $(this).remove(); });
        $.each(selected, function (i, id) {
            if (box.find('.js-lesson[data-id="' + id + '"]').length) return;
            var title = $('#pkg-lessons option[value="' + id + '"]').text();
            var teachers = '<option value="">جست‌وجو و انتخاب مدرس</option>' + $.map(unitData.teachers, function (t) { return '<option value="' + esc(t.id) + '">' + esc(t.name) + '</option>'; }).join('');
            var card = $('<div class="col-md-6 col-xl-4 js-lesson"><div class="border rounded p-3 h-100">' +
                '<h6 class="mb-3"><i class="bx bx-book-open me-1"></i><span class="js-title"></span></h6>' +
                '<input type="hidden" class="js-id">' +
                '<div class="mb-2"><label class="form-label small">مدرس *</label><select class="form-select js-teacher" required>' + teachers + '</select></div>' +
                '<div class="row g-2"><div class="col-6"><label class="form-label small">تاریخ شروع *</label><input type="text" class="form-control js-from" data-f="from" required autocomplete="off"></div>' +
                '<div class="col-6"><label class="form-label small">تاریخ اتمام *</label><input type="text" class="form-control js-to" data-f="to" required autocomplete="off"></div>' +
                '<div class="col-6"><label class="form-label small">ساعت شروع *</label><input type="text" class="form-control js-time" required data-error-pattern="ساعت را به شکل ۱۸:۳۰ وارد کنید" placeholder="18:30" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" dir="ltr" maxlength="5"></div>' +
                '<div class="col-6"><label class="form-label small">مدت (ساعت) *</label><input type="text" class="form-control js-hours" required inputmode="numeric" data-input="digits" dir="ltr" maxlength="3"></div>' +
                '<div class="col-12"><label class="form-label small">مخفی کردن آرشیو</label><select class="form-select js-archive"><option value="0">خیر</option><option value="1">بله</option></select></div></div>' +
                '</div></div>');
            card.attr('data-id', id).find('.js-title').text(title);
            card.find('.js-id').val(id);
            box.append(card);
            EecSelect.init(card[0]);
            card.find('[data-f="from"], [data-f="to"]').each(function () { datePicker(this, courseRange()); });
            // اتمام هر درس حداقل یک روز بعد از شروع آن (سرور هم بررسی می‌کند)
            EecDateRange.bind(card.find('[data-f="from"]')[0], card.find('[data-f="to"]')[0], 'تاریخ اتمام درس باید حداقل یک روز بعد از تاریخ شروع آن باشد');
        });
        box.find('.js-lesson').each(function (i) {
            var c = $(this);
            c.find('.js-id').attr('name', 'Courses[lessons][' + i + '][_id]');
            c.find('.js-teacher').attr('name', 'Courses[lessons][' + i + '][teachers]');
            // فقط فیلد اصلی (flatpickr یک فیلد نمایشی با همان کلاس‌ها می‌سازد)
            c.find('[data-f="from"]').attr('name', 'Courses[lessons][' + i + '][date][from]');
            c.find('[data-f="to"]').attr('name', 'Courses[lessons][' + i + '][date][to]');
            c.find('.js-time').attr('name', 'Courses[lessons][' + i + '][date][time]');
            c.find('.js-hours').attr('name', 'Courses[lessons][' + i + '][date][duration]');
            c.find('.js-archive').attr('name', 'Courses[lessons][' + i + '][hide_archive]');
        });
        $('#pkg-lessons-empty').toggle(selected.length === 0);
    });

    // بازه‌ی دوره: تاریخ دروس و سررسید اقساط داخل بازه
    function onRange() {
        var r = courseRange();
        form.find('[data-f="from"], [data-f="to"], [data-f="deadline"]').each(function () {
            if (this._flatpickr) { this._flatpickr.set('minDate', r.from || null); this._flatpickr.set('maxDate', r.to || null); }
        });
    }

    // --- شرایط اقساطی: مجموع = شهریه‌ی قابل پرداخت، سررسید داخل بازه‌ی دوره
    function payable() { var d = digits($('#pkg-discount').val()); return d > 0 ? d : digits($('#pkg-price').val()); }
    function total() {
        var rows = form.find('.js-installment'), sum = digits($('#pkg-prepayment').val());
        rows.each(function () { sum += digits($(this).find('.js-amount').val()); });
        var active = rows.length > 0 || digits($('#pkg-prepayment').val()) > 0, diff = payable() - sum, box = $('#pkg-installments-total');
        box.toggle(active);
        box.find('.js-sum').text(money(sum));
        box.find('.js-payable').text(money(payable()));
        box.find('.js-diff').text(diff === 0 ? 'برابر با شهریه' : (diff > 0 ? 'کسری ' + money(diff) : 'مازاد ' + money(-diff)) + ' تومان');
        box.removeClass('alert-success alert-danger').addClass(diff === 0 ? 'alert-success' : 'alert-danger');
        var bad = active && diff !== 0;
        $('#pkg-prepayment')[0].setCustomValidity(bad ? 'مجموع پیش‌پرداخت و اقساط باید دقیقاً برابر شهریه باشد (' + (diff > 0 ? 'کسری ' + money(diff) : 'مازاد ' + money(-diff)) + ' تومان)' : '');
        if ($('#pkg-prepayment').hasClass('is-invalid')) EecValidate.validateField($('#pkg-prepayment')[0]);
    }
    form.on('click', '.js-add-installment', function () {
        if (form.find('.js-installment').length >= cfg.maxInstallments) return;
        var row = $($('#pkg-installment-template').html());
        $('#pkg-installments').append(row);
        datePicker(row.find('[data-f="deadline"]')[0], courseRange());
        renumber(); total();
    });
    form.on('click', '.js-remove-installment', function () { $(this).closest('.js-installment').remove(); renumber(); total(); });
    function renumber() {
        form.find('.js-installment').each(function (i) {
            $(this).find('.js-number').text(fa(i + 1));
            $(this).find('[data-f="deadline"]').attr('name', 'Courses[installments][' + i + '][deadline]');
            $(this).find('.js-amount').attr('name', 'Courses[installments][' + i + '][amount]');
        });
        $('#pkg-prepayment').prop('required', form.find('.js-installment').length > 0);
    }
    form.on('input change', '#pkg-price, #pkg-discount, #pkg-prepayment, .js-amount', total);

    EecCourseForm.init(form[0], {optionsUrl: cfg.optionsUrl, minHours: cfg.minHours, kind: 'package'});
    // پیام خطای هر فیلد زیر همان فیلد؛ خطای سرور هم بدون از دست رفتن اطلاعات فرم
    EecValidate.bind(form[0], {ajax: true});
    total();
})();
JS
, \yii\web\View::POS_END);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb" class="d-flex justify-content-between align-items-center mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="<?= Url::to(['index']) ?>">دوره‌های میان‌مدت</a></li>
            <li class="breadcrumb-item active">ثبت دوره‌ی جدید</li>
        </ol>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-sm btn-label-secondary">بازگشت</a>
    </nav>

    <?= Html::beginForm(['new'], 'post', ['enctype' => 'multipart/form-data', 'id' => 'package-form']) ?>
    <?= $this->render('@frontend/views/courses/_form-fields', ['kind' => 'package', 'model' => null, 'units' => $units, 'servers' => $servers, 'capacityTypes' => $capacityTypes, 'p' => 'pkg', 'readOnly' => false]) ?>

    <div class="card mb-4">
        <h5 class="card-header">دروس دوره</h5>
        <div class="card-body">
            <label class="form-label" for="pkg-lessons">دروس *</label>
            <select id="pkg-lessons" class="form-select" multiple data-placeholder="جست‌وجو و انتخاب دروس" required data-error-required="حداقل یک درس برای دوره انتخاب کنید" data-error-for="lessons-select" disabled></select>
            <small class="text-muted d-block mb-3">برای هر درس مدرس، تاریخ‌ها (داخل بازه‌ی دوره)، ساعت و مدت را وارد کنید.</small>
            <div class="text-muted" id="pkg-lessons-empty">هنوز درسی انتخاب نشده است.</div>
            <div class="row g-3" id="pkg-lesson-cards"></div>
        </div>
    </div>

    <div class="card mb-4">
        <h5 class="card-header">شرایط اقساطی <small class="text-muted">(اختیاری)</small></h5>
        <div class="card-body">
            <p class="text-muted small">اگر دوره فقط نقدی است این بخش را خالی بگذارید. مجموع پیش‌پرداخت و اقساط باید دقیقاً برابر شهریه‌ی قابل پرداخت باشد و سررسید اقساط بین تاریخ شروع و اتمام دوره (به ترتیب) باشد.</p>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4"><label class="form-label" for="pkg-prepayment">مبلغ پیش‌پرداخت (تومان)</label><input type="text" id="pkg-prepayment" name="Courses[prepayment_installments]" class="form-control" inputmode="numeric" maxlength="12" data-input="digits" dir="ltr"></div>
                <div class="col-md-4"><button type="button" class="btn btn-label-primary js-add-installment"><i class="bx bx-plus me-1"></i>افزودن قسط</button></div>
            </div>
            <div id="pkg-installments"></div>
            <div class="alert mt-3" id="pkg-installments-total" data-error-for="installments-total" style="display:none">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>مجموع پیش‌پرداخت و اقساط: <b class="js-sum"></b> تومان</span>
                    <span>شهریه: <b class="js-payable"></b> تومان — <b class="js-diff"></b></span>
                </div>
            </div>
            <template id="pkg-installment-template">
                <div class="row g-2 align-items-end mb-2 js-installment">
                    <div class="col-auto"><span class="badge bg-label-primary">قسط <span class="js-number"></span></span></div>
                    <div class="col-md-4"><label class="form-label small">تاریخ سررسید *</label><input type="text" class="form-control js-deadline" data-f="deadline" required autocomplete="off"></div>
                    <div class="col-md-4"><label class="form-label small">مبلغ (تومان) *</label><input type="text" class="form-control js-amount" required inputmode="numeric" data-input="digits" dir="ltr" maxlength="12"></div>
                    <div class="col-auto"><button type="button" class="btn btn-icon btn-label-danger js-remove-installment" title="حذف قسط"><i class="bx bx-trash"></i></button></div>
                </div>
            </template>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-5">
        <a href="<?= Url::to(['index']) ?>" class="btn btn-label-secondary">انصراف</a>
        <?php if (!$isAdmin): ?>
            <button type="submit" name="draft" value="1" class="btn btn-label-primary">ذخیره‌ی پیش‌نویس</button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary" <?= empty($units) ? 'disabled' : '' ?>><?= $isAdmin ? 'ثبت و فعال‌سازی دوره' : 'ثبت و ارسال برای بررسی' ?></button>
    </div>
    <?= Html::endForm() ?>
</div>
