<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CoursesContents;
use app\models\Teachers;

/**
 * AdminSearch represents the model behind the search form of `app\models\Courses`.
 */
class CoursesContentsSearch extends CoursesContents
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'course_id',
                'content',
                'registrant',
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
    public function search($params, $courseId)
    {
        $query = CoursesContents::find();

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
            ->andFilterWhere(['like', 'title', $this->title])
            ->andWhere(['course_id' => $courseId])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
