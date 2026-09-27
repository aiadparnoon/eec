<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "QuestionsBank".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class QuestionsBank extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'questions_bank'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'question_text',
            'options',
            'level',
            'type',
            'group',
            'college',
            'image',
            'from_xml',
            'registrant'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'question_text',
                'options',
                'level',
                'type',
                'group',
                'college',
                'image',
                'from_xml',
                'registrant'
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
