<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Principals".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class Principals extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'principals'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'username',
            'first_name',
            'last_name',
            'password',
            'principal_id',
            'type'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'first_name',
                'last_name',
                'password',
                'principal_id',
                'type'
            ], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
        ];
    }
}
