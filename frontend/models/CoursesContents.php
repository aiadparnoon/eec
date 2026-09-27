<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $course_id
 * @property string $lesson_id
 * @property string $content
 * @property string $registrant
 */
class CoursesContents extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'courses_contents'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'course_id',
            'lesson_id',
            'content',
            'registrant',
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
                'course_id',
                'lesson_id',
                'content',
                'registrant',
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
