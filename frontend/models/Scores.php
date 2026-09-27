<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Scores".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $course_id
 * @property string $user_id
 * @property string $scores
 */
class Scores extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'scores'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'course_id',
            'user_id',
            'scores',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'course_id',
                'user_id',
                'scores',
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
