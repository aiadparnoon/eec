<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "installments".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class Installments extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'installments'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'username',
            'first_name',
            'last_name',
            'course_id',
            'college',
            'broker',
            'order_id',
            'status',
            'maturities',
            'is_cheque',
            'createdAt',
            'updatedAt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'username',
                'first_name',
                'last_name',
                'course_id',
                'college',
                'broker',
                'order_id',
                'status',
                'maturities',
                'is_cheque',
                'createdAt',
                'updatedAt',
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
