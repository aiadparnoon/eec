<?php

namespace frontend\controllers;

use Yii;
use app\models\Lessons;
use app\models\LessonsSearch;
use app\models\Colleges;
use app\models\Courses;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * BuyersController implements the CRUD actions for Lessons model.
 */
class LessonsController extends Controller
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
                        'actions' => ['index', 'new', 'edit','report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['delete_lesson','get_lesson_detail'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'user')
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
        $searchModel = new LessonsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $colleges = Colleges::find()->all();
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title')
        ]);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new Colleges();
            $find = Colleges::find()->where(['title' => Yii::$app->request->post()['Lessons']['title']])->andWhere(['college' => Yii::$app->request->post()['Lessons']['college']])->one();
            if ($find == null)
            {
                if ($_FILES['Lessons']['size']['imagePreview'] <= Yii::getAlias('@uploadSize'))
                {
                    $model = new Lessons();
                    $model->load(Yii::$app->request->post());
                    if ($_FILES['Lessons']['name']['imagePreview'] != '')
                    {
                        $file = UploadedFile::getInstance($model, 'imagePreview');
                        $file_ext = $file->extension;
                        $file_name = uniqid() . '.' . $file_ext;
                        $file->saveAs('../../frontend/web/lesson_images/' . $file_name);
                        if (UploadedFile::getInstance($model, 'imagePreview') != null)
                            $model->imagePreview = $file_name;
                    }
                    else
                        $model->imagePreview = 'default_lesson.png';
                    $model->status = '1';
                    if ($model->save())
                        Yii::$app->session->setFlash('status', '1');
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
                else
                    Yii::$app->session->setFlash('status', '3');
            }
            else
                Yii::$app->session->setFlash('status', '5');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
    {
        if (Yii::$app->request->isPost)
        {
            $find = Lessons::findOne(Yii::$app->request->post()['Lessons']['_id']);
            if ($find != null)
            {
                if ($_FILES['Lessons']['size']['imagePreview'] <= Yii::getAlias('@uploadSize'))
                {
                    $preImage = $find->imagePreview;
                    $find->load(Yii::$app->request->post());
                    $imageFlag = false;
                    if ($_FILES['Lessons']['name']['imagePreview'] != '')
                    {
                        if ($preImage != '' && $preImage != null && $preImage != 'default_lesson.png')
                            unlink('../../frontend/web/lesson_images/' . $preImage);
                        $file1 = UploadedFile::getInstance($find, 'imagePreview');
                        $file1_ext = $file1->extension;
                        $file1_name = uniqid() . '.' . $file1_ext;
                        $file1->saveAs('../../frontend/web/lesson_images/' . $file1_name);
                        if (UploadedFile::getInstance($find, 'imagePreview') != null) {
                            $profile = $file1_name;
                            $find->imagePreview = $profile;
                        }
                        $imageFlag = true;
                    }
                    else
                        $find->imagePreview = $preImage;
                    if ($find->save())
                    {
                        if($imageFlag == true) // Change Lesson Image in Courses
                        {
                            $courses = Courses::find()->where(['lessons._id' => (string) $find->_id])->andWhere(['type' => '1'])->all();
                            if($courses != null)
                            {
                                foreach ($courses as $item)
                                {
                                    $item->preview_image = $find->imagePreview;
                                    $item->save();
                                }
                            }
                        }
                        Yii::$app->session->setFlash('status', '4');
                    }
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
                else
                    Yii::$app->session->setFlash('status', '3');
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
        $searchModel = new LessonsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'title',
                    'header' => ' عنوان  فارسی درس'
                ],
                [
                    'attribute' => 'en_title',
                    'header' => 'عنوان  انگلیسی درس'
                ],
                [
                    'attribute' => function($model){
                        return $model->comment;
                    },
                    'header' => 'توضیحات'
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
        $file_name = 'Lessons-' . jdate('Y/m/d-H:i:s') . '.xlsx';
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

    public function actionGet_lesson_detail()
    {
        if(isset($_POST['id']))
        {
            $lesson = Lessons::findOne($_POST['id']);
            if($lesson != null)
            {
                $courses = Courses::find()->where(['lessons._id' => (string) $lesson->_id])->all();
                if($courses != null)
                {
                    $response = array(
                        'title' => 'حذف درس '.$lesson->title,
                        'body' => 'به دلیل اینکه درس '.$lesson->title.' در حداقل یک دوره استفاده شده است قادر به حذف این درس نمی باشد',
                        'submit' => null
                    );
                }
                else
                {
                    ob_start();
                     $form = ActiveForm::begin(
                        [
                            'action' => ['delete_lesson'],
                            "method" => "post",
                        ]
                    );
                    echo '<input type="hidden" name="_id" value="'.(string) $lesson->_id.'">';
                    echo '<button type="submit" class="btn btn-label-danger">بله مطمئنم</button>';
                    ActiveForm::end();
                    $submit = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'title' => 'حذف درس '.$lesson->title,
                        'body' => 'آیا از حذف درس '.$lesson->title.' مطمئن هستید؟',
                        'submit' => $submit
                    );
                }
                return json_encode($response);
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionDelete_lesson()
    {
        if(Yii::$app->request->isPost)
        {
            $lesson = Lessons::findOne(Yii::$app->request->post('_id'));
            if($lesson != null)
            {
                $image = $lesson->imagePreview;
                if($lesson->delete())
                {
                    if(file_exists('../../frontend/web/lesson_images/' . $image))
                        unlink('../../frontend/web/lesson_images/' . $image);
                    Yii::$app->session->setFlash('status','6');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
}
