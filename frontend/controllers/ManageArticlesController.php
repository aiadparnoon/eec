<?php

namespace frontend\controllers;

use Yii;
use app\models\Articles;
use app\models\ArticlesSearch;
use app\models\CompletionArticle;
use app\models\CompletionSearch;
use app\models\Departments;
use app\models\UploadCenter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * ViewPostController implements the CRUD actions for Posts model.
 */
class ManageArticlesController extends Controller
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
                        'actions' => ['index', 'completion-article','add' ,'delete','edit-article','edit-completion-article','upload','update_base'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback'=>function($rule, $action){
                            if(DashboardController::access('law','manageArticles'))
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
     * Lists all Posts models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ArticlesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCompletionArticle($_id)
    {
        if(isset($_GET['_id']))
        {
            $articleDetail = Articles::findOne($_id);
            if($articleDetail != null)
            {
                $allCompletions = CompletionArticle::find()->where(['articleId' => (string) $articleDetail->_id])->all();
                $type1 = CompletionArticle::find()->where(['articleId' => (string) $articleDetail->_id])->andWhere(['type' => '1'])->all();
                $type2 = CompletionArticle::find()->where(['articleId' => (string) $articleDetail->_id])->andWhere(['type' => '2'])->all();
                $searchModel = new CompletionSearch();
                $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $_id);
                return $this->render('completion-article',[
                    'articleDetail' => $articleDetail,
                    'allCompletions' => $allCompletions,
                    'type1' => ArrayHelper::map($type1,function ($model){
                        return (string) $model->_id;
                    },'title'),
                    'type2' => ArrayHelper::map($type2,function ($model){
                        return (string) $model->_id;
                    },'title'),
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                ]);
            }
            else
                Yii::$app->response->redirect(['create-article']);
        }
        else
            Yii::$app->response->redirect(['create-article']);
    }

    public function actionAdd()
    {
        $model=new CompletionArticle();
        if($model->load(Yii::$app->request->post()))
        {
            if(isset($_POST['CompletionArticle']['tmp1']))
                $model->content = $_POST['CompletionArticle']['tmp1'];
            if(isset($_POST['CompletionArticle']['tmp2']))
                $model->content = $_POST['CompletionArticle']['tmp2'];
            if($model->save(false))
            {
                if($_FILES['CompletionArticle']['name']['image'] != null && $_FILES['CompletionArticle']['name']['image'] != '')
                {
                    $file1=UploadedFile::getInstance($model,'image');
                    $file1_ext=$file1->extension;
                    $doc=uniqid();
                    $file1_name=$doc.'.'.$file1_ext;
                    $file1->saveAs('../../backend/web/completion_article_images/'.$doc.'.'.$file1_ext);
                    if(UploadedFile::getInstance($model,'image')!=null)
                        $model->image=$file1_name;
                }
                $model->date=time();
                $model->registrant=Yii::$app->user->identity->username;
                $model->save(false);
                Yii::$app->session->setFlash('status','1');
                return $this->redirect(Yii::$app->request->referrer);
            }
            else
            {
                Yii::$app->session->setFlash('status','2');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }
        else
        {
            Yii::$app->session->setFlash('status','2');
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function registrant($id)
    {
        return 'مدیریت';
    }

    public function parent($id)
    {
        $model = CompletionArticle::findOne($id);
        return $model->title;
    }

    public function actionDelete()
    {
        if(Yii::$app->request->post())
        {
            $article = CompletionArticle::findOne(Yii::$app->request->post()['CompletionArticle']['_id']);
            if($article != null)
            {
                if($article->type == '1')
                {
                    $type2 = CompletionArticle::find()->where(['parent' => (string) $article->id])->andWhere(['type' => '2'])->all();
                    if($type2 != null)
                    {
                        foreach ($type2 as $item)
                        {
                            $type3 = CompletionArticle::find()->where(['parent' => (string) $item->id])->andWhere(['type' => '3'])->all();
                            foreach ($type3 as $value)
                                $value->delete();
                            $item->delete();
                        }
                    }
                }
                else if($article->type == '2')
                {
                    $type3 = CompletionArticle::find()->where(['parent' => (string) $article->id])->andWhere(['type' => '3'])->all();
                    foreach ($type3 as $value)
                        $value->delete();
                }
                if($article->delete())
                    Yii::$app->session->setFlash('status',5);
                else
                    Yii::$app->session->setFlash('status',4);
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEditArticle($_id)
    {
        if($_GET['_id'] != null && $_GET['_id'] != '')
        {
            $model = Articles::findOne($_id);
            if($model != null)
            {
                $departments = Departments::find()->all();
                return $this->render('edit-article',[
                    'departments' => ArrayHelper::map($departments,function ($model){
                        return (string) $model->_id;
                    },'title'),
                    'model' => $model
                ]);
            }
            else
                Yii::$app->getResponse()->redirect('../manage-articles');
        }
        Yii::$app->getResponse()->redirect('../manage-articles');
    }

    public function actionEditCompletionArticle($_id)
    {
        if(isset($_GET['_id']))
        {
            $post_detail=CompletionArticle::findOne($_id);
            if($post_detail!=null)
            {
                $type = null;
                $type1 = CompletionArticle::find()->where(['articleId' => (string) $post_detail->articleId])->andWhere(['type' => '1'])->all();
                $type2 = CompletionArticle::find()->where(['articleId' => (string) $post_detail->articleId])->andWhere(['type' => '2'])->all();
                if($post_detail->type == 2)
                    $type = ArrayHelper::map($type1,function ($model){
                        return (string) $model->_id;
                    },'title');
                else if($post_detail->type == 3)
                    $type = ArrayHelper::map($type2,function ($model){
                        return (string) $model->_id;
                    },'title');
                $images = UploadCenter::find()->orderBy(['_id'=>SORT_DESC])->all();
                return $this->render('edit-completion-article',[
                    'post_detail'=>$post_detail,
                    'type' => $type,
                    'images' => $images,
                ]);
            }
            else
                Yii::$app->getResponse()->redirect('../manage-articles');
        }
        else
            Yii::$app->getResponse()->redirect('../manage-articles');
    }

    public function actionUpload()
    {
        $model=new UploadCenter();
        if(Yii::$app->request->post())
        {
            $file1 = UploadedFile::getInstance($model,'image');
            $file1_ext = $file1->extension;
            $doc = uniqid();
            $file1_name = $doc.'.'.$file1_ext;
            if($model->save(false))
            {
                $file1->saveAs('../../frontend/web/uploads/'.$doc.'.'.$file1_ext);
                if(UploadedFile::getInstance($model,'image')!=null)
                    $model->image = $file1_name;
                $model->save(false);
                Yii::$app->session->setFlash('status','4');
                return $this->redirect(Yii::$app->request->referrer);
            }
            else
            {
                Yii::$app->session->setFlash('status','2');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }
        else
        {
            Yii::$app->session->setFlash('status','2');
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function actionUpdate()
    {
        if(Yii::$app->request->post())
        {
            $model=CompletionArticle::findOne(Yii::$app->request->post()['CompletionArticle']['_id']);
            if($model!=null)
            {
                $pre_image=$model->image;
                if($model->load(Yii::$app->request->post()))
                {
                    if($model->save(false))
                    {
                        $model->image=$pre_image;
                        if(UploadedFile::getInstance($model,'image') != null)
                        {
                            $file1 = UploadedFile::getInstance($model,'image');
                            $file1_ext = $file1->extension;
                            $doc = uniqid();
                            $file1_name = $doc.'.'.$file1_ext;
                            $file1->saveAs('../../frontend/web/completion_article_images/'.$doc.'.'.$file1_ext);
                            if($pre_image != null && $pre_image != '')
                                unlink('../../frontend/web/completion_article_images/'.$pre_image);
                            if(UploadedFile::getInstance($model,'image')!=null)
                                $model->image = $file1_name;
                        }
                        $model->save(false);
                        Yii::$app->session->setFlash('status','3');
                        Yii::$app->response->redirect(['manage-articles/completion-article','_id' => $model->articleId]);
                    }
                    else
                    {
                        Yii::$app->session->setFlash('status','4');
                        Yii::$app->response->redirect(['manage-articles/completion-article','_id' => $model->articleId]);
                    }
                }
                else
                    return $this->redirect(Yii::$app->request->referrer);
            }
            else
                return $this->redirect(Yii::$app->request->referrer);
        }
        else
            return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpdate_base()
    {
        if(Yii::$app->request->post())
        {
            $model=Articles::findOne(Yii::$app->request->post()['Articles']['_id']);
            if($model!=null)
            {
                $pre_image=$model->image;
                if($model->load(Yii::$app->request->post()))
                {
                    if($model->save(false))
                    {
                        $model->image=$pre_image;
                        if(UploadedFile::getInstance($model,'image') != null)
                        {
                            $file1=UploadedFile::getInstance($model,'image');
                            $file1_ext=$file1->extension;
                            $doc=uniqid();
                            $file1_name=$doc.'.'.$file1_ext;
                            $file1->saveAs('../../backend/web/article_images/'.$doc.'.'.$file1_ext);
                            if($pre_image != null && $pre_image != '')
                                unlink('../../backend/web/article_images/'.$pre_image);
                            if(UploadedFile::getInstance($model,'image')!=null)
                                $model->image=$file1_name;
                        }
                        $model->save(false);
                        Yii::$app->session->setFlash('status','1');
                        Yii::$app->getResponse()->redirect('../manage-articles');
                    }
                    else
                    {
                        Yii::$app->session->setFlash('status','2');
                        Yii::$app->getResponse()->redirect('../manage-articles');
                    }
                }
                else
                    return $this->redirect(Yii::$app->request->referrer);
            }
            else
                return $this->redirect(Yii::$app->request->referrer);
        }
        else
            return $this->redirect(Yii::$app->request->referrer);
    }
}
