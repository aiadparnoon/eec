<?php
namespace app\components;

use Yii;

/**
 * تنظیمات درگاه پرداخت. کلید API فقط در common/config/main-local.php (gitignored) نگه داشته می‌شود:
 *     'aliases' => ['@payment_api_key' => '...'],
 */
class PaymentConfig
{
    public static function apiKey()
    {
        $key = Yii::getAlias('@payment_api_key', false);
        if (!is_string($key) || $key === '') {
            Yii::error('Payment gateway key is not configured: set the @payment_api_key alias in common/config/main-local.php', __METHOD__);
            return '';
        }
        return $key;
    }
}
