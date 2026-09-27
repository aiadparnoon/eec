<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class Select2Asset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/vendor/libs/bootstrap-select/bootstrap-select.css',
        'assets/vendor/libs/select2/select2.css',
        'assets/vendor/libs/tagify/tagify.css'
    ];
    public $js = [
        'assets/js/forms-selects.js',
        'assets/vendor/libs/tagify/tagify.js',
        'assets/js/forms-tagify.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',

    ];
}
