<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "UserAssignments".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $course_id
 * @property string $lesson_id
 * @property string $exam_id
 * @property string $user_id
 * @property string $assignment_id
 * @property string $content
 */
class UserAssignments extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'user_assignments'];
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
            'exam_id',
            'user_id',
            'content',
            'assignment_id',
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
                'exam_id',
                'user_id',
                'content',
                'assignment_id',
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
