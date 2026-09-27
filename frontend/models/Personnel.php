<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $firstNameFa
 * @property string $firstNameEn
 * @property string $lastNameFa
 * @property string $lastNameEn
 * @property string $sex
 * @property string $degree
 * @property string $mobile
 * @property string $id
 * @property string $profile
 * @property string $category
 * @property string $cv
 * @property string $subDomain
 * @property string $subDomainStatus
 * @property string $textIntro
 * @property string $about
 * @property string $access
 * @property string $departments
 * @property string $personalWebsite
 * @property string $introImage
 * @property string $role
 */
class Personnel extends \yii\mongodb\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['moeid', 'personnel'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'firstName',
            'lastName',
            'sex',
            'degree',
            'mobile',
            'id',
            'profile',
            'category',
            'cv',
            'subDomain',
            'subDomainStatus',
            'about',
            'textIntro',
            'access',
            'departments',
            'personalWebsite',
            'introImage',
            'role'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'firstName',
                'lastName',
                'sex',
                'degree',
                'mobile',
                'id',
                'profile',
                'category',
                'cv',
                'subDomain',
                'subDomainStatus',
                'about',
                'textIntro',
                'access',
                'departments',
                'personalWebsite',
                'introImage',
                'role'
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
