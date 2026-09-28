<?php
/**
 * مودال ثبت دوره‌ی کوتاه‌مدت. همه‌ی قواعد سمت سرور (ShortCourseForm + مدل) هم تکرار می‌شوند؛
 * این‌جا فقط برای راهنمایی کاربر است.
 *
 * @var $this yii\web\View
 * @var $units array واحدهای قابل انتخاب (مدیر سیستم: همه؛ بقیه: واحد خودشان)
 * @var $servers array سرورهای فعال کلاس آنلاین [id => نام]
 * @var $capacityTypes array
 */
use app\components\CourseAccess;
use app\components\ShortCourseForm;
use app\models\ClassroomServers;
use frontend\assets\DateRangeAsset;
use frontend\assets\SelectSearchAsset;
use mihaildev\ckeditor\CKEditor;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$optionsUrl = Json::htmlEncode(Url::to(['unit-options']));
DateRangeAsset::register($this);
SelectSearchAsset::register($this);
\frontend\assets\FormValidateAsset::register($this);
$minHours = ShortCourseForm::MIN_HOURS;
$maxHours = ShortCourseForm::MAX_HOURS;
$this->registerJs(<<<JS
(function () {
    var form = $('#course-form');
    var unitData = {brokers: []};
    function fill(select, items, prompt) {
        select.empty().append($('<option>').val('').text(prompt));
        $.each(items, function (i, item) { select.append($('<option>').val(item.id).text(item.name)); });
        select.prop('disabled', items.length === 0).trigger('change.select2');
    }
    form.on('change', '#new-unit', function () {
        var id = $(this).val();
        fill($('#new-broker'), [], 'بدون کارگزار');
        fill($('#new-contract'), [], 'ابتدا کارگزار را انتخاب کنید');
        fill($('#new-lesson'), [], 'در حال بارگذاری…');
        $('#new-lesson').trigger('change');
        fill($('#new-teacher'), [], 'در حال بارگذاری…');
        if (!id) return;
        $.getJSON($optionsUrl, {id: id}).done(function (data) {
            unitData = data;
            fill($('#new-broker'), data.brokers, data.brokers.length ? 'بدون کارگزار' : 'کارگزاری برای این واحد ثبت نشده');
            $('#new-broker').prop('disabled', data.brokers.length === 0);
            if (data.brokers.length === 1 && $('#new-broker').data('force')) $('#new-broker').val(data.brokers[0].id).trigger('change');
            fill($('#new-lesson'), data.lessons, data.lessons.length ? 'لطفاً درس را انتخاب کنید' : 'درسی برای این واحد ثبت نشده');
            fill($('#new-teacher'), data.teachers, data.teachers.length ? 'لطفاً مدرس را انتخاب کنید' : 'مدرسی برای این واحد ثبت نشده');
        });
    });
    // سرور برگزاری کلاس بعد از انتخاب درس پرسیده می‌شود؛ «مخفی کردن آرشیو» فقط وقتی کلاس در سامانه است
    form.on('change', '#new-lesson', function () {
        var has = !!$(this).val();
        $('#new-server-wrap').toggle(has);
        $('#new-server').prop('required', has);
    });
    form.on('change', '#new-server', function () {
        var none = $(this).val() === 'none';
        $('#new-archive-wrap').toggle(!!$(this).val() && !none);
    });
    form.on('change', '#new-broker', function () {
        var id = $(this).val(), broker = null;
        $.each(unitData.brokers, function (i, b) { if (b.id === id) broker = b; });
        var contracts = broker ? $.map(broker.contracts, function (c) { return {id: c.id, name: c.title}; }) : [];
        fill($('#new-contract'), contracts, id ? 'لطفاً قرارداد را انتخاب کنید' : 'ابتدا کارگزار را انتخاب کنید');
        $('#new-contract').prop('required', !!id);
    });
    form.on('change', '#new-capacity-type', function () {
        var type = $(this).val();
        $('#new-capacity-number-wrap').toggle(type === '2');
        $('#new-capacity-number').prop('required', type === '2');
        $('#new-contract-file-wrap').toggle(type === '3');
        $('#new-contract-file').prop('required', type === '3');
    });
    form.on('input change', '#new-duration', function () {
        var v = parseInt(this.value, 10), msg = '';
        if (isNaN(v)) msg = 'مدت دوره را وارد کنید';
        else if (v < $minHours) msg = 'امکان ثبت دوره‌ی کمتر از $minHours ساعت نیست';
        else if (v > $maxHours) msg = 'دوره‌ی بیشتر از $maxHours ساعت را از بخش دوره‌های میان‌مدت ثبت کنید';
        this.setCustomValidity(msg);
        $('#new-duration-help').text(msg).toggleClass('text-danger', !!msg);
    });
    // تاریخ‌ها: مقدار ارسالی شمسی Y-m-d؛ پایان بعد از شروع؛ پیش‌نمایش مهلت ثبت عضو (شروع + یک‌چهارم دوره)
    if ($.fn.flatpickr) {
        var opts = {locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true};
        var end = $('#new-end').flatpickr($.extend({}, opts, {onChange: preview}));
        var start = $('#new-start').flatpickr($.extend({}, opts, {onChange: preview}));
        // اتمام حداقل یک روز بعد از شروع: روزهای نامجاز غیرفعال، مقدار نامعتبر پاک و خطا نمایش داده می‌شود
        EecDateRange.bind(document.getElementById('new-start'), document.getElementById('new-end'));
        function preview() {
            var s = start.selectedDates[0], e = end.selectedDates[0];
            if (!s || !e) { $('#new-deadline').val(''); return; }
            var days = Math.floor((e.getTime() - s.getTime()) / 86400000);
            if (days < 1) { $('#new-deadline').val(''); return; }
            var d = new Date(s.getTime() + Math.floor(days / 4) * 86400000);
            // تاریخ‌های این flatpickr شیء JDate هستند؛ قالب‌بندی شمسی با خود flatpickr
            $('#new-deadline').val(end.formatDate(typeof JDate === 'function' ? new JDate(d) : d, 'Y/m/d'));
        }
    }
    // همه‌ی فیلدهای انتخابی با جست‌وجو (مثلاً فهرست چندصدتایی دروس)
    EecSelect.init(document.getElementById('new-course'));
    EecValidate.bind(document.getElementById('course-form'), {ajax: true});
    $('#new-course').on('shown.bs.modal', function () {
        var unit = $('#new-unit');
        if (unit.is('input')) { if (!unit.data('loaded')) { unit.data('loaded', 1).trigger('change'); } return; }
        if (unit.find('option').length === 2 && !unit.val()) unit.val(unit.find('option:last').val()).trigger('change');
    });
})();
JS
);
$isBroker = CourseAccess::role() === 'broker';
$canChooseUnit = CourseAccess::canChooseUnit();
$fixedUnit = !$canChooseUnit && count($units) === 1 ? [key($units), current($units)] : null;
$serverOptions = $servers + [ClassroomServers::NONE => 'هیچ‌کدام (برگزاری در سامانه‌ی دیگر / بدون کلاس آنلاین)'];
?>
<div class="modal fade" id="new-course" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <?php /* خود فرم .modal-content است تا بدنه‌ی مودال اسکرول بخورد (form میان content و body اسکرول را خراب می‌کرد) */ ?>
        <?= Html::beginForm(['new'], 'post', ['enctype' => 'multipart/form-data', 'id' => 'course-form', 'class' => 'modal-content']) ?>
            <div class="modal-header">
                <h5 class="modal-title">ثبت دوره‌ی کوتاه‌مدت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <?php // ترتیب فیلدها مطابق فرم قبلی که واحدها به آن عادت دارند ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label" for="new-title-fa">عنوان اصلی فارسی *</label><input type="text" id="new-title-fa" name="Courses[title][main_fa]" class="form-control" required maxlength="250"></div>
                    <div class="col-md-6"><label class="form-label" for="new-title-en">عنوان اصلی انگلیسی</label><input type="text" id="new-title-en" name="Courses[title][main_en]" class="form-control" maxlength="250" dir="ltr" data-input="en"></div>
                    <div class="col-md-6"><label class="form-label" for="new-degree-fa">عنوان فارسی (داخل گواهی) *</label><input type="text" id="new-degree-fa" name="Courses[title][degree_fa]" class="form-control" required maxlength="250"></div>
                    <div class="col-md-6"><label class="form-label" for="new-degree-en">عنوان انگلیسی (داخل گواهی)</label><input type="text" id="new-degree-en" name="Courses[title][degree_en]" class="form-control" maxlength="250" dir="ltr" data-input="en"></div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label" for="new-price">قیمت اصلی (تومان) *</label><input type="text" id="new-price" name="Courses[price]" class="form-control" required inputmode="numeric" maxlength="12" data-input="digits" dir="ltr"></div>
                    <?php if (CourseAccess::canSetDiscount()): ?>
                        <div class="col-md-3"><label class="form-label" for="new-discount">قیمت با تخفیف (تومان)</label><input type="text" id="new-discount" name="Courses[discount_price]" class="form-control" inputmode="numeric" maxlength="12" data-input="digits" dir="ltr"><small class="text-muted">خالی = بدون تخفیف</small></div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label" for="new-duration">مدت زمان دوره (ساعت) *</label>
                        <input type="text" id="new-duration" name="Courses[duration]" class="form-control" required inputmode="numeric" maxlength="2" data-input="digits" dir="ltr">
                        <small class="text-muted" id="new-duration-help">بین <?= $minHours ?> تا <?= $maxHours ?> ساعت</small>
                    </div>
                    <div class="col-md-3"><label class="form-label" for="new-time">زمان برگزاری *</label><input type="text" id="new-time" name="Courses[time]" class="form-control" required maxlength="100" placeholder="مثلاً شنبه‌ها ۱۸ تا ۲۰"></div>
                    <div class="col-md-3"><label class="form-label" for="new-place">محل برگزاری *</label><input type="text" id="new-place" name="Courses[place]" class="form-control" required maxlength="200"></div>
                    <div class="col-md-3">
                        <label class="form-label" for="new-content-type">نوع دوره *</label>
                        <?= Html::dropDownList('Courses[content_type]', null, ShortCourseForm::CONTENT_TYPES, ['id' => 'new-content-type', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true]) ?>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="new-capacity-type">نوع ظرفیت *</label>
                        <?= Html::dropDownList('Courses[student_capacity][type]', null, $capacityTypes, ['id' => 'new-capacity-type', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true]) ?>
                    </div>
                    <div class="col-md-3" id="new-capacity-number-wrap" style="display:none">
                        <label class="form-label" for="new-capacity-number">ظرفیت (نفر) *</label>
                        <input type="text" id="new-capacity-number" name="Courses[student_capacity][number]" class="form-control" inputmode="numeric" maxlength="5" data-input="digits" dir="ltr">
                    </div>
                    <div class="col-md-6" id="new-contract-file-wrap" style="display:none">
                        <label class="form-label" for="new-contract-file">فایل قرارداد *</label>
                        <input type="file" id="new-contract-file" name="Courses[contract_file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="text-muted">PDF یا تصویر، حداکثر ۱۰ مگابایت</small>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="form-label" for="new-hour">ساعت شروع دوره *</label><input type="text" id="new-hour" name="Courses[lessons][0][date][time]" class="form-control" required placeholder="18:30" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" dir="ltr" maxlength="5"></div>
                    <div class="col-md-3"><label class="form-label" for="new-start">تاریخ شروع دوره *</label><input type="text" id="new-start" name="Courses[lessons][0][date][from]" class="form-control" required autocomplete="off"></div>
                    <div class="col-md-3"><label class="form-label" for="new-end">تاریخ اتمام دوره *</label><input type="text" id="new-end" name="Courses[lessons][0][date][to]" class="form-control" required autocomplete="off"><small class="text-muted">حداقل یک روز بعد از شروع</small></div>
                    <div class="col-md-3"><label class="form-label" for="new-deadline">آخرین مهلت ثبت عضو</label><input type="text" id="new-deadline" class="form-control" disabled placeholder="خودکار"><small class="text-muted">یک‌چهارم ابتدای دوره</small></div>
                </div>

                <div class="mb-4">
                    <label class="form-label">توضیحات دوره</label>
                    <?= CKEditor::widget(['name' => 'Courses[description]', 'editorOptions' => ['preset' => 'standard', 'inline' => false]]) ?>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="new-unit">واحد *</label>
                        <?php if ($fixedUnit !== null): ?>
                            <input type="text" class="form-control" value="<?= Html::encode($fixedUnit[1]) ?>" disabled>
                            <input type="hidden" id="new-unit" name="Courses[college]" value="<?= Html::encode($fixedUnit[0]) ?>">
                        <?php elseif (empty($units)): ?>
                            <input type="text" class="form-control is-invalid" value="واحدی برای حساب شما تعریف نشده است" disabled>
                        <?php else: ?>
                            <?= Html::dropDownList('Courses[college]', null, $units, ['id' => 'new-unit', 'class' => 'form-select', 'prompt' => 'انتخاب واحد', 'required' => true]) ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new-broker">کارگزار</label>
                        <select id="new-broker" name="Courses[broker][_id]" class="form-select" data-placeholder="بدون کارگزار" disabled <?= $isBroker ? 'data-force="1"' : '' ?>><option value="">ابتدا واحد را انتخاب کنید</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new-contract">نوع قرارداد کارگزار</label>
                        <select id="new-contract" name="Courses[broker][contract]" class="form-select" data-placeholder="انتخاب قرارداد" disabled><option value="">ابتدا کارگزار را انتخاب کنید</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new-lesson">درس دوره *</label>
                        <select id="new-lesson" name="Courses[lessons][0][_id]" class="form-select" data-placeholder="جست‌وجو و انتخاب درس" required disabled><option value="">ابتدا واحد را انتخاب کنید</option></select>
                    </div>
                    <div class="col-md-4" id="new-server-wrap" style="display:none">
                        <label class="form-label" for="new-server">کلاس روی کدام سرور برگزار شود؟ *</label>
                        <?= Html::dropDownList('Courses[classroom_server]', null, $serverOptions, ['id' => 'new-server', 'class' => 'form-select', 'prompt' => 'انتخاب سرور', 'required' => true]) ?>
                    </div>
                    <div class="col-md-4" id="new-archive-wrap" style="display:none">
                        <label class="form-label" for="new-archive">مخفی کردن آرشیو *</label>
                        <?= Html::dropDownList('Courses[lessons][0][hide_archive]', '0', ['0' => 'خیر', '1' => 'بله'], ['id' => 'new-archive', 'class' => 'form-select']) ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="new-teacher">مدرس دوره *</label>
                        <select id="new-teacher" name="Courses[lessons][0][teachers]" class="form-select" data-placeholder="جست‌وجو و انتخاب مدرس" required disabled><option value="">ابتدا واحد را انتخاب کنید</option></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary" <?= empty($units) ? 'disabled' : '' ?>>ثبت دوره</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
