<?php

namespace frontend\controllers;

use Yii;
use app\models\Brokers;
use app\models\Colleges;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * BuyersController implements the CRUD actions for Brokers model.
 */
class CreateBrokerController1111 extends Controller
{
    /**
     * {@inheritdoc}
     */
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
                        'actions' => ['index', 'create'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (DashboardController::access('academy', 'manageColleges'))
                                return true;
                            else
                                return false;
                        }
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->getResponse()->redirect(['access-denied']);
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

    /**
     * Lists all Buyers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $colleges = Colleges::find()->all();
        return $this->render('index', [
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionCreate()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new Brokers();
            $find = Admin::find()->where(['username' => Yii::$app->request->post()['Brokers']['connector_info']['mobile']])->one();
            if ($find == null)
            {
                $model = new Brokers();
                $model->load(Yii::$app->request->post());

                $file = UploadedFile::getInstance($model, 'statute_file');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/broker_files/' . $file_name);
                if (UploadedFile::getInstance($model, 'statute_file') != null)
                    $model->statute_file = $file_name;

                $file = UploadedFile::getInstance($model, 'newspaper_file');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/broker_files/' . $file_name);
                if (UploadedFile::getInstance($model, 'newspaper_file') != null)
                    $model->newspaper_file = $file_name;

                $file = UploadedFile::getInstance($model, 'id_file');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/broker_files/' . $file_name);
                if (UploadedFile::getInstance($model, 'id_file') != null)
                    $model->id_file = $file_name;

                $model->status = '1';
                $model->registrant = Yii::$app->user->identity->username;
                $contract = array(
                    'id' => uniqid(),
                    'title' => $_POST['Brokers']['contracts']['title'],
                    'share' => $_POST['Brokers']['contracts']['share'],
                    'status' => '1'
                );
                $co = array(
                    '0' => $contract
                );
                $model->contracts = $co;
                if ($model->save())
                {
                    $access = new Admin();
                    $access->first_name = $model->first_name;
                    $access->last_name = $model->last_name;
                    $access->setPassword($model->connector_info['id']);
                    $access->username = $model->connector_info['mobile'];
                    $access->auth_key = Yii::$app->security->generateRandomString();
                    $access->verification_token = Yii::$app->security->generateRandomString();
                    $access->getAuthKey();
                    $access->role = 'broker';
                    $access->status = 10;
                    $access->college = $model->college;
                    $access->save();
                    Yii::$app->session->setFlash('status', '1');
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $find = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if ($find != null) {
                $preImage = $find->profile_image;
                $find->load(Yii::$app->request->post());
                if ($_FILES['Brokers']['name']['profile_image'] != '')
                {
                    if ($find->profile_image != '' && $find->profile_image != null)
                        unlink('../../frontend/web/teacher_profiles/' . $preImage);
                    $file1 = UploadedFile::getInstance($find, 'profile_image');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/teacher_profiles/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'profile_image') != null) {
                        $profile = $file1_name;
                        $find->profile_image = $profile;
                    }
                }
                else
                    $find->profile_image = $preImage;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }
}
