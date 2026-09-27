<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Tests;

/**
 * AdminSearch represents the model behind the search form of `app\models\Tests`.
 */
class TestsSearch extends Tests
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'college',
                'registrant',
                'question_count', // total = Total of Questions, easy, medium, hard
                'questions', // Array of _id in questions_bank Collection
                'random_answers', // true = Random Answers
                'time',
                'group',
                'repeat', // Number of Repeat Test. null = Unlimited,
                'score_type', // 1 = Average, 2 = Maximum
                'sale_status', // 1 = Sale in Site, 2 = not of Selling
                'price',
                'preview_image',
                'description',
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
        $query = Tests::find();

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
        if($user->role == 'user')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'title', $this->title])
                ->orderBy(['_id' => SORT_DESC]);
        else 
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'title', $this->title])
                ->andWhere(['college' => $user->college])
                ->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }
}
