<?php
/**
 * پیام‌های نتیجه‌ی عملیات (toastr) — مشترک فهرست و پروفایل.
 * @var $this yii\web\View
 */
use frontend\controllers\UsersManageController;
use yii\helpers\Json;

$flash = Yii::$app->session->getFlash(UsersManageController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $message = Json::htmlEncode($flash['message']);
    $this->registerJs("toastr['$type']($message, '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 6000});");
}
