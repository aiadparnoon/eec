<?php
$this->title = 'ویرایش کارگزار حقیقی';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\SingleAsset;
use frontend\assets\Select2Asset;
use app\models\Brokers;
SingleAsset::register($this);
Select2Asset::register($this);
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.error("لطفا اطلاعات مالی کارگزار را وارد کنید", {
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
                <a href="javascript:void(0);">مدیریت کارگزاران</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">ویرایش کارگزار حقیقی</a>
            </li>
        </ol>
    </nav>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">فرم ویرایش کارگزار <?= ' ( '.Html::encode($model->connector_info['first_name'].' '.$model->connector_info['last_name']).')' ?></h5>
                    <?php
                    if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                    {
                        ?>
                        <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                            <?php
                            if($model->status != '1')
                            {
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['confirm_broker'],
                                        "method" => "post",
                                    ]
                                );
                                ?>
                                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                                <input type="hidden" name="page" value="packages">
                                <button type="submit" class="btn btn-success">تائید کارگزار</button>
                                <?php
                                ActiveForm::end();
                            }
                            ?>
                            <?php
                            if($model->status != '3')
                                echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                            if($model->status != '4')
                                echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action'=>['edit_natural_broker'],
                            'options' => [
                                'class' => 'card-body',
                                'enctype'=>'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">2. اطلاعات کارگزار</h6>
                    <div class="row">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">دانشکده *</label>
                            <?php
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                    'class' => 'select2 form-select form-select-lg none-parent',
                                    'id' => '',
                                    'data-allow-clear' => true,
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا دانشکده را مشخص کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">نام *</label>
                            <?= $form->field($model, 'connector_info[first_name]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام رابط را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">نام خانوادگی *</label>
                            <?= $form->field($model, 'connector_info[last_name]')->textInput(
                                [
                                    'class' => 'form-control input',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی رابط را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">شماره همراه *</label>
                            <input value="<?= Html::encode($model->connector_info['mobile']) ?>" class="form-control text-start" readonly>
                            <?= $form->field($model, 'connector_info[mobile]')->hiddenInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی رابط را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">کد ملی *</label>
                            <?= $form->field($model, 'connector_info[id]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی رابط را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">تاریخ تولد *</label>
                            <?= $form->field($model, 'connector_info[birth_day]')->textInput(
                                [
                                    'class' => 'form-control dob-picker form-control input text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ تولد را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-4 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">کدپستی </label>
                            <?= $form->field($model, 'connector_info[zip_code]')->textInput(
                                [
                                    'class' => 'form-control input text-start',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-8 col-md-8 col-sm-8 dol-lg-8 col-xl-8 mb-3">
                            <label for="nameWithTitle" class="form-label">آدرس *</label>
                            <?= $form->field($model, 'connector_info[address]')->textarea(
                                [
                                    'class' => 'form-control dob-picker form-control input text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا آدرس را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">2. اطلاعات مالی</h6>
                    <div class="row">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">شناسه واریز *</label>
                            <?= $form->field($model, 'financial_info[id]')->textInput(
                                [
                                    'class' => 'form-control text-start',
//                                    'required' => true,
//                                    'oninvalid' => 'this.setCustomValidity(\'لطفا شماره حساب کارگزار را وارد کنید\')',
//                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)",
                                    'readonly' => true,
                                    'value' => '0'
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">ساب سرویس آی دی *</label>
                            <?= $form->field($model, 'financial_info[sub_service_id]')->textInput(
                                [
                                    'class' => 'form-control form-control input',
//                                    'required' => true,
//                                    'oninvalid' => 'this.setCustomValidity(\'لطفا ساب سرویس آی دی را وارد کنید\')',
//                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">3. قراردادها</h6>
                    <?php
                    if($model->contracts != null)
                    {
                        $i = 0;
                        foreach ($model->contracts as $contract)
                        {
                            echo $form->field($model, 'contracts[' . $i . '][id]')->hiddenInput()->label(false);
                            echo $form->field($model, 'contracts[' . $i . '][status]')->hiddenInput()->label(false);
                            ?>
                            <div class="row">
                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                    <label for="nameWithTitle" class="form-label">عنوان قرارداد *</label>
                                    <?php
                                    if(($model->status != '1') || (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
                                        echo $form->field($model, 'contracts['.$i.'][title]')->textInput(
                                            [
                                                'class' => 'form-control text-start',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان قرارداد را وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                            ]
                                        )->label(false);
                                    else
                                        echo '<input readonly disabled type="text" class="form-control text-start" value="'.Html::encode($contract['title']).'">';
                                    ?>
                                </div>
                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                    <label for="nameWithTitle" class="form-label">درصد سهم کارگزار *</label>
                                    <?php
                                    if(($model->status != '1') || (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
                                        echo $form->field($model, 'contracts['.$i.'][share]')->textInput(
                                            [
                                                'class' => 'form-control form-control input',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا درصد سهم کارگزار را وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                            ]
                                        )->label(false);
                                    else
                                        echo '<input readonly disabled type="text" class="form-control text-start" value="'.$contract['share'].'">';
                                    ?>
                                </div>
                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                    <label for="nameWithTitle" class="form-label">تاریخ اتمام قرارداد *</label>
                                    <?php
                                    if(($model->status != '1') || (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
                                        echo $form->field($model, 'contracts['.$i.'][expiration_date]')->textInput(
                                            [
                                                'class' => 'form-control form-control input dob-picker',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام قرارداد کارگزار را وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                            ]
                                        )->label(false);
                                    else
                                    {
                                        if(array_key_exists('expiration_date', $contract))
                                            echo '<input type="text" class="form-control" disabled readonly value="'.$contract['expiration_date'].'">';
                                        else
                                            echo '<input type="text" class="form-control" disabled readonly value="-">';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                            $i++;
                        }
                    }
                    ?>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">3. فایل ها</h6>
                    <div class="row">
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-1">
                                <div class="dz-message needsclick">
                                    <?= $form->field($model, 'id_file')->fileInput(
                                        [
                                            'class' => 'form-control text-start drop-file',
                                        ]
                                    )->label(false); ?>
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">تصویر کارت ملی </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-3">
                                <div class="dz-message needsclick">
                                    <?php
                                    if((Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
                                        echo $form->field($model, 'contract_file')->fileInput(
                                            [
                                                'class' => 'form-control text-start drop-file',
                                            ]
                                        )->label(false);
                                    ?>
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">فایل قرارداد کارگزار </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="d-grid gap-2 col-lg-6 mx-auto">
                            <button class="btn btn-warning btn-lg" type="submit">ویرایش کارگزار</button>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

</div>


<?php
if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
{
    ?>
    <div class="modal fade" id="back" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">نیاز به اصلاح کارگزار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['back_broker'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل نیاز به اصلاح کارگزار *</label>
                        <?= $form->field($model, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل نیاز به اصلاح کارگزار را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت اصلاح کارگزار</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reject" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">رد کردن کارگزار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['reject_broker'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد کارگزار *</label>
                        <?= $form->field($model, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل رد کارگزار را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت رد کارگزار</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
