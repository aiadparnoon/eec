<?php
/**
 * فیلدهای مشترک فرم ثبت و ویرایش دوره (کوتاه‌مدت و میان‌مدت) — یک فایل برای هر چهار حالت تا
 * ترتیب، ظاهر و قواعد ثبت و ویرایش دقیقاً یکسان باشد. رفتار: assets/js/eec-course-form.js
 * قواعد سمت سرور: ShortCourseForm / PackageForm.
 *
 * @var $this yii\web\View
 * @var $kind string 'short' | 'package'
 * @var $model app\models\Courses|null  null = ثبت
 * @var $units array واحدهای قابل انتخاب [id => عنوان]
 * @var $servers array سرورهای فعال کلاس آنلاین [id => نام]
 * @var $capacityTypes array
 * @var $p string پیشوند شناسه‌ی فیلدها
 * @var $readOnly bool فقط نمایش (بدون امکان ویرایش)
 */
use app\components\CourseAccess;
use app\components\PackageForm;
use app\components\ShortCourseForm;
use app\components\UsersImport;
use app\models\ClassroomServers;
use mihaildev\ckeditor\CKEditor;
use yii\helpers\Html;

$isNew = $model === null || $model->isNewRecord;
$readOnly = !empty($readOnly);
$isAdmin = CourseAccess::isAdmin();
$isBroker = CourseAccess::role() === 'broker';
$v = function ($path, $default = '') use ($model) {
    if ($model === null)
        return $default;
    $value = $model;
    foreach (explode('.', $path) as $key) {
        if (is_array($value) && array_key_exists($key, $value))
            $value = $value[$key];
        else if (is_object($value) && isset($value->$key))
            $value = $value->$key;
        else
            return $default;
    }
    return is_scalar($value) ? (string) $value : $default;
};
$dis = $readOnly ? ['disabled' => true] : [];
$attrs = function (array $extra) use ($dis) { return Html::renderTagAttributes($extra + $dis); };

// تاریخ شروع/اتمام بعد از ثبت فقط توسط مدیر سیستم تغییر می‌کند (سرور هم همین را اعمال می‌کند)
$datesLocked = $readOnly || (!$isNew && !$isAdmin);
$dateFrom = $kind === 'short' ? $v('lessons.0.date.from') : $v('date.from');
$dateTo = $kind === 'short' ? $v('lessons.0.date.to') : $v('date.to');
$dateName = $kind === 'short' ? 'Courses[lessons][0][date]' : 'Courses[date]';
$minHours = $kind === 'short' ? ShortCourseForm::MIN_HOURS : PackageForm::MIN_HOURS;
$durationHelp = $kind === 'short'
    ? 'بین ' . UsersImport::faDigits(ShortCourseForm::MIN_HOURS) . ' تا ' . UsersImport::faDigits(ShortCourseForm::MAX_HOURS) . ' ساعت'
    : 'حداقل ' . UsersImport::faDigits(PackageForm::MIN_HOURS) . ' ساعت';

// واحد: فقط مدیر سیستم انتخاب می‌کند؛ بقیه واحد خودشان (ثبت) یا واحد فعلی دوره (ویرایش)
$unitValue = $v('college');
$canChooseUnit = CourseAccess::canChooseUnit() && !$readOnly;
if (!$canChooseUnit) {
    if ($isNew && count($units) === 1)
        $unitValue = (string) key($units);
    $unitTitle = isset($units[$unitValue]) ? $units[$unitValue] : (\app\components\UsersDirectory::collegeTitles()[$unitValue] ?? '');
}

$brokerId = $v('broker._id');
$contractId = $v('broker.contract');
$serverOptions = $servers + [ClassroomServers::NONE => 'هیچ‌کدام (برگزاری در سامانه‌ی دیگر / بدون کلاس آنلاین)'];
$serverValue = $v('classroom_server');
if ($serverValue !== '' && !isset($serverOptions[$serverValue]))
    $serverOptions[$serverValue] = 'سرور فعلی دوره (غیرفعال)';
$capacityType = $v('student_capacity.type');
if ($capacityType !== '' && !isset($capacityTypes[$capacityType]))
    $capacityTypes[$capacityType] = $capacityType === '1' ? 'نامحدود (قدیمی)' : 'نوع فعلی';
$contentTypes = ShortCourseForm::CONTENT_TYPES;
if ($v('content_type') === '3')
    $contentTypes['3'] = 'محتوامحور (قدیمی)';
