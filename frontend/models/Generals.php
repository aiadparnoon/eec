<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "General".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $type
 * @property string $data
 */
class Generals extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'generals'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'type',
            'data',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'type',
                'data',
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
