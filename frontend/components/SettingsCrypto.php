<?php

namespace app\components;

use Yii;

/**
 * رمزنگاری مقادیر محرمانه‌ی تنظیمات سایت (رمز سرور ادوبی، کلید BBB و ...) پیش از ذخیره در دیتابیس.
 *
 * کلید: alias «@settings_key» در common/config/main-local.php. اگر تعریف نشده باشد از
 * cookieValidationKey همین برنامه استفاده می‌شود؛ با عوض شدن کلید، مقادیر ذخیره‌شده باید دوباره وارد شوند.
 */
class SettingsCrypto
{
    const PREFIX = 'enc1:';

    private static function key()
    {
        $key = Yii::getAlias('@settings_key', false);
        if (is_string($key) && $key !== '')
            return $key;
        if (Yii::$app->has('request') && isset(Yii::$app->request->cookieValidationKey) && Yii::$app->request->cookieValidationKey !== '')
            return (string) Yii::$app->request->cookieValidationKey;
        throw new \yii\base\InvalidConfigException('Set the @settings_key alias in common/config/main-local.php');
    }

    public static function encrypt($plain)
    {
        return self::PREFIX . base64_encode(Yii::$app->security->encryptByKey((string) $plain, self::key()));
    }

    /**
     * @return string|null null اگر مقدار خراب باشد یا کلید عوض شده باشد
     */
    public static function decrypt($stored)
    {
        if (!is_string($stored) || strpos($stored, self::PREFIX) !== 0)
            return null;
        $raw = base64_decode(substr($stored, strlen(self::PREFIX)), true);
        if ($raw === false)
            return null;
        $plain = Yii::$app->security->decryptByKey($raw, self::key());
        return $plain === false ? null : $plain;
    }
}
