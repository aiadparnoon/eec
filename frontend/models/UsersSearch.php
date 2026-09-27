<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;
use app\components\StudentAccess;
use common\models\Admin;

/**
 * جست‌وجو و فیلتر دانشپذیران (users) برای صفحه‌ی مدیریت کاربران و خروجی اکسل.
 * محدودیت دسترسی (docs/specs/users-manage.md بند ۱) همیشه قبل از فیلترها اعمال می‌شود.
 */
class UsersSearch extends Users
{
    /** جست‌وجوی کلی: نام، نام خانوادگی، نام کاربری یا کد ملی */
    public $q;
    public $national_code;
    /** شناسه‌ی دانشکده یا 'none' (بدون دانشکده) */
    public $college_id;
    public $course_id;
    /** وضعیت در دوره: '0' ثبت‌نشده در کلاس آنلاین، '1' فعال، '2' غیرفعال */
    public $course_status;
    /** self | me | user | emp | broker | unknown */
    public $registrant_type;
    /** '1' دارای دوره، '0' بدون دوره */
    public $has_courses;
    /** '1' دارای کد ملی، '0' بدون کد ملی */
    public $has_national_code;
    /** '1' مرد، '0' زن */
    public $gender;
    /** '1' دارای حساب کلاس آنلاین (principal_id) */
    public $has_platform_account;
    /** تاریخ شمسی 1404/01/01 */
    public $created_from;
    public $created_to;
    /** newest | oldest | name */
    public $sort_by;
    public $per_page;

    const PAGE_SIZES = [20, 50, 100];

    public function rules()
    {
        return [
            [['first_name', 'last_name', 'username', 'q', 'national_code', 'college_id', 'course_id', 'course_status',
                'registrant_type', 'has_courses', 'has_national_code', 'gender', 'has_platform_account',
                'created_from', 'created_to', 'sort_by', 'role'], 'string', 'max' => 100],
            [['status'], 'in', 'range' => ['9', '10', 9, 10]],
            [['course_status'], 'in', 'range' => ['0', '1', '2']],
            [['registrant_type'], 'in', 'range' => ['self', 'me', 'user', 'emp', 'broker', 'unknown']],
            [['has_courses', 'has_national_code', 'gender', 'has_platform_account'], 'in', 'range' => ['0', '1']],
            [['role'], 'in', 'range' => ['user', 'mentor']],
            [['sort_by'], 'in', 'range' => ['newest', 'oldest', 'name']],
            [['college_id', 'course_id'], 'match', 'pattern' => '/^([a-f0-9]{24}|none)$/i'],
            [['created_from', 'created_to'], 'match', 'pattern' => '/^\d{4}\/\d{1,2}\/\d{1,2}$/'],
            [['per_page'], 'in', 'range' => self::PAGE_SIZES],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function formName()
    {
        return 'UsersSearch';
    }

    /**
     * کوئری پایه با محدودیت دسترسی کاربر جاری.
     *
     * @return \yii\mongodb\ActiveQuery
     */
    public static function scopedQuery()
    {
        $query = Users::find();
        StudentAccess::applyScope($query);
        return $query;
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        return new ActiveDataProvider([
            'query' => $this->buildQuery($params),
            'pagination' => ['pageSize' => $this->pageSize()],
            'sort' => false,
        ]);
    }

    /**
     * کوئری فیلترشده (مشترک فهرست و خروجی اکسل تا خروجی دقیقاً همان چیزی باشد که کاربر می‌بیند).
     *
     * @param array $params
     * @return \yii\mongodb\ActiveQuery
     */
    public function buildQuery($params)
    {
        $query = self::scopedQuery();

        $this->load($params);
        $this->normalizeInput();

        if (!$this->validate()) {
            // فیلتر نامعتبر: هیچ نتیجه‌ای (به جای نادیده گرفتن بی‌صدای فیلتر)
            return $query->andWhere(['_id' => null]);
        }

        $this->applyFilters($query);

        if ($this->sort_by === 'oldest')
            $query->orderBy(['_id' => SORT_ASC]);
        else if ($this->sort_by === 'name')
            $query->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);
        else
            $query->orderBy(['_id' => SORT_DESC]);

        return $query;
    }

    public function pageSize()
    {
        return in_array((int) $this->per_page, self::PAGE_SIZES, true) ? (int) $this->per_page : 50;
    }

    /**
     * آیا فیلتری (به جز مرتب‌سازی و تعداد در صفحه) فعال است.
     */
    public function hasFilters()
    {
        foreach (['q', 'first_name', 'last_name', 'username', 'national_code', 'college_id', 'course_id', 'course_status',
                     'registrant_type', 'has_courses', 'has_national_code', 'gender', 'has_platform_account',
                     'created_from', 'created_to', 'status', 'role'] as $attribute)
            if ($this->$attribute !== null && $this->$attribute !== '')
                return true;
        return false;
    }

    /**
     * ارقام فارسی/عربی را به لاتین تبدیل و فاصله‌های اضافه را حذف می‌کند.
     */
    public static function normalizeDigits($value)
    {
        if (!is_string($value))
            return $value;
        if (!mb_check_encoding($value, 'UTF-8'))
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8'); // بایت نامعتبر در regex مونگو خطا می‌دهد
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        return trim($value);
    }

    private function normalizeInput()
    {
        foreach (['q', 'first_name', 'last_name', 'username', 'national_code', 'created_from', 'created_to'] as $attribute)
            if (is_string($this->$attribute))
                $this->$attribute = self::normalizeDigits($this->$attribute);
        if (is_string($this->username))
            $this->username = mb_strtolower($this->username, 'UTF-8');
        foreach (['per_page', 'status'] as $attribute)
            if ($this->$attribute === '')
                $this->$attribute = null;
        if ($this->status !== null && $this->status !== '')
            $this->status = (string) $this->status;
    }

    /**
     * @param \yii\mongodb\ActiveQuery $query
     */
    private function applyFilters($query)
    {
        $query->andFilterWhere(['like', 'last_name', $this->last_name])
            ->andFilterWhere(['like', 'first_name', $this->first_name])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'issuance_certificate_information.id', $this->national_code]);

