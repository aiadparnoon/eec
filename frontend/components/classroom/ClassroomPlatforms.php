<?php

namespace app\components\classroom;

/**
 * انتخاب سامانه‌ی کلاس آنلاین برای هر دوره.
 * وقتی BigBlueButton اضافه شد، کافی است اینجا بر اساس فیلد دوره (مثلاً platform)
 * پیاده‌سازی BBB برگردانده شود.
 */
class ClassroomPlatforms
{
    /**
     * آیا دوره کلاس آنلاین دارد (content_type: 1=آنلاین، 2=آفلاین با کلاس، 3=محتوایی).
     * همان شرطی که packages/change_status برای فراخوانی ادوبی استفاده می‌کند.
     */
    public static function hasOnlineClass($course)
    {
        return $course !== null && in_array((string) $course->content_type, ['1', '2'], true);
    }

    /**
     * @return ClassroomPlatform
     */
    public static function forCourse($course = null)
    {
        return new AdobeConnectPlatform();
    }
}
