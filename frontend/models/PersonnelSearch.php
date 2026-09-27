<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Personnel;

/**
 * AdminSearch represents the model behind the search form of `app\models\Authors`.
 */
class PersonnelSearch extends Personnel
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'firstName',
                'lastName',
                'sex',
                'degree',
                'mobile',
                'id',
                'profile',
                'category',
                'cv',
                'subDomain',
                'subDomainStatus',
                'about',
                'textIntro'
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
        $query = Personnel::find();

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
        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'lastName', $this->lastName])
            ->andFilterWhere(['like', 'mobile', $this->mobile])
            ->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere(['like', 'category', $this->category])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
