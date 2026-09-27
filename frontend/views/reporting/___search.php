<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Brokers;
/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$select2 = <<< JS
    $('#college').select2({
    placeholder: "فیلتر دانشکده"
});
$('#status').select2({
    placeholder: "فیلتر بر اساس وضعیت"
});
$('#broker').select2({
    placeholder: "فیلتر کارگزار"
});
 $("#college").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
   $("#broker").select2({
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
                'action'=>['courses-financial'],
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
            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
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
                echo $form->field($model, 'registrant')->dropDownList(
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
            $broker = '';
            if (isset(Yii::$app->request->queryParams['CoursesFinancialSearch']) && (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp'))
            {
                if((Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') && isset(Yii::$app->request->queryParams['CoursesFinancialSearch']['shares']['college']))
                    $college = Yii::$app->request->queryParams['CoursesFinancialSearch']['college'];
                $broker = Yii::$app->request->queryParams['CoursesFinancialSearch']['registrant'] ?? '';
            }
            if(Yii::$app->user->identity->role == 'broker')
            {
                $broker = Yii::$app->user->identity->username;
            }
            ?>
            <?php $form = ActiveForm::begin([
                'action'=>['financial_excel'],
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
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <?= $form->field($model, 'registrant')->hiddenInput(['value' => $broker])->label(false); ?>
            <input type="hidden" name="date1" value="<?= $date1 ?>">
            <input type="hidden" name="date2" value="<?= $date2 ?>">
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>