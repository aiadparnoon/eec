<?php

namespace frontend\controllers;

use Yii;
use app\models\Exams;
use app\models\UserExams;
use app\models\ExamsSearch;
use app\models\Colleges;
use app\models\Courses;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * BuyersController implements the CRUD actions for Exams model.
 */
class SurveyMakerController extends Controller
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
                        'actions' => ['index', 'new', 'edit','manage-questions','manage-survey','change_status','create_question','delete_question','edit_question','manage-management-survey-result'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
//                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                            if (Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker' || (Yii::$app->user->identity->role == 'teacher' && Yii::$app->user->identity->mentor == true))
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['management-survey','new_management_survey','manage-management-survey','new_s_question','edit_s_question','edit_management_survey'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
//                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                            if (Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'user')
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
        $searchModel = new ExamsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false);
        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionManagementSurvey()
    {
        $searchModel = new ExamsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);
        $dataProvider->pagination->pageSize = 50;
        $courses = Courses::find()->where(['status' => '1'])->orderBy(['_id' => SORT_DESC])->all();
        $courses = ArrayHelper::map($courses, function ($model){
            return (string) $model->_id;
        }, function ($model){
            return $model->title['main_fa'];
        });
        return $this->render('management-survey', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'courses' => $courses
        ]);
    }


    public function actionManageQuestions($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Exams::findOne($_GET['_id']);
            if($examDetails != null)
                return $this->render('manage-questions', [
                    'examDetails' => $examDetails
                ]);
        }
        return $this->redirect(['../survey-maker']);
    }

    public function actionManageSurvey($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Exams::find()->where(['_id' => $_GET['_id']])->andWhere(['management' => false])->one();
            if($examDetails != null)
                return $this->render('manage-survey', [
                    'examDetails' => $examDetails
                ]);
        }
        return $this->redirect(['../survey-maker']);
    }

    public function actionManageManagementSurvey($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Exams::find()->where(['_id' => $_GET['_id']])->andWhere(['management' => true])->one();
            if($examDetails != null)
                return $this->render('manage-management-survey', [
                    'examDetails' => $examDetails
                ]);
        }
        return $this->redirect(['../survey-maker']);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Exams::find()->where(['title' => Yii::$app->request->post()['Exams']['title']])->andWhere(['registrant' => Yii::$app->user->identity->username])->one();
            if ($find == null)
            {
                $model = new Exams();
                $model->load(Yii::$app->request->post());
                $model->status = '1';
                $model->management = false;
                $model->registrant = Yii::$app->user->identity->username;
                if ($model->save())
                {
                    $model->type = '2';
                    $model->save();
                    return $this->redirect(array('survey-maker/manage-survey', '_id' => (string) $model->_id));
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionNew_management_survey()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Exams::find()->where(['title' => Yii::$app->request->post()['Exams']['title']])->andWhere(['registrant' => Yii::$app->user->identity->username])->one();
            if ($find == null)
            {
                $model = new Exams();
                $model->load(Yii::$app->request->post());
                $model->status = '1';
                $model->management = true;
                $model->registrant = Yii::$app->user->identity->username;
                if ($model->save())
                {
                    $model->type = '2';
                    $model->save();
                    return $this->redirect(array('survey-maker/manage-management-survey', '_id' => (string) $model->_id));
                }
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            else
                Yii::$app->session->setFlash('status', '3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost) {
            $find = Teachers::findOne(Yii::$app->request->post()['Teachers']['_id']);
            if ($find != null) {
                $preImage = $find->profile_image;
                $find->load(Yii::$app->request->post());
                if ($_FILES['Teachers']['name']['profile_image'] != '')
                {
                    if ($find->profile_image != '' && $find->profile_image != null)
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
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit_management_survey()
    {
        if (Yii::$app->request->isPost) {
            $find = Exams::findOne(Yii::$app->request->post()['Exams']['_id']);
            if ($find != null) {
                $find->load(Yii::$app->request->post());
                if ($find->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCreate_question()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = Exams::findOne(Yii::$app->request->post('_id'));
            if($exam != null)
            {
                if($exam->questions != null)
                {
                    $questions = $exam->questions;
                    array_push($questions, Yii::$app->request->post()['Exams']['questions']);
                }
                else
                    $questions = array(
                        '0' => Yii::$app->request->post()['Exams']['questions']
                    );
                $exam->questions = $questions;
                if($exam->save())
                    Yii::$app->session->setFlash('status','1');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit_question()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = Exams::findOne(Yii::$app->request->post('_id'));
            if($exam != null)
            {
                $questions = $exam->questions;
                $questions[Yii::$app->request->post('row')] = Yii::$app->request->post()['Exams']['questions'][Yii::$app->request->post('row')];
                $exam->questions = $questions;
                if($exam->save())
                    Yii::$app->session->setFlash('status','4');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionManageManagementSurveyResult()
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Exams::find()->where(['_id' => $_GET['_id']])->andWhere(['management' => true])->one();
            if($examDetails != null)
            {
                $results = UserExams::find()->where(['exam_id' => (string) $examDetails->_id])->all();
                return $this->render('manage-management-survey-result', [
                    'examDetails' => $examDetails,
                    'userPolls' => $results
                ]);
            }
        }
        return $this->redirect(['../survey-maker']);
    }

    // در کنترلر، این تابع را اضافه کنید
    protected function getProgressBarColor($index)
    {
        $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'];
        return $colors[$index % count($colors)];
    }

    public function user_detail($_id)
    {
        return Admin::find()->where(['_id' => $_id])->one();
    }

    public function actionEdit_s_question()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = Exams::findOne(Yii::$app->request->post('_id'));
            if($exam != null)
            {
                $questions = $exam->questions;
                $questions[Yii::$app->request->post('row')] = Yii::$app->request->post()['Exams']['questions'][Yii::$app->request->post('row')];
                $exam->questions = $questions;
                if($exam->save())
                    Yii::$app->session->setFlash('status','4');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDelete_question()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = Exams::findOne(Yii::$app->request->post('_id'));
            if($exam != null)
            {
                $questions = $exam->questions;
                unset($questions[Yii::$app->request->post('row')]);
                $questions = array_values($questions);
                $exam->questions = $questions;
                if($exam->save())
                    Yii::$app->session->setFlash('status','5');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionNew_s_question()
    {
        if (Yii::$app->request->isPost)
        {
            $exam = Exams::findOne(Yii::$app->request->post()['Exams']['_id']);
            if($exam != null)
            {
                $questions = array();
                if($exam->questions != null)
                    $questions = $exam->questions;
                if(isset($_POST['questions']))
                {
                    $options = array();
                    foreach ($_POST['questions'] as $item)
                    {
                        if($item['title'] != '')
                        {
                            $tmp = array(
                                'title' => $item['title'],
                                'id' => uniqid()
                            );
                            array_push($options, $tmp);
                        }
                    }
                    if($options != null)
                    {
                        $newQuestion = array(
                            'question_text' => Yii::$app->request->post()['Exams']['questions']['questions_text'],
                            'type' => Yii::$app->request->post()['Exams']['questions']['type'],
                            'required' => Yii::$app->request->post()['Exams']['questions']['required'],
                            'options' => $options,
                        );
                        array_push($questions, $newQuestion);
                        $exam->questions = $questions;
                        if($exam->save())
                            Yii::$app->session->setFlash('status','1');
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                    else
                        Yii::$app->session->setFlash('status','2');
                }
                else
                    Yii::$app->session->setFlash('status','3');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function digit2word($i)
    {
        $ret = 'اول';
        if($i == 2)
            $ret = 'دوم';
        else if($i == 3)
            $ret = 'سوم';
        else if($i == 4)
            $ret = 'چهارم';
        else if($i == 5)
            $ret = 'پنجم';
        else if($i == 6)
            $ret = 'ششم';
        else if($i == 7)
            $ret = 'هفتم';
        else if($i == 8)
            $ret = 'هشتم';
        else if($i == 9)
            $ret = 'نهم';
        else if($i == 10)
            $ret = 'دهم';
        else if($i == 11)
            $ret = 'یازدهم';
        else if($i == 12)
            $ret = 'دوازدهم';
        else if($i == 13)
            $ret = 'سیزدهم';
        else if($i == 14)
            $ret = 'چهاردهم';
        else if($i == 15)
            $ret = 'پانزدهم';
        return $ret;
    }
}
