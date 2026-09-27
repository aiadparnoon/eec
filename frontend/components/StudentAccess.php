<?php

namespace app\components;

use Yii;
use app\models\Users;

/**
 * قواعد دسترسی کارکنان به دانشپذیران (مجموعه‌ی users).
 *
 * طبق docs/specs/users-manage.md بند ۱:
 *  - نقش‌های user و cnt (مدیر سیستم) همه‌ی دانشپذیران را می‌بینند.
 *  - کارشناس دانشکده (emp) فقط دانشپذیرانی را می‌بیند که users.college آن‌ها شامل یکی
 *    از دانشکده‌های خودش باشد.
 *  - کارگزار (بند ۱.۳) هنوز نهایی نشده؛ فعلاً همان رفتار قبلی (فقط کاربرانی که خودش
 *    ثبت کرده) حفظ می‌شود.
 *
 * همین قاعده هم برای فهرست (applyScope) و هم برای هر اکشن تغییر (canManage) استفاده
 * می‌شود تا نتوان با ارسال _id کاربر دیگر، او را ویرایش کرد (IDOR).
 *
 * users.college تا اجرای مهاجرت (بند ۲) ممکن است رشته یا آرایه باشد؛ هر دو پشتیبانی می‌شوند.
 *
 * @since 2026-09-27
 */
class StudentAccess
{
    /**
     * @return bool آیا کاربر جاری مدیر سیستم است (همه‌چیز را می‌بیند)
     */
    public static function isAdmin($identity = null)
    {
        $identity = $identity ?: Yii::$app->user->identity;
        return $identity !== null && in_array($identity->role, ['user', 'cnt'], true);
    }

    /**
     * شناسه‌ی دانشکده‌های کارمند جاری، به صورت آرایه‌ای از رشته.
     *
     * @return string[]
     */
    public static function staffColleges($identity = null)
    {
        $identity = $identity ?: Yii::$app->user->identity;
        return self::normalizeColleges($identity === null ? null : $identity->college);
    }

    /**
     * فیلتر دسترسی را روی کوئری Users اعمال می‌کند.
     *
     * @param \yii\mongodb\ActiveQuery $query
     * @return \yii\mongodb\ActiveQuery
     */
    public static function applyScope($query, $identity = null)
    {
        $identity = $identity ?: Yii::$app->user->identity;
        if ($identity === null)
            return $query->andWhere(['_id' => null]);
        if (self::isAdmin($identity))
            return $query;

        if ($identity->role == 'emp') {
            // شرط in (همان $in مونگو) روی فیلد رشته‌ای و آرایه‌ای هر دو درست کار می‌کند.
            // موقت: کاربران قدیمی که خود کارشناس ثبت کرده ولی college ندارند هم نمایش داده
            // می‌شوند (کاربران جدید هنگام ثبت، دانشکده‌ی کارشناس را می‌گیرند).
            $colleges = self::staffColleges($identity);
            $conditions = ['or', ['registrant' => (string) $identity->username]];
            if (!empty($colleges))
                $conditions[] = ['in', 'college', $colleges];
            return $query->andWhere($conditions);
        }

        // کارگزار و سایر نقش‌ها: رفتار قبلی (بند ۱.۳ هنوز تصمیم‌گیری نشده)
        return $query->andWhere(['registrant' => (string) $identity->username]);
    }

    /**
     * آیا کاربر جاری مجاز به مشاهده/تغییر این دانشپذیر است.
     * باید با applyScope هم‌خوان بماند.
     *
     * @param Users|null $student
     * @return bool
     */
    public static function canManage($student, $identity = null)
    {
        $identity = $identity ?: Yii::$app->user->identity;
        if ($student === null || $identity === null)
            return false;
        if (self::isAdmin($identity))
            return true;

        $isOwnRegistrant = $student->registrant !== null
            && (string) $student->registrant === (string) $identity->username;

        if ($identity->role == 'emp') {
            if ($isOwnRegistrant)
                return true;
            return count(array_intersect(
                self::normalizeColleges($student->college),
                self::staffColleges($identity)
            )) > 0;
        }

        return $isOwnRegistrant;
    }

    /**
     * دانشپذیر با _id داده‌شده را فقط در صورت داشتن دسترسی برمی‌گرداند.
     *
     * @param mixed $id
     * @return Users|null
     */
    public static function findStudent($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id))
            return null;
        $student = Users::findOne($id);
        return self::canManage($student) ? $student : null;
    }

    /**
     * دانشکده‌هایی که هنگام ثبت دانشپذیر توسط کاربر جاری باید به users.college اضافه شوند.
     *  - مدیر سیستم (user/cnt): هیچ دانشکده‌ای (دانشپذیر عمومی است).
     *  - کارشناس دانشکده (emp): دانشکده‌(های) خود کارشناس؛ فرم ثبت دیگر دانشکده نمی‌پرسد.
     *  - سایر نقش‌ها (از جمله کارگزار تا تصمیم بند ۱.۳): هیچ.
     *
     * @return string[]
     */
    public static function collegesForNewStudent($identity = null)
    {
        $identity = $identity ?: Yii::$app->user->identity;
        if ($identity === null || self::isAdmin($identity) || $identity->role != 'emp')
            return [];
        return self::staffColleges($identity);
    }

    /**
     * دانشکده‌ها را (بدون تکرار) به آرایه‌ی users.college اضافه می‌کند؛ مقدار رشته‌ای قدیمی
     * هم به آرایه تبدیل می‌شود. ذخیره با فراخواننده است.
     *
     * @param Users $student
     * @param string[] $colleges
     * @return bool آیا مقدار تغییر کرد
     */
    public static function addColleges($student, array $colleges)
    {
        $current = self::normalizeColleges($student->college);
        $merged = self::normalizeColleges(array_merge($current, $colleges));
        if ($merged === $current && is_array($student->college))
            return false;
        $student->college = $merged;
        return true;
    }

    /**
     * مقدار college (رشته، ObjectId، آرایه یا null) را به آرایه‌ای از رشته‌های غیرخالی تبدیل می‌کند.
     *
     * @return string[]
     */
    public static function normalizeColleges($value)
    {
        if ($value === null || $value === '')
            return [];
        if (!is_array($value))
            $value = [$value];
        $result = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '')
                $result[] = $item;
        }
        return array_values(array_unique($result));
    }
}
