<?php

namespace frontend\controllers;

use Yii;
use app\models\Colleges;
use app\models\CollegesSearch;
use yii\filters\AccessControl;
use app\models\Courses;
use app\models\College;
use app\models\Users;
use app\models\CollegeCoursesSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii2tech\spreadsheet\Spreadsheet;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

/**
 * CollagesManageController implements the CRUD actions for Colleges model.
 */
//class CollagesManageController extends BaseController
class CollagesManageController extends Controller
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
                        'actions' => ['index', 'report', 'new', 'edit', 'report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (in_array(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
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
        $searchModel = new CollegesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 30;
        $years = array();
        for ($i = 1401; $i <= jdate('Y'); $i++)
            array_push($years, $i);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'years' => $years,
        ]);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new Colleges();
            $find = Colleges::find()->where(['title' => Yii::$app->request->post()['Colleges']['title']])->andWhere(['prefix' => Yii::$app->request->post()['Colleges']['prefix']])->one();
            if ($find == null)
            {
                $model = new Colleges();
                $model->load(Yii::$app->request->post());

                $file = UploadedFile::getInstance($model, 'logo');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/college_logos/' . $file_name);
                if (UploadedFile::getInstance($model, 'logo') != null)
                    $model->logo = $file_name;

                if ($_FILES['Colleges']['name']['signature_file'] != '')
                {
                    $file = UploadedFile::getInstance($model, 'signature_file');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/college_logos/' . $file_name);
                    if (UploadedFile::getInstance($model, 'signature_file') != null)
                        $model->signature_file = $file_name;
                }

                $model->status = '1';
                if ($model->save())
                    Yii::$app->session->setFlash('status', '1');
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
        $searchModel = new CollegeCoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, Yii::$app->request->post('year'), Yii::$app->request->post('college'));
        $dataProvider->pagination = false;
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => function($model){
                        return ' '.$model->title['main_fa'].' ';
                    },
                    'header' => ' نام دوره'
                ],
                [
                    'attribute' => function($model)
                    {
                        $users = Users::find()->where(['courses._id' => (string) $model->_id])->count();
                        return $users;
                    },
                    'header' => 'تعداد شرکت کنندگان'
                ],
            ],
        ]);
        $exporter->save('./newfile.xlsx');
        $file_name = 'CollegeReport-' . Yii::$app->request->post('year') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function author_detail($_id)
    {
        return Personnel::findOne($_id);
    }

    public function lesson_detail($_id)
    {
        return Lessons::find()->where(['code' => $_id])->one();
    }
}
