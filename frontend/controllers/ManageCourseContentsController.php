<?php

namespace frontend\controllers;

use Yii;
use app\models\Courses;
use app\models\CoursesSearch;
use app\models\Colleges;
use app\models\Principals;
use app\models\Brokers;
use app\models\Teachers;
use app\models\Lessons;
use app\models\CoursesContents;
use app\models\CoursesContentsSearch;
use app\models\UploadCenter;
use app\models\Exams;
use app\models\Tests;
use app\models\Users;
use app\models\UserExams;
use app\models\UserTests;
use app\models\CourseSessions;
use app\models\UserAssignments;
use app\models\UserExamsSearch;
use common\models\Admin;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;

require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

/**
 * CoursesController implements the CRUD actions for Courses model.
 */
class ManageCourseContentsController extends Controller
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
                        'actions' => ['index', 'new_title', 'new_video_from_upload_center', 'new_direct_video', 'new_exam', 'new_survey', 'new_direct_workout', 'new_workout_from_upload_center', 'delete_event', 'new_message', 'new_file_from_upload_center', 'new_direct_file', 'edit_title', 'go-to-class', 'teacher-part', 'manage-lesson', 'file', 'go-to-archive', 'poll_report','session_report','correct-questions','add_question_correct','go_to_user_exam','exam_report','download_file','download_exercise'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (!Yii::$app->user->isGuest)
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
     * Lists all CoursesContent models.
     * @return mixed
     */
    public function actionIndex($_id)
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
                    if ($user->role == 'user' || $user->role == 'cnt' || $user->role == 'emp' || $user->role == 'brokers')
                        $lessons = $courseDetail->lessons;
                    else if ($user->role == 'teacher')
                    {
                        $teacherDetail = Teachers::find()->where(['mobile' => $user->username])->one();
                        if ($teacherDetail != null) {
                            foreach ($courseDetail->lessons as $lesson)
                                if ($lesson['teachers'] == (string) $teacherDetail->_id)
                                    array_push($lessons, $lesson);
                        }
                    }
                    if ($user->role == 'user' || $user->role == 'cnt')
                    {
                        $uploadCenter = UploadCenter::find()->all();
                        $exams = Exams::find()->all();
                    } else {
                        $uploadCenter = UploadCenter::find()->where(['registrant' => $user->username])->all();
                        $exams = Exams::find()->where(['registrant' => $user->username])->all();
                    }
                    return $this->render('index', [
                        'courseDetail' => $courseDetail,
                        'lessons' => $lessons,
                        'uploadCenter' => ArrayHelper::map($uploadCenter, 'file', 'title'),
                        'exams' => ArrayHelper::map($exams, function ($model) {
                            return (string) $model->_id;
                        }, 'title'),
                    ]);
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }

    public function actionDownload_file($filename)
    {
        // امنیتی: نام فایل مستقیم از درخواست می‌آید. بدون این بررسی، ورودی
        // «../../config/main-local.php» کلید cookieValidationKey را برمی‌گرداند.
        $path = \app\components\SecureFile::resolve('upload_center', $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    public function actionDownload_exercise($filename, $course_id, $assignment_id)
    {
        // امنیتی: هر سه پارامتر از URL می‌آیند و همگی جزئی از مسیر می‌شوند،
        // پس علاوه بر نام فایل، شناسه‌های دوره و تمرین هم اعتبارسنجی می‌شوند.
        $courseId     = \app\components\SecureFile::segment($course_id);
        $assignmentId = \app\components\SecureFile::segment($assignment_id);
        if($courseId === null || $assignmentId === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');

        $path = \app\components\SecureFile::resolve('user_assignments/' . $courseId . '/' . $assignmentId, $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    public function actionFile($assignment_id, $course_id)
    {
        $adminRole = Admin::find()->where(['role' => 'user'])->one();
        if ($adminRole != null) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => Yii::getAlias('@baseUrl') . '/adobe-connect/archive-assignments/' . $assignment_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    '_id: ' . (string) $adminRole->_id
                ),
            ));
            $response = curl_exec($curl);
            $response = json_decode($response);
            curl_close($curl);
            // امنیتی: course_id و assignment_id از URL می‌آیند و data از پاسخ سرویس بیرونی؛
            // هیچ‌کدام نباید مستقیم به مسیر فایل تبدیل شوند.
            $courseId     = \app\components\SecureFile::segment($course_id);
            $assignmentId = \app\components\SecureFile::segment($assignment_id);
            if($courseId === null || $assignmentId === null)
                throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');

            $path = \app\components\SecureFile::resolve(
                'user_assignments/' . $courseId . '/' . $assignmentId,
                isset($response->data) ? $response->data : ''
            );
            if($path === null)
                throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
            return Yii::$app->response->sendFile($path, basename($path));
        }
    }
    public function actionNew_title()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::find()->where(['title' => Yii::$app->request->post()['CoursesContents']['title']])->andWhere(['course_id' => Yii::$app->request->post()['CoursesContents']['course_id']])->andWhere(['lesson_id' => Yii::$app->request->post()['CoursesContents']['lesson_id']])->one();
            if ($find == null) {
                $model = new CoursesContents();
                $model->load(Yii::$app->request->post());
                $model->registrant = Yii::$app->user->identity->username;
                if ($model->save())
                    Yii::$app->session->setFlash('status', '1');
                else
                    Yii::$app->session->setFlash('status', '2');
            } else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_video_from_upload_center()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $download = false;
                if (isset($_POST['download']))
                    $download = true;
                $content = array(
                    'type' => '1',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username,
                    'download' => $download
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_direct_video()
    {
        if (Yii::$app->request->isPost)
        {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                //                $file = UploadedFile::getInstance($find, 'content[content]');
                //                $file_ext = $file->extension;
                //                $file_name = uniqid() . '.' . $file_ext;
                //                $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                //                UploadedFile::getInstance($find, 'content[content]');
                if (Yii::$app->user->identity->role != 'teacher')
                {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                }
                else
                    $teacher = Yii::$app->user->identity->username;
                $download = false;
                if (isset($_POST['download']))
                    $download = true;
                $content = array(
                    'type' => '1',
                    //                        'content' => $file_name,
                    'content' => Yii::$app->request->post('file_name'),
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username,
                    'download' => $download
                );
                if ($find->content == null)
                {
                    $newContent[0] = $content;
                    $find->content = $newContent;
                }
                else
                {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                    $find->content = $newContent;
                }
                if ($find->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_exam()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $content = array(
                    'type' => '2',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '5');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_survey()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $content = array(
                    'type' => '3',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '11');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_workout_from_upload_center()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $reaction = false;
                if (isset($_POST['reaction']))
                    $reaction = true;
                $content = array(
                    'type' => '4',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'reaction' => $reaction,
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '6');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_direct_workout()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $reaction = false;
                if (isset($_POST['reaction']))
                    $reaction = true;
                //                $file = UploadedFile::getInstance($find, 'content[content]');
                //                $file_ext = $file->extension;
                //                $file_name = uniqid() . '.' . $file_ext;
                //                $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                //                if(UploadedFile::getInstance($find, 'content[content]'))
                $content = array(
                    'type' => '4',
                    'content' => Yii::$app->request->post('file_name'),
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'reaction' => $reaction,
                    'registrant' => Yii::$app->user->identity->username
                );

                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '6');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionDelete_event()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                $content = $find->content;
                unset($content[$_POST['row']]);
                $newContent = array_values($content);
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '7');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_message()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $content = array(
                    'type' => '5',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '8');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_file_from_upload_center()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $content = array(
                    'type' => '6',
                    'content' => Yii::$app->request->post()['CoursesContents']['content']['content'],
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                }
                $find->content = $newContent;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '9');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionNew_direct_file()
    {
        if (Yii::$app->request->isPost) {
            $find = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($find != null) {
                //                $file = UploadedFile::getInstance($find, 'content[content]');
                //                $file_ext = $file->extension;
                //                $file_name = uniqid() . '.' . $file_ext;
                //                $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                //                UploadedFile::getInstance($find, 'content[content]');
                if (Yii::$app->user->identity->role != 'teacher') {
                    $teacherDetail = Teachers::findOne(Yii::$app->request->post()['CoursesContents']['content']['teacher_id']);
                    $teacher = $teacherDetail->mobile;
                } else
                    $teacher = Yii::$app->user->identity->username;
                $content = array(
                    'type' => '6',
                    'content' => Yii::$app->request->post('file_name'),
                    'date' => time(),
                    'teacher_id' => $teacher,
                    '_id' => uniqid(),
                    'title' => Yii::$app->request->post()['CoursesContents']['content']['title'],
                    'registrant' => Yii::$app->user->identity->username
                );
                if ($find->content == null)
                    $newContent[0] = $content;
                else {
                    $newContent = $find->content;
                    array_push($newContent, $content);
                    $find->content = $newContent;
                }
                if ($find->save())
                    Yii::$app->session->setFlash('status', '9');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit_title()
    {
        if (Yii::$app->request->isPost) {
            $model = CoursesContents::findOne(Yii::$app->request->post()['CoursesContents']['_id']);
            if ($model != null) {
                $model->title = Yii::$app->request->post()['CoursesContents']['title'];
                if ($model->save())
                    Yii::$app->session->setFlash('status', '10');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function actionGoToClass()
    {
        $user = Yii::$app->user->identity;
        if($user->role != 'cnt' && $user->role != 'broker')
        {
            $detail = null;
            if ($user->role == 'user')
            {
                $detail = Admin::find()->where(['username' => $user->username])->one();
                // اطلاعات مدیر ادوبی از تنظیمات سایت › سرورها (دیگر در کد/پیکربندی نیست)
                $adobeServer = self::adobeServer();
                $username = $adobeServer !== null ? $adobeServer->setting('username') : Yii::getAlias('@adobe_user');
                $password = $adobeServer !== null ? $adobeServer->setting('password') : Yii::getAlias('@adobe_password');
            }
            else if ($user->role == 'teacher' || $user->role == 'emp' || $user->role == 'broker')
            {
                $detail = Principals::find()->where(['username' => $user->username])->one();
                $username = $detail->username;
                $password = $detail->password;
//                $password = (string) Yii::$app->user->identity->_id;
//                $username = Yii::$app->user->identity->username;
            }
            if ($detail != null)
            {
                // Call AdobeConnect For Login
                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                if ($adminRole != null)
                {
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => self::adobeApiUrl() . '/adobe-connect/login',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode(['username' => (string) $username, 'password' => (string) $password]),
                        CURLOPT_HTTPHEADER => array(
                            '_id: ' . (string) $adminRole->_id,
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response);
                    curl_close($curl);
                    if (property_exists($response, 'status')) {
                        if ($response->status == 'ok')
                            return $this->redirectToClass($response->data);
                        else {
                            Yii::$app->session->setFlash('status', '2');
                            return $this->redirect(Yii::$app->request->referrer);
                        }
                    } else {
                        Yii::$app->session->setFlash('status', '2');
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                }
                // Call AdobeConnect For Login
            }
        }
        else
            Yii::$app->getResponse()->redirect(['dashboard']);
    }
    public function actionGoToArchive()
    {
        $user = Yii::$app->user->identity;
        if($user->role != 'cnt')
        {
            if ($user->role == 'user')
            {
                $detail = Admin::find()->where(['username' => $user->username])->one();
                // اطلاعات مدیر ادوبی از تنظیمات سایت › سرورها (دیگر در کد/پیکربندی نیست)
                $adobeServer = self::adobeServer();
                $username = $adobeServer !== null ? $adobeServer->setting('username') : Yii::getAlias('@adobe_user');
                $password = $adobeServer !== null ? $adobeServer->setting('password') : Yii::getAlias('@adobe_password');
            }
            else if ($user->role == 'teacher' || $user->role == 'emp')
            {
                if ($user->mentor == true) {
                    $detail = Principals::find()->where(['username' => $user->username])->one();
                    $username = $detail->username;
                    $password = $detail->password;
                }
            }
            if ($detail != null)
            {
                // Call AdobeConnect For Login
                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                if ($adminRole != null) {
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => self::adobeApiUrl() . '/adobe-connect/login',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => json_encode(['username' => (string) $username, 'password' => (string) $password]),
                        CURLOPT_HTTPHEADER => array(
                            '_id: ' . (string) $adminRole->_id,
                            'Content-Type: application/json'
                        ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response);
                    curl_close($curl);
                    if (property_exists($response, 'status')) {
                        if ($response->status == 'ok')
                            return $this->redirectToClass($response->data);
                        else {
                            Yii::$app->session->setFlash('status', '2');
                            return $this->redirect(Yii::$app->request->referrer);
                        }
                    } else {
                        Yii::$app->session->setFlash('status', '2');
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                }
                // Call AdobeConnect For Login
            }
        }
        else
            Yii::$app->getResponse()->redirect(['dashboard']);
    }
    /**
     * سرور ادوبی کانکت برای ورود به کلاس/آرشیو (سرور پیش‌فرض از نوع ادوبی).
     *
     * @return \app\models\ClassroomServers|null
     */
    private static function adobeServer()
    {
        $server = \app\models\ClassroomServers::defaultServer();
        if ($server === null || $server->type !== \app\models\ClassroomServers::TYPE_ADOBE)
            $server = \app\models\ClassroomServers::find()->where(['type' => \app\models\ClassroomServers::TYPE_ADOBE, 'active' => true])->one();
        return $server;
    }

    private static function adobeApiUrl()
    {
        $server = self::adobeServer();
        $url = $server !== null ? rtrim($server->setting('api_url'), '/') : '';
        return $url !== '' ? $url : Yii::getAlias('@baseUrl');
    }

    /**
     * امنیتی: courseUrl از آدرس صفحه می‌آید و به انتهای دامنه‌ی ادوبی چسبانده می‌شد؛ مقداری مثل
     * «@evil.com/» کاربر را همراه session ادوبی به سایت دیگری می‌برد. فقط مسیر نسبی ساده پذیرفته می‌شود.
     */
    private function redirectToClass($session)
    {
        $path = (string) Yii::$app->request->get('courseUrl', '');
        $server = self::adobeServer();
        $host = $server !== null ? rtrim($server->setting('url'), '/') : '';
        if ($host === '')
            $host = 'https://eecvclass1.ut.ac.ir';
        if (!preg_match('~^/[A-Za-z0-9_\-/]*$~', $path) || strpos($path, '//') !== false) {
            Yii::$app->session->setFlash('status', '2');
            return $this->redirect(\app\components\SafeRedirect::referrer(['index']));
        }
        return $this->redirect($host . $path . '?session=' . rawurlencode((string) $session));
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }
    public function lesson_contents($courseId, $lessonId)
    {
        return CoursesContents::find()->where(['course_id' => $courseId])->andWhere(['lesson_id' => $lessonId])->all();
    }
    public function actionTeacherPart($_id)
    {
        if (isset($_GET['_id']))
        {
            $courseDetail = Courses::findOne($_id);
            if ($courseDetail != null)
            {
                if($this->allow($courseDetail->college))
                {
                    $lessons = array();
                    $user = Yii::$app->user->identity;
                    if ($user->role == 'user' || $user->role == 'cnt' || $user->role == 'emp' || $user->role == 'broker')
                        $lessons = $courseDetail->lessons;
                    else if ($user->role == 'teacher')
                    {
                        if ($user->mentor == true)
                            $lessons = $courseDetail->lessons;
                        else {
                            $teacherDetail = Teachers::find()->where(['mobile' => $user->username])->one();
                            if ($teacherDetail != null)
                            {
                                foreach ($courseDetail->lessons as $lesson)
                                    if ($lesson['teachers'] == (string) $teacherDetail->_id)
                                        array_push($lessons, $lesson);
                                if($courseDetail->other_teachers != null)
                                    if(is_array($courseDetail->other_teachers))
                                        if(in_array((string) $teacherDetail->_id, $courseDetail->other_teachers))
                                            $lessons = $courseDetail->lessons;
                            }
                        }
                    }
                    return $this->render('teacher-part', [
                        'courseDetail' => $courseDetail,
                        'lessons' => $lessons,
                    ]);
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }
    public function actionManageLesson($courseId, $lessonId)
    {
        if (isset($_GET['courseId']) && isset($_GET['lessonId']))
        {
            $courseDetail = Courses::findOne($courseId);
            if ($courseDetail != null)
            {
                if($this->allow($courseDetail->college))
                {
                    $lesson = Lessons::findOne($lessonId);
                    if ($lesson != null) {
                        $index = 0;
                        $i = 0;
                        foreach ($courseDetail->lessons as $item) {
                            if ($item['_id'] == $lessonId)
                                $index = $i;
                            $i++;
                        }
                        $lessonsContents = CoursesContents::find()->where(['course_id' => $courseId])->andWhere(['lesson_id' => $lessonId])->all();
                        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') {
                            $uploadCenter = UploadCenter::find()->all();
                            $exams = Tests::find()->all();
                            $surveys = Exams::find()->where(['type' => '2'])->all();
                        } else {
                            if (Yii::$app->user->identity->mentor == true) {
                                $teacher = $courseDetail->lessons[$index]['teachers'];
                                $teacherDetail = Teachers::findOne($teacher);
                                $uploadCenter = UploadCenter::find()->where(['registrant' => [Yii::$app->user->identity->username, $teacherDetail->mobile]])->all();
                                $exams = Tests::find()->where(['registrant' => [Yii::$app->user->identity->username, $teacherDetail->mobile]])->all();
                                $surveys = Exams::find()->where(['registrant' => [Yii::$app->user->identity->username, $teacherDetail->mobile]])->andWhere(['type' => '2'])->all();
                            } else {
                                $uploadCenter = UploadCenter::find()->where(['registrant' => Yii::$app->user->identity->username])->all();
                                $exams = Tests::find()->where(['college' => Yii::$app->user->identity->college])->all();
                                $surveys = Exams::find()->where(['registrant' => Yii::$app->user->identity->username])->andWhere(['type' => '2'])->all();
                            }
                        }
                        return $this->render('manage-lesson', [
                            'courseDetail' => $courseDetail,
                            'lessonDetail' => $lesson,
                            'lessonsContents' => $lessonsContents,
                            'lesson' => $courseDetail->lessons[$index],
                            'uploadCenter' => ArrayHelper::map($uploadCenter, 'file', 'title'),
                            'exams' => ArrayHelper::map($exams, function ($model) {
                                return (string) $model->_id;
                            }, 'title'),
                            'surveys' => ArrayHelper::map($surveys, function ($model) {
                                return (string) $model->_id;
                            }, 'title'),
                        ]);
                    }
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }
    public function actionCorrectQuestions($_id)
    {
        if (isset($_GET['_id']))
        {
            $userTest = UserTests::findOne($_id);
            if ($userTest != null)
            {
                $user = Users::find()->where(['username' => $userTest->username])->one();
                if($user != null)
                {
                    $test = Tests::findOne($userTest->exam_id);
                    if($test != null)
                    {
                        $finalScore = 0;
                        $maxScore = 0;
                        $totalScore = 0;
                        foreach ($userTest->questions as $try)
                        {
                            if($try['score'] > $maxScore)
                                $maxScore = $try['score'];
                            $totalScore += $try['score'];
                            $countQuestions = count($userTest->questions);
                        }
                        if($test->score_type == '1')
                            $finalScore = $totalScore / $countQuestions;
                        else if($test->score_type == '2')
                            $finalScore = $maxScore;
                        $otherUser = UserTests::find()->where(['course_id' => $userTest->course_id])->andWhere(['assignment_id' => $userTest->assignment_id])->andWhere(['exam_id' => $userTest->exam_id])->andWhere(['lesson_id' => $userTest->lesson_id])->andWhere(['<>','_id',$userTest->_id])->orderBy(['corrected' => SORT_ASC])->all();
                        $otherUser = ArrayHelper::map($otherUser,function ($model){
                            return (string) $model->_id;
                        },function ($model){
                            $userDetail = Users::find()->where(['username' => $model->username])->one();
                            $corrected = '';
                            if($model->corrected == true)
                                $corrected = '(تصحیح شده)';
                            return $userDetail->first_name.' '.$userDetail->last_name.' '.$corrected;
                        });
                        return $this->render('correct-questions', [
                            'userTest' => $userTest,
                            'userDetail' => $user,
                            'testDetail' => $test,
                            'finalScore' => $finalScore,
                            'otherUser' => $otherUser
                        ]);
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    public function lesson_sessions($courseId, $lessonId)
    {
        return CourseSessions::find()->where(['course_id' => $courseId])->andWhere(['lesson_id' => $lessonId])->one();
    }
    public function digit2word($num)
    {
        $num        = (string)$num;
        $one = array('', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه');
        $ten = array('', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود',);
        $hundred = array('', 'یکصد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد',);
        $categories = array('', 'هزار', 'میلیون', 'میلیارد', 'بیلیون', 'بیلیارد', 'تریلیون', 'تریلیارد', 'کوآدریلیون',);
        $exceptions = array('ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده',);
        $out = '';
        $j   = 0;
        $cnt = strlen($num);
        if ($cnt == 1)
            return $one[$num];
        for ($i = --$cnt; $i >= 0; $i -= 3) {
            $add = '';
            $i1 = $num[$i];
            $i2 = isset($num[$i - 1]) ? $num[$i - 1] : '';
            $i3 = isset($num[$i - 2]) ? $num[$i - 2] : '';
            if (!empty($i3))
                $add .= $hundred[$i3] . ' و ';
            if ($i2 > 1)
                $add .= $ten[$i2] . ' و ' . $one[$i1] . ' ';
            elseif ($i2 == 1)
                $add .= $exceptions[$i1] . ' ';
            else
                $add .= $one[$i1] . ' ';
            if ($add != ' ')
                $add .= $categories[$j++] . ' و ';
            else
                $j++;
            $out = $add . $out;
        }
        return mb_substr($out, 0, -4);
    }
    public function exam_result($assignmentsId)
    {
        return UserExams::find()->where(['assignments_id' => $assignmentsId])->all();
    }
    public function test_detail($_id)
    {
        return Tests::findOne($_id);
    }

    public function user_detail($username)
    {
        return Users::find()->where(['username' => $username])->one();
    }

    public function user_id($_id)
    {
        return Users::find()->where(['_id' => $_id])->one();
    }
    public function particle_result($assignmentsId)
    {
        return UserAssignments::find()->where(['assignment_id' => $assignmentsId])->all();
    }
    public function actionPoll_report($assignments_id)
    {
        $polls = UserExams::find()->where(['assignments_id' => $assignments_id])->all();
        if ($polls == null)
        {
            return $this->redirect(Yii::$app->request->referrer);
        }

        // date_default_timezone_set('Asia/Tehran');
        // require_once(Yii::$app->basePath . '/web/jdf.php');
        // Yii::$app->setTimeZone('Asia/Tehran');
        // $searchModel = new UserExamsSearch();
        // $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $assignments_id);

        $columns = [
            ['attribute' => 'کاربر']
        ];
        $rows = [];

        $exam = Exams::find()->where(['_id' => $polls[0]->exam_id])->one();

        foreach ($exam->questions as $item) {
            array_push($columns, ['attribute' => $item['question_text']]);
        }

        foreach ($polls as $poll) {
            $userDetail = Users::findOne($poll->user_id);
            $currentRow = ["کاربر"  => $userDetail->first_name.' '.$userDetail->last_name];

            foreach ($poll->questions[0]['questions'] as $item) {
                $userAnswer = "نامشخص";

                if ($item['user_answer'] == '1')  $userAnswer = $item['first_option'];
                if ($item['user_answer'] == '2')  $userAnswer = $item['second_option'];
                if ($item['user_answer'] == '3')  $userAnswer = $item['third_option'];
                if ($item['user_answer'] == '4')  $userAnswer = $item['fourth_option'];
                $currentRow[$item['question_text']] = $userAnswer;
            }

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
        $file_name = 'Poll-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionExam_report($assignments_id)
    {
        $exam = UserTests::find()->where(['assignment_id' => $assignments_id])->all();
        if($exam != null)
        {
            $columns = [];
            array_push($columns, ['attribute' => 'نام و نام خانوادگی']);
            array_push($columns, ['attribute' => 'نام کاربری']);
            array_push($columns, ['attribute' => 'نتیجه آزمون']);
            array_push($columns, ['attribute' => 'وضعیت']);
            $rows = [];
            foreach ($exam as $item)
            {
                $userDetail = $this->user_detail($item->username);
                if($userDetail != null)
                {
                    $fullName = $userDetail->first_name.' '.$userDetail->last_name;
                    if($item->questions != null)
                    {
                        $tryCounter = 1;
                        $sumScore = 0;
                        $maxScore = 0;
                        foreach ($item->questions as $try)
                        {
                            $testDetail = $this->test_detail($item->exam_id);
                            if($testDetail->score_type != null)
                            {
                                if($testDetail->score_type == '1')
                                    $sumScore += $try['score'];
                                else if($try['score'] > $maxScore)
                                    $maxScore = $try['score'];
                            }
                            if($try['questions'] != null)
                            {
                                $descriptiveFlag = false;
                                foreach ($try['questions'] as $examQuestion)
                                    if(array_key_exists('type', $examQuestion))
                                        if($examQuestion['type'] == '3')
                                            $descriptiveFlag = true;
                            }
                            if($testDetail->score_type == '1')
                                $finalScore = $sumScore / count($item->questions);
                            else
                                $finalScore = $maxScore;
                            $examStatus = 'رد شده';
                            if($finalScore >= $testDetail->pass_score)
                                $examStatus = 'پاس شده';
                        }
                    }
                    $currentRow = ["نام و نام خانوادگی"  => $fullName, "نام کاربری"  => $item->username, "نتیجه آزمون"  => round($finalScore,1), "وضعیت"  => $examStatus];
                    array_push($rows, $currentRow);
                }
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
            $file_name = 'ExamResul -'. jdate('Y/m/d-H:i:s') . '.xlsx';
            return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
        }
    }
    public function actionSession_report($_id, $row, $counter)
    {
        $session = CourseSessions::findOne($_id);
        if ($session == null)
        {
            return $this->redirect(Yii::$app->request->referrer);
        }

        $columns = [];
        array_push($columns, ['attribute' => 'کاربر']);
        array_push($columns, ['attribute' => 'نام کاربری']);
        array_push($columns, ['attribute' => 'ورود']);
        array_push($columns, ['attribute' => 'خروج']);
        $rows = [];
        foreach ($session->sessions[$row]['participants'] as $item)
        {
            $user = Principals::find()->where(['principal_id' => $item['_id']])->one();
            if($user != null)
                $username = $user->username;
            else
                $username = '-';
            $name = $item['name'];
            if($name == 'eec admin')
                $name = 'ادمین ادوبی';
            $enter = strtotime($item['enter']);
            $exit =  strtotime($item['exit']);
            $currentRow = ["کاربر"  => $name, "نام کاربری"  => $username, "ورود"  => jdate('H:i:s', $enter), "خروج"  => jdate('H:i:s', $exit)];
//            $currentRow = ["ورود"  => jdate('H:i:s', $enter)];
//            $currentRow = ["خروج"  => jdate('H:i:s', $exit)];
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
        $file_name = 'Session ' . $counter .' - '. jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function teacher_detail($username)
    {
        return Teachers::find()->where(['mobile' => $username])->one();
    }

    public function actionAdd_question_correct()
    {
        if(Yii::$app->request->isPost)
        {
            $userTest = UserTests::findOne(Yii::$app->request->post('_id'));
            if($userTest != null)
            {
                $testDetail = Tests::findOne($userTest->exam_id);
                if($testDetail != null)
                {
                    $trys = $userTest->questions;
                    $myTry = $trys[Yii::$app->request->post('row1')]['questions'];
                    $preScore = $trys[Yii::$app->request->post('row1')]['score'];
                    $unitScore = $testDetail->total_score / count($myTry);
                    $row = 0;
                    $score = 0;
                    foreach ($myTry as $item)
                    {
                        if(isset($_POST[$item['_id']]))
                        {
                            if(array_key_exists('result', $myTry[$row]))
                            {
                                if(($myTry[$row]['result'] == 'true') && ($_POST[$item['_id']] == 'false'))
                                    $score += -$unitScore;
                                if(($myTry[$row]['result'] == 'false') && ($_POST[$item['_id']] == 'true'))
                                    $score += $unitScore;
                            }
                            else if($_POST[$item['_id']] == 'true')
                                $score += $unitScore;
                            $myTry[$row]['result'] = $_POST[$item['_id']];
                        }
                        $row++;
                    }
                    $trys[Yii::$app->request->post('row1')]['questions'] = $myTry;
                    $trys[Yii::$app->request->post('row1')]['score'] = (string) $preScore + $score;
                    $userTest->questions = $trys;
                    $userTest->corrected = true;
                    if($userTest->save())
                        Yii::$app->session->setFlash('status','1');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionGo_to_user_exam()
    {
        if(Yii::$app->request->isPost)
        {
            $userTest = UserTests::findOne(Yii::$app->request->post()['UserTests']['_id']);
            if($userTest != null)
            {
                return $this->redirect(['manage-course-contents/correct-questions','_id' => (string) $userTest->_id]);
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function participant_username($_id)
    {
        if($_id != '')
            return Principals::find()->where(['principal_id' => $_id])->one();
        else
            return '';
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
}
