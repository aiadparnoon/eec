<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Cities".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class Cities extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'cities'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'id',
            'name',
            'province_id'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'id',
                'name',
                'province_id'
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
