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
            echo $form->field($model, 'title')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس عنوان درس',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            if(Yii::$app->user->identity->role == 'user')
                echo $form->field($model, 'college')->dropDownList(
                    $colleges,
                    [
                        'prompt' => 'فیلتر بر اساس دانشکده',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'college',
                        'data-allow-clear' => true
                    ]
                )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['LessonsSearch']))
            {
                if(isset($_GET['LessonsSearch']['title']))
                    $title = Yii::$app->request->queryParams['LessonsSearch']['title'];
                else
                    $title = '';
                if(isset($_GET['LessonsSearch']['college']))
                    $college = Yii::$app->request->queryParams['LessonsSearch']['college'];
                else
                    $college = '';
            } else {
                $title = '';
                $college = '';
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
            <?= $form->field($model, 'title')->hiddenInput(['value' => $title])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>