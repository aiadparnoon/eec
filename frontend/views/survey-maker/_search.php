<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
?>
<?php $form = ActiveForm::begin([
    'action' => ['index'],
    'method' => 'get',
]); ?>
    <div class="row mb-3">
        <div class="faq-header d-flex flex-column justify-content-center">
            <div class="row">
                <div class="mb-3 col-lg-3 col-xl-3 col-12 mb-0">
                    <label class="form-label" for="form-repeater-1-1"></label>
                    <?= $form->field($model, 'title')->textInput(
                        [

                            'class' => 'form-control text-start',
                            'placeholder' => 'عنوان نظرسنجی',
                        ]
                    )->label(false); ?>
                </div>
                <div class="mb-3 col-lg-3 col-xl-3 col-3 d-flex align-items-center mb-0">
                    <button type="submit" class="btn btn-primary mt-4 btn-block">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>
                        <span class="align-middle">جستجو</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php ActiveForm::end(); ?>