<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "WalletTransactions".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class WalletTransactions extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'wallet_transactions'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'broker_id',
            'amount',
            'payment_info',
            'type', // 1=Increase With Link, 2=Increase With WebService, 3=decrease After Add User, 4=Increase After Confirm Financial Statements, 5=Direct Increase in EEC1 Panel
            'status',
            'college',
            'date',
            'payer',
            'description',
            'reference_id'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'broker_id',
                'amount',
                'payment_info',
                'type',
                'status',
                'college',
                'date',
                'payer',
                'description',
                'reference_id'
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
