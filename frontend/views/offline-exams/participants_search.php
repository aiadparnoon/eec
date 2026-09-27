<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$select2 = <<< JS
    $('#college').select2({
    placeholder: "فیلتر بر اساس دانشکده"
});
$('#status').select2({
    placeholder: "فیلتر بر اساس وضعیت"
});
 $("#college").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
   $("#status").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
JS;
$this->registerJs($select2);
?>

<div class="card mb-4">
    <h5 class="card-header heading-color">جستجو در بین شرکت کنندگان</h5>
    <?php $form = ActiveForm::begin([
        'action'=>['participants'],
        'method'=>'get',
        'options' => [
            'class' => 'card-body',
        ],
        'fieldConfig' => [
            'options' => [
                'tag' => false,
            ],
        ],
    ]); ?>
    <input type="hidden" name="_id" value="<?= (string) $examDetail->_id ?>">
        <div class="row g-3">
            <div class="col-md-3">
                <?php
                echo $form->field($model, 'username')->textInput(
                    [
                        'placeholder' => 'نام کاربری',
                        'class' => 'form-control form-control-sm',
                        'id' => '',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                echo $form->field($model, 'orders[applicant_id]')->textInput(
                    [
                        'placeholder' => 'شماره داوطلبی',
                        'class' => 'form-control form-control-sm',
                        'id' => '',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                echo $form->field($model, 'applicant_info[id]')->textInput(
                    [
                        'placeholder' => 'کد ملی',
                        'class' => 'form-control form-control-sm',
                        'id' => '',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                $utStudent = array(
                    '1' => 'هستم',
                    '0' => 'نیستم'
                );
                echo $form->field($model, 'applicant_info[ut_student]')->dropDownList(
                    $utStudent,
                    [
                        'prompt' => 'دانشجوی دکتری دانشگاه تهران',
                        'class' => 'form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                $utStudent = array(
                    '1' => 'هستم',
                    '0' => 'نیستم'
                );
                echo $form->field($model, 'applicant_info[master_student]')->dropDownList(
                    $utStudent,
                    [
                        'prompt' => 'دانشجوی کارشناسی ارشد دانشگاه تهران',
                        'class' => 'form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                $gender = array(
                    '1' => 'آقا',
                    '0' => 'خانم'
                );
                echo $form->field($model, 'applicant_info[gender]')->dropDownList(
                    $gender,
                    [
                        'prompt' => 'جنسیت',
                        'class' => 'form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                $assistant = array(
                    '1' => 'بله',
                    '0' => 'خیر'
                );
                echo $form->field($model, 'applicant_info[needs_assistant]')->dropDownList(
                    $assistant,
                    [
                        'prompt' => 'نیازمند منشی',
                        'class' => 'form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3">
                <?php
                $lh = array(
                    '1' => 'بله',
                    '0' => 'خیر'
                );
                echo $form->field($model, 'applicant_info[left_handed]')->dropDownList(
                    $lh,
                    [
                        'prompt' => 'چپ دست',
                        'class' => 'form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-4">
                <?php
                echo $form->field($model, 'applicant_info[major]')->textInput(
                    [
                        'placeholder' => 'رشته تحصیلی',
                        'class' => 'form-control form-control-sm',
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-4">
                <?php
                echo $form->field($model, 'applicant_info[student_id]')->textInput(
                    [
                        'placeholder' => 'شماره دانشجویی دکتری',
                        'class' => 'form-control form-control-sm',
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-4">
                <?php
                echo $form->field($model, 'applicant_info[master_student_id]')->textInput(
                    [
                        'placeholder' => 'شماره دانشجویی کارشناسی ارشد',
                        'class' => 'form-control form-control-sm',
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary me-sm-3 me-1 btn-block">جستجو</button>
                <?php ActiveForm::end(); ?>

            </div>
            <div class="col-md-4">
                <?php
                if (isset(Yii::$app->request->queryParams['ExamParticipants']))
                {
                    $username = Yii::$app->request->queryParams['ExamParticipants']['username'];
//                $status = Yii::$app->request->queryParams['ExamParticipants']['status'];
                    $applicantId = Yii::$app->request->queryParams['ExamParticipants']['orders']['applicant_id'];
                    $utStudent = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['ut_student'];
                    $utStudentId = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['student_id'];
                    $utMasterStudent = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['master_student'];
                    $utMasterStudentId = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['master_student_id'];
                    $major = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['major'];
                    $id = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['id'];
                    $gender = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['gender'];
                    $needs_assistant = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['needs_assistant'];
                    $left_handed = Yii::$app->request->queryParams['ExamParticipants']['applicant_info']['left_handed'];
                }
                else
                {
                    $username = '';
//                $status = '';
                    $id = '';
                    $utStudent = '';
                    $utStudentId = '';
                    $utMasterStudent = '';
                    $utMasterStudentId = '';
                    $major = '';
                    $applicantId = '';
                    $gender = '';
                    $needs_assistant = '';
                    $left_handed = '';
                }
                ?>
                <?php $form = ActiveForm::begin([
                    'action'=>['report'],
                    'method'=>'get',
                    'fieldConfig' => [
                        'options' => [
                            'tag' => false,
                        ],
                    ],
                ]); ?>
                <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
                <?= $form->field($model, 'orders[applicant_id]')->hiddenInput(['value' => $applicantId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[id]')->hiddenInput(['value' => $id])->label(false); ?>
                <?= $form->field($model, 'applicant_info[ut_student]')->hiddenInput(['value' => $utStudent])->label(false); ?>
                <?= $form->field($model, 'applicant_info[student_id]')->hiddenInput(['value' => $utStudentId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[master_student]')->hiddenInput(['value' => $utMasterStudent])->label(false); ?>
                <?= $form->field($model, 'applicant_info[master_student_id]')->hiddenInput(['value' => $utMasterStudentId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[major]')->hiddenInput(['value' => $major])->label(false); ?>
                <?= $form->field($model, 'applicant_info[gender]')->hiddenInput(['value' => $gender])->label(false); ?>
                <?= $form->field($model, 'applicant_info[needs_assistant]')->hiddenInput(['value' => $needs_assistant])->label(false); ?>
                <?= $form->field($model, 'applicant_info[left_handed]')->hiddenInput(['value' => $left_handed])->label(false); ?>
                <?= $form->field($model, '_id')->hiddenInput(['value' => (string) $examDetail->_id])->label(false); ?>
                <button type="submit" class="btn btn-warning me-sm-3 me-1">دریافت فایل اکسل</button>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="col-md-4">
                <?php $form = ActiveForm::begin([
                    'action'=>['financial_report'],
                    'method'=>'get',
                    'fieldConfig' => [
                        'options' => [
                            'tag' => false,
                        ],
                    ],
                ]); ?>
                <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
                <?= $form->field($model, 'orders[applicant_id]')->hiddenInput(['value' => $applicantId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[id]')->hiddenInput(['value' => $id])->label(false); ?>
                <?= $form->field($model, 'applicant_info[ut_student]')->hiddenInput(['value' => $utStudent])->label(false); ?>
                <?= $form->field($model, 'applicant_info[student_id]')->hiddenInput(['value' => $utStudentId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[master_student]')->hiddenInput(['value' => $utMasterStudent])->label(false); ?>
                <?= $form->field($model, 'applicant_info[master_student_id]')->hiddenInput(['value' => $utMasterStudentId])->label(false); ?>
                <?= $form->field($model, 'applicant_info[major]')->hiddenInput(['value' => $major])->label(false); ?>
                <?= $form->field($model, 'applicant_info[gender]')->hiddenInput(['value' => $gender])->label(false); ?>
                <?= $form->field($model, 'applicant_info[needs_assistant]')->hiddenInput(['value' => $needs_assistant])->label(false); ?>
                <?= $form->field($model, 'applicant_info[left_handed]')->hiddenInput(['value' => $left_handed])->label(false); ?>
                <?= $form->field($model, '_id')->hiddenInput(['value' => (string) $examDetail->_id])->label(false); ?>
                <button class="btn btn-info" style="margin-right: 5px;" type="submit">
                    دریافت گزارش مالی
                </button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>


</div>
