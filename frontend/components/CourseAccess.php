<?php

namespace app\components;

use Yii;
use app\models\Brokers;
use app\models\Teachers;
use app\models\Users;

/**
 * دسترسی به دوره‌ها (مجموعه‌ی courses) — مشترک دوره‌های کوتاه‌مدت و میان‌مدت.
 *
 *  - مدیر سیستم (user/cnt): همه‌ی دوره‌ها.
 *  - کارشناس واحد (emp): دوره‌های واحد(های) خودش.
 *  - کارگزار (broker): دوره‌هایی که کارگزارِ آن‌ها خودش است.
 *  - استاد (teacher): فقط مشاهده‌ی دوره‌هایی که مدرس/دستیار آن است؛ بدون ثبت/ویرایش/حذف.
 */
class CourseAccess
{
    /** واحدهایی که کارشناسانشان دوره‌های واحد دیگری را هم می‌بینند (قاعده‌ی موجود پروژه) */
    const LINKED_UNITS = [
        '65948ca8f08779a235072195' => ['65afa2ea5136ec5b5b0c4064'],
    ];

    /** وضعیت‌هایی که بعد از آن فقط مدیر سیستم می‌تواند دوره را ویرایش کند (تأییدشده/پایان‌یافته) */
    const LOCKED_STATUSES = ['0', '1', '6'];

    /** وضعیت‌هایی که دوره (بدون دانشپذیر) قابل حذف است برای غیرمدیر */
    const DELETABLE_STATUSES = ['2', '3', '4', '5', '7', '8', '9'];

    private static $broker = false;
    private static $teacher = false;

    public static function identity()
    {
        return Yii::$app->user->identity;
    }

    public static function role()
    {
        $identity = self::identity();
        return $identity === null ? null : (string) $identity->role;
    }

    public static function isAdmin()
    {
        return StudentAccess::isAdmin();
    }

    /**
     * واحدهای کارشناس (به‌همراه واحدهای مرتبط).
     *
     * @return string[]
     */
    public static function units()
    {
        $role = self::role();
        if ($role === 'broker') {
            $broker = self::broker();
            return $broker === null ? [] : StudentAccess::normalizeColleges($broker->college);
        }
        $units = StudentAccess::staffColleges();
        foreach ($units as $unit)
            if (isset(self::LINKED_UNITS[$unit]))
                $units = array_merge($units, self::LINKED_UNITS[$unit]);
        return array_values(array_unique($units));
    }

    /**
     * رکورد کارگزارِ کاربر جاری (نقش broker)، بر اساس موبایل.
     *
     * @return Brokers|null
     */
    public static function broker()
    {
        if (self::$broker === false) {
            self::$broker = self::role() === 'broker'
                ? Brokers::find()->where(['connector_info.mobile' => (string) self::identity()->username])->one()
                : null;
        }
        return self::$broker;
    }

    /**
     * @return Teachers|null
     */
    public static function teacher()
    {
        if (self::$teacher === false) {
            self::$teacher = self::role() === 'teacher'
                ? Teachers::find()->where(['mobile' => (string) self::identity()->username])->one()
                : null;
        }
        return self::$teacher;
    }

    /**
     * محدودیت دسترسی روی کوئری courses.
     *
     * @param \yii\mongodb\ActiveQuery $query
     */
    public static function applyScope($query)
    {
        $identity = self::identity();
        if ($identity === null)
            return $query->andWhere(['_id' => null]);
        if (self::isAdmin())
            return $query;
        switch (self::role()) {
            case 'emp':
                $units = self::units();
                return $query->andWhere(empty($units) ? ['_id' => null] : ['college' => $units]);
            case 'broker':
                $broker = self::broker();
                return $query->andWhere($broker === null ? ['_id' => null] : ['broker._id' => (string) $broker->_id]);
            case 'teacher':
                if ($identity->mentor)
                    return $query->andWhere(['mentors' => (string) $identity->username]);
                $teacher = self::teacher();
                if ($teacher === null)
                    return $query->andWhere(['_id' => null]);
                return $query->andWhere(['or',
                    ['lessons.teachers' => (string) $teacher->_id],
                    ['other_teachers' => (string) $teacher->_id],
                ]);
        }
        return $query->andWhere(['_id' => null]);
    }

    public static function canView($course)
    {
        if ($course === null)
            return false;
        if (self::isAdmin())
            return true;
        switch (self::role()) {
            case 'teacher':
                $identity = self::identity();
                if ($identity->mentor)
                    return is_array($course->mentors) && in_array((string) $identity->username, array_map('strval', $course->mentors), true);
                $teacher = self::teacher();
                if ($teacher === null)
                    return false;
                $id = (string) $teacher->_id;
                foreach ((array) $course->lessons as $lesson)
                    if (is_array($lesson) && isset($lesson['teachers']) && (string) $lesson['teachers'] === $id)
                        return true;
                return is_array($course->other_teachers) && in_array($id, array_map('strval', $course->other_teachers), true);
            default:
                return self::canManage($course);
        }
    }

    /**
     * ثبت/کپی/مدیریت دوره (بدون توجه به وضعیت).
     */
    public static function canManage($course)
    {
        if ($course === null)
            return false;
        if (self::isAdmin())
            return true;
        switch (self::role()) {
            case 'emp':
                return in_array((string) $course->college, self::units(), true);
            case 'broker':
                $broker = self::broker();
                return $broker !== null && is_array($course->broker) && isset($course->broker['_id'])
                    && (string) $course->broker['_id'] === (string) $broker->_id;
        }
        return false;
    }

    /**
     * ویرایش اطلاعات دوره: غیرمدیر فقط تا پیش از تأیید.
     */
    public static function canEdit($course)
    {
        return self::canManage($course) && (self::isAdmin() || !in_array((string) $course->status, self::LOCKED_STATUSES, true));
    }

    /**
     * حذف: فقط دوره‌ی بدون دانشپذیر؛ غیرمدیر فقط در وضعیت‌های پیش از تأیید.
     */
    public static function canDelete($course)
    {
        if (!self::canManage($course))
            return false;
        if (!self::isAdmin() && !in_array((string) $course->status, self::DELETABLE_STATUSES, true))
            return false;
        return !Users::find()->where(['courses._id' => (string) $course->_id])->exists();
    }

    /**
     * آیا کاربر جاری می‌تواند برای این واحد دوره ثبت کند.
     */
    public static function canUseUnit($unitId)
    {
        if (!is_string($unitId) || !preg_match('/^[a-f0-9]{24}$/i', $unitId))
            return false;
        if (self::isAdmin())
            return true;
        if (!in_array(self::role(), ['emp', 'broker'], true))
            return false;
        return in_array($unitId, self::units(), true);
    }

    /** کارگزار یا کارشناس واحد نباید تخفیف شهریه ثبت کند (بند ۵.۲ صورتجلسه) */
    public static function canSetDiscount()
    {
        return self::isAdmin();
    }
}
