<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Brokers;
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
$('#broker').select2({
    placeholder: "فیلتر بر کارگزار"
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
if(isset($_GET['date1']))
    $date1 = $_GET['date1'];
else
    $date1 = '';
if(isset($_GET['date2']))
    $date2 = $_GET['date2'];
else
    $date2 = '';
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
            <input value="<?= $date1 ?>" class="form-control dob-picker text-start" placeholder="از تاریخ" name="date1">
            <input value="<?= $date2 ?>" class="form-control dob-picker text-start" placeholder="تا تاریخ" name="date2">
            <?php
            echo $form->field($model, 'username')->textInput(
                [
                    'placeholder' => 'فیلتر نام کاربری',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'last_name')->textInput(
                [
                    'placeholder' => 'فیلتر نام خانوادگی',
                    'class' => 'form-control form-control-sm',
                ]
            )->label(false);
            ?>
            <?php
            if(Yii::$app->user->identity->role == 'user')
                echo $form->field($model, 'orders[college]')->dropDownList(
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
            echo $form->field($model, 'shares[broker]')->dropDownList(
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
            $username = '';
            $last_name = '';
            $college = '';
            $broker = '';
            if (isset(Yii::$app->request->queryParams['OrdersSearch']) && (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp'))
            {
                if(isset(Yii::$app->request->queryParams['OrdersSearch']['username']))
                    $username = Yii::$app->request->queryParams['OrdersSearch']['username'];
                if(isset(Yii::$app->request->queryParams['OrdersSearch']['last_name']))
                    $last_name = Yii::$app->request->queryParams['OrdersSearch']['last_name'];
                if((Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') && isset(Yii::$app->request->queryParams['OrdersSearch']['shares']['college']))
                    $college = Yii::$app->request->queryParams['OrdersSearch']['shares']['college'];
                $broker = Yii::$app->request->get('OrdersSearch')['shares']['broker'] ?? '';
            }
            if(Yii::$app->user->identity->role == 'broker')
            {
                $brokerDetail = Brokers::find()->where(['connector_info.mobile' => Yii::$app->user->identity->username])->one();
                $broker = (string) $brokerDetail->_id;
            }
            ?>
            <?php $form = ActiveForm::begin([
//                'action'=>['report'],
                'action'=>['create_excel'],
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
            <?= $form->field($model, 'last_name')->hiddenInput(['value' => $last_name])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <?= $form->field($model, 'shares[broker]')->hiddenInput(['value' => $broker])->label(false); ?>
            <input type="hidden" name="date1" value="<?= $date1 ?>">
            <input type="hidden" name="date2" value="<?= $date2 ?>">
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>