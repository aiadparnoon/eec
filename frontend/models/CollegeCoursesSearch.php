<?php

namespace app\models;

use jDateTime;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Courses;
use app\models\Teachers;
use app\models\Brokers;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

/**
 * AdminSearch represents the model behind the search form of `app\models\Courses`.
 */
class CollegeCoursesSearch extends Courses
{
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
                'mentors'
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
    public function search($params, $persianYear, $college)
    {
        $query = Courses::find();
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $gregorianYear = $persianYear - 621;
        $startTimestamp = strtotime("$gregorianYear-03-21");
        $endTimestamp = strtotime(($gregorianYear + 1) . "-03-20");

        $query->where(['<>','from_pec', true])
            ->andWhere(['in','status', ['0','1','6']])
            ->andWhere(['college' => $college])
            ->andWhere(['<>','status', '8'])
            ->andWhere(['<>','status', '9'])
            ->orderBy(['_id'=>SORT_DESC]);

        // استفاده از aggregation pipeline
        $pipeline = [
            [
                '$addFields' => [
                    'objectIdStr' => ['$toString' => '$_id'],
                    'timestampHex' => ['$substrCP' => [['$toString' => '$_id'], 0, 8]],
                    'timestamp' => [
                        '$convert' => [
                            'input' => ['$substrCP' => [['$toString' => '$_id'], 0, 8]],
                            'to' => 'int',
                            'onError' => 0,
                            'onNull' => 0
                        ]
                    ]
                ]
            ],
            [
                '$match' => [
                    'timestamp' => [
                        '$gte' => $startTimestamp,
                        '$lt' => $endTimestamp
                    ]
                ]
            ],
            [
                '$sort' => ['_id' => -1]
            ]
        ];

        $query->addOptions(['pipeline' => $pipeline]);

        return $dataProvider;
    }
}
