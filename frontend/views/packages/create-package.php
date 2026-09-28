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
use app\models\ClassroomServers;
use frontend\assets\DateRangeAsset;
use frontend\assets\FormValidateAsset;
use frontend\assets\InputGuardAsset;
use frontend\assets\SelectSearchAsset;
use frontend\controllers\CourseMembersController;
use mihaildev\ckeditor\CKEditor;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'ثبت دوره‌ی میان‌مدت';
DateRangeAsset::register($this);
FormValidateAsset::register($this);
SelectSearchAsset::register($this);
InputGuardAsset::register($this);

$flash = Yii::$app->session->getFlash(CourseMembersController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$type'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 9000, escapeHtml: true});", \yii\web\View::POS_END);
}

$isAdmin = CourseAccess::isAdmin();
$isBroker = CourseAccess::role() === 'broker';
$fixedUnit = !CourseAccess::canChooseUnit() && count($units) === 1 ? [key($units), current($units)] : null;
$serverOptions = $servers + [ClassroomServers::NONE => 'هیچ‌کدام (برگزاری در سامانه‌ی دیگر / بدون کلاس آنلاین)'];
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

    // --- واحد → کارگزاران، مدرسان و دروس
    form.on('change', '#pkg-unit', function () {
        var id = $(this).val();
        $('#pkg-lessons').empty().trigger('change');
        fill($('#pkg-broker'), [], 'بدون کارگزار');
        fill($('#pkg-contract'), [], 'ابتدا کارگزار را انتخاب کنید');
        if (!id) return;
        $.getJSON(cfg.optionsUrl, {id: id}).done(function (data) {
            unitData = data;
            fill($('#pkg-broker'), data.brokers, data.brokers.length ? 'بدون کارگزار' : 'کارگزاری با قرارداد برای این واحد ثبت نشده');
            if (data.brokers.length === 1 && $('#pkg-broker').data('force')) $('#pkg-broker').val(data.brokers[0].id).trigger('change');
            var lessons = $('#pkg-lessons').empty();
            $.each(data.lessons, function (i, item) { lessons.append($('<option>').val(item.id).text(item.name)); });
            lessons.prop('disabled', data.lessons.length === 0).trigger('change');
        });
    });
    form.on('change', '#pkg-broker', function () {
        var id = $(this).val(), broker = null;
        $.each(unitData.brokers, function (i, b) { if (b.id === id) broker = b; });
        fill($('#pkg-contract'), broker ? $.map(broker.contracts, function (c) { return {id: c.id, name: c.title}; }) : [], id ? 'لطفاً قرارداد را انتخاب کنید' : 'ابتدا کارگزار را انتخاب کنید');
        $('#pkg-contract').prop('required', !!id);
    });

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

    // --- ظرفیت
    form.on('change', '#pkg-capacity', function () {
        var type = $(this).val();
        $('#pkg-capacity-number-wrap').toggle(type === '2');
        $('#pkg-capacity-number').prop('required', type === '2');
        $('#pkg-contract-file-wrap').toggle(type === '3');
        $('#pkg-contract-file').prop('required', type === '3');
    });
    form.on('input change', '#pkg-duration', function () {
        var v = parseInt(this.value, 10), msg = isNaN(v) ? 'مدت دوره را وارد کنید' : (v < cfg.minHours ? 'دوره‌ی میان‌مدت حداقل ' + fa(cfg.minHours) + ' ساعت است؛ دوره‌ی کوتاه‌تر را از بخش کوتاه‌مدت ثبت کنید' : '');
        this.setCustomValidity(msg === 'مدت دوره را وارد کنید' ? '' : msg);
        $('#pkg-duration-help').toggle(!msg);
    });

    // --- تاریخ دوره: اتمام حداقل یک روز بعد؛ تاریخ دروس و اقساط داخل بازه‌ی دوره
    if ($.fn.flatpickr) {
        var opts = {locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true};
        $('#pkg-start, #pkg-end').flatpickr($.extend({}, opts, {onChange: onRange}));
        EecDateRange.bind(document.getElementById('pkg-start'), document.getElementById('pkg-end'));
    }
    function onRange() {
        var r = courseRange();
        form.find('[data-f="from"], [data-f="to"], [data-f="deadline"]').each(function () {
            if (this._flatpickr) { this._flatpickr.set('minDate', r.from || null); this._flatpickr.set('maxDate', r.to || null); }
        });
        var s = $('#pkg-start')[0]._flatpickr, e = $('#pkg-end')[0]._flatpickr;
        if (s && e && s.selectedDates[0] && e.selectedDates[0]) {
            var days = Math.floor((e.selectedDates[0].getTime() - s.selectedDates[0].getTime()) / 86400000);
            if (days >= 1) {
                var d = new Date(s.selectedDates[0].getTime() + Math.floor(days / 4) * 86400000);
                $('#pkg-deadline').val(e.formatDate(typeof JDate === 'function' ? new JDate(d) : d, 'Y/m/d'));
                return;
            }
        }
        $('#pkg-deadline').val('');
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

    EecSelect.init(form[0]);
    // پیام خطای هر فیلد زیر همان فیلد؛ خطای سرور هم بدون از دست رفتن اطلاعات فرم
    EecValidate.bind(form[0], {ajax: true});
    var unit = $('#pkg-unit');
    if (unit.is('input') || (unit.find('option').length === 2 && !unit.val())) {
        if (!unit.is('input')) unit.val(unit.find('option:last').val());
        unit.trigger('change');
    }
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
    <div class="card mb-4">
        <h5 class="card-header">مشخصات دوره</h5>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6"><label class="form-label" for="pkg-title-fa">عنوان اصلی فارسی *</label><input type="text" id="pkg-title-fa" name="Courses[title][main_fa]" class="form-control" required maxlength="250"></div>
                <div class="col-md-6"><label class="form-label" for="pkg-title-en">عنوان اصلی انگلیسی</label><input type="text" id="pkg-title-en" name="Courses[title][main_en]" class="form-control" maxlength="250" dir="ltr" data-input="en"></div>
                <div class="col-md-6"><label class="form-label" for="pkg-degree-fa">عنوان فارسی (داخل گواهی) *</label><input type="text" id="pkg-degree-fa" name="Courses[title][degree_fa]" class="form-control" required maxlength="250"></div>
                <div class="col-md-6"><label class="form-label" for="pkg-degree-en">عنوان انگلیسی (داخل گواهی)</label><input type="text" id="pkg-degree-en" name="Courses[title][degree_en]" class="form-control" maxlength="250" dir="ltr" data-input="en"></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label" for="pkg-price">قیمت اصلی (تومان) *</label><input type="text" id="pkg-price" name="Courses[price]" class="form-control" required inputmode="numeric" maxlength="12" data-input="digits" dir="ltr"></div>
                <?php if (CourseAccess::canSetDiscount()): ?>
                    <div class="col-md-3"><label class="form-label" for="pkg-discount">قیمت با تخفیف (تومان)</label><input type="text" id="pkg-discount" name="Courses[discount_price]" class="form-control" inputmode="numeric" maxlength="12" data-input="digits" dir="ltr"><small class="text-muted">خالی = بدون تخفیف</small></div>
                <?php else: ?>
                    <input type="hidden" id="pkg-discount" value="">
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-duration">مدت زمان دوره (ساعت) *</label>
                    <input type="text" id="pkg-duration" name="Courses[duration]" class="form-control" required inputmode="numeric" maxlength="4" data-input="digits" dir="ltr">
                    <small class="text-muted" id="pkg-duration-help">حداقل <?= PackageForm::MIN_HOURS ?> ساعت</small>
                </div>
                <div class="col-md-3"><label class="form-label" for="pkg-time">زمان برگزاری *</label><input type="text" id="pkg-time" name="Courses[time]" class="form-control" required maxlength="100" placeholder="مثلاً پنجشنبه‌ها ۹ تا ۱۳"></div>
                <div class="col-md-3"><label class="form-label" for="pkg-place">محل برگزاری *</label><input type="text" id="pkg-place" name="Courses[place]" class="form-control" required maxlength="200"></div>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-content-type">نوع دوره *</label>
                    <?= Html::dropDownList('Courses[content_type]', null, ShortCourseForm::CONTENT_TYPES, ['id' => 'pkg-content-type', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true]) ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-capacity">نوع ظرفیت *</label>
                    <?= Html::dropDownList('Courses[student_capacity][type]', null, $capacityTypes, ['id' => 'pkg-capacity', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true]) ?>
                </div>
                <div class="col-md-3" id="pkg-capacity-number-wrap" style="display:none">
                    <label class="form-label" for="pkg-capacity-number">ظرفیت (نفر) *</label>
                    <input type="text" id="pkg-capacity-number" name="Courses[student_capacity][number]" data-label="ظرفیت" class="form-control" inputmode="numeric" maxlength="5" data-input="digits" dir="ltr">
                </div>
                <div class="col-md-6" id="pkg-contract-file-wrap" style="display:none">
                    <label class="form-label" for="pkg-contract-file">فایل قرارداد *</label>
                    <input type="file" id="pkg-contract-file" name="Courses[contract_file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted">PDF یا تصویر، حداکثر ۱۰ مگابایت</small>
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="form-label" for="pkg-start">تاریخ شروع دوره *</label><input type="text" id="pkg-start" name="Courses[date][from]" class="form-control" required autocomplete="off"></div>
                <div class="col-md-3"><label class="form-label" for="pkg-end">تاریخ اتمام دوره *</label><input type="text" id="pkg-end" name="Courses[date][to]" class="form-control" required autocomplete="off"><small class="text-muted">حداقل یک روز بعد از شروع</small></div>
                <div class="col-md-3"><label class="form-label" for="pkg-deadline">آخرین مهلت ثبت عضو</label><input type="text" id="pkg-deadline" class="form-control" disabled placeholder="خودکار"><small class="text-muted">یک‌چهارم ابتدای دوره</small></div>
                <div class="col-md-3"><label class="form-label" for="pkg-image">تصویر دوره</label><input type="file" id="pkg-image" name="Courses[preview_image]" class="form-control" accept=".jpg,.jpeg,.png,.webp"><small class="text-muted">حداکثر ۲ مگابایت</small></div>
            </div>
            <label class="form-label">توضیحات دوره</label>
            <?= CKEditor::widget(['name' => 'Courses[description]', 'editorOptions' => ['preset' => 'standard', 'inline' => false]]) ?>
        </div>
    </div>

    <div class="card mb-4">
        <h5 class="card-header">واحد، کارگزار و کلاس آنلاین</h5>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="pkg-unit">واحد *</label>
                    <?php if ($fixedUnit !== null): ?>
                        <input type="text" class="form-control" value="<?= Html::encode($fixedUnit[1]) ?>" disabled>
                        <input type="hidden" id="pkg-unit" name="Courses[college]" value="<?= Html::encode($fixedUnit[0]) ?>">
                    <?php elseif (empty($units)): ?>
                        <input type="text" class="form-control is-invalid" value="واحدی برای حساب شما تعریف نشده است" disabled>
                    <?php else: ?>
                        <?= Html::dropDownList('Courses[college]', null, $units, ['id' => 'pkg-unit', 'class' => 'form-select', 'prompt' => 'انتخاب واحد', 'required' => true]) ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-broker">کارگزار</label>
                    <select id="pkg-broker" name="Courses[broker][_id]" class="form-select" data-placeholder="بدون کارگزار" disabled <?= $isBroker ? 'data-force="1"' : '' ?>><option value="">ابتدا واحد را انتخاب کنید</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-contract">نوع قرارداد کارگزار</label>
                    <select id="pkg-contract" name="Courses[broker][contract]" class="form-select" data-placeholder="انتخاب قرارداد" disabled><option value="">ابتدا کارگزار را انتخاب کنید</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="pkg-server">کلاس روی کدام سرور برگزار شود؟ *</label>
                    <?= Html::dropDownList('Courses[classroom_server]', null, $serverOptions, ['id' => 'pkg-server', 'class' => 'form-select', 'prompt' => 'انتخاب سرور', 'required' => true]) ?>
                </div>
            </div>
        </div>
    </div>

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
