<?php

namespace frontend\controllers;

use Yii;
use app\models\Tests;
use app\models\TestsSearch;
use app\models\Colleges;
use app\models\TestsGroups;
use app\models\QuestionsBank;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

/**
 * BuyersController implements the CRUD actions for Tests model.
 */
class TestMakerController extends Controller
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
                        'actions' => ['index', 'new', 'edit','manage-questions','random_questions','show_in_site','random_questions1','show_in_site1','group_questions','change_status','create_question','delete_question','edit_question','add_question_to_test','get_exam_details'],
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
        $searchModel = new TestsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 20;
        if(Yii::$app->user->identity->role == 'user')
        {
            $groups = TestsGroups::find()->all();
            $colleges = Colleges::find()->all();
            $colleges = ArrayHelper::map($colleges, function ($model){
                return (string) $model->_id;
            },'title');
        }
        else
        {
            $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
            $colleges = null;
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'groups' => ArrayHelper::map($groups,function ($model){
                return (string) $model->_id;
            },'title'),
            'colleges' => $colleges
        ]);
    }

    public function actionManageQuestions($_id)
    {
        if(isset($_GET['_id']))
        {
            $examDetails = Tests::findOne($_GET['_id']);
            if($examDetails != null)
            {
                if($this->allow($examDetails->college))
                {
                    $groups = TestsGroups::find()->where(['college' => $examDetails->college])->all();
                    if($groups != null)
                        $groups = ArrayHelper::map($groups,function($model){
                            return (string) $model->_id;
                        },'title');
                    return $this->render('manage-questions', [
                        'examDetails' => $examDetails,
                        'groups' => $groups
                    ]);
                }
            }
        }
        return $this->redirect(['../test-maker']);
    }

    public function actionNew()
    {
        if (Yii::$app->request->isPost)
        {
            $model = new Tests();
            $model->load(Yii::$app->request->post());
            if(isset($_FILES['Tests']))
            {
                if ($_FILES['Tests']['name']['preview_image'] != '')
                {
                    $file = UploadedFile::getInstance($model, 'preview_image');
                    $file_ext = $file->extension;
                    $file_name = uniqid() . '.' . $file_ext;
                    $file->saveAs('../../frontend/web/tests_images/' . $file_name);
                    if (UploadedFile::getInstance($model, 'preview_image') != null)
                        $model->preview_image = $file_name;
                }
                else
                    $model->preview_image = null;
            }
            else
                $model->preview_image = null;
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
            $model = Tests::findOne(Yii::$app->request->post()['Tests']['_id']);
            if($model != null)
            {
                $preImage = $model->preview_image;
                $model->load(Yii::$app->request->post());
                if(isset($_FILES['Tests']))
                {
                    if ($_FILES['Tests']['name']['preview_image'] != '')
                    {
                        if($preImage != null)
                            unlink('../../frontend/web/tests_images/' . $preImage);
                        $file = UploadedFile::getInstance($model, 'preview_image');
                        $file_ext = $file->extension;
                        $file_name = uniqid() . '.' . $file_ext;
                        $file->saveAs('../../frontend/web/tests_images/' . $file_name);
                        if (UploadedFile::getInstance($model, 'preview_image') != null)
                            $model->preview_image = $file_name;
                    }
                    else
                        $model->preview_image = $preImage;
                }
                else
                    $model->preview_image = $preImage;
                if($model->save())
                    Yii::$app->session->setFlash('status','3');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
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

    public function actionDelete_question()
    {
        if(Yii::$app->request->isPost)
        {
            $exam = Tests::findOne(Yii::$app->request->post('_id'));
            if($exam != null)
            {
                $questions = $exam->questions;
                unset($questions[Yii::$app->request->post('row')]);
                $questions = array_values($questions);
                $exam->questions = $questions;
                if($exam->save())
                    Yii::$app->session->setFlash('status','3');
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

    public function actionRandom_questions($id)
    {
        if($id == '2')
        {
            if(Yii::$app->user->identity->role == 'user')
            {
                $groups = TestsGroups::find()->all();
                $groups = ArrayHelper::map($groups, function ($model){
                    return (string) $model->_id;
                },'title');
            }
            else
            {
                $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
                $groups = ArrayHelper::map($groups, function ($model){
                    return (string) $model->_id;
                },'title');
            }
            if($groups != null)
            {
                $model = new Tests();
                $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                );
                ?>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">گروه  *</label>
                    <?php
                    echo $form->field($model, 'group')->dropDownList(
                        $groups,
                        [
                            'prompt' => 'لطفا گروه سوالات را مشخص کنید',
                            'class' => 'select2 form-select',
                            'required' => true,
                            'data-allow-clear' => true,
                            'id' => rand(),
                        ]
                    )->label(false);
                    ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات آسان  *</label>
                    <?= $form->field($model, 'question_count[easy]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات متوسط  *</label>
                    <?= $form->field($model, 'question_count[medium]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات متوسط  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات سخت  *</label>
                    <?= $form->field($model, 'question_count[hard]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات سخت  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <?php
            }
        }
        else
            return false;
    }

    public function actionRandom_questions1($id)
    {
        if($id == '2')
        {
            if(Yii::$app->user->identity->role == 'user')
            {
                $groups = TestsGroups::find()->all();
                $groups = ArrayHelper::map($groups, function ($model){
                    return (string) $model->_id;
                },'title');
            }
            else
            {
                $groups = TestsGroups::find()->where(['college' => Yii::$app->user->identity->college])->all();
                $groups = ArrayHelper::map($groups, function ($model){
                    return (string) $model->_id;
                },'title');
            }
            if($groups != null)
            {
                $model = new Tests();
                $form = ActiveForm::begin(
                    [
                        'action' => ['edit'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                );
                ?>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">گروه  *</label>
                    <?php
                    echo $form->field($model, 'group')->dropDownList(
                        $groups,
                        [
                            'prompt' => 'لطفا گروه سوالات را مشخص کنید',
                            'class' => 'select2 form-select',
                            'required' => true,
                            'data-allow-clear' => true,
                            'id' => rand(),
                        ]
                    )->label(false);
                    ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات آسان  *</label>
                    <?= $form->field($model, 'question_count[easy]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات متوسط  *</label>
                    <?= $form->field($model, 'question_count[medium]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات متوسط  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                    <label for="nameWithTitle" class="form-label">تعداد سوالات سخت  *</label>
                    <?= $form->field($model, 'question_count[hard]')->textInput(
                        [
                            'type' => 'number',
                            'class' => 'form-control text-start',
                            'required' => true,
                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات سخت  را وارد کنید\')',
                            'oninput' => 'setCustomValidity(\'\')',
                        ]
                    )->label(false); ?>
                </div>
                <?php
            }
        }
        else
            return false;
    }

    public function actionShow_in_site($id)
    {
        if($id == '1')
        {
            $model = new Tests();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    "method" => "post",
                    'options' => [
                        'class' => '',
                        'enctype' => 'multipart/form-data'
                    ],
                ]
            );
            ob_start();
            ?>
            <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
            <?php
            echo $form->field($model, 'price')->textInput(
                [
                    'type' => 'number',
                    'class' => 'form-control text-start',
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت آزمون را وارد کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            ?>
            <?php
            $part1 = ob_get_contents();
            ob_end_clean();
            ob_start();
            ?>
            <div class="card mb-4 relative">
                <div class="card-body">
                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                        <div class="dz-message needsclick">
                            <?= $form->field($model, 'preview_image')->fileInput(
                                [
                                    'class' => 'form-control text-start drop-file',
                                    'required' => true
                                ]
                            )->label(false); ?>
                            <span class="drop-title"></span>
                            <span class="note needsclick">تصویر پیش نمایش *</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $part2 = ob_get_contents();
            ob_end_clean();
            ob_start();
            ?>
            <label for="nameWithTitle" class="form-label">توضیحات  *</label>
            <?= $form->field($model, 'description')->textarea(
            [
                'type' => 'number',
                'class' => 'form-control text-start',
            ]
        )->label(false); ?>
            <?php
            $part3 = ob_get_contents();
            ob_end_clean();
            $response = array(
                'part1' => $part1,
                'part2' => $part2,
                'part3' => $part3,
            );
            return json_encode($response);
        }
        else
            return false;
    }

    public function actionShow_in_site1($id)
    {
        if($id == '1')
        {
            $model = new Tests();
            $form = ActiveForm::begin(
                [
                    'action' => ['edit'],
                    "method" => "post",
                    'options' => [
                        'class' => '',
                        'enctype' => 'multipart/form-data'
                    ],
                ]
            );
            ob_start();
            ?>
            <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
            <?php
            echo $form->field($model, 'price')->textInput(
                [
                    'type' => 'number',
                    'class' => 'form-control text-start',
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت آزمون را وارد کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            ?>
            <?php
            $part1 = ob_get_contents();
            ob_end_clean();
            ob_start();
            ?>
            <div class="card mb-4 relative">
                <div class="card-body">
                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                        <div class="dz-message needsclick">
                            <?= $form->field($model, 'preview_image')->fileInput(
                                [
                                    'class' => 'form-control text-start drop-file',
                                    'required' => true
                                ]
                            )->label(false); ?>
                            <span class="drop-title"></span>
                            <span class="note needsclick">تصویر پیش نمایش *</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $part2 = ob_get_contents();
            ob_end_clean();
            ob_start();
            ?>
            <label for="nameWithTitle" class="form-label">توضیحات  *</label>
            <?= $form->field($model, 'description')->textarea(
            [
                'type' => 'number',
                'class' => 'form-control text-start',
            ]
        )->label(false); ?>
            <?php
            $part3 = ob_get_contents();
            ob_end_clean();
            $response = array(
                'part1' => $part1,
                'part2' => $part2,
                'part3' => $part3,
            );
            return json_encode($response);
        }
        else
            return false;
    }

    public function actionGroup_questions()
    {
       $exam = Tests::findOne($_POST['exam_id']);
       if($exam != null)
       {
           $questions = QuestionsBank::find()->where(['group' => $_POST['id']])->all();
           if($questions != null)
           {
               $model = new Tests();
               $form = ActiveForm::begin(
                   [
                       'action' => ['add_question_to_test'],
                       "method" => "post",
                       'options' => [
                           'class' => '',
                           'enctype' => 'multipart/form-data'
                       ],
                   ]
               );
               $questions = ArrayHelper::map($questions,function ($model){
                   return (string) $model->_id;
               },'question_text');
               ?>
               <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                   <label for="nameWithTitle" class="form-label">سوالات *</label>
                   <input type="hidden" name="exam_id" value="<?= (string) $exam->_id ?>">
                   <?php
                   echo $form->field($model, 'questions_tmp')->dropDownList(
                       $questions,
                       [
                           'prompt' => 'لطفا سوالات را مشخص کنید',
                           'class' => 'select2 form-select',
                           'required' => true,
                           'data-allow-clear' => true,
                           'multiple' => true,
                           'id' => 'id'.rand(),
                       ]
                   )->label(false);
                   ?>
               </div>
               <?php
           }
           else
               return false;
       }
       else
           return false;
    }

    public function actionAdd_question_to_test()
    {
        if(Yii::$app->request->isPost)
        {
            $test = Tests::findOne(Yii::$app->request->post('_id'));
            if($test != null)
            {
                if($test->questions != null)
                    $questions = $test->questions;
                else
                    $questions = array();
                if(isset($_POST['Tests']['questions_tmp']))
                {
                    foreach (Yii::$app->request->post()['Tests']['questions_tmp'] as $item)
                    {
                        if($questions != null)
                        {
                            $flag = 1;
                            foreach ($questions as $value)
                                if($value == $item)
                                    $flag = 2;
                            if($flag == 1)
                                array_push($questions, $item);
                        }
                        else
                            array_push($questions, $item);
                    }
                    $test->questions = $questions;
                    if($test->save())
                        Yii::$app->session->setFlash('status','1');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function question_detail($_id)
    {
        return QuestionsBank::findOne($_id);
    }

    public function group_detail($_id)
    {
        return TestsGroups::findOne($_id);
    }

    public function actionGet_exam_details()
    {
        if(isset($_POST['id']))
        {
            $examDetail = Tests::findOne($_POST['id']);
            if($examDetail != null)
            {
                $groups = TestsGroups::find()->all();
                $colleges = Colleges::find()->all();
                $colleges = ArrayHelper::map($colleges, function ($model){
                    return (string) $model->_id;
                },'title');
                $model = $examDetail;
                ob_start();
                $form = ActiveForm::begin(
                    [
                        'action' => ['edit'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                );
                ?>
                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <?php
                    if (Yii::$app->user->identity->role == 'user')
                    {
                        ?>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="select2Basic" class="form-label">دانشکده *</label>
                            <?php
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'id' => rand(),
                                    'data-allow-clear' => true,
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <?php
                    }
                    else
                    {
                        echo $form->field($model, 'college')->hiddenInput(
                            [
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                                'value' => Yii::$app->user->identity->college,
                            ]
                        )->label(false);
                    }
                    ?>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="select2Basic" class="form-label">نوع سوالات آزمون *</label>
                        <?php
                        $type = array(
                            '1' => 'سوالات انتخابی',
                            '2' => 'سوالات رندم'
                        );
                        echo $form->field($model, 'type')->dropDownList(
                            $type,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'id' => '',
                                'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/random_questions') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#random-questions2\').html(data);
                                                                                        }
                                                                                    );'
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="random-questions2">
                        <?php
                        if($model->type == '2')
                        {
                            ?>
                            <label for="nameWithTitle" class="form-label">گروه *</label>
                            <?php
                            echo $form->field($model, 'group')->dropDownList(
                                ArrayHelper::map($groups, function ($model){
                                    return (string) $model->_id;
                                },'title'),
                                [
                                    'prompt' => 'لطفا گروه سوالات را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => rand(),
                                ]
                            )->label(false);
                            ?>
                                <?php
                        }
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع پاسخ *</label>
                        <?php
                        $randomAnswers = array(
                            true => 'پاسخ ها مرتب',
                            false => 'پاسخ ها رندم',
                        );
                        echo $form->field($model, 'random_answers')->dropDownList(
                            $randomAnswers,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">زمان آزمون (دقیقه) *</label>
                        <?= $form->field($model, 'time')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">تعداد دفعات شرکت در آزمون *</label>
                        <?= $form->field($model, 'repeat')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                        <div id="floatingInputHelp" class="form-text">
                            مقدار خالی یعنی بی نهایت بار
                        </div>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نحوه نمره آزمون *</label>
                        <?php
                        $scoreType = array(
                            '1' => 'میانگین نمرات',
                            '2' => 'بیشترین نمره',
                        );
                        echo $form->field($model, 'score_type')->dropDownList(
                            $scoreType,
                            [
                                'prompt' => 'نحوه نمره آزمون',
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نمایش آزمون در سایت *</label>
                        <?php
                        $saleStatus = array(
                            '2' => 'خیر',
                            '1' => 'بله',
                        );
                        echo $form->field($model, 'sale_status')->dropDownList(
                            $saleStatus,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/show_in_site1') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                        console.log(main_data);
                                                                                        $(\'#part111\').html(main_data.part1);
                                                                                        $(\'#part222\').html(main_data.part2);
                                                                                        $(\'#part333\').html(main_data.part3);
                                                                                        }
                                                                                    );'
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نمره کلی آزمون *</label>
                        <?= $form->field($model, 'total_score')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نمره کلی آزمون را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">حداقل نمره قبولی *</label>
                        <?= $form->field($model, 'pass_score')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا حداقل نمره قبولی در آزمون را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3" id="part111">
                        <?php
                        if($model->sale_status == '1')
                        {
                            ?>
                            <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
                            <?php
                            echo $form->field($model, 'price')->textInput(
                                [
                                    'type' => 'number',
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت آزمون را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false);
                            ?>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="col-8 col-md-8 col-sm-12 dol-lg-8 col-xl-8 mb-3" id="part222">
                        <?php
                        if($model->sale_status == '1')
                        {
                            $front = Yii::getAlias('@front');
                            ?>
                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                        <div class="dz-message needsclick">
                                            <?php
                                            if($model->preview_image != null)
                                                echo '<img src="'.$front.'/tests_images/'.$model->preview_image.'" class="upload-preview img-fluid">';
                                            ?>
                                            <?= $form->field($model, 'preview_image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر پیش نمایش </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3" id="part333">
                        <?php
                        if($model->sale_status == '1')
                        {
                            ?>
                            <label for="nameWithTitle" class="form-label">توضیحات  *</label>
                            <?= $form->field($model, 'description')->textarea(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                            <?php
                        }
                        ?>
                    </div>
<?php
                $body = ob_get_contents();
                ob_end_clean();
                $response = array(
                    'title' => 'ویرایش آزمون '.$model->title,
                    'body' => $body
                );
                return json_encode($response);
            }
            else  return false;
        }
        else
            return false;
    }

    public function allow($college)
    {
        $allow = false;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $allow = true;
        else
        {
            if(is_array(Yii::$app->user->identity->college))
            {
                if(array_search($college, Yii::$app->user->identity->college) !== false)
                    $allow = true;
            }
            else if($college == Yii::$app->user->identity->college)
                $allow = true;
        }
        return $allow;
    }

}
