<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Brokers;

/**
 * AdminSearch represents the model behind the search form of `app\models\Brokers`.
 */
class BrokersSearch extends Brokers
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'company_info',
                'connector_info',
                'financial_info',
                'contracts',
                'statute_file',
                'newspaper_file',
                'id_file',
                'registrant',
                'college',
                'status',
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
        $query = Brokers::find();

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
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'connector_info.last_name',
                    $this->connector_info['last_name'] ?? null
                ])
                ->andFilterWhere([
                    'like',
                    'connector_info.mobile',
                    $this->connector_info['mobile'] ?? null
                ])
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'status', $this->status])
                ->orderBy(['_id' => SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'connector_info.last_name',
                    $this->connector_info['last_name'] ?? null
                ])
                ->andFilterWhere([
                    'like',
                    'connector_info.mobile',
                    $this->connector_info['mobile'] ?? null
                ])
                ->andWhere(['college' => $user->college])
                ->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }
}
