<?php
$this->title = 'ایجاد کارگزار';
use yii\widgets\ActiveForm;
use frontend\assets\SingleAsset;
use frontend\assets\Select2Asset;
use app\models\Brokers;
SingleAsset::register($this);
Select2Asset::register($this);
$model = new Brokers();
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">مدیریت کارگزاران /</span> ایجاد کارگزار
    </h4>
    <div class="row">
        <!-- Basic Layout -->
        <div class="col-xxl">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">فرم ایجاد کارگزار</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action'=>['create'],
                            'options' => [
                                'class' => 'card-body',
                                'enctype'=>'multipart/form-data'
                            ],
                        ]
                    ); ?>
                        <h6 class="mb-b fw-normal">1. اطلاعات شرکت</h6>
                        <div class="row">
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">نام شرکت حقوقی *</label>
                            <?= $form->field($model, 'company_info[company_title]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام شرکت را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">تاریخ تاسیس *</label>
                            <?= $form->field($model, 'company_info[establishment_date]')->textInput(
                                [
                                    'class' => 'form-control dob-picker form-control input',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ تاسیس شرکت را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">شماره ثبت *</label>
                            <?= $form->field($model, 'company_info[registration_number]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا شماره ثبت شرکت را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'id' => 'numeral-mask'
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">شناسه ملی *</label>
                            <?= $form->field($model, 'company_info[id]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا شناسه ملی شرکت را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">تاریخ شروع قرارداد *</label>
                            <?= $form->field($model, 'company_info[start_contract_date]')->textInput(
                                [
                                    'class' => 'form-control dob-picker form-control input text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع قرارداد را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">تاریخ اتمام قرارداد *</label>
                            <?= $form->field($model, 'company_info[end_contract_date]')->textInput(
                                [
                                    'class' => 'form-control dob-picker form-control input',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام قرارداد را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">آدرس شرکت *</label>
                            <?= $form->field($model, 'company_info[address]')->textarea(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا آدرس شرکت را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                        <hr class="my-4 mx-n4">
                        <h6 class="mb-3 fw-normal">2. اطلاعات رابط</h6>
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
                                <label for="nameWithTitle" class="form-label">شماره همراه (نام کاربری) *</label>
                                <?= $form->field($model, 'connector_info[mobile]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا شماره همراه رابط را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'id' => 'numeral-mask'
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">کد ملی (رمز عبور) *</label>
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
                        </div>
                        <hr class="my-4 mx-n4">
                        <h6 class="mb-3 fw-normal">3. اطلاعات مالی</h6>
                        <div class="row">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">شناسه کارگزار *</label>
                                <?= $form->field($model, 'financial_info[id]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا شناسه کارگزار را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'type' => 'number'
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
                                        'type' => 'number'
                                    ]
                                )->label(false); ?>
                            </div>
                        </div>
                        <hr class="my-4 mx-n4">
                        <h6 class="mb-3 fw-normal">4. قراردادها</h6>
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
                            <label for="nameWithTitle" class="form-label">درصد سهم کارگزار *</label>
                            <?= $form->field($model, 'contracts[share]')->textInput(
                                [
                                    'class' => 'form-control form-control input',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا درصد سهم کارگزار را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'type' => 'number'
                                ]
                            )->label(false); ?>
                        </div>
                        </div>
                         <hr class="my-4 mx-n4">
                        <h6 class="mb-3 fw-normal">5. فایل ها</h6>
                        <div class="row">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-1">
                                    <div class="dz-message needsclick">
                                        <?= $form->field($model, 'statute_file')->fileInput(
                                            [
                                                'class' => 'form-control text-start drop-file',
                                                'required' => true
                                            ]
                                        )->label(false); ?>
                                        <span class="drop-title"></span>
                                        <span class="note needsclick">تصویر اساسنامه شرکت *</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-2">
                                    <div class="dz-message needsclick">
                                        <?= $form->field($model, 'newspaper_file')->fileInput(
                                            [
                                                'class' => 'form-control text-start drop-file',
                                                'required' => true
                                            ]
                                        )->label(false); ?>
                                        <span class="drop-title"></span>
                                        <span class="note needsclick">تصویر روزنامه رسمی شرکت *</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-3">
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
