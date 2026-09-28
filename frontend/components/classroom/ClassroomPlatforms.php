<?php

namespace app\components\classroom;

use app\models\ClassroomServers;

/**
 * انتخاب سامانه‌ی کلاس آنلاین برای هر دوره، بر اساس سروری که در دوره انتخاب شده (classroom_server).
 *
 *  - classroom_server = 'none': دوره در هیچ سروری کلاس ندارد (مثلاً کارگزار در LMS خودش برگزار می‌کند).
 *  - classroom_server = شناسه‌ی سرور: همان سرور (تنظیمات سایت › سرورها).
 *  - بدون فیلد (دوره‌های قدیمی): سرور پیش‌فرض، فقط برای نوع آنلاین/نیمه‌حضوری (قاعده‌ی قبلی).
 */
class ClassroomPlatforms
{
    public static function hasOnlineClass($course)
    {
        if ($course === null)
            return false;
        $server = $course->classroom_server;
        if ($server === ClassroomServers::NONE)
            return false;
        if (is_string($server) && $server !== '')
            return true;
        return in_array((string) $course->content_type, ['1', '2'], true);
    }

    /**
     * @return ClassroomServers|null
     */
    public static function serverFor($course = null)
    {
        $id = $course === null ? null : $course->classroom_server;
        $server = is_string($id) && $id !== ClassroomServers::NONE ? ClassroomServers::findById($id) : null;
        return $server !== null ? $server : ClassroomServers::defaultServer();
    }

    /**
     * @return ClassroomPlatform
     */
    public static function forCourse($course = null)
    {
        return self::forServer(self::serverFor($course));
    }

    /**
     * @return ClassroomPlatform
     */
    public static function forServer(ClassroomServers $server = null)
    {
        if ($server !== null && $server->type === ClassroomServers::TYPE_BBB)
            return new BigBlueButtonPlatform($server);
        return new AdobeConnectPlatform($server);
    }
}
