<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CourseSessions;
use app\models\Teachers;

/**
 * CourseSessionsSearch represents the model behind the search form of `app\models\CourseSessions`.
 */
class CourseSessionsSearch extends CourseSessions
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'course_id',
                'lesson_id',
                'sessions',
                'archives',
                'createAt',
                'updateAt'
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
        $query = CourseSessions::find();

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
        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'course_id', $this->course_id])
            ->andFilterWhere(['like', 'lesson_id', $this->lesson_id])
            ->andFilterWhere(['like', 'archives', $this->archives])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
