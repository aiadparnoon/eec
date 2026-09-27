<?php

namespace app\components;

use Yii;
use yii\web\UploadedFile;

/**
 * ذخیره‌ی امن فایل‌های آپلودی.
 *
 * پیش از این پسوند فایل مستقیماً از نام فایلِ کاربر گرفته و داخل web/ ذخیره می‌شد؛ یعنی
 * آپلود shell.php = اجرای کد روی سرور (SECURITY-AUDIT بند ۳). این کلاس:
 *  ۱- پسوند را فقط از لیست سفیدِ پروفایل می‌پذیرد (آخرین نقطه‌ی نام فایل)،
 *  ۲- نوع واقعی فایل را از محتوا (finfo) تشخیص می‌دهد و باید با پسوند هم‌خوان باشد،
 *  ۳- برای تصاویر، سالم بودن تصویر را با getimagesize بررسی می‌کند و SVG را نمی‌پذیرد (XSS)،
 *  ۴- فایل‌هایی که کد PHP/اسکریپت داخلشان است را رد می‌کند،
 *  ۵- نام مقصد را کاملاً تصادفی می‌سازد و پسوند را از نوع تشخیص‌داده‌شده انتخاب می‌کند،
 *  ۶- حجم را محدود می‌کند.
 */
class SecureUpload
{
    const PROFILES = [
        'image' => [
            'types' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'maxSize' => 2097152, // 2MB
            'image' => true,
        ],
        'document' => [
            'types' => ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
            'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
            'maxSize' => 10485760, // 10MB
            'image' => false,
        ],
        'excel' => [
            'types' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/zip' => 'xlsx',
                'application/octet-stream' => 'xlsx',
                'application/vnd.ms-excel' => 'xls',
                'application/CDFV2' => 'xls',
                'application/x-ole-storage' => 'xls',
            ],
            'extensions' => ['xlsx', 'xls'],
            'maxSize' => 10485760,
            'image' => false,
        ],
    ];

    /** آخرین خطای اعتبارسنجی (فارسی) */
    public static $lastError;

    /**
     * فایل را بررسی و با نام تصادفی ذخیره می‌کند.
     *
     * @param UploadedFile|null $file
     * @param string $profile image | document | excel
     * @param string $directory مسیر یا alias پوشه‌ی مقصد
     * @return string|null نام فایل ذخیره‌شده، یا null (دلیل در self::$lastError)
     */
    public static function save($file, $profile, $directory)
    {
        self::$lastError = null;
        $extension = self::validate($file, $profile);
        if ($extension === null)
            return null;
        $dir = rtrim(Yii::getAlias($directory), '/\\');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            self::$lastError = 'پوشه‌ی مقصد قابل ایجاد نیست';
            return null;
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        if (!$file->saveAs($dir . DIRECTORY_SEPARATOR . $name)) {
            self::$lastError = 'ذخیره‌ی فایل با خطا مواجه شد';
            return null;
        }
        @chmod($dir . DIRECTORY_SEPARATOR . $name, 0644);
        return $name;
    }

    /**
     * فقط بررسی (بدون ذخیره).
     *
     * @return string|null پسوند امن برای ذخیره، یا null
     */
    public static function validate($file, $profile)
    {
        $rules = self::PROFILES[$profile];
        if (!$file instanceof UploadedFile || $file->error !== UPLOAD_ERR_OK || !is_file($file->tempName)) {
            self::$lastError = 'فایلی دریافت نشد';
            return null;
        }
        if ($file->size <= 0 || $file->size > $rules['maxSize']) {
            self::$lastError = 'حجم فایل بیش از حد مجاز (' . round($rules['maxSize'] / 1048576) . ' مگابایت) است';
            return null;
        }
        $clientExtension = strtolower((string) pathinfo((string) $file->name, PATHINFO_EXTENSION));
        if (!in_array($clientExtension, $rules['extensions'], true)) {
            self::$lastError = 'نوع فایل مجاز نیست (مجاز: ' . implode('، ', $rules['extensions']) . ')';
            return null;
        }
        $mime = self::detectMime($file->tempName);
        if ($mime === null || !isset($rules['types'][$mime])) {
            self::$lastError = 'محتوای فایل با نوع مجاز هم‌خوانی ندارد';
            return null;
        }
        $extension = $rules['types'][$mime];
        // برای xlsx/xls پسوند کاربر ملاک است (هر دو قالب zip/ole هستند و finfo همیشه دقیق نیست)
        if ($profile === 'excel')
            $extension = $clientExtension;
        if ($rules['image'] && @getimagesize($file->tempName) === false) {
            self::$lastError = 'فایل تصویر معتبر نیست';
            return null;
        }
        if (self::containsCode($file->tempName)) {
            self::$lastError = 'فایل حاوی کد غیرمجاز است';
            return null;
        }
        return $extension;
    }

    /**
     * حذف امن فایل قبلی (نام از دیتابیس می‌آید؛ از پوشه بیرون نمی‌رود).
     */
    public static function delete($directory, $name)
    {
        if (!is_string($name) || $name === '')
            return;
        $path = SecureFile::resolve(Yii::getAlias($directory), $name);
        if ($path !== null)
            @unlink($path);
    }

    private static function detectMime($path)
    {
        if (!function_exists('finfo_open'))
            return null;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : false;
        if ($finfo)
            finfo_close($finfo);
        return is_string($mime) ? $mime : null;
    }

    /**
     * جست‌وجوی نشانه‌های کد اجرایی در ابتدا و انتهای فایل (polyglot مثل GIF+PHP).
     */
    private static function containsCode($path)
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false)
            return true;
        $head = (string) fread($handle, 65536);
        $size = filesize($path);
        $tail = '';
        if ($size > 65536) {
            fseek($handle, -65536, SEEK_END);
            $tail = (string) fread($handle, 65536);
        }
        fclose($handle);
        return preg_match('/<\?php|<script\b|\beval\s*\(|base64_decode\s*\(/i', $head . $tail) === 1; // فقط الگوهای بلند تا بایت‌های تصادفی تصویر اشتباه تشخیص داده نشوند
    }
}
