<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * اعتبارسنجی فرم با پیام زیر هر فیلد و خط قرمز (شامل select2 و تاریخ شمسی) و ارسال در پس‌زمینه.
 * در هر صفحه: FormValidateAsset::register($this); سپس EecValidate.bind(form, {ajax: true}).
 */
class FormValidateAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'assets/js/eec-validate.js',
    ];
    public $depends = [
        'frontend\assets\AppAsset',
    ];
}
