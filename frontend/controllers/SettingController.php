<?php

namespace frontend\controllers;

use common\models\Admin;
use Yii;
use app\models\Articles;
use app\models\ArticlesSearch;
use app\models\CompletionArticle;
use app\models\CompletionSearch;
use app\models\Departments;
use app\models\UploadCenter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * SettingController implements the CRUD actions for Posts model.
 */
class SettingController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index'],
                'rules' => [

                    [
                        'actions' => ['index','change_password'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Posts models.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionChange_password()
    {
       if(Yii::$app->request->isPost)
       {
           if(Yii::$app->request->post('newPassword') == Yii::$app->request->post('confirmPassword'))
           {
               $user = Admin::find()->where(['username' => Yii::$app->user->identity->username])->one();
               if($user != null)
               {
                  if(Yii::$app->security->validatePassword(Yii::$app->request->post('currentPassword'),$user->password_hash))
                      {
                          $user->password_hash = Yii::$app->security->generatePasswordHash(Yii::$app->request->post('newPassword'));
                          if($user->save(false))
                              Yii::$app->session->setFlash('status','1');
                          else
                              Yii::$app->session->setFlash('status','2');
                      }
                  else
                      Yii::$app->session->setFlash('status','3');
               }
           }
           else
               Yii::$app->session->setFlash('status','4');
       }
       return $this->redirect(Yii::$app->request->referrer);
    }
}
