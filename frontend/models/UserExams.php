<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "UserExams".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $course_id
 * @property string $lesson_id
 * @property string $exam_id
 * @property string $user_id
 * @property string $questions
 * @property string $exam_time
 * @property string $status
 * @property string $assignments_id
 * @property string $final_score
 */
class UserExams extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'user_exams'];
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
            'questions',
            'exam_time',
            'status',
            'assignments_id',
            'final_score'
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
                'questions',
                'exam_time',
                'status',
                'assignments_id',
                'final_score'
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
