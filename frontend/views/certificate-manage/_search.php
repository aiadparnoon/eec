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
$status = array(
    '0' => 'تائید شده غیر فعال',
    '1' => 'تائید شده',
    '2' => 'در انتظار بررسی',
    '3' => 'پیش نویس',
    '4' => 'نیاز به اصلاح',
    '5' => 'رد شده',
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
                    'placeholder' => 'فیلتر بر اساس نام دوره',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'license_code')->textInput(
                [
                    'placeholder' => 'فیلتر بر شماره مجوز',
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
            if (isset(Yii::$app->request->queryParams['CoursesSearch'])) {
                $title = Yii::$app->request->queryParams['CoursesSearch']['title']['main_fa'];
                $licenseCode = Yii::$app->request->queryParams['CoursesSearch']['license_code'];
                $college = Yii::$app->request->queryParams['CoursesSearch']['college'];
            } else {
                $title = '';
                $licenseCode = '';
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
            <?= $form->field($model, 'title[main_fa]')->hiddenInput(['value' => $title])->label(false); ?>
            <?= $form->field($model, 'license_code')->hiddenInput(['value' => $licenseCode])->label(false); ?>
            <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
            <button class="btn btn-secondary" style="margin-right: 5px;" type="button" data-bs-toggle="modal" data-bs-target="#search-user">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                    <g fill="none" stroke="#fff" stroke-width="1.5">
                        <path d="M3 6c0-1.414 0-2.121.44-2.56C3.878 3 4.585 3 6 3s2.121 0 2.56.44C9 3.878 9 4.585 9 6s0 2.121-.44 2.56C8.122 9 7.415 9 6 9s-2.121 0-2.56-.44C3 8.122 3 7.415 3 6Zm0 12c0-1.414 0-2.121.44-2.56C3.878 15 4.585 15 6 15s2.121 0 2.56.44C9 15.878 9 16.585 9 18s0 2.121-.44 2.56C8.122 21 7.415 21 6 21s-2.121 0-2.56-.44C3 20.122 3 19.415 3 18Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h6m3-9v5" />
                        <path d="M15 6c0-1.414 0-2.121.44-2.56C15.878 3 16.585 3 18 3s2.121 0 2.56.44C21 3.878 21 4.585 21 6s0 2.121-.44 2.56C20.122 9 19.415 9 18 9s-2.121 0-2.56-.44C15 8.122 15 7.415 15 6Z" />
                        <path stroke-linecap="round" d="M21 12h-6c-1.414 0-2.121 0-2.56.44C12 12.878 12 13.585 12 15m0 2.77v2.768M15 15v1.5c0 1.446.784 1.5 2 1.5a1 1 0 0 1 1 1m-2 2h-1m3-6c1.414 0 2.121 0 2.56.44s.44 1.148.44 2.564s0 2.125-.44 2.565c-.32.32-.783.408-1.56.431" />
                    </g>
                </svg>
            </button>
        </div>
    </div>
</nav>



<div class="modal fade" id="search-user" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">جستجو بر اساس شماره سریال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <input type="text" id="serial-number" class="form-control" placeholder="شماره سریال" aria-label="Recipient's username with two button addons">
                    <button class="btn btn-secondary" type="button" id="check-serial-number-btn">جستجو</button>
                </div>
                <div id="serial-check-result" class="form-text"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>