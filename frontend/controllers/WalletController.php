<?php

namespace frontend\controllers;

use Yii;
use app\models\WalletTransactions;
use app\models\WalletTransactionsSearch;
use app\models\WalletTransactionsForBrokersSearch;
use app\models\Brokers;
use app\models\Colleges;
use app\models\Users;
use app\models\CancelingRequests;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;
date_default_timezone_set("Asia/Tehran");
require_once(Yii::$app->basePath . '/web/jdf.php');

/**
 * ManageBrokersController implements the CRUD actions for Brokers model.
 */
class WalletController extends Controller
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
                        'actions' => ['index','increase_wallet','increase_wallet_callback','report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker')
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

    public function actionIndex()
    {
        $searchModel = new WalletTransactionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchModelForBrokers = new WalletTransactionsForBrokersSearch();
        $dataProviderForBrokers = $searchModelForBrokers->search(Yii::$app->request->queryParams);
        $brokers = Brokers::find()->all();
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->andWhere(['_id' => Yii::$app->user->identity->college])->all();
        if(Yii::$app->user->identity->role == 'emp')
        {
            $brokers = Brokers::find()->where(['college' => Yii::$app->user->identity->college])->all();
        }
        $brokers = ArrayHelper::map($brokers, function ($model){
            return (string) $model->_id;
        }, function ($model){
            return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
        });
        $dataProvider->pagination->pageSize = 50;
        $dataProviderForBrokers->pagination->pageSize = 50;
        return $this->render('brokers', [
                'searchModel' => $searchModelForBrokers,
                'dataProvider' => $dataProviderForBrokers,
                'colleges' => ArrayHelper::map($colleges, function ($model){
                    return (string) $model->_id;
                },'title'),
                'brokers' => $brokers
            ]);
    }

    public function actionIncrease_wallet()
    {
        if(Yii::$app->request->isPost)
        {
            if(Yii::$app->user->identity->role != 'broker')
                $broker = Brokers::findOne(Yii::$app->request->post()['WalletTransactions']['broker_id']);
            else
                $broker = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            if($broker != null)
            {
                $collegeDetail = Colleges::findOne($broker->college);
                if($collegeDetail != null)
                {
                    $amount = Yii::$app->request->post()['WalletTransactions']['amount'] * 10;
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api-eec.ut.ac.ir/external-api/gateway-token',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{
                "mobile": "",
                "amount": "'. $amount.'",
                "id": "'. $collegeDetail->financial_info['id'].'",
                "api_key": "'. \app\components\PaymentConfig::apiKey() .'",
                "callback": "https://eec1.ut.ac.ir/wallet/increase_wallet_callback"
}',
                        CURLOPT_HTTPHEADER => array(
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response);
                    curl_close($curl);
                    if($response != null)
                    {
                        if (property_exists($response->data, 'gateway_token'))
                        {
                            $model = new WalletTransactions();
                            $model->amount = Yii::$app->request->post()['WalletTransactions']['amount'];
                            $model->broker_id = (string) $broker->_id;
                            $model->college = (string) $collegeDetail->_id;
                            $model->status = '0';
                            $model->date = jdate('Y/m/d');
                            $paymentInfo = array(
                                'amount' => Yii::$app->request->post()['WalletTransactions']['amount'],
                                'order_id' => $response->data->order_id,
                                'date' => jdate('Y/m/d'),
                                'clean_date' => jdate('Ymd'),
                                'gateway_token' => $response->data->gateway_token,
                            );
                            $model->payment_info = $paymentInfo;
                            $model->type = '5';
                            if($model->save())
                            {
                                $data = ['autoSubmit' => true, 'formAction' => 'test', 'formData' => ['RefId' => $response->data->gateway_token]];
                                return $this->render('auto_submit_form', $data);
                            }
                            else
                                Yii::$app->session->setFlash('status','2');
                        }
                        else
                            Yii::$app->session->setFlash('status','3');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionIncrease_wallet_callback()
    {
        if(isset($_GET['order_id']))
        {
            $model = WalletTransactions::find()->where(['payment_info.order_id' => Yii::$app->request->get('order_id')])->one();
            if($model != null)
            {
                $broker = Brokers::findOne($model->broker_id);
                if($broker != null)
                {
                    if($model->status == '0')
                    {
                        if (Yii::$app->request->get('status') == 'success')
                        {
                            $paymentInfo = $model->payment_info;
                            $paymentInfo['tref'] = Yii::$app->request->get('tref');
                            $model->status = '1';
                            $model->payment_info = $paymentInfo;
                            $model->save();
                            if($broker->wallet_amount != null)
                                $brokerWalletAmount = $broker->wallet_amount + $model->amount;
                            else
                                $brokerWalletAmount = $model->amount;
                            $broker->wallet_amount = (string) $brokerWalletAmount;
                            $broker->save();
                            Yii::$app->session->setFlash('status', '1');
                        }
                        else
                        {
                            Yii::$app->session->setFlash('status', '4');
                        }
                        return $this->redirect(['index']);
                    }
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }


    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new WalletTransactionsForBrokersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        return number_format($model->amount);
                    },
                    'header' => 'مبلغ'
                ],
                [
                    'attribute' => function($model){
                        $ret = '-';
                        if($model->type == '3' || $model->type == '6')
                            $ret = 'سامانه';
                        else
                        {
                            $payerName = null;
                            if($model->payer != null)
                            {
                                if(is_array($model->payer))
                                {
                                    if(array_key_exists('first_name', $model->payer) && array_key_exists('last_name', $model->payer))
                                        $payerName = $model->payer['first_name'].' '.$model->payer['last_name'];
                                }
                            }
                            if($payerName !== null)
                                $ret = $payerName;
                        }
                        return $ret;
                    },
                    'header' => 'واریز / برداشت کننده'
                ],
                [
                    'attribute' => function($model){
                        $ret = '-';
                        $payerMobile = null;
                        if($model->payer != null)
                        {
                            if(is_array($model->payer))
                            {
                                if(array_key_exists('mobile', $model->payer))
                                    $payerMobile = $model->payer['mobile'];
                            }
                        }
                        if($payerMobile !== null)
                            $ret = $payerMobile;
                        return $ret;
                    },
                    'header' => 'شماره همراه واریز کننده'
                ],
                [
                    'attribute' => function($model){
                        return $model->date;
                    },
                    'header' => 'تاریخ'
                ],
                [
                    'attribute' => function($model){
                        $type = 'پرداخت از لینک';
                        if($model->type == '2')
                            $type = 'پرداخت از وب سرویس';
                        else if($model->type == '3')
                            $type = 'افزودن دانشپذیر';
                        else if($model->type == '4')
                            $type = 'تائید صورت حساب مالی';
                        else if($model->type == '5')
                            $type = 'پرداخت مستقیم';
                        else if($model->type == '6')
                            $type = 'انصراف دانشپذیر';
                        return $type;
                    },
                    'header' => 'نوع'
                ],
                [
                    'attribute' => function($model){
                        $ret = '-';
                        if($model->payment_info != null)
                            if(array_key_exists('tref', $model->payment_info))
                                $ret = ' '.$model->payment_info['tref'].' ';
                        return $ret;
                    },
                    'header' => 'کد رهگیری'
                ],
                [
                    'attribute' => function($model){
                        $ret = 'ندارد';
                        if($model->payment_info != null)
                            if(array_key_exists('financial_check', $model->payment_info))
                                $ret = 'دارد';
                        return $ret;
                    },
                    'header' => 'تائیدیه مالی'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'walletReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function broker_detail($username)
    {
        return Brokers::findOne($username);
    }

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function wallet($username)
    {
        $ret = 0;
        $broker = Brokers::find()->where(['connector_info.mobile' => $username])->one();
        if($broker != null)
            if($broker->wallet_amount != null)
                $ret = $broker->wallet_amount;
        return $ret;
    }

    public function canceling_request($_id)
    {
        $ret = '-';
        $cancel = CancelingRequests::findOne($_id);
        if($cancel != null)
        {
            $user = Users::find()->where(['username' => $cancel->username])->one();
            if($user != null)
                $ret = $user->first_name.' '.$user->last_name;
        }
        return $ret;
    }

}
