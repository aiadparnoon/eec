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

    /**
     * ساخت (یا به‌روزرسانی) کلاس‌های آنلاین دوره؛ برای درس جدید، شناسه‌ی درس داده می‌شود.
     *
     * @return bool|null true موفق، false پاسخ ناموفق، null خطای ارتباط
     */
    public function createCourseMeetings($courseId, $newLessonId = null);

    /** حذف دسترسی کاربر از کلاس‌های دوره */
    public function removeCourseUser($user, $courseId);

    /** به‌روزرسانی نام/نام کاربری کاربر در سامانه */
    public function updateUser($user);

    /**
     * تغییر تاریخ و/یا مدرس یک درس دوره.
     *
     * @param string|null $teacherAdminId شناسه‌ی حساب (admin) مدرس جدید؛ null یعنی مدرس تغییری نکرده
     * @return bool
     */
    public function updateLesson($courseId, $lessonId, $from, $to, $teacherAdminId = null);

    /** حذف کلاس یک درس از دوره */
    public function deleteLesson($courseId, $lessonId);

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
