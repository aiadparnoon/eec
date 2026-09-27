<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "tests".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $title
 * @property string $college
 * @property string $registrant
 */
class Tests extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'tests'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'college',
            'type', // 1 = Question Fixed, 2 = Questions Random
            'registrant',
            'question_count', // total = Total of Questions, easy, medium, hard
            'questions', // Array of _id in questions_bank Collection
            'questions_tmp', // Array of _id in questions_bank Collection
            'random_answers', // true = Random Answers
            'time',
            'group',
            'repeat', // Number of Repeat Test. null = Unlimited,
            'score_type', // 1 = Average, 2 = Maximum
            'sale_status', // 1 = Sale in Site, 2 = not of Selling
            'price',
            'preview_image',
            'description',
            'status',
            'total_score', // Score Test
            'pass_score', // Pass Number For Test
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
                'college',
                'type',
                'registrant',
                'question_count', // total = Total of Questions, easy, medium, hard
                'questions', // Array of _id in questions_bank Collection
                'questions_tmp', // Array of _id in questions_bank Collection
                'random_answers', // true = Random Answers
                'time',
                'group',
                'repeat', // Number of Repeat Test. null = Unlimited,
                'score_type', // 1 = Average, 2 = Maximum
                'sale_status', // 1 = Sale in Site, 2 = not of Selling
                'price',
                'preview_image',
                'description',
                'status',
                'total_score', // Score Test
                'pass_score', // Pass Number For Test
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
