<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "CoursesFinancial".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class CoursesFinancial extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'courses_financial'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'registrant',
            'course_id',
            'file',
            'amount',
            'payment_info',
            'type',
            'college',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'registrant',
                'course_id',
                'file',
                'amount',
                'payment_info',
                'type',
                'college',
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
