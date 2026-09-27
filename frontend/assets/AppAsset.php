<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
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
        'assets/css/cropper.min.css',
        'assets/vendor/css/rtl/rtl.css',
        'assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css',
        'assets/vendor/libs/typeahead-js/typeahead.css',
        'assets/vendor/libs/formvalidation/dist/css/formValidation.min.css',
        'assets/vendor/css/pages/page-auth.css',
        'assets/vendor/css/pages/page-faq.css',
        'assets/vendor/libs/dropzone/dropzone.css',
        'assets/vendor/libs/toastr/toastr.css',
        'assets/vendor/libs/flatpickr/flatpickr.css',
        'assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.css',
        'assets/vendor/libs/quill/editor-fa.css',
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
        'assets/vendor/libs/typeahead-js/typeahead.js',
        'assets/vendor/js/menu.js',
        'assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js',
        'assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js',
        'assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js',
        'assets/vendor/libs/dropzone/dropzone.js',
        'assets/js/pages-auth.js',
        'assets/js/main.js',
        'assets/js/forms-file-upload.js',
        'assets/vendor/libs/bootstrap-select/bootstrap-select.js',
        'assets/vendor/libs/select2/select2.js',
        'assets/vendor/libs/toastr/toastr.js',
        'assets/js/ui-toasts.js',
        'assets/vendor/libs/moment/moment.js',
        'assets/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js',
        'assets/vendor/libs/jdate/jdate.js',
        'assets/vendor/libs/flatpickr/flatpickr-jdate.js',
        'assets/vendor/libs/flatpickr/l10n/fa-jdate.js',
        'assets/js/forms-selects.js',
        'assets/js/form-layouts.js',
        'assets/vendor/libs/jquery-repeater/jquery-repeater.js',
        'assets/js/forms-extras.js',
        'assets/js/extended-ui-drag-and-drop.js',
        'assets/js/axios.min.js',
        'assets/js/cropper.min.js',
        'assets/vendor/libs/quill/katex.js',
        'assets/vendor/libs/quill/quill.js',
        'assets/js/forms-editors.js'
    ];
    //    public $jsOptions = [
    //        'defer' => 'defer',
    //    ];
    public $depends = [
        'yii\web\YiiAsset',

    ];
}
