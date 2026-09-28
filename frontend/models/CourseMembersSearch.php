<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\UsersSearch;

/**
 * جست‌وجوی اعضای یک دوره (تب «اعضا» در صفحه‌ی دوره). فقط مقادیر رشته‌ای پذیرفته می‌شوند و
 * در regex به‌صورت متن ساده (preg_quote) قرار می‌گیرند؛ آرایه/عملگر Mongo از ورودی عبور نمی‌کند.
 */
class CourseMembersSearch extends Model
{
    const STATUSES = [
        '1' => 'فعال',
        '2' => 'غیرفعال',
        '0' => 'ثبت‌نشده در کلاس آنلاین',
        'cancel' => 'درخواست انصراف در انتظار',
    ];

    public $first_name;
    public $last_name;
    public $username;
    public $national_code;
    public $status;

    public function formName()
    {
        return 'CM';
    }

    public function rules()
    {
        return [
            [['first_name', 'last_name', 'username', 'national_code', 'status'], 'filter', 'filter' => function ($value) {
                return is_scalar($value) ? mb_substr(trim((string) $value), 0, 100, 'UTF-8') : null;
            }],
            ['status', 'in', 'range' => array_map('strval', array_keys(self::STATUSES)), 'skipOnEmpty' => true],
        ];
    }

    public function hasFilters()
    {
        foreach (['first_name', 'last_name', 'username', 'national_code', 'status'] as $attribute)
            if ($this->$attribute !== null && $this->$attribute !== '')
                return true;
        return false;
    }

    /**
     * @return \yii\mongodb\ActiveQuery
     */
    public function query($params, $courseId)
    {
        $courseId = (string) $courseId;
        $query = Users::find()->where(['courses._id' => $courseId]);
        $this->load($params);
        if (!$this->validate())
            return $query->andWhere(['_id' => null]);

        $regex = function ($value) {
            return ['$regex' => preg_quote($value, '/'), '$options' => 'i'];
        };
        if ($this->first_name !== null && $this->first_name !== '')
            $query->andWhere(['first_name' => $regex($this->first_name)]);
        if ($this->last_name !== null && $this->last_name !== '')
            $query->andWhere(['last_name' => $regex($this->last_name)]);
        if ($this->username !== null && $this->username !== '')
            $query->andWhere(['username' => $regex(mb_strtolower(UsersSearch::normalizeDigits($this->username), 'UTF-8'))]);
        if ($this->national_code !== null && $this->national_code !== '') {
            $code = preg_replace('/\D/', '', UsersSearch::normalizeDigits($this->national_code));
            $query->andWhere($code === '' ? ['_id' => null] : ['issuance_certificate_information.id' => ['$regex' => '^' . preg_quote($code, '/')]]);
        }
        if ($this->status !== null && $this->status !== '') {
            $match = $this->status === 'cancel'
                ? ['_id' => $courseId, 'begin_deleted' => true]
                : ['_id' => $courseId, 'status' => (string) $this->status];
            $query->andWhere(['courses' => ['$elemMatch' => $match]]);
        }
        return $query;
    }

    /**
     * @return ActiveDataProvider
     */
    public function search($params, $courseId, $pageSize = 50)
    {
        return new ActiveDataProvider([
            'query' => $this->query($params, $courseId)->orderBy(['_id' => SORT_DESC]),
            'pagination' => ['pageSize' => $pageSize, 'pageParam' => 'page'],
        ]);
    }

    /**
     * آمار اعضای دوره (بدون فیلتر جست‌وجو).
     *
     * @return array
     */
    public static function stats($courseId)
    {
        $courseId = (string) $courseId;
        $count = function ($match) use ($courseId) {
            return (int) Users::find()->where(['courses' => ['$elemMatch' => array_merge(['_id' => $courseId], $match)]])->count();
        };
        return [
            'total' => (int) Users::find()->where(['courses._id' => $courseId])->count(),
            'active' => $count(['status' => '1']),
            'inactive' => $count(['status' => '2']),
            'pending' => $count(['status' => '0']),
            'cancel' => $count(['begin_deleted' => true]),
            'certificateReady' => (int) Users::find()->where(['courses._id' => $courseId])
                ->andWhere(['issuance_certificate_information.id' => ['$nin' => [null, '']]])
                ->andWhere(['issuance_certificate_information.first_name_en' => ['$nin' => [null, '']]])
                ->andWhere(['issuance_certificate_information.last_name_en' => ['$nin' => [null, '']]])
                ->count(),
        ];
    }
}
