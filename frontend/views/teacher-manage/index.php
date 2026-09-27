<?php
$this->title = 'مدیریت اساتید';

use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Teachers;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Teachers();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("استاد مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.error("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.error("شماره همراه وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("استاد مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور استاد مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت استاد مرود نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.warning("استاد مورد نظر هم اکنون در لیست اساتید دانشکده شما می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.warning("مشخصات استاد مورد نظر يافت نشد لطفا از قسمت ثبت با مشخصات اقدام شود", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.warning("حجم تصویر انتخاب شده بیشتر از اندازه تعیین شده است", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}



?>

<style>
    .drop-file {
        position: absolute;
        background: red;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
    }

    div[data-key] {
        display: none;
    }

    .summary {
        display: none;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت اساتید</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">اساتید ثبت شده</h5>
            <?php
            if (Yii::$app->user->identity->role == 'user') {
            ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter" id="create-course">
                    <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت استاد جدید
                </button>
            <?php
            } else {
            ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        ثبت استاد جدید
                    </button>
                    <ul class="dropdown-menu" style="">
                        <li><a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCenter">ثبت با مشخصات</a></li>
                        <li><a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCenterId">ثبت با کد ملی</a></li>
                    </ul>
                </div>
            <?php
            }
            ?>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>تصویر استاد</th>
                        <th>نام</th>
                        <th>نام خانوادگی</th>
                        <th>شماره همراه</th>
                        <th>کد ملی</th>
                        <?php
                        if (Yii::$app->user->identity->role == 'user')
                            echo ' <th>دانشکده</th>';
                        ?>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php
                    foreach ($dataProvider->models as $teacher) {
                        $edit = 'edit' . rand();
                        $changeStatus = 'changeStatus' . rand();
                        $resetPassword = 'resetPassword' . rand();
                        $teacherGender = 'آقای';
                        $teacherStatus = 'فعال';
                        $statusIcon = 'avatar-online';
                        if ($teacher->gender == 1)
                            $teacherGender = 'خانم';
                        if ($teacher->status == 0) {
                            $teacherStatus = 'غیر فعال';
                            $statusIcon = 'avatar-busy';
                        }
                    ?>
                        <tr>
                            <th scope="row"><?= $dataProvider->pagination->page * 20 + $i++ ?></th>
                            <td>
                                <div class="avatar avatar-lg me-2 <?= $statusIcon ?>">
                                    <img src="<?= $front . '/teacher_profiles/' . $teacher->profile_image ?>" alt=" <?= $teacher->first_name . ' ' . $teacher->last_name ?>" class="rounded-circle">
                                </div>
                            </td>
                            <td><?= $teacher->first_name ?></td>
                            <td><?= $teacher->last_name ?></td>
                            <td><?= $teacher->mobile ?></td>
                            <?php
                            // امنیتی: کد ملی استاد در حال حاضر رمز عبور حساب او هم هست
                            // (TeacherManageController.php خط ۱۹۷: setPassword($model->id)).
                            // چون این ستون برای نقش broker هم قابل مشاهده بود، نمایش کامل آن
                            // یعنی در اختیار گذاشتن رمز عبور. فقط چهار رقم آخر نمایش داده می شود
                            // که برای تشخیص استاد کافی است ولی رمز را لو نمی دهد.
                            $nationalCode = $teacher->id === null ? '' : (string) $teacher->id;
                            $maskedCode = mb_strlen($nationalCode) > 4
                                ? str_repeat('•', mb_strlen($nationalCode) - 4) . mb_substr($nationalCode, -4)
                                : '-';
                            ?>
                            <td><?= \yii\helpers\Html::encode($maskedCode) ?></td>
                            <?php
                            if (Yii::$app->user->identity->role == 'user') {
                            ?>
                                <td class="demo-inline-spacing">
                                    <?php
                                    if ($teacher->colleges != null) {
                                        foreach ($teacher->colleges as $college) {
                                            $collegeDetail = $this->context->college_detail($college);
                                            echo '<span class="badge bg-label-primary">' . $collegeDetail->title . '</span>';
                                        }
                                    }
                                    ?>
                                </td>
                            <?php
                            }
                            ?>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش مشخصات</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $resetPassword ?>">بازنشانی رمز عبور</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">
                                        <?php
                                        if ($teacher->status == 1)
                                            echo 'غیر فعال کردن';
                                        else
                                            echo 'فعال کردن';
                                        ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش مشخصات <?= $teacher->first_name . ' ' . $teacher->last_name ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['edit'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($teacher, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام *</label>
                                                <?= $form->field($teacher, 'first_name')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نام استاد را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام خانوادگی *</label>
                                                <?= $form->field($teacher, 'last_name')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی استاد را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">شماره همراه (نام کاربری) *</label>
                                                <div class="form-control text-start"><?= $teacher->mobile ?></div>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">کد ملی *</label>
                                                <?= $form->field($teacher, 'id')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی استاد را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                                <label for="nameWithTitle" class="form-label">جنسیت *</label>
                                                <?php
                                                $gender = array(
                                                    '0' => 'مرد',
                                                    '1' => 'زن'
                                                )
                                                ?>
                                                <?= $form->field($teacher, 'gender')->dropDownList(
                                                    $gender,
                                                    [
                                                        'class' => 'form-select text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا جنسیت استاد را مشخص کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-9 col-md-9 col-sm-12 dol-lg-9 col-xl-9 mb-3">
                                                <label for="nameWithTitle" class="form-label">درباره استاد</label>
                                                <?= $form->field($teacher, 'comment')->textarea(
                                                    [
                                                        'class' => 'form-control text-start',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="select2Basic" class="form-label">دانشکده (ها) *</label>
                                                <?php
                                                echo $form->field($teacher, 'colleges')->dropDownList(
                                                    $colleges,
                                                    [
                                                        'prompt' => 'لطفا دانشکده را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'multiple' => true,
                                                        'id' => '',
                                                    ]
                                                )->label(false);
                                                ?>
                                            </div>
                                        </div>

                                        <div class="card mb-4 relative">
                                            <div class="card-body">
                                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                    <div class="dz-message needsclick">
                                                        <img src="<?= $front.'/teacher_profiles/'.$teacher->profile_image ?>" class="upload-preview" alt="upload-preview">
                                                        <?= $form->field($teacher, 'profile_image')->fileInput(
                                                            [
                                                                'class' => 'form-control text-start drop-file',
                                                            ]
                                                        )->label(false); ?>
                                                        <span class="drop-title"></span>
                                                        <span class="note needsclick">تصویر استاد</span>
                                                        <div class="alert alert-danger note needsclick" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت باشد</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">ویرایش استاد</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $resetPassword ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">بازنشانی رمز عبور <?= $teacher->first_name . ' ' . $teacher->last_name ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['reset_password'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($teacher, '_id')->hiddenInput()->label(false); ?>
                                        <?php
                                        // امنیتی: مقدار کد ملی از این متن حذف شد. این مودال برای هر استاد
                                        // در سورس صفحه رندر می شود، پس چاپ کد ملی اینجا همان رمز عبور را
                                        // در HTML صفحه لو می داد - حتی اگر مودال هیچ وقت باز نشود.
                                        ?>
                                        <p>آیا از بازنشانی رمز عبور <?= $teacherGender . ' ' . $teacher->first_name . ' ' . $teacher->last_name ?> به کد ملی وی اطمینان دارید؟</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $changeStatus ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= $teacherGender . ' ' . $teacher->first_name . ' ' . $teacher->last_name ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['change_status'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($teacher, '_id')->hiddenInput()->label(false); ?>
                                        <p>وضعیت <?= $teacherGender . ' ' . $teacher->first_name . ' ' . $teacher->last_name ?> در حال حاضر (<?= $teacherStatus ?>) می باشد</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">تغییر وضعیت</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
            <div class="demo-inline-spacing">
                <nav aria-label="Page navigation">
                    <?=
                    ListView::widget([
                        'dataProvider' => $dataProvider,
                        'emptyText' => '<div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">نتیجه ای یافت نشد</div>
                    </div>
                </div>',
                        'pager' => [
                            'prevPageLabel' => ' <i class="tf-icon bx bx-chevrons-left"></i>',
                            'nextPageLabel' => ' <i class="tf-icon bx bx-chevrons-right"></i>',
                            'maxButtonCount' => 10,

                            'options' => [
                                'tag' => 'ul',
                                'class' => 'pagination justify-content-center',
                                'id' => 'pager-container',
                            ],
                            'linkOptions' => ['class' => 'page-item page-link'],
                            'activePageCssClass' => 'page-item active',
                            'disabledPageCssClass' => 'disable',
                            'prevPageCssClass' => 'paginate_button page-item previous',
                            'nextPageCssClass' => 'paginate_button page-item next',
                        ],
                    ]);
                    ?>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت استاد جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">نام *</label>
                        <?= $form->field($model, 'first_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نام استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">نام خانوادگی *</label>
                        <?= $form->field($model, 'last_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">شماره همراه (نام کاربری) *</label>
                        <?= $form->field($model, 'mobile')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا شماره همراه استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">کد ملی (رمز عبور) *</label>
                        <?= $form->field($model, 'id')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">جنسیت *</label>
                        <?php
                        $gender = array(
                            '0' => 'مرد',
                            '1' => 'زن'
                        )
                        ?>
                        <?= $form->field($model, 'gender')->dropDownList(
                            $gender,
                            [
                                'class' => 'form-select text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا جنسیت استاد را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-9 col-md-9 col-sm-12 dol-lg-9 col-xl-9 mb-3">
                        <label for="nameWithTitle" class="form-label">درباره استاد</label>
                        <?= $form->field($model, 'comment')->textarea(
                            [
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده (ها) *</label>
                        <?php
                        if (Yii::$app->user->identity->role == 'user')
                            echo $form->field($model, 'colleges')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'multiple' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                        else
                            echo $form->field($model, 'colleges')->dropDownList(
                                $colleges,
                                [
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'multiple' => true,
                                    'data-allow-clear' => true,
                                    'options' =>
                                    [
                                        $model->colleges => ['selected' => true]
                                    ]
                                ]
                            )->label(false);
                        ?>
                    </div>
                </div>

                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'profile_image')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر استاد</span><br>
                                <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت باشد</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت استاد</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenterId" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت استاد با شماره همراه و کد ملی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new_with_id'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">شماره همراه *</label>
                        <?= $form->field($model, 'mobile')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا شماره همراه استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">کد ملی *</label>
                        <?= $form->field($model, 'id')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی استاد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت استاد</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>