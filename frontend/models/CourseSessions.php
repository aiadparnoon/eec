<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "CourseSessions".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $course_id
 * @property string $lesson_id
 * @property string $sessions
 * @property string $createdAt
 * @property string $updateAt
 */
class CourseSessions extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'course_sessions'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'course_id',
            'lesson_id',
            'sessions',
            'archives',
            'createAt',
            'updateAt'
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
                'lesson_id',
                'sessions',
                'archives',
                'createAt',
                'updateAt'
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
