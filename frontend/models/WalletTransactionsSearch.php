<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\WalletTransactions;

/**
 * AdminSearch represents the model behind the search form of `app\models\WalletTransactions`.
 */
class WalletTransactionsSearch extends WalletTransactions
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'broker_id',
                'amount',
                'payment_info',
                'type',
                'status',
                'college',
                'payer',
                'reference_id'
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
        $query = WalletTransactions::find();

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
            ->andFilterWhere(['like', 'broker_id', $this->broker_id])
            ->andWhere(['status' => '1'])
            ->andFilterWhere(['like', 'type', $this->type]);
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', 'college', $this->college]);
        else if($user->role == 'emp')
            $query->andWhere(['college' => $user->college]);
        else if($user->role == 'broker')
        {
            $broker = Brokers::find()->where(['connector_info.mobile' => $user->username])->one();
            $query->andWhere(['broker_id' => (string) $broker->_id]);
        }
        $query->orderBy(['_id' => SORT_DESC]);
        return $dataProvider;
    }
}
