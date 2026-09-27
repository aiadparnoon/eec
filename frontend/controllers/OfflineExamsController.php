<?php

namespace frontend\controllers;

use app\models\Members;
use Yii;
use app\models\OfflineExams;
use app\models\OfflineExamsSearch;
use app\models\ExamParticipants;
use app\models\Colleges;
use app\models\Users;
use app\models\Orders;
use app\models\Generals;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;
ini_set('max_execution_time', 600); //300 seconds = 5 minutes
/**
 * OfflineExamsController implements the CRUD actions for OfflineExams model.
 */
class OfflineExamsController extends Controller
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
                        'actions' => ['index', 'report', 'new', 'edit','participants','change_status','edit_user','edit_score','edit_card_tips','delete_user_from_exam','archive_profiles','financial_report','print','financial-print','terminate_exam'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
//                            if (in_array(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                            if (Yii::$app->user->identity->username == '09351306525' || Yii::$app->user->identity->username == '09122881335' || Yii::$app->user->identity->username == '09121539367')
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
        $searchModel = new OfflineExamsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        $access = Generals::find()->where(['type' => 'access'])->one();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'access' => ArrayHelper::map($access->data,function ($model){
                return $model['controller'];
            },function($model){
                return $model['title'];
            })
        ]);
    }
    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            if ($_FILES['OfflineExams']['size']['preview_image'] <= Yii::getAlias('@uploadSize'))
            {
                $model = new OfflineExams();
                $model->load(Yii::$app->request->post());
                if ($_FILES['OfflineExams']['name']['preview_image'] != '')
                {
                    $file = UploadedFile::getInstance($model, 'preview_image');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/offline_exams_images/' . $file_name);
                    if (UploadedFile::getInstance($model, 'preview_image') != null)
                        $model->preview_image = $file_name;
                    $model->status = '1';
                    $model->registrant = Yii::$app->user->identity->username;
                    $model->college = Yii::$app->user->identity->college;
                    if ($model->save())
                        Yii::$app->session->setFlash('status', '1');
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
            }
            else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $find = OfflineExams::findOne(Yii::$app->request->post()['OfflineExams']['_id']);
            if ($find != null)
            {
                if ($_FILES['OfflineExams']['size']['preview_image'] <= Yii::getAlias('@uploadSize'))
                {
                    $preImage = $find->preview_image;
                    $find->load(Yii::$app->request->post());
                    if ($_FILES['OfflineExams']['name']['preview_image'] != '')
                    {
                        if ($preImage != '' && $preImage != null)
                            if(file_exists('../../frontend/web/offline_exams_images/' . $preImage))
                                unlink('../../frontend/web/offline_exams_images/' . $preImage);
                        $file1 = UploadedFile::getInstance($find, 'preview_image');
                        $file1_ext = $file1->extension;
                        $file1_name = uniqid() . '.' . $file1_ext;
                        $file1->saveAs('../../frontend/web/offline_exams_images/' . $file1_name);
                        if (UploadedFile::getInstance($find, 'preview_image') != null) {
                            $profile = $file1_name;
                            $find->preview_image = $profile;
                        }
                    }
                    else
                        $find->preview_image = $preImage;
                    if ($find->save())
                        Yii::$app->session->setFlash('status', '4');
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
                else
                    Yii::$app->session->setFlash('status', '3');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $admin = Admin::findOne(Yii::$app->request->post()['Admin']['_id']);
            if($admin != null)
            {
                $status = $admin->status;
                if($status == 10)
                    $admin->status = 9;
                else
                    $admin->status = 10;
                if($admin->save())
                    Yii::$app->session->setFlash('status','6');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionParticipants($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetail = OfflineExams::findOne($_id);
            if($examDetail != null)
            {
                $searchModel = new ExamParticipants();
                $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $examDetail->_id);
                $dataProvider->pagination->pageSize = 50;
                return $this->render('participants', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'examDetail' => $examDetail
                ]);
            }
            else
                return $this->redirect(['../offline-exam']);
        }
        else
            return $this->redirect(['../offline-exam']);
    }

    public function actionPrint($_id)
    {
        if(isset($_GET['_id']))
        {
            $orderDetail = Orders::findOne($_id);
            if($orderDetail != null)
            {
                $this->layout = 'empty';
                $examDetail = OfflineExams::findOne($orderDetail->orders[0]['_id']);
                if($examDetail != null)
                {
                    return $this->render('print', [
                        'orderDetail' => $orderDetail,
                        'examDetail' => $examDetail,
                    ]);
                }
                else
                    return $this->redirect(['../offline-exam']);
            }
            else
                return $this->redirect(['../offline-exam']);
        }
        else
            return $this->redirect(['../offline-exam']);
    }

    public function actionFinancialPrint($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetail = OfflineExams::findOne($_id);
            if($examDetail != null)
            {
                $searchModel = new ExamParticipants();
                $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $examDetail->_id);
                $dataProvider->pagination->pageSize = 50;
                $this->layout = 'empty';
                return $this->render('financial-print', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'examDetail' => $examDetail
                ]);
            }
            else
                return $this->redirect(['../offline-exam']);
        }
        else
            return $this->redirect(['../offline-exam']);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new ExamParticipants();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Yii::$app->request->get()['ExamParticipants']['_id']);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fullName = '';
                                if(array_key_exists('first_name',$user->applicant_info))
                                    $fullName = $user->applicant_info['first_name'];
                                return $fullName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fullName = '';
                                if(array_key_exists('last_name',$user->applicant_info))
                                    $fullName = $user->applicant_info['last_name'];
                                return $fullName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'نام کاربری'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $phone_number = '';
                                if(array_key_exists('phone_number',$user->applicant_info))
                                    $phone_number = $user->applicant_info['phone_number'];
                                return $phone_number;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fatherName = '';
                                if(array_key_exists('father_name',$user->applicant_info))
                                    $fatherName = $user->applicant_info['father_name'];
                                return $fatherName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام پدر'
                ],
                [
                    'attribute' => function($model)
                    {
                        $row = null;
                        $i = 0;
                        foreach ($model->orders as $order)
                        {
                            if(($order['type'] == '3') && ($order['_id'] == Yii::$app->request->get()['ExamParticipants']['_id']))
                                $row = $i;
                            $i++;
                        }
                        if($row !== null)
                        {
                            if(array_key_exists('applicant_id',$model->orders[$row]))
                                return ' '.(string) $model->orders[$row]['applicant_id'].' ';
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'شماره داوطلبی'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('id',$user->applicant_info))
                                    return ' '.(string) $user->applicant_info['id'].' ';
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'کد ملی'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('needs_assistant',$user->applicant_info))
                                {
                                    if($user->applicant_info['needs_assistant'] == 1)
                                        return 'هستم';
                                    else
                                        return 'نیستم';
                                }
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نیازمند به منشی'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('left_handed',$user->applicant_info))
                                {
                                    if($user->applicant_info['left_handed'] == 1)
                                        return 'هستم';
                                    else
                                        return 'نیستم';
                                }
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'چپ دست هستم'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('major',$user->applicant_info))
                                {
                                    if($user->applicant_info['major'] != null && $user->applicant_info['major'] != '')
                                        return $user->applicant_info['major'];
                                    else
                                        return '-';
                                }
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'رشته تحصیلی'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('ut_student',$user->applicant_info))
                                {
                                    if($user->applicant_info['ut_student'] == true)
                                    {
                                        $utString = 'هستم ';
                                        if(array_key_exists('student_id',$user->applicant_info))
                                            if($user->applicant_info['student_id'] != null && $user->applicant_info['student_id'] != '')
                                                $utString = $utString.' - شماره دانشجویی - '.$user->applicant_info['student_id'];
                                        return $utString;
                                    }
                                    else
                                        return 'نیستم';
                                }
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'دانشجوی دکتری دانشگاه تهران'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('master_student',$user->applicant_info))
                                {
                                    if($user->applicant_info['master_student'] == true)
                                    {
                                        $utString = 'هستم ';
                                        if(array_key_exists('master_student_id',$user->applicant_info))
                                            if($user->applicant_info['master_student_id'] != null && $user->applicant_info['student_id'] != '')
                                                $utString = $utString.' - شماره دانشجویی - '.$user->applicant_info['master_student_id'];
                                        return $utString;
                                    }
                                    else
                                        return 'نیستم';
                                }
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'دانشجوی کارشناسی ارشد دانشگاه تهران'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['date'];
                    },
                    'header' => 'تاریخ پرداخت'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['order_id'];
                    },
                    'header' => 'شماره سفارش'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['reference_id'];
                    },
                    'header' => 'شماره مرجع پرداخت'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('profile_image',$user->applicant_info))
                                {
                                    if($user->applicant_info['profile_image'] != null && $user->applicant_info['profile_image'] != '')
                                        return 'https://eec1.ut.ac.ir/users_profile/'.$user->applicant_info['profile_image'];
                                    else
                                        return '';
                                }
                                return '-';
                            }
                        }
                        else
                            return '-';
                    },
                    'header' => 'لینک تصویر'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'ExamReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionFinancial_report()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new ExamParticipants();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,Yii::$app->request->get()['ExamParticipants']['_id']);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fullName = '';
                                if(array_key_exists('first_name',$user->applicant_info))
                                    $fullName = $user->applicant_info['first_name'];
                                return $fullName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fullName = '';
                                if(array_key_exists('last_name',$user->applicant_info))
                                    $fullName = $user->applicant_info['last_name'];
                                return $fullName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                $fatherName = '';
                                if(array_key_exists('father_name',$user->applicant_info))
                                    $fatherName = $user->applicant_info['father_name'];
                                return $fatherName;
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'نام پدر'
                ],
                [
                    'attribute' => function($model)
                    {
                        $row = null;
                        $i = 0;
                        foreach ($model->orders as $order)
                        {
                            if(($order['type'] == '3') && ($order['_id'] == Yii::$app->request->get()['ExamParticipants']['_id']))
                                $row = $i;
                            $i++;
                        }
                        if($row !== null)
                        {
                            if(array_key_exists('applicant_id',$model->orders[$row]))
                                return ' '.(string) $model->orders[$row]['applicant_id'].' ';
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'شماره داوطلبی'
                ],
                [
                    'attribute' => function($model){
                        $user = Users::find()->where(['username' => $model->username])->one();
                        if($user != null)
                        {
                            if($user->applicant_info != null)
                            {
                                if(array_key_exists('id',$user->applicant_info))
                                    return ' '.(string) $user->applicant_info['id'].' ';
                                else
                                    return '-';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'کد ملی'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['date'];
                    },
                    'header' => 'تاریخ پرداخت'
                ],
                [
                    'attribute' => function($model){
                        return number_format($model->amount).' ریال';
                    },
                    'header' => 'مبلغ پرداختی'
                ],
                [
                    'attribute' => function($model){
                        return ' '.$model->payment_info['order_id'].' ';
                    },
                    'header' => 'شماره سفارش'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['reference_id'];
                    },
                    'header' => 'شماره مرجع پرداخت'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'ExamReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }
    public function actionEdit_user()
    {
        if(Yii::$app->request->isPost)
        {
            $find = Users::findOne(Yii::$app->request->post()['Users']['_id']);
            if ($find != null)
            {
                $preImage = $find->applicant_info['profile_image'];
                $applicant_info = Yii::$app->request->post()['Users']['applicant_info'];
                if ($_FILES['Users']['name']['applicant_info']['profile_image'] != '')
                {
                    if ($preImage != '' && $preImage != null)
                        if(file_exists('../../frontend/web/users_profile/' . $preImage))
                            unlink('../../frontend/web/users_profile/' . $preImage);
                    $file1 = UploadedFile::getInstance($find, 'applicant_info[profile_image]');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/users_profile/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'applicant_info[profile_image]') != null) {
                        $profile = $file1_name;
                        $applicant_info['profile_image'] = $profile;
                    }
                }
                else
                    $applicant_info['profile_image'] = $preImage;
                $find->applicant_info = $applicant_info;
                if ($find->save())
                {
                    Yii::$app->session->setFlash('status', '1');
                    $userOrders = Orders::find()->where(['username' => $find->username])->all();
                    if($userOrders != null)
                    {
                        foreach ($userOrders as $order)
                        {
                            $order->applicant_info = $find->applicant_info;
                            $order->save();
                        }
                    }
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionEdit_score()
    {
        if(Yii::$app->request->isPost)
        {
            $order = Orders::findOne(Yii::$app->request->post()['Orders']['_id']);
            if($order != null)
            {
                if($order->orders != null)
                {
                    $row = null;
                    $i = 0;
                    foreach ($order->orders as $item)
                    {
                        if($item['type'] == '3' && Yii::$app->request->post('exam_id') == $item['_id'])
                            $row = $i;
                        $i++;
                    }
                    if($row !== null)
                    {
                        $myOrder = $order->orders;
                        $myOrder[$row]['score'] = Yii::$app->request->post('score');
                        $myOrder[$row]['registrant'] = Yii::$app->user->identity->username;
                        $order->orders = $myOrder;
                        if($order->save())
                            Yii::$app->session->setFlash('status','3');
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionEdit_card_tips()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = OfflineExams::findOne(Yii::$app->request->post()['OfflineExams']['_id']);
            if($exam != null)
            {
                $exam->load(Yii::$app->request->post());
                if($exam->save())
                    Yii::$app->session->setFlash('status','5');
                else
                    Yii::$app->session->setFlash('status','4');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDelete_user_from_exam()
    {
        if(Yii::$app->request->isPost)
        {
            $order = Orders::findOne(Yii::$app->request->post()['Orders']['_id']);
            if($order != null)
            {
                $row = null;
                $i = 0;
                foreach ($order->orders as $item)
                {
                    if($item['type'] == '3')
                       $row = $i;
                    $i++;
                }
                if($row !== null)
                {
                    $newOrder = $order->orders;
                    $newOrder[$row]['removed'] = '1';
                    $order->orders = $newOrder;
                    if($order->save())
                        Yii::$app->session->setFlash('status','5');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionArchive_profiles()
    {
        if(isset($_GET['_id']))
        {
            $exam = OfflineExams::findOne($_GET['_id']);
            if($exam != null)
            {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api-eec.ut.ac.ir/offline-exams/archive-profiles/'.$_GET['_id'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_POSTFIELDS => array('id' => '5'),
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJFUlA0OTE1MTM5IiwiZXhwIjoxNzE2MzM2OTU0LCJqdGkiOiI1NTMiLCJpYXQiOjE3MTYzMzMzNTR9.5ZWyfdhhYV6a8oeFfdx54NBL-ML_YRVxB7S7hyCeyYs'
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response);
                if(!property_exists($response,'statusCode'))
                {
                    if(property_exists($response,'data'))
                        $this->redirect('https://eec1.ut.ac.ir/users_profile/'.$response->data);
                }
            }
        }

    }

    public function actionTerminate_exam()
    {
        if(Yii::$app->request->isPost)
        {
            $find = OfflineExams::findOne(Yii::$app->request->post()['OfflineExams']['_id']);
            if ($find != null)
            {
                $find->status = '0';
                if ($find->save())
                    Yii::$app->session->setFlash('status', '6');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function user_detail($username)
    {
        return Users::find()->where(['username' => $username])->one();
    }

    public function number_of_participants($examId)
    {
        return Orders::find()->where(['orders._id' => $examId])->andWhere(['status' => '1'])->count();
    }
}
