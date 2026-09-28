<?php
/**
 * فیلترهای دوره‌های کوتاه‌مدت (صورتجلسه بند ۵.۲: از تاریخ تا تاریخ، تاریخ ثبت/درخواست، وضعیت فرآیند، کارگزار، ...).
 *
 * @var $this yii\web\View
 * @var $model app\models\ShortCoursesSearch
 * @var $units array
 * @var $brokers array
 * @var $teachers array
 */
use app\components\CourseStatus;
use app\components\ShortCourseForm;
use yii\helpers\Html;
use yii\helpers\Url;

$advancedOpen = false;
foreach (['broker', 'teacher', 'content_type', 'start_from', 'start_to', 'created_from', 'created_to'] as $attribute)
    if ($model->$attribute !== null && $model->$attribute !== '')
        $advancedOpen = true;
$value = function ($attribute) use ($model) {
    return is_scalar($model->$attribute) ? (string) $model->$attribute : '';
};
$select = function ($attribute, $items, $prompt = 'همه', $extra = []) use ($value) {
    return Html::dropDownList('CS[' . $attribute . ']', $value($attribute), $items, array_merge(['id' => 'cs-' . $attribute, 'class' => 'form-select', 'prompt' => $prompt], $extra));
};
$date = function ($attribute, $placeholder) use ($value) {
    return Html::textInput('CS[' . $attribute . ']', $value($attribute), ['id' => 'cs-' . $attribute, 'class' => 'form-control cs-date', 'placeholder' => $placeholder, 'autocomplete' => 'off', 'dir' => 'ltr']);
};
\frontend\assets\SelectSearchAsset::register($this);
$this->registerJs(<<<JS
EecSelect.init(document.getElementById('course-filters'));
if ($.fn.flatpickr) { $('.cs-date').flatpickr({locale: 'fa', dateFormat: 'Y/m/d', disableMobile: true, allowInput: true}); }
JS
);
$contentTypes = ShortCourseForm::CONTENT_TYPES + ['3' => 'محتوامحور (قدیمی)'];
?>
<div class="card mb-4">
    <div class="card-body">
        <form action="<?= Url::to(['index']) ?>" method="get" id="course-filters">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="cs-q">جست‌وجو</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <?= Html::textInput('CS[q]', $value('q'), ['id' => 'cs-q', 'class' => 'form-control', 'placeholder' => 'عنوان دوره یا کد مجوز', 'maxlength' => 100, 'autocomplete' => 'off']) ?>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="cs-unit">واحد</label>
                    <?= $select('unit', $units, '', ['data-placeholder' => 'همه‌ی واحدها']) ?>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="cs-status">وضعیت</label>
                    <?= $select('status', CourseStatus::FILTERS) ?>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>اعمال</button>
                    <button class="btn btn-label-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cs-advanced" aria-expanded="<?= $advancedOpen ? 'true' : 'false' ?>" title="فیلترهای بیشتر"><i class="bx bx-slider-alt"></i></button>
                </div>
            </div>
            <div class="collapse <?= $advancedOpen ? 'show' : '' ?>" id="cs-advanced">
                <hr class="my-4">
                <div class="row g-3">
                    <?php if (!empty($brokers)): ?>
                        <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="cs-broker">کارگزار</label><?= $select('broker', $brokers, '', ['data-placeholder' => 'همه‌ی کارگزاران']) ?></div>
                    <?php endif; ?>
                    <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="cs-teacher">مدرس</label><?= $select('teacher', $teachers, '', ['data-placeholder' => 'همه‌ی مدرسان']) ?></div>
                    <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="cs-content_type">نوع دوره</label><?= $select('content_type', $contentTypes) ?></div>
                    <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="cs-sort_by">مرتب‌سازی</label><?= Html::dropDownList('CS[sort_by]', $value('sort_by'), ['newest' => 'جدیدترین ثبت', 'oldest' => 'قدیمی‌ترین ثبت', 'start' => 'تاریخ شروع دوره'], ['id' => 'cs-sort_by', 'class' => 'form-select']) ?></div>
                    <div class="col-6 col-lg-3"><label class="form-label" for="cs-start_from">شروع دوره از تاریخ</label><?= $date('start_from', '1404/01/01') ?></div>
                    <div class="col-6 col-lg-3"><label class="form-label" for="cs-start_to">تا تاریخ</label><?= $date('start_to', '1404/12/29') ?></div>
                    <div class="col-6 col-lg-3"><label class="form-label" for="cs-created_from">تاریخ ثبت/درخواست از</label><?= $date('created_from', '1404/01/01') ?></div>
                    <div class="col-6 col-lg-3"><label class="form-label" for="cs-created_to">تا تاریخ</label><?= $date('created_to', '1404/12/29') ?></div>
                    <div class="col-6 col-lg-3"><label class="form-label" for="cs-per_page">تعداد در صفحه</label><?= Html::dropDownList('CS[per_page]', $model->pageSize(), array_combine($model::PAGE_SIZES, $model::PAGE_SIZES), ['id' => 'cs-per_page', 'class' => 'form-select']) ?></div>
                </div>
            </div>
        </form>
    </div>
</div>
