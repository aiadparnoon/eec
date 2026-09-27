<?php

namespace frontend\controllers;

use Yii;
use app\models\TestsGroups;
use app\models\TestsGroupsSearch;
use app\models\Colleges;
use app\models\QuestionsBank;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

/**
 * BuyersController implements the CRUD actions for Exams model.
 */
class TestsGroupsController extends Controller
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
                        'actions' => ['index', 'new', 'edit', 'edit_options','manage-questions','manage-survey','change_status','create_question','delete_question','edit_question','show_question_detail','create','edit2','read-xml-file'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search('test-maker', Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
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
        $searchModel = new TestsGroupsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        $colleges = Colleges::find()->all();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges,function($model){
                return (string) $model->_id;
            },'title')
        ]);
    }
    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $find = TestsGroups::find()->where(['title' => Yii::$app->request->post()['TestsGroups']['title']])->andWhere(['college' => Yii::$app->request->post()['TestsGroups']['college']])->all();
            if($find == null)
            {
                $model = new TestsGroups();
                if($model->load(Yii::$app->request->post()))
                {
                    if($model->save())
                        Yii::$app->session->setFlash('status','1');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
            else
                Yii::$app->session->setFlash('status','3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $find = TestsGroups::findOne(Yii::$app->request->post()['TestsGroups']['_id']);
            if($find != null)
            {
                if($find->load(Yii::$app->request->post()))
                {
                    if($find->save())
                        Yii::$app->session->setFlash('status','4');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit_question()
    {
        if (Yii::$app->request->isPost)
        {
            $find = QuestionsBank::findOne(Yii::$app->request->post()['QuestionsBank']['_id']);
            if ($find != null)
            {
                $preImage = $find->image;
                $find->load(Yii::$app->request->post());
                if ($_FILES['QuestionsBank']['name']['image'] != '')
                {
                    if ($find->image != '' && $find->image != null)
                        unlink('../../frontend/web/upload_center/' . $preImage);
                    $file1 = UploadedFile::getInstance($find, 'image');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/upload_center/' . $file1_name);
                    if (UploadedFile::getInstance($find, 'image') != null)
                    {
                        $profile = $file1_name;
                        $find->image = $profile;
                    }
                }
                else
                    $find->image = $preImage;
                if ($find->save())
                    Yii::$app->session->setFlash('status', '5');
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
    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function number_questions($group)
    {
        return QuestionsBank::find()->where(['group' => $group])->count();
    }

    public function actionReadXmlFile()
    {
        if(Yii::$app->request->isPost)
        {
            $group = TestsGroups::findOne(Yii::$app->request->post()['TestsGroups']['_id']);
            if($group != null)
            {
                if ($_FILES['TestsGroups']['name']['xml'] != '')
                {
                    $file = UploadedFile::getInstance($group, 'xml');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/upload_xmls/' . $file_name);
                    if (UploadedFile::getInstance($group, 'xml') != null)
                    {
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://api-eec.ut.ac.ir/exams/xml-to-json?filename='.$file_name,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                        ));
                        $response = curl_exec($curl);
                        curl_close($curl);
                        $response = json_decode($response);
                        $error = false;
                        if(property_exists($response, 'statusCode'))
                            if($response->statusCode != 500)
                                $error = true;
                        if(!$error)
                        {
                            $counter = 0;
                            foreach ($response->data as $data)
                            {
                                $model = new QuestionsBank();
                                $model->question_text= $data->question_text	;
                                $model->from_xml = $data->from_xml;
                                $model->group = (string) $group->_id;
                                $model->level = $data->level;
                                $model->image = $data->image;
                                $model->options = $data->options;
                                $model->type = $data->type;
                                $model->save();
                                $counter++;
                            }
                            if(file_exists('../../frontend/web/upload_xmls/' . $file_name))
                                unlink('../../frontend/web/upload_xmls/' . $file_name);
                            Yii::$app->session->setFlash('status','5');
                        }
                        else
                            Yii::$app->session->setFlash('status','6');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
}
