<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\UploadedExcels;
/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$status = array(
    9 => 'غیر فعال',
    10 => 'فعال',
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
            echo $form->field($model, 'last_name')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام خانوادگی دانشپذیر',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'username')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام کاربری دانشپذیر',
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
                    'prompt' => 'فیلتر بر اساس وضعیت دانشپذیر',
                    'class' => 'form-select none-parent',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['CoursesSearch'])) {
                $title = Yii::$app->request->queryParams['CoursesSearch']['title'];
                $status = Yii::$app->request->queryParams['CoursesSearch']['status'];
                $college = Yii::$app->request->queryParams['CoursesSearch']['college'];
            } else {
                $title = '';
                $status = '';
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
            <?= $form->field($model, 'status')->hiddenInput(['value' => $status])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>