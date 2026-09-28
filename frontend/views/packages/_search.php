<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$select2 = <<< JS
    $('#broker').select2({
    placeholder: "فیلتر کارگزار"
});
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
  $("#content_type").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
JS;
$this->registerJs($select2);
$status = array(
    '0' => 'تائید شده غیر فعال',
    '1' => 'تائید شده',
    '2' => 'در انتظار بررسی',
    '3' => 'پیش نویس',
    '4' => 'نیاز به اصلاح',
    '5' => 'رد شده',
    '6' => 'اتمام یافته',
);
$type = array(
        '3' => 'محتوا محور',
        '1' => 'غیرحضوری',
        '2' => 'نیمه حضوری',
        '4' => 'حضوری',
);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-5">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <?php $form = ActiveForm::begin([
                'action'=>['index'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <?php
            echo $form->field($model, 'title[main_fa]')->textInput(
                [
                    'placeholder' => 'فیلتر نام دوره',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'license_code')->textInput(
                [
                    'placeholder' => 'فیلتر کد مجوز دوره',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'status')->dropDownList(
                $status,
                [
                    'prompt' => 'فیلتر وضعیت دوره',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'content_type')->dropDownList(
                $type,
                [
                    'prompt' => 'فیلتر نوع دوره',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'content_type',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'college')->dropDownList(
                $colleges,
                [
                    'prompt' => 'فیلتر دانشکده',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'college',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            if(Yii::$app->user->identity->role != 'broker')
                echo $form->field($model, 'broker[_id]')->dropDownList(
                    $brokers,
                    [
                        'prompt' => 'فیلتر کارگزار',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'broker',
                        'data-allow-clear' => true
                    ]
                )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['CoursesSearch']))
            {
                if (isset(Yii::$app->request->queryParams['CoursesSearch']['title']['main_fa']))
                    $title = Yii::$app->request->queryParams['CoursesSearch']['title']['main_fa'];
                else
                    $title = '';
                if (isset(Yii::$app->request->queryParams['CoursesSearch']['status']))
                    $status = Yii::$app->request->queryParams['CoursesSearch']['status'];
                else
                    $status = '';
                if (isset(Yii::$app->request->queryParams['CoursesSearch']['college']))
                    $college = Yii::$app->request->queryParams['CoursesSearch']['college'];
                else
                    $college = '';
                if (isset(Yii::$app->request->queryParams['CoursesSearch']['broker']['_id']))
                    $broker = Yii::$app->request->queryParams['CoursesSearch']['broker']['_id'];
                else
                    $broker = '';
            }
            else
            {
                $title = '';
                $status = '';
                $college = '';
                $broker = '';
            }
            ?>
            <?php $form = ActiveForm::begin([
                'action'=>['report'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <?= $form->field($model, 'title[main_fa]')->hiddenInput(['value' => $title])->label(false); ?>
            <?= $form->field($model, 'status')->hiddenInput(['value' => $status])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <?= $form->field($model, 'broker[_id]')->hiddenInput(['value' => $broker])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>