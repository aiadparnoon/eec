<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "UploadedExcels".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $file
 * @property string $course_id
 * @property string $registrant
 */
class UploadedExcels extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'uploaded_excels'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'file',
            'course_id',
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
                'file',
                'course_id',
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
