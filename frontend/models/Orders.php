<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use MongoDB\BSON\UTCDateTime;
/**
 * This is the model class for collection "Cities".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $username
 * @property string $first_name
 * @property string $last_name
 * @property string $orders
 * @property string $amount
 * @property string $payment_info
 * @property string $shares
 * @property string $status
 */
class Orders extends \yii\mongodb\ActiveRecord
{

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt',
                'value' => function() {
                    return new UTCDateTime(time() * 1000);
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'orders'];
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
            'orders',
            'amount',
            'payment_info',
            'shares',
            'status',
            'applicant_info',
            'payments',
            'settlement_payment',
            'prepayment_settlement',
            'is_pos',
            'is_canceled',
            'financial_confirm',
            'is_canceling',
            'discount_code',
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
                'orders',
                'amount',
                'payment_info',
                'shares',
                'status',
                'applicant_info',
                'payments',
                'settlement_payment',
                'prepayment_settlement',
                'is_pos',
                'is_canceled',
                'is_canceling',
                'financial_confirm',
                'discount_code',
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
