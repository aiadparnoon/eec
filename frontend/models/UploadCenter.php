<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "upload_center".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $file
 * @property string $registrant
 */
class UploadCenter extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'upload_center'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'file',
            'registrant',
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
                'file',
                'registrant',
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
