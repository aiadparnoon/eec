<?php

namespace frontend\controllers;

use Yii;
use app\models\News;
use app\models\NewsSearch;
use app\models\NewsFiles;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
class ManageNewsController extends Controller
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
                        'actions' => ['index', 'new_news', 'edit', 'new_file','delete','new','edit-news','add_new_file','edit_type_2'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
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
        $searchModel = new NewsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionNew()
    {
        $files = NewsFiles::find()->all();
        return $this->render('new',[
            'files' => $files
        ]);
    }

    public function actionEditNews($_id)
    {
        if(isset($_GET['_id']))
        {
            $model = News::findOne($_GET['_id']);
            if($model!= null)
                return $this->render('edit-news',[
                    'model' => $model
                ]);
        }
        return $this->redirect(['../manage-news']);
    }
    public function actionNew_news()
    {
        if(Yii::$app->request->isPost)
        {
            $model = new News();
            $model->load(Yii::$app->request->post());
            $file1 = UploadedFile::getInstance($model, 'image');
            $file1_ext = $file1->extension;
            $file1_name = uniqid() . '.' . $file1_ext;
            $file1->saveAs('../../frontend/web/news_images/' . $file1_name);
            if (UploadedFile::getInstance($model, 'image') != null)
                $model->image = $file1_name;
            $model->registrant = Yii::$app->user->identity->username;
            if($model->save())
            {
                $model->id = strtoupper(substr((string)$model->_id, -5));
                $model->save();
                Yii::$app->session->setFlash('status','1');
            }
            else
                Yii::$app->session->setFlash('status','2');
        }
        return $this->redirect(['../manage-news']);
    }

    public function actionNew_file()
    {
        if(Yii::$app->request->isPost)
        {
            $model = new News();
            $model->load(Yii::$app->request->post());
            $file1 = UploadedFile::getInstance($model, 'file');
            $file1_ext = $file1->extension;
            $file1_name = uniqid() . '.' . $file1_ext;
            $file1->saveAs('../../frontend/web/news_files/' . $file1_name);
            if (UploadedFile::getInstance($model, 'file') != null)
                $model->file = $file1_name;
            $model->type = '2';
            $model->registrant = Yii::$app->user->identity->username;
            if($model->save())
            {
                $model->id = strtoupper(substr((string)$model->_id, -5));
                $model->save();
                Yii::$app->session->setFlash('status','3');
            }
            else
                Yii::$app->session->setFlash('status','2');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAdd_new_file()
    {
        if(Yii::$app->request->isPost)
        {
            $model = new NewsFiles();
            $model->load(Yii::$app->request->post());
            $file1 = UploadedFile::getInstance($model, 'file');
            $file1_ext = $file1->extension;
            $file1_name = uniqid() . '.' . $file1_ext;
            $file1->saveAs('../../frontend/web/news_files/' . $file1_name);
            if (UploadedFile::getInstance($model, 'file') != null)
                $model->file = $file1_name;
            $model->registrant = Yii::$app->user->identity->username;
            if($model->save())
                Yii::$app->session->setFlash('status','6');
            else
                Yii::$app->session->setFlash('status','2');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $news = News::findOne(Yii::$app->request->post()['News']['_id']);
            if ($news != null)
            {
                $preimage = $news->image;
                $news->load(Yii::$app->request->post());
                if ($_FILES['News']['name']['image'] != '')
                {
                    if ($preimage != '' && $preimage != null)
                        unlink('../../frontend/web/news_images/' . $preimage);
                    $file1 = UploadedFile::getInstance($news, 'image');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/news_images/' . $file1_name);
                    if (UploadedFile::getInstance($news, 'image') != null)
                        $news->image = $file1_name;
                }
                else
                    $news->image = $preimage;
                if ($news->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(['../manage-news']);
    }

    public function actionEdit_type_2()
    {
        if (Yii::$app->request->isPost)
        {
            $news = News::findOne(Yii::$app->request->post()['News']['_id']);
            if ($news != null)
            {
                $preimage = $news->file;
                $news->load(Yii::$app->request->post());
                if ($_FILES['News']['name']['file'] != '')
                {
                    if ($preimage != '' && $preimage != null)
                        unlink('../../frontend/web/news_files/' . $preimage);
                    $file1 = UploadedFile::getInstance($news, 'file');
                    $file1_ext = $file1->extension;
                    $file1_name = uniqid() . '.' . $file1_ext;
                    $file1->saveAs('../../frontend/web/news_files/' . $file1_name);
                    if (UploadedFile::getInstance($news, 'file') != null)
                        $news->file = $file1_name;
                }
                else
                    $news->file = $preimage;
                if ($news->save())
                    Yii::$app->session->setFlash('status', '4');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
        }
        return $this->redirect(['../manage-news']);
    }

    public function actionDelete()
    {
        if (Yii::$app->request->isPost)
        {
            $news = News::findOne(Yii::$app->request->post()['News']['_id']);
            if ($news != null)
            {
                if($news->type == '1' || $news->type == '3')
                {
                    $file = $news->image;
                    unlink('../../frontend/web/news_images/' . $file);
                }
                else
                {
                    $file = $news->file;
                    unlink('../../frontend/web/news_files/' . $file);
                }
                if ($news->delete())
                    Yii::$app->session->setFlash('status', '5');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
            return $this->redirect(Yii::$app->request->referrer);
    }
}
