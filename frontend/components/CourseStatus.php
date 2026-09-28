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
            return [$status === self::AWAITING_UNIT ? 'اصلاح‌شده، در انتظار تأیید واحد' : 'اصلاح‌شده، در انتظار بررسی', 'info'];
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
            // دوره‌ی کارگزار که مدیر سیستم برگردانده: اصلاح کارگزار دوباره از بررسی واحد می‌گذرد؛
            // اگر خود واحد اصلاح کند، مستقیم به مدیر سیستم می‌رود
            $course->status = $byBroker && self::hasBroker($course) ? self::AWAITING_UNIT : self::AWAITING;
            $course->modified = true;
        } else if ($status === self::UNIT_CORRECTION) {
            $course->status = self::AWAITING_UNIT;
            $course->modified = true;
        } else if (in_array($status, [self::DRAFT, ''], true)) {
            $course->status = $byBroker ? self::AWAITING_UNIT : self::AWAITING;
            $course->modified = false;
        }
    }

    /**
     * با تأیید/بازگشت/رد، برچسب «اصلاح‌شده» پاک می‌شود. returned_by نشان می‌دهد آخرین بار چه کسی دوره را
     * برگردانده/رد کرده (admin | unit) تا نمودار مراحل وضعیت را درست نشان دهد؛ با تأیید پاک می‌شود.
     */
    public static function setReviewed($course, $status)
    {
        $course->status = $status;
        $course->modified = false;
        if (in_array($status, [self::NEEDS_CORRECTION, self::REJECTED], true))
            $course->returned_by = 'admin';
        else if (in_array($status, [self::UNIT_CORRECTION, self::UNIT_REJECTED], true))
            $course->returned_by = 'unit';
        else if (in_array($status, [self::ACTIVE, self::APPROVED_INACTIVE], true))
            $course->returned_by = null;
    }

    public static function hasBroker($course)
    {
        return is_array($course->broker) ? !empty($course->broker['_id']) : (is_object($course->broker) && !empty($course->broker->_id));
    }
}
