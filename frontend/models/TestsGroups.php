<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "tests_groups".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $title
 * @property string $college
 * @property string $registrant
 */
class TestsGroups extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'tests_groups'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'college',
            'registrant',
            'xml'
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
                'college',
                'registrant',
                'xml'
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
