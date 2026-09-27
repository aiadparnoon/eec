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
    '1' => 'افزایش با لینک',
    '2' => 'افزایش با وب سرویس',
    '3' => 'کاهش شارژ (افزودم عضو)',
    '4' => 'افزایش با صورت حساب مالی',
    '5' => 'افزایش با پرداخت مستقیم',
    '6' => 'افزایش با انصراف دانشپذیر',
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
            echo $form->field($model, 'payer[mobile]')->textInput(
                [
                    'placeholder' => 'نام کاربری پرداخت کننده',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'payer[last_name]')->textInput(
                [
                    'placeholder' => 'نام خانوادگی پرداخت کننده',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'payer[description]')->textInput(
                [
                    'placeholder' => 'توضیحات',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'payment_info[tref]')->textInput(
                [
                    'placeholder' => 'شماره رهگیری پرداخت',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'broker_id')->dropDownList(
                $brokers,
                [
                    'prompt' => 'فیلتر بر اساس کارگزار',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'type')->dropDownList(
                $type,
                [
                    'prompt' => 'فیلتر بر اساس نوع تراکنش',
                    'class' => 'select2 form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            $mobile = '';
            $lastName = '';
            $description = '';
            $tref = '';
            $type = '';
            if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']))
            {
                if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payer']))
                {
                    if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payer']['mobile']))
                        $mobile = Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payer']['mobile'];
                    if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payer']['last_name']))
                        $lastName = Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payer']['last_name'];
                }
                if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['description']))
                    $description = Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['description'];
                if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payment_info']))
                    if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payment_info']['tref']))
                        $tref = Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['payment_info']['tref'];
                if (isset(Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['type']))
                    $type = Yii::$app->request->queryParams['WalletTransactionsForBrokersSearch']['type'];
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
            <?= $form->field($model, 'payer[mobile]')->hiddenInput(['value' => $mobile])->label(false); ?>
            <?= $form->field($model, 'payer[last_name]')->hiddenInput(['value' => $lastName])->label(false); ?>
            <?= $form->field($model, 'description')->hiddenInput(['value' => $description])->label(false); ?>
            <?= $form->field($model, 'payment_info[tref]')->hiddenInput(['value' => $tref])->label(false); ?>
            <?= $form->field($model, 'type')->hiddenInput(['value' => $type])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>