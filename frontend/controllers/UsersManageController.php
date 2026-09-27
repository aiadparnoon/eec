<?php

namespace frontend\controllers;

use Yii;
use app\models\Users;
use app\models\UsersSearch;
use app\models\Colleges;
use app\models\Courses;
use app\components\StudentAccess;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;
use PHPExcel_IOFactory;
/**
 * UsersManageController implements the CRUD actions for Users model.
 */
class UsersManageController extends Controller
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
                        'actions' => ['index', 'change_status', 'change_user_course_status', 'report' ,'new', 'edit','new_user','check_excel_file','add_user_from_exel','edit_user','change_password'],
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
     * Lists all Lessons models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
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
                    'attribute' => function($model){
                        $memberCourse = null;
                        if ($model->courses != null)
                            foreach ($model->courses as $course)
                                if ($course['_id'] == Yii::$app->request->get('_id'))
                                    $memberCourse = $course;
                        $registrant = 'نامشخص';
                        if ($model->registrant != null)
                        {
                            if ($model->registrant == Yii::getAlias('@adminUsername'))
                                $registrant = 'مدیریت';
                            else if ($model->registrant == $model->username)
                                $registrant = 'کاربر';
                            else {
                                $registrantDetail = DashboardController::registrant_detail($model->registrant);
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
                    'attribute' => function($model)
                    {
                        if($model->status == 9)
                            return 'غیر فعال';
                        else
                            return 'فعال';
                    },
                    'header' => 'وضعیت'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'Members-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionNew_user()
    {
        if(Yii::$app->request->isPost)
        {
            $find = Users::find()->where(['username' => strtolower(Yii::$app->request->post()['Users']['username'])])->one();
            if($find == null)
            {
                $model = new Users();
                // فقط فیلدهای فرم؛ courses/college/principal_id نباید از فرم قابل تزریق باشند
                $input = Yii::$app->request->post('Users');
                $model->setAttributes(array_intersect_key(is_array($input) ? $input : [], array_flip(['first_name', 'last_name'])));
                $model->username = strtolower(Yii::$app->request->post()['Users']['username']);
                $model->setPassword(Yii::$app->request->post()['Users']['password_hash']);
                $model->auth_key = Yii::$app->security->generateRandomString();
                $model->verification_token = Yii::$app->security->generateRandomString();
                $model->getAuthKey();
                $model->role = 'user';
                $model->status = 10;
                $model->registrant = Yii::$app->user->identity->username;
                if( $model->save())
                    Yii::$app->session->setFlash('status','1');
                else
                    Yii::$app->session->setFlash('status','2');
            }
            else
                Yii::$app->session->setFlash('status','3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCheck_excel_file()
    {
        $errors= array();
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_type = $_FILES['file']['type'];
        $file_ext = explode('.',$file_name)[1];


        $extensions= array("xlsx", "xls");

        if(in_array($file_ext,$extensions) === false){
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">پسوند فایل انتخاب شده اشتباه می باشد</div>'
            );
            return json_encode($response);
        }

        if($file_size > Yii::getAlias('@maxFileSize'))
        {
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">حجم فایل وارد شده باید کمتر از ۱۰ مگابایت باشد</div>'
            );
            return json_encode($response);
        }

        if(empty($errors) == true)
        {
            $newName = Yii::$app->user->identity->username.'-'.uniqid().'.'.$file_ext;
            move_uploaded_file($file_tmp,"../../frontend/web/uploaded_excels/".$newName);
            $objPHPExcel = PHPExcel_IOFactory::load('../../frontend/web/uploaded_excels/'.$newName);
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
            $i = 0;
            $j = 1;
            if($sheetData != null)
            {
                $count = count($sheetData) - 1;
                ob_start();
                echo '<div class="card">';
                echo '<div class="mt-3">';
                echo '<div class="btn-group" role="group" aria-label="Basic example">';
                echo '<button class="btn btn-secondary">تعداد کاربران موجود در فایل: '.$count.' نفر می باشد</button>';
                $form = ActiveForm::begin(['action' => ['add_user_from_exel']]);
                echo '<button type="submit" class="btn btn-success">افزودن نهایی به دوره</button>';
                echo '<input name="fileName" value="'.$newName.'" type="hidden">';
                ActiveForm::end();
                echo '</div>';
                echo '</div>';
                echo '<h5 class="card-header heading-color">تعداد کاربر: '.$count.'</h5>';
                echo '<div class="table-responsive text-nowrap" id="tbl">';
                echo '<table class="table">';
                echo '<thead> <tr><th>#</th><th>نام</th><th>نام خانوادگی</th><th>نام کاربری</th><th>رمز عبور</th></tr></thead>';
                echo '<tbody class="table-border-bottom-0">';
                foreach ($sheetData as $data)
                {
                    if($j++ != 1)
                    {
                        echo '<tr>';
                        echo '<td><span class="badge badge-center bg-label-secondary">'.$i.'</span></td>';
                        echo '<td>'.$data['A'].'</td>';
                        echo '<td>'.$data['B'].'</td>';
                        echo '<td>'.$data['C'].'</td>';
                        echo '<td>'.$data['D'].'</td>';
                        echo '</tr>';
                    }
                    $i++;
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                $message = ob_get_contents();
                ob_end_clean();
            }
            $response = array(
                'message' => $message
            );
            return json_encode($response);
        }
        else
        {
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">خطایی در بارگزاری فایل رخ داده، لطفا مجددا تلاش کنید</div>'
            );
            return json_encode($response);
        }
    }

    public function actionAdd_user_from_exel()
    {
        if(Yii::$app->request->isPost)
        {
            if(file_exists('../../frontend/web/uploaded_excels/'.Yii::$app->request->post('fileName')))
            {
                $objPHPExcel = PHPExcel_IOFactory::load('../../frontend/web/uploaded_excels/'.Yii::$app->request->post('fileName'));
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                $i = 1;
                foreach ($sheetData as $data)
                {
                    if($i++ != 1)
                    {
                        $user = Users::find()->where(['username' => strtolower($data['C'])])->one();
                        if($user == null)
                        {
                            $model = new Users();
                            $model->first_name = $data['A'];
                            $model->last_name = $data['B'];
                            $model->username = strtolower($data['C']);
                            $model->setPassword($data['D']);
                            $model->auth_key = Yii::$app->security->generateRandomString();
                            $model->verification_token = Yii::$app->security->generateRandomString();
                            $model->getAuthKey();
                            $model->role = 'user';
                            $model->status = 10;
                            $model->registrant = Yii::$app->user->identity->username;
                            $model->save();
                        }
                    }
                }
                Yii::$app->session->setFlash('status','4');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $user = $this->findManagedStudent();
            if($user != null)
            {
                if($user->status == 9)
                    $user->status = 10;
                else
                    $user->status = 9;
                if($user->save(false))
                    Yii::$app->session->setFlash('status','6');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_user_course_status()
    {
        if(Yii::$app->request->isPost)
        {
            $user = $this->findManagedStudent();
            if($user != null)
            {
                if($user->courses != null)
                {
                    $userCourses = $user->courses;
                    $row = Yii::$app->request->post('row');
                    if(is_scalar($row) && isset($userCourses[$row]['_id']) && $userCourses[$row]['_id'] == Yii::$app->request->post('courseId'))
                    {
                        $myCourse = $userCourses[Yii::$app->request->post('row')];
                        if($myCourse['status'] == '0') // if Courses Not Inserted in AdobeConnect Call API For Insert Course in AdobeConnet
                        {
                            // Call AdobeConnect API For Insert User To Course (With Username And Course _ID)
                            if(true) // Replace With AdobeConnect API Response
                            {
                                $userCourses[Yii::$app->request->post('row')]['status'] = '1';
                            }
                            else
                            {
                                Yii::$app->session->setFlash('status','8');
                                return $this->redirect(Yii::$app->request->referrer);
                            }
                        }
                        else if($myCourse['status'] == '1')
                            $userCourses[Yii::$app->request->post('row')]['status'] = '2';
                        else if($myCourse['status'] == '2')
                            $userCourses[Yii::$app->request->post('row')]['status'] = '1';
                        $user->courses = $userCourses;
                        if($user->save())
                            Yii::$app->session->setFlash('status','6');
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function course_detail($_id)
    {
        return Courses::findOne($_id);
    }

    public function actionEdit_user()
    {
        if(Yii::$app->request->isPost)
        {
            $user = $this->findManagedStudent();
            if($user != null)
            {
                // فقط فیلدهای فرم ویرایش؛ نه college/registrant/courses/principal_id
                $input = Yii::$app->request->post('Users');
                $user->setAttributes(array_intersect_key(is_array($input) ? $input : [], array_flip(['first_name', 'last_name'])));
                if($user->principal_id != null && !$this->updateOnlineClassUser($user))
                    Yii::$app->session->setFlash('status', '9');
                else if ($user->save())
                    Yii::$app->session->setFlash('status', '8');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * مشخصات کاربر را در سامانه‌ی کلاس آنلاین (فعلاً Adobe Connect) به‌روز می‌کند.
     * در صورت قطع ارتباط، timeout (۱۵ ثانیه) یا پاسخ نامعتبر false برمی‌گرداند.
     *
     * @param Users $user
     * @return bool
     */
    private function updateOnlineClassUser($user)
    {
        $adminRole = Admin::find()->where(['role' => 'user'])->one();
        if($adminRole == null)
            return false;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/update-user-info',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                'principal_id' => (string) $user->principal_id,
                'username' => (string) $user->username,
                'first_name' => (string) $user->first_name,
                'last_name' => (string) $user->last_name,
            ], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array(
                '_id: '.(string) $adminRole->_id,
                'Content-Type: application/json'
            ),
        ));
        $raw = curl_exec($curl);
        $failed = $raw === false || curl_errno($curl) !== 0;
        curl_close($curl);
        if($failed)
        {
            Yii::warning('Adobe update-user-info failed for user '.(string) $user->_id, __METHOD__);
            return false;
        }

        $response = json_decode($raw);
        return is_object($response) && isset($response->status) && $response->status == 'ok';
    }

    public function actionChange_password()
    {
        if(Yii::$app->request->isPost)
        {
            $user = $this->findManagedStudent();
            $input = Yii::$app->request->post('Users');
            $password = is_array($input) && isset($input['password_hash']) ? (string) $input['password_hash'] : '';
            if($user != null && $password === '')
                Yii::$app->session->setFlash('status','2');
            else if($user != null)
            {
                $user->password_hash = Yii::$app->security->generatePasswordHash($password);
                if($user->save())
                {
                    Yii::$app->session->setFlash('status','11');
                    // حساب مدرس (mentor) با همین نام کاربری هم هم‌زمان تغییر می‌کند
                    $teacher = Admin::find()->where(['username' => $user->username])->andWhere(['mentor' => true])->one();
                    if($teacher != null)
                    {
                        $teacher->password_hash = Yii::$app->security->generatePasswordHash($password);
                        $teacher->save();
                    }
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * دانشپذیرِ ارسال‌شده در Users[_id] را فقط اگر کاربر جاری به او دسترسی داشته باشد
     * برمی‌گرداند؛ در غیر این صورت پیام «عدم دسترسی» ثبت و null برمی‌گردد (جلوگیری از IDOR).
     *
     * @return Users|null
     */
    private function findManagedStudent()
    {
        $input = Yii::$app->request->post('Users');
        $id = is_array($input) && isset($input['_id']) ? $input['_id'] : null;
        $user = StudentAccess::findStudent($id);
        if($user == null)
            Yii::$app->session->setFlash('status', '12');
        return $user;
    }
}
