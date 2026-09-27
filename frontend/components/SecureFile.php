<?php

namespace app\components;

/**
 * کمکی امنیتی برای سرو کردن فایل از پوشه‌های آپلود.
 *
 * پیش از این، اکشن‌های دانلود نام فایل را مستقیم از درخواست می‌گرفتند و به مسیر
 * می‌چسباندند؛ یعنی با ورودی «../../config/main-local.php» می‌شد هر فایلی از سرور را
 * خواند (از جمله cookieValidationKey). این کلاس همان کار را امن انجام می‌دهد.
 *
 * @since 2026-08-29
 */
class SecureFile
{
    /**
     * مسیر مطلق و امنِ یک فایل داخل پوشه‌ی مشخص را برمی‌گرداند.
     *
     * @param string $storageDir مسیر پوشه (نسبی به مسیر جاری یا مطلق)
     * @param string $filename   نام فایل که از درخواست کاربر آمده است
     * @return string|null مسیر مطلق فایل، یا null اگر نامعتبر/بیرون از پوشه/ناموجود باشد
     */
    public static function resolve($storageDir, $filename)
    {
        $filename = (string) $filename;

        // ۱- بایت null باعث بریده شدن مسیر در توابع سیستمی می‌شود
        if (strpos($filename, "\0") !== false)
            return null;

        // ۲- هر جزء مسیری را دور بریز: «../../x» و «..\..\x» هر دو به «x» تبدیل می‌شوند
        $filename = basename(str_replace('\\', '/', $filename));

        if ($filename === '' || $filename === '.' || $filename === '..')
            return null;

        // ۳- خودِ پوشه باید وجود داشته باشد
        $baseReal = realpath($storageDir);
        if ($baseReal === false)
            return null;

        // ۴- مسیر نهایی را resolve کن (symlink و . و .. همگی باز می‌شوند)
        $fullReal = realpath($baseReal . DIRECTORY_SEPARATOR . $filename);
        if ($fullReal === false)
            return null;

        // ۵- بررسی نهایی: نتیجه حتماً باید داخل همان پوشه باشد
        if (strpos($fullReal, $baseReal . DIRECTORY_SEPARATOR) !== 0)
            return null;

        return is_file($fullReal) ? $fullReal : null;
    }

    /**
     * یک جزء مسیر (مثل course_id یا assignment_id) را که از URL می‌آید اعتبارسنجی می‌کند.
     *
     * @param string $value
     * @return string|null خود مقدار اگر امن باشد، وگرنه null
     */
    public static function segment($value)
    {
        $value = (string) $value;

        if ($value === '' || $value === '.' || $value === '..')
            return null;

        return preg_match('/^[A-Za-z0-9_-]+$/', $value) === 1 ? $value : null;
    }
}
