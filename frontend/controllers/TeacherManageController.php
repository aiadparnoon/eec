<?php

namespace frontend\controllers;

use Yii;
use app\models\Teachers;
use app\models\TeachersSearch;
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
 * BuyersController implements the CRUD actions for Teachers model.
 */
class TeacherManageController extends Controller
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
                        'actions' => ['index','report' , 'new', 'edit','reset_password','change_status', 'new_with_id'],
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
//        echo Yii::$app->user->identity->college;
//        exit;
        $searchModel = new TeachersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        $dataProvider->pagination->pageSize = 20;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
        $searchModel = new TeachersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
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
                    'attribute' => 'mobile',
                    'header' => 'شماره همراه'
                ],
                [
                    'attribute' => function($model){
                    return ' '.(string) $model->id.' ';
                    },
                    'header' => 'کد ملی'
                ],
                [
                    'attribute' => function($model){
                    if($model->gender == '0')
                        return 'مرد';
                    else
                        return 'زن';
                    },
                    'header' => 'جنسیت'
                ],
                [
                    'attribute' => 'comment',
                    'header' => 'توضیحات'
                ],
                [
                    'attribute' => function($model){
                        $totalColleges = '';
                        if($model->colleges != null)
                        {
                            foreach ($model->colleges as $item)
                            {
                                $colleges = Colleges::findOne($item);
                                $totalColleges = $totalColleges.' - '.$colleges->title;
                            }
                            return $totalColleges;
                        }
                        else
                            return 'خطا';
                    },
                    'header' => 'دانشکده'
                ],
                [
                    'attribute' => function($model){
                        $registrant = 'نامشخص';
                        if($model->registrant == Yii::getAlias('@adminUsername'))
                            $registrant = 'مدیریت';
                        else
                        {
                            $registrantDetail = DashboardController::registrant_detail($model->registrant);
                            $role = '';
                            if($registrantDetail->role == 'emp')
                                $role = 'کارشناس دانشکده';
                            else if($registrantDetail->role == 'broker')
                                $role = 'کارگزار';
                            $registrant = $registrantDetail->first_name.' '.$registrantDetail->last_name.'('.$role.')';
                        }
                        return $registrant;
                    },
                    'header' => 'ثبت کننده'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'Teachers-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Teachers::find()->where(['mobile' => Yii::$app->request->post()['Teachers']['mobile']])->one();
            if ($find == null)
            {
                    $model = new Teachers();
                    $model->load(Yii::$app->request->post());
                    if ($_FILES['Teachers']['name']['profile_image'] != '')
                    {
                        $file = UploadedFile::getInstance($model, 'profile_image');
                        $file_ext = $file->extension;
                        $file_name = uniqid() . '.' . $file_ext;
                        $file->saveAs('../../frontend/web/teacher_profiles/' . $file_name);
                        if (UploadedFile::getInstance($model, 'profile_image') != null)
                            $model->profile_image = $file_name;
                    }
                    else
                        $model->profile_image = 'teacher_profile.svg';
                    $model->status = '1';
                    $model->registrant = Yii::$app->user->identity->username;
                    if ($model->save())
                    {
                        $access = new Admin();
                        $access->first_name = $model->first_name;
                        $access->last_name = $model->last_name;
                        $access->setPassword($model->id);
                        $access->username = $model->mobile;
                        $access->auth_key = Yii::$app->security->generateRandomString();
                        $access->verification_token = Yii::$app->security->generateRandomString();
                        $access->getAuthKey();
                        $access->role = 'teacher';
                        $access->status = 10;
                        $access->college = $model->colleges;
                        $access->access = array("courses","packages","test-maker","upload-center");
                        $access->save();
                        Yii::$app->session->setFlash('status', '1');
                    }
                    else
                        Yii::$app->session->setFlash('status', '2');


            }
            else
            {
                if($find->id == Yii::$app->request->post()['Teachers']['id'])
                {
                    $flag = false;
                    foreach ($find->colleges as $college)
                        if($college == Yii::$app->user->identity->college)
                            $flag = true;
                    if($flag)
                    {
                        Yii::$app->session->setFlash('status', '7');
                    }
                    else
                    {
                        $finalCollege = $find->colleges;
                        array_push($finalCollege, Yii::$app->user->identity->college);
                        $find->colleges = $finalCollege;
                        if($find->save())
                            Yii::$app->session->setFlash('status', '1');
                        else
                            Yii::$app->session->setFlash('status', '2');
                    }
                }
                else
                    Yii::$app->session->setFlash('status', '3');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionNew_with_id()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Teachers::find()->where(['mobile' => Yii::$app->request->post()['Teachers']['mobile']])->andWhere(['id' => Yii::$app->request->post()['Teachers']['id']])->one();
            if($find != null)
            {
                $flag = false;
                foreach ($find->colleges as $college)
                    if($college == Yii::$app->user->identity->college)
                        $flag = true;
                if($flag)
                {
                    Yii::$app->session->setFlash('status', '7');
                }
                else
                {
                    $finalCollege = $find->colleges;
                    array_push($finalCollege, Yii::$app->user->identity->college);
                    $find->colleges = $finalCollege;
                    if($find->save())
                        Yii::$app->session->setFlash('status', '1');
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
            }
            else
                Yii::$app->session->setFlash('status', '8');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Teachers::findOne(Yii::$app->request->post()['Teachers']['_id']);
            if ($find != null)
            {
                if ($_FILES['Teachers']['size']['profile_image'] <= Yii::getAlias('@uploadSize'))
                {
                    $preImage = $find->profile_image;
                    $find->load(Yii::$app->request->post());
                    if ($_FILES['Teachers']['name']['profile_image'] != '')
                    {
                        if ($preImage != '' && $preImage != null && $preImage != 'teacher_profile.svg')
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
                    {
                        $admin = Admin::find()->where(['username' => $find->mobile])->one();
                        $admin->first_name = $find->first_name;
                        $admin->last_name = $find->last_name;
                        $admin->college = $find->colleges;
                        $admin->save();
                        Yii::$app->session->setFlash('status', '4');
                    }
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
                else
                    Yii::$app->session->setFlash('status', '9');

            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionReset_password()
    {
        if(Yii::$app->request->isPost)
        {
            $teacher = Teachers::findOne(Yii::$app->request->post()['Teachers']['_id']);
            if($teacher != null)
            {
                $admin = Admin::find()->where(['username' => $teacher->mobile])->one();
                if($admin != null)
                {
                    $admin->setPassword($teacher->id);
                    if($admin->save())
                        Yii::$app->session->setFlash('status','5');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $teacher = Teachers::findOne(Yii::$app->request->post()['Teachers']['_id']);
            if($teacher != null)
            {
                $status = $teacher->status;
                if($status == 1)
                    $teacher->status = '0';
                else
                    $teacher->status = '1';
                if($teacher->save())
                {
                    $admin = Admin::find()->where(['username' => $teacher->mobile])->one();
                    if($admin != null)
                    {
                        if($status == 1)
                            $admin->status = 9;
                        else
                            $admin->status = 10;
                        if($admin->save())
                            Yii::$app->session->setFlash('status','6');
                        else
                        {
                            $teacher->status = $status;
                            $teacher->save();
                            Yii::$app->session->setFlash('status','2');
                        }
                    }
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }
}
