<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\OfflineExams;

/**
 * AdminSearch represents the model behind the search form of `app\models\OfflineExams`.
 */
class OfflineExamsSearch extends OfflineExams
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'description',
                'price',
                'max_score',
                'image',
                'start_date',
                'applicant_info',
                'status',
                'applicant_info',
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
        $query = OfflineExams::find();

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
                ->andFilterWhere(['like', 'title.fa', ($this->title['fa'] ?? null)])
                ->andFilterWhere(['like', 'start_date', $this->start_date])
                ->andFilterWhere(['like', 'applicant_info.gender', $this->applicant_info['gender']])
                ->andFilterWhere(['like', 'applicant_info.ut_student', $this->applicant_info['ut_student']])
                ->orderBy(['_id' => SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'title.fa', ($this->title['fa'] ?? null)])
                ->andFilterWhere(['like', 'start_date', $this->start_date])
                ->andFilterWhere(['like', 'applicant_info.gender', $this->applicant_info['gender']])
                ->andWhere(['registrant' => Yii::$app->user->identity->username])
                ->orderBy(['_id' => SORT_DESC]);
        return $dataProvider;
    }
}
