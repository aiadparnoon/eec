<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Courses;
use app\models\Teachers;

/**
 * CertificateManage represents the model behind the search form of `app\models\Courses`.
 */
class CertificateManage extends Courses
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'price',
                'discount_price',
                'broker',
                'college',
                'license_code',
                'student_capacity',
                'type',
                'content_type',
                'status',
                'rejection_reason',
                'preview_image',
                'date',
                'registration_deadline',
                'description',
                'teachers',
                'license_code',
                'mentors'
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
        $query = Courses::find();

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
        if($user->role == 'user' || $user->role == 'cnt')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    $this->title['main_fa'] ?? null
                ])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere(
                    ['or',
                    ['status' => '6'],
                    ['content_type' => '3']]
                )
                ->andFilterWhere(['like', 'college', $this->college])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'emp')
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    $this->title['main_fa'] ?? null
                ])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere(
                    ['or',
                        ['status' => '6'],
                        ['content_type' => '3']]
                )
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->andWhere(['college' => $user->college])
                ->orderBy(['_id'=>SORT_DESC]);
        else if($user->role == 'broker')
        {
            $brokerDetail = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            $query->andFilterWhere(['like', '_id', $this->_id])
                ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    $this->title['main_fa'] ?? null
                ])
                ->andFilterWhere(['like', 'license_code', $this->license_code])
                ->andFilterWhere(['like', 'content_type', $this->content_type])
                ->andFilterWhere([
                    'like',
                    'broker._id',
                    $this->broker['_id'] ?? null
                ])
                ->andFilterWhere(['like', 'status', $this->status])
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                ->andFilterWhere(['like', '_id', $this->_id])
                ->andWhere(['status' => '6'])
                ->andWhere(['<>','from_pec', true])
//                ->andWhere(['registrant' => $user->username])
                ->andWhere(['broker._id' => (string) $brokerDetail->_id])
                ->orderBy(['_id'=>SORT_DESC]);
        }
        else if($user->role == 'teacher')
        {
            if($user->mentor == 'mentor')
            {
                $query->andFilterWhere(['like', '_id', $this->_id])
                    ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    $this->title['main_fa'] ?? null
                ])
                    ->andFilterWhere(['like', 'license_code', $this->license_code])
                    ->andFilterWhere(['like', 'content_type', $this->content_type])
                    ->andFilterWhere(['status' => '6'])
                    ->andFilterWhere(['like', '_id', $this->_id])
                    ->andWhere(['mentors' => $user->username])
                    ->orderBy(['_id'=>SORT_DESC]);
            }
            else
            {
                $teacher = Teachers::find()->where(['mobile' => $user->username])->one();
                $query->andFilterWhere(['like', '_id', $this->_id])
                    ->andFilterWhere([
                    'like',
                    'title.main_fa',
                    $this->title['main_fa'] ?? null
                ])
                    ->andFilterWhere(['like', 'license_code', $this->license_code])
                    ->andFilterWhere(['like', 'content_type', $this->content_type])
                    ->andFilterWhere(['status' => '6'])
//            ->andFilterWhere(['like', 'teachers', $this->teachers])
                    ->andFilterWhere(['like', '_id', $this->_id])
//                    ->andWhere(['lessons.teachers' => (string) $teacher->_id])
                    ->andWhere(['or',
                        ['lessons.teachers' => (string) $teacher->_id],
                        ['other_teachers' => (string) $teacher->_id]
                    ])
                    ->orderBy(['_id'=>SORT_DESC]);
            }
        }
        return $dataProvider;
    }
}
