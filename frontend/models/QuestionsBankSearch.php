<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\QuestionsBank;
use app\models\TestsGroups;
use yii\helpers\ArrayHelper;

/**
 * QuestionsBankSearch represents the model behind the search form of `app\models\QuestionsBank`.
 */
class QuestionsBankSearch extends QuestionsBank
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'question_text',
                'options',
                'level',
                'type',
                'group',
                'image',
                'registrant'
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
        $query = QuestionsBank::find();

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
                ->andFilterWhere(['like', 'question_text', $this->question_text])
                ->andFilterWhere(['like', 'level', $this->level])
                ->andFilterWhere(['like', 'type', $this->type])
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'group', $this->group])
                ->orderBy(['_id' => SORT_DESC]);
        else if($user->role == 'emp')
        {
            $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
//            $groups = ArrayHelper::map($groups,function ($model){
//                return (string) $model->_id;
//            }, function ($model){
//                return (string) $model->_id;
//            });
            $grp = array();
            foreach ($groups as $item)
                array_push($grp, (string) $item->_id);
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'question_text', $this->question_text])
                ->andFilterWhere(['like', 'level', $this->level])
                ->andFilterWhere(['like', 'type', $this->type])
                ->andFilterWhere([ 'group' => $grp])
//                ->andWhere(['groups' , $groups])
                ->orderBy(['_id' => SORT_DESC]);
        }
        else
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere(['like', 'question_text', $this->question_text])
                ->andFilterWhere(['like', 'level', $this->level])
                ->andFilterWhere(['like', 'type', $this->type])
                ->andFilterWhere(['like', 'group', $this->group])
                ->andWhere(['registrant' => $user->username])
                ->orderBy(['_id' => SORT_DESC]);
        return $dataProvider;
    }
}
