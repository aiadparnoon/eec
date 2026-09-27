<?php

namespace frontend\controllers;

use Yii;
use common\models\Admin;
use app\models\AdminsSearch;
use app\models\Colleges;
use app\models\Generals;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * BuyersController implements the CRUD actions for Admin model.
 */
class ManageMembersController extends Controller
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
                        'actions' => ['index', 'report', 'new', 'edit','reset_password','change_status'],
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
     * Lists all Buyers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AdminsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $colleges = Colleges::find()->all();
        $colleges = ArrayHelper::map($colleges, function ($model){
            return (string) $model->_id;
        },'title');
        $zk = array(
            '0' => 'کارمند مرکز'
        );
        array_push($colleges, 'کارمند مرکز');
        $dataProvider->pagination->pageSize = 50;
        $access = Generals::find()->where(['type' => 'access'])->one();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'colleges' => $colleges,
            'access' => ArrayHelper::map($access->data,function ($model){
                return $model['controller'];
            },function($model){
                return $model['title'];
            })
        ]);
    }

    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');
        $searchModel = new AdminsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'first_name',
                    'header' => ' نام'
                ],
                [
                    'attribute' => 'last_name',
                    'header' => 'نام خانوادگی'
                ],
                [
                    'attribute' => 'username',
                    'header' => 'شماره همراه'
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
        $file_name = 'EmployeeReport-' . jdate('Y/m/d-H:i:s') . '.xlsx';
        return Yii::$app->response->sendFile('./newfile.xlsx', $file_name);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $value = Yii::$app->request->post('TagifyUserList');
            $value1 = json_decode($value);
            $finalAccess = array();
            foreach ($value1 as $item)
                array_push($finalAccess,$item->value);
            $find = Admin::find()->where(['username' => Yii::$app->request->post()['Admin']['username']])->one();
            if ($find == null)
            {
                $model = new Admin();
                $model->load(Yii::$app->request->post());
                $model->setPassword(Yii::$app->request->post()['Admin']['national_code']);
                $model->auth_key = Yii::$app->security->generateRandomString();
                $model->verification_token = Yii::$app->security->generateRandomString();
                $model->getAuthKey();
                if(Yii::$app->request->post()['Admin']['college'] == '0')
                    $model->role = 'cnt';
                else
                    $model->role = 'emp';
                $model->access = $finalAccess;
                $model->status = 10;
                $model->serving = false;
                if(isset($_POST['serving']))
                    if(Yii::$app->request->post('serving') == '1')
                        $model->serving = true;
                $model->save();
                if ($model->save())
                {
                    // Call AdobeConnect For Add Personnel To College Courses
                    $adminRole = Admin::find()->where(['role' => 'user'])->one();
                    if($adminRole != null && $model->role == 'emp')
                    {
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-college-user/'.(string) $model->_id,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_HTTPHEADER => array(
                                '_id: '.(string) $adminRole->_id
                            ),
                        ));
                        $response = curl_exec($curl);
                        curl_close($curl);
                    }
                    // Call AdobeConnect For Add Personnel To College Courses
                    Yii::$app->session->setFlash('status', '1');
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
        if(Yii::$app->request->isPost)
        {
            $find = Admin::findOne(Yii::$app->request->post()['Admin']['_id']);
            if($find != null)
            {
                $find->load(Yii::$app->request->post());
                $find->access = Yii::$app->request->post()['access'];
                if(isset($_POST['serving']))
                    $find->serving = true;
                else
                    $find->serving = false;
                if($find->save())
                    Yii::$app->session->setFlash('status','4');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionReset_password()
    {
        if(Yii::$app->request->isPost)
        {
            $admin = Admin::findOne(Yii::$app->request->post()['Admin']['_id']);
            if($admin != null)
            {
                $admin->setPassword($admin->national_code);
                if($admin->save())
                    Yii::$app->session->setFlash('status','5');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $admin = Admin::findOne(Yii::$app->request->post()['Admin']['_id']);
            if($admin != null)
            {
                $status = $admin->status;
                if($status == 10)
                    $admin->status = 9;
                else
                    $admin->status = 10;
                if($admin->save())
                    Yii::$app->session->setFlash('status','6');
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
}
