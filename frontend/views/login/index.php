<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\User;
use common\models\AdminLoginForm;
$front = Yii::getAlias('@front');
$this->title = 'مدیریت سامانه';
$this->params['breadcrumbs'][] = $this->title;
$adminimage=Yii::getAlias('@adminimage');
?>
<?php
$model = new AdminLoginForm();
?>
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
            <!-- Register -->
            <div class="card">
                <div class="card-body">
                    <!-- Logo -->
                    <div class="app-brand justify-content-center">
                        <img src="<?= $front ?>/assets/images/logo.png" style="max-width: 150px;max-height: 150px;">
                    </div>
                    <!-- /Logo -->
                    <p class="mb-4">مدیریت سامانه جامه آموزش های کاربردی و حرفه ای </p>

                    <?php $form = ActiveForm::begin([
                        'method'=>'post',
                        'options' => [
                            'class' => 'mb-3',
                        ],
                        'fieldConfig' => [
                            'options' => [
                                'tag' => false,
                            ],
                        ],
                    ]); ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">نام کاربری *</label>
                            <?= $form->field($model, 'username')->textInput(
                                [
                                    'autofocus' => true,
                                    'class' => 'form-control text-start',
                                    'required'=>true,
                                    'oninvalid'=>'this.setCustomValidity(\'لطفا نام کاربری خود را وارد کنید\')',
                                    'oninput'=>'setCustomValidity(\'\')',
                                    'dir' => 'ltr'
                                ]
                            )->label(false) ?>
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">رمز عبور *</label>
                                <a href="auth-forgot-password-basic.html">
                                    <small>رمز عبور را فراموش کردید؟</small>
                                </a>
                            </div>
                            <div class="input-group input-group-merge">
                                <?= $form->field($model, 'password')->passwordInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'placeholder'=>'رمز عبور *',
                                        'required'=>true,
                                        'oninvalid'=>'this.setCustomValidity(\'لطفا رمز عبور خود را وارد کنید\')',
                                        'oninput'=>'setCustomValidity(\'\')',
                                        'aria-describedby' => 'password',
                                        'dir' => 'ltr'
                                    ]
                                )->label(false) ?>
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">ورود</button>
                        </div>
                    <?php ActiveForm::end(); ?>

                    <p class="text-center">
                        <span>کاربر جدید هستید؟</span>
                        <a href="auth-register-basic.html">
                            <span>یک حساب بسازید</span>
                        </a>
                    </p>

                    <div class="divider my-4">
                        <div class="divider-text">یا</div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <a href="javascript:;" class="btn btn-icon btn-label-facebook me-3">
                            <i class="tf-icons bx bxl-facebook"></i>
                        </a>

                        <a href="javascript:;" class="btn btn-icon btn-label-google-plus me-3">
                            <i class="tf-icons bx bxl-google-plus"></i>
                        </a>

                        <a href="javascript:;" class="btn btn-icon btn-label-twitter">
                            <i class="tf-icons bx bxl-twitter"></i>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /Register -->
        </div>
    </div>
</div>
