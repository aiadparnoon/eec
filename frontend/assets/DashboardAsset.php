<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class DashboardAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/vendor/css/pages/page-profile.css'
    ];
    public $js = [
        'assets/js/pages-profile.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',

    ];
}
