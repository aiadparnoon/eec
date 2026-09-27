<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;
use app\models\Brokers;

/**
 * InstallmentsReportSearch represents the model behind the search form of `app\models\Orders`.
 */
class OrdersSearch extends Orders
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
                'username',
                'orders',
                'amount',
                'payment_info',
                'shares',
                'status'
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
        $query = Orders::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        $this->load($params);

        require_once(Yii::$app->basePath . '/web/jdf.php');
        date_default_timezone_set('Asia/Tehran');
        $date1 = null;
        $date2=null;
        if(isset($_GET['date1']))
        {
            if($_GET['date1'] != '')
            {
                $d1 = $params['date1'];
                $d2 = jalali_to_gregorian(explode('-',$d1)[0],explode('-',$d1)[1],explode('-',$d1)[2]);
                $date1 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d2[0].'-'.$d2[1].'-'.$d2['2'])).'0000000000000000');
            }
        }
        if(isset($_GET['date2']))
        {
            if($_GET['date2'] != '')
            {
                $d1 = $params['date2'];
                $d2 = jalali_to_gregorian(explode('-',$d1)[0],explode('-',$d1)[1],explode('-',$d1)[2]);
                $date2 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d2[0].'-'.$d2[1].'-'.$d2['2'])).'0000000000000000');
            }
        }
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        // grid filtering conditions

//        $query->andFilterWhere(['>=', '_id', $date1])
//            ->andFilterWhere(['<=', '_id', $date2])
//            ->andFilterWhere(['like', 'username', $this->username])
//            ->andFilterWhere(['like', 'maturities', $this->maturities])
//            ->andWhere(['status' => '2'])
//            ->orderBy(['_id'=>SORT_DESC]);
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $query->andFilterWhere(['>=', '_id', $date1])
                ->andFilterWhere(['<=', '_id', $date2])
                ->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere([
                    'like',
                    'shares.college',
                    $this->orders['college'] ?? null
                ])
                ->andFilterWhere([
                    'like',
                    'shares.broker',
                    $this->shares['broker'] ?? null
                ])
                ->andWhere(['status' => '1'])
                ->andWhere(['orders.type' => ['1','2']])
                ->orderBy(['_id'=>SORT_DESC]);

        else if(Yii::$app->user->identity->role == 'broker')
        {
            $broker = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            $query->andFilterWhere(['>=', '_id', $date1])
                ->andFilterWhere(['<=', '_id', $date2])
                ->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andWhere(['shares.broker' => (string) $broker->_id])
                ->andWhere(['status' => '1'])
                ->andWhere(['orders.type' => ['1','2']])
                ->orderBy(['_id'=>SORT_DESC]);
        }
        else
            $query->andFilterWhere(['>=', '_id', $date1])
                ->andFilterWhere(['<=', '_id', $date2])
                ->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andWhere(['shares.college' => Yii::$app->user->identity->college])
                ->andFilterWhere(['like', 'shares.broker', $this->shares['broker']])
                ->andWhere(['status' => '1'])
                ->andWhere(['orders.type' => ['1','2']])
                ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
