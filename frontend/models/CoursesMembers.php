<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * AdminSearch represents the model behind the search form of `app\models\Users`.
 */
class CoursesMembers extends Users
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name','last_name','username','mobile','registrant','courses','id','role'],'safe']
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
        $query->andFilterWhere(['like', '_id', $this->_id])
            ->andFilterWhere(['like', 'last_name', $this->last_name])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'first_name', $this->first_name])
            ->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere([
                'like',
                'courses.role',
                $this->courses['role'] ?? null
            ])
            ->andFilterWhere([
                'like',
                'courses.status',
                $this->courses['status'] ?? null
            ])
            ->andWhere(['courses._id' => $courseId])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
