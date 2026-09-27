<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Installments;
use app\models\Brokers;
use yii\data\ArrayDataProvider;

/**
 * InstallmentsReportSearch represents the model behind the search form of `app\models\Installments`.
 */
class InstallmentsSearch extends Installments
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'first_name',
                'last_name',
                'course_id',
                'college',
                'broker',
                'order_id',
                'status',
                'maturities',
                'createdAt',
                'updatedAt',
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
    public function search($params)
    {
        $query = Installments::find();

        $this->load($params);

        date_default_timezone_set('Asia/Tehran');

        $startDate = null;
        $endDate = null;

        if (isset($params['date1']) && $params['date1'] != '') {
            $startDate = str_replace('-', '/', $params['date1']);
        }
        if (isset($params['date2']) && $params['date2'] != '') {
            $endDate = str_replace('-', '/', $params['date2']);
        }

        if (!$this->validate()) {
            return new ArrayDataProvider(['allModels' => []]);
        }

        // شرط فیلتر بر اساس تاریخ و وضعیت
        $dateCondition = [];

        if ($startDate !== null && $endDate !== null) {
            $dateCondition = [
                'maturities' => [
                    '$elemMatch' => [
                        'payment_info.date' => ['$gte' => $startDate, '$lte' => $endDate],
                        'status' => '1'
                    ]
                ]
            ];
        } elseif ($startDate !== null) {
            $dateCondition = [
                'maturities' => [
                    '$elemMatch' => [
                        'payment_info.date' => ['$gte' => $startDate],
                        'status' => '1'
                    ]
                ]
            ];
        } elseif ($endDate !== null) {
            $dateCondition = [
                'maturities' => [
                    '$elemMatch' => [
                        'payment_info.date' => ['$lte' => $endDate],
                        'status' => '1'
                    ]
                ]
            ];
        }

        // اعمال شرایط بر اساس نقش کاربر
        if (Yii::$app->user->identity->role == 'user') {
            $query->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'broker', $this->broker]);
        } else if (Yii::$app->user->identity->role == 'emp') {
            $query->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['college' => Yii::$app->user->identity->college])
                ->andFilterWhere(['like', 'broker', $this->broker]);
        } else if (Yii::$app->user->identity->role == 'broker') {
            $broker = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            $query->andFilterWhere(['like', 'username', $this->username])
                ->andWhere(['broker' => (string) $broker->_id]);
        }

        // شرط وجود maturities
        $query->andWhere([
            'maturities' => [
                '$exists' => true,
                '$ne' => null,
                '$not' => ['$size' => 0]
            ]
        ]);

        // اعمال شرط تاریخ‌ها و وضعیت
        if (!empty($dateCondition)) {
            $query->andWhere($dateCondition);
        }

        $query->orderBy(['updatedAt' => SORT_DESC]);

        // استفاده از ArrayDataProvider به جای ActiveDataProvider
        $models = $query->all();

        return new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => false,
            'sort' => false,
        ]);
    }
}
