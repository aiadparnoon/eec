<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\UserExams;
use app\models\Teachers;

/**
 * UserExamsSearch represents the model behind the search form of `app\models\UserExams`.
 */
class UserExamsSearch extends UserExams
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
                'exam_id',
                'user_id',
                'questions',
                'exam_time',
                'status',
                'assignments_id',
                'final_score'
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
    public function search($params, $assignments_id)
    {
        $query = UserExams::find();

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
            ->andFilterWhere(['like', 'user_id', $this->user_id])
            ->andFilterWhere(['like', 'course_id', $this->course_id])
            ->andFilterWhere(['like', 'lesson_id', $this->lesson_id])
            ->andWhere(['assignments_id' => $assignments_id])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
