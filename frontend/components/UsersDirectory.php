<?php

namespace app\components;

use Yii;
use app\models\Colleges;
use common\models\Admin;

/**
 * جست‌وجوهای کمکی مشترک صفحه‌های مدیریت کاربران (فهرست، پروفایل، خروجی اکسل).
 * هر کدام با یک کوئری انجام می‌شود تا در حلقه‌ی ردیف‌ها کوئری تکراری زده نشود.
 */
class UsersDirectory
{
    private static $collegeTitles;

    /**
     * @return array [collegeId => title] همه‌ی دانشکده‌ها
     */
    public static function collegeTitles()
    {
        if (self::$collegeTitles === null) {
            self::$collegeTitles = [];
            foreach (Colleges::find()->select(['_id', 'title'])->all() as $college)
                self::$collegeTitles[(string) $college->_id] = (string) $college->title;
        }
        return self::$collegeTitles;
    }

    /**
     * دانشکده‌هایی که کاربر جاری می‌تواند در فیلترها انتخاب کند.
     *
     * @return array [collegeId => title]
     */
    public static function selectableColleges()
    {
        $all = self::collegeTitles();
        if (StudentAccess::isAdmin())
            return $all;
        $result = [];
        foreach (StudentAccess::staffColleges() as $id)
            $result[$id] = isset($all[$id]) ? $all[$id] : $id;
        return $result;
    }

    /**
     * @param mixed $college مقدار users.college
     * @return string[] عنوان دانشکده‌ها
     */
    public static function collegeNames($college)
    {
        $titles = self::collegeTitles();
        $names = [];
        foreach (StudentAccess::normalizeColleges($college) as $id)
            $names[] = isset($titles[$id]) ? $titles[$id] : 'نامشخص';
        return $names;
    }

    public static function roleLabel($role)
    {
        $labels = [
            'user' => 'مدیر سیستم',
            'cnt' => 'کارمند مرکز',
            'emp' => 'کارشناس دانشکده',
            'broker' => 'کارگزار',
            'teacher' => 'استاد',
        ];
        return isset($labels[$role]) ? $labels[$role] : '';
    }

    /**
     * اطلاعات ثبت‌کننده‌ها با یک کوئری $in.
     *
     * @param string[] $usernames
     * @return array [username => ['name' => ..., 'role' => ..., 'roleLabel' => ...]]
     */
    public static function registrants(array $usernames)
    {
        $usernames = array_values(array_unique(array_filter(array_map('strval', array_filter($usernames, 'is_scalar')), 'strlen')));
        if (empty($usernames))
            return [];
        $result = [];
        foreach (Admin::find()->select(['username', 'first_name', 'last_name', 'role'])->where(['username' => $usernames])->all() as $admin) {
            $result[(string) $admin->username] = [
                'name' => trim($admin->first_name . ' ' . $admin->last_name),
                'role' => (string) $admin->role,
                'roleLabel' => self::roleLabel($admin->role),
            ];
        }
        return $result;
    }

    /**
     * توضیح ثبت‌کننده‌ی یک دانشپذیر.
     *
     * @param \app\models\Users $student
     * @param array $registrants خروجی registrants()
     * @return array ['name' => ..., 'roleLabel' => ...]
     */
    public static function describeRegistrant($student, array $registrants)
    {
        $registrant = is_scalar($student->registrant) ? (string) $student->registrant : '';
        return self::describeUsername($registrant, is_scalar($student->username) ? (string) $student->username : '', $registrants);
    }

    /**
     * توضیح یک نام کاربری ثبت‌کننده (برای دانشپذیر یا ثبت‌نام در یک دوره).
     *
     * @param string $registrant
     * @param string $studentUsername
     * @param array $registrants خروجی registrants()
     * @return array ['name' => ..., 'roleLabel' => ...]
     */
    public static function describeUsername($registrant, $studentUsername, array $registrants)
    {
        if ($registrant === '')
            return ['name' => 'نامشخص', 'roleLabel' => ''];
        if ($registrant === $studentUsername)
            return ['name' => 'خود دانشپذیر', 'roleLabel' => 'ثبت‌نام اینترنتی'];
        if (isset($registrants[$registrant]))
            return $registrants[$registrant];
        if ($registrant === (string) Yii::getAlias('@adminUsername'))
            return ['name' => 'مدیریت', 'roleLabel' => 'مدیر سیستم'];
        return ['name' => $registrant, 'roleLabel' => ''];
    }

    /**
     * وضعیت کاربر در یک دوره (users.courses[].status).
     *
     * @return array [label, bootstrap color]
     */
    public static function courseStatus($status)
    {
        switch ((string) $status) {
            case '1':
                return ['فعال', 'success'];
            case '2':
                return ['غیرفعال', 'secondary'];
            default:
                return ['ثبت‌نشده در کلاس آنلاین', 'warning'];
        }
    }

    public static function courseType($type)
    {
        $types = ['1' => 'دوره تک درس', '2' => 'دوره جامع', '3' => 'دوره یک‌ساله'];
        return isset($types[(string) $type]) ? $types[(string) $type] : 'دوره';
    }

    public static function contentType($type)
    {
        $types = ['1' => 'آنلاین', '2' => 'حضوری/آفلاین', '3' => 'محتوایی'];
        return isset($types[(string) $type]) ? $types[(string) $type] : '-';
    }

    /**
     * تاریخ شمسی از timestamp. jdf.php فقط یک بار بارگذاری می‌شود.
     */
    public static function jdate($format, $timestamp, $digits = 'fa')
    {
        if ($timestamp === null || $timestamp === '')
            return '-';
        return self::inTehran(function () use ($format, $timestamp, $digits) {
            return jdate($format, (int) $timestamp, '', 'Asia/Tehran', $digits);
        });
    }

    /**
     * jdf.php از منطقه‌ی زمانی پیش‌فرض PHP استفاده می‌کند (پارامتر time_zone آن غیرفعال است)؛
     * پس موقتاً روی تهران تنظیم و بعد برگردانده می‌شود.
     */
    private static function inTehran(callable $fn)
    {
        require_once Yii::getAlias('@frontend') . '/web/jdf.php';
        $previous = date_default_timezone_get();
        date_default_timezone_set('Asia/Tehran');
        try {
            return $fn();
        } finally {
            date_default_timezone_set($previous);
        }
    }

    /**
     * تاریخ امروز شمسی به صورت YYYYMMDD (برای مقایسه با سررسید اقساط).
     */
    public static function todayJalaliKey()
    {
        return self::inTehran(function () {
            return jdate('Ymd', time(), '', 'Asia/Tehran', 'en');
        });
    }

    /**
     * حروف اول نام برای آواتار.
     */
    public static function initials($firstName, $lastName)
    {
        $a = mb_substr(trim((string) $firstName), 0, 1, 'UTF-8');
        $b = mb_substr(trim((string) $lastName), 0, 1, 'UTF-8');
        $result = trim($a . ' ' . $b);
        return $result === '' ? '؟' : $result;
    }

    public static function money($amount)
    {
        return number_format((float) $amount) . ' تومان';
    }
}
