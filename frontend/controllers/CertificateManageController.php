<?php

namespace frontend\controllers;

use Yii;
use app\models\CertificateRequests;
use app\models\CertificateRequestsSearch;
use app\models\CertificateManage;
use app\models\CoursesMembers;
use app\models\Colleges;
use app\models\Users;
use app\models\Courses;
use app\models\Generals;
use app\models\Cities;
use app\models\Provinces;
use app\models\Scores;
use app\models\Lessons;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\User;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * CollagesManageController implements the CRUD actions for Colleges model.
 */
class CertificateManageController extends Controller
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
                        'actions' => ['index', 'report', 'add_serial', 'report1','view-requests','print-certificate' ,'new-print-certificate','issued-certificates','course-members','cities','complete_profile','show_profile_form','add_single_request','confirm_request','change_preview','file','check-serial-number','change_digital_cert'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker' || Yii::$app->user->identity->username == '09122388496')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['print-all-certificate','new-print-all-certificate','check-serial-number'],
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
     * Lists all CertificateRequests models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CertificateManage();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        $dataProvider->pagination->pageSize = 30;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model) {
                return (string) $model->_id;
            }, 'title')
        ]);
    }

    public function actionCheckSerialNumber()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $serialNumber = Yii::$app->request->post('serial_number');

        if (!$serialNumber) {
            return ['success' => false, 'message' => 'شماره سریال ارسال نشده است'];
        }

        // جستجوی شماره سریال
        $existingCertificate = CertificateRequests::find()
            ->where(['serial_number' => $serialNumber])
            ->asArray()
            ->one();

        if ($existingCertificate) {
            // پیدا کردن کاربر
            $user = Users::find()
                ->where(['username' => $existingCertificate['username']])
                ->asArray()
                ->one();

            // پیدا کردن اطلاعات دوره
            $course = Courses::findOne($existingCertificate['course_id']);

            return [
                'success' => true,
                'exists' => true,
                'userInfo' => [
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['last_name'] ?? '',
                    'course_id' => $existingCertificate['course_id'] ?? '',
                    'course_title' => $course->title['main_fa'] ?? '',
                ]
            ];
        }

        return [
            'success' => true,
            'exists' => false
        ];
    }

    public function actionFile($filename)
    {
        $storagePath = 'certificate_files';
        if(file_exists("$storagePath/$filename"))
            return Yii::$app->response->sendFile("$storagePath/$filename", $filename);
        else
        {
            Yii::$app->session->setFlash('status','7');
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function actionCourseMembers($_id)
    {
        if (isset($_GET['_id'])) {
            $courseDetail = Courses::findOne($_GET['_id']);
            if ($courseDetail != null)
            {
                if($this->allow($courseDetail->college))
                {
                    if (Yii::$app->user->identity->role == 'user')
                        $colleges = Colleges::find()->all();
                    else
                        $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
                    $searchModel = new CoursesMembers();
                    $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $courseDetail->_id);
                    $dataProvider->pagination->pageSize = 50;
                    $provinces = Provinces::find()->all();
                    return $this->render('course-members', [
                        'colleges' => ArrayHelper::map($colleges, function ($model) {
                            return (string) $model->_id;
                        }, 'title'),
                        'courseDetail' => $courseDetail,
                        'model' => $courseDetail,
                        'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,
                        'provinces' => ArrayHelper::map($provinces, 'id', 'name'),
                    ]);
                }
            }
        }
        return $this->redirect(['../certificate-manage']);
    }

    public function actionViewRequests()
    {
        $searchModel = new CertificateRequestsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['1']);
        if(Yii::$app->user->identity->role == 'user')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        return $this->render('view-requests', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionIssuedCertificates()
    {
        $searchModel = new CertificateRequestsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,['4']);
        if(Yii::$app->user->identity->role == 'user')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        return $this->render('issued-certificates', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $find = Colleges::findOne(Yii::$app->request->post()['Colleges']['_id']);
            if ($find != null) {
                $preLogo = $find->logo;
                $preSignatureFile = $find->signature_file;
                $find->load(Yii::$app->request->post());
                if ($_FILES['Colleges']['name']['logo'] != '')
                {
                    if ($preLogo != '' && $preLogo != null)
                        unlink('../../frontend/web/college_logos/' . $preLogo);
                    $file1 = UploadedFile::getInstance($find, 'logo');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/college_logos/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'logo') != null)
                        $find->logo = $file1_name;
                }
                else
                    $find->logo = $preLogo;

                if ($_FILES['Colleges']['name']['signature_file'] != '')
                {
                    if ($preSignatureFile != '' && $preSignatureFile != null)
                        unlink('../../frontend/web/college_logos/' . $preSignatureFile);
                    $file1 = UploadedFile::getInstance($find, 'signature_file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/college_logos/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'signature_file') != null)
                        $find->signature_file = $file1_name;
                }
                else
                    $find->signature_file = $preSignatureFile;

                if ($find->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new CertificateRequestsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,'[1]');
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                    $userDetail = Users::find()->where(['username' => $model->username])->one();
                    if($userDetail != null)
                                return $userDetail->first_name.' '.$userDetail->last_name;
                    else
                        return 'خطا';
                    },
                    'header' => 'نام دانشپذیر'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'نام کاربری'
                ],
                [
                    'attribute' => function($model){
                       $courseDetail = Courses::findOne($model->course_id);
                       if($courseDetail != null)
                           return $courseDetail->title['main_fa'];
                       else
                           return 'خطا';
                    },
                    'header' => 'نام دوره'
                ],
                [
                    'attribute' => function($model){
                        return jdate('Y/m/d',hexdec(substr($model->_id, 0, 8)));
                    },
                    'header' => 'تاریخ درخواست'
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
        $file_name = 'Certificate-Requests' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }
    public function actionPrintCertificate($_id)
    {
        if(isset($_GET['_id']))
        {
            $requestDetail = CertificateRequests::findOne($_id);
            if($requestDetail != null)
            {
                $userDetail = Users::find()->where(['username' => $requestDetail->username])->one();
                {
                    $courseDetail = Courses::findOne($requestDetail->course_id);
                    if($courseDetail != null)
                    {
                        if($this->allow($courseDetail->college))
                        {
                            $license1 = explode('-',$courseDetail->license_code);
                            $license = $license1[1].'-'.$license1[0];
                            $license2 = $license1[0].'-'.$license1[1];
                            $collegeDetail = Colleges::findOne($courseDetail->college);
                            $general = Generals::find()->where(['type' => 'certificateSignature'])->one();
                            $this->layout = 'empty';
                            if($userDetail != null)
                                return $this->render('print-certificate', [
                                    'requestDetail' => $requestDetail,
                                    'userDetail' => $userDetail,
                                    'courseDetail' => $courseDetail,
                                    'collegeDetail' => $collegeDetail,
                                    'license' => $license,
                                    'license2' => $license2,
                                    'general' => $general,
                                ]);
                        }
                    }
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }

    public function actionNewPrintCertificate($_id)
    {
        if(isset($_GET['_id']))
        {
            $requestDetail = CertificateRequests::findOne($_id);
            if($requestDetail != null)
            {
                $userDetail = Users::find()->where(['username' => $requestDetail->username])->one();
                {
                    $courseDetail = Courses::findOne($requestDetail->course_id);
                    if($courseDetail != null)
                    {
                        if($this->allow($courseDetail->college))
                        {
                            $license1 = explode('-',$courseDetail->license_code);
                            $license = $license1[1].'-'.$license1[0];
                            $license2 = $license1[0].'-'.$license1[1];
                            $collegeDetail = Colleges::findOne($courseDetail->college);
                            $general = Generals::find()->where(['type' => 'certificateSignature'])->one();
                            $this->layout = 'empty';
                            if($userDetail != null)
                                return $this->render('new-print-certificate', [
                                    'requestDetail' => $requestDetail,
                                    'userDetail' => $userDetail,
                                    'courseDetail' => $courseDetail,
                                    'collegeDetail' => $collegeDetail,
                                    'license' => $license,
                                    'license2' => $license2,
                                    'general' => $general,
                                ]);
                        }
                    }
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }

    public function actionPrintAllCertificate($_id)
    {
        if(isset($_GET['_id']))
        {
            $requests = CertificateRequests::find()->where(['course_id' => $_id])->orderBy(['username'=>SORT_DESC])->all();
            $users = Users::find()->where(['courses._id' => $_id])->orderBy(['last_name'=>SORT_ASC])->all();
            if($users != null)
            {
                $courseDetail = Courses::findOne($_id);
                $license1 = explode('-',$courseDetail->license_code);
                $license = $license1[1].'-'.$license1[0];
                $license2 = $license1[0].'-'.$license1[1];
                $collegeDetail = Colleges::findOne($courseDetail->college);
                $general = Generals::find()->where(['type' => 'certificateSignature'])->one();
                $this->layout = 'empty';
                return $this->render('print-all-certificate', [
                    'courseDetail' => $courseDetail,
                    'collegeDetail' => $collegeDetail,
                    'license' => $license,
                    'license2' => $license2,
                    'general' => $general,
                    'requests' => $requests,
                    'users' => $users,
                ]);
            }
        }
        return $this->redirect(['../dashboard']);
    }

    public function actionNewPrintAllCertificate($_id)
    {
        if(isset($_GET['_id']))
        {
            $requests = CertificateRequests::find()->where(['course_id' => $_id])->orderBy(['username'=>SORT_DESC])->all();
            $users = Users::find()->where(['courses._id' => $_id])->orderBy(['last_name'=>SORT_ASC])->all();
            if($users != null)
            {
                $courseDetail = Courses::findOne($_id);
                $license1 = explode('-',$courseDetail->license_code);
                $license = $license1[1].'-'.$license1[0];
                $license2 = $license1[0].'-'.$license1[1];
                $collegeDetail = Colleges::findOne($courseDetail->college);
                $general = Generals::find()->where(['type' => 'certificateSignature'])->one();
                $this->layout = 'empty';
                return $this->render('new-print-all-certificate', [
                    'courseDetail' => $courseDetail,
                    'collegeDetail' => $collegeDetail,
                    'license' => $license,
                    'license2' => $license2,
                    'general' => $general,
                    'requests' => $requests,
                    'users' => $users,
                ]);
            }
        }
        return $this->redirect(['../dashboard']);
    }
    public function actionAdd_serial()
    {
        if(Yii::$app->request->isPost)
        {
            $request = CertificateRequests::findOne(Yii::$app->request->post()['CertificateRequests']['_id']);
            if($request != null)
            {
                if($request->serial_number != null && $request->serial_number != '')
                    $request->pre_serial_number = $request->serial_number;
                $request->load(Yii::$app->request->post());
                $request->status = '4';
                if($request->save())
                    Yii::$app->session->setFlash('status','6');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function user_detail($username)
    {
        return Users::find()->where(['username' => $username])->one();
    }

    public function find_user($_id)
    {
        return Users::findOne($_id);
    }
    public function course_detail($_id)
    {
        return Courses::findOne($_id);
    }
    public function user_request($username, $courseId)
    {
        return CertificateRequests::find()->where(['username' => $username])->andWhere(['course_id' => $courseId])->one();
    }

    public function actionCities()
    {
        if(isset($_GET['id']))
        {
            $id = $_GET['id'];
            $province = Provinces::find()->where(['id' => (int) $id])->one();
            if($province != null)
            {
                $cities = Cities::find()->where(['province_id' => $province->id])->all();
                if($cities != null)
                {
                    ob_start();
                    $model = new Users();
                    $form = ActiveForm::begin(
                        [
                            'action' => ['complete_profile'],
                        ]
                    );
                    echo $form->field($model, 'issuance_certificate_information[city]')->dropDownList(
                        ArrayHelper::map($cities, 'id', 'name'),
                        [
                            'class' => 'select2 form-select',
                            'id' => '',
                            'required' => true
                        ]
                    )->label(false);
                    $res = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'res' => $res
                    );
                    return json_encode($response);
                }
                else
                    return  false;
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionComplete_profile()
    {
        if(Yii::$app->request->isPost)
        {
            $user = Users::findOne(Yii::$app->request->post()['Users']['_id']);
            if($user != null);
            {
                $preData = $user['issuance_certificate_information'];
                $newData = Yii::$app->request->post()['Users']['issuance_certificate_information'];
                if($_FILES['Users']['size']['issuance_certificate_information']['degree_education_file'] != 0)
                {
                    if(file_exists('../../frontend/web/certificate_files/'. $preData['degree_education_file']) &&  $preData['degree_education_file'] != null &&  $preData['degree_education_file'] != '')
                        unlink('../../frontend/web/certificate_files/'. $preData['degree_education_file']);
                    $file = UploadedFile::getInstance($user, 'issuance_certificate_information[degree_education_file]');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/certificate_files/' . $file_name);
                    if (UploadedFile::getInstance($user, 'issuance_certificate_information[degree_education_file]') != null)
                        $newData['degree_education_file'] = $file_name;
                }
                else if($preData != null)
                    if(array_key_exists('degree_education_file', $preData))
                        $newData['degree_education_file'] = $preData['degree_education_file'];
                if($_FILES['Users']['size']['issuance_certificate_information']['id_file'] != 0)
                {
                    if(file_exists('../../frontend/web/certificate_files/'. $preData['id_file']) &&  $preData['id_file'] != null &&  $preData['id_file'] != '')
                        unlink('../../frontend/web/certificate_files/'. $preData['id_file']);
                    $file = UploadedFile::getInstance($user, 'issuance_certificate_information[id_file]');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/certificate_files/' . $file_name);
                    if (UploadedFile::getInstance($user, 'issuance_certificate_information[id_file]') != null)
                        $newData['id_file'] = $file_name;
                }
                else if($preData != null)
                    if(array_key_exists('id_file', $preData))
                        $newData['id_file'] = $preData['id_file'];
                $user->issuance_certificate_information = $newData;
                if($user->save())
                {
                    if(Yii::$app->request->post('courseId') != '0')
                    {
                        $courseDetail = Courses::findOne(Yii::$app->request->post('courseId'));
                        if($courseDetail != null)
                        {
                            $model = new CertificateRequests();
                            $model->username = $user->username;
                            $model->course_id = (string) $courseDetail->_id;
                            $model->college = $courseDetail->college;
                            $model->status = '2';
                            $model->request = '1';
                            $model->save();
                        }
                    }
                    Yii::$app->session->setFlash('status','3');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionShow_profile_form()
    {
        if(isset($_POST['id']))
        {
            $data = explode('-',$_POST['id']);
            if($data != null)
            {
                $member = Users::findOne($data[0]);
                if($member != null)
                {
                    $model = $member;
                    $provinces = ArrayHelper::map(Provinces::find()->all(), 'id', 'name');
                    $modalTitle = 'درخواست صدور مدرک '.Html::encode($member->first_name.' '.$member->last_name);
                    $modalText = 'برای درخواست صدور مدرک ابتدا باید پروفایل را تکمیل فرمائید';
                    $buttonText = 'تکمیل پروفایل و درخواست صدور مدرک';
                    if($data[1] == '0')
                    {
                        $modalTitle = 'ویرایش پروفایل '.Html::encode($member->first_name.' '.$member->last_name);
                        $modalText = '';
                        $buttonText = 'ویرایش پروفایل';
                    }
                    ob_start();
                    ?>
                    <div class="modal-header">
                        <h5 class="modal-title secondary-font" id="modalCenterTitle"><?= $modalTitle ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        if($modalText != '')
                            echo '<p>'.$modalText.'</p>';
                        $form = ActiveForm::begin(
                            [
                                'action' => ['complete_profile'],
                                "method" => "post",
                                'options' => [
                                    'class' => '',
                                    'enctype' => 'multipart/form-data'
                                ],
                            ]
                        );
                        echo $form->field($member, '_id')->hiddenInput()->label(false);
                        echo '<input type="hidden" name="courseId" value="'.(string) $data[1].'">';
                        ?>
                        <div class="row">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نام فارسی *</label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[first_name_fa]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نام خانوادگی فارسی *</label>
                                <?php
                                echo  $form->field($member, 'issuance_certificate_information[last_name_fa]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نام پدر </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[father_name]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>

                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نام انگلیسی *</label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[first_name_en]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نام خانوادگی انگلیسی *</label>
                                <?php
                                echo  $form->field($member, 'issuance_certificate_information[last_name_en]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">جنسیت *</label>
                                <?php
                                $gender = array(
                                    '0' => 'زن',
                                    '1' => 'مرد'
                                );
                                echo $form->field($member, 'issuance_certificate_information[gender]')->dropDownList(
                                    $gender,
                                    [
                                        'class' => 'select2 form-control text-start',
                                        'required' => true,
                                        'prompt' => 'لطفا انتخاب کنید'
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">شماره شناسنامه </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[shsh]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">کد ملی *</label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[id]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">شماره دانشجویی</label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[student_number]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">رشته تحصیلی </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[field]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">ایمیل </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[email]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">تاریخ تولد </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[birth_day]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">استان </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[province]')->dropDownList(
                                    $provinces,
                                    [
                                        'class' => 'select2 form-control text-start',
                                        'prompt' => 'لطفا انتخاب کنید',
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/certificate-manage/cities') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                           $(\'#cities\').html(main_data.res);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">شهرستان *</label>
                                <?php
                                $cityFlag = false;
                                if($model->issuance_certificate_information != null)
                                    if(array_key_exists('city', $model->issuance_certificate_information))
                                        $cityFlag = true;
                                if($cityFlag)
                                {
                                    ?>
                                    <div id="cities">
                                        <?php
                                        $cities = Cities::find()->where(['province_id' => (int) $model->issuance_certificate_information['province']])->all();
                                        echo $form->field($model, 'issuance_certificate_information[city]')->dropDownList(
                                            ArrayHelper::map($cities, 'id', 'name'),
                                            [
                                                'class' => 'select2 form-select',
                                                'id' => '',
                                                'required' => true
                                            ]
                                        )->label(false);
                                        ?>
                                    </div>
                                    <?php
                                }
                                else
                                {
                                    ?>
                                    <div id="cities">
                                        <span class="badge bg-label-warning">در انتظار انتخاب استان</span>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">کدپستی </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[zip_code]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">آدرس </label>
                                <?php
                                echo $form->field($member, 'issuance_certificate_information[address]')->textarea(
                                    [
                                        'class' => 'form-control text-start',
                                        'cols' => 10
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <div class="card mb-4 relative">
                                    <div class="card-body">
                                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test-'.rand().'">
                                            <div class="dz-message needsclick">
                                                <?php
                                                $idFileFlag = false;
                                                $idFileReq = true;
                                                $degreeFileFlag = false;
                                                $degreeFileReq = true;
                                                $front = Yii::getAlias('@front');
                                                if($model->issuance_certificate_information != null)
                                                {
                                                    if(array_key_exists('id_file', $model->issuance_certificate_information))
                                                    {
                                                        if($model->issuance_certificate_information['id_file'] != null)
                                                            $idFileFlag = true;
                                                        $idFileReq = false;
                                                    }
                                                    if(array_key_exists('degree_education_file', $model->issuance_certificate_information))
                                                    {
                                                        if($model->issuance_certificate_information['degree_education_file'] != null)
                                                            $degreeFileFlag = true;
                                                        $degreeFileReq = false;
                                                    }
                                                }
//                                                    echo '<img src="'.$front.'/certificate_files/'.$model->issuance_certificate_information['id_file'].'" class="upload-preview img-fluid">';
                                                echo $form->field($member, 'issuance_certificate_information[id_file]')->fileInput(
                                                    [
                                                        'class' => 'form-control text-start drop-file',
//                                                        'required' => $idFileReq
                                                        'required' => false
                                                    ]
                                                )->label(false);
                                                ?>
                                                <span class="drop-title"></span>
                                                <span class="note needsclick">تصویر کارت ملی </span>
                                            </div>
                                        </div>
                                        <?php
                                        if($idFileFlag)
                                            echo '<a href="'.Yii::$app->urlManager->createUrl(['certificate-manage/file','filename' => $model->issuance_certificate_information['id_file']]).'">دانلود تصویر کارت ملی</a>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <div class="card mb-4 relative">
                                    <div class="card-body">
                                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test-'.rand().'">
                                            <div class="dz-message needsclick">
                                                <?php
//                                                if($degreeFileFlag)
//                                                    echo '<img src="'.$front.'/certificate_files/'.$model->issuance_certificate_information['degree_education_file'].'" class="upload-preview img-fluid">';
                                                echo $form->field($member, 'issuance_certificate_information[degree_education_file]')->fileInput(
                                                    [
                                                        'class' => 'form-control text-start drop-file',
//                                                        'required' => $degreeFileReq
                                                        'required' => false
                                                    ]
                                                )->label(false);
                                                ?>
                                                <span class="drop-title"></span>
                                                <span class="note needsclick">تصویر آخرین مدرک تحصیلی </span>
                                            </div>
                                        </div>
                                        <?php
                                        if($degreeFileFlag)
                                            echo '<a href="'.Yii::$app->urlManager->createUrl(['certificate-manage/file','filename' => $model->issuance_certificate_information['degree_education_file']]).'">دانلود تصویر آخرین مدرک تحصیلی</a>';
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <?= $buttonText ?>
                            </button>
                        </div>';
                    </div>
                    <div class="modal-footer">
                    </div>
                    <?php
                    ActiveForm::end();
                    $res = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'body' => $res
                    );
                    return json_encode($response);
                }
                else
                    return false;
            }
            else
                return  false;
        }
        else
            return false;
    }

    public function actionAdd_single_request()
    {
        if(Yii::$app->request->isPost)
        {
            $user = Users::findOne(Yii::$app->request->post()['Users']['_id']);
            if($user != null)
            {
                $course = Courses::findOne(Yii::$app->request->post('course_id'));
                if($course != null)
                {
                    $model = new CertificateRequests();
                    $model->username = $user->username;
                    $model->course_id = (string) $course->_id;
                    $model->college = $course->college;
                    $model->status = '2';
                    $model->request = '1';
                    if($model->save())
                        Yii::$app->session->setFlash('status','3');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionConfirm_request()
    {
        if(Yii::$app->request->isPost)
        {
            $userRequest = CertificateRequests::findOne(Yii::$app->request->post()['request_id']);
            if($userRequest != null)
            {
                $userRequest->status = '2';
                if($userRequest->save())
                    Yii::$app->session->setFlash('status','4');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function convert($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    public function english_convert($str)
    {
        $western_arabic = array('0','1','2','3','4','5','6','7','8','9');
        $eastern_arabic = array('٠','١','٢','٣','٤','٥','٦','٧','٨','٩');

        $str = str_replace($western_arabic, $eastern_arabic, $str);
        return $str;
    }

    public function user_score($courseId, $userId)
    {
        $finalScore = null;
        $userScore = Scores::find()->where(['course_id' => $courseId])->andWhere(['user_id' => $userId])->one();
        if($userScore != null)
        {
            if($userScore->scores != null)
            {
                $score = 0;
                $count = 0;
                foreach ($userScore->scores as $item)
                {
                    if($item['score'] != null && $item['score'] != '' && is_numeric($item['score']))
                    {
                        $score += $this->convertPersianNumbersToEnglish($item['score']);
                        $count++;
                    }
                }
                if($count != 0)
                    $finalScore = $score / $count;
                else
                    $finalScore = 0;
            }
        }
        return $finalScore;
    }

    public function convertPersianNumbersToEnglish($input)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '٤', '۵', '٥', '٦', '۶', '۷', '۸', '۹'];
        $english = [ 0 ,  1 ,  2 ,  3 ,  4 ,  4 ,  5 ,  5 ,  6 ,  6 ,  7 ,  8 ,  9 ];
        return str_replace($persian, $english, $input);
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }

    public function lesson_score($courseId, $userId, $lessonId)
    {
        $score = null;
        $userScores = Scores::find()->where(['course_id' => $courseId])->andWhere(['user_id' => $userId])->one();
        if($userScores != null)
        {
            foreach ($userScores->scores as $item)
                if($item['lesson'] == $lessonId)
                    if($item['score'] != null && $item['score'] != '' && is_numeric($item['score']))
                        $score = $item['score'];
        }
        return $score;
    }

    public function actionChange_preview()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post('_id'));
            if($course != null)
            {
                $course->load(Yii::$app->request->post());
                if($course->save())
                    Yii::$app->session->setFlash('status','5');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_digital_cert()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($course != null)
            {
                $digital_cert_state = false;
                if($course->digital_cert != null)
                    if($course->digital_cert == true)
                        $digital_cert_state = true;
                if($digital_cert_state)
                    $course->digital_cert = false;
                else
                    $course->digital_cert = true;
                if($course->save())
                    Yii::$app->session->setFlash('status','8');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
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

    public function check_request($username, $course_id)
    {
        return CertificateRequests::find()->where(['username' => $username])->andWhere(['course_id' => $course_id])->andWhere(['status' => ['2','4']])->one();
    }

}
