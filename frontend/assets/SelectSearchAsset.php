<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * فیلدهای انتخابی با قابلیت جست‌وجو (select2). در هر صفحه: SelectSearchAsset::register($this);
 * سپس EecSelect.init(container).
 */
class SelectSearchAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/vendor/libs/select2/select2.css',
    ];
    public $js = [
        'assets/js/eec-select.js',
    ];
    public $depends = [
        'frontend\assets\AppAsset', // بعد از jQuery، flatpickr و select2 قالب
    ];
}
