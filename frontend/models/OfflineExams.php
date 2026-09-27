<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "OfflineExams".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class OfflineExams extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'offline_exams'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'description',
            'price',
            'max_score',
            'preview_image',
            'start_date',
            'status',
            'tips',
            'registrant',
            'college',
            'deadline',
            'applicant_info'
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
                'description',
                'price',
                'max_score',
                'preview_image',
                'status',
                'college',
                'registrant',
                'tips',
                'start_date',
                'deadline',
                'applicant_info'
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
