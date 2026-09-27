<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Admin;

/**
 * AdminSearch represents the model behind the search form of `common\models\Admin`.
 */
class AdminsSearch extends Admin
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'password_hash',
                'password_reset_token',
                'email',
                'auth_key',
                'role',
                'status',
                'created_at',
                'updated_at',
                'verification_token',
                'first_name',
                'last_name',
                'national_code',
                'college',
                'registrant',
                'access',
                'gender',
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
        $query = Admin::find();

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
            ->andFilterWhere(['like', 'college', $this->college])
            ->andWhere(['in', 'role' , ['emp','cnt']])
            ->orderBy(['_id'=>SORT_DESC]);

        return $dataProvider;
    }
}
