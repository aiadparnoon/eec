<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use common\component\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\AdminLoginForm;

class LoginController extends Controller
{
    public function actionIndex()
    {
        $this->layout='login';
        $model = new AdminLoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login())
        {
            return $this->redirect(['../dashboard']);
        }
        else
            {
                $model->password = '';
                return $this->render('index', [
                    'model' => $model,
                ]);
             }
    }



    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->redirect(['./dashboard']);
    }

}