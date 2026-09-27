<?php
namespace frontend\controllers;
use app\models\Members;
use common\models\Admin;
use MongoDB\BSON\ObjectId;
use yii\helpers\ArrayHelper;
use PHPExcel_IOFactory;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\models\Colleges;
use app\models\Installments;
use app\models\Orders;
use app\models\Teachers;
use app\models\Brokers;
use app\models\Courses;
use app\models\Generals;
use app\models\Users;
use app\models\CoursesSearch;
use app\models\Lessons;
use app\models\CourseSessions;
use app\models\CertificateRequests;
use app\models\Exams;
use app\models\WalletTransactions;
use app\models\CoursesFinancial;
use yii\web\UploadedFile;
use yii\data\ArrayDataProvider;
use yii2tech\spreadsheet\Spreadsheet;
use MongoDB\BSON\UTCDateTime;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

class DashboardController extends \common\component\Controller
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
                        'actions' => ['index','export-courses-year','test','test1','test2','text3','create_natural','full-report','courses-report','report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            if ($user->role == 'user' || $user->role == 'emp' || $user->role == 'teacher' || $user->role == 'broker' || $user->role == 'cnt')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['confirm_package','back_package','reject_package'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['confirm_package_from_college','back_package_from_college','reject_package_from_college'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'emp')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['send_course_to_admin'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            if ($user->role == 'user' || $user->role == 'emp' || $user->role == 'broker')
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

    public function actionIndex()
    {
        $searchModel = new CoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, ['1','2','3']);
        $dataProvider->pagination->pageSize = 50;
        $userDetail = $this->user_detail();
        $allPrintedCertificate = CertificateRequests::find()->where(['<>', 'serial_number', null])->count();

        // فیلتر بخش «دوره های در انتظار بررسی» (طبق درخواست ۲۰۲۶-۰۸-۲۸):
        // همون فیلدهای فیلتر لیست دوره‌های بلندمدت (packages/_search.php) -
        // نام دوره، کد مجوز، نوع دوره، دانشکده، کارگزار - به‌همراه فیلتر
        // «از تاریخ / تا تاریخ» (بر اساس timestamp توکاررفته توی _id، دقیقاً
        // همون تکنیکِ CoursesSearch::search()) اینجا هم اضافه شده - با
        // نام‌های GET جدا (پیشوند pending_) که با پارامترهای فرم CoursesSearch
        // بالای همین صفحه تداخلی نداره. فیلتر «وضعیت» عمداً اضافه نشده، چون
        // این بخش خودش ذاتاً فقط دوره‌های status=2 رو نشون می‌ده.
        $pendingRegDateFrom = Yii::$app->request->get('pending_reg_date_from');
        $pendingRegDateTo = Yii::$app->request->get('pending_reg_date_to');
        $pendingTitle = Yii::$app->request->get('pending_title');
        $pendingLicenseCode = Yii::$app->request->get('pending_license_code');
        $pendingContentType = Yii::$app->request->get('pending_content_type');
        $pendingCollege = Yii::$app->request->get('pending_college');
        $pendingBroker = Yii::$app->request->get('pending_broker');
        $pendingQuery = Courses::find()->where(['status' => '2']);
        $pendingQuery->andFilterWhere(['like', 'title.main_fa', $pendingTitle])
            ->andFilterWhere(['like', 'license_code', $pendingLicenseCode])
            ->andFilterWhere(['like', 'content_type', $pendingContentType])
            ->andFilterWhere(['like', 'college', $pendingCollege])
            ->andFilterWhere(['like', 'broker._id', $pendingBroker]);
        if ($pendingRegDateFrom !== null && trim((string) $pendingRegDateFrom) !== '') {
            $parts = explode('-', $pendingRegDateFrom);
            if (count($parts) === 3) {
                $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                $ts = strtotime($g[0] . '-' . $g[1] . '-' . $g[2]); // ابتدای همون روز
                if ($ts !== false) {
                    $pendingQuery->andWhere(['>=', '_id', new ObjectId(dechex($ts) . '0000000000000000')]);
                }
            }
        }
        if ($pendingRegDateTo !== null && trim((string) $pendingRegDateTo) !== '') {
            $parts = explode('-', $pendingRegDateTo);
            if (count($parts) === 3) {
                $g = jalali_to_gregorian($parts[0], $parts[1], $parts[2]);
                $ts = strtotime($g[0] . '-' . $g[1] . '-' . $g[2]);
                if ($ts !== false) {
                    $ts += 86399; // انتهای همون روز (23:59:59)
                    $pendingQuery->andWhere(['<=', '_id', new ObjectId(dechex($ts) . '0000000000000000')]);
                }
            }
        }

        // گزینه‌های دراپ‌داونِ «دانشکده» و «کارگزار» برای همون فرم فیلتر -
        // فقط وقتی لازمه (نقش user) ساخته می‌شن تا برای بقیه‌ی نقش‌ها بار
        // اضافه‌ای روی دیتابیس نذاره.
        $pendingColleges = [];
        $pendingBrokers = [];
        if (Yii::$app->user->identity->role == 'user') {
            // (همون الگوی موجودِ پروژه برای map کردن دانشکده‌ها - مثلاً
            // ManageFinancialController - چون _id از نوع MongoDB\BSON\ObjectId
            // هست و باید صریحاً به رشته تبدیل بشه، وگرنه به‌عنوان کلید آرایه
            // قابل استفاده نیست.)
            $pendingColleges = ArrayHelper::map(Colleges::find()->all(), function ($model) {
                return (string) $model->_id;
            }, 'title');
            $pendingBrokers = ArrayHelper::map(
                Brokers::find()->orderBy(['_id' => SORT_DESC])->all(),
                function ($model) { return (string) $model->_id; },
                function ($model) { return $model->connector_info['first_name'] . ' ' . $model->connector_info['last_name']; }
            );
        }

        return $this->render('index',[
            'fullName' => $userDetail['fullName'],
            'role' => $userDetail['role'],
            'profile' => $userDetail['profile'],
            'user' => Yii::$app->user->identity,
            'coursesPending' => $pendingQuery->orderBy(['_id'=>SORT_DESC])->all(),
            'pendingRegDateFrom' => $pendingRegDateFrom,
            'pendingRegDateTo' => $pendingRegDateTo,
            'pendingTitle' => $pendingTitle,
            'pendingLicenseCode' => $pendingLicenseCode,
            'pendingContentType' => $pendingContentType,
            'pendingCollege' => $pendingCollege,
            'pendingBroker' => $pendingBroker,
            'pendingColleges' => $pendingColleges,
            'pendingBrokers' => $pendingBrokers,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'allPrintedCertificate' => $allPrintedCertificate,
        ]);
    }

    public function actionExportCoursesYear()
    {
        // تنظیم زمان و شامل کردن jdf
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');

        // 1. دریافت همه دوره‌های با شرایط پایه
        $coursesQuery = Courses::find();

        // شرط from_pec true نباشد (false یا null)
        $coursesQuery->andWhere(['or', ['from_pec' => false], ['from_pec' => null]]);
        // شرط status در [0,1,6] (مقدار رشته‌ای)
        $coursesQuery->andWhere(['in', 'status', ['0', '1', '6']]);

        // فیلتر سال ۱۴۰۴ بر اساس date.to (شروع با '1404-')
        // در مانگو می‌توان از regex استفاده کرد
        $coursesQuery->andWhere(['regex', 'date.to', '/^1404-/']);

        $courses = $coursesQuery->all();

        if (empty($courses)) {
            Yii::$app->session->setFlash('warning', 'هیچ دوره‌ای با شرایط مورد نظر یافت نشد.');
            return $this->redirect(['index']); // یا هر جای دیگر
        }

        // 2. جمع‌آوری id دوره‌ها (رشته)
        $courseIds = array_map(function($course) {
            return (string) $course->_id;
        }, $courses);
        $objectIds = array_map(function($id) {
            return new ObjectId($id);
        }, $courseIds);

        // 3. تعداد دانش‌پذیران ثبت‌نام شده (ورودی) از مدل Users
        $enrollCounts = [];
        foreach ($courses as $course) {
            $enrollCounts[(string) $course->_id] = Users::find()
                ->where(['courses._id' => (string) $course->_id])
                ->count();
        }

        // 4. تعداد فارغ‌التحصیلان (خروجی) از مدل CancelingRequests با status = 4
        $graduateCounts = [];
        $cancelCollection = CertificateRequests::getCollection();
        $pipelineGrad = [
            ['$match' => [
                'course_id' => ['$in' => $courseIds],
                'status' => '4'
            ]],
            ['$group' => [
                '_id' => '$course_id',
                'count' => ['$sum' => 1]
            ]]
        ];
        $gradResult = $cancelCollection->aggregate($pipelineGrad);
        foreach ($gradResult as $row) {
            $graduateCounts[(string) $row['_id']] = $row['count'];
        }

        // 5. تاریخ امروز شمسی برای مقایسه
        $today = jdate('Y-m-d');

        // 6. آماده‌سازی داده‌ها برای Spreadsheet
        $data = [];
        foreach ($courses as $course) {
            // نام دوره
            $title = $course->title['main_fa'] ?? 'بدون عنوان';

            // شهریه (قبلاً نرمالایز شده)
            $price = isset($course->price) ? number_format($course->price) . ' تومان' : 'نامشخص';

            // طول دوره (دقیقه؟ یا ساعت؟ همانطور که در دیتابیس است)
            $duration = $course->duration ?? '-';

            // --- وضعیت برگزاری ---
            $dateFrom = $course->date['from'] ?? null;
            $dateTo = $course->date['to'] ?? null;
            if ($dateTo && $dateFrom) {
                if ($today > $dateTo) {
                    $statusEvent = 'برگزار شده';
                } elseif ($today < $dateFrom) {
                    $statusEvent = 'برگزار نشده';
                } else {
                    $statusEvent = 'در حال برگزاری';
                }
            } else {
                $statusEvent = 'نامشخص';
            }

            // --- سال برگزاری از date.to ---
            $year = $dateTo ? substr($dateTo, 0, 4) : '-';

            // --- ظرفیت بر اساس مجوز ---
            $capacity = '-';
            $sc = $course->student_capacity;
            if (is_array($sc) || is_object($sc)) {
                $type = is_array($sc) ? $sc['type'] : $sc->type;
                if ($type == '1') {
                    $capacity = 'نامحدود';
                } elseif ($type == '2') {
                    $num = is_array($sc) ? ($sc['number'] ?? '-') : ($sc->number ?? '-');
                    $capacity = $num . ' نفر';
                } elseif ($type == '3') {
                    $capacity = 'سازمانی';
                }
            }

            // --- تعداد دانش‌پذیران (ورودی) ---
            $enrolled = $enrollCounts[(string) $course->_id] ?? 0;

            // --- تعداد فارغ‌التحصیلان (خروجی) ---
            $graduates = $graduateCounts[(string) $course->_id] ?? 0;

            // --- شماره مجوز ---
            $license = $course->license_code ?? '-';

            $data[] = [
                $title,
                $price,
                $duration,
                $statusEvent,
                $year,
                $capacity,
                $enrolled,
                $graduates,
                $license,
            ];
        }

        // 7. ساخت Spreadsheet
        $exporter = new Spreadsheet([
            'dataProvider' => new \yii\data\ArrayDataProvider([
                'allModels' => $data,
                'pagination' => false,
            ]),
            'columns' => [
                ['attribute' => '0', 'header' => 'نام دوره'],
                ['attribute' => '1', 'header' => 'شهریه'],
                ['attribute' => '2', 'header' => 'طول دوره'],
                ['attribute' => '3', 'header' => 'وضعیت برگزاری'],
                ['attribute' => '4', 'header' => 'سال برگزاری'],
                ['attribute' => '5', 'header' => 'ظرفیت بر اساس مجوز'],
                ['attribute' => '6', 'header' => 'تعداد دانش‌پذیران (ورودی)'],
                ['attribute' => '7', 'header' => 'تعداد فارغ‌التحصیلان (خروجی)'],
                ['attribute' => '8', 'header' => 'شماره مجوز'],
            ],
        ]);

        // 8. ذخیره و ارسال فایل
        $fileName = 'Courses_1404_' . jdate('Ymd_His') . '.xlsx';
        $tempFile = Yii::getAlias('@runtime') . '/' . $fileName;
        $exporter->save($tempFile);

        return Yii::$app->response->sendFile($tempFile, $fileName)->on(\yii\web\Response::EVENT_AFTER_SEND, function() use ($tempFile) {
            unlink($tempFile);
        });
    }

    public static function access($page)
    {
        $userDetail = Yii::$app->user->identity;
        $access = false;
        $accessList = Yii::$app->user->identity->access;
        if($page == 'create-course' || $page == 'create-package')
        {
            if($userDetail->role == 'user' || $userDetail->role == 'cnt' || $userDetail->role == 'emp' || $userDetail->role == 'broker')
                $access = true;
        }
        else if($page == 'certificate-manage')
        {
            if($userDetail->role == 'user' || (array_search('certificate-manage', $accessList) && $userDetail->role == 'cnt'))
                $access = true;
        }
        return $access;
    }

    public static function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public static function teacher_detail($_id)
    {
        return Teachers::findOne($_id);
    }

    public static function user_detail()
    {
        $user = Yii::$app->user->identity;
        if($user->role == 'user')
        {
            $detail = Admin::find()->where(['username' => $user->username])->one();
            $fullName = $detail->first_name.' '.$detail->last_name;
            $role = 'ادمین';
            $profile = 'assets/images/logo.png';
        }
        else
        {
            if($user->role == 'emp' || $user->role == 'cnt' || $user->role == 'broker')
            {
                $gender = '';
                if($user->role == 'emp')
                {
                    $gender = 'آقای';
                    if($user->gender == '1')
                        $gender = 'خانم';
                }
                $collegeDetail = Colleges::findOne($user->college);
                if($user->role == 'emp')
                    $role = 'مسئول  '.$collegeDetail->title;
                else if($user->role == 'cnt')
                    $role = 'کارمند مرکز';
                else if($user->role == 'broker')
                    $role = 'کارگزار دانشکده '.$collegeDetail->title;
                if($user->role == 'cnt')
                    $profile = 'college_logos/cnt.svg';
                else
                    $profile = 'college_logos/'.$collegeDetail->logo;
            }
            else if($user->role == 'teacher')
            {
                if($user->mentor == true)
                {
                    $role = 'دستیار استاد';
                    $gender = '';
                    $profile = 'teacher_profiles/teacher_profile.svg';
                }
                else
                {
                    $gender = 'آقای';
                    $role = 'استاد';
                    $teacher = Teachers::find()->where(['mobile' => $user->username])->one();
                    if($teacher->gender == '1')
                        $gender = 'خانم';
                    if($teacher->profile_image == 'teacher_profile.svg')
                        $profile = 'teacher_profiles/teacher_profile.svg';
                    else
                        $profile = 'teacher_profiles/'.$teacher->profile_image;
                }
            }
            $fullName = $gender.' '. $user->first_name.' '.$user->last_name;
        }
        $userDetail = array(
            'role' => $role,
            'fullName' => $fullName,
            'profile' => $profile
        );
        return $userDetail;
    }

    public static function registrant_detail($username)
    {
        return Admin::find()->where(['username' => $username])->one();
    }

    public function actionConfirm_package()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                $flag = 0;
                $college = Colleges::findOne($package->college);
                if($package->student_capacity['type'] != '3')
                {
                    if($college != null)
                    {
                        if($college->financial_info == null)
                            $flag = 1;
                        else if($college->financial_info['id'] == '')
                            $flag = 1;
                    }
                    if($package->broker != null)
                    {
                        $broker = Brokers::findOne($package->broker['_id']);
                        if($broker !== null)
                        {
                            if($broker->financial_info == null)
                                $flag = 2;
                            else if($broker->financial_info['id'] == '')
                                $flag = 2;
                            if($package->date != null)
                            {
                                $packageDate = $package->date['to'];
                                $contractDate = null;
                                if($broker->contracts != null)
                                {
                                    foreach ($broker->contracts as $item)
                                        if($package->broker['contract'] == $item['id'])
                                            if(array_key_exists('expiration_date', $item))
                                                $contractDate = $item['expiration_date'];
                                }
                                if($packageDate != null && $contractDate != null)
                                {
                                    $contractDate = str_replace('-','/', $contractDate);
                                    $packageDate = str_replace('-','/', $packageDate);
                                    if(!$this->isPackageDateValid($contractDate, $packageDate))
                                        $flag = '3';
                                    if(!$this->isContractDateValid($contractDate))
                                        $flag = '4';
                                }
                            }
                        }
                    }
                }
                if($package->status != '1' && $flag == 0)
                {
                    $lastLicense = Generals::find()->where(['type' => 'license_code'])->one();
                    if($lastLicense != null)
                    {
                        if($college != null)
                        {
                            $package->status = '1';
                            $package->license_code = $college->prefix.'-'.(string) ($lastLicense->data + 1);
                            if($package->save())
                            {
                                $lastLicense->updateCounters(['data' => 1]);
                                $lastLicense->save();
                                // Call AdobeConnect For Create Meetings Course
                                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                                if($adminRole != null &&  ($package->content_type == '1' || $package->content_type == '2'))
                                {
                                    $curl = curl_init();

                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/create-meeting/'.(string) $package->_id,
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_HTTPHEADER => array(
                                            '_id: '.(string) $adminRole->_id
                                        ),
                                    ));
                                    $response = curl_exec($curl);
                                    curl_close($curl);
                                }
                                // Call AdobeConnect For Create Meetings Course
                                Yii::$app->session->setFlash('status','6');
                            }
                            else
                                Yii::$app->session->setFlash('status','2');
                        }
                    }
                }
                else if($flag == 1)
                    Yii::$app->session->setFlash('status','8');
                else if($flag == 2)
                    Yii::$app->session->setFlash('status','9');
                else if($flag == 3)
                    Yii::$app->session->setFlash('status','16');
                else if($flag == 4)
                    Yii::$app->session->setFlash('status','18');
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page').'/index?CoursesSearch[college]='.$package->college]);
    }

    public function actionConfirm_package_from_college()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                if($package->college == Yii::$app->user->identity->college)
                {
                    $package->status = '2';
                    if($package->save())
                        Yii::$app->session->setFlash('status','15');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page').'/index?CoursesSearch[college]='.$package->college]);
    }

    public function actionBack_package()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                if($package->status != '4')
                {
                    $package->load(Yii::$app->request->post());
                    if(Yii::$app->user->identity->role == 'user')
                        $package->status = '4';
                    else
                        $package->status = '8';
                    if($package->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page')]);
    }

    public function actionBack_package_from_college()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                if($package->status != '8')
                {
                    $package->load(Yii::$app->request->post());
                    $package->status = '8';
                    if($package->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page')]);
    }

    public function actionReject_package()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                if($package->status != '5')
                {
                    $package->load(Yii::$app->request->post());
                    if(Yii::$app->user->identity->role == 'user')
                        $package->status = '5';
                    else
                        $package->status = '9';
                    if($package->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page')]);
    }

    public function actionReject_package_from_college()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                if($package->status != '9')
                {
                    $package->load(Yii::$app->request->post());
                    $package->status = '9';
                    if($package->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page')]);
    }

    public function actionSend_course_to_admin()
    {
        if(Yii::$app->request->isPost)
        {
            $package = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($package != null)
            {
                $nullLessons = true;
                if($package->lessons != null)
                    if(count($package->lessons) > 0)
                        $nullLessons = false;
                if(!$nullLessons)
                {
                    if($package->status == '4')
                        $package->modified = true;
                    else
                    {
                        if(Yii::$app->user->identity->role == 'broker')
                            $package->status = '7';
                        else
                            $package->status = '2';
                    }

                    if($package->save())
                        Yii::$app->session->setFlash('status','6');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
                else
                    Yii::$app->session->setFlash('status','17');
            }
        }
        return $this->redirect(['../'.Yii::$app->request->post('page')]);
    }

    public function statistics($type, $status)
    {
        $user = Yii::$app->user->identity;
        if($user->role == 'user')
        {
            if($status == '-1')
                return Courses::find()->where(['type' => $type])->count();
            else if($status == '-2')
                return Teachers::find()->count();
            else if($status == '-3')
                return Colleges::find()->count();
            else if($status == '-5')
                return Brokers::find()->count();
            else
                return Courses::find()->where(['type' => $type])->andWhere(['status' => $status])->count();
        }
        else if($user->role == 'emp')
        {
            if($status == '-1')
                return Courses::find()->where(['type' => $type])->andWhere(['college' => $user->college])->count();
            else if($status == '-2')
                return Teachers::find()->where(['like','colleges',$user->college])->count();
            else if($status == '-3')
                return 1;
            else if($status == '-5')
                return Brokers::find()->where(['like','colleges',$user->college])->count();
            else
                return Courses::find()->where(['type' => $type])->andWhere(['status' => $status])->andWhere(['college' => $user->college])->count();
        }
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }

    public function lesson_sessions($courseId, $lessonId)
    {
        return CourseSessions::find()->where(['course_id' => $courseId])->andWhere(['lesson_id' => $lessonId])->one();
    }

    public function get_teacher_id($username)
    {
        return Teachers::find()->where(['mobile' => $username])->one();
    }

    public function digit2word($num)
    {
        $num        = (string)$num;
        $one = array('','یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه');
        $ten = array('','','بیست','سی','چهل','پنجاه','شصت','هفتاد','هشتاد','نود',);
        $hundred = array('','یکصد','دویست','سیصد','چهارصد','پانصد','ششصد','هفتصد','هشتصد','نهصد',);
        $categories = array('','هزار','میلیون','میلیارد','بیلیون','بیلیارد','تریلیون','تریلیارد','کوآدریلیون',);
        $exceptions = array('ده','یازده','دوازده','سیزده','چهارده','پانزده','شانزده','هفده','هجده','نوزده',);
        $out = '';
        $j   = 0;
        $cnt = strlen($num);
        if($cnt==1)
            return $one[$num];
        for($i=--$cnt;$i>=0;$i-=3){
            $add = '';
            $i1 = $num[$i];
            $i2 = isset($num[$i-1]) ? $num[$i-1] : '';
            $i3 = isset($num[$i-2]) ? $num[$i-2] : '';
            if(!empty($i3))
                $add .= $hundred[$i3].' و ';
            if($i2>1)
                $add .= $ten[$i2].' و '.$one[$i1].' ';
            elseif($i2==1)
                $add .= $exceptions[$i1].' ';
            else
                $add .= $one[$i1].' ';
            if($add!=' ')
                $add .= $categories[$j++].' و ';
            else
                $j++;
            $out = $add.$out;
        }
        return mb_substr($out,0,-4);
    }

    public function fa2en($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    public function actionCreate_natural()
    {
        if (Yii::$app->request->isPost)
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
                    $access->access = array("lessons","courses","packages","test-maker","upload-center");
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

    public function actionTest()
    {
        $installments = Installments::find()->all();
        foreach ($installments as $item)
        {
           if($item->college == null)
           {
               $course = Courses::findOne($item->course_id);
               if($course != null)
               {
                   $item->college = $course->college;
                   $item->save();
               }
           }
        }
        exit;
        $installments = Installments::find()->all();
        $totalAmount = 0;
        $collegeAmount = 0;
        $brokerAmount = 0;
        $blch = 0;
        foreach ($installments as $installment)
        {
            if($installment->maturities != null)
            {
                $order = Orders::findOne($installment->order_id);
                if($order != null)
                {
                    $collegeShare = 100;
                    $brokerShare = 0;
                    if($order->shares != null)
                    {
                        if(array_key_exists('broker_contract_percent', $order->shares[0]))
                        {
                            $brokerShare = $order->shares[0]['broker_contract_percent'];
                            $collegeShare -= $brokerShare;
                        }
                    }
                    foreach ($installment->maturities as $maturity)
                    {
                        if($maturity['status'] == '1')
                        {
                            $totalAmount += $maturity['amount'];
                            $c = $maturity['amount'] * ($collegeShare / 100);
                            $b = $maturity['amount'] - $c;
                            $collegeAmount += $c;
                            $brokerAmount += $b;
                        }
                    }
                }
                else
                {
                    foreach ($installment->maturities as $maturity)
                    {
                        if($maturity['status'] == '1')
                        {
                            $totalAmount += $maturity['amount'];
                        }
                    }
                }
            }
            else
                $blch ++;
        }
        echo 'محموع اقساط پرداخت شده: '.number_format($totalAmount).'<br>';
        echo 'سهم دانشکده ها از مجموع اقساط: '.number_format($collegeAmount).'<br>';
        echo 'سهم کارگزاران از مجموع اقساط: '.number_format($brokerAmount).'<br>';
        $orders = Orders::find()->where(['status' => '1'])->all();
        $totalAmount = 0;
        $collegeShare = 0;
        $brokerShare = 0;
        foreach ($orders as $order)
        {
            if($order->shares != null)
            {
                $totalAmount += $order->amount;
                $collegeShare += $order->shares[0]['college_share'];
                if(array_key_exists('broker_share', $order->shares[0]))
                    $brokerShare += $order->shares[0]['broker_share'];
            }
        }
        echo 'مجموع دریافتی ها:'.number_format($totalAmount).'<br>';
        echo 'سهم دانشکده ها از مجموع پرداختی ها:'.number_format($collegeShare).'<br>';
        echo 'سهم کارگزاران از مجموع پرداختی ها:'.number_format($brokerShare).'<br>';

    }

    function generateRandomCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 6; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    public function actionTest1()
    {
        $orders = Orders::find()->where(['status' => '1'])->all();
        $totalAmount = 0;
        $collegeShare = 0;
        $brokerShare = 0;
        foreach ($orders as $order)
        {
            if($order->shares != null)
            {
                $totalAmount += $order->amount;
                $collegeShare += $order->shares[0]['college_share'];
                if(array_key_exists('broker_share', $order->shares[0]))
                    $brokerShare += $order->shares[0]['broker_share'];
            }
        }
        echo 'مجموع دریافتی ها:'.number_format($totalAmount).'<br>';
        echo 'سهم دانشکده ها از مجموع پرداختی ها:'.number_format($collegeShare).'<br>';
        echo 'سهم کارگزاران از مجموع پرداختی ها:'.number_format($brokerShare).'<br>';

    }

    public function actionTest2()
    {
        $orders = Orders::find()->where(['shares.broker' => '65a515cbf53a75071f0648f2'])->andWhere(['status' => '2'])->all();
        $amount = 0;
        foreach ($orders as $order)
            $amount += $order->amount;
        echo number_format($amount);
    }

    public function actionFullReport()
    {
        $installments = Installments::find()->all();
        $totalAmount = 0;
        $collegeAmount = 0;
        $brokerAmount = 0;
        $blch = 0;
        foreach ($installments as $installment)
        {
            if($installment->maturities != null)
            {
                $order = Orders::findOne($installment->order_id);
                if($order != null)
                {
                    $collegeShare = 100;
                    $brokerShare = 0;
                    if($order->shares != null)
                    {
                        if(array_key_exists('broker_contract_percent', $order->shares[0]))
                        {
                            $brokerShare = $order->shares[0]['broker_contract_percent'];
                            if(is_numeric($brokerShare))
                                $collegeShare -= $brokerShare;

                        }
                    }
                    foreach ($installment->maturities as $maturity)
                    {
//                        if($maturity['deadline'] >= '14040401' && $maturity['deadline'] <= '14040631')
                        if($maturity['status'] == '1')
                        {
                            $totalAmount += $maturity['amount'];
                            $c = $maturity['amount'] * ($collegeShare / 100);
                            $b = $maturity['amount'] - $c;
                            $collegeAmount += $c;
                            $brokerAmount += $b;
                        }
                    }
                }
                else
                {
                    foreach ($installment->maturities as $maturity)
                    {
                        if($maturity['status'] == '1')
                        {
                            $totalAmount += $maturity['amount'];
                        }
                    }
                }
            }
            else
                $blch ++;
        }
        $totalInstallment = $totalAmount;
        $collegeInstallment = $collegeAmount;
        $brokerInstallment = $brokerAmount;


        $currentYear = 1404; // سال جاری
        $startJalali = $currentYear * 10000 + 101; // 14030101
        $endJalali = $currentYear * 10000 + 631;   // 14030631
        // تبدیل به timestamp

        $startTimestamp = $this->jalaliToTimestamp($currentYear * 10000 + 101);
        $endTimestamp = $this->jalaliToTimestamp($currentYear * 10000 + 631);
        $d1 = '1404-04-01';
        $d2 = '1404-06-29';
        $d1 = jalali_to_gregorian(explode('-',$d1)[0],explode('-',$d1)[1],explode('-',$d1)[2]);
        $date1 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d1[0].'-'.$d1[1].'-'.$d1['2'])).'0000000000000000');
        $d2 = jalali_to_gregorian(explode('-',$d2)[0],explode('-',$d2)[1],explode('-',$d2)[2]);
        $date2 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d2[0].'-'.$d2[1].'-'.$d2['2'])).'0000000000000000');
//        $orders = Orders::find()->where(['status' => '1'])
//            ->andWhere(['>=', '_id', new \MongoDB\BSON\UTCDateTime($startTimestamp * 1000)])
//            ->andWhere(['<=', '_id', new \MongoDB\BSON\UTCDateTime($endTimestamp * 1000)])
//            ->all();
//        $orders = Orders::find()->where(['status' => '1'])->andWhere(['>=', '_id', $date1])->andWhere(['<=', '_id', $date2])->all();
        $orders = Orders::find()->where(['status' => '1'])->andWhere(['<>','payment_info.order_id','wallet'])->all();
        $totalAmount = 0;
        $collegeShare = 0;
        $brokerShare = 0;
        foreach ($orders as $order)
        {
            if($order->shares != null)
            {
                if($order->payments != null)
                {
                    $myAmount = 0;
                    foreach ($order->payments as $payment)
                    {
                        if(array_key_exists('status', $payment))
                            if($payment['status'] == '1')
                                    $myAmount += $payment['amount'];
                    }
                    $totalAmount += $myAmount;
                    if($order->shares != null)
                    {
                        if(array_key_exists('broker_contract_percent', $order->shares[0]))
                        {
                            $bs = $order->shares[0]['broker_contract_percent'];
                            $us = 100 - $bs;
                            $bsAmount = $myAmount * ($bs / 100);
                            $usAmount = $myAmount * ($us / 100);
                            $collegeShare += $usAmount;
                            $brokerShare += $bsAmount;
                        }
                    }
                }
                else if($order->prepayment_settlement != null || $order->settlement_payment != null)
                {
                    $myAmount = $order->amount;
                    if($order->prepayment_settlement != null)
                        if($order->prepayment_settlement['status'] == '1')
                            $myAmount += $order->prepayment_settlement['amount'];
                    if($order->settlement_payment != null)
                        if($order->settlement_payment['status'] == '1')
                            $myAmount += $order->settlement_payment['amount'];
                    $totalAmount += $myAmount;
                    if($order->shares != null)
                    {
                        if(array_key_exists('broker_contract_percent', $order->shares[0]))
                        {
                            $bs = $order->shares[0]['broker_contract_percent'];
                            $us = 100 - $bs;
                            $bsAmount = $myAmount * ($bs / 100);
                            $usAmount = $myAmount * ($us / 100);
                            $collegeShare += $usAmount;
                            $brokerShare += $bsAmount;
                        }
                    }
                }
                else
                {
                    $totalAmount += $order->amount;
                    $collegeShare += $order->shares[0]['college_share'];
                    if(array_key_exists('broker_share', $order->shares[0]))
                        $brokerShare += $order->shares[0]['broker_share'];
                }
            }
        }
        $finalAmount = $totalAmount;
        $finalCollege = $collegeShare;
        $finalBroker = $brokerShare;

        $finalWallet = 0;
        $wallTransactions = WalletTransactions::find()->where(['status' => '1'])->andWhere(['type' => ['1','2','4','5']])->all();
        if($wallTransactions != null)
        {
            foreach ($wallTransactions as $wallTransaction)
                $finalWallet += $wallTransaction->amount;
        }

        $finalCoursesFinancial = 0;
        $coursesFinancial = CoursesFinancial::find()->where(['payment_info.status' => '2'])->all();
        if($coursesFinancial != null)
        {
            foreach ($coursesFinancial as $value)
                $finalCoursesFinancial += $value->amount;
        }

        return $this->render('full-report',[
            'totalInstallment' => $totalInstallment,
            'collegeInstallment' => $collegeInstallment,
            'brokerAmount' => $brokerAmount,
            'finalAmount' => $finalAmount,
            'finalCollege' => $finalCollege,
            'finalBroker' => $finalBroker,
            'finalWallet' => $finalWallet,
            'finalCoursesFinancial' => $finalCoursesFinancial,
        ]);

    }

    public function actionReport()
    {
        $totalOrderCollegeShare = null;
        $totalOrderBrokerShare = null;
        $totalOrder = null;
        $totalINSCollegeShare = null;
        $totalINSBrokerShare = null;
        $totalInstallment = null;
        $totalWalletTRX = null;
        $totalFiCourse = null;
        $totalIncome = null;
        $start_date = null;
        $end_date = null;
        $college = null;
        if(isset($_POST['start_date']) && isset($_POST['end_date']))
        {
            $token = '68cbf10e4072ce76924';
            $start_date = str_replace('-','/',Yii::$app->request->post('start_date'));
            $end_date = str_replace('-','/',Yii::$app->request->post('end_date'));
            if(isset($_POST['college']))
                $college = Yii::$app->request->post('college');
            $adminRole = Admin::find()->where(['role' => 'user'])->one();
            if($adminRole != null)
            {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => Yii::getAlias('@baseUrl').'/orders/total-excel-report',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>'{
                            "start_date": "'.(string) $start_date.'",
                            "end_date": "'.(string) $end_date.'",
                            "college": "'.$college.'",
                            "auth_token": "'.$token.'"
                        }',
                    CURLOPT_HTTPHEADER => array(
                        '_id: '.(string) $adminRole->_id,
                        'Content-Type: application/json'
                    ),
                ));
                $response = curl_exec($curl);
                $response = json_decode($response);
                $totalOrderCollegeShare = $response->totalOrderCollegeShare;
                $totalOrderBrokerShare = $response->totalOrderBrokerShare;
                $totalOrder = $response->totalOrder;
                $totalINSCollegeShare =$response->totalINSCollegeShare;
                $totalINSBrokerShare = $response->totalINSBrokerShare;
                $totalInstallment = $response->totalInstallment;
                $totalWalletTRX = $response->totalWalletTRX;
                $totalFiCourse = $response->totalFiCourse;
                $totalIncome = $response->totalIncome;
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
                }
                curl_close($curl);
            }
        }
        $colleges = Colleges::find()->orderBy(['_id'=>SORT_DESC])->all();
        return $this->render('report',[
            'colleges' => $colleges,
            'totalOrderCollegeShare' => $totalOrderCollegeShare,
            'totalOrderBrokerShare' => $totalOrderBrokerShare,
            'totalOrder' => $totalOrder,
            'totalINSCollegeShare' => $totalINSCollegeShare,
            'totalINSBrokerShare' => $totalINSBrokerShare,
            'totalInstallment' => $totalInstallment,
            'totalWalletTRX' => $totalWalletTRX,
            'totalFiCourse' => $totalFiCourse,
            'totalIncome' => $totalIncome,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'college' => $college,
        ]);
    }


    public function actionReport1()
    {
        // همیشه این متغیرها را تعریف کن
        $viewData = [
            'colleges' => Colleges::find()->orderBy(['_id' => SORT_DESC])->all(),
            'totalOrderCollegeShare' => 0,
            'totalOrderBrokerShare' => 0,
            'totalOrder' => 0,
            'totalINSCollegeShare' => 0,
            'totalINSBrokerShare' => 0,
            'totalInstallment' => 0,
            'totalWalletTRX' => 0,
            'totalFiCourse' => 0,
            'totalIncome' => 0,
            'start_date' => Yii::$app->request->get('start_date', ''),
            'end_date' => Yii::$app->request->get('end_date', ''),
            'college' => Yii::$app->request->get('college', ''),
            'hasFile' => false,
            'fileName' => '',
            'showDownloadMessage' => false
        ];

        // اگر از GET آمده (بعد از دانلود)
        if (Yii::$app->request->isGet && Yii::$app->request->get('downloaded')) {
            $viewData['showDownloadMessage'] = true;

            // بازیابی داده‌ها از session
            if (Yii::$app->session->has('report_data_after_download')) {
                $sessionData = Yii::$app->session->get('report_data_after_download');
                foreach ($sessionData as $key => $value) {
                    if (array_key_exists($key, $viewData)) {
                        $viewData[$key] = $value;
                    }
                }
                Yii::$app->session->remove('report_data_after_download');
            }
        }

        // اگر فرم ارسال شده باشد
        if (Yii::$app->request->isPost) {
            $postData = Yii::$app->request->post();
            $viewData['start_date'] = $postData['start_date'] ?? '';
            $viewData['end_date'] = $postData['end_date'] ?? '';
            $viewData['college'] = $postData['college'] ?? '';

            $apiResult = $this->callApiForReport(
                $viewData['start_date'],
                $viewData['end_date'],
                $viewData['college']
            );

            if ($apiResult['success'] && isset($apiResult['data'])) {
                $response = $apiResult['data'];

                // ذخیره داده‌ها
                $viewData['totalOrderCollegeShare'] = $response->totalOrderCollegeShare ?? 0;
                $viewData['totalOrderBrokerShare'] = $response->totalOrderBrokerShare ?? 0;
                $viewData['totalOrder'] = $response->totalOrder ?? 0;
                $viewData['totalINSCollegeShare'] = $response->totalINSCollegeShare ?? 0;
                $viewData['totalINSBrokerShare'] = $response->totalINSBrokerShare ?? 0;
                $viewData['totalInstallment'] = $response->totalInstallment ?? 0;
                $viewData['totalWalletTRX'] = $response->totalWalletTRX ?? 0;
                $viewData['totalFiCourse'] = $response->totalFiCourse ?? 0;
                $viewData['totalIncome'] = $response->totalIncome ?? 0;

                // اگر فایل موجود است
                if (isset($response->filename) && !empty($response->filename)) {
                    $fileName = $response->filename . '.xlsx';
                    $filePath = Yii::getAlias('@webroot') . '/orders_reports/' . $fileName;

                    if (file_exists($filePath)) {
                        $viewData['hasFile'] = true;
                        $viewData['fileName'] = $fileName;
                        $viewData['downloadUrl'] = \yii\helpers\Url::to([
                            'download-report',
                            'file' => $fileName
                        ]);

                        // 3. ریدایرکت کن به همین اکشن با پارامتر دانلود
                        return $this->redirect([
                            'report',
                            'start_date' => $viewData['start_date'],
                            'end_date' => $viewData['end_date'],
                            'college' => $viewData['college'],
                            'download' => '1' // فلگ دانلود
                        ]);
                    }
                }
            } else {
                Yii::$app->session->setFlash('error', $apiResult['error'] ?? 'خطا در دریافت داده‌ها');
            }
        }

        // بررسی اگر باید فایل دانلود شود
        if (Yii::$app->request->get('download') == '1' &&
            Yii::$app->session->has('force_download_file')) {

            $fileInfo = Yii::$app->session->get('force_download_file');
            Yii::$app->session->remove('force_download_file');

            if (file_exists($fileInfo['path'])) {
                // دانلود فایل
                Yii::$app->response->sendFile($fileInfo['path'], $fileInfo['name'], [
                    'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ]);

                // بعد از دانلود، ریدایرکت به صفحه با داده‌ها
                return $this->redirect([
                    'report',
                    'start_date' => $viewData['start_date'],
                    'end_date' => $viewData['end_date'],
                    'college' => $viewData['college'],
                    'downloaded' => '1' // نشان‌دهنده دانلود انجام شده
                ]);
            }
        }

        return $this->render('report', $viewData);
    }

    private function callApiForReport($startDate, $endDate, $college)
    {
        $token = '68cbf10e4072ce76924';
        $adminRole = Admin::find()->where(['role' => 'user'])->one();

        if (!$adminRole) {
            return ['success' => false, 'error' => 'مدیر یافت نشد'];
        }

        $curl = curl_init();
        $postData = json_encode([
            "start_date" => str_replace('-', '/', $startDate),
            "end_date" => str_replace('-', '/', $endDate),
            "college" => $college,
            "auth_token" => $token
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => Yii::getAlias('@baseUrl') . '/orders/total-excel-report',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                '_id: ' . (string) $adminRole->_id,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            return ['success' => false, 'error' => 'خطا در ارتباط با سرور'];
        }

        $data = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'پاسخ سرور نامعتبر است'];
        }

        return ['success' => true, 'data' => $data];
    }

    public function jalaliToTimestamp($jalaliDate) {
        $year = floor($jalaliDate / 10000);
        $month = floor(($jalaliDate % 10000) / 100);
        $day = $jalaliDate % 100;

        // تبدیل ساده (برای دقت بیشتر از کتابخانه تبدیل تاریخ استفاده کنید)
        $gYear = $year - 621;

        // ایجاد تاریخ میلادی
        $gregorianDate = "$gYear-$month-$day";
        return strtotime($gregorianDate);
    }

    public function generateObjectIdFromTimestamp($timestamp) {
        $hexTimestamp = dechex($timestamp);
        $hexPadding = str_pad($hexTimestamp, 8, '0', STR_PAD_LEFT);
        return $hexPadding . '0000000000000000'; // بقیه بایت‌های ObjectId را صفر می‌کنیم
    }

    public function actionCoursesReport()
    {
        $startTimestamp = strtotime('2024-03-20 00:00:00');
        $endTimestamp = strtotime('2025-03-20 23:59:59');

        $startObjectId = new ObjectId($this->generateObjectIdFromTimestamp($startTimestamp));
        $endObjectId = new ObjectId($this->generateObjectIdFromTimestamp($endTimestamp));
        $courses = Courses::find()
            ->where(['status' => ['1', '6']])
            ->andWhere(['between', '_id', $startObjectId, $endObjectId])
            ->orderBy(['_id' => SORT_DESC])
            ->all();
        $columns = [];
        array_push($columns, ['attribute' => 'عنوان دوره']);
        array_push($columns, ['attribute' => 'دانشکده']);
        array_push($columns, ['attribute' => 'تعداد دانشپذیر']);
        $rows = [];
        foreach ($courses as $item)
        {
            $users = Users::find()->where(['courses._id' => (string) $item->_id])->all();
            $userCourses = 0;
            if($users != null)
                $userCourses = count($users);
            $college = Colleges::findOne($item->college);
            $collegeTitle = '-';
            if($college != null)
                $collegeTitle = $college->title;
            $currentRow = ["عنوان دوره"  => $item->title['main_fa'], "دانشکده"  => $collegeTitle, "تعداد دانشپذیر"  => $userCourses];
            array_push($rows, $currentRow);
        }
        // return var_dump($columns, $rows);
        $exporter = (new Spreadsheet([
            'title' => 'Monitors',
            'dataProvider' => new ArrayDataProvider([
                'allModels' => $rows,
            ]),
            'columns' => $columns,
        ]))->render(); // call `render()` to create a single worksheet
        $exporter->save('./newfile.xlsx');
        $file_name = 'coursesReport'. jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionFullReport1()
    {
        $installments = Installments::find()->all();
        $totalAmount = 0;
        $collegeAmount = 0;
        $brokerAmount = 0;
        $blch = 0;
        foreach ($installments as $installment)
        {
            if($installment->maturities != null)
            {
                $order = Orders::findOne($installment->order_id);
                if($order != null)
                {
                    $objectId = new ObjectId($order->_id);
                    $timestamp = $objectId->getTimestamp();
                    if(jdate('Ymd', $timestamp) >= '14040323' && jdate('Ymd', $timestamp) <= 14040404)
                    {
                        $collegeShare = 100;
                        $brokerShare = 0;
                        if($order->shares != null)
                        {
                            if(array_key_exists('broker_contract_percent', $order->shares[0]))
                            {
                                $brokerShare = $order->shares[0]['broker_contract_percent'];
                                $collegeShare -= $brokerShare;
                            }
                        }
                        foreach ($installment->maturities as $maturity)
                        {
                            if($maturity['status'] == '1')
                            {
                                $totalAmount += $maturity['amount'];
                                $c = $maturity['amount'] * ($collegeShare / 100);
                                $b = $maturity['amount'] - $c;
                                $collegeAmount += $c;
                                $brokerAmount += $b;
                            }
                        }
                    }
                }
                else
                {
                    foreach ($installment->maturities as $maturity)
                    {
                        if($maturity['status'] == '1')
                        {
                            $totalAmount += $maturity['amount'];
                        }
                    }
                }
            }
            else
                $blch ++;
        }
//        echo 'محموع اقساط پرداخت شده: '.number_format($totalAmount).'<br>';
//        echo 'سهم دانشکده ها از مجموع اقساط: '.number_format($collegeAmount).'<br>';
//        echo 'سهم کارگزاران از مجموع اقساط: '.number_format($brokerAmount).'<br>';
        $totalInstallment = $totalAmount;
        $collegeInstallment = $collegeAmount;
        $brokerInstallment = $brokerAmount;
        $orders = Orders::find()->where(['status' => '1'])->all();
        $totalAmount = 0;
        $collegeShare = 0;
        $brokerShare = 0;
        foreach ($orders as $order)
        {
            $objectId = new ObjectId($order->_id);
            $timestamp = $objectId->getTimestamp();
            if(jdate('Ymd', $timestamp) >= '14040323' && jdate('Ymd', $timestamp) <= 14040404)
            {
                if($order->shares != null)
                {
                    $totalAmount += $order->amount;
                    $collegeShare += $order->shares[0]['college_share'];
                    if(array_key_exists('broker_share', $order->shares[0]))
                        $brokerShare += $order->shares[0]['broker_share'];
                }
            }
        }
//        echo 'مجموع دریافتی ها:'.number_format($totalAmount).'<br>';
//        echo 'سهم دانشکده ها از مجموع پرداختی ها:'.number_format($collegeShare).'<br>';
//        echo 'سهم کارگزاران از مجموع پرداختی ها:'.number_format($brokerShare).'<br>';
        $finalAmount = $totalAmount;
        $finalCollege = $collegeShare;
        $finalBroker = $brokerShare;

        return $this->render('full-report',[
            'totalInstallment' => $totalInstallment,
            'collegeInstallment' => $collegeInstallment,
            'brokerAmount' => $brokerAmount,
            'finalAmount' => $finalAmount,
            'finalCollege' => $finalCollege,
            'finalBroker' => $finalBroker,
        ]);

    }

    public function compare_date($date1, $date2)
    {
        list($year1, $month1, $day1) = explode('/', $date1);
        list($year2, $month2, $day2) = explode('/', $date2);
        $g1 = jalali_to_gregorian($year1, $month1, $day1);
        $g2 = jalali_to_gregorian($year2, $month2, $day2);
        $timestamp1 = mktime(0, 0, 0, $g1[1], $g1[2], $g1[0]);
        $timestamp2 = mktime(0, 0, 0, $g2[1], $g2[2], $g2[0]);
        if ($timestamp1 < $timestamp2) {
            echo "تاریخ دوم بزرگتر است";
        } elseif ($timestamp1 > $timestamp2) {
            echo "تاریخ اول بزرگتر است";
        } else {
            echo "تاریخ‌ها برابر هستند";
        }
    }

    function is_date1_after_6_months($date1, $date2) {
        $date2_plus_6_months = $this->add_6_months_to_jalali($date2);
        $result = compareJalaliDates($date1, $date2_plus_6_months);
        return ($result === 1);
    }

    public function add_6_months_to_jalali($jalaliDate) {

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $jalaliDate = str_replace($persian, $english, $jalaliDate);

        list($year, $month, $day) = explode('/', $jalaliDate);

        $month += 4;
        if ($month > 12) {
            $year += floor($month / 12);
            $month = $month % 12;
            if ($month === 0) {
                $month = 12;
                $year--;
            }
        }

        $maxDays = [
            1 => 31, 2 => 31, 3 => 31, 4 => 31, 5 => 31, 6 => 31,
            7 => 30, 8 => 30, 9 => 30, 10 => 30, 11 => 30, 12 => 29
        ];

        if ($month == 12) {
            $leap = ($year % 33 % 4 == 1) ? 30 : 29;
            $maxDays[12] = $leap;
        }

        if ($day > $maxDays[$month]) {
            $day = $maxDays[$month];
        }

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    public function isPackageDateValid($contractDate, $packageDate) {
        // تبدیل تاریخ قرارداد به اجزای سال، ماه و روز
        list($cYear, $cMonth, $cDay) = explode('/', $contractDate);
        $cYear = (int)$cYear;
        $cMonth = (int)$cMonth;
        $cDay = (int)$cDay;

        // اضافه کردن ۳ ماه به تاریخ قرارداد
        $newMonth = $cMonth + 3;
        $newYear = $cYear;

        // تنظیم سال در صورت عبور از ۱۲ ماه
        if ($newMonth > 12) {
            $newYear += floor($newMonth / 12);
            $newMonth = $newMonth % 12;
            if ($newMonth === 0) {
                $newMonth = 12;
                $newYear--;
            }
        }

        // محاسبه آخرین روز ماه جدید
        $lastDay = $this->j_days_in_month($newMonth, $newYear);
        $newDay = min($cDay, $lastDay);

        // ایجاد تاریخ جدید بعد از ۳ ماه
        $maxPackageDate = sprintf('%04d/%02d/%02d', $newYear, $newMonth, $newDay);

        // مقایسه تاریخ پکیج با حداکثر تاریخ مجاز
        return (strtotime($packageDate) <= strtotime($maxPackageDate));
    }

    public function isContractDateValid($date)
    {
        $dateParts = explode('-', $date);

        // بررسی صحت فرمت تاریخ
        if (count($dateParts) !== 3) {
            return "فرمت تاریخ نامعتبر است. باید به صورت YYYY-MM-DD باشد";
        }

        // تبدیل رشته‌ها به اعداد
        $year = (int)$dateParts[0];
        $month = (int)$dateParts[1];
        $day = (int)$dateParts[2];

        // بررسی اعتبار تاریخ
        if (!isValidPersianDate($year, $month, $day)) {
            return "تاریخ وارد شده معتبر نیست";
        }

        // تبدیل تاریخ شمسی به میلادی
        $gregorianDate = jd_to_gregorian(
            persian_to_jd($year, $month, $day)
        );

        // تبدیل به فرمت استاندارد
        list($gMonth, $gDay, $gYear) = $gregorianDate;
        $timestamp = strtotime("$gYear-$gMonth-$gDay");

        // تاریخ امروز
        $today = time();

        // مقایسه با تاریخ امروز
        if ($timestamp < $today)
            return false;
        else
            return true;
    }

    public function isValidPersianDate($year, $month, $day)
    {
        if ($month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        // حداکثر روزهای هر ماه
        $maxDays = 31;
        if ($month <= 6) {
            $maxDays = 31;
        } elseif ($month <= 11) {
            $maxDays = 30;
        } else {
            // ماه اسفند - بررسی سال کبیسه
            $maxDays = isLeapPersianYear($year) ? 30 : 29;
        }

        return $day <= $maxDays;
    }

// تابع کمکی برای بررسی کبیسه بودن سال شمسی
    public function isLeapPersianYear($year)
    {
        // الگوریتم تشخیص سال کبیسه شمسی
        $y = $year - 474;
        $leapYear = (($y % 2820) + 474 + 38) * 682;
        $leapYear = (int)($leapYear % 2816);
        return $leapYear < 682;
    }

    public function j_days_in_month($month, $year) {
        $days_in_month = [
            1 => 31,   // فروردین
            2 => 31,   // اردیبهشت
            3 => 31,   // خرداد
            4 => 31,   // تیر
            5 => 31,   // مرداد
            6 => 31,   // شهریور
            7 => 30,   // مهر
            8 => 30,   // آبان
            9 => 30,   // آذر
            10 => 30,  // دی
            11 => 30,  // بهمن
            12 => 29   // اسفند (پیش‌فرض)
        ];

        // بررسی سال کبیسه برای اسفند
        if ($month == 12) {
            $leap_years = [1, 5, 9, 13, 17, 22, 26, 30];
            $mod = $year % 33;
            if (in_array($mod, $leap_years)) {
                $days_in_month[12] = 30;
            }
        }

        return $days_in_month[$month];
    }

    public function courses_status($type)
    {
        if($type == '1')
            return Courses::find()->where(['type' => '1'])->andWhere(['status' => '7'])->andWhere(['college' => Yii::$app->user->identity->college])->count();
        else if($type == '2')
            return Courses::find()->where(['type' => ['2','3']])->andWhere(['status' => '7'])->andWhere(['college' => Yii::$app->user->identity->college])->count();
    }

}
