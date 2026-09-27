<?php
$this->title = 'ایجاد کارگزار حقیقی';
use yii\widgets\ActiveForm;
use frontend\assets\SingleAsset;
use frontend\assets\Select2Asset;
use app\models\Brokers;
SingleAsset::register($this);
Select2Asset::register($this);
$model = new Brokers();
$script = <<< JS
   $(function(){
    $("#user").keypress(function(event){
        var ew = event.which;
        if(ew == 32)
            return true;
        if(48 <= ew && ew <= 57)
            return true;
        if(65 <= ew && ew <= 90)
            return true;
        if(97 <= ew && ew <= 122)
            return true;
        return false;
    });
});
JS;
$this->registerJs($script);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت کارگزاران</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">ایجاد کارگزار حقیقی</a>
            </li>
        </ol>
    </nav>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">فرم ایجاد کارگزار حقیقی</h5>
                    <span class="badge bg-label-secondary"><a href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-brokers') ?>">بازگشت</a></span>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action'=>['create_natural_2'],
                            'options' => [
                                'class' => 'card-body',
                                'enctype'=>'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">1. اطلاعات کارگزار</h6>
                    <div class="row">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">دانشکده *</label>
                            <?php
                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                echo $form->field($model, 'college')->dropDownList(
                                    $colleges,
                                    [
                                        'prompt' => 'لطفا دانشکده را مشخص کنید',
                                        'class' => 'select2 form-select',
                                        'required' => true,
                                        'data-allow-clear' => true,
                                        'id' => '',
                                    ]
                                )->label(false);
                            else
                                echo $form->field($model, 'college')->dropDownList(
                                    $colleges,
                                    [
                                        'class' => 'select2 form-select',
                                        'required' => true,
                                        'data-allow-clear' => true,
                                        'options' =>
                                            [
                                                $model->college => ['selected' => true]
                                            ]
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
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام کارگزار را وارد کنید\')',
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
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی کارگزار را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">شماره همراه (لطفا کیبور خود را بر روی انگلیسی بگذارید) *</label>
                            <?= $form->field($model, 'connector_info[mobile]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا شماره همراه کارگزار را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">کد ملی (لطفا کیبور خود را بر روی انگلیسی بگذارید) *</label>
                            <?= $form->field($model, 'connector_info[id]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی کارگزار را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
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
                    <?php
                    if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                    {
                        ?>
                        <h6 class="mb-3 fw-normal"> اطلاعات مالی</h6>
                        <div class="row">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">شناسه واریز کارگزار *</label>
                                <?= $form->field($model, 'financial_info[id]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا شماره حساب کارگزار را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
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
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا ساب سرویس آی دی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                    ]
                                )->label(false); ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal"> اطلاعات قرارداد (برای ثبت کارگزار ثبت حداقل یک قرارداد الزامی می باشد. بعد از ثبت کارگزار می توانید قراردادهای دیگر کارگزار را اضافه کنید)</h6>
                    <div class="row">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان قرارداد *</label>
                            <?= $form->field($model, 'contracts[title]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان قرارداد را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">درصد سهم کارگزار (لطفا کیبور خود را بر روی انگلیسی قرار دهید) *</label>
                            <?= $form->field($model, 'contracts[share]')->textInput(
                                [
                                    'class' => 'form-control form-control input',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا درصد سهم کارگزار را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)  || (event.charCode == 46)"
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">تاریح اتمام قرارداد *</label>
                            <?= $form->field($model, 'contracts[expiration_date]')->textInput(
                                [
                                    'class' => 'form-control text-start dob-picker',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام قرارداد را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                    <hr class="my-4 mx-n4">
                    <h6 class="mb-3 fw-normal">3. فایل ها</h6>
                    <div class="row">
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-2">
                                <div class="dz-message needsclick">
                                    <?= $form->field($model, 'id_file')->fileInput(
                                        [
                                            'class' => 'form-control text-start drop-file',
                                            'required' => true
                                        ]
                                    )->label(false); ?>
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">تصویر کارت ملی کارگزار *</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-3">
                                <div class="dz-message needsclick">
                                    <?= $form->field($model, 'contract_file')->fileInput(
                                        [
                                            'class' => 'form-control text-start drop-file',
                                            'required' => true
                                        ]
                                    )->label(false); ?>
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">فایل قرارداد کارگزار *</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="d-grid gap-2 col-lg-6 mx-auto">
                            <button class="btn btn-info btn-lg" type="submit">ثبت کارگزار</button>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

</div>
