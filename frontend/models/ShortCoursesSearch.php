<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\components\CourseAccess;
use app\components\CourseStatus;

/**
 * جست‌وجو و فیلتر دوره‌های کوتاه‌مدت (courses.type = '1').
 * محدودیت دسترسی (CourseAccess) همیشه قبل از فیلترها اعمال می‌شود.
 */
class ShortCoursesSearch extends Model
{
    /** عنوان یا کد مجوز */
    public $q;
    public $unit;
    public $broker;
    public $teacher;
    /** یکی از کلیدهای CourseStatus::FILTERS */
    public $status;
    public $content_type;
    /** بازه‌ی تاریخ برگزاری (شروع دوره)، شمسی 1404/01/01 */
    public $start_from;
    public $start_to;
    /** بازه‌ی تاریخ ثبت/درخواست دوره، شمسی */
    public $created_from;
    public $created_to;
    public $sort_by;
    public $per_page;

    const TYPE = '1';
    const PAGE_SIZES = [20, 50, 100];

    public function formName()
    {
        return 'CS';
    }

    public function rules()
    {
        return [
            [['q', 'unit', 'broker', 'teacher', 'status', 'content_type', 'start_from', 'start_to', 'created_from', 'created_to', 'sort_by'], 'string', 'max' => 100],
            [['unit', 'broker', 'teacher'], 'match', 'pattern' => '/^[a-f0-9]{24}$/i'],
            [['status'], 'in', 'range' => array_map('strval', array_keys(CourseStatus::FILTERS))],
            [['content_type'], 'in', 'range' => ['1', '2', '3', '4']],
            [['start_from', 'start_to', 'created_from', 'created_to'], 'match', 'pattern' => '/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            [['sort_by'], 'in', 'range' => ['newest', 'oldest', 'start']],
            [['per_page'], 'in', 'range' => self::PAGE_SIZES],
        ];
    }

    /**
     * کوئری پایه: نوع دوره + دسترسی. مدیر، دوره‌هایی را که هنوز در مرحله‌ی واحد هستند (۷، ۸، ۹) نمی‌بیند.
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public static function scopedQuery()
    {
        $query = Courses::find()->where(['type' => self::TYPE, 'from_pec' => ['$ne' => true]]);
        CourseAccess::applyScope($query);
        if (CourseAccess::isAdmin())
            $query->andWhere(['status' => ['$nin' => [CourseStatus::AWAITING_UNIT, CourseStatus::UNIT_CORRECTION, CourseStatus::UNIT_REJECTED]]]);
        return $query;
    }

    public function search($params)
    {
        $query = self::scopedQuery();
        $this->load($params);
        foreach ($this->safeAttributes() as $attribute)
            if ($this->$attribute !== null && !is_scalar($this->$attribute))
                $this->$attribute = null;
        foreach (['q', 'start_from', 'start_to', 'created_from', 'created_to'] as $attribute)
            if (is_string($this->$attribute))
                $this->$attribute = UsersSearch::normalizeDigits($this->$attribute);
        if ($this->per_page === '')
            $this->per_page = null;

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->pageSize()],
            'sort' => false,
        ]);
        if (!$this->validate()) {
            $query->andWhere(['_id' => null]);
            return $provider;
        }

        if ($this->q !== null && $this->q !== '')
            $query->andWhere(['or', ['like', 'title.main_fa', $this->q], ['like', 'title.main_en', $this->q], ['like', 'license_code', $this->q]]);
        if ($this->unit)
            $query->andWhere(['college' => $this->unit]);
        if ($this->broker)
            $query->andWhere(['broker._id' => $this->broker]);
        if ($this->teacher)
            $query->andWhere(['or', ['lessons.teachers' => $this->teacher], ['other_teachers' => $this->teacher]]);
        if ($this->content_type)
            $query->andWhere(['content_type' => $this->content_type]);
        if (($condition = CourseStatus::filterCondition($this->status)) !== null)
            $query->andWhere($condition);

        // تاریخ برگزاری به‌صورت رشته‌ی شمسی YYYY-MM-DD ذخیره شده؛ مقایسه‌ی رشته‌ای درست است
        if (($from = self::jalaliKey($this->start_from)) !== null)
            $query->andWhere(['lessons.0.date.from' => ['$gte' => $from]]);
        if (($to = self::jalaliKey($this->start_to)) !== null)
            $query->andWhere(['lessons.0.date.from' => ['$lte' => $to]]);

        $created1 = UsersSearch::jalaliToTimestamp($this->created_from, false);
        $created2 = UsersSearch::jalaliToTimestamp($this->created_to, true);
        if ($created1 !== null)
            $query->andWhere(['_id' => ['$gte' => UsersSearch::objectIdFromTime($created1)]]);
        if ($created2 !== null)
            $query->andWhere(['_id' => ['$lte' => UsersSearch::objectIdFromTime($created2, true)]]);

        if ($this->sort_by === 'oldest')
            $query->orderBy(['_id' => SORT_ASC]);
        else if ($this->sort_by === 'start')
            $query->orderBy(['lessons.0.date.from' => SORT_DESC, '_id' => SORT_DESC]);
        else
            $query->orderBy(['_id' => SORT_DESC]);
        return $provider;
    }

    public function pageSize()
    {
        return in_array((int) $this->per_page, self::PAGE_SIZES, true) ? (int) $this->per_page : 50;
    }

    public function hasFilters()
    {
        foreach (['q', 'unit', 'broker', 'teacher', 'status', 'content_type', 'start_from', 'start_to', 'created_from', 'created_to'] as $attribute)
            if ($this->$attribute !== null && $this->$attribute !== '')
                return true;
        return false;
    }

    /**
     * 1404/7/1 → 1404-07-01
     */
    public static function jalaliKey($date)
    {
        if (!is_string($date) || !preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $m))
            return null;
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    /**
     * آمار دوره‌های در دسترس کاربر جاری.
     */
    public static function stats()
    {
        $count = function ($condition = null) {
            $query = self::scopedQuery();
            if ($condition !== null)
                $query->andWhere($condition);
            return (int) $query->count();
        };
        $stats = [
            'total' => $count(),
            'awaiting' => $count(CourseStatus::filterCondition('awaiting')),
            'corrected' => $count(CourseStatus::filterCondition('corrected')),
            'correction' => $count(['status' => [CourseStatus::NEEDS_CORRECTION, CourseStatus::UNIT_CORRECTION]]),
            'active' => $count(['status' => CourseStatus::ACTIVE]),
            'finished' => $count(['status' => CourseStatus::FINISHED]),
        ];
        if (!CourseAccess::isAdmin())
            $stats['unit'] = $count(CourseStatus::filterCondition(CourseStatus::AWAITING_UNIT));
        return $stats;
    }
}
