<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "OrganizationPayments".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $type
 * @property string $data
 */
class OrganizationPayments extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'organization_payments'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'product_id',
            'amount',
            'order_id',
            'date',
            'clean_date',
            'callback',
            'reference_id',
            'status',
            'createdAt',
            'updatedAt',
            'tref',
            'number',
            'gateway_token',
            'registrant',
            'deadline',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'product_id',
                'amount',
                'order_id',
                'date',
                'clean_date',
                'callback',
                'reference_id',
                'status',
                'createdAt',
                'updatedAt',
                'tref',
                'number',
                'gateway_token',
                'registrant',
                'deadline',
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
