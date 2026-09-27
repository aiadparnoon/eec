<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $logo
 * @property string $first_line_signature_fa
 * @property string $first_line_signature_en
 * @property string $second_line_signature_fa
 * @property string $second_line_signature_en
 * @property string $title_en
 * @property string $name
 * @property string $last_name
 * @property string $financial_info
 * @property string $prefix
 * @property string $signature_file
 * @property string $status
 */
class Colleges extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'colleges'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'logo',
            'first_line_signature_fa',
            'second_line_signature_fa',
            'first_line_signature_en',
            'second_line_signature_en',
            'title_en',
            'name',
            'last_name',
            'financial_info',
            'prefix',
            'signature_file',
            'status',
            'pre_id',
            'phone',
            'allow_free_add_user',
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
                'logo',
                'first_line_signature_fa',
                'second_line_signature_fa',
                'first_line_signature_en',
                'second_line_signature_en',
                'title_en',
                'name',
                'last_name',
                'financial_info',
                'prefix',
                'signature_file',
                'status',
                'phone',
                'allow_free_add_user',
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
