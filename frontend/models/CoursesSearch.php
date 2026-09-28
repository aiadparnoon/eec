<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Courses;
use app\models\Teachers;
use app\models\Brokers;

/**
 * AdminSearch represents the model behind the search form of `app\models\Courses`.
 */
class CoursesSearch extends Courses
{
    /**
     * فیلتر «از تاریخ / تا تاریخ» توی لیست دوره‌ها (تغییر ۳ - 2026-08-28).
     * این‌ها فیلد واقعی مستندی نیستن - فقط برای گرفتن مقدار از فرم فیلتر
     * (GET) استفاده می‌شن؛ خودِ فیلتر بر اساس تاریخ ثبت رکورد (timestamp
     * توکار‌رفته توی _id) اجرا می‌شه، نه هیچ فیلد تاریخی دیگه‌ای روی سند.
     *
     * @var string|null
     */
    public $reg_date_from;
    public $reg_date_to;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'price',
                'discount_price',
                'broker',
                'college',
                'license_code',
                'student_capacity',
                'type',
                'content_type',
                'status',
                'rejection_reason',
                'preview_image',
                'date',
                'registration_deadline',
                'description',
                'teachers',
                'license_code',
                'mentors',
                'reg_date_from',
                'reg_date_to',
            ], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $type)
    {
        $query = Courses::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        // فیلتر «از تاریخ / تا تاریخ» - تغییر ۳ (2026-08-28): طبق دستور صریح
        // مستند، این فیلتر بر اساس تاریخ *ثبت رکورد* (یعنی timestamp توکار‌رفته
        // توی _id) اجراست، نه date[from]/date[to] یا هیچ فیلد تاریخی دیگه‌ای
        // روی خود سند. دقیقاً همون تکنیک موجود پروژه (dechex(strtotime(...))
        // به‌عنوان یک MongoDB\BSON\ObjectId) که همین الان توی OrdersSearch،
        // FinancialSearch، CoursesFinancialSearch و ExamParticipants استفاده
        // شده - اینجا هم عیناً تکرار شده، با این تفاوت که «تا تاریخ» به انتهای
        // همون روز (23:59:59) تنظیم می‌شه (طبق خواسته‌ی صریح مستند)، نه ابتدای
        // روز. مقایسه با _id واقعی (نه رشته‌ای) انجام می‌شه.
        require_once(Yii::$app->basePath . '/web/jdf.php');
        date_default_timezone_set('Asia/Tehran');
        $regDate1 = null;
        $regDate2 = null;
        if ($this->reg_date_from !== null && trim((string) $this->reg_date_from) !== '') {
            $parts = explode('-', $this->reg_date_from);
            if (count($parts) === 3) {
                $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                $ts = strtotime($g[0] . '-' . $g[1] . '-' . $g[2]); // ابتدای همون روز
                if ($ts !== false) {
                    $regDate1 = new \MongoDB\BSON\ObjectId(dechex($ts) . '0000000000000000');
                }
            }
        }
        if ($this->reg_date_to !== null && trim((string) $this->reg_date_to) !== '') {
            $parts = explode('-', $this->reg_date_to);
            if (count($parts) === 3) {
                $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                $ts = strtotime($g[0] . '-' . $g[1] . '-' . $g[2]);
                if ($ts !== false) {
                    $ts += 86399; // انتهای همون روز (23:59:59)
                    $regDate2 = new \MongoDB\BSON\ObjectId(dechex($ts) . '0000000000000000');
                }
            }
        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $user = Yii::$app->user->identity;
        $college = array($user->college);
        if($user->college == '65948ca8f08779a235072195')
            $college = array('65948ca8f08779a235072195','65afa2ea5136ec5b5b0c4064');
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    ($this->title['main_fa'] ?? null) ?? null
                ])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere(['like', 'status', $this->status])
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere([
                    'like',
                    'broker._id',
                    ($this->broker['_id'] ?? null) ?? null
                ])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->andWhere(['in','type', $type])
                ->andWhere(['<>','from_pec', true])
                ->andWhere(['<>','status', '7'])
                ->andWhere(['<>','status', '8'])
                ->andWhere(['<>','status', '9'])
                ->andFilterWhere(['>=', '_id', $regDate1])
                ->andFilterWhere(['<=', '_id', $regDate2])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'title.main_fa', ($this->title['main_fa'] ?? null)])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere(['like', 'broker._id', ($this->broker['_id'] ?? null)])
                ->andFilterWhere(['like', 'status', $this->status])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->andWhere(['type' => $type])
                ->andWhere(['IN','college' , $college])
                ->andWhere(['<>','from_pec', true])
                ->andFilterWhere(['>=', '_id', $regDate1])
                ->andFilterWhere(['<=', '_id', $regDate2])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'broker')
        {
            $brokerDetail = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'title.main_fa', ($this->title['main_fa'] ?? null)])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere(['like', 'broker._id', ($this->broker['_id'] ?? null)])
                ->andFilterWhere(['like', 'status', $this->status])
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->andWhere(['type' => $type])
                ->andWhere(['<>','from_pec', true])
