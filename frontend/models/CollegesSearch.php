<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Colleges;

/**
 * AdminSearch represents the model behind the search form of `app\models\Authors`.
 */
class CollegesSearch extends Colleges
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // فقط رشته؛ آرایه (مثل title[$ne]=) رد می‌شود
            [['title'], 'string', 'max' => 100],
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
        $query = Colleges::find();

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
        $query->andFilterWhere(['like', 'title', is_string($this->title) ? trim($this->title) : null])
            ->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }
}
