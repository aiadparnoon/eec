<?php

namespace frontend\controllers;

use Yii;
use app\models\QuestionsBank;
use app\models\QuestionsBankSearch;
use app\models\Colleges;
use app\models\TestsGroups;
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
class QuestionsBankController extends Controller
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
                        'actions' => ['index', 'new', 'edit', 'edit_options','manage-questions','manage-survey','change_status','create_question','delete_question','edit_question','show_question_detail','create','edit2'],
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
        $searchModel = new QuestionsBankSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 50;
        $groups = null;
        if(Yii::$app->user->identity->role == 'user')
            $groups = TestsGroups::find()->all();
        else
            $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'groups' => ArrayHelper::map($groups,function ($model){
                return (string) $model->_id;
            },'title'),
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
        return $this->redirect(['../test-maker']);
    }

    public function actionManageSurvey($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Exams::findOne($_GET['_id']);
            if($examDetails != null)
                return $this->render('manage-survey', [
                    'examDetails' => $examDetails
                ]);
        }
        return $this->redirect(['../test-maker']);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            if(isset($_POST['options']))
            {
                $options = array();
                $response = 0;
                foreach ($_POST['options'] as $item)
                {
                    if($item['title'] != '')
                    {
                        $flag = false;
                        if(array_key_exists('is_correct', $item))
                        {
                            $flag = true;
                            $response ++;
                        }
                        $tmp = array(
                            'title' => $item['title'],
                            'is_correct' => $flag,
                            'id' => uniqid()
                        );
                        array_push($options, $tmp);
                    }
                }
                if($response != 0)
                {
                    if($options != null)
                    {
                        $model = new QuestionsBank();
                        $model->load(Yii::$app->request->post());
                        if ($_FILES['QuestionsBank']['name']['image'] != '')
                        {
                            $file = UploadedFile::getInstance($model, 'image');
                            $file_ext = $file->extension;
                            $file_name = uniqid() . '.' . $file_ext;
                            $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                            if (UploadedFile::getInstance($model, 'image') != null)
                                $model->image = $file_name;
                        }
                        else
                            $model->image = null;
                        $model->options = $options;
                        if($response == 1)
                            $model->type = '1';
                        else
                            $model->type = '2';
                        if($model->save())
                            Yii::$app->session->setFlash('status','1');
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                    else
                        Yii::$app->session->setFlash('status','2');
                }
                else
                    Yii::$app->session->setFlash('status','4');
            }
            else
                Yii::$app->session->setFlash('status','3');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCreate()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new QuestionsBank();
            $model->load(Yii::$app->request->post());
            if ($_FILES['QuestionsBank']['name']['image'] != '')
            {
                $file = UploadedFile::getInstance($model, 'image');
                $file_ext = $file->extension;
                $file_name = uniqid() . '.' . $file_ext;
                $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                if (UploadedFile::getInstance($model, 'image') != null)
                    $model->image = $file_name;
            }
            else
                $model->image = null;
            $model->options = null;
            $model->type = '3';
            if($model->save())
                Yii::$app->session->setFlash('status','1');
            else
                Yii::$app->session->setFlash('status','2');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit()
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
                $options = array();
                if(isset($_POST['options']))
                {
                    foreach ($_POST['options'] as $item)
                    {
                        if($item['title'] != '')
                        {
                            $flag = false;
                            if(array_key_exists('is_correct', $item))
                                $flag = true;
                            $tmp = array(
                                'title' => $item['title'],
                                'is_correct' => $flag,
                                'id' => uniqid()
                            );
                            array_push($options, $tmp);
                        }
                    }
                }
                if($options != null)
                {
                    $preOptions = $find->options;
                    $newOptions = array_merge($preOptions, $options);
                    $find->options = $newOptions;
                }
                if ($find->save())
                    Yii::$app->session->setFlash('status', '5');
                else
                    Yii::$app->session->setFlash('status', '2');
            }
            return $this->redirect(Yii::$app->request->referrer);
        } else
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

    public function actionEdit2()
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

    public function college_detail($_id)
    {
        return Colleges::findOne($_id);
    }

    public function actionShow_question_detail()
    {
        if(isset($_POST['id']))
        {
            $question = QuestionsBank::findOne($_POST['id']);
            if($question != null)
            {
                if($question->type != '3')
                {
                    ob_start();
                    ?>
                    <div class="card text-center">
                        <ul class="nav nav-tabs tabs-line" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#link1" aria-controls="navs-tabs-line-card-active" aria-selected="true">
                                    مشخصات
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#link2" aria-controls="options" aria-selected="false" tabindex="-1">
                                    گزینه ها
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="link1" role="tabpanel">
                                <?php $form = ActiveForm::begin(
                                    [
                                        'action' => ['edit'],
                                        "method" => "post",
                                        'options' => [
                                            'class' => '',
                                            'enctype' => 'multipart/form-data'
                                        ],
                                    ]
                                ); ?>
                                <?= $form->field($question, '_id')->hiddenInput()->label(false); ?>
                                <div class="row">
                                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">عنوان *</label>
                                        <?= $form->field($question, 'question_text')->textarea(
                                            [
                                                'class' => 'form-control text-start',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان سوال  را وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                            ]
                                        )->label(false); ?>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                        <label for="select2Basic" class="form-label">سطح سوال *</label>
                                        <?php
                                        $level = array(
                                            '1' => 'آسان',
                                            '2' => 'متوسط',
                                            '3' => 'سحت'
                                        );
                                        echo $form->field($question, 'level')->dropDownList(
                                            $level,
                                            [
                                                'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                                'class' => 'select2 form-select',
                                                'required' => true,
                                                'data-allow-clear' => true,
                                                'id' => '',
                                            ]
                                        )->label(false);
                                        ?>
                                    </div>
                                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                        <label for="select2Basic" class="form-label">گروه *</label>
                                        <?php
                                        if(Yii::$app->user->identity->role == 'user')
                                            $groups = TestsGroups::find()->all();
                                        else
                                            $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
                                        $groups = ArrayHelper::map($groups, function($model){
                                            return (string) $model->_id;
                                        },'title');
                                        echo $form->field($question, 'group')->dropDownList(
                                            $groups,
                                            [
                                                'prompt' => 'لطفا گروه سوال را مشخص کنید',
                                                'class' => 'select2 form-select',
                                                'required' => true,
                                                'data-allow-clear' => true,
                                                'id' => '',
                                            ]
                                        )->label(false);
                                        ?>
                                    </div>
                                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                        <div class="card mb-4 relative">
                                            <div class="card-body">
                                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                    <div class="dz-message needsclick">
                                                        <?= $front = Yii::getAlias('@front') ?>
                                                        <?php
                                                        if($question->image != null)
                                                            echo '<img src="'. $front.'/upload_center/'.$question->image .'" class="upload-preview img-fluid">';
                                                        ?>
                                                        <?= $form->field($question, 'image')->fileInput(
                                                            [
                                                                'class' => 'form-control text-start drop-file',
                                                            ]
                                                        )->label(false); ?>
                                                        <span class="drop-title"></span>
                                                        <span class="note needsclick">تصویر سوال</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-repeater">
                                        <hr>
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div class="form-send-message d-flex justify-content-between align-items-center">
                                                <div class="message-actions d-flex align-items-center">
                                                <span class="badge bg-label-info" data-repeater-create="" type="button" style="width: 150px;">
                                                    <i class="bx bx-plus me-1"></i>
                                                    <span class="align-middle grow">افزودن گزینه جدید</span>
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div data-repeater-list="options">
                                            <div data-repeater-item="">
                                                <div class="row mb-2">
                                                    <div class="mb-12 col-lg-12 col-xl-12 col-12 mb-0">
                                                        <div class="input-group">
                                                            <div class="input-group-text form-check-success">
                                                                <input name="is_correct" class="form-check-input mt-0" type="checkbox">
                                                            </div>
                                                            <textarea name="title" type="text" class="form-control" aria-label="Text input with radio button"></textarea>
                                                            <button class="btn btn-outline-danger" type="button"  data-repeater-delete="" id="inputGroupFileAddon04">
                                                                <svg class="tf-icons navbar-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path opacity="0.5" d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#1C274C"></path>
                                                                    <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-0 add-item">
                                            <button type="submit" class="btn btn-success">ویرایش سوال</button>
                                        </div>
                                    </div>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="link2" role="tabpanel">
                                <?php
                                if($question->options != null)
                                {
                                    $row = 0;
                                    foreach ($question->options as $item)
                                    {
                                        $checked = '';
                                        if($item['is_correct'] == true)
                                            $checked = 'checked';
                                        $form = ActiveForm::begin(
                                            [
                                                'action' => ['edit_options'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        );
                                        ?>
                                        <div data-repeater-list="options">
                                            <div data-repeater-item="">
                                                <div class="row mb-2">
                                                    <div class="mb-12 col-lg-12 col-xl-12 col-12 mb-0">
                                                        <div class="input-group">
                                                            <input type="hidden" name="_id" value="<?= (string) $question->_id ?>">
                                                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                            <input type="hidden" name="row" value="<?= $row++ ?>">
                                                            <div class="input-group-text form-check-success">
                                                                <input name="is_correct" class="form-check-input mt-0" type="checkbox" <?= $checked ?>>
                                                            </div>
                                                            <textarea name="title" type="text" class="form-control" aria-label="Text input with radio button"><?= $item['title'] ?></textarea>
                                                            <button class="btn btn-outline-warning" name="edit" type="submit"  data-repeater-delete="" id="inputGroupFileAddon04">
                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path opacity="0.5" d="M20.8487 8.71306C22.3844 7.17735 22.3844 4.68748 20.8487 3.15178C19.313 1.61607 16.8231 1.61607 15.2874 3.15178L14.4004 4.03882C14.4125 4.0755 14.4251 4.11268 14.4382 4.15035C14.7633 5.0875 15.3768 6.31601 16.5308 7.47002C17.6848 8.62403 18.9133 9.23749 19.8505 9.56262C19.888 9.57563 19.925 9.58817 19.9615 9.60026L20.8487 8.71306Z" fill="#1C274C"/>
                                                                    <path d="M14.4386 4L14.4004 4.03819C14.4125 4.07487 14.4251 4.11206 14.4382 4.14973C14.7633 5.08687 15.3768 6.31538 16.5308 7.4694C17.6848 8.62341 18.9133 9.23686 19.8505 9.56199C19.8876 9.57489 19.9243 9.58733 19.9606 9.59933L11.4001 18.1598C10.823 18.7369 10.5343 19.0255 10.2162 19.2737C9.84082 19.5665 9.43469 19.8175 9.00498 20.0223C8.6407 20.1959 8.25351 20.3249 7.47918 20.583L3.39584 21.9442C3.01478 22.0712 2.59466 21.972 2.31063 21.688C2.0266 21.4039 1.92743 20.9838 2.05445 20.6028L3.41556 16.5194C3.67368 15.7451 3.80273 15.3579 3.97634 14.9936C4.18114 14.5639 4.43213 14.1578 4.7249 13.7824C4.97307 13.4643 5.26165 13.1757 5.83874 12.5986L14.4386 4Z" fill="#1C274C"/>
                                                                </svg>
                                                            </button>
                                                            <br>
                                                            <?php
                                                            if(count($question->options) > 1)
                                                            {
                                                                ?>
                                                                <button class="btn btn-outline-danger" type="submit" name="delete"  data-repeater-delete="" id="inputGroupFileAddon04">
                                                                    <svg class="tf-icons navbar-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path opacity="0.5" d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#1C274C"></path>
                                                                        <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C"></path>
                                                                    </svg>
                                                                </button>
                                                                <?php
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php ActiveForm::end(); ?>
                                        <?php
                                    }
                                }
                                else
                                    echo 'این سوال فاقد گزینه می باشد';
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    $body = ob_get_contents();
                    ob_end_clean();
                }
                else
                {
                    ob_start();
                    ?>
                    <?php $form = ActiveForm::begin(
                    [
                        'action' => ['edit2'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                    <?= $form->field($question, '_id')->hiddenInput()->label(false); ?>
                    <div class="row">
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان *</label>
                            <?= $form->field($question, 'question_text')->textarea(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان سوال  را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">سطح سوال *</label>
                            <?php
                            $level = array(
                                '1' => 'آسان',
                                '2' => 'متوسط',
                                '3' => 'سحت'
                            );
                            echo $form->field($question, 'level')->dropDownList(
                                $level,
                                [
                                    'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                        <div class="dz-message needsclick">
                                            <?php
                                            $front = Yii::getAlias('@front');
                                            if($question->image != null)
                                                echo '<img src="'. $front.'/upload_center/'.$question->image .'" class="upload-preview img-fluid">';
                                            ?>
                                            <?= $form->field($question, 'image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر سوال</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-repeater">
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">ویرایش سوال</button>
                            </div>
                        </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                    <?php
                    $body = ob_get_contents();
                    ob_end_clean();
                }
                $response = array(
                    'body' => $body,
                );
                return json_encode($response);
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionEdit_options()
    {
        if(Yii::$app->request->isPost)
        {
            $question = QuestionsBank::findOne(Yii::$app->request->post('_id'));
            if($question != null)
            {
                if($question->options[Yii::$app->request->post('row')]['id'] == Yii::$app->request->post('id'))
                {
                    $options = $question->options;
                    if(isset($_POST['edit']))
                    {
                        $options[Yii::$app->request->post('row')]['title'] = Yii::$app->request->post('title');
                        if(isset($_POST['is_correct']))
                            $options[Yii::$app->request->post('row')]['is_correct'] = true;
                        else
                            $options[Yii::$app->request->post('row')]['is_correct'] = false;
                        $question->options = $options;
                        $response = 0;
                        foreach ($options as $item)
                            if($item['is_correct'] == true)
                                $response++;
                        if($response != 0)
                        {
                            if($question->save())
                                Yii::$app->session->setFlash('status','6');
                            else
                                Yii::$app->session->setFlash('status','2');
                        }
                        else
                            Yii::$app->session->setFlash('status','8');
                    }
                    else if(isset($_POST['delete']))
                    {
                        $options = $question->options;
                        unset($options[Yii::$app->request->post('row')]);
                        $options = array_values($options);
                        $question->options = $options;
                        $response = 0;
                        foreach ($options as $item)
                            if($item['is_correct'] == true)
                                $response++;
                        if($response != 0)
                        {
                            if($question->save())
                                Yii::$app->session->setFlash('status','7');
                            else
                                Yii::$app->session->setFlash('status','2');
                        }
                        else
                            Yii::$app->session->setFlash('status','9');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function group_detail($_id)
    {
        $group = TestsGroups::findOne($_id);
        if($group != null)
            return $group->title;
        else
            return 'نامشخص';
    }
}
