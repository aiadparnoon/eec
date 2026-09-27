<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "news_files".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $file
 * @property string $registrant
 */
class NewsFiles extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'news_files'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
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