        if ($this->q !== null && $this->q !== '') {
            $or = ['or',
                ['like', 'first_name', $this->q],
                ['like', 'last_name', $this->q],
                ['like', 'username', mb_strtolower($this->q, 'UTF-8')],
                ['like', 'issuance_certificate_information.id', $this->q],
            ];
            // «نام نام‌خانوادگی» با هم
            $parts = preg_split('/\s+/u', $this->q, 2);
            if (count($parts) === 2)
                $or[] = ['and', ['like', 'first_name', $parts[0]], ['like', 'last_name', $parts[1]]];
            $query->andWhere($or);
        }

        if ($this->status !== null && $this->status !== '')
            $query->andWhere(['status' => (int) $this->status]);

        if ($this->role === 'mentor')
            $query->andWhere(['role' => 'mentor']);
        else if ($this->role === 'user')
            $query->andWhere(['role' => ['$ne' => 'mentor']]);

        if ($this->college_id === 'none')
            $query->andWhere(['or', ['college' => null], ['college' => ''], ['college' => ['$size' => 0]]]);
        else if ($this->college_id)
            // برای مقدار رشته‌ای قدیمی و آرایه‌ای هر دو کار می‌کند
            $query->andWhere(['college' => $this->college_id]);

        if ($this->course_id && $this->course_id !== 'none') {
            $match = ['_id' => $this->course_id];
            if ($this->course_status !== null && $this->course_status !== '')
                $match['status'] = $this->course_status;
            $query->andWhere(['courses' => ['$elemMatch' => $match]]);
        } else if ($this->course_status !== null && $this->course_status !== '') {
            $query->andWhere(['courses' => ['$elemMatch' => ['status' => $this->course_status]]]);
        }

        if ($this->has_courses === '1')
            $query->andWhere(['courses.0' => ['$exists' => true]]);
        else if ($this->has_courses === '0')
            $query->andWhere(['courses.0' => ['$exists' => false]]);

        if ($this->has_national_code === '1')
            $query->andWhere(['issuance_certificate_information.id' => ['$nin' => [null, '']]]);
        else if ($this->has_national_code === '0')
            $query->andWhere(['issuance_certificate_information.id' => ['$in' => [null, '']]]);

        if ($this->gender === '1' || $this->gender === '0')
            $query->andWhere(['issuance_certificate_information.gender' => $this->gender]);

        if ($this->has_platform_account === '1')
            $query->andWhere(['principal_id' => ['$nin' => [null, '']]]);
        else if ($this->has_platform_account === '0')
            $query->andWhere(['principal_id' => ['$in' => [null, '']]]);

        $this->applyRegistrantFilter($query);

