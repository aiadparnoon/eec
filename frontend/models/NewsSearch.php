<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\News;

/**
 * AdminSearch represents the model behind the search form of `app\models\News`.
 */
class NewsSearch extends News
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'id',
                'title',
                'content',
                'file',
                'registrant',
                'image',
                'type',
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
        $query = News::find();

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
                ->andFilterWhere(['like', 'title', $this->title])
                ->andFilterWhere(['like', 'type', $this->type])
                ->orderBy(['_id' => SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'title', $this->title])
                ->andFilterWhere(['like', 'type', $this->type])
                ->andWhere(['registrant' => $user->username])
                ->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }
}
