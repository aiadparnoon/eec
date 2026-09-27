<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * AdminSearch represents the model behind the search form of `app\models\Users`.
 */
class UsersSearch extends Users
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name','last_name','username','mobile','registrant','courses','id','status'],'safe']
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
        $query = Users::find();

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

        // grid filtering conditions
        $user = Yii::$app->user->identity;
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'id', $this->id])
//                ->andFilterWhere(['like', 'status', (int) $this->status])
                ->orderBy(['_id'=>SORT_DESC]);
        else
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'username', $this->username])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'id', $this->id])
//                ->andFilterWhere([ 'status' => (int) $this->status])
                ->andWhere(['registrant' => $user->username])
                ->orderBy(['_id'=>SORT_DESC]);
        return $dataProvider;
    }
}
