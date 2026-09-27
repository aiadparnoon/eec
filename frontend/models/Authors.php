<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $firstName
 * @property string $lastName
 * @property string $sex
 * @property string $degree
 * @property string $mobile
 * @property string $id
 * @property string $profile
 */
class Authors extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['moeid', 'authors'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'firstName',
            'lastName',
            'sex',
            'degree',
            'mobile',
            'id',
            'profile'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'firstName',
                'lastName',
                'sex',
                'degree',
                'mobile',
                'id'
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
