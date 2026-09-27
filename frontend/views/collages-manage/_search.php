<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */

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
                    'placeholder' => 'فیلتر بر اساس نام واحد',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['CollegesSearch'])) {
                $title = Yii::$app->request->queryParams['CollegesSearch']['title'];
            } else {
                $title = '';
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
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>