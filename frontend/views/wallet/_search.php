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
    placeholder: "فیلتر بر اساس نوع"
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
$type = array(
    '0' => 'افزایش با وب سرویس',
    '1' => 'افزایش با لینک',
    '2' => 'کاهش شارژ',
    '3' => 'افزایش با صورت حساب مالی'
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
            echo $form->field($model, 'broker_id')->textInput(
                [
                    'placeholder' => 'نام کاربری کارگزار',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'status')->dropDownList(
                $type,
                [
                    'prompt' => 'فیلتر بر اساس نوع تراکنش',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                echo $form->field($model, 'college')->dropDownList(
                    $colleges,
                    [
                        'prompt' => 'فیلتر بر اساس دانشکده',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
            else
                echo $form->field($model, 'college')->hiddenInput(
                    [
                        'value' => Yii::$app->user->identity->college,
                    ]
                )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            $username = '';
            $type = '';
            $college = '';
            if (isset(Yii::$app->request->queryParams['WalletTransactionsSearch']))
            {
                $username = Yii::$app->request->queryParams['WalletTransactionsSearch']['username'];
                $type = Yii::$app->request->queryParams['WalletTransactionsSearch']['type'];
                $college = Yii::$app->request->queryParams['WalletTransactionsSearch']['college'];
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
            <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
            <?= $form->field($model, 'type')->hiddenInput(['value' => $type])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>