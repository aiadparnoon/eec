<?php

namespace frontend\controllers;

use DateInterval;
use DateTime;
use Yii;
use app\models\Courses;
use app\models\CoursesSearch;
use app\models\CoursesMembers;
use app\models\Colleges;
use app\models\Brokers;
use app\models\Teachers;
use app\models\Lessons;
use app\models\CoursesContents;
use app\models\CoursesContentsSearch;
use app\models\Generals;
use app\models\Users;
use app\models\Scores;
use app\models\Discounts;
use app\models\CoursesFinancial;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * CoursesController implements the CRUD actions for Courses model.
 */
class CoursesController extends Controller
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
                        'actions' => ['index','new', 'report','check-username', 'members_report', 'edit', 'brokers', 'brokers1', 'broker_contracts', 'capacity', 'course_date', 'my_brokers', 'edit-course','show_course_users','delete-course','delete_course','copy-course'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if ((array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user') || Yii::$app->user->identity->role == 'teacher')
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
     * Lists all Courses models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, '1');
        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
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
        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model) {
                return (string) $model->_id;
            }, 'title'),
            'myCollege' => $colleges,
            'brokers' => $brokers,
            'capacityType' => $this->resolveCreateCourseCapacityType()
        ]);
    }

    /**
     * گزینه‌های "نوع ظرفیت" برای Modal «ثبت دوره تک درس» (دوره‌های کوتاه‌مدت) -
     * دقیقاً همون منطق role/financial_info-based
     * PackagesController::resolveCreatePackageOptions() (دوره‌های میان‌مدت) اینجا
     * هم پیاده شده (طبق درخواست صریح ۲۰۲۶-۰۸-۲۸: «فیلد نوع ظرفیت مثل دوره‌های
     * میان‌مدت ۲ تا گزینه داشته باشه»). «نامحدود» دیگر برای دوره‌های جدید
     * قابل‌انتخاب نیست - رکوردهای قدیمی که از قبل این مقدار رو دارن دست
     * نمی‌خوره (نگاه کنید Courses::validateCapacityTypeNotDisabled()).
     *
     * توجه: بخش broker این متد عیناً همون کد resolveCreatePackageOptions() رو
     * تکرار می‌کنه - شامل همون $flag/$capacityFlag که در شاخه‌ی broker با هم
     * فرق دارن؛ این عیناً رفتار فعلی دوره‌های میان‌مدت هست و برای حفظ «قوانین
     * دقیقاً یکسان با میان‌مدت» تغییر داده نشده (تغییرش باعث می‌شد رفتار این
     * دو نوع دوره از هم متفاوت بشه).
     *
     * @return array|null
     */
    private function resolveCreateCourseCapacityType()
    {
        $capacityType = null;
        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
        {
            $capacityType = array(
                '2' => 'محدود',
                '3' => 'سازمانی'
            );
        }
        if (Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker')
        {
            $capacityFlag = 0;
            $collegeDetail = Colleges::findOne(Yii::$app->user->identity->college);
            if ($collegeDetail != null)
            {
                if (Yii::$app->user->identity->role == 'emp')
                {
                    if ($collegeDetail->financial_info == null)
                        $capacityFlag = 1;
                    else if ($collegeDetail->financial_info['id'] == '')
                        $capacityFlag = 1;
                }
                else if (Yii::$app->user->identity->role == 'broker')
                {
                    if ($collegeDetail->financial_info == null)
                        $capacityFlag = 1;
                    else if ($collegeDetail->financial_info['id'] == '')
                        $capacityFlag = 1;
                    else
                    {
                        $broker = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
                        if ($broker != null)
                        {
                            if ($broker->financial_info == null)
                                $flag = 1;
                            else if ($broker->financial_info['id'] == '')
                                $flag = 1;
                        }
                    }
                }
            }
            else
                $capacityFlag = 1;
            if ($capacityFlag == 0)
                $capacityType = array(
                    '2' => 'محدود',
                    '3' => 'سازمانی'
                );
            else
                $capacityType = array(
                    '3' => 'سازمانی'
                );
        }
        return $capacityType;
    }

    public function actionCheckUsername()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $username = Yii::$app->request->post('username');

        if (!$username) {
            return ['success' => false, 'message' => 'نام کاربری ارسال نشده است'];
        }
        $existingUser = Users::find()
            ->where(['username' => $username])
            ->asArray()
            ->one();

        if ($existingUser) {
            return [
                'success' => true,
                'exists' => true,
                'userInfo' => [
                    'first_name' => $existingUser['first_name'] ?? '',
                    'last_name' => $existingUser['last_name'] ?? ''
                ]
            ];
        }

        return [
            'success' => true,
            'exists' => false
        ];
    }

    public function actionEditCourse($_id)
    {
        if (isset($_GET['_id']))
        {
            $courseDetail = Courses::findOne($_GET['_id']);
            if ($courseDetail != null)
            {
                if($this->allow($courseDetail->college))
                {
                    if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                        $colleges = Colleges::find()->all();
                    else
                        $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
                    $searchModel = new CoursesMembers();
                    $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $courseDetail->_id);
                    $dataProvider->pagination->pageSize = 50;
                    $discounts = Discounts::find()->where(['course_id' => (string) $courseDetail->_id])->all();
                    $courseFinancial = CoursesFinancial::find()->where(['course_id' => (string) $courseDetail->_id])->andWhere(['payment_info.status' => '2'])->orderBy(['_id'=>SORT_DESC])->all();
                    $allowEdit = true;
                    if(($courseDetail->status == '1' || $courseDetail->status == '6') && Yii::$app->user->identity->role != 'user' && Yii::$app->user->identity->role != 'cnt')
                        $allowEdit = false;
                    return $this->render('edit-course', [
                        'colleges' => ArrayHelper::map($colleges, function ($model) {
                            return (string) $model->_id;
                        }, 'title'),
                        'courseDetail' => $courseDetail,
                        'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,
                        'discounts' => $discounts,
                        'allowEdit' => $allowEdit,
                        'courseFinancial' => $courseFinancial
                    ]);
                }
            }
        }
        return $this->redirect(['../courses']);
    }

    public function actionCopyCourse($_id)
    {
        if (isset($_GET['_id']))
        {
            $course = Courses::findOne($_GET['_id']);
            if ($course != null)
            {
                if($this->allow($course->college))
                {
                    $model = new Courses();
                    $model->setAttributes($course->attributes);
                    if(Yii::$app->user->identity->role == 'broker')
                        $model->status = '7';
                    else
                        $model->status = '2';
                    $model->license_code = null;
                    $model->rejection_reason = null;
                    $model->mentors = null;
                    $model->other_teachers = null;
                    $model->adobe_status = null;
                    $model->preview_image = $course->preview_image;
//                $model->lessons = null;
                    $lessons = $model->lessons;
                    unset($lessons[0]['meeting']);
                    $model->lessons = $lessons;
                    $model->registrant = Yii::$app->user->identity->username;
                    $model->save();
                    return $this->redirect(['../courses/edit-course?_id='.(string) $model->_id]);
                }
            }
        }
        return $this->redirect(['../courses']);
    }

    public function actionManageCourse($_id)
    {
        if (isset($_GET['_id']))
        {
            $courseDetail = Courses::findOne($_GET['_id']);
            if ($courseDetail != null)
            {
                if($this->allow($courseDetail->college))
                {
                    $lessons = array();
                    $user = Yii::$app->user->identity;
                    if ($user->role == 'user' || $user->role == 'emp' || $user->role == 'brokers')
                        $lessons = $courseDetail->lessons;
                    else if ($user->role == 'teacher') {
                        $teacherDetail = Teachers::find()->where(['mobile' => $user->username])->one();
                        if ($teacherDetail != null) {
                            foreach ($courseDetail->lessons as $lesson)
                                if ($lesson['teachers'] == (string) $teacherDetail->_id)
                                    array_push($lessons, $lesson);
                        }
                    }
                    return $this->render('manage-course', [
                        'courseDetail' => $courseDetail,
                        'lessons' => $lessons
                    ]);
                }
            }
        }
        return $this->redirect(['../courses']);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost) {
            //            $find = Courses::find()->where(['mobile' => Yii::$app->request->post()['Teachers']['mobile']])->one();
            if (true) {
                $model = new Courses();
                // اصلاح ۲۰۲۶-۰۸-۲۸ (طبق سند بررسی دوره‌های کوتاه‌مدت، بخش ۲۰/۲۹): قبلاً این
                // مدل در سناریوی پیش‌فرض ذخیره می‌شد که هیچ قانون required/فرمتی روش اجرا
                // نمی‌شد (فقط 'safe'). حالا با تنظیم سناریوی مخصوص دوره کوتاه‌مدت، دقیقاً
                // همون فیلدهایی که در Modal هم required‌ان (دانشکده، قیمت، زمان، محل، نوع
                // دوره، مدت، ظرفیت، عنوان، و درسِ انتخاب‌شده) سمت سرور هم اعتبارسنجی می‌شن —
                // مستقل از اینکه فرانت‌اند چی فرستاده (بخش ۲۳ سند: UI Restriction ≠ Security).
                $model->scenario = Courses::SCENARIO_CREATE_COURSE;
                $model->load(Yii::$app->request->post());
                $model->type = '1';
                if (Yii::$app->user->identity->role == 'user' || $model->college == '663b1d28c9c6ce2e65073e22')
                    $model->status = '1';
                else
                    $model->status = '2';
                if(Yii::$app->user->identity->role == 'broker' || $model->college == '65afa2ea5136ec5b5b0c4064')
                    $model->status = '7';
                $model->show_in_site = true;
                $model->credit = '0';
                $model->registrant = Yii::$app->user->identity->username;
                if(isset($_POST['Courses']['broker']))
                    if(Yii::$app->request->post()['Courses']['broker']['_id'] == null || Yii::$app->request->post()['Courses']['broker']['_id'] == '')
                        $model->broker = null;
                if ($_FILES['Courses']['name']['contract_file'] != '')
                {
                    $file = UploadedFile::getInstance($model, 'contract_file');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/contract_files/' . $file_name);
                    if (UploadedFile::getInstance($model, 'contract_file') != null)
                        $model->contract_file = $file_name;
                }
                if ($model->save())
                {
                    $lesson = Lessons::findOne($model->lessons[0]['_id']);
                    if ($lesson != null)
                    {
                        $model->preview_image = $lesson->imagePreview;
                        // Calculating the member registration deadline
                        if(array_key_exists('date', $model->lessons[0]))
                        {
                            $startDate = $model->lessons[0]['date']['from'];
                            $endDate = $model->lessons[0]['date']['to'];

                            $start = DateTime::createFromFormat('Y-m-d', $startDate);
                            $end = DateTime::createFromFormat('Y-m-d', $endDate);

                            if ($start && $end && $end > $start) {
                                $interval = $start->diff($end);
                                $quarterDays = intval($interval->days / 4);

                                $deadline = clone $start;
                                $deadline->add(new DateInterval('P' . $quarterDays . 'D'));
                                $model->deadline_date = $deadline->format('Y-m-d');
                            } else {
                                $model->deadline_date = null;
                            }
                        }
                        // Calculating the member registration deadline
                        $model->save();
                    }
                   if(Yii::$app->user->identity->role == 'user' || $model->college == '663b1d28c9c6ce2e65073e22')
                   {
                       $lastLicense = Generals::find()->where(['type' => 'license_code'])->one();
                       if($lastLicense != null)
                       {
                           $college = Colleges::findOne($model->college);
                           $model->license_code = $college->prefix.'-'.(string) ($lastLicense->data + 1);
                           $lastLicense->updateCounters(['data' => 1]);
                           $lastLicense->save();
                       }
                   }
                    // Call AdobeConnect For Create Meetings Course
                    $adminRole = Admin::find()->where(['role' => 'user'])->one();
                    if($adminRole != null && ($model->content_type == '1' || $model->content_type == '2'))
                    {
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/create-meeting/'.(string) $model->_id,
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
                        $response = json_decode($response);
                        if(property_exists($response,'status'))
                        {
                            if($response->status == 'ok')
                                $model->adobe_status = '1';
                            else
                                $model->adobe_status = '0';
                        }
                        else
                            $model->adobe_status = '0';
                    }
                    // Call AdobeConnect For Create Meetings Course
                    if($model->discount_price == '')
                        $model->discount_price = $model->price;
                    $model->save();
                    if($model->adobe_status === '0')
                        Yii::$app->session->setFlash('status', '11');
                    else
                        Yii::$app->session->setFlash('status', '1');
                } else
                    Yii::$app->session->setFlash('status', '2');
            } else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $find = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if ($find != null)
            {
                $from = '';
                $to = '';
                $time = '';
                $meetings = null;
                $lessons = $find->lessons[0];
                $date = $find->lessons[0]['date'];
                if(array_key_exists('from', $lessons['date']))
                    $from = $find->lessons[0]['date']['from'];
                if(array_key_exists('to', $lessons['date']))
                    $to = $find->lessons[0]['date']['to'];
                if(array_key_exists('time', $lessons['date']))
                    $time = $find->lessons[0]['date']['time'];
                if(array_key_exists('meeting', $lessons))
                    $meetings = $lessons['meeting'];
                if(isset($_POST['Courses']['lessons'][0]['date']['time']))
                    $time = Yii::$app->request->post()['Courses']['lessons'][0]['date']['time'];
                $find->load(Yii::$app->request->post());
                // اصلاح ۲۰۲۶-۰۸-۲۸: مشابه actionNew()، سناریوی مخصوص ویرایش دوره کوتاه‌مدت
                // فعال می‌شه تا همون فیلدهای required فرم ویرایش سمت سرور هم چک بشن.
                $find->scenario = Courses::SCENARIO_EDIT_COURSE;
                if($find->status == '4')
                    $find->status = '2';
                if ($find->save())
                {
                    if(Yii::$app->user->identity->role != 'user' && Yii::$app->user->identity->role != 'cnt')
                    {
                        $date = $find->lessons[0]['date'];
                        $date['time'] = $time;
                        $date['from'] = $from;
                        $date['to'] = $to;
                        $lessons['meeting'] = $meetings;
                        $finalLessons = array();
                        array_push($finalLessons, $lessons);
                        $find->lessons = $finalLessons;
                        $find->save();
                    }
                    // Calculating the member registration deadline
                    if(array_key_exists('date', $find->lessons[0]))
                    {
                        $startDate = $find->lessons[0]['date']['from'];
                        $endDate = $find->lessons[0]['date']['to'];

                        $start = DateTime::createFromFormat('Y-m-d', $startDate);
                        $end = DateTime::createFromFormat('Y-m-d', $endDate);

                        if ($start && $end && $end > $start) {
                            $interval = $start->diff($end);
                            $quarterDays = intval($interval->days / 4);

                            $deadline = clone $start;
                            $deadline->add(new DateInterval('P' . $quarterDays . 'D'));
                            $find->deadline_date = $deadline->format('Y-m-d');
                        } else {
                            $find->deadline_date = null;
                        }
                        $find->save();
                    }
                    // Calculating the member registration deadline
                    $adminRole = Admin::find()->where(['role' => 'user'])->one();
                    if($adminRole != null)
                    {
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/create-meeting/'.(string) $find->_id.'?new-lesson='.$find->lessons[0]['_id'],
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
                    Yii::$app->session->setFlash('status', '4');
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(['../courses']);
    }

    public function actionBrokers($id)
    {
        $collegeBrokers = Brokers::find()->where(['college' => $id])->all();
        $collegeTeachers = Teachers::find()->where(['like', 'colleges', $id])->all();
        $collegeLessons = Lessons::find()->where(['college' => $id])->all();
        $brokers = null;
        $teachers = null;
        $lessons = null;
        if ($collegeBrokers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'broker[_id]')->dropDownList(
                ArrayHelper::map($collegeBrokers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->company_info['company_title'];
                }),
                [
                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                ]
            )->label(false);
            $brokers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeTeachers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[0][teachers]')->dropDownList(
                ArrayHelper::map($collegeTeachers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->first_name . ' ' . $model->last_name;
                }),
                [
                    'prompt' => 'لطفا مدرس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true
                ]
            )->label(false);
            $teachers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeLessons != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[0][_id]')->dropDownList(
                ArrayHelper::map($collegeLessons, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->title;
                }),
                [
                    'prompt' => 'لطفا درس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true
                ]
            )->label(false);
            $lessons = ob_get_contents();
            ob_end_clean();
        }
        $response = array(
            'brokers' => $brokers,
            'teachers' => $teachers,
            'lessons' => $lessons,
        );
        return json_encode($response);
    }

    public function actionBrokers1($id)
    {
        $hiddenArchive = array(
            false => 'خیر' ,
            true => 'بله' ,
        );
        $collegeBrokers = Brokers::find()->where(['college' => $id])->andWhere(['status' => '1'])->all();
        if (Yii::$app->user->identity->role == 'broker')
            $collegeBrokers = Brokers::find()->where(['college' => $id])->andWhere(['connector_info.mobile' => Yii::$app->user->identity->username])->andWhere(['status' => '1'])->all();
        $collegeTeachers = Teachers::find()->where(['like', 'colleges', $id])->all();
        $collegeLessons = Lessons::find()->where(['college' => $id])->all();
        $brokers = null;
        $teachers = null;
        $lessons = null;
        if ($collegeBrokers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'broker[_id]')->dropDownList(
                ArrayHelper::map($collegeBrokers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    $type = 'حقیقی';
                    if ($model->type == '1')
                        $type = 'حقیقی - شرکت ' . Html::encode($model->company_info['company_title']);
                    return Html::encode($model->connector_info['first_name'] . ' ' . $model->connector_info['last_name']) . '(' . $type . ')';
                }),
                [
                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts1\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                ]
            )->label(false);
            $brokers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeTeachers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[0][teachers]')->dropDownList(
                ArrayHelper::map($collegeTeachers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return Html::encode($model->first_name . ' ' . $model->last_name);
                }),
                [
                    'prompt' => 'لطفا مدرس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    "onChange" => "$('.submit-course-btn').attr('disabled', false)",
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا مدرس را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $teachers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeLessons != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[0][_id]')->dropDownList(
                ArrayHelper::map($collegeLessons, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return Html::encode($model->title);
                }),
                [
                    'prompt' => 'لطفا درس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => 'lessons'.rand(),
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا درس را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $lessons = ob_get_contents();
            ob_end_clean();
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[0][hide_archive]')->dropDownList(
                $hiddenArchive,
                [
                    'id' => '',
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا وضعیت مخفی کردن آرشیو را مشخص کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $archive = ob_get_contents();
            ob_end_clean();
        }
        $response = array(
            'brokers' => $brokers,
            'teachers' => $teachers,
            'lessons' => $lessons,
            'archive' => $archive,
        );
        return json_encode($response);
    }

    public function actionBroker_contracts($id)
    {
        $broker = Brokers::findOne($id);
        if ($broker != null) {
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],

                ]
            );
            echo $form->field($model, 'broker[contract]')->dropDownList(
                ArrayHelper::map($broker->contracts, 'id', function ($model) {
                    return Html::encode($model['title']) . ' (' . $model['share'] . ' درصد)';
                }),
                [
                    'prompt' => 'لطفا قرارداد کارگزار را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا قرارداد کارگزار را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
        }
    }

    public function actionCapacity($id)
    {
        if ($id == 2) {
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],

                ]
            );
            echo '<label for="nameWithTitle" class="form-label">ظرفیت *</label>';
            echo $form->field($model, 'student_capacity[number]')->textInput(
                [

                    'class' => 'form-control numeral-mask text-start',
                    'required' => true,
                    'type' => 'number',
                    'oninvalid' => 'this.setCustomValidity(\'لطفا ظرفیت دوره را وارد کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
        }
    }

    public function actionCourse_date($id)
    {
        if ($id == 3) {
            $response = array(
                'from' => "<div></div>",
                'to' => "<div></div>",
                'time' => "<div></div>"
            );
            return json_encode($response);
        }


        ob_start();
        $model = new Courses();
        $form = ActiveForm::begin(
            [
                'action' => ['new'],
                'options' => [
                    'enctype' => 'multipart/form-data',
                ],

            ]
        );
        echo '<label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>';
        echo  $form->field($model, 'lessons[0][date][from]')->textInput(
            [
                'class' => 'form-control dob-picker text-start',
                'required' => true,
                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                'oninput' => 'setCustomValidity(\'\')',
            ]
        )->label(false);
        $from = ob_get_contents();
        ob_end_clean();
        ob_start();
        $model = new Courses();
        $form = ActiveForm::begin(
            [
                'action' => ['new'],
                'options' => [
                    'enctype' => 'multipart/form-data',
                ],

            ]
        );
        echo '<label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>';
        echo  $form->field($model, 'lessons[0][date][to]')->textInput(
            [
                'class' => 'form-control dob-picker text-start',
                'required' => true,
                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                'oninput' => 'setCustomValidity(\'\')',
            ]
        )->label(false);
        $to = ob_get_contents();
        ob_end_clean();
        ob_start();
        $model = new Courses();
        $form = ActiveForm::begin(
            [
                'action' => ['new'],
                'options' => [
                    'enctype' => 'multipart/form-data',
                ],

            ]
        );
        echo '<label for="nameWithTitle" class="form-label">ساعت شروع دوره *</label>';
        echo  $form->field($model, 'lessons[0][date][time]')->textInput(
            [
                'class' => 'form-control text-start',
                'required' => true,
                'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع دوره را وارد کنید\')',
                'oninput' => 'setCustomValidity(\'\')',
            ]
        )->label(false);
        $time = ob_get_contents();
        ob_end_clean();
        $response = array(
            'from' => $from,
            'to' => $to,
            'time' => $time
        );
        return json_encode($response);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new CoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, '1');
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function ($model) {
                        return $model->title['main_fa'];
                    },
                    'header' => 'عنوان اصلی فارسی'
                ],
                [
                    'attribute' => function ($model) {
                        return $model->title['main_en'];
                    },
                    'header' => 'عنوان اصلی انگلیسی'
                ],
                [
                    'attribute' => function ($model) {
                        return $model->title['degree_fa'];
                    },
                    'header' => 'عنوان فارسی داخل مدرک'
                ],
                [
                    'attribute' => function ($model) {
                        return $model->title['degree_en'];
                    },
                    'header' => 'عنوان انگلیسی داخل مدرک'
                ],
                [
                    'attribute' => function($model){
                        if($model->status == 0)
                            return 'تائید شده - غیرفعال';
                        else if($model->status == 1)
                            return 'تائید شده - فعال';
                        else if($model->status == 2)
                            return 'در انتظار تائید';
                        else if($model->status == 3)
                            return 'پیش نویس';
                        else if($model->status == 4)
                            return 'نیار به اصلاح';
                        else if($model->status == 5)
                            return 'رد شده';
                        else if($model->status == 6)
                            return 'پایان یافته';
                    },
                    'header' => 'وضعیت دوره'
                ],
                [
                    'attribute' => function($model)
                    {
                        if($model->license_code != null && $model->license_code != '')
                            return $model->license_code;
                        else
                            return '-';
                    },
                    'header' => 'کد مجوز'
                ],
                [
                    'attribute' => function ($model) {
                        return number_format($model->price);
                    },
                    'header' => 'قیمت اصلی (تومان)'
                ],
                [
                    'attribute' => function ($model) {
                        return number_format($model->discount_price);
                    },
                    'header' => 'قیمت با تخفیف (تومان)'
                ],
                [
                    'attribute' => function ($model) {
                        return $model->duration;
                    },
                    'header' => 'مدت زمان دوره (ساعت)'
                ],
                [
                    'attribute' => function ($model) {
                        if ($model->student_capacity['type'] == '1')
                            return 'نامحدود';
                        else if ($model->student_capacity['type'] == '2')
                        {
                            if(isset($model->student_capacity['number']))
                                return $model->student_capacity['number'] . ' نفر';
                            else
                                return '-';
                        }
                        else
                            return 'سازمانی';
                    },
                    'header' => 'ظرفیت دوره'
                ],
                [
                    'attribute' => function($model){
                        if($model->content_type == '1')
                            return 'غیر حضوری';
                        else if($model->content_type == '2')
                            return 'نیمه حضوری';
                        else if($model->content_type == '3')
                            return 'محتوا محور';
                        else
                            return 'حضوری';
                    },
                    'header' => 'نوع دوره'
                ],
                [
                    'attribute' => function ($model) {
                        if($model->lessons != null)
                        {
                            if(array_key_exists('date', $model->lessons[0]))
                            {
                                if(array_key_exists('from', $model->lessons[0]['date']))
                                    return $model->lessons[0]['date']['from'];
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'تاریخ شروع دوره'
                ],
                [
                    'attribute' => function ($model) {
                        if($model->lessons != null)
                        {
                            if(array_key_exists('date', $model->lessons[0]))
                            {
                                if(array_key_exists('to', $model->lessons[0]['date']))
                                    return $model->lessons[0]['date']['to'];
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'تاریخ اتمام دوره'
                ],
                [
                    'attribute' => function ($model) {
                        if($model->lessons != null)
                        {
                            if(array_key_exists('date', $model->lessons[0]))
                            {
                                if(array_key_exists('time', $model->lessons[0]['date']))
                                    return $model->lessons[0]['date']['time'];
                            }
                            else
                                return '-';
                        }
                        else
                            return '-';
                    },
                    'header' => 'ساعت شروع دوره'
                ],
                [
                    'attribute' => function ($model) {
                        return $model->description;
                    },
                    'header' => 'توضیحات دوره'
                ],
                [
                    'attribute' => function ($model) {
                        $college = Colleges::findOne($model->college);
                        if ($college != null)
                            return $college->title;
                        else
                            return 'خطا';
                    },
                    'header' => 'دانشکده'
                ],
                [
                    'attribute' => function ($model) {
                        if ($model->broker != null) {
                            if ($model->broker['_id'] != null && $model->broker['contract'] != null) {
                                $broker = Brokers::findOne($model->broker['_id']);
                                if ($broker != null) {
                                    $brokerContract = 'قرارداد یاقت نشد';
                                    foreach ($broker->contracts as $contract) {
                                        if ($contract['id'] == $model->broker['contract'])
                                            $brokerContract = 'قرارداد با عنوان ' . $contract['title'] . ' و سهم ' . $contract['share'] . ' درصدی';
                                    }
                                    return $broker->connector_info['first_name'] . ' ' . $broker->connector_info['last_name'] . ' - ' . $brokerContract;
                                } else
                                    return 'خطا';
                            } else
                                return '-';
                        } else
                            return '-';
                    },
                    'header' => 'کارگزار'
                ],
                [
                    'attribute' => function ($model)
                    {
                        $lesson = null;
                        $teacher = null;
                        $lessonTitle = '*';
                        $teacherTitle = '*';
                        if($model->lessons != null)
                            if(array_key_exists('_id', $model->lessons[0]))
                                $lesson = Lessons::findOne($model->lessons[0]['_id']);
                        if($model->lessons != null)
                            if(array_key_exists('teachers', $model->lessons[0]))
                                $teacher = Teachers::findOne($model->lessons[0]['teachers']);
                        if($lesson != null)
                            $lessonTitle = $lesson->title;
                        if($teacher != null)
                            $teacherTitle = $teacher->first_name . ' ' . $teacher->last_name;
                        return $lessonTitle . ' - مدرس: ' . $teacherTitle;
                    },
                    'header' => 'درس دوره'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'SingleLessonCourses-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function my_brokers($college)
    {
        $brokers = Brokers::find()->where(['college' => (string) $college])->all();
        if ($brokers != null)
            return $brokers;
        else
            return  null;
    }

    public function my_broker_contract($_id)
    {
        $broker = Brokers::findOne($_id);
        if ($broker != null)
            return $broker->contracts;
        else
            return null;
    }

    public function my_courses($college)
    {
        return Lessons::find()->where(['college' => $college])->all();
    }

    public function my_teachers($college)
    {
//        return Teachers::find()->where(['like', 'colleges', $college])->all();
        return Teachers::find()->all();
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }

    public function lesson_contents($courseId, $lessonId)
    {
        return CoursesContents::find()->where(['course_id' => $courseId])->andWhere(['lesson_id' => $lessonId])->all();
    }

    public function actionMembers_report()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new CoursesMembers();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->request->get('_id'));
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'first_name',
                    'header' => 'نام'
                ],
                [
                    'attribute' => 'last_name',
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'نام کاربری'
                ],
                [
                    'attribute' => function ($model) {
                        $memberCourse = null;
                        if ($model->courses != null)
                            foreach ($model->courses as $course)
                                if ($course['_id'] == Yii::$app->request->get('_id'))
                                    $memberCourse = $course;
                        $registrant = 'نامشخص';
                        if (array_key_exists('registrant', $memberCourse)) {
                            if ($memberCourse['registrant'] == Yii::getAlias('@adminUsername'))
                                $registrant = 'مدیریت';
                            else if ($memberCourse['registrant'] == $model->username)
                                $registrant = 'کاربر';
                            else {
                                $registrantDetail = DashboardController::registrant_detail($memberCourse['registrant']);
                                $role = '';
                                if ($registrantDetail->role == 'emp')
                                    $role = 'کارشناس دانشکده';
                                else if ($registrantDetail->role == 'broker')
                                    $role = 'کارگزار';
                                $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name . '(' . $role . ')';
                            }
                        }
                        return $registrant;
                    },
                    'header' => 'ثبت کننده'
                ],
                [
                    'attribute' => function ($model) {
                        $memberCourse = null;
                        if ($model->courses != null)
                            foreach ($model->courses as $course)
                                if ($course['_id'] == Yii::$app->request->get('_id'))
                                    $memberCourse = $course;
                        $status = 'نامشخص';
                        if ($memberCourse != null) {
                            if ($memberCourse['status'] == '0')
                                $status = 'خطا در ثبت در ادوبی';
                            else if ($memberCourse['status'] == '1')
                                $status = 'فعال';
                            else if ($memberCourse['status'] == '2')
                                $status = 'غیرفعال';
                        }
                        return $status;
                    },
                    'header' => 'وضعیت'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'CourseMembers-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionShow_course_users()
    {
        if(isset($_POST['id']))
        {
            $course = Courses::findOne($_POST['id']);
            if($course != null)
            {
                $users = Users::find()->where(['courses._id' => (string) $course->_id])->all();
                if($users != null)
                {
                    $response = array(
                        'title' => 'حذف دوره '.$course->title['main_fa'],
                        'body' => 'به دلیل اینکه دوره '.$course->title['main_fa'].' دارای دانشپذیر می باشد قابل حذف نیست',
                        'submit' => null
                    );
                }
                else
                {
                    ob_start();
                    $form = ActiveForm::begin(
                        [
                            'action' => ['delete_course'],
                            "method" => "post",
                        ]
                    );
                    echo '<input type="hidden" name="_id" value="'.(string) $course->_id.'">';
                    echo '<button type="submit" class="btn btn-label-danger">بله مطمئنم</button>';
                    ActiveForm::end();
                    $submit = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'title' => 'حذف دوره '.$course->title['main_fa'],
                        'body' => 'آیا از حذف دوره '.$course->title['main_fa'].' مطمئن هستید؟',
                        'submit' => $submit
                    );
                }
                return json_encode($response);
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionDelete_course()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post('_id'));
            if($course != null)
            {
                if($course->delete())
                    Yii::$app->session->setFlash('status','10');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function score_status($courseId, $userId)
    {
        $score = Scores::find()->where(['course_id' => $courseId])->andWhere(['user_id' => $userId])->one();
        if($score != null)
            return '
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path opacity="0.5" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" fill="green"/>
<path d="M16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="green"/>
</svg>

            ';
        else
            return '
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path opacity="0.5" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" fill="red"/>
<path d="M8.96967 8.96967C9.26256 8.67678 9.73744 8.67678 10.0303 8.96967L12 10.9394L13.9697 8.96969C14.2626 8.6768 14.7374 8.6768 15.0303 8.96969C15.3232 9.26258 15.3232 9.73746 15.0303 10.0304L13.0607 12L15.0303 13.9696C15.3232 14.2625 15.3232 14.7374 15.0303 15.0303C14.7374 15.3232 14.2625 15.3232 13.9696 15.0303L12 13.0607L10.0304 15.0303C9.73746 15.3232 9.26258 15.3232 8.96969 15.0303C8.6768 14.7374 8.6768 14.2626 8.96969 13.9697L10.9394 12L8.96967 10.0303C8.67678 9.73744 8.67678 9.26256 8.96967 8.96967Z" fill="red"/>
</svg>

            ';
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

    public function check_date($startDateJalali, $endDateJalali)
    {
        $allowDeadlineDate = true;
        $startDateGregorian = $this->jalaliToGregorian($startDateJalali);
        $endDateGregorian = $this->jalaliToGregorian($endDateJalali);
        // محاسبه فاصله بین دو تاریخ به ثانیه
        $startTimestamp = strtotime($startDateGregorian);
        $endTimestamp = strtotime($endDateGregorian);
        $intervalSeconds = $endTimestamp - $startTimestamp;

// محاسبه یک‌چهارم فاصله (به ثانیه)
        $quarterIntervalSeconds = $intervalSeconds / 4;

// تاریخ امروز به شمسی
        $todayJalali = jdate('Y/m/d'); // با استفاده از jdf
        $todayGregorian = $this->jalaliToGregorian($todayJalali);
        $todayTimestamp = strtotime($todayGregorian);
        // تاریخ مهلت: تاریخ شروع + یک‌چهارم فاصله
        $deadlineTimestamp = $startTimestamp + $quarterIntervalSeconds;

// بررسی: اگر امروز از تاریخ مهلت بزرگتر باشد
        $allowDeadlineDate = ($todayTimestamp <= $deadlineTimestamp);
        $ret = array(
            'allowDeadlineDate' => $allowDeadlineDate,
            'deadlineTimestamp' => $deadlineTimestamp
        );
        return json_encode($ret);
    }
    function jalaliToGregorian($jalaliDate) {
        // جدا کردن و بررسی اولیه
        $parts = explode('-', $jalaliDate);

        if (count($parts) !== 3) {
            // اگر با - جدا نشد، با / امتحان کن
            $parts = explode('/', $jalaliDate);
            if (count($parts) !== 3) {
                die('فرمت تاریخ اشتباه است. باید Y-m-d یا Y/m/d باشد');
            }
        }

        list($jY, $jM, $jD) = $parts;

        // تبدیل
        $gregorian = jalali_to_gregorian($jY, $jM, $jD);
        return implode('-', $gregorian) . ' 00:00:00';
    }

}
