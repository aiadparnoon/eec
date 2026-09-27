<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "news".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class News extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'news'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'id',
            'title',
            'sub_title',
            'content',
            'file',
            'registrant',
            'image',
            'type',
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
                'title',
                'sub_title',
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
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
        ];
    }
}
