<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Teachers;

/**
 * AdminSearch represents the model behind the search form of `app\models\Teachers`.
 */
class TeachersSearch extends Teachers
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'first_name',
                'last_name',
                'gender',
                'mobile',
                'id',
                'comment',
                'profile_image',
                'colleges',
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
        $query = Teachers::find();

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
        // grid filtering conditions
        if($user->role == 'user')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'mobile', $this->mobile])
                ->andFilterWhere(['like', 'id', $this->id])
                ->andFilterWhere(['colleges' => $this->colleges])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'mobile', $this->mobile])
                ->andFilterWhere(['like', 'id', $this->id])
                ->andWhere(['colleges' => $user->college])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'broker')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'first_name', $this->first_name])
                ->andFilterWhere(['like', 'last_name', $this->last_name])
                ->andFilterWhere(['like', 'mobile', $this->mobile])
                ->andFilterWhere(['like', 'id', $this->id])
                ->andWhere([ 'registrant' => $user->username])
                ->andWhere(['colleges' => $user->college])
                ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
