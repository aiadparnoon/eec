<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CancelingRequests;

/**
 * AdminSearch represents the model behind the search form of `app\models\CancelingRequests`.
 */
class CancelingRequestsSearch extends CancelingRequests
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'course_id',
                'order_id',
                'college',
                'registrant',
                'registrant_role',
                'request_date',
                'status',
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
        $query = CancelingRequests::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        $user = Yii::$app->user->identity;
        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'course_id', $this->course_id])
            ->andFilterWhere(['like', 'status', $this->status]);
        if($user->role == 'emp')
            $query->where([ 'college' => $user->college]);
        else if($user->role == 'broker')
        {
            $broker = Brokers::find()->where(['connector_info.mobile' => $user->username])->one();
            $query->where([ 'registrant' => (string) $broker->_id]);
        }
        $query->orderBy(['_id' => SORT_DESC]);
        return $dataProvider;
    }
}
