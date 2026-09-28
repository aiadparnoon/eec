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
$('#broker').select2({
    placeholder: "فیلتر کارگزار"
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
                'action'=>['installments'],
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
            echo $form->field($model, 'last_name')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام خانوادگی',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'username')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام کاربری',
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
            <?php
            if(Yii::$app->user->identity->role != 'broker')
                echo $form->field($model, 'broker')->dropDownList(
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
            $college = '';
            if (isset(Yii::$app->request->queryParams['InstallmentsSearch']) && (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp'))
            {
                $last_name = Yii::$app->request->queryParams['InstallmentsSearch']['last_name'];
                $username = Yii::$app->request->queryParams['InstallmentsSearch']['username'];
                if(Yii::$app->user->identity->role == 'emp')
                    $college = Yii::$app->user->identity->college;
                else if(isset(Yii::$app->request->queryParams['InstallmentsSearch']['college']))
                    $college = Yii::$app->request->queryParams['InstallmentsSearch']['college'];
                $broker = Yii::$app->request->queryParams['InstallmentsSearch']['broker'];
            }
            else
            {
                $last_name = '';
                $username = '';
                $college = '';
                $broker = '';
            }
            ?>
            <?php $form = ActiveForm::begin([
                'action'=>['installments_report'],
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
            <?= $form->field($model, 'last_name')->hiddenInput(['value' => $last_name])->label(false); ?>
            <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <?= $form->field($model, 'broker')->hiddenInput(['value' => $broker])->label(false); ?>
            <input type="hidden" name="date1" value="<?= $date1 ?>">
            <input type="hidden" name="date2" value="<?= $date2 ?>">
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>