<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use app\models\Members;
use yii\widgets\ActiveForm;

$this->title = 'Main Admin Page';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="form-holder">
    <div class="form-content">
        <div class="form-items">
            <h3 class="lalezar">ورود به مدیریت، های ایران</h3>
            <p></p>
            <div class="page-links right">
                <a href="expert.php" class="lalezar">ورود کارشناس</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="index.php" class="active lalezar">ورود مدیریت</a>
            </div>
            <?php
            $form=ActiveForm::begin();
            ?>
            <?=$form->field($model,'login')->label('')->textInput(['placeholder'=>'نام کاربری','class'=>'lalezar right']); ?>
            <?=$form->field($model,'passwd')->label('')->passwordInput(['placeholder'=>'رمز عبور','class'=>'lalezar right']); ?>
            <?= Html::submitButton('ورود', ['class' => 'ibtn lalezar']);?>
            <?php ActiveForm::end(); ?>
            <div class="other-links">
            </div>
        </div>
    </div>
</div>
</div>
</div>
