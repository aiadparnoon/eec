<?php

namespace frontend\controllers;

use common\models\Admin;
use app\models\Users;
use Yii;
use app\models\Orders;
use app\models\Brokers;
use app\models\OrdersSearch;
use app\models\Installments;
use app\models\InstallmentsSearch;
use app\models\CoursesFinancialSearch;
use app\models\CoursesFinancial;
use app\models\Colleges;
use app\models\Courses;
use app\models\Generals;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;
require_once(Yii::$app->basePath . '/web/jdf.php');
/**
 * BuyersController implements the CRUD actions for Orders model.
 */
class ReportingController extends Controller
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
                        'actions' => ['index', 'report', 'new', 'edit','reset_password','change_status','installments','test','installments_report','create_excel','courses-financial','financial_excel'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker')
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
        $searchModel = new OrdersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(Yii::$app->user->identity->role == 'user')
        {
            $colleges = Colleges::find()->all();
            $brokers = Brokers::find()->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->_id;
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
        }
        else
        {
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
            $brokers = Brokers::find()->where(['college' => Yii::$app->user->identity->college])->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->_id;
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
        }
        $colleges = ArrayHelper::map($colleges, function ($model){
            return (string) $model->_id;
        },'title');
        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => $colleges,
            'brokers' => $brokers,
        ]);
    }

    public function actionInstallments()
    {
        $searchModel = new InstallmentsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(Yii::$app->user->identity->role == 'user')
        {
            $colleges = Colleges::find()->all();
            $brokers = Brokers::find()->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->_id;
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
        }
        else
        {
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
            $brokers = Brokers::find()->where(['college' => Yii::$app->user->identity->college])->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->_id;
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
        }
        $colleges = ArrayHelper::map($colleges, function ($model){
            return (string) $model->_id;
        },'title');
        $dataProvider->setPagination([
            'pageSize' => 50,
            // سایر تنظیمات pagination
        ]);
        return $this->render('installments', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => $colleges,
            'brokers' => $brokers,
        ]);
    }

    public function actionCoursesFinancial()
    {
        $searchModel = new CoursesFinancialSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $colleges = null;
        $brokers = null;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
        {
            $colleges = Colleges::find()->all();
            $brokers = Brokers::find()->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->connector_info['mobile'];
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
            $colleges = ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title');
        }
        else if(Yii::$app->user->identity->role == 'emp')
        {
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
            $brokers = Brokers::find()->where(['college' => Yii::$app->user->identity->college])->orderBy(['_id'=>SORT_DESC])->all();
            $brokers = ArrayHelper::map($brokers, function ($model){
                return (string) $model->connector_info['mobile'];
            }, function ($model){
                return $model->connector_info['first_name'].' '.$model->connector_info['last_name'];
            });
            $colleges = ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title');
        }
        $dataProvider->setPagination([
            'pageSize' => 50,
        ]);
        return $this->render('courses-financial', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => $colleges,
            'brokers' => $brokers,
        ]);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new OrdersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination = false;
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        return ' '.$model->username.' ';
                    },
                    'header' => ' نام کاربری'
                ],
                [
                    'attribute' => function($model){
                        $userDetail = Users::find()->where(['username' => $model->username])->one();
                        if($userDetail != null)
                            return $userDetail->first_name.' '.$userDetail->last_name;
                        else
                            return  'نامشخص';
                    },
                    'header' => 'نام و نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => function($model){
                        $orders = '';
                        if($model->orders != null)
                        {
                            $i = 0;
                            foreach ($model->orders as $order)
                            {
                                $collegeDetail = null;
                                $courseDetail = Courses::findOne($order['_id']);
                                if($courseDetail != null)
                                    $courseTitle = $courseDetail->title['main_fa'];
                                else
                                    $courseTitle = 'نامشخص';
                                if($courseDetail != null)
                                    $collegeDetail = Colleges::findOne($courseDetail->college);
                                if($collegeDetail != null && Yii::$app->user->identity->role == 'user')
                                {
                                    $collegeTitle = '(دانشکده '.$collegeDetail->title.')';
                                }
                                else
                                    $collegeTitle = '';
                                if($i++ == 0)
                                    $orders = $orders.' - '.$courseTitle.$collegeTitle;
                                else
                                    $orders = ' - '.$orders.' - '.$collegeTitle.$collegeTitle;
                            }
                        }
                        return $orders;
                    },
                    'header' => 'دوره ها'
                ],
                [
                    'attribute' => function($model){
                        if($model->shares != null)
                        {
                            $collegeDetail = Colleges::findOne($model->shares[0]['college']);
                            if($collegeDetail != null)
                                return $collegeDetail->title;
                            else
                                return 'نامشخص';
                        }
                        return 'نامشخص';
                    },
                    'header' => 'دانشکده'
                ],
                [
                    'attribute' => function($model){
                        $orders = '';
                        if($model->orders != null)
                        {
                            $courseDetail = Courses::findOne($model->orders[0]['_id']);
                            if($courseDetail != null)
                            {
                                if($courseDetail->license_code != null && $courseDetail->license_code != '')
                                    return $courseDetail->license_code;
                                else
                                    return '-';
                            }
                            else
                                return 'نامشخص';
                        }
                        return $orders;
                    },
                    'header' => 'کد مجوز دوره'
                ],
                [
                    'attribute' => function($model){
                        return jdate('Y/m/d',intval(substr($model->_id, 0, 8), 16));
                    },
                    'header' => 'تاریخ'
                ],
                [
                    'attribute' => function($model)
                    {
                        $finalAmount = $model->amount;
                        if($model->payments != null)
                        {
                            $i = 0;
                            foreach ($model->payments as $payment)
                            {
                                if($i != 0)
                                    $finalAmount += $payment['amount'];
                                $i++;
                            }
                        }
                        else
                        {
                            if($model->prepayment_settlement != null)
                                if(array_key_exists('status', $model->prepayment_settlement))
                                    $finalAmount += $model->prepayment_settlement['amount'];
                            if($model->settlement_payment != null)
                                if(array_key_exists('status', $model->settlement_payment))
                                    $finalAmount += $model->settlement_payment['amount'];
                        }
                        return $finalAmount;
                    },
                    'header' => 'مبلغ پرداخت شده'
                ],
                [
                    'attribute' => function($model){
                        return ' '.$model->payment_info['order_id'].' ';
                    },
                    'header' => 'شماره سفارش'
                ],
                [
                    'attribute' => function($model){
                        if($model->payment_info != null)
                        {
                            if(array_key_exists('tref', $model->payment_info))
                                return ' '.$model->payment_info['tref'].' ';
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'شماره پیگیری'
                ],
                [
                    'attribute' => function($model){
                        if($model->payment_info != null)
                        {
                            if(array_key_exists('tref', $model->payment_info))
                                return ' '.substr($model->payment_info['tref'], -6).' ';
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'شماره پیگری بانک مرکزی'
                ],
                [
                    'attribute' => function($model){
                        if($model->shares != null)
                        {
                            $ex = '';
                            $finalAmount = $model->amount;
                            if($model->payments != null)
                            {
                                $i = 0;
                                foreach ($model->payments as $payment)
                                {
                                    if($i != 0)
                                        $finalAmount += $payment['amount'];
                                    $i++;
                                }
                            }
                            else
                            {
                                if($model->prepayment_settlement != null)
                                    if(array_key_exists('status', $model->prepayment_settlement))
                                        $finalAmount += $model->prepayment_settlement['amount'];
                                if($model->settlement_payment != null)
                                    if(array_key_exists('status', $model->settlement_payment))
                                        $finalAmount += $model->settlement_payment['amount'];
                            }
                            $collegeDetail = DashboardController::college_detail($model->shares[0]['college']);
                            if(array_key_exists('broker_contract_percent', $model->shares[0]))
                                $ex = $ex.$collegeDetail->title.': '.number_format($finalAmount * ( (100 - $model->shares[0]['broker_contract_percent']) / 100 )).'  ';
                            return $ex;
                        }
                        else
                            return '-';
                    },
                    'header' => 'سهم دانشکده'
                ],
                [
                    'attribute' => function($model){
                        if($model->shares != null)
                        {
                            $ex = '';
                            $broker = '';
                            if(array_key_exists('broker', $model->shares[0]))
                            {
                                $brokerDetail = Brokers::findOne($model->shares[0]['broker']);
                                $broker = $brokerDetail->connector_info['first_name'].' '.$brokerDetail->connector_info['last_name'];
                            }
                            $finalAmount = $model->amount;
                            if($model->payments != null)
                            {
                                $i = 0;
                                foreach ($model->payments as $payment)
                                {
                                    if($i != 0)
                                        $finalAmount += $payment['amount'];
                                    $i++;
                                }
                            }
                            else
                            {
                                if($model->prepayment_settlement != null)
                                    if(array_key_exists('status', $model->prepayment_settlement))
                                        $finalAmount += $model->prepayment_settlement['amount'];
                                if($model->settlement_payment != null)
                                    if(array_key_exists('status', $model->settlement_payment))
                                        $finalAmount += $model->settlement_payment['amount'];
                            }
                            if(array_key_exists('broker_contract_percent', $model->shares[0]))
                            {
                                $brokerShare = $finalAmount * ( ($model->shares[0]['broker_contract_percent']) / 100 );
                                $ex = $ex. ' سهم کارگزار '.$broker.': '.number_format($brokerShare).' ';
                            }
                            return $ex;
                        }
                        else
                            return '-';
                    },
                    'header' => 'سهم کارگزار'
                ],
                [
                    'attribute' => function($model){
                        if($model->orders[0]['payment_method'] == 1)
                            return 'نقدی';
                        else
                            return 'اقساطی';
                    },
                    'header' => 'نوع پرداخت'
                ],
//                [
//                    'attribute' => function($model){
//                        if($model->orders[0]['payment_method'] == 1)
//                            return '-';
//                        else
//                        {
//                            $installments = Installments::find()->where(['order_id' => (string) $model->_id])->one();
//                            if($installments != null)
//                            {
//                                $finalMaturity = '';
//                                foreach ($installments->maturities as $maturity)
//                                {
//                                    $status = 'پرداخت نشده';
//                                    if($maturity['status'] == 1)
//                                        $status = 'پرداخت شده در تاریخ '.$maturity['payment_info']['date'];
//                                    $string = 'مبلغ '.number_format($maturity['amount']).' تومان سررسید تاریخ '.$maturity['date'].' '.$status;
//                                    $finalMaturity = $finalMaturity.' - '.$string;
//                                }
//                                return $finalMaturity;
//                            }
//                            else
//                                return 'خطا در بازیابی داده ها';
//                        }
//                    },
//                    'header' => 'اقساط'
//                ],
//                [
//                    'attribute' => function($model){
//                        if($model->orders[0]['payment_method'] == 1)
//                            return '-';
//                        else
//                        {
//                            $installments = Installments::find()->where(['order_id' => (string) $model->_id])->one();
//                            if($installments != null)
//                            {
//                                $finalMaturity = 0;
//                                foreach ($installments->maturities as $maturity)
//                                {
//                                    $status = 'پرداخت نشده';
//                                    if($maturity['status'] == 1)
//                                        $finalMaturity += $maturity['amount'];
//                                }
//                                return $finalMaturity;
//                            }
//                            else
//                                return 'خطا در بازیابی داده ها';
//                        }
//                    },
//                    'header' => 'مجموع اقساط پرداختی'
//                ],
                [
                    'attribute' => function($model){
                        if($model->shares != null)
                        {
                            $finalAmount = $model->amount;
                            if($model->payments != null)
                            {
                                $i = 0;
                                foreach ($model->payments as $payment)
                                {
                                    if($i != 0)
                                        $finalAmount += $payment['amount'];
                                    $i++;
                                }
                            }
                            else
                            {
                                if($model->prepayment_settlement != null)
                                    if(array_key_exists('status', $model->prepayment_settlement))
                                        $finalAmount += $model->prepayment_settlement['amount'];
                                if($model->settlement_payment != null)
                                    if(array_key_exists('status', $model->settlement_payment))
                                        $finalAmount += $model->settlement_payment['amount'];
                            }
                            if (array_key_exists('broker_contract_percent', $model->shares[0]))
                            {
                                $brokerShare = $model->shares[0]['broker_contract_percent'];
                                return ' '.number_format($finalAmount * ((100 - $brokerShare) / 100)).' ';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'سهم دانشکده'
                ],
                [
                    'attribute' => function($model)
                    {
                        if($model->shares != null)
                        {
                            $finalAmount = $model->amount;
                            if ($model->payments != null)
                            {
                                $i = 0;
                                foreach ($model->payments as $payment) {
                                    if ($i != 0)
                                        $finalAmount += $payment['amount'];
                                    $i++;
                                }

                            }
                            else
                            {
                                if ($model->prepayment_settlement != null)
                                    if (array_key_exists('status', $model->prepayment_settlement))
                                        $finalAmount += $model->prepayment_settlement['amount'];
                                if ($model->settlement_payment != null)
                                    if (array_key_exists('status', $model->settlement_payment))
                                        $finalAmount += $model->settlement_payment['amount'];
                            }
                            if (array_key_exists('broker_contract_percent', $model->shares[0]))
                            {
                                $brokerShare = $finalAmount * ( ($model->shares[0]['broker_contract_percent']) / 100 );
                                return ' '.number_format($brokerShare).' ';
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'سهم کارگزار'
                ],
//                [
//                    'attribute' => function($model){
//                        if($model->orders[0]['payment_method'] == 1)
//                            return '-';
//                        else
//                        {
//                            $installments = Installments::find()->where(['order_id' => (string) $model->_id])->one();
//                            if($installments != null)
//                            {
//                                $finalMaturity = 0;
//                                $orderDetail = Orders::findOne($installments->order_id);
//                                $share = null;
//                                if($orderDetail != null)
//                                {
//                                    if($orderDetail->shares != null)
//                                        if(array_key_exists('broker_contract_percent', $orderDetail->shares[0]))
//                                            $share = $orderDetail->shares[0]['broker_contract_percent'];
//                                }
//                                foreach ($installments->maturities as $maturity)
//                                {
//                                    if($maturity['status'] == 1)
//                                    {
//                                        $finalMaturity += ($maturity['amount'] * ((100 - $share) / 100));
//                                    }
//                                }
//                                return $finalMaturity;
//                            }
//                            else
//                                return 'خطا در بازیابی داده ها';
//                        }
//                    },
//                    'header' => 'سهم دانشگاه از پرداخت اقساط'
//                ],

//                [
//                    'attribute' => function($model){
//                        if($model->orders[0]['payment_method'] == 1)
//                            return '-';
//                        else
//                        {
//                            $installments = Installments::find()->where(['order_id' => (string) $model->_id])->one();
//                            if($installments != null)
//                            {
//                                $finalMaturity = 0;
//                                $orderDetail = Orders::findOne($installments->order_id);
//                                $share = null;
//                                if($orderDetail != null)
//                                {
//                                    if($orderDetail->shares != null)
//                                        if(array_key_exists('broker_contract_percent', $orderDetail->shares[0]))
//                                            $share = $orderDetail->shares[0]['broker_contract_percent'];
//                                }
//                                foreach ($installments->maturities as $maturity)
//                                {
//                                    if($maturity['status'] == 1)
//                                    {
//                                        $finalMaturity += ($maturity['amount'] * (($share) / 100));
//                                    }
//                                }
//                                return $finalMaturity;
//                            }
//                            else
//                                return 'خطا در بازیابی داده ها';
//                        }
//                    },
//                    'header' => 'سهم کارگزار از پرداخت اقساط'
//                ],

            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'OrdersReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionInstallments_report()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');

        $searchModel = new InstallmentsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // دریافت تاریخ‌های فیلتر از پارامترهای ورودی
        $params = Yii::$app->request->queryParams;

        // تبدیل تاریخ‌های فیلتر از فرمت با - به فرمت با /
        $startDate = isset($params['date1']) ? str_replace('-', '/', $params['date1']) : null;
        $endDate = isset($params['date2']) ? str_replace('-', '/', $params['date2']) : null;

        // همچنین برای فیلد deadline که بدون اسلش است
        $startDateNoSlash = $startDate ? str_replace('/', '', $startDate) : null;
        $endDateNoSlash = $endDate ? str_replace('/', '', $endDate) : null;

        // تبدیل داده‌ها به فرمت flat (یک سطر برای هر پرداخت موفق)
        $flatData = [];
        $models = $dataProvider->getModels();

        foreach ($models as $model)
        {
            $userDetail = Users::find()->where(['username' => $model->username])->one();
            $userName = ($userDetail != null) ? $userDetail->first_name.' '.$userDetail->last_name : 'نامشخص';

            $orderDetail = Orders::findOne($model->order_id);
            $orders = '';
            $courseLic = '';
            if ($orderDetail != null)
            {
                $i = 0;
                foreach ($orderDetail->orders as $order) {
                    $courseDetail = Courses::findOne($order['_id']);
                    $courseTitle = ($courseDetail != null) ? $courseDetail->title['main_fa'] : 'نامشخص';
                    $courseLic = ($courseDetail != null) ? $courseDetail->license_code : '-';

                    $collegeTitle = '';
                    if ($courseDetail != null) {
                        $collegeDetail = Colleges::findOne($courseDetail->college);
                        if ($collegeDetail != null && Yii::$app->user->identity->role == 'user') {
                            $collegeTitle = '(دانشکده '.$collegeDetail->title.')';
                        }
                    }

                    if ($i++ == 0) {
                        $orders = $courseTitle . $collegeTitle;
                    } else {
                        $orders .= ' - ' . $courseTitle . $collegeTitle;
                    }
                }
            }

            // محاسبه سهم دانشگاه و کارگزار
            $share = null;
            if ($orderDetail != null && !empty($orderDetail->shares)) {
                $share = $orderDetail->shares[0]['broker_contract_percent'] ?? null;
            }

            // ایجاد سطر برای هر پرداخت موفق
            foreach ($model->maturities as $maturity)
            {
                if ($maturity['status'] == '1')
                {
                    // بررسی فیلتر تاریخ - ابتدا چک کنیم آیا کاربر فیلتر زده یا نه
                    $shouldInclude = true;

                    if ($startDate || $endDate) {
                        $shouldInclude = false;

                        // بررسی بر اساس فیلد date (با اسلش)
                        $maturityDate = $maturity['payment_info']['date'];
                        if ((!$startDate || $maturityDate >= $startDate) &&
                            (!$endDate || $maturityDate <= $endDate)) {
                            $shouldInclude = true;
                        }

                        // اگر بر اساس date شامل نشد، بر اساس deadline چک کنیم
                        if (!$shouldInclude) {
                            $maturityDeadline = str_replace('/','',$maturity['payment_info']['date']);
                            if ((!$startDateNoSlash || $maturityDeadline >= $startDateNoSlash) &&
                                (!$endDateNoSlash || $maturityDeadline <= $endDateNoSlash)) {
                                $shouldInclude = true;
                            }
                        }
                    }

                    if (!$shouldInclude) {
                        continue;
                    }

                    // محاسبه سهم‌ها برای این پرداخت خاص
                    $universityShare = ($share !== null) ? ($maturity['amount'] * ((100 - $share) / 100)) : 0;
                    $brokerShare = ($share !== null) ? ($maturity['amount'] * ($share / 100)) : 0;
                    $tref = '-';
                    if(array_key_exists('payment_info', $maturity))
                        if(array_key_exists('tref', $maturity['payment_info']))
                            $tref = $maturity['payment_info']['tref'];
                    $financial_status = 'ندارد';
                    if(array_key_exists('payment_info', $maturity))
                        if(array_key_exists('financial_check', $maturity['payment_info']))
                            $financial_status = 'دارد';
                    $flatData[] = [
                        'username' => $model->username,
                        'full_name' => $userName,
                        'phone' => $model->username,
                        'courses' => $orders,
                        'courseLic' => $courseLic,
                        'maturity_details' => 'مبلغ '.number_format($maturity['amount']).' تومان سررسید تاریخ '.$maturity['date'],
                        'payment_date' => $maturity['payment_info']['date'] ?? $maturity['date'],
                        'amount' => $maturity['amount'],
                        'total_paid' => $maturity['amount'],
                        'university_share' => $universityShare,
                        'broker_share' => $brokerShare,
                        'deadline' => $maturity['deadline'],
                        'tref' => $tref,
                        'financial_status' => $financial_status
                    ];
                }
            }
        }

        // مرتب‌سازی بر اساس تاریخ پرداخت
        usort($flatData, function($a, $b) {
            return strcmp($a['deadline'], $b['deadline']);
        });

        $exporter = new Spreadsheet([
            'dataProvider' => new ArrayDataProvider([
                'allModels' => $flatData,
                'pagination' => false,
            ]),
            'columns' => [
                [
                    'attribute' => 'username',
                    'header' => 'نام کاربری'
                ],
                [
                    'attribute' => 'full_name',
                    'header' => 'نام و نام خانوادگی'
                ],
                [
                    'attribute' => 'phone',
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => 'courses',
                    'header' => 'دوره '
                ],
                [
                    'attribute' => 'courseLic',
                    'header' => 'کد مجوز'
                ],
                [
                    'attribute' => 'maturity_details',
                    'header' => 'جزئیات قسط'
                ],
                [
                    'attribute' => 'payment_date',
                    'header' => 'تاریخ پرداخت'
                ],
                [
                    'attribute' => 'amount',
                    'header' => 'مبلغ پرداختی',
                    'format' => ['decimal', 0]
                ],
                [
                    'attribute' => 'total_paid',
                    'header' => 'مجموع اقساط پرداختی',
                    'format' => ['decimal', 0]
                ],
                [
                    'attribute' => 'university_share',
                    'header' => 'سهم دانشگاه',
                    'format' => ['decimal', 0]
                ],
                [
                    'attribute' => 'broker_share',
                    'header' => 'سهم کارگزار',
                    'format' => ['decimal', 0]
                ],
                [
                    'attribute' => 'tref',
                    'header' => 'کد رهگیری'
                ],
                [
                    'attribute' => 'financial_status',
                    'header' => ' تائیده مالی'
                ],
            ],
        ]);

        $exporter->save('./newfile.xlsx');
        $file_name = 'InstallmentsReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionFinancial_excel()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new CoursesFinancialSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        $ret = '';
                        $user = Admin::find()->where(['username' => $model->registrant])->one();
                        if($user != null)
                        {
                            $role = 'ادمین';
                            if($user->role == 'emp')
                                $role = 'کارشناس دانشکده';
                            else if($user->role == 'cnt')
                                $role = 'کارمند مرکز';
                            else if($user->role == 'broker')
                                $role = 'کارگزار';
                            $ret = $user->first_name.' '.$user->last_name.' - '.$role;
                        }
                        return $ret;
                    },
                    'header' => 'ثبت کننده'
                ],
                [
                    'attribute' => function($model){
                        return $model->payment_info['date'];
                    },
                    'header' => 'تاریخ پرداخت'
                ],
                [
                    'attribute' => function($model){
                        return number_format($model->amount);
                    },
                    'header' => 'مبلغ (تومان)'
                ],
                [
                    'attribute' => function($model){
                        $ret = '';
                        $course = Courses::findOne($model->course_id);
                        if($course != null)
                            $ret = $course->title['main_fa'];
                        return $ret;
                    },
                    'header' => 'دوره'
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
        $file_name = 'coursesFinancial-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }


    public function user_detail($username)
    {
        return Users::find()->where(['username' => $username])->one();
    }

    public function course_detail($_id)
    {
        return Courses::findOne($_id);
    }

    public function broker_detail($_id)
    {
        return Brokers::findOne($_id);
    }

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function registrant($username)
    {
        $ret = '';
        $user = Admin::find()->where(['username' => $username])->one();
        if($user != null)
        {
            $role = 'ادمین';
            if($user->role == 'emp')
                $role = 'کارشناس دانشکده';
            else if($user->role == 'cnt')
                $role = 'کارمند مرکز';
            else if($user->role == 'broker')
                $role = 'کارگزار';
            $ret = $user->first_name.' '.$user->last_name.'<hr>'.$role;
        }
        return $ret;
    }
    public function installment_info($orderId, $courseId)
    {
        return Installments::find()->where(['order_id' => $orderId])->andWhere(['course_id' => $courseId])->one();
    }

    public function actionCreate_excel()
    {
        $auth_token = '68cbf10e4072ce76924';
        $first_name = '';
        if(Yii::$app->user->identity->role == 'emp')
            $college = Yii::$app->user->identity->college;
        else
            $college = Yii::$app->request->get()['OrdersSearch']['college'];
        if(Yii::$app->user->identity->role == 'broker')
        {
            $brokerDetail = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
            $broker = (string) $brokerDetail->_id;
        }
        else
            $broker = Yii::$app->request->get()['OrdersSearch']['shares']['broker'];
//        if(isset($_GET['date1']))
//            $date1 = Yii::$app->request->get('date1');
//        else
//            $date1 = jdate('Y-m-d');
//        if(isset($_GET['date2']))
//            $date2 = Yii::$app->request->get('date2');
//        else
//            $date2 = jdate('Y-m-d');
//        echo '<pre>';
//        print_r($_GET);
//        exit;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => Yii::getAlias('@baseUrl').'/orders/excel-report',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
                            "start_date": "'.Yii::$app->request->get('date1').'",
                            "end_date": "'.Yii::$app->request->get('date2').'",
                            "username": "'.Yii::$app->request->get()['OrdersSearch']['username'].'",
                            "first_name": "'.$first_name.'",
                            "last_name": "'.Yii::$app->request->get()['OrdersSearch']['last_name'].'",
                            "broker": "'.$broker.'",
                            "college": "'.$college.'",
                            "auth_token": "'.$auth_token.'"
                        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);
        // اگر فایل ایجاد شد
        if (isset($response->filename)) {
            // مسیر فایل را مشخص کنید (بستگی به ساختار پوشه دارد)
            $filePath = Yii::getAlias('@webroot') . '/orders_reports/' . $response->filename . '.xlsx';

            // یا اگر مسیر متفاوت است:
            // $filePath = Yii::getAlias('@app') . '/web/orders_reports/' . $response->filename . '.xlsx';

            // بررسی وجود فایل
            if (file_exists($filePath)) {
                // ارسال فایل برای دانلود
                return Yii::$app->response->sendFile($filePath, $response->filename . '.xlsx', [
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ]);
            } else {
                throw new \yii\web\NotFoundHttpException('فایل یافت نشد.');
            }
        } else {
            throw new \yii\web\ServerErrorHttpException('خطا در ایجاد فایل اکسل.');
        }
    }

    public function actionTest()
    {
//        $installments = Installments::find()->all();
//        foreach ($installments as $item)
//        {
//            $course = Courses::findOne($item->course_id);
//            if($course != null)
//            {
//                $item->college = $course->college;
//                if($course->broker != null)
//                    $item->broker = $course->broker['_id'];
//                else
//                    $item->broker = null;
//                if($item->save())
//                    echo (string) $item->_id.' => Saved<br>';
//                else
//                    echo (string) $item->_id.' => NotSaved<br>';
//            }
//            else
//                echo (string) $item->_id.' => Course NotFound<br>';
//        }
    }
}
