<?php

namespace app\models;

/**
 * جست‌وجو و فیلتر دوره‌های میان‌مدت (courses.type = '2' و دوره‌های سالانه‌ی قدیمی '3') — همان فیلترها و آمار
 * دوره‌های کوتاه‌مدت، با تاریخ شروع از date.from.
 */
class PackagesSearch extends ShortCoursesSearch
{
    const TYPE = '2';
    const TYPES = ['2', '3'];
    const START_FIELD = 'date.from';

    public function formName()
    {
        return 'PS';
    }
}
