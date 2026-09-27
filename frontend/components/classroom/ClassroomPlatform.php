<?php

namespace app\components\classroom;

/**
 * سامانه‌ی کلاس آنلاین (فعلاً Adobe Connect، به‌زودی BigBlueButton).
 *
 * کد مدیریت کاربران فقط با این قرارداد کار می‌کند تا افزودن BBB نیازی به تغییر
 * کنترلرها و ویوها نداشته باشد. برای هر دوره، پیاده‌سازی مناسب را
 * ClassroomPlatforms::forCourse() برمی‌گرداند.
 */
interface ClassroomPlatform
{
    /** نام نمایشی سامانه، مثلاً «ادوبی کانکت» */
    public function title();

    /**
     * ثبت (مجدد) کاربرانِ در انتظار دوره در سامانه.
     * فعلاً سرویس فقط سطح دوره دارد؛ کاربرانی که وضعیتشان '0' است ثبت می‌شوند.
     *
     * @return bool موفق بودن فراخوانی
     */
    public function registerCourseUsers($courseId);

    /** حذف دسترسی کاربر از کلاس‌های دوره */
    public function removeCourseUser($user, $courseId);

    /** به‌روزرسانی نام/نام کاربری کاربر در سامانه */
    public function updateUser($user);

    /**
     * حضور و غیاب کاربر در دوره.
     *
     * @return array فهرست جلسات: [
     *   'lesson' => عنوان درس, 'from' => timestamp, 'to' => timestamp,
     *   'present' => bool, 'enter' => timestamp|null, 'exit' => timestamp|null, 'minutes' => int
     * ]
     */
    public function attendance($user, $course);
}
