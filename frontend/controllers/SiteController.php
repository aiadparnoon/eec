<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\Html;
use yii\web\BadRequestHttpException;
use common\component\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\AdminLoginForm;
use app\middlewares\InputSanitizerMiddleware;

class SiteController extends Controller
{
//    public function behaviors()
//    {
//        return [
//            'inputSanitizer' => [
//                'class' => InputSanitizerMiddleware::class,
//            ],
//        ];
//    }

    public function actionIndex()
    {
        $this->layout = 'login';
        $model = new AdminLoginForm();
        if(isset($_POST['AdminLoginForm']))
        {
            $data = Yii::$app->request->post()['AdminLoginForm'];
            $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
            $password = filter_var($data['password'], FILTER_SANITIZE_STRING);
            $username = Html::encode($username);
            $password = Html::encode($password);
            $model = new AdminLoginForm();
            $model->username = $username;
            $model->password = $password;
        }
        if ($model->login())
        {
            return $this->redirect(['../dashboard']);
        }
        else
        {
//            echo '<pre>';
//            print_r($model->errors);
//            exit;
            $model->password = '';
            if($model->errors != null)
            {
                $error = $model->errors;
                if(array_key_exists('password', $error))
                {
                    if($error['password'][0] == 'Incorrect username or password.')
                        Yii::$app->session->setFlash('status','1');
                }
            }
            return $this->render('index', [
                'model' => $model,
            ]);
        }
    }

    public function actionError()
    {
//        echo Yii::$app->security->generatePasswordHash('newadmin@430@');
    }



    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->redirect(['/']);
    }

}