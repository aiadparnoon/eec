<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * بازه‌ی تاریخ شروع/اتمام (flatpickr شمسی): تاریخ اتمام حداقل یک روز بعد از شروع.
 * در هر صفحه: DateRangeAsset::register($this); سپس EecDateRange.bind(startInput, endInput).
 */
class DateRangeAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'assets/js/eec-date-range.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
