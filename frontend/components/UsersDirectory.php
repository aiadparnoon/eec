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
     * @return array [collegeId => title] همه‌ی واحدها
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
     * واحدهایی که کاربر جاری می‌تواند در فیلترها انتخاب کند.
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
     * @return string[] عنوان واحدها
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
            'emp' => 'کارشناس واحد',
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
        foreach (Admin::find()->select(['username', 'first_name', 'last_name', 'role', 'college'])->where(['username' => $usernames])->all() as $admin) {
            $role = (string) $admin->role;
            $label = self::roleLabel($role);
            if ($role === 'emp') {
                // ثبت توسط واحد: نام واحد کارشناس هم نمایش داده می‌شود
                $units = self::collegeNames($admin->college);
                $label = 'ثبت توسط واحد' . (empty($units) ? '' : ' ' . implode('، ', $units));
            } else if ($role === 'broker') {
                $label = 'ثبت توسط کارگزار (کیف پول)';
            }
            $result[(string) $admin->username] = [
                'name' => trim($admin->first_name . ' ' . $admin->last_name),
                'role' => $role,
                'roleLabel' => $label,
            ];
        }
        return $result;
    }

    const INFERRED_PREFIX = '@source:';

    /**
     * منبع ثبت دانشپذیرانی که فیلد registrant ندارند، از روی سفارش‌های موفقشان (یک کوئری):
     * پرداخت با کیف پول ← کارگزار؛ پرداخت از درگاه ← خرید مستقیم.
     * خروجی با کلید INFERRED_PREFIX.username در آرایه‌ی registrants ادغام می‌شود.
     *
     * @param \app\models\Users[]|array[] $students مدل یا سند خام
     * @return array
     */
    public static function inferSources(array $students)
    {
        $usernames = [];
        foreach ($students as $student) {
            $registrant = is_array($student) ? (isset($student['registrant']) ? $student['registrant'] : null) : $student->registrant;
            $username = is_array($student) ? (isset($student['username']) ? $student['username'] : null) : $student->username;
            if ((!is_scalar($registrant) || (string) $registrant === '') && is_scalar($username) && (string) $username !== '')
                $usernames[] = (string) $username;
        }
        if (empty($usernames))
            return [];
        $orders = \app\models\Orders::find()
            ->select(['username', 'payment_info', 'shares', 'status'])
            ->where(['username' => array_values(array_unique($usernames)), 'status' => '1'])
            ->orderBy(['_id' => SORT_ASC])
            ->asArray()->all();
        $brokerIds = [];
        $firstOrder = [];
        foreach ($orders as $order) {
            $username = (string) $order['username'];
            if (isset($firstOrder[$username]))
                continue; // اولین خرید، منبع ثبت است
            $firstOrder[$username] = $order;
            if (isset($order['shares'][0]['broker']) && is_scalar($order['shares'][0]['broker']) && preg_match('/^[a-f0-9]{24}$/i', (string) $order['shares'][0]['broker']))
                $brokerIds[] = (string) $order['shares'][0]['broker'];
        }
        $brokerNames = [];
        if (!empty($brokerIds))
            foreach (\app\models\Brokers::find()->select(['connector_info', 'company_info'])->where(['_id' => array_values(array_unique($brokerIds))])->asArray()->all() as $broker) {
                $ci = isset($broker['connector_info']) && is_array($broker['connector_info']) ? $broker['connector_info'] : [];
                $brokerNames[(string) $broker['_id']] = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
            }
        $result = [];
        foreach ($firstOrder as $username => $order) {
            $wallet = isset($order['payment_info']['order_id']) && $order['payment_info']['order_id'] === 'wallet';
            $brokerId = isset($order['shares'][0]['broker']) && is_scalar($order['shares'][0]['broker']) ? (string) $order['shares'][0]['broker'] : '';
            if ($wallet)
                $result[self::INFERRED_PREFIX . $username] = ['name' => !empty($brokerNames[$brokerId]) ? $brokerNames[$brokerId] : 'کارگزار', 'roleLabel' => 'ثبت توسط کارگزار (کیف پول)'];
            else
                $result[self::INFERRED_PREFIX . $username] = ['name' => 'خود دانشپذیر', 'roleLabel' => 'خرید مستقیم دوره از سامانه'];
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
        if ($registrant === '') {
            // داده‌ی قدیمی بدون ثبت‌کننده: منبع از روی سفارش‌ها (inferSources) تشخیص داده می‌شود
            if (isset($registrants[self::INFERRED_PREFIX . $studentUsername]))
                return $registrants[self::INFERRED_PREFIX . $studentUsername];
            return ['name' => 'ثبت قدیمی', 'roleLabel' => 'منبع ثبت در داده‌های قدیمی ذخیره نشده'];
        }
        if ($registrant === $studentUsername)
            return ['name' => 'خود دانشپذیر', 'roleLabel' => 'خرید مستقیم دوره از سامانه'];
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
        // بند ۱.۱ صورتجلسه: فقط دو دسته — تک‌درس = کوتاه‌مدت؛ جامع و یک‌ساله = میان‌مدت
        $types = ['1' => 'دوره کوتاه‌مدت', '2' => 'دوره میان‌مدت', '3' => 'دوره میان‌مدت'];
        return isset($types[(string) $type]) ? $types[(string) $type] : 'دوره';
    }

    public static function contentType($type)
    {
        $types = ['1' => 'غیرحضوری', '2' => 'نیمه‌حضوری', '3' => 'محتوامحور', '4' => 'حضوری'];
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
