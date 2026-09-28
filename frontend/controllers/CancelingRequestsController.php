<?php

namespace frontend\controllers;

use app\models\Employee;
use Yii;
use common\models\Admin;
use app\models\CancelingRequestsSearch;
use app\models\CancelingRequests;
use app\models\Colleges;
use app\models\Courses;
use app\models\Users;
use app\models\Brokers;
use app\models\Orders;
use app\models\WalletTransactions;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

/**
 * BuyersController implements the CRUD actions for Admin model.
 */
class CancelingRequestsController extends Controller
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
                        'actions' => ['index', 'accept', 'reject'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (in_array(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker')
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
                    'accept' => ['post'],
                    'reject' => ['post'],
                ],
            ],
        ];
    }

    /**
     * امنیتی: تأیید/رد درخواست انصراف با بازگشت وجه به کیف پول کارگزار همراه است؛ پس کارگزار نباید
     * بتواند درخواست خودش را تأیید کند، هیچ‌کس درخواستی را که خودش ثبت کرده تأیید نمی‌کند و کارشناس
     * واحد فقط درخواست‌های واحد خودش را بررسی می‌کند.
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action))
            return false;
        if (!in_array($action->id, ['accept', 'reject'], true) || Yii::$app->user->isGuest)
            return true;
        $identity = Yii::$app->user->identity;
        $post = Yii::$app->request->post('CancelingRequests');
        $id = is_array($post) && isset($post['_id']) && is_string($post['_id']) ? $post['_id'] : '';
        $request = preg_match('/^[a-f0-9]{24}$/i', $id) ? CancelingRequests::findOne($id) : null;
        $allowed = $request !== null && $identity->role != 'broker'
            && (string) $request->registrant !== (string) $identity->username;
        if ($allowed && $identity->role == 'emp')
            $allowed = in_array((string) $request->college, \app\components\StudentAccess::staffColleges(), true);
        if (!$allowed) {
            Yii::$app->session->setFlash('status', '2');
            $this->redirect(\app\components\SafeRedirect::referrer(['index']))->send();
            return false;
        }
        return true;
    }

    /**
     * Lists all Buyers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CancelingRequestsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $colleges = Colleges::find()->all();
        $colleges = ArrayHelper::map($colleges, function ($model){
            return (string) $model->_id;
        },'title');

        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => $colleges,
        ]);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new AdminsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'first_name',
                    'header' => ' نام'
                ],
                [
                    'attribute' => 'last_name',
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'شماره همراه'
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
        $file_name = 'EmployeeReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionAccept()
    {
        if (Yii::$app->request->isPost)
        {
            $request = CancelingRequests::findOne(Yii::$app->request->post()['CancelingRequests']['_id']);
            if($request != null)
            {
                $course = Courses::findOne($request->course_id);
                if($course != null)
                {
                    $user = Users::find()->where(['username' => $request->username])->one();
                    if($user != null)
                    {
                        $request->status = 'approved';
                        $request->edited_by = Yii::$app->user->identity->username;
                        if($request->save())
                        {
                            $findWalletTransaction = WalletTransactions::find()->where(['reference_id' => (string) $request->_id])->all();
                            if($findWalletTransaction == null)
                            {
                                //Delete Course For User
                                $userCourses = $user->courses;
                                if($userCourses != null)
                                {
                                    $index = null;
                                    $i = 0;
                                    foreach ($userCourses as $item)
                                    {
                                        if($item['_id'] == $request->course_id)
                                            $index = $i;
                                        $i++;
                                    }
                                    if($index !== null)
                                    {
                                        unset($userCourses[$index]);
                                        $userCourses = array_values($userCourses);
                                        $user->courses = $userCourses;
                                        $user->save();
                                    }
                                }
                                //Delete Course For User

                                if($course->broker != null)
                                {
                                    if(array_key_exists('_id', $course->broker))
                                    {
                                        $broker = Brokers::findOne($course->broker['_id']);
                                        if($broker != null)
                                        {
                                            $brokerWallet = 0;
                                            if($broker->wallet_amount != null)
                                                $brokerWallet = $broker->wallet_amount;
                                            $order = Orders::findOne($request->order_id);
                                            if($order != null)
                                            {
                                                //Update Orders Collection
                                                $order->is_canceled = true;
                                                $order->save();
                                                //Update Orders Collection
                                                $brokerWallet += $order->shares[0]['college_share'];
                                                $broker->wallet_amount = (string) $brokerWallet;
                                                $broker->save();
                                                $walletTransaction = new WalletTransactions();
                                                $walletTransaction->amount = $order->shares[0]['college_share'];
                                                $walletTransaction->college = $request->college;
                                                $walletTransaction->broker_id = (string) $broker->_id;
                                                $walletTransaction->status = '1';
                                                $walletTransaction->date = jdate('Y/m/d');
                                                $walletTransaction->payment_info = null;
                                                $walletTransaction->type = '6';
                                                $walletTransaction->reference_id = (string) $request->_id;
                                                $walletTransaction->save();
                                                //Call API For Fmut
                                                if((string) $broker->_id == '65b0aa2ea7609e1c9e0e8d73')
                                                {
                                                    $adminRole = Admin::find()->where(['role' => 'user'])->one();
                                                    if($adminRole != null)
                                                    {
                                                        $curl = curl_init();

                                                        curl_setopt_array($curl, array(
                                                            CURLOPT_URL => 'https://api.fmut.ir/user-course-info/update-canceling-status',
                                                            CURLOPT_RETURNTRANSFER => true,
                                                            CURLOPT_ENCODING => '',
                                                            CURLOPT_MAXREDIRS => 10,
                                                            CURLOPT_TIMEOUT => 0,
                                                            CURLOPT_FOLLOWLOCATION => true,
                                                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                            CURLOPT_CUSTOMREQUEST => 'POST',
                                                            CURLOPT_POSTFIELDS =>'{
    "order_id": "'.(string) $order->_id.'",
    "status": "'.(string) $request->status.'"
}',
                                                            CURLOPT_HTTPHEADER => array(
                                                                '_id: '.(string) $adminRole->_id,
                                                                'Content-Type: application/json'
                                                            ),
                                                        ));

                                                        $response = curl_exec($curl);
                                                        curl_close($curl);
                                                    }
                                                }
                                                //Call API For Fmut
                                            }
                                        }
                                    }
                                }
                                Yii::$app->session->setFlash('status','1');
                            }
                        }
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                }
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

    public function course_detail($_id)
    {
        return Courses::findOne($_id);
    }

    public function registrant_detail($registrant, $role)
    {
        $ret = null;
        if($role == 'broker')
        {
            $broker = Brokers::findOne($registrant);
            if($broker != null)
                $ret = 'کارگزار-'.$broker->connector_info['first_name'].' '.$broker->connector_info['last_name'];
        }
        else if($role == 'emp')
        {
            $emp = Admin::findOne($registrant);
            if($emp != null)
                $ret = 'کارمند-'.$emp->first_name.' '.$emp->last_name;
        }
        return $ret;
    }

    public function order_detail($_id)
    {
        return Orders::findOne($_id);
    }

    public function who_edited($username)
    {
        return Admin::find()->where(['username' => $username])->one();
    }
}
