<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class SingleAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/vendor/libs/flatpickr/flatpickr.css',
        'assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css'
    ];
    public $js = [
        // 'assets/vendor/libs/cleavejs/cleave.js',
        // 'assets/vendor/libs/cleavejs/cleave-phone.js',
        // 'assets/vendor/libs/moment/moment.js',
        // 'assets/vendor/libs/jdate/jdate.js',
        // 'assets/vendor/libs/flatpickr/flatpickr-jdate.js',
        // 'assets/vendor/libs/flatpickr/l10n/fa-jdate.js',
        // 'assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js',
        // 'assets/js/form-layouts.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',

    ];
}
