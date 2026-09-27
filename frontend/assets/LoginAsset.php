<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class LoginAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'assets/vendor/fonts/boxicons.css',
        'assets/vendor/fonts/fontawesome.css',
        'assets/vendor/fonts/flag-icons.css',
        'assets/vendor/css/rtl/core.css',
        'assets/vendor/css/rtl/theme-default.css',
        'assets/css/demo.css',
        'assets/vendor/css/rtl/rtl.css',
        'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css',
        'assets/vendor/libs/typeahead-js/typeahead.css',
        'assets/vendor/libs/formvalidation/dist/css/formValidation.min.css',
        'assets/vendor/css/pages/page-auth.css',
    ];
    public $js = [
       'assets/vendor/js/helpers.js',
      'assets/vendor/js/template-customizer.js',
      'assets/js/config.js',
      'assets/vendor/libs/jquery/jquery.js',
      'assets/vendor/libs/popper/popper.js',
      'assets/vendor/js/bootstrap.js',
      'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
      'assets/vendor/libs/hammer/hammer.js',
      'assets/vendor/libs/i18n/i18n.js',
      'assets/vendor/libs/typeahead-js/typeahead.js',
      'assets/vendor/js/menu.js',
      'assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js',
      'assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js',
      'assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js',
      'assets/js/main.js',
      'assets/js/pages-auth.js',
    ];
    //    public $jsOptions = [
    //        'defer' => 'defer',
    //    ];
    public $depends = [
        'yii\web\YiiAsset',

    ];
}
