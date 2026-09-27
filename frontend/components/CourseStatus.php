<?php

namespace app\components;

/**
 * وضعیت‌های دوره و فرآیند ثبت/بررسی/تأیید (صورتجلسه‌ی ۱۴۰۳/۴/۳۱، بندهای ۵.۳ و ۶).
 *
 * در هر لحظه فقط یک برچسب نمایش داده می‌شود. «اصلاح‌شده» یک وضعیت جدا نیست:
 * دوره‌ای که پس از «نیاز به اصلاح» دوباره ارسال شده، status = در انتظار بررسی و modified = true دارد
 * و فقط برچسب «اصلاح‌شده، در انتظار بررسی» می‌گیرد. با تأیید/بازگشت/رد، modified پاک می‌شود.
 */
class CourseStatus
{
    const APPROVED_INACTIVE = '0';
    const ACTIVE = '1';
    const AWAITING = '2';           // در انتظار بررسی مرکز
    const DRAFT = '3';
    const NEEDS_CORRECTION = '4';   // بازگشت توسط مرکز
    const REJECTED = '5';
    const FINISHED = '6';
    const AWAITING_UNIT = '7';      // ثبت کارگزار، در انتظار بررسی واحد
    const UNIT_CORRECTION = '8';    // بازگشت توسط واحد
    const UNIT_REJECTED = '9';

    const LABELS = [
        '0' => ['تأیید شده - غیرفعال', 'secondary'],
        '1' => ['تأیید شده - فعال', 'success'],
        '2' => ['در انتظار بررسی', 'primary'],
        '3' => ['پیش‌نویس', 'info'],
        '4' => ['نیاز به اصلاح', 'warning'],
        '5' => ['رد شده', 'danger'],
        '6' => ['پایان یافته', 'dark'],
        '7' => ['در انتظار بررسی واحد', 'primary'],
        '8' => ['نیاز به اصلاح (واحد)', 'warning'],
        '9' => ['رد شده توسط واحد', 'danger'],
    ];

    /** گزینه‌های فیلتر وضعیت؛ «corrected» و «awaiting» مجازی‌اند */
    const FILTERS = [
        'awaiting' => 'در انتظار بررسی',
        'corrected' => 'اصلاح‌شده، در انتظار بررسی',
        '7' => 'در انتظار بررسی واحد',
        '4' => 'نیاز به اصلاح',
        '8' => 'نیاز به اصلاح (واحد)',
        '3' => 'پیش‌نویس',
        '1' => 'تأیید شده - فعال',
        '0' => 'تأیید شده - غیرفعال',
        '6' => 'پایان یافته',
        '5' => 'رد شده',
        '9' => 'رد شده توسط واحد',
    ];

    public static function isCorrected($course)
    {
        return $course->modified === true && in_array((string) $course->status, [self::AWAITING, self::AWAITING_UNIT], true);
    }

    /**
     * @return array [label, color]
     */
    public static function label($course)
    {
        $status = (string) $course->status;
        if (self::isCorrected($course))
            return [$status === self::AWAITING_UNIT ? 'اصلاح‌شده، در انتظار بررسی واحد' : 'اصلاح‌شده، در انتظار بررسی', 'info'];
        return isset(self::LABELS[$status]) ? self::LABELS[$status] : ['نامشخص', 'secondary'];
    }

    /**
     * شرط کوئری برای مقدار فیلتر وضعیت.
     *
     * @return array|null
     */
    public static function filterCondition($value)
    {
        if ($value === 'awaiting')
            return ['status' => self::AWAITING, 'modified' => ['$ne' => true]];
        if ($value === 'corrected')
            return ['status' => [self::AWAITING, self::AWAITING_UNIT], 'modified' => true];
        if ($value === self::AWAITING_UNIT)
            return ['status' => self::AWAITING_UNIT, 'modified' => ['$ne' => true]];
        if (is_string($value) && isset(self::LABELS[$value]))
            return ['status' => $value];
        return null;
    }

    /**
     * ارسال (مجدد) برای بررسی: از «نیاز به اصلاح» → «اصلاح‌شده، در انتظار بررسی».
     */
    public static function submit($course, $byBroker)
    {
        $status = (string) $course->status;
        if ($status === self::NEEDS_CORRECTION) {
            $course->status = self::AWAITING;
            $course->modified = true;
        } else if ($status === self::UNIT_CORRECTION) {
            $course->status = self::AWAITING_UNIT;
            $course->modified = true;
        } else if (in_array($status, [self::DRAFT, ''], true)) {
            $course->status = $byBroker ? self::AWAITING_UNIT : self::AWAITING;
            $course->modified = false;
        }
    }

    /** با تأیید/بازگشت/رد، برچسب «اصلاح‌شده» پاک می‌شود */
    public static function setReviewed($course, $status)
    {
        $course->status = $status;
        $course->modified = false;
    }

    const STEP_TITLES = [
        1 => 'ثبت دوره',
        2 => 'پیش‌نویس',
        3 => 'ارسال برای بررسی و تأیید',
        4 => 'بازگشت برای اصلاح',
        5 => 'نیاز به اصلاح',
        6 => 'اصلاح موارد',
        7 => 'ارسال مجدد، در انتظار بررسی',
        8 => 'بررسی مجدد',
        9 => 'تأیید رئیس مرکز',
        10 => 'تأیید شده / فعال',
    ];

    /**
     * مراحل فرآیند برای نمودار مرحله‌ای (بند ۶ صورتجلسه).
     * حلقه‌ی اصلاح (مراحل ۴ تا ۸) فقط وقتی دوره به اصلاح برگشته باشد طی می‌شود؛ در غیر این صورت skipped است.
     *
     * @return array[] ['number', 'title', 'state' => done|current|todo|skipped|failed]
     */
    public static function steps($course)
    {
        $status = (string) $course->status;
        // [مراحل انجام‌شده, مرحله‌ی جاری, مراحل حذف‌شده, مرحله‌ی ناموفق]
        if ($status === self::DRAFT)
            $plan = [[1], 2, [], null];
        else if (in_array($status, [self::NEEDS_CORRECTION, self::UNIT_CORRECTION], true))
            $plan = [[1, 2, 3, 4], 5, [], null];
        else if (self::isCorrected($course))
            $plan = [[1, 2, 3, 4, 5, 6], 7, [], null];
        else if (in_array($status, [self::AWAITING, self::AWAITING_UNIT], true))
            $plan = [[1, 2], 3, [4, 5, 6, 7, 8], null];
        else if (in_array($status, [self::ACTIVE, self::APPROVED_INACTIVE, self::FINISHED], true))
            $plan = [[1, 2, 3, 9, 10], null, [4, 5, 6, 7, 8], null];
        else if (in_array($status, [self::REJECTED, self::UNIT_REJECTED], true))
            $plan = [[1, 2, 3], null, [4, 5, 6, 7, 8], 9];
        else
            $plan = [[], 1, [], null];

        list($done, $current, $skipped, $failed) = $plan;
        $steps = [];
        foreach (self::STEP_TITLES as $number => $title) {
            if ($number === $failed)
                $state = 'failed';
            else if ($number === $current)
                $state = 'current';
            else if (in_array($number, $done, true))
                $state = 'done';
            else if (in_array($number, $skipped, true))
                $state = 'skipped';
            else
                $state = 'todo';
            $steps[] = ['number' => $number, 'title' => $title, 'state' => $state];
        }
        return $steps;
    }
}
