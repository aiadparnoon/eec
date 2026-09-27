<?php

namespace frontend\controllers;

use Yii;
use app\models\Brokers;
use app\models\BrokersSearch;
use app\models\Colleges;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * ManageBrokersController implements the CRUD actions for Brokers model.
 */
class ManageBrokersController extends Controller
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
                        'actions' => ['index','report', 'create-legal-broker', 'create-natural-broker' ,'create', 'create_natural_2', 'edit','reset_password','change_status','change_access','edit-legal-broker','edit-natural-broker','new_contract','change_contract_status','edit_natural_broker','file'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['confirm_broker'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['confirm','un_confirm','back_broker','reject_broker'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
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
     * Lists all Brokers models.
     * @return mixed
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $searchModel = new BrokersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->andWhere(['_id' => Yii::$app->user->identity->college])->all();
        $dataProvider->pagination->pageSize = 20;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionFile($filename)
    {
        // امنیتی: نام فایل مستقیم از درخواست می‌آید. بدون این بررسی، ورودی
        // «../../config/main-local.php» کلید cookieValidationKey را برمی‌گرداند.
        $path = \app\components\SecureFile::resolve('broker_files', $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    public function actionCreateLegalBroker()
    {
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->andWhere(['_id' => Yii::$app->user->identity->college])->all();
        return $this->render('create-legal-broker', [
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new BrokersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                    if($model->type == '1')
                        return 'حقوقث';
                    else
                        return 'حقیقی';
                    },
                    'header' => ' نوع کارگزار'
                ],
                [
                    'attribute' => function($model){
                        return $model->connector_info['first_name'];
                    },
                    'header' => 'نام'
                ],
                [
                    'attribute' => function($model){
                        return $model->connector_info['last_name'];
                    },
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => function($model){
                        return $model->connector_info['mobile'];
                    },
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => function($model){
                        return ' '.(string) $model->connector_info['id'].' ';
                    },
                    'header' => 'کد ملی'
                ],
                [
                    'attribute' => function($model){
                        return $model->connector_info['birth_day'];
                    },
                    'header' => 'تاریخ تولد'
                ],
                [
                    'attribute' => function($model){
                        return ' '.(string) $model->financial_info['id'].' ';
                    },
                    'header' => 'شماره حساب کارگزار'
                ],
                [
                    'attribute' => function($model){
                        return ' '.(string) $model->financial_info['sub_service_id'].' ';
                    },
                    'header' => 'ساب سرویس آی دی'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['company_title'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'نام شرکت'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['establishment_date'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'تاریخ تاسیس'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['registration_number'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'شماره ثبت'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['id'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'شناسه ملی'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['start_contract_date'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'تاریخ شروع قرارداد'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['end_contract_date'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'تاریخ اتمام قرارداد'
                ],
                [
                    'attribute' => function($model){
                        if($model->type == 1)
                        {
                            return $model->company_info['address'];
                        }
                        else
                            return  '-';
                    },
                    'header' => 'آدرس شرکت'
                ],
                [
                    'attribute' => function($model){
                        $college = Colleges::findOne($model->college);
                        if($college != null)
                            return $college->title;
                        else
                            return 'خطا';
                    },
                    'header' => 'دانشکده'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'BrokersReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionCreateNaturalBroker()
    {
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->andWhere(['_id' => Yii::$app->user->identity->college])->all();
        return $this->render('create-natural-broker', [
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionCreate()
    {
        if (Yii::$app->request->isPost)
        {
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
                $model->status = '2';
                $model->type = '1';
                $model->registrant = Yii::$app->user->identity->username;
                $model->link = $this->generateRandomCode();
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
                    $access->first_name = $model->connector_info['first_name'];
                    $access->last_name = $model->connector_info['last_name'];
                    $access->setPassword($model->connector_info['id']);
                    $access->username = $model->connector_info['mobile'];
                    $access->auth_key = Yii::$app->security->generateRandomString();
                    $access->verification_token = Yii::$app->security->generateRandomString();
                    $access->getAuthKey();
                    $access->role = 'broker';
                    $access->status = 9;
                    $access->college = $model->college;
                    $access->access = array("lessons","courses","packages","test-maker","upload-center","survey-maker","certificate-manage");
                    $access->save();
                    Yii::$app->session->setFlash('status', '1');
                }
                else
                {
                    echo '<pre>';
                    print_r($model->errors);
                    exit;
                    Yii::$app->session->setFlash('status', '2');
                }
            }
            else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionCreate_natural_2()
    {
        if (Yii::$app->request->isPost)
        {
            if ($_FILES['Brokers']['size']['id_file'] <= 40000000 && $_FILES['Brokers']['size']['contract_file'] <= 40000000)
            {
            $model = new Brokers();
            $find = Admin::find()->where(['username' => Yii::$app->request->post()['Brokers']['connector_info']['mobile']])->one();
            if ($find == null)
            {
                $model = new Brokers();
                $model->load(Yii::$app->request->post());
                $file = UploadedFile::getInstance($model, 'contract_file');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/broker_files/' . $file_name);
                if (UploadedFile::getInstance($model, 'contract_file') != null)
                    $model->contract_file = $file_name;

                $file = UploadedFile::getInstance($model, 'id_file');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/broker_files/' . $file_name);
                if (UploadedFile::getInstance($model, 'id_file') != null)
                    $model->id_file = $file_name;
                $model->status = '2';
                $model->type = '2';
                $model->registrant = Yii::$app->user->identity->username;
                $model->link = $this->generateRandomCode();
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
                    $access->first_name = $model->connector_info['first_name'];
                    $access->last_name = $model->connector_info['last_name'];
                    $access->setPassword($model->connector_info['id']);
                    $access->username = $model->connector_info['mobile'];
                    $access->auth_key = Yii::$app->security->generateRandomString();
                    $access->verification_token = Yii::$app->security->generateRandomString();
                    $access->getAuthKey();
                    $access->role = 'broker';
                    $access->status = 9;
                    $access->college = $model->college;
                    $access->access = array("lessons","courses","packages","test-maker","upload-center","survey-maker","certificate-manage");
                    $access->save();
                    Yii::$app->session->setFlash('status', '1');
                }
                else
                    Yii::$app->session->setFlash('status', '2');
                }
                else
                    Yii::$app->session->setFlash('status', '3');
            }
            else
                Yii::$app->session->setFlash('status', '10');
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionEditLegalBroker($_id)
    {
        if(isset($_GET['_id']))
        {
            $model = Brokers::findOne($_GET['_id']);
            if($model != null)
            {
                if($this->allow($model->college))
                {
                    $colleges = Colleges::find()->all();
                    return $this->render('edit-legal-broker', [
                        'model' => $model,
                        'colleges' => ArrayHelper::map($colleges, function ($model){
                            return (string) $model->_id;
                        },'title')
                    ]);
                }
            }
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionEditNaturalBroker($_id)
    {
        if(isset($_GET['_id']))
        {
            $model = Brokers::findOne($_GET['_id']);
            if($model != null)
            {
                if($this->allow($model->college))
                {
                    $colleges = Colleges::find()->all();
                    return $this->render('edit-natural-broker', [
                        'model' => $model,
                        'colleges' => ArrayHelper::map($colleges, function ($model){
                            return (string) $model->_id;
                        },'title')
                    ]);
                }
            }
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if ($find != null)
            {
                $contracts = $find->contracts;
                $preFile1 = $find->statute_file;
                $preFile2 = $find->newspaper_file;
                $preFile3 = $find->id_file;
                $find->load(Yii::$app->request->post());
                if(($find->status == '1') && Yii::$app->user->identity->role == 'emp')
                    $find->contracts = $contracts;
                if ($_FILES['Brokers']['name']['statute_file'] != '')
                {
                    if ($preFile1 != '' && $preFile1 != null)
                        if(file_exists('../../frontend/web/broker_files/' . $preFile1))
                            unlink('../../frontend/web/broker_files/' . $preFile1);
                    $file1 = UploadedFile::getInstance($find, 'statute_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/broker_files/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'statute_file') != null) {
                        $profile = $file1_name;
                        $find->statute_file = $profile;
                    }
                }
                else
                    $find->statute_file = $preFile1;

                if (isset($_FILES['Brokers']['name']['newspaper_file']) && $_FILES['Brokers']['name']['newspaper_file'] != '')
                {
                    if ($preFile2 != '' && $preFile2 != null)
                        if(file_exists('../../frontend/web/broker_files/' . $preFile2))
                            unlink('../../frontend/web/broker_files/' . $preFile2);
                    $file1 = UploadedFile::getInstance($find, 'newspaper_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/broker_files/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'newspaper_file') != null) {
                        $profile = $file1_name;
                        $find->newspaper_file = $profile;
                    }
                }
                else
                    $find->newspaper_file = $preFile2;

                if (isset($_FILES['Brokers']['name']['id_file']) && $_FILES['Brokers']['name']['id_file'] != '')
                {
                    if ($preFile3 != '' && $preFile3 != null)
                        if(file_exists('../../frontend/web/broker_files/' . $preFile3))
                            unlink('../../frontend/web/broker_files/' . $preFile3);
                    $file1 = UploadedFile::getInstance($find, 'id_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/broker_files/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'id_file') != null) {
                        $profile = $file1_name;
                        $find->id_file = $profile;
                    }
                }
                else
                    $find->id_file = $preFile3;

                if ($find->save())
                {
                    $admin = Admin::find()->where(['username' => $find->connector_info['mobile']])->one();
                    $admin->first_name = $find->connector_info['first_name'];
                    $admin->last_name = $find->connector_info['last_name'];
                    $admin->college = $find->college;
                    $admin->save();
                    Yii::$app->session->setFlash('status', '4');
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionEdit_natural_broker()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if ($find != null)
            {
                $contracts = $find->contracts;
                $preFile1 = $find->id_file;
                $preFile2 = $find->contract_file;
                $find->load(Yii::$app->request->post());
                if(($find->status == '1') && (Yii::$app->user->identity->role == 'emp'))
                    $find->contracts = $contracts;
                if (isset($_FILES['Brokers']['name']['id_file']) && $_FILES['Brokers']['name']['id_file'] != '')
                {
                    if ($preFile1 != '' && $preFile1 != null)
                        if(file_exists('../../frontend/web/broker_files/' . $preFile1))
                            unlink('../../frontend/web/broker_files/' . $preFile1);
                    $file1 = UploadedFile::getInstance($find, 'id_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/broker_files/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'id_file') != null) {
                        $profile = $file1_name;
                        $find->id_file = $profile;
                    }
                }
                else
                    $find->id_file = $preFile1;

                if (isset($_FILES['Brokers']['name']['contract_file']) &&
                    $_FILES['Brokers']['name']['contract_file'] != '')
                {
                    if ($preFile2 != '' && $preFile2 != null)
                        if(file_exists('../../frontend/web/broker_files/' . $preFile2))
                            unlink('../../frontend/web/broker_files/' . $preFile2);
                    $file1 = UploadedFile::getInstance($find, 'contract_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/broker_files/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'contract_file') != null) {
                        $profile = $file1_name;
                        $find->contract_file = $profile;
                    }
                }
                else
                    $find->contract_file = $preFile2;

                if ($find->save())
                {
                    $admin = Admin::find()->where(['username' => $find->connector_info['mobile']])->one();
                    $admin->first_name = $find->connector_info['first_name'];
                    $admin->last_name = $find->connector_info['last_name'];
                    $admin->college = $find->college;
                    $admin->save();
                    Yii::$app->session->setFlash('status', '4');
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(['../manage-brokers']);
    }
    public function actionReset_password()
    {
        if(Yii::$app->request->isPost)
        {
            $teacher = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($teacher != null)
            {
                $admin = Admin::find()->where(['username' => $teacher->connector_info['mobile']])->one();
                if($admin != null)
                {
                    $admin->setPassword($teacher->connector_info['id']);
                    if($admin->save())
                        Yii::$app->session->setFlash('status','5');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionConfirm()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                $broker->status = '1';
                if($broker->save())
                {
//                    $admin = Admin::find()->where(['username' => $broker->connector_info['mobile']])->one();
//                    $admin->status = 10;
//                    $admin->save();
                    Yii::$app->session->setFlash('status','6');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUn_confirm()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                $broker->status = '2';
                if($broker->save())
                {
//                    $admin = Admin::find()->where(['username' => $broker->connector_info['mobile']])->one();
//                    $admin->status = 9;
//                    $admin->save();
                    Yii::$app->session->setFlash('status','6');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                if((string) $broker->_id != '664c3d5ffaaf76ecff082a72___')
                {
                    if($broker->status == '1')
                        $broker->status = '0';
                    else
                        $broker->status = '1';
                    if($broker->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
                else
                    Yii::$app->session->setFlash('status','11');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_access()
    {
        if(Yii::$app->request->isPost && (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                $admin = Admin::find()->where(['username' => $broker->connector_info['mobile']])->one();
                if($admin != null)
                {
                    if($admin->status == 10)
                        $admin->status = 9;
                    else
                        $admin->status = 10;
                    if($admin->save())
                        Yii::$app->session->setFlash('status','9');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }


    public function admin($username)
    {
        return Admin::find()->where(['username' => $username])->one();
    }

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function actionNew_contract()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post('_id'));
            if($broker != null)
            {
                $preContract = $broker->contracts;
                $newContract = array(
                    'id' => uniqid(),
                    'title' => Yii::$app->request->post('contractTitle'),
                    'share' => Yii::$app->request->post('contractShare'),
                    'status' => '1'
                );
                array_push($preContract,$newContract);
                $broker->contracts = $preContract;
                if($broker->save())
                    Yii::$app->session->setFlash('status','8');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_contract_status()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post('_id'));
            if($broker != null)
            {
                $status = $broker->contracts[Yii::$app->request->post('row')]['status'];
                if($status == '1')
                    $newStatus = '0';
                else
                    $newStatus = '1';
                $contracts = $broker->contracts;
                $contracts[Yii::$app->request->post('row')]['status'] = $newStatus;
                $broker->contracts = $contracts;
                if($broker->save())
                    Yii::$app->session->setFlash('status','7');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionConfirm_broker()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                if($broker->financial_info != null)
                {
                    if(array_key_exists('id',$broker->financial_info) && array_key_exists('sub_service_id',$broker->financial_info))
                    {
                        if($broker->financial_info['id'] != '' && $broker->financial_info['sub_service_id'] != '')
                        {
                            if($broker->status != '1')
                            {
                                $broker->status = '1';
                                if($broker->save())
                                    Yii::$app->session->setFlash('status','6');
                                else
                                    Yii::$app->session->setFlash('status','2');
                            }
                        }
                        else
                        {
                            Yii::$app->session->setFlash('status','1');
                            return  $this->redirect(Yii::$app->request->referrer);
                        }
                    }
                    else
                    {
                        Yii::$app->session->setFlash('status','1');
                        return  $this->redirect(Yii::$app->request->referrer);
                    }
                }
                {
                    Yii::$app->session->setFlash('status','1');
                    return  $this->redirect(Yii::$app->request->referrer);
                }
            }
        }
        return $this->redirect('../manage-brokers');
    }

    public function actionBack_broker()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                if($broker->status != '4')
                {
                    $broker->load(Yii::$app->request->post());
                    $broker->status = '4';
                    if($broker->save())
                    {
                        $access = Admin::find()->where(['username' => $broker->connector_info['mobile']])->one();
                        if($access != null)
                        {
                            $access->status = 9;
                            $access->save();
                        }
                        Yii::$app->session->setFlash('status','6');
                    }
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function actionReject_broker()
    {
        if(Yii::$app->request->isPost)
        {
            $broker = Brokers::findOne(Yii::$app->request->post()['Brokers']['_id']);
            if($broker != null)
            {
                if($broker->status != '5')
                {
                    $broker->load(Yii::$app->request->post());
                    $broker->status = '5';
                    if($broker->save())
                    {
                        $access = Admin::find()->where(['username' => $broker->connector_info['mobile']])->one();
                        if($access != null)
                        {
                            $access->status = 9;
                            $access->save();
                        }
                        Yii::$app->session->setFlash('status','6');
                    }
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../manage-brokers']);
    }

    public function allow($college)
    {
        $allow = false;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $allow = true;
        else
        {
            if(is_array(Yii::$app->user->identity->college))
            {
                if(array_search($college, Yii::$app->user->identity->college) !== false)
                    $allow = true;
            }
            else if($college == Yii::$app->user->identity->college)
                $allow = true;
        }
        return $allow;
    }

    function generateRandomCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 6; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

}
