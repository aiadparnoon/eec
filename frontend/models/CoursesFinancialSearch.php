<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CoursesFinancial;

/**
 * AdminSearch represents the model behind the search form of `app\models\CoursesFinancial`.
 */
class CoursesFinancialSearch extends CoursesFinancial
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'registrant',
                'course_id',
                'file',
                'amount',
                'payment_info',
                'type',
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
        $query = CoursesFinancial::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        require_once(Yii::$app->basePath . '/web/jdf.php');
        date_default_timezone_set('Asia/Tehran');
        $date1 = null;
        $date2 = null;
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
        $user = Yii::$app->user->identity;
        $query->andFilterWhere(['>=', '_id', $date1])
            ->andFilterWhere(['<=', '_id', $date2])
            ->andFilterWhere(['like', '_id', $this->_id])
            ->andWhere(['payment_info.status' => '2']);
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'registrant', $this->registrant]);
        else if($user->role == 'emp')
            $query->andWhere(['college' => $user->college])
                ->andFilterWhere(['like', 'registrant', $this->registrant]);
        else if($user->role == 'broker')
        {
            $query->andWhere(['registrant' => $user->username]);
        }
        $query->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }
}
