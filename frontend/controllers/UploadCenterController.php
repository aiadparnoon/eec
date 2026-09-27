<?php

namespace frontend\controllers;

use Yii;
use app\models\UploadCenter;
use app\models\UploadCenterSearch;
use app\models\CoursesContents;
use app\models\Lessons;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * UploadCenterController implements the CRUD actions for UploadCenter model.
 */
class UploadCenterController extends Controller
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
                        'actions' => ['index', 'new', 'edit','report','show_file_uses','delete_file','file'],
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
     * Lists all Lessons models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UploadCenterSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 30;
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionFile($filename)
    {
        // امنیتی: نام فایل مستقیم از درخواست می‌آید. بدون این بررسی، ورودی
        // «../../config/main-local.php» کلید cookieValidationKey را برمی‌گرداند.
        $path = \app\components\SecureFile::resolve('upload_center', $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new Colleges();
            $find = Colleges::find()->where(['title' => Yii::$app->request->post()['Lessons']['title']])->andWhere(['college' => Yii::$app->request->post()['Lessons']['college']])->one();
            if ($find == null)
            {
                $model = new Lessons();
                $model->load(Yii::$app->request->post());
                $file = UploadedFile::getInstance($model, 'imagePreview');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/lesson_images/' . $file_name);
                if (UploadedFile::getInstance($model, 'imagePreview') != null)
                    $model->imagePreview = $file_name;
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
        if (Yii::$app->request->isPost)
        {
            $find = UploadCenter::findOne(Yii::$app->request->post()['UploadCenter']['_id']);
            if ($find != null)
            {
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

    public function actionShow_file_uses()
    {
        if(isset($_POST['id']))
        {
            $file = UploadCenter::findOne($_POST['id']);
            if($file != null)
            {
                $uses = CoursesContents::find()->where(['content.content' => (string) $file->file])->all();
                if($uses != null)
                {
                    $response = array(
                        'title' => 'حذف فایل '.$file->title,
                        'body' => 'به دلیل اینکه فایل با عنوان '.$file->title.' در محتوای بیش از یک درس به کار رفته است قابل حذف نیست',
                        'submit' => null
                    );
                }
                else
                {
                    ob_start();
                    $form = ActiveForm::begin(
                        [
                            'action' => ['delete_file'],
                            "method" => "post",
                        ]
                    );
                    echo '<input type="hidden" name="_id" value="'.(string) $file->_id.'">';
                    echo '<button type="submit" class="btn btn-label-danger">بله مطمئنم</button>';
                    ActiveForm::end();
                    $submit = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'title' => 'حذف فایل '.$file->title,
                        'body' => 'آیا از حذف فایل '.$file->title.' مطمئن هستید؟',
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

    public function actionDelete_file()
    {
        if(Yii::$app->request->isPost)
        {
            $file = UploadCenter::findOne(Yii::$app->request->post('_id'));
            if($file != null)
            {
                if((Yii::$app->user->identity->role == 'user') || (Yii::$app->user->identity->username == $file->registrant))
                {
                    $uses = CoursesContents::find()->where(['content.content' => (string) $file->file])->all();
                    if($uses == null)
                    {
                        if(file_exists('../../frontend/web/upload_center/'.$file->file))
                        {
                            unlink('../../frontend/web/upload_center/'.$file->file);
                            if($file->delete())
                                Yii::$app->session->setFlash('status','5');
                            else
                                Yii::$app->session->setFlash('status','2');
                        }
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function filesize_formatted($path)
    {
        $size = filesize($path);
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
