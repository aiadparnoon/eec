<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Exams".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $questions
 * @property string $registrant
 * @property string $status
 */
class Exams extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'exams'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'questions', // question_text, first_option, second_option, third_option, fourth_option
            'questions_tmp', // question_text, first_option, second_option, third_option, fourth_option
            'registrant',
            'status',
            'management',
            'contact',
            'course_id',
            'type', // 1 = For Exam, 2 = For Survey
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
                'questions',
                'questions_tmp',
                'registrant',
                'status',
                'management',
                'contact',
                'course_id',
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
