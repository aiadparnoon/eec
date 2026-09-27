<?php
$this->title = 'مدیریت دوره های تک درس';

use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use frontend\controllers;
use yii\widgets\ListView;

SingleAsset::register($this);
Select2Asset::register($this);
$model = new Courses();
$front = Yii::getAlias('@front');
$courseDetailType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'حضوری',
);
$capacityType = array(
    '1' => 'نامحدود',
    '2' => 'محدود',
    '3' => 'سازمانی'
);
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("دوره مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.danger("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.danger("شماره همراه وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("دوره مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور دوره مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت دوره مرود نظر تغییر یافت", {
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
                    <a href="javascript:void(0);">مدیریت دوره</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">دوره های تک درس</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">ویرایش دوره</a>
                </li>
            </ol>
        </nav>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">ویرایش دوره <?= $courseDetail->title['main_fa'] ?></h5>
                <?php
                if(Yii::$app->user->identity->role == 'user')
                {
                    ?>
                    <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                        <?php
                        if($courseDetail->status != '1')
                        {
                            $form = ActiveForm::begin(
                                [
                                    'action' => ['dashboard/confirm_package'],
                                    "method" => "post",
                                ]
                            );
                            ?>
                            <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                            <input type="hidden" name="page" value="courses">
                            <button type="submit" class="btn btn-success">تائید دوره</button>
                            <?php
                            ActiveForm::end();
                        }
                        ?>
                        <?php
                        if($model->status != '4')
                            echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                        if($model->status != '5')
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
                        'action' => ['edit'],
                        "method" => "post",
                        'options' => [
                            // 'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                <div class="row">
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان اصلی فارسی *</label>
                        <?= $form->field($courseDetail, 'title[main_fa]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی فارسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان اصلی انگلیسی *</label>
                        <?= $form->field($courseDetail, 'title[main_en]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی انگلیسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان فارسی (داخل مدرک) *</label>
                        <?= $form->field($courseDetail, 'title[degree_fa]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فارسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان انگلیسی (داخل مدرک) *</label>
                        <?= $form->field($courseDetail, 'title[degree_en]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان انگلیسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">قیمت اصلی (تومان) *</label>
                        <?= $form->field($courseDetail, 'price')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'type' => 'number'
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">قیمت با تخفیف (تومان) *</label>
                        <?= $form->field($courseDetail, 'discount_price')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'type' => 'number'
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">مدت زمان دوره (دقیقه) *</label>
                        <?= $form->field($courseDetail, 'duration')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'type' => 'number',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع ظرفیت *</label>
                        <?= $form->field($courseDetail, 'student_capacity[type]')->dropDownList(
                            $capacityType,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نوع ظرفیت دوره را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/capacity') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#capacity\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                            ]
                        )->label(false); ?>
                    </div>
                    <?php
                    if ($courseDetail->student_capacity['type'] == 2) {
                        ?>
                        <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity">
                            <label for="nameWithTitle" class="form-label">ظرفیت *</label>
                            <?= $form->field($courseDetail, 'student_capacity[number]')->textInput(
                                [

                                    'class' => 'form-control numeral-mask text-start',
                                    'required' => true,
                                    'type' => 'number'
                                ]
                            )->label(false) ?>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity"></div>
                        <?php
                    }
                    ?>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع دوره *</label>
                        <?= $form->field($courseDetail, 'content_type')->dropDownList(
                            $courseDetailType,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نوع دوره را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'onchange' => '
                                            $.get( "' . Url::toRoute('/packages/course_date') . '", { id: $(this).val() } )
                                            .done(function( data ) {
                                            var main_data=JSON.parse(data);
                                                $(\'#from\').html(main_data.from);
                                                $(\'#to\').html(main_data.to);
                                                $(\'#time\').html(main_data.time);
                                            }
                                        );'
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from">
                        <?php
                        if ($courseDetail->lessons != null) {
                            echo '<label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>';
                            echo  $form->field($courseDetail, 'lessons[0][date][from]')->textInput(
                                [
                                    'class' => 'form-control dob-picker text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false);
                        }
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to">
                        <?php
                        if ($courseDetail->lessons != null) {
                            echo '<label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>';
                            echo  $form->field($courseDetail, 'lessons[0][date][to]')->textInput(
                                [
                                    'class' => 'form-control dob-picker text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false);
                        }
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="time">
                        <?php
                        if ($courseDetail->lessons != null) {
                            echo '<label for="nameWithTitle" class="form-label">ساعت شروع دوره *</label>';
                            echo  $form->field($courseDetail, 'lessons[0][date][time]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع دوره را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false);
                        }
                        ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">توضیحات دوره</label>
                        <?= $form->field($courseDetail, 'description')->textarea(
                            [
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                    </div>
                    <hr class="mt-2">
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده *</label>
                        <?php
                        echo $form->field($courseDetail, 'college')->dropDownList(
                            $colleges,
                            [
                                'prompt' => 'لطفا دانشکده را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'data-allow-clear' => true,
                                "data" => "colleges",
                                'onchange' => '
                                                            $.get( "' . Url::toRoute('/courses/brokers') . '", { id: $(this).val() } )
                                                            .done(function( data ) {
                                                            var main_data=JSON.parse(data);
                                                                $(\'#broker\').html(main_data.brokers);
                                                                $(\'#teachers\').html(main_data.teachers);
                                                                $(\'#lessons\').html(main_data.lessons);
                                                            }
                                                        );'
                            ]
                        )->label(false);
                        ?>
                    </div>

                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">کارگزار</label>
                        <div id="broker">
                            <?php
                            if ($courseDetail->broker != null) {
                                $myBrokers = $this->context->my_brokers($courseDetail->college);
                                echo $form->field($courseDetail, 'broker[_id]')->dropDownList(
                                    ArrayHelper::map($myBrokers, function ($model) {
                                        return (string) $model->_id;
                                    }, function ($model) {
                                        return $model->company_info['company_title'];
                                    }),
                                    [
                                        'prompt' => 'لطفا کارگزار را انتخاب کنید',
                                        'class' => 'select2s form-select',
                                        'id' => '',
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false);
                                ?>
                                <?php
                            } else {
                                ?>
                                <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع قرارداد کارگزار</label>
                        <div id="broker_contracts">
                            <?php
                            if ($courseDetail->broker != null) {
                                $myBrokerContracts = $this->context->my_broker_contract($courseDetail->broker['_id']);
                                echo $form->field($courseDetail, 'broker[contract]')->dropDownList(
                                    ArrayHelper::map($myBrokerContracts, 'id', function ($model) {
                                        return $model['title'] . ' (' . $model['share'] . ' درصد)';
                                    }),
                                    [
                                        'prompt' => 'لطفا قرارداد کارگزار را انتخاب کنید',
                                        'class' => 'select2s form-select',
                                        'id' => '',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                                <?php
                            } else {
                                ?>
                                <span class="badge bg-label-warning">در انتظار انتخاب کارگزار</span>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <hr class="mb-2">
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">درس دوره *</label>
                        <div id="lessons">
                            <?php
                            if ($courseDetail->lessons != null)
                            {
                                $myCourses = $this->context->my_courses($courseDetail->college);
                                echo $form->field($courseDetail, 'lessons[0][_id]')->dropDownList(
                                    ArrayHelper::map($myCourses, function ($model) {
                                        return (string) $model->_id;
                                    }, function ($model) {
                                        return $model->title;
                                    }),
                                    [
                                        'prompt' => 'لطفا درس را انتخاب کنید',
                                        'class' => 'select2 form-select',
                                        'id' => '',
                                        'required' => true
                                    ]
                                )->label(false);
                                ?>
                                <?php
                            } else {
                                ?>
                                <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">مدرس دوره *</label>
                        <div id="teachers">
                            <?php
                            if ($courseDetail->lessons != null) {
                                $myTeachers = $this->context->my_teachers($courseDetail->college);
                                echo $form->field($courseDetail, 'lessons[0][teachers]')->dropDownList(
                                    ArrayHelper::map($myTeachers, function ($model) {
                                        return (string) $model->_id;
                                    }, function ($model) {
                                        return $model->first_name . ' ' . $model->last_name;
                                    }),
                                    [
                                        'prompt' => 'لطفا مدرس را انتخاب کنید',
                                        'class' => 'select2 form-select',
                                        'id' => '',
                                        'required' => true,
                                    ]
                                )->label(false);
                            } else {
                                ?>
                                <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                                <?php
                            }
                            ?>
                        </div>
                    </div>

                </div>
                <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>



<?php
if(Yii::$app->user->identity->role == 'user')
{
    ?>
    <div class="modal fade" id="back" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">نیاز به اصلاح دوره</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['dashboard/back_package'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="courses">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($courseDetail, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل رد دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت اصلاح دوره</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reject" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">رد کردن دوره</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['dashboard/reject_package'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="courses">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($model, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل رد دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت رد دوره</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>