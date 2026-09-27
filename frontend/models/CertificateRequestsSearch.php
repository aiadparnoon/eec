<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CertificateRequests;

/**
 * CertificateRequestsSearch represents the model behind the search form of `app\models\CertificateRequests`.
 */
class CertificateRequestsSearch extends CertificateRequests
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'course_id',
                'college',
                'broker',
                'college_verifier',
                'final_verifier',
                'status',
                'serial_number',
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
    public function search($params, $status)
    {
        $query = CertificateRequests::find();

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
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'course_id', $this->course_id])
            ->andFilterWhere(['like', 'college', $this->college])
            ->andFilterWhere([
                'like',
                'broker._id',
                $this->broker['_id'] ?? null
            ])
            ->andWhere(['in','status',$status])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
