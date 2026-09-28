<?php
$this->title = 'مدیریت دوره های جامع و یکساله';

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
$courseType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'حضوری',
);
$capacityType = array(
    '1' => 'نامحدود',
    '2' => 'محدود',
    '3' => 'سازمانی'
);
if (Yii::$app->user->identity->role != 'user' || Yii::$app->user->identity->role != 'cnt') {
    $collegeScript = <<< JS
     $.get("/courses/brokers1", { id: "6567b4e3a882f9ecb300fee2" } )
        .done(function(data) {
        var main_data=JSON.parse(data);
            $('#broker1').html(main_data.brokers);
            $('#teachers1').html(main_data.teachers);
            $('#lessons1').html(main_data.lessons);
        });
JS;
    $this->registerJs($collegeScript);
}
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
    toastr.error("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.warning("برای ثبت دوره حداقل یک درس باید اضافه شود", {
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
    toastr.success("وضعیت دوره مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.warning("حجم عکس وارد شده بیشتر از اندازه تعیین شده است", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.error("به دلیل ناقص بودن اطلاعات مالی دانشکده امکان ثبت دوره وجود ندارد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.error("به دلیل ناقص بودن اطلاعات مالی کارگزار امکان ثبت دوره وجود ندارد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("دوره مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.error("دوره با موفقیت ثبت گردید اما در ادوبی ثبت نگردید لطفا مجددا برای ثبت در ادوبی دوره تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '12')
        $script = <<< JS
    toastr.success("کلاس مورد نظر در ادوبی ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '13')
        $script = <<< JS
    toastr.error("خطای ثبت دوره در ادوبی، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '14')
        $script = <<< JS
    toastr.success("وضعیت نمایش دوره مورد نظر در سایت تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '15')
        $script = <<< JS
    toastr.success("دوره مورد نظر برای تائید به ادمین ارسال گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '16')
        $script = <<< JS
    toastr.error("دوره مورد نظر به دلیل رو به اتمام بودن قرارداد کارگزار قابل تائید نمی باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '17')
        $script = <<< JS
    toastr.error("برای ارسال دوره به منظور دریافت مجوز باید حداقل یک درس را ثبت کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '18')
        $script = <<< JS
    toastr.error("دوره مورد نظر به دلیل اتمام قرارداد کارگزار قابل تائید نمی باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

?>
<?php
$url = Yii::$app->urlManager->createAbsoluteUrl('courses/show_course_users', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-course-detail',function(e) {
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
                <a href="javascript:void(0);">دوره های جامع و یکساله</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges,
        'brokers' => $brokers,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"> دوره های ثبت شده</h5>
            <?php
            if (DashboardController::access('create-package')) {
            ?>
                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('packages/create-package') ?>" type="button" class="btn btn-success">
                    <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت دوره جدید
                </a>
            <?php
            }
            ?>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>تصویر</th>
                        <th>عنوان دوره</th>
                        <?php if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') echo '<th>دانشکده</th>'; ?>
                        <?php
                        if (Yii::$app->user->identity->role != 'teacher') {
                        ?>
                            <th>ثبت کننده</th>
                            <th>تاریخ درخواست</th>
                            <th>کد مجوز</th>
                        <?php
                        }
                        ?>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php
                    $i = 1;
                    foreach ($dataProvider->models as $course)
                    {
                        $viewRejectionReason = 'viewRejectionReason' . rand();
                        $registrantInAdobe = 'registrantInAdobe' . rand();
                        $changeStatus = 'changeStatus' . rand();
                        $hiddenCourse = 'hiddenCourse' . rand();
                        $copyCourse = 'copyCourse' . rand();
                        $collegeDetail = DashboardController::college_detail($course->college);
                        $status = 'نامشخص';
                        $licenseCode = '-';
                        $sis = '';
                        if($course->status == '1' || $course->status == '6')
                        {
                            if($course->show_in_site === false)
                                $sis = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M2.91858 6.60465C2.70062 6.09784 2.11327 5.86324 1.60603 6.08063C1.0984 6.29818 0.863613 6.8869 1.08117 7.39453L1.0816 7.39553L1.08267 7.39802L1.08566 7.4049L1.09505 7.42618C1.10282 7.44366 1.11363 7.46765 1.12752 7.49772C1.15529 7.55783 1.19539 7.64235 1.2481 7.74777C1.35345 7.95845 1.5096 8.25357 1.71879 8.605C2.12772 9.29201 2.74529 10.2043 3.59029 11.1241L2.79285 11.9215C2.40232 12.312 2.40232 12.9452 2.79285 13.3357C3.18337 13.7262 3.81654 13.7262 4.20706 13.3357L5.04746 12.4953C5.61245 12.9515 6.24405 13.3814 6.94417 13.7519L6.16177 14.9544C5.86056 15.4173 5.99165 16.0367 6.45457 16.338C6.91748 16.6392 7.53693 16.5081 7.83814 16.0452L8.82334 14.531C9.50014 14.7386 10.2253 14.8864 11 14.9556V16.4998C11 17.0521 11.4477 17.4998 12 17.4998V12.9998C9.25227 12.9998 7.18102 11.8012 5.69633 10.4109C5.68823 10.4031 5.68003 10.3954 5.67173 10.3878C5.47324 10.2009 5.28532 10.0105 5.10775 9.81932C4.35439 9.00801 3.80137 8.19355 3.43737 7.58204C3.25594 7.27722 3.12302 7.02546 3.03696 6.85334C2.99397 6.76735 2.96278 6.70147 2.94319 6.65905C2.93339 6.63785 2.92651 6.62253 2.9225 6.61352L2.91858 6.60465ZM1.08117 7.39453L1.99995 6.99977C1.08081 7.39369 1.08117 7.39453 1.08117 7.39453Z" fill="#1C274C"/>
<path opacity="0.5" d="M15.2209 12.3984C14.2784 12.7694 13.209 13.0002 12 13.0002V17.5002C12.5523 17.5002 13 17.0525 13 16.5002V14.9559C13.772 14.8867 14.4974 14.7392 15.1764 14.5311L16.1618 16.0456C16.463 16.5085 17.0825 16.6396 17.5454 16.3384C18.0083 16.0372 18.1394 15.4177 17.8382 14.9548L17.0558 13.7524C17.757 13.3816 18.3885 12.9517 18.9527 12.496L19.7929 13.3361C20.1834 13.7267 20.8166 13.7267 21.2071 13.3361C21.5976 12.9456 21.5976 12.3124 21.2071 11.9219L20.4097 11.1245C21.1521 10.3164 21.7181 9.51502 22.1207 8.86887C22.384 8.44627 22.5799 8.08609 22.7116 7.82793C22.7775 7.69874 22.8274 7.59476 22.8619 7.5209C22.8791 7.48397 22.8924 7.45453 22.902 7.4332L22.9134 7.40736L22.917 7.39913L22.9191 7.39411C23.1367 6.88648 22.9015 6.2986 22.3939 6.08105C21.8864 5.86355 21.2985 6.09892 21.0809 6.60627L21.0759 6.61747C21.0706 6.62926 21.0617 6.6489 21.0492 6.6758C21.0241 6.72962 20.9844 6.81235 20.9299 6.91928C20.8207 7.13337 20.6526 7.4431 20.4233 7.81119C19.9628 8.55023 19.2652 9.50857 18.3156 10.3999C17.4746 11.1893 16.4469 11.9158 15.2209 12.3984Z" fill="#1C274C"/>
</svg>
';
                            else
                                $sis = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path opacity="0.5" d="M2 12C2 13.6394 2.42496 14.1915 3.27489 15.2957C4.97196 17.5004 7.81811 20 12 20C16.1819 20 19.028 17.5004 20.7251 15.2957C21.575 14.1915 22 13.6394 22 12C22 10.3606 21.575 9.80853 20.7251 8.70433C19.028 6.49956 16.1819 4 12 4C7.81811 4 4.97196 6.49956 3.27489 8.70433C2.42496 9.80853 2 10.3606 2 12Z" fill="#1C274C"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12ZM9.75 12C9.75 10.7574 10.7574 9.75 12 9.75C13.2426 9.75 14.25 10.7574 14.25 12C14.25 13.2426 13.2426 14.25 12 14.25C10.7574 14.25 9.75 13.2426 9.75 12Z" fill="#1C274C"/>
</svg>
';
                        }
                        if ($course->license_code != null)
                            $licenseCode = $course->license_code;
                        if ($course->status == '0') {
                            $status = 'تائید شده - غیر فعال';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '1') {
                            $status = 'تائید شده - فعال';
                            $statusBg = 'bg-label-success';
                        } else if ($course->status == '2') {
                            $status = 'در انتظار بررسی';
                            $statusBg = 'bg-label-primary';
                        } else if ($course->status == '3') {
                            $status = 'پیش نویس';
                            $statusBg = 'bg-label-info';
                        } else if ($course->status == '4') {
                            $status = 'نیاز به اصلاح';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '5') {
                            $status = 'رد شده';
                            $statusBg = 'bg-label-danger';
                        } else if ($course->status == '6') {
                            $status = 'اتمام یافته';
                            $statusBg = 'bg-label-dark';
                        } else if ($course->status == '7') {
                            $status = 'در انتظار بررسی دانشکده';
                            $statusBg = 'bg-label-primary';
                        }  else if ($course->status == '8') {
                            $status = 'نیاز به اصلاح توسط دانشکده';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '9') {
                            $status = 'رد شده توسط دانشکده';
                            $statusBg = 'bg-label-danger';
                        }
                        $meetingLost = true;
                        if($course->lessons != null)
                            if(count($course->lessons) > 0)
                                if(array_key_exists('meeting', $course->lessons[0]))
                                    $meetingLost = false;
                        $registrant = 'نامشخص';
                        $role = '';
                        $registrantDetail = DashboardController::registrant_detail($course->registrant);
                        if ($registrantDetail != null)
                        {
                            if ($registrantDetail->role == 'user')
                                $role = 'ادمین';
                            else  if ($registrantDetail->role == 'cnt')
                                $role = 'کارمند مرکز';
                            else if ($registrantDetail->role == 'emp')
                                $role = 'کارشناس دانشکده';
                            else if ($registrantDetail->role == 'broker')
                                $role = 'کارگزار';
                            $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name;
                        }

                    ?>
                        <tr>
                            <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                            <td>
                                <div class="avatar avatar-sm me-2">
                                    <img src="<?= $front . '/package_images/' . $course->preview_image ?>" alt="" class="rounded-circle">
                                </div>
                            </td>
                            <td class="text-wrap w-25"><?= $course->title['main_fa'] ?></td>
                            <?php
                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') {
                                if (strlen($collegeDetail->title) <= 30)
                                    echo '<td>' . $collegeDetail->title . '</td>';
                                else {
                            ?>
                                    <td>
                                        <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $collegeDetail->title ?>">
                                            <?= substr($collegeDetail->title, 0, 27) . '...' ?>
                                        </button>
                                    </td>
                            <?php
                                }
                            }

                            ?>
                            <?php
                            if (Yii::$app->user->identity->role != 'teacher') {
                            ?>
                                <td>
                                    <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $role ?>">
                                        <?= $registrant ?>
                                    </button>
                                </td>
                                <td><?= jdate('Y/m/d', hexdec(substr($course->_id, 0, 8))) ?></td>
                                <td><?= $licenseCode ?></td>
                            <?php
                            }
                            ?>
                            <td>
                                <span class="badge <?= $statusBg ?>"><?= $status ?></span>
                                <?php
                                if($course->modified === true)
                                    echo '<br><span class="badge bg-label-info">اطلاح شده در انتظار بررسی</span>';
                                ?>
                                <?php
                                if(($course->adobe_status == '0' || $meetingLost) && ($course->content_type == '1' || $course->content_type == '2'))
                                    echo '<br><span class="badge bg-label-danger">خطای ادوبی در ثبت کلاس</span>';
                                ?>
                                <?= $sis ?>
                            </td>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <?php
                                    if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker') {
                                    ?>
                                        <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['packages/edit-package', '_id' => (string) $course->_id]) ?>">
                                            <?php
                                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                                echo 'تاييد و بررسي دوره';
                                            else
                                                echo 'مشاهده و ویرایش';
                                            ?>
                                        </a>
                                    <?php
                                    }
                                    ?>
                                    <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/teacher-part', '_id' => (string) $course->_id]) ?>">مدیریت دوره</a>
                                    <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $copyCourse ?>">کپی کردن دوره</a>
                                    <?php
                                    if ((Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker') && ($course->status == '4' || $course->status == '5' || $course->status == '8' || $course->status == '9'))
                                    {
                                    ?>
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewRejectionReason ?>">مشاهده دلیل رد یا اصلاح </a>
                                    <?php
                                    }
                                    if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') {
                                    ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete" id="<?php echo (string) $course->_id; ?>">حذف دوره</a>
                                    <?php
                                    }
                                    if(($course->adobe_status == '0' || $meetingLost) && ($course->content_type == '1' || $course->content_type == '2'))
                                    {
                                        ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $registrantInAdobe ?>">ثبت کلاس در ادوبی</a>
                                        <?php
                                    }
                                    if ($course->status == '1' || $course->status == '6')
                                    {
                                        $showInSite = 'مخفی کردن در سایت';
                                        if($course->show_in_site === false)
                                            $showInSite = 'نمایش در سایت';
                                        ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $hiddenCourse ?>"><?= $showInSite ?></a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $viewRejectionReason ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده دلیل رد یا اصلاح درس <?= $course->title['main_fa'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>وضعیت: <?= $status ?></p>
                                        <p style="word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; margin: 0; line-height: 1.6;">
                                            دلیل: <?= $course->rejection_reason ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $registrantInAdobe ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت دوره در ادوبی </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['packages/register_class_in_adobe'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($course, '_id')->hiddenInput()->label(false); ?>
                                        آیا از ثبت دوره در ادوبی کاکنت اطمینان دارید؟
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary submit-course-btn">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $hiddenCourse ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">مخفی / نمایش درس در سایت </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['packages/hidden_course'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($course, '_id')->hiddenInput()->label(false); ?>
                                        <p>
                                            <?php
                                            $currentShowInSite = true;
                                            if($course->status == '1')
                                                if($course->show_in_site === false)
                                                    $currentShowInSite = false;
                                            if($currentShowInSite == true)
                                                echo 'دوره مورد نظر هم اکنون در سایت نمایان است.<br> آیا از مخفی کردن درس در سایت مطمئن هستید؟';
                                            else
                                                echo 'درس مورد نظر هم اکنون در سایت مخفی شده است<br> آیا از نمایان کردن آن مطمئن هستید؟';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary submit-course-btn">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $copyCourse ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">کپی کردن دوره <?= $course->title['main_fa'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>آیا از کپی کردن دوره <?= $course->title['main_fa'] ?> مطمئن هستید؟ </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['packages/copy-package', '_id' => (string) $course->_id]) ?>" class="btn btn-primary submit-course-btn">بله مطمئنم</a>
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


<div class="modal fade" id="delete" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <div id="submit"></div>
            </div>
        </div>
    </div>
</div>