        $from = self::jalaliToTimestamp($this->created_from, false);
        $to = self::jalaliToTimestamp($this->created_to, true);
        if ($from !== null)
            $query->andWhere(['_id' => ['$gte' => self::objectIdFromTime($from)]]);
        if ($to !== null)
            $query->andWhere(['_id' => ['$lte' => self::objectIdFromTime($to, true)]]);
    }

    private function applyRegistrantFilter($query)
    {
        switch ($this->registrant_type) {
            case 'self':
                $query->andWhere(['$expr' => ['$eq' => ['$registrant', '$username']]]);
                break;
            case 'me':
                $query->andWhere(['registrant' => (string) Yii::$app->user->identity->username]);
                break;
            case 'unknown':
                $query->andWhere(['registrant' => ['$in' => [null, '']]]);
                break;
            case 'user':
            case 'emp':
            case 'broker':
                $roles = $this->registrant_type === 'user' ? ['user', 'cnt'] : [$this->registrant_type];
                $usernames = [];
                foreach (Admin::find()->select(['username'])->where(['role' => $roles])->asArray()->all() as $admin)
                    if (isset($admin['username']))
                        $usernames[] = (string) $admin['username'];
                if ($this->registrant_type === 'user')
                    $usernames[] = (string) Yii::getAlias('@adminUsername');
                $query->andWhere(['registrant' => ['$in' => array_values(array_unique($usernames))]]);
                break;
        }
    }

    /**
     * @param string|null $date تاریخ شمسی 1404/1/15
     * @param bool $endOfDay
     * @return int|null
     */
    public static function jalaliToTimestamp($date, $endOfDay)
    {
        if (!is_string($date) || !preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $m))
            return null;
        require_once Yii::getAlias('@frontend') . '/web/jdf.php';
        list($gy, $gm, $gd) = jalali_to_gregorian((int) $m[1], (int) $m[2], (int) $m[3]);
        $tz = new \DateTimeZone('Asia/Tehran');
        $dt = \DateTime::createFromFormat('Y-n-j H:i:s', $gy . '-' . $gm . '-' . $gd . ($endOfDay ? ' 23:59:59' : ' 00:00:00'), $tz);
        return $dt === false ? null : $dt->getTimestamp();
    }

    public static function objectIdFromTime($timestamp, $max = false)
    {
        return new \MongoDB\BSON\ObjectId(sprintf('%08x', $timestamp) . ($max ? 'ffffffffffffffff' : '0000000000000000'));
    }

    /**
     * آمار دانشپذیرانِ در دسترس کاربر جاری (بدون فیلترهای جست‌وجو).
     *
     * @return array
     */
    public static function stats()
    {
        $count = function ($condition = null) {
            $query = self::scopedQuery();
            if ($condition !== null)
                $query->andWhere($condition);
            return (int) $query->count();
        };
        $monthAgo = self::objectIdFromTime(time() - 30 * 86400);

        $stats = [
            'total' => $count(),
            'active' => $count(['status' => 10]),
            'inactive' => $count(['status' => 9]),
            'withCourses' => $count(['courses.0' => ['$exists' => true]]),
            'newThisMonth' => $count(['_id' => ['$gte' => $monthAgo]]),
            'withoutNationalCode' => $count(['issuance_certificate_information.id' => ['$in' => [null, '']]]),
            'pendingPlatform' => $count(['courses' => ['$elemMatch' => ['status' => '0']]]),
            'colleges' => [],
        ];
        $stats['withoutCourses'] = $stats['total'] - $stats['withCourses'];

        // تعداد به تفکیک دانشکده (college رشته‌ای یا آرایه‌ای؛ $unwind هر دو را پوشش می‌دهد)
        $scope = self::scopedQuery();
        $match = $scope->where ? Yii::$app->mongodb->getQueryBuilder()->buildCondition($scope->where) : [];
        $pipeline = [];
        if (!empty($match))
            $pipeline[] = ['$match' => $match];
        $pipeline[] = ['$project' => ['college' => 1]];
        $pipeline[] = ['$unwind' => ['path' => '$college', 'preserveNullAndEmptyArrays' => true]];
        $pipeline[] = ['$group' => ['_id' => '$college', 'count' => ['$sum' => 1]]];
        $rows = Users::getCollection()->aggregate($pipeline);
        $visible = StudentAccess::isAdmin() ? null : StudentAccess::staffColleges();
        $none = 0;
        foreach ($rows as $row) {
            $id = $row['_id'] === null ? '' : (string) $row['_id'];
            if ($id === '') {
                $none += (int) $row['count'];
                continue;
            }
            if ($visible !== null && !in_array($id, $visible, true))
                continue;
            $stats['colleges'][$id] = (isset($stats['colleges'][$id]) ? $stats['colleges'][$id] : 0) + (int) $row['count'];
        }
        arsort($stats['colleges']);
        $stats['noCollege'] = $none;
        return $stats;
    }
}
