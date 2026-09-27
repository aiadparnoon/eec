<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Teachers".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $first_name
 * @property string last_name
 * @property string $gender
 * @property string $mobile
 * @property string $id
 * @property string $comment
 * @property string $profile_image
 * @property string $colleges
 * @property string $status
 * @property string $registrant
 */
class Teachers extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'teachers'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'first_name',
            'last_name',
            'gender',
            'mobile',
            'id',
            'comment',
            'profile_image',
            'colleges',
            'status',
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
                'first_name',
                'last_name',
                'gender',
                'mobile',
                'id',
                'comment',
                'profile_image',
                'colleges',
                'status',
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