$identity = CourseAccess::identity();
$canFreeAdd = $identity !== null && ($identity->role === 'user' || $identity->additional_access === true) && !$readOnly;
?>
<div class="card mb-4">
    <h5 class="card-header">مشخصات دوره</h5>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label" for="<?= $p ?>-title-fa">عنوان اصلی فارسی *</label><input type="text" id="<?= $p ?>-title-fa" name="Courses[title][main_fa]" value="<?= Html::encode($v('title.main_fa')) ?>" class="form-control" required maxlength="250" <?= $attrs([]) ?>></div>
            <div class="col-md-6"><label class="form-label" for="<?= $p ?>-title-en">عنوان اصلی انگلیسی</label><input type="text" id="<?= $p ?>-title-en" name="Courses[title][main_en]" value="<?= Html::encode($v('title.main_en')) ?>" class="form-control" maxlength="250" dir="ltr" data-input="en" <?= $attrs([]) ?>></div>
            <div class="col-md-6"><label class="form-label" for="<?= $p ?>-degree-fa">عنوان فارسی (داخل گواهی) *</label><input type="text" id="<?= $p ?>-degree-fa" name="Courses[title][degree_fa]" value="<?= Html::encode($v('title.degree_fa')) ?>" class="form-control" required maxlength="250" <?= $attrs([]) ?>></div>
            <div class="col-md-6"><label class="form-label" for="<?= $p ?>-degree-en">عنوان انگلیسی (داخل گواهی)</label><input type="text" id="<?= $p ?>-degree-en" name="Courses[title][degree_en]" value="<?= Html::encode($v('title.degree_en')) ?>" class="form-control" maxlength="250" dir="ltr" data-input="en" <?= $attrs([]) ?>></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><label class="form-label" for="<?= $p ?>-price">قیمت اصلی (تومان) *</label><input type="text" id="<?= $p ?>-price" name="Courses[price]" value="<?= Html::encode($v('price')) ?>" class="form-control" required inputmode="numeric" maxlength="12" data-input="digits" dir="ltr" <?= $attrs([]) ?>></div>
            <?php if (CourseAccess::canSetDiscount()): ?>
                <?php $discount = $v('discount_price'); ?>
                <div class="col-md-3"><label class="form-label" for="<?= $p ?>-discount">قیمت با تخفیف (تومان)</label><input type="text" id="<?= $p ?>-discount" name="Courses[discount_price]" value="<?= Html::encode($discount !== $v('price') ? $discount : '') ?>" class="form-control" inputmode="numeric" maxlength="12" data-input="digits" dir="ltr" <?= $attrs([]) ?>><small class="text-muted">خالی = بدون تخفیف (برابر قیمت اصلی)</small></div>
            <?php else: ?>
                <input type="hidden" id="<?= $p ?>-discount" value="">
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-duration">مدت زمان دوره (ساعت) *</label>
                <input type="text" id="<?= $p ?>-duration" data-role="duration" name="Courses[duration]" value="<?= Html::encode($v('duration')) ?>" class="form-control" required inputmode="numeric" maxlength="<?= $kind === 'short' ? 2 : 4 ?>" data-input="digits" dir="ltr" <?= $attrs([]) ?>>
                <small class="text-muted" data-role="duration-help" data-text="<?= Html::encode($durationHelp) ?>"><?= Html::encode($durationHelp) ?></small>
            </div>
            <div class="col-md-3"><label class="form-label" for="<?= $p ?>-time">زمان برگزاری *</label><input type="text" id="<?= $p ?>-time" name="Courses[time]" value="<?= Html::encode($v('time')) ?>" class="form-control" required maxlength="100" placeholder="مثلاً شنبه‌ها ۱۸ تا ۲۰" <?= $attrs([]) ?>></div>
            <div class="col-md-3"><label class="form-label" for="<?= $p ?>-place">محل برگزاری *</label><input type="text" id="<?= $p ?>-place" name="Courses[place]" value="<?= Html::encode($v('place')) ?>" class="form-control" required maxlength="200" <?= $attrs([]) ?>></div>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-content-type">نوع دوره *</label>
                <?= Html::dropDownList('Courses[content_type]', $v('content_type') ?: null, $contentTypes, ['id' => $p . '-content-type', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true] + $dis) ?>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-capacity">نوع ظرفیت *</label>
                <?= Html::dropDownList('Courses[student_capacity][type]', $capacityType ?: null, $capacityTypes, ['id' => $p . '-capacity', 'data-role' => 'capacity', 'class' => 'form-select', 'prompt' => 'انتخاب', 'required' => true] + $dis) ?>
            </div>
            <div class="col-md-3" data-role="capacity-number-wrap" style="display:none">
                <label class="form-label" for="<?= $p ?>-capacity-number">ظرفیت (نفر) *</label>
                <input type="text" id="<?= $p ?>-capacity-number" data-role="capacity-number" name="Courses[student_capacity][number]" value="<?= Html::encode($v('student_capacity.number')) ?>" data-label="ظرفیت" class="form-control" inputmode="numeric" maxlength="5" data-input="digits" dir="ltr" <?= $attrs([]) ?>>
            </div>
            <div class="col-md-6" data-role="contract-file-wrap" style="display:none">
                <label class="form-label" for="<?= $p ?>-contract-file">فایل قرارداد *</label>
                <input type="file" id="<?= $p ?>-contract-file" data-role="contract-file" data-has-file="<?= $v('contract_file') !== '' ? '1' : '' ?>" name="Courses[contract_file]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" <?= $attrs([]) ?>>
                <small class="text-muted"><?= $v('contract_file') !== '' ? 'فایل قرارداد قبلاً بارگذاری شده؛ فقط برای جایگزینی فایل جدید انتخاب کنید. ' : '' ?>PDF یا تصویر، حداکثر ۱۰ مگابایت</small>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <?php if ($kind === 'short'): ?>
                <div class="col-md-3"><label class="form-label" for="<?= $p ?>-hour">ساعت شروع دوره *</label><input type="text" id="<?= $p ?>-hour" name="Courses[lessons][0][date][time]" value="<?= Html::encode($v('lessons.0.date.time')) ?>" class="form-control" required placeholder="18:30" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" data-error-pattern="ساعت را به شکل ۱۸:۳۰ وارد کنید" dir="ltr" maxlength="5" <?= $attrs([]) ?>></div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-start">تاریخ شروع دوره *</label>
                <input type="text" id="<?= $p ?>-start" data-role="start" name="<?= $dateName ?>[from]" value="<?= Html::encode($dateFrom) ?>" class="form-control" required autocomplete="off" <?= $datesLocked ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-end">تاریخ اتمام دوره *</label>
                <input type="text" id="<?= $p ?>-end" data-role="end" name="<?= $dateName ?>[to]" value="<?= Html::encode($dateTo) ?>" class="form-control" required autocomplete="off" <?= $datesLocked ? 'disabled' : '' ?>>
                <small class="text-muted"><?= $datesLocked && !$readOnly ? 'پس از ثبت فقط مدیر سیستم تغییر می‌دهد' : 'حداقل یک روز بعد از شروع' ?></small>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="<?= $p ?>-deadline">آخرین مهلت ثبت عضو</label>
                <input type="text" id="<?= $p ?>-deadline" data-role="deadline" class="form-control" disabled placeholder="خودکار" value="<?= Html::encode(UsersImport::faDigits(str_replace('-', '/', $v('deadline_date')))) ?>">
                <small class="text-muted">یک‌چهارم ابتدای دوره (خودکار)</small>
            </div>
            <?php if ($kind === 'package'): ?>
                <div class="col-md-3">
                    <label class="form-label" for="<?= $p ?>-image">تصویر دوره</label>
                    <input type="file" id="<?= $p ?>-image" name="Courses[preview_image]" class="form-control" accept=".jpg,.jpeg,.png,.webp" <?= $attrs([]) ?>>
                    <small class="text-muted"><?= !$isNew && $v('preview_image') !== '' && $v('preview_image') !== 'default_course.png' ? 'تصویر فعلی حفظ می‌شود مگر فایل جدید انتخاب کنید؛ ' : '' ?>حداکثر ۲ مگابایت</small>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canFreeAdd || ($model !== null && $model->allow_free_add_user === true)): ?>
            <div class="form-check form-switch mb-4">
                <input type="hidden" name="Courses[allow_free_add_user]" value="0" <?= $canFreeAdd ? '' : 'disabled' ?>>
                <input class="form-check-input" type="checkbox" id="<?= $p ?>-free-add" name="Courses[allow_free_add_user]" value="1" <?= $model !== null && $model->allow_free_add_user === true ? 'checked' : '' ?> <?= $canFreeAdd ? '' : 'disabled' ?>>
                <label class="form-check-label" for="<?= $p ?>-free-add">افزودن عضو بدون کیف پول (فقط از فایل اکسل و تا پایان مهلت ثبت عضو)</label>
            </div>
        <?php endif; ?>

        <label class="form-label">توضیحات دوره</label>
        <?php if ($readOnly): ?>
            <div class="border rounded p-3 text-start"><?= \yii\helpers\HtmlPurifier::process((string) ($model !== null ? $model->description : '')) ?></div>
        <?php else: ?>
            <?= CKEditor::widget(['name' => 'Courses[description]', 'value' => $model !== null ? (string) $model->description : '', 'id' => $p . '-description', 'editorOptions' => ['preset' => 'standard', 'inline' => false]]) ?>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <h5 class="card-header"><?= $kind === 'short' ? 'واحد، کارگزار، درس و کلاس آنلاین' : 'واحد، کارگزار و کلاس آنلاین' ?></h5>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="<?= $p ?>-unit">واحد *</label>
                <?php if ($canChooseUnit): ?>
                    <?= Html::dropDownList('Courses[college]', $unitValue ?: null, $units, ['id' => $p . '-unit', 'data-role' => 'unit', 'class' => 'form-select', 'prompt' => 'انتخاب واحد', 'required' => true]) ?>
                    <?php if (!$isNew): ?><small class="text-muted">با تغییر واحد، کارگزار<?= $kind === 'short' ? '، درس و مدرس' : '' ?> را دوباره انتخاب کنید</small><?php endif; ?>
                <?php elseif ($unitValue === ''): ?>
                    <input type="text" class="form-control is-invalid" value="واحدی برای حساب شما تعریف نشده است" disabled>
                <?php else: ?>
                    <input type="text" class="form-control" value="<?= Html::encode($unitTitle) ?>" disabled>
                    <input type="hidden" id="<?= $p ?>-unit" data-role="unit" name="Courses[college]" value="<?= Html::encode($unitValue) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="<?= $p ?>-broker">کارگزار *</label>
                <select id="<?= $p ?>-broker" data-role="broker" name="Courses[broker][_id]" class="form-select" required data-placeholder="جست‌وجو و انتخاب کارگزار" data-value="<?= Html::encode($brokerId) ?>" <?= $isBroker ? 'data-force="1"' : '' ?> <?= $readOnly ? 'disabled' : '' ?>><option value="">ابتدا واحد را انتخاب کنید</option></select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="<?= $p ?>-contract">نوع قرارداد کارگزار *</label>
                <select id="<?= $p ?>-contract" data-role="contract" name="Courses[broker][contract]" class="form-select" required data-placeholder="انتخاب قرارداد" data-value="<?= Html::encode($contractId) ?>" data-label="قرارداد فعلی دوره" <?= $readOnly ? 'disabled' : '' ?>><option value="">ابتدا کارگزار را انتخاب کنید</option></select>
            </div>
            <?php if ($kind === 'short'): ?>
                <div class="col-md-4">
                    <label class="form-label" for="<?= $p ?>-lesson">درس دوره *</label>
                    <select id="<?= $p ?>-lesson" data-role="lesson" name="Courses[lessons][0][_id]" class="form-select" required data-placeholder="جست‌وجو و انتخاب درس" data-value="<?= Html::encode($v('lessons.0._id')) ?>" <?= $readOnly ? 'disabled' : '' ?>><option value="">ابتدا واحد را انتخاب کنید</option></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="<?= $p ?>-teacher">مدرس دوره *</label>
                    <select id="<?= $p ?>-teacher" data-role="teacher" name="Courses[lessons][0][teachers]" class="form-select" required data-placeholder="جست‌وجو و انتخاب مدرس" data-value="<?= Html::encode($v('lessons.0.teachers')) ?>" data-label="مدرس فعلی دوره" <?= $readOnly ? 'disabled' : '' ?>><option value="">ابتدا واحد را انتخاب کنید</option></select>
                </div>
                <div class="col-md-4" data-role="server-wrap" style="display:none">
                    <label class="form-label" for="<?= $p ?>-server">کلاس روی کدام سرور برگزار شود؟ *</label>
                    <?= Html::dropDownList('Courses[classroom_server]', $serverValue ?: null, $serverOptions, ['id' => $p . '-server', 'data-role' => 'server', 'class' => 'form-select', 'prompt' => 'انتخاب سرور'] + $dis) ?>
                </div>
                <div class="col-md-4" data-role="archive-wrap" style="display:none">
                    <label class="form-label" for="<?= $p ?>-archive">مخفی کردن آرشیو</label>
                    <?= Html::dropDownList('Courses[lessons][0][hide_archive]', $v('lessons.0.hide_archive') === '1' ? '1' : '0', ['0' => 'خیر', '1' => 'بله'], ['id' => $p . '-archive', 'class' => 'form-select'] + $dis) ?>
                </div>
            <?php else: ?>
                <div class="col-md-4">
                    <label class="form-label" for="<?= $p ?>-server">کلاس روی کدام سرور برگزار شود؟ *</label>
                    <?= Html::dropDownList('Courses[classroom_server]', $serverValue ?: null, $serverOptions, ['id' => $p . '-server', 'data-role' => 'server', 'class' => 'form-select', 'prompt' => 'انتخاب سرور', 'required' => true] + $dis) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
