<?php

namespace app\components;

use Yii;

/**
 * برگشت به صفحه‌ی قبل فقط اگر آدرس داخل همین سایت باشد (جلوگیری از open redirect
 * با دستکاری هدر Referer).
 */
class SafeRedirect
{
    /**
     * @param array|string $fallback مسیر پیش‌فرض (فرمت Url::to)
     * @return array|string
     */
    public static function referrer($fallback)
    {
        $referrer = Yii::$app->request->referrer;
        if (!is_string($referrer) || $referrer === '')
            return $fallback;
        $parts = parse_url($referrer);
        if ($parts === false || !isset($parts['path']))
            return $fallback;
        if (isset($parts['host']) && strcasecmp($parts['host'], (string) Yii::$app->request->hostName) !== 0)
            return $fallback;
        $path = $parts['path'];
        // فقط مسیر نسبی داخلی (نه //evil.com)
        if (strpos($path, '/') !== 0 || strpos($path, '//') === 0)
            return $fallback;
        return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
}
