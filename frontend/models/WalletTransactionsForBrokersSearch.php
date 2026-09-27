<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\WalletTransactions;

/**
 * AdminSearch represents the model behind the search form of `app\models\WalletTransactions`.
 */
class WalletTransactionsForBrokersSearch extends WalletTransactions
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
                'description',
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

        // فیلترهای اصلی
        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'broker_id', $this->broker_id])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andWhere(['status' => '1'])
            ->andFilterWhere(['like', 'type', $this->type]);

        // فیلتر payer.mobile با بررسی وجود اندیس
        if (is_array($this->payer) && isset($this->payer['mobile'])) {
            $query->andFilterWhere(['like', 'payer.mobile', $this->payer['mobile']]);
        } elseif (!empty($this->payer) && !is_array($this->payer)) {
            // اگر payer یک string باشد (مثلاً شماره موبایل مستقیم)
            $query->andFilterWhere(['like', 'payer.mobile', $this->payer]);
        }

        // فیلتر payer.last_name با بررسی وجود اندیس
        if (is_array($this->payer) && isset($this->payer['last_name'])) {
            $query->andFilterWhere(['like', 'payer.last_name', $this->payer['last_name']]);
        }

        // فیلتر payment_info.tref با بررسی وجود اندیس
        if (is_array($this->payment_info) && isset($this->payment_info['tref'])) {
            $query->andFilterWhere(['like', 'payment_info.tref', $this->payment_info['tref']]);
        }

        // فیلترهای بر اساس نقش کاربر
        if($user->role == 'user' || $user->role == 'cnt') {
            $query->andFilterWhere(['like', 'college', $this->college]);
        } else if($user->role == 'emp') {
            $query->andWhere(['college' => $user->college]);
        } else if($user->role == 'broker') {
            $broker = Brokers::find()->where(['connector_info.mobile' => $user->username])->one();
            if ($broker) {
                $query->andWhere(['broker_id' => (string) $broker->_id]);
            }
        }

        $query->orderBy(['_id' => SORT_DESC]);
        return $dataProvider;
    }
}