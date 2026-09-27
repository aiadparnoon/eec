<?php
$this->title = 'مدیریت دوره های جامع و یکساله';
use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use app\models\Discounts;
use frontend\controllers;
use yii\widgets\ListView;

$newLesson = new Courses();
SingleAsset::register($this);
Select2Asset::register($this);
$front = Yii::getAlias('@front');
$courseType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'نیمه حضوری',
    '4' => ' حضوری',
);
$capacityType = array(
    '1' => 'نامحدود',
    '2' => 'محدود',
    '3' => 'سازمانی'
);
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("دوره مورد نظر ویرایش گردید", {
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
    toastr.success("درس مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("درس مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("درس مورد نظر اضافه گردید", {
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
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.success("عضو مورد نظر به دوره اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.warning("عضو مورد نظر هم اکنون در لیست این دوره موجود می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.success("اعضای مورد نظر به دوره اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("وضعیت دانشپذیر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.success("دانشپذیر مورد نظر از دوره حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '12')
        $script = <<< JS
    toastr.success("مبلغ پیش پرداخت ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '13')
        $script = <<< JS
    toastr.success("قسط مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '14')
        $script = <<< JS
    toastr.success("قسط مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '15')
        $script = <<< JS
    toastr.warning("کاربر مورد نظر در سیستم ادوبی ثبت نشده است، لطفا ابتدا کاربر را در ادوبی ثبت نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '16')
        $script = <<< JS
    toastr.warning("کاربر مورد نظر دارای نقش دیگری در سامانه است و نمی تواند به عنوان دستیار استاد انتخاب شود", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '17')
        $script = <<< JS
    toastr.success("نقش کاربر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '18')
        $script = <<< JS
    toastr.warning("حجم عکس انتخاب شده بیشتر اندازه تعیین شده است", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '19')
        $script = <<< JS
    toastr.success("شرایط اقساط مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '20')
        $script = <<< JS
    toastr.success("قسط مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '21')
        $script = <<< JS
    toastr.error("خطایی در ارتباط با سیستم ادوبی رخ داده است، لطفا مجددا تلاش نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '22')
        $script = <<< JS
    toastr.error("تاریخ شروع باید کمتر یا برابر تاریخ اتمام کلاس باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '23')
        $script = <<< JS
    toastr.success("ثبت در ادوبی انجام گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '24')
        $script = <<< JS
    toastr.success("نمرات مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '25')
        $script = <<< JS
    toastr.success("لیست اساتید دوره بروز گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '26')
        $script = <<< JS
    toastr.success("کد تخفیف مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '27')
        $script = <<< JS
    toastr.success("کد تخفیف مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
$tab = <<< JS
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get('tab');

    if (tabValue) {
        $("#" + tabValue + " > button").click();
    } else {
        const isSearch = window.location.href?.includes('status') || window.location.href?.includes('page');
        $(isSearch ? ".course-tab:nth-child(4) > button" : ".course-tab:first > button").click();
    }

    function updateQueryStringParameter(uri, key, value) {
            let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            let separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            }
            return uri + separator + key + "=" + value;   
    }

    $(".course-tab").click(function() {
        const currentUrl = window.location.href;
        const newUrl = updateQueryStringParameter(currentUrl, 'tab', $(this).attr("id"));
        history.pushState(null, '', newUrl);
    });
JS;
$this->registerJs($tab);
$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_user_detail', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$list = <<<JS
$(document).on('click','#add_from-list',function(e) {
    e.preventDefault();
    var username = $('#username').val();
    var id = $('#packageId').val();
    $.ajax({
        url:'$url',
        type:'POST',
        data:{username:username,id:id, _csrf: yii.getCsrfToken()},
        success:function(data) {
            var main_data = JSON.parse(data);
            $('#selected-user').html(main_data.userDetail);  
            $('#final-ok').html(main_data.footer);  
        }
    });
});
JS;
$this->registerJs($list);

$excelUrl = Yii::$app->urlManager->createAbsoluteUrl('packages/check_excel_file', 'https');
$excel = <<<JS
$(document).on('click','#excel',function(e) {
    e.preventDefault();
    var id = $('#packageId').val();
    var fd = new FormData();
    fd.append('file',$('#excel-file')[0].files[0]);
    fd.append('packageId ',$('#packageId').val());
    fd.append('_csrf', yii.getCsrfToken());

    console.log(fd)
    $.ajax({
        url:'$excelUrl',
        type:'POST',
        processData: false,  // Don't process the data
        contentType: false,  // Don't set content type
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-CSRF-Token', yii.getCsrfToken());
            $('#ajax-loader').css("visibility", "visible");
        },
        data:fd,
        success:function(data) {
            var main_data = JSON.parse(data);
            $('#message').html(main_data.message);  
            $('#final-ok').html(main_data.footer);  
        },
        complete: function(){
    $('#ajax-loader').css("visibility", "hidden");
  }
    });
});
JS;
$this->registerJs($excel);
$type = array(
    '2' => 'دوره جامع (بین ۲۰ تا ۲۵۰ ساعت)',
    '3' => 'دوره یکساله (بین ۲۵۰ تا ۳۵۰ ساعت)',
);

$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_course_lessons', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-course-scores',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("در حال دریافت...");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('.title').html(main_data.title);
            $('#body').html(main_data.body);
            $('#submit').html(main_data.submit);
        }
        })
}
)
JS;
$this->registerJs($show_off);


$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_finance_info', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_finance = <<<JS
$(document).on('click','.show-finance-info',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("در حال دریافت...");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('.finance_title').html(main_data.title);
            $('#finance_body').html(main_data.body);
        }
        })
}
)
JS;
$this->registerJs($show_finance);
$hiddenArchive = array(
    false => 'خیر' ,
    true => 'بله' ,
);
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <ol class="lh-1-85 breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">مدیریت دوره</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">دوره های جامع و یکساله</a>
                </li>
                <li class="breadcrumb-item active">ویرایش دوره (<?= $model->title['main_fa'] ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2">
                <?php
                if(Yii::$app->user->identity->role == 'user')
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl(['packages','CoursesSearch[college]' => $model->college ,'CoursesSearch[title][main_fa]' => '','CoursesSearch[license_code]' => '','CoursesSearch[status]' => '']).'">بازگشت</a>';
                else
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl('packages').'">بازگشت</a>';
                ?>
            </span>
        </div>
    </nav>
    <div class="card text-center mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item course-tab" role="presentation" id="tab-id0">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id0" aria-controls="id0" aria-selected="false" tabindex="-1">
                        مشخصات دوره
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id3">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id3" aria-controls="id3" aria-selected="false" tabindex="-1">
                        اقساط
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id1">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id1" aria-controls="id1" aria-selected="true">
                        دروس دوره
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id2">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id2" aria-controls="id2" aria-selected="true">
                        اعضا
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id4">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id4" aria-controls="id4" aria-selected="true">
                        اساتید
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id5">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id5" aria-controls="id5" aria-selected="true">
                        کدهای تخفیف
                    </button>
                </li>
            </ul>
            <?php
            if (Yii::$app->user->identity->role == 'user' && $model->status != '7') {
                ?>
                <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                    <?php
                    if ($model->status != '1') {
                        $form = ActiveForm::begin(
                            [
                                'action' => ['dashboard/confirm_package'],
                                "method" => "post",
                            ]
                        );
                        ?>
                        <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                        <input type="hidden" name="page" value="packages">
                        <button type="submit" class="btn btn-success">تائید دوره</button>
                        <?php
                        ActiveForm::end();
                    } else
                        echo '<button type="button" disabled class="btn btn-label-dark">تائید دوره</button>';
                    ?>
                    <?php
                    if ($model->status != '4')
                        echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">نیاز به اصلاح</button>';
                    if ($model->status != '5')
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">رد کرد</button>';
                    ?>
                </div>
                <?php
            } else if ($model->status == '3' || $model->status == '4') {
                $form = ActiveForm::begin(
                    [
                        'action' => ['dashboard/send_course_to_admin'],
                        "method" => "post",
                    ]
                );
                ?>
                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                <input type="hidden" name="page" value="packages">
                <button type="submit" class="btn btn-success">ارسال برای مجوز</button>
                <?php
                ActiveForm::end();
            }
            ?>
        </div>

        <div class="tab-content shadow-none">
            <div class="tab-pane fade" id="id0" role="tabpanel">
                <div class="card-body">
                    <div class="modal-body">
                        <?php $form = ActiveForm::begin(
                            [
                                'action' => ['edit'],
                                "method" => "post",
                            ]
                        ); ?>
                        <div class="row">
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان اصلی فارسی *</label>
                                <?= $form->field($model, 'title[main_fa]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی فارسی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false); ?>
                                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان اصلی انگلیسی *</label>
                                <?= $form->field($model, 'title[main_en]')->textInput(
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
                                <?= $form->field($model, 'title[degree_fa]')->textInput(
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
                                <?= $form->field($model, 'title[degree_en]')->textInput(
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
                                <?= $form->field($model, 'price')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">قیمت با تخفیف (تومان) *</label>
                                <?= $form->field($model, 'discount_price')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت با تخفیف دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">نوع دوره *</label>
                                <?= $form->field($model, 'content_type')->dropDownList(
                                    $courseType,
                                    [
                                        'class' => 'form-select',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onchange' => '
                                            $.get( "' . Url::toRoute('/packages/course_date') . '", { id: $(this).val() } )
                                            .done(function( data ) {
                                            var main_data=JSON.parse(data);
                                                $(\'#from1\').html(main_data.from);
                                                $(\'#to1\').html(main_data.to);
                                                $(".dob-picker").each(function () {
                                                    $(this).flatpickr({
                                                        monthSelectorType: "static",
                                                        locale: "fa",
                                                        altInput: true,
                                                        altFormat: "Y/m/d",
                                                        disableMobile: true,
                                                        allowInput:true,
                                                    });
                                                });
                                            }
                                        );'
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                                <label for="nameWithTitle" class="form-label">نوع ظرفیت *</label>
                                <?= $form->field($model, 'student_capacity[type]')->dropDownList(
                                    $capacityType,
                                    [
                                        'class' => 'form-select',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع ظرفیت دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/packages/capacity') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#capacity1\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity1">
                                <?php
                                if ($model->student_capacity != null)
                                {
                                    if($model->student_capacity['type'] == '2')
                                    {
                                        echo '<label for="nameWithTitle" class="form-label">ظرفیت *</label>';
                                        echo $form->field($model, 'student_capacity[number]')->textInput(
                                            [

                                                'class' => 'form-control numeral-mask text-start',
                                                'required' => true,
                                                'type' => 'number'
                                            ]
                                        )->label(false);
                                    }
                                }
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">دسته بندی دوره *</label>
                                <?= $form->field($model, 'type')->dropDownList(
                                    $type,
                                    [
                                        'class' => 'form-select',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا دسته بندی دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false); ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">مدت زمان دوره (ساعت) *</label>
                                <?= $form->field($model, 'duration')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'type' => 'number'
                                    ]
                                )->label(false); ?>
                            </div>
                            <?php
                            if ($model->content_type != '3') {
                                ?>
                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from1">
                                    <?php
                                    echo '<label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>';
                                    echo  $form->field($model, 'date[from]')->textInput(
                                        [
                                            'class' => 'form-control dob-picker text-start',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                    ?>
                                </div>
                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to1">
                                    <?php
                                    echo '<label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>';
                                    echo  $form->field($model, 'date[to]')->textInput(
                                        [
                                            'class' => 'form-control dob-picker text-start',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                            <hr class="mt-2">
                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                <label for="nameWithTitle" class="form-label">توضیحات دوره</label>
                                <?= $form->field($model, 'description')->textarea(
                                    [
                                        'class' => 'form-control text-start',
                                    ]
                                )->label(false); ?>
                            </div>
                            <hr class="mt-2">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="select2Basic" class="form-label">دانشکده *</label>
                                <?php
                                echo $form->field($model, 'college')->dropDownList(
                                    $colleges,
                                    [
                                        'prompt' => 'لطفا دانشکده را مشخص کنید',
                                        'class' => 'select2 form-select form-select-lg',
                                        'required' => true,
                                        'id' => '',
                                        'data-allow-clear' => true,
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/packages/brokers1') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                           $(\'#broker1\').html(main_data.brokers);
                                                                                           $(\'#teachers1\').html(main_data.teachers);
                                                                                           $(\'#lessons1\').html(main_data.lessons);
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">کارگزار</label>
                                <div id="broker1">
                                    <?php
                                    $myBrokers = $this->context->my_brokers($model->college);
                                    if ($myBrokers != null) {
                                        echo $form->field($model, 'broker[_id]')->dropDownList(
                                            ArrayHelper::map($myBrokers, function ($model) {
                                                return (string) $model->_id;
                                            }, function ($model) {
                                                $type = 'حقیقی';
                                                if ($model->type == '1')
                                                    $type = 'حقوقی - شرکت ' . $model->company_info['company_title'];
                                                return $model->connector_info['first_name'] . ' ' . $model->connector_info['last_name'] . '(' . $type . ')';
                                            }),
                                            [
                                                'prompt' => 'لطفا کارگزار را انتخاب کنید',
                                                'class' => 'select2s form-select',
                                                'id' => '',
                                                'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts1\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                            ]
                                        )->label(false);
                                    }

                                    ?>
                                </div>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نوع قرارداد کارگزار</label>
                                <div id="broker_contracts1">
                                    <?php
                                    if ($model->broker != null) {
                                        $myBrokerContracts = $this->context->my_broker_contract($model->broker['_id']);
                                        if ($myBrokerContracts != null) {
                                            echo $form->field($model, 'broker[contract]')->dropDownList(
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
                                        }
                                    } else {
                                        ?>
                                        <span class="badge bg-label-warning">در انتظار انتخاب کارگزار</span>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <hr class="mb-2">
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from">

                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to">

                            </div>

                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-<?= rand() ?>">
                                        <img src="<?= $front . '/package_images/' . $model->preview_image ?>" class="upload-preview img-fluid">
                                        <div class="dz-message needsclick">
                                            <?= $form->field($model, 'preview_image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر پیش نمایش دوره</span>
                                            <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize') / 1000 ?> کیلوبایت باشد</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">ویرایش مشخصات دوره</button>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="id3" role="tabpanel">
                <?php
                if ($model->installments != null && $model->prepayment_installments != null) {
                    ?>
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['edit_prepayment_installments'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 row">
                                <label for="html5-text-input" class="col-md-4 col-form-label">مبلغ پیش پرداخت (تومان) *</label>
                                <div class="col-md-8">
                                    <?= $form->field($model, 'prepayment_installments')->textInput(
                                        [
                                            'class' => 'form-control message-input me-3',
                                            'type' => 'number',
                                            'placeholder' => 'مبلغ پیش پرداخت'
                                        ]
                                    )->label(false); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-primary btn-block">ویرایش پیش پرداخت</button>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">اقساط ثبت شده</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_single_installment">
                            افزودن قسط
                        </button>
                        <div class="modal fade" id="add_single_installment" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن قسط</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['add_single_installment'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">تاریخ پرداخت: +</label>
                                                <input type="text" name="deadline" class="form-control text-start dob-picker" required>
                                            </div>
                                            <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                                <label for="emailWithTitle" class="form-label">مبلغ (تومان): *</label>
                                                <input type="number" name="amount" class="form-control text-start" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">افزودن</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $installmentRow = 0;
                    foreach ($model->installments as $installment) {
                        $editInstallment = 'editInstallment' . rand();
                        $deleteInstallment = 'deleteInstallment' . rand();
                        $installmentDeadline = explode('-', $installment['deadline']);
                        ?>
                        <nav class="navbar navbar-expand-lg bg-label-secondary mb-2">
                            <div class="container-fluid">
                                <div class="collapse navbar-collapse" id="navbar-ex-8">
                                    <div class="navbar-nav me-auto">
                                        <a class="nav-item nav-link active" href="javascript:void(0)">مبلغ قسط: <?= number_format($installment['amount']) . ' تومان' ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)"><?= '  تاریخ پرداخت: ' . $installmentDeadline[0] . '/' . $installmentDeadline[1] . '/' . $installmentDeadline[2] ?></a>
                                    </div>
                                    <ul class="navbar-nav ms-lg-auto">
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="modal" data-bs-target="#<?= $editInstallment ?>" href="javascript:void(0);">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M3.25 22C3.25 21.5858 3.58579 21.25 4 21.25H20C20.4142 21.25 20.75 21.5858 20.75 22C20.75 22.4142 20.4142 22.75 20 22.75H4C3.58579 22.75 3.25 22.4142 3.25 22Z" fill="#1C274C" />
                                                    <path opacity="0.5" d="M19.0807 7.37162C20.3095 6.14279 20.3095 4.15046 19.0807 2.92162C17.8519 1.69279 15.8595 1.69279 14.6307 2.92162L13.9209 3.63141C13.9306 3.66076 13.9407 3.69052 13.9512 3.72066C14.2113 4.47054 14.7022 5.45356 15.6256 6.37698C16.549 7.30039 17.532 7.79126 18.2819 8.05142C18.3119 8.06183 18.3415 8.07187 18.3708 8.08155L19.0807 7.37162Z" fill="#1C274C" />
                                                    <path d="M13.9511 3.59961L13.9205 3.63017C13.9303 3.65952 13.9403 3.68928 13.9508 3.71942C14.211 4.4693 14.7018 5.45232 15.6252 6.37574C16.5487 7.29915 17.5317 7.79002 18.2816 8.05018C18.3113 8.0605 18.3407 8.07046 18.3696 8.08005L11.5198 14.9299C11.058 15.3917 10.827 15.6227 10.5724 15.8213C10.2721 16.0555 9.94711 16.2564 9.60326 16.4202C9.31177 16.5591 9.00196 16.6624 8.38235 16.869L5.11497 17.9581C4.81005 18.0597 4.47388 17.9804 4.24661 17.7531C4.01934 17.5258 3.93998 17.1897 4.04162 16.8847L5.13074 13.6173C5.33728 12.9977 5.44055 12.6879 5.57947 12.3964C5.74334 12.0526 5.94418 11.7276 6.17844 11.4273C6.37702 11.1727 6.60794 10.9418 7.06971 10.48L13.9511 3.59961Z" fill="#1C274C" />
                                                </svg>

                                                ویرایش</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="modal" data-bs-target="#<?= $deleteInstallment ?>" href="javascript:void(0);">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C" />
                                                    <path opacity="0.5" d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12405C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001Z" fill="#1C274C" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.42543 11.4815C9.83759 11.4381 10.2051 11.7547 10.2463 12.1885L10.7463 17.4517C10.7875 17.8855 10.4868 18.2724 10.0747 18.3158C9.66253 18.3592 9.29499 18.0426 9.25378 17.6088L8.75378 12.3456C8.71256 11.9118 9.01327 11.5249 9.42543 11.4815Z" fill="#1C274C" />
                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5747 11.4815C14.9868 11.5249 15.2875 11.9118 15.2463 12.3456L14.7463 17.6088C14.7051 18.0426 14.3376 18.3592 13.9254 18.3158C13.5133 18.2724 13.2126 17.8855 13.2538 17.4517L13.7538 12.1885C13.795 11.7547 14.1625 11.4381 14.5747 11.4815Z" fill="#1C274C" />
                                                </svg>
                                                حذف</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </nav>
                        <div class="modal fade" id="<?= $editInstallment ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش قسط</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['edit_installments'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <?php echo $form->field($model, '_id')->hiddenInput()->label(false);
                                        ?>
                                        <input type="hidden" name="row" value="<?= $installmentRow ?>">
                                        <div class="row">
                                            <div class="col col-md-6 col-lg-6 col-sm-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">تاریخ پرداخت *</label>
                                                <?php echo $form->field($model, 'installments[' . $installmentRow . '][deadline]')->textInput(
                                                    [
                                                        'class' => 'form-control dob-picker text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ پرداخت قسط را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                        'id' => '',
                                                    ]
                                                )->label(false);
                                                ?>
                                            </div>
                                            <div class="col col-md-6 col-lg-6 col-sm-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">مبلغ قسط (تومان) *</label>
                                                <?php echo $form->field($model, 'installments[' . $installmentRow . '][amount]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا مبلغ قسط را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                        'id' => '',
                                                        'type' => 'number',
                                                    ]
                                                )->label(false);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">ویرایش قسط</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $deleteInstallment ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش قسط</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['delete_installments'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <?php echo $form->field($model, '_id')->hiddenInput()->label(false);
                                        ?>
                                        <input type="hidden" name="row" value="<?= $installmentRow ?>">
                                        <div class="row">
                                            آیا از حذف قسط با مبلغ <?= number_format($installment['amount']) ?> تومان و تاریخ بازپرداخت <?= $installmentDeadline[0] . '/' . $installmentDeadline[1] . '/' . $installmentDeadline[2] ?> مطمئن هستید؟
                                        </div>
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
                        <?php
                        $installmentRow++;
                    }
                } else {
                    ?>
                    <div class="alert alert-warning text-dark" role="alert">برای این دوره شرایط اقساطی ثبت نشده است</div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_installment">
                        افزودن شرایط اقساطی
                    </button>
                    <div class="modal fade" id="add_installment" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن شرایط قسط</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['add_installment'],
                                            "method" => "post",
                                        ]
                                    ); ?>
                                    <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                                    <div class="row">
                                        <div class="col-12 col-md-12 col-lg-12 col-sm-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">مبلغ پیش پرداخت (تومان): +</label>
                                            <input type="number" name="prepayment_installments" class="form-control text-start" required>
                                        </div>
                                        <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">تاریخ پرداخت اولین قسط: +</label>
                                            <input type="text" name="deadline" class="form-control text-start dob-picker" required>
                                        </div>
                                        <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                            <label for="emailWithTitle" class="form-label">مبلغ اولین قسط (تومان): *</label>
                                            <input type="number" name="amount" class="form-control text-start" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">افزودن</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="tab-pane fade" id="id1" role="tabpanel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"></h5>
                    <?php
                    if ($model->status != '1' || $model->status == '3' || $model->status == '4' || Yii::$app->user->identity->role == 'user') {
                        ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCenter">
                            <span class="tf-icons fa-solid fa-plus fa-fw me-1"></span>افزودن درس جدید
                        </button>
                        <?php
                    }
                    ?>
                </div>
                <div class="row">
                    <?php
                    if ($model->lessons != null) {
                        $counter = 0;
                        foreach ($model->lessons as $lesson) {
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                            ?>
                            <div class="col-4 col-md-4 col-sm-4 dol-lg-4 col-xl-4 mb-3">
                                <?php
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['edit_course_in_package'],
                                        "method" => "post",
                                    ]
                                );
                                echo $form->field($model, '_id')->hiddenInput()->label(false)
                                ?>
                                <input type="hidden" name="row" value="<?= $counter ?>">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <?= $lessonDetail->title ?>
                                        <div class="avatar avatar-md me-2">
                                            <img src="<?= $front . '/lesson_images/' . $lessonDetail->imagePreview ?>" alt="آواتار" class="rounded-circle">
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-fullname">استاد درس *</label>
                                            <?= $form->field($model, 'lessons[' . $counter . '][teachers]')->dropDownList(
                                                ArrayHelper::map($teachers, function ($model) {
                                                    return (string) $model->_id;
                                                }, function ($model) {
                                                    return $model->first_name . ' ' . $model->last_name;
                                                }),
                                                [
                                                    'prompt' => 'لطفا استاد درس را انتخاب کنید',
                                                    'class' => 'select2 form-select',
                                                    'id' => ''
                                                    //                            'multiple' => true
                                                ]
                                            )->label(false) ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">تاریخ شروع *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][from]')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][_id]')->hiddenInput(
                                                [
                                                    'value' => (string) $lessonDetail->_id
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">تاریخ اتمام *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][to]')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">ساعت شروع *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][time]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">مدت زمان (ساعت) *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][duration]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">مخفی کردن آرشیو *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][hide_archive]')->dropDownList(
                                                $hiddenArchive,
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="pt-4">
                                            <button name="edit" type="submit" class="btn btn-warning me-sm-3 me-1">ویرایش درس</button>
                                            <?php
                                            if ($model->status != '1' || $model->status == '3' || $model->status == '4' || Yii::$app->user->identity->role == 'user')
                                                echo '<button name="delete" type="submit" class="btn btn-danger">حذف درس</button>';
                                            ?>
                                            <?php ActiveForm::end(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $counter++;
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="tab-pane fade" id="id2" role="tabpanel">
                <?php echo $this->render('_member_search', [
                    'model' => $searchModel,
                    'packageDetail' => $model,
                ]); ?>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th>نام</th>
                            <th>نام خانوادگی</th>
                            <th>نام کاربری</th>
                            <th>ثبت کننده</th>
                            <th>وضعیت</th>
                            <th>نقش</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        <?php
                        $i = 1;
                        $ii = 1;
                        foreach ($dataProvider->models as $member) {
                            $edit = 'edit' . rand();
                            $changeStatus = 'changeStatus' . rand();
                            $addToClass = 'addToClass' . rand();
                            $deleteFromClass = 'deleteFromClass' . rand();
                            $changeRole = 'changeRole' . rand();
                            $scoreRegister = 'scoreRegister' . rand();
                            $financeInfo = 'financeInfo' . rand();
                            $userRole = 'دانشپذیر';
                            //                                if ($member->role == 'mentor')
                            //                                    $userRole = 'دستیار استاد';
                            $memberStatus = 'خطا در ادوبی';
                            $statusClass = 'bg-label-danger';
                            $memberCourse = null;
                            if ($member->courses != null)
                                foreach ($member->courses as $course)
                                    if (is_array($course))
                                        if (array_key_exists('_id', $course))
                                            if ($course['_id'] == (string) $model->_id)
                                                $memberCourse = $course;
                            $registrant = 'کاربر';
                            if ($memberCourse != null) {
                                if ($memberCourse['status'] == '1') {
                                    $memberStatus = 'فعال';
                                    $statusClass = 'bg-label-success';
                                } else if ($memberCourse['status'] == '2') {
                                    $memberStatus = 'غیرفعال';
                                    $statusClass = 'bg-label-warning';
                                }
                                if (array_key_exists('role', $memberCourse))
                                    if ($memberCourse['role'] == 'mentor')
                                        $userRole = 'دستیار استاد';
                                $role = '';
                                $registrant = 'نامشخص';
                                if (array_key_exists('registrant', $memberCourse)) {
                                    if ($memberCourse['registrant'] == $member->username)
                                        $registrant = 'دانشپذیر';
                                    else {
                                        $registrantDetail = DashboardController::registrant_detail($memberCourse['registrant']);
                                        if ($registrantDetail != null) {
                                            if ($registrantDetail->role == 'user')
                                                $role = 'ادمین';
                                            else if ($registrantDetail->role == 'emp')
                                                $role = 'کارشناس دانشکده';
                                            else if ($registrantDetail->role == 'broker')
                                                $role = 'کارگزار';
                                            $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name;
                                        }
                                    }
                                }
                            }
                            ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 50 + $ii++ ?></th>
                                <td><?= $member->first_name ?></td>
                                <td><?= $member->last_name ?></td>
                                <td><?= $member->username ?></td>
                                <td>
                                    <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $role ?>">
                                        <?= $registrant ?>
                                    </button>
                                </td>
                                <td><span class="badge <?= $statusClass ?>"><?= $memberStatus ?></span></td>
                                <td><?= $userRole ?></td>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions">
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $deleteFromClass ?>">حذف از دوره</a>
                                        <?php
                                        if ($memberCourse['status'] == '0') {
                                            $form = ActiveForm::begin(
                                                [
                                                    'action' => ['add_user_to_adobe'],
                                                    "method" => "post",
                                                ]
                                            );
                                            ?>
                                            <input type="hidden" name="courseId" value="<?= (string) $model->_id ?>">
                                            <button class="dropdown-item">ثبت در ادوبی</button>
                                            <?php
                                            ActiveForm::end();
                                        } else {
                                            ?>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">تغییر وضعیت</a>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $changeRole ?>">تغییر نقش</a>
                                            <?php
                                        }
                                        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker' || (Yii::$app->user->identity->role == 'teacher' && Yii::$app->user->identity->mentor != true))
                                        {
                                            ?>
                                            <a class="dropdown-item show-course-scores" href="#" data-bs-toggle="modal" data-bs-target="#show-course-scores" id="<?php echo (string) $model->_id . '-' . (string) $member->_id; ?>">ثبت نمره</a>
                                            <?php
                                        }
                                        ?>
                                        <a class="dropdown-item show-finance-info" href="#" data-bs-toggle="modal" data-bs-target="#show-finance-info" id="<?php echo (string) $model->_id . '-' . (string) $member->username; ?>">اطلاعات مالی</a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="<?= $changeStatus ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= $member->first_name . ' ' . $member->last_name ?></h5>
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
                                            <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                            <input type="hidden" name="courseId" value="<?= (string) $model->_id ?>">
                                            <p>وضعیت <?= $member->first_name . ' ' . $member->last_name ?> در حال حاضر (<?= $memberStatus ?>) می باشد</p>
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
                            <div class="modal fade" id="<?= $deleteFromClass ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف از دوره <?= $member->first_name . ' ' . $member->last_name ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php $form = ActiveForm::begin(
                                                [
                                                    'action' => ['delete_user_from_course'],
                                                    "method" => "post",
                                                    'options' => [
                                                        'class' => '',
                                                        'enctype' => 'multipart/form-data'
                                                    ],
                                                ]
                                            ); ?>
                                            <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                            <input type="hidden" name="courseId" value="<?= (string) $model->_id ?>">
                                            <p>آیا از حذف <?= $member->first_name . ' ' . $member->last_name ?> از این دوره مطمئن هستید؟ </p>
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
                            <div class="modal fade" id="<?= $changeRole ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر نقش <?= $member->first_name . ' ' . $member->last_name ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php $form = ActiveForm::begin(
                                                [
                                                    'action' => ['change_role'],
                                                    "method" => "post",
                                                    'options' => [
                                                        'class' => '',
                                                        'enctype' => 'multipart/form-data'
                                                    ],
                                                ]
                                            ); ?>
                                            <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                            <input type="hidden" name="courseId" value="<?= (string) $model->_id ?>">
                                            <p>نقش <?= $member->first_name . ' ' . $member->last_name ?> در حال حاضر (<?= $userRole ?>) می باشد</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">تغییر نقش</button>
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
                        <div class="alert alert-danger" role="alert">کاربری یافت نشد</div>
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
            <div class="tab-pane fade" id="id4" role="tabpanel">
                <?php
                $form = ActiveForm::begin(
                    [
                        'action' => ['packages/other_teachers'],
                        "method" => "post",
                    ]
                );
                ?>
                <div class="row">
                    <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                    <div class="col-8 col-md-8 col-lg-8 col-sm-12 mb-3">
                        <label class="form-label" for="basic-default-fullname">سایر اساتید *</label>
                        <?= $form->field($model, 'other_teachers')->dropDownList(
                            ArrayHelper::map($teachers, function ($model) {
                                return (string) $model->_id;
                            }, function ($model) {
                                return $model->first_name . ' ' . $model->last_name;
                            }),
                            [
                                'prompt' => 'لطفا استاد درس را انتخاب کنید',
                                'class' => 'select2 form-select',
                                'id' => 'inputGroupSelect04',
                                'multiple' => true,
                                'aria-label' => 'Example select with button addon',
                            ]
                        )->label(false) ?>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4 col-sm-12 mb-3">
                        <button class="btn btn-success mt-4" type="submit">بروزرسانی اساتید</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="tab-pane fade" id="id5" role="tabpanel">
                <?php
                if ($discounts != null)
                {
                    ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">کدهای تخفیف ثبت شده</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                            افزودن کد تخفیف
                        </button>
                    </div>
                    <?php
                    $discountRow = 0;
                    foreach ($discounts as $discount)
                    {
                        $deleteDiscount = 'deleteDiscount' . rand();
                        $used = 'استفاده نشده';
                        if($discount->used != null)
                        {
                            $usedCodeMember = $this->context->member_detail($discount->used);
                            if($usedCodeMember != null)
                                $used = 'استفاده شده توسط '.$usedCodeMember->first_name.' '.$usedCodeMember->last_name.'('.$usedCodeMember->username.')';
                            else
                                $used = 'ناشناس';
                        }
                        ?>
                        <nav class="navbar navbar-expand-lg bg-label-secondary mb-2">
                            <div class="container-fluid">
                                <div class="collapse navbar-collapse" id="navbar-ex-8">
                                    <div class="navbar-nav me-auto">
                                        <a class="nav-item nav-link active" href="javascript:void(0)">مبلغ تخفیف: <?= number_format($discount->amount) . ' تومان' ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">کد تخفیف: <?= $discount->code ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">وضعیت: <?= $used ?></a>
                                    </div>
                                    <?php
                                    if($discount->used == null)
                                    {
                                        ?>
                                        <ul class="navbar-nav ms-lg-auto">
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="modal" data-bs-target="#<?= $deleteDiscount ?>" href="javascript:void(0);">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C" />
                                                        <path opacity="0.5" d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12405C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001Z" fill="#1C274C" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.42543 11.4815C9.83759 11.4381 10.2051 11.7547 10.2463 12.1885L10.7463 17.4517C10.7875 17.8855 10.4868 18.2724 10.0747 18.3158C9.66253 18.3592 9.29499 18.0426 9.25378 17.6088L8.75378 12.3456C8.71256 11.9118 9.01327 11.5249 9.42543 11.4815Z" fill="#1C274C" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5747 11.4815C14.9868 11.5249 15.2875 11.9118 15.2463 12.3456L14.7463 17.6088C14.7051 18.0426 14.3376 18.3592 13.9254 18.3158C13.5133 18.2724 13.2126 17.8855 13.2538 17.4517L13.7538 12.1885C13.795 11.7547 14.1625 11.4381 14.5747 11.4815Z" fill="#1C274C" />
                                                    </svg>
                                                    حذف
                                                </a>
                                            </li>
                                        </ul>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </nav>
                        <div class="modal fade" id="<?= $deleteDiscount ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف کد تخفیف</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['delete_discount'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <?php echo $form->field($discount, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            آیا از حذف کد تخفیف با مبلغ <?= number_format($discount->amount) ?> تومان و کد <?= $discount->code  ?> مطمئن هستید؟
                                        </div>
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
                        <?php
                        $discountRow++;
                    }
                }
                else
                {
                    ?>
                    <div class="alert alert-warning text-dark" role="alert">برای این دوره تا کنون کد تخفیفی ثبت نشده است</div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                        افزودن کد تخفیف
                    </button>
                    <?php
                }
                ?>
                <div class="modal fade" id="add_discount_code" tabindex="-1" style="display: none;" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن کد تخفیف</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                $discountModel = new Discounts();
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['add_discount'],
                                        "method" => "post",
                                    ]
                                ); ?>
                                <?= $form->field($discountModel, 'course_id')->hiddenInput(
                                    [
                                        'value' => (string) $model->_id,
                                    ]
                                )->label(false); ?>
                                <div class="row">
                                    <div class="col-12 col-md-12 col-lg-12 col-sm-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">مبلغ کد تخفیف (تومان): +</label>
                                        <?= $form->field($discountModel, 'amount')->textInput(
                                            [
                                                'class' => 'form-control text-start',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا مبلغ را صحیح وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                                'onkeypress' => 'return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)'
                                            ]
                                        )->label(false); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    بستن
                                </button>
                                <button type="submit" class="btn btn-primary">افزودن کد تخفیف</button>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت درس جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add_lesson_to_package'],
                        "method" => "post",
                    ]
                ); ?>
                <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان درس *</label>
                        <?php
                        echo $form->field($newLesson, 'lessons[_id]')->dropDownList(
                            $remainingLessons,
                            [
                                'prompt' => 'لطفا درس را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">استاد *</label>
                        <?php
                        echo $form->field($newLesson, 'lessons[teachers]')->dropDownList(
                            ArrayHelper::map($teachers, function ($model) {
                                return (string) $model->_id;
                            }, function ($model) {
                                return $model->first_name . ' ' . $model->last_name;
                            }),
                            [
                                'prompt' => 'لطفا مدرس درس را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">تاریخ شروع درس * </label>
                        <?=
                        $form->field($newLesson, 'lessons[date][from]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">تاریخ اتمام درس * </label>
                        <?=
                        $form->field($newLesson, 'lessons[date][to]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">ساعت شروع دوره * </label>
                        <?= $form->field($model, 'lessons[date][time]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">مدت زمان (ساعت) * </label>
                        <?= $form->field($model, 'lessons[date][duration]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">مخفی کردن آرشیو * </label>
                        <?= $form->field($model, 'lessons[hide_archive]')->dropDownList(
                            $hiddenArchive,
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                            ]
                        )->label(false); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت درس</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>



<?php
if (Yii::$app->user->identity->role == 'user' && $model->status != '7') {
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
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
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
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
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

<div class="modal fade" id="show-course-scores" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="body"></div>
        </div>
    </div>
</div>


<div class="modal fade" id="show-finance-info" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font finance_title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="finance_body" class="modal-body"></div>
        </div>
    </div>
</div>