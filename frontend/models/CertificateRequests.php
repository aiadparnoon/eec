<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "CertificateRequests".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $username
 * @property string $course_id
 * @property string $college
 * @property string $college_verifier
 * @property string $final_verifier
 * @property string $status
 * @property string $serial_number
 */
class CertificateRequests extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'certificate_requests'];
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
            'college',
            'college_verifier',
            'final_verifier',
            'status',
            'broker',
            'serial_number',
            'pre_serial_number',
            'request'
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
                'college',
                'college_verifier',
                'final_verifier',
                'status',
                'broker',
                'serial_number',
                'pre_serial_number',
                'request'
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