//                ->andWhere(['registrant' => $user->username])
                ->andWhere(['broker._id' => (string) $brokerDetail->_id])
                ->andFilterWhere(['>=', '_id', $regDate1])
                ->andFilterWhere(['<=', '_id', $regDate2])
                ->orderBy(['_id'=>SORT_DESC]);
        }
        else if($user->role == 'teacher')
        {
            if($user->mentor == 'mentor')
            {
                $query->andFilterWhere(['like', '_id', $this->_id])
                    ->andFilterWhere(['like', 'title.main_fa', ($this->title['main_fa'] ?? null)])
                    ->andFilterWhere(['like', 'license_code', $this->license_code])
                    ->andFilterWhere(['like', 'content_type', $this->content_type])
                    ->andFilterWhere(['like', 'broker._id', ($this->broker['_id'] ?? null)])
                    ->andFilterWhere(['like', 'status', $this->status])
                    ->andFilterWhere(['like', '_id', $this->_id])
                    ->andWhere(['type' => $type])
                    ->andWhere(['<>','from_pec', true])
                    ->andWhere(['mentors' => $user->username])
                    ->andFilterWhere(['>=', '_id', $regDate1])
                    ->andFilterWhere(['<=', '_id', $regDate2])
                    ->orderBy(['_id'=>SORT_DESC]);
            }
            else
            {
                $teacher = Teachers::find()->where(['mobile' => $user->username])->one();
                $query->andFilterWhere(['like', '_id', $this->_id])
                    ->andFilterWhere(['like', 'title.main_fa', ($this->title['main_fa'] ?? null)])
                    ->andFilterWhere(['like', 'license_code', $this->license_code])
                    ->andFilterWhere(['like', 'content_type', $this->content_type])
                    ->andFilterWhere(['like', 'broker._id', ($this->broker['_id'] ?? null)])
                    ->andFilterWhere(['like', 'status', $this->status])
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                    ->andWhere(['type' => $type])
                    ->andWhere(['<>','from_pec', true]);
                // رفع باگ (۲۰۲۶-۰۸-۲۸): وقتی کاربرِ نقشِ «استاد» توی کالکشن Teachers
                // (بر اساس شماره موبایل) ثبت نشده باشه، $teacher قبلاً null برمی‌گشت
                // و خط بعدی ((string) $teacher->_id) یه اخطار خام PHP (انگلیسی) نشون
                // می‌داد. حالا در این حالت، بدون هیچ خطایی، دیتاپرووایدر خالی برمی‌گرده
                // (این کاربر منطقاً نباید هیچ دوره‌ای رو ببینه، چون به هیچ درسی وصل نیست).
                if ($teacher !== null) {
                    $query->andWhere(['or',
                        ['lessons.teachers' => (string) $teacher->_id],
                        ['IN' ,'other_teachers', (string) $teacher->_id]
                    ]);
                } else {
                    $query->andWhere(['_id' => null]);
                }
                $query->andFilterWhere(['>=', '_id', $regDate1])
                    ->andFilterWhere(['<=', '_id', $regDate2])
                    ->orderBy(['_id'=>SORT_DESC]);
            }
        }
        return $dataProvider;
    }
}
