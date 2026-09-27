<?php

use yii\mongodb\Migration;

/**
 * تکمیل users.college برای داده‌های قدیمی (بعد از m260927_100000).
 *
 * پیش از این، ثبت‌نام در دوره college را «جایگزین» می‌کرد (نه اضافه) و افزودن تکی کاربر
 * توسط کارشناس هیچ دانشکده‌ای ثبت نمی‌کرد. این مهاجرت همان قاعده‌ی جدید را روی داده‌ی موجود اعمال می‌کند:
 *  ۱- دانشکده‌ی هر دوره‌ای که کاربر در آن ثبت‌نام کرده (users.courses[]._id → courses.college)
 *  ۲- دانشکده‌ی کارشناسی (role = emp) که کاربر را ثبت کرده (users.registrant)
 *
 * فقط با $addToSet اضافه می‌کند و چیزی حذف نمی‌کند؛ اجرای دوباره بی‌اثر است.
 */
class m260927_100100_users_college_backfill extends Migration
{
    public function up()
    {
        $users = Yii::$app->mongodb->getCollection(['eec', 'users']);

        $fromCourses = 0;
        foreach (Yii::$app->mongodb->getCollection(['eec', 'courses'])->find([], ['_id' => 1, 'college' => 1]) as $course) {
            $college = isset($course['college']) ? trim((string) $course['college']) : '';
            if ($college === '')
                continue;
            $fromCourses += $users->update(
                ['courses._id' => (string) $course['_id'], 'college' => ['$ne' => $college]],
                ['$addToSet' => ['college' => $college]],
                ['multi' => true]
            );
        }

        $fromRegistrant = 0;
        foreach (Yii::$app->mongodb->getCollection(['eec', 'admin'])->find(['role' => 'emp'], ['username' => 1, 'college' => 1]) as $emp) {
            $colleges = [];
            foreach ((array) (isset($emp['college']) ? $emp['college'] : []) as $c)
                if (trim((string) $c) !== '')
                    $colleges[] = trim((string) $c);
            if (empty($colleges) || empty($emp['username']))
                continue;
            $fromRegistrant += $users->update(
                ['registrant' => (string) $emp['username'], 'college' => ['$type' => 'array']],
                ['$addToSet' => ['college' => ['$each' => $colleges]]],
                ['multi' => true]
            );
        }

        echo "    > users updated from enrolled courses: $fromCourses, from registering staff: $fromRegistrant\n";
        return true;
    }

    public function down()
    {
        echo "    > m260927_100100_users_college_backfill only adds values; nothing to revert.\n";
        return true;
    }
}
