<?php

namespace frontend\controllers;

use Yii;
use app\models\Courses;
use app\models\CoursesSearch;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * BuyersController implements the CRUD actions for Buyers model.
 */
class SingleLessonController extends Controller
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
                        'actions' => ['index','new','edit'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback'=>function($rule, $action){
                            if(DashboardController::access('academy','manageBooks'))
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
        $searchModel = new CoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionNew()
    {
        if(Yii::$app->request->isPost)
        {
            $find = Books::find()->where(['title' => Yii::$app->request->post()['Books']['title']])->one();
            if($find == null)
            {
                $model = new Books();
                $model->load(Yii::$app->request->post());
                $file = UploadedFile::getInstance($model,'previewImage');
                $file_ext = $file->extension;
                $file_name = uniqid().'.'.$file_ext;
                $file->saveAs('../../backend/web/book_preview_images/'.$file_name);
                if(UploadedFile::getInstance($model,'previewImage') != null)
                    $model->previewImage = $file_name;
                $file = UploadedFile::getInstance($model,'bookFile');
                $file_ext = $file->extension;
                $file_name = uniqid().'.'.$file_ext;
                $file->saveAs('../../backend/web/book_files/'.$file_name);
                if(UploadedFile::getInstance($model,'bookFile') != null)
                    $model->bookFile = $file_name;
                $model->status = '1';
                if($model->save())
                {
                    $model->code = strtoupper(substr((string)$model->_id, -5));
                    $model->save();
                    Yii::$app->session->setFlash('status','1');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
            else
                Yii::$app->session->setFlash('status','3');
            return $this->redirect(Yii::$app->request->referrer);
        }
        else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if(Yii::$app->request->isPost)
        {
            $find = Books::findOne(Yii::$app->request->post()['Books']['_id']);
            if($find != null)
            {
                $preImage = $find->previewImage;
                $preFile = $find->bookFile;
                $find->load(Yii::$app->request->post());
                if($_FILES['Books']['name']['previewImage'] != '')
                {
                    if($find->previewImage != '' && $find->previewImage != null)
                        unlink('../../backend/web/book_preview_images/'.$find->previewImage);
                    $file1=UploadedFile::getInstance($find,'previewImage');
                    $file1_ext=$file1->extension;
                    $file1_name=uniqid().'.'.$file1_ext;
                    $file1->saveAs('../../backend/web/book_preview_images/'.$file1_name);
                    if(UploadedFile::getInstance($find,'previewImage') != null)
                    {
                        $profile = $file1_name;
                        $find->previewImage = $profile;
                    }
                }
                else
                    $find->previewImage = $preImage;
                if($_FILES['Books']['name']['bookFile'] != '')
                {
                    if($find->bookFile != '' && $find->bookFile != null)
                        unlink('../../backend/web/book_files/'.$find->bookFile);
                    $file1=UploadedFile::getInstance($find,'bookFile');
                    $file1_ext=$file1->extension;
                    $file1_name=uniqid().'.'.$file1_ext;
                    $file1->saveAs('../../backend/web/book_files/'.$file1_name);
                    if(UploadedFile::getInstance($find,'bookFile') != null)
                    {
                        $profile = $file1_name;
                        $find->bookFile = $profile;
                    }
                }
                else
                    $find->bookFile = $preFile;
                if($find->save())
                    Yii::$app->session->setFlash('status','4');
                else
                    Yii::$app->session->setFlash('status','2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }
        else
            return $this->redirect(Yii::$app->request->referrer);
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
