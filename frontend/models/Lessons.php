<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $previewImage
 * @property string $comment
 * @property string $description
 * @property string $status
 */
class Lessons extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'lessons'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'en_title',
            'imagePreview',
            'comment',
            'description',
            'college',
            'status',
            'pre_id',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'en_title',
                'imagePreview',
                'comment',
                'description',
                'college',
                'status',
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
