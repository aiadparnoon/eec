<?php
/**
 * مودال ثبت دوره‌ی کوتاه‌مدت. فیلدها و رفتار با صفحه‌ی ویرایش مشترک است (_form-fields + eec-course-form.js)؛
 * همه‌ی قواعد سمت سرور در ShortCourseForm تکرار می‌شوند.
 *
 * @var $this yii\web\View
 * @var $units array واحدهای قابل انتخاب (مدیر سیستم: همه؛ بقیه: واحد خودشان)
 * @var $servers array سرورهای فعال کلاس آنلاین [id => نام]
 * @var $capacityTypes array
 */
use app\components\ShortCourseForm;
use frontend\assets\CourseFormAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

CourseFormAsset::register($this);
$config = Json::htmlEncode(['optionsUrl' => Url::to(['unit-options']), 'minHours' => ShortCourseForm::MIN_HOURS, 'maxHours' => ShortCourseForm::MAX_HOURS, 'kind' => 'short']);
$this->registerJs(<<<JS
(function () {
    var ready = false;
    // فرم داخل مودال: وقتی مودال باز شد ساخته می‌شود تا تقویم و جست‌وجوی فهرست‌ها درست جا بیفتند
    $('#new-course').on('shown.bs.modal', function () {
        if (ready) return;
        ready = true;
        EecCourseForm.init(document.getElementById('course-form'), $config);
        EecValidate.bind(document.getElementById('course-form'), {ajax: true});
    });
})();
JS
, \yii\web\View::POS_END);
?>
<div class="modal fade" id="new-course" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <?php /* خود فرم .modal-content است تا بدنه‌ی مودال اسکرول بخورد */ ?>
        <?= Html::beginForm(['new'], 'post', ['enctype' => 'multipart/form-data', 'id' => 'course-form', 'class' => 'modal-content']) ?>
            <div class="modal-header">
                <h5 class="modal-title">ثبت دوره‌ی کوتاه‌مدت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body bg-lighter">
                <?= $this->render('_form-fields', ['kind' => 'short', 'model' => null, 'units' => $units, 'servers' => $servers, 'capacityTypes' => $capacityTypes, 'p' => 'new', 'readOnly' => false]) ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary" <?= empty($units) ? 'disabled' : '' ?>>ثبت دوره</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
