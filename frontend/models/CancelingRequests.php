<?php

namespace app\models;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * This is the model class for collection "CancelingRequests".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class CancelingRequests extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'canceling_requests'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'username',
            'course_id',
            'order_id',
            'college',
            'registrant',
            'registrant_role',
            'edited_by',
            'request_date',
            'status',
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
                'course_id',
                'order_id',
                'college',
                'registrant',
                'registrant_role',
                'edited_by',
                'request_date',
                'status',
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

    /**
     * تبدیل اعداد فارسی و عربی به انگلیسی
     */
    private function convertToEnglishNumbers($value)
    {
        if (is_string($value)) {
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $value = str_replace([',', '،', ' '], '', $value);
            $value = str_replace($persian, $english, $value);
            $value = str_replace($arabic, $english, $value);
        }

        return $value;
    }

    /**
     * پردازش financial_info قبل از ذخیره‌سازی
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->processFinancialInfo();
            return true;
        }
        return false;
    }

    /**
     * پردازش financial_info قبل از اعتبارسنجی
     */
    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->processFinancialInfo();
            return true;
        }
        return false;
    }

    /**
     * پردازش فیلد financial_info
     */
    private function processFinancialInfo()
    {
        if (!empty($this->financial_info) && is_array($this->financial_info)) {
            $financialInfo = $this->financial_info;

            if (isset($financialInfo['sub_service_id'])) {
                $financialInfo['sub_service_id'] = $this->convertToEnglishNumbers(
                    $financialInfo['sub_service_id']
                );
            }

            if (isset($financialInfo['id'])) {
                $financialInfo['id'] = $this->convertToEnglishNumbers(
                    $financialInfo['id']
                );
            }

            $this->financial_info = $financialInfo;
        }
    }
}