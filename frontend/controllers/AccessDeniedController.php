<?php
namespace frontend\controllers;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class AccessDeniedController extends \common\component\Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),

                'rules' => [
                    [
                        'allow' => false,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if(!Yii::$app->user->isGuest)
                                return true;
                            else
                                return false;
                        }
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->getResponse()->redirect(['login']);
                }
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index',[

        ]);
    }


}