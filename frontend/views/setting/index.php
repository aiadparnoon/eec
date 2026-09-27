<?php
$this->title = 'تغییر رمز عبور';
use common\models\Admin;
use yii\widgets\ActiveForm;

$model = Yii::$app->user->identity;
if(Yii::$app->session->has('status'))
{
    if(Yii::$app->session->getFlash('status') == '1')
        $script = <<< JS
    toastr.success("پسورد شما تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '2')
        $script = <<< JS
    toastr.warning("خطایی رخ داده، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '3')
        $script = <<< JS
    toastr.error("رمز عبور فعلی اشتباه می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.warning("مرز عبور جدید با تکرار آن همسان نمی باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">تنظیمات</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">تغییر رمز عبور</a>
            </li>
        </ol>
    </nav>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">تغییر رمز عبور</h5></div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(
                [
                    'action' => ['change_password'],
                    "method" => "post",
                    'options' => [
                        'class' => 'fv-plugins-bootstrap5 fv-plugins-framework',
                        'enctype' => 'multipart/form-data'
                    ],
                ]
            ); ?>
                <div class="row">
                    <div class="mb-3 col-md-6 form-password-toggle fv-plugins-icon-container">
                        <label class="form-label" for="currentPassword">رمز عبور کنونی</label>
                        <div class="input-group input-group-merge has-validation">
                            <input required class="form-control text-start" type="password" dir="ltr" name="currentPassword" id="currentPassword" placeholder="············">
                            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                        </div><div class="fv-plugins-message-container invalid-feedback"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6 form-password-toggle fv-plugins-icon-container">
                        <label class="form-label" for="newPassword">رمز عبور جدید</label>
                        <div class="input-group input-group-merge has-validation">
                            <input required class="form-control text-start" type="password" dir="ltr" id="newPassword" name="newPassword" placeholder="············">
                            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                        </div><div class="fv-plugins-message-container invalid-feedback"></div>
                    </div>

                    <div class="mb-3 col-md-6 form-password-toggle fv-plugins-icon-container">
                        <label class="form-label" for="confirmPassword">تایید رمز عبور جدید</label>
                        <div class="input-group input-group-merge has-validation">
                            <input required class="form-control text-start" type="password" dir="ltr" name="confirmPassword" id="confirmPassword" placeholder="············">
                            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                        </div><div class="fv-plugins-message-container invalid-feedback"></div>
                    </div>
                    <div class="col-12 mt-1">
                        <button type="submit" class="btn btn-primary me-2">ذخیره تغییرات</button>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

