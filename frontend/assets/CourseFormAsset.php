<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * فرم مشترک ثبت/ویرایش دوره‌ی کوتاه‌مدت و میان‌مدت (assets/js/eec-course-form.js) با وابستگی‌هایش:
 * بازه‌ی تاریخ، select2 با جست‌وجو و اعتبارسنجی فرم.
 */
class CourseFormAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'assets/js/eec-course-form.js',
    ];
    public $depends = [
        'frontend\assets\DateRangeAsset',
        'frontend\assets\SelectSearchAsset',
        'frontend\assets\FormValidateAsset',
    ];
}
