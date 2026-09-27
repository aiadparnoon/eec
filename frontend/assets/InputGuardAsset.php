<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * کنترل نوع ورودی فیلدها با data-input (بند ۱.۴ صورتجلسه). وابستگی ندارد (vanilla JS).
 * در هر صفحه: InputGuardAsset::register($this);
 */
class InputGuardAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'assets/js/eec-input-guard.js',
    ];
}
