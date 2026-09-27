<?php


use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use app\models\CoursesContents;
use frontend\controllers;

$this->title = 'مدیریت محتوای درس ' . Html::encode($lessonDetail->title);
require_once(Yii::$app->basePath . '/web/jdf.php');

Select2Asset::register($this);
$model = new CoursesContents();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("سرفصل نظر ثبت گردید", {
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
    toastr.danger("عنوان سرفصل وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("ویدئو مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("ازمون مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("تمرین مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.success("حذف با موفقیت انجام گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.success("پیغام مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.success("فایل مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("عنوان مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.success("نظرسنجی مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$downloadScript = <<< JS
    $('.download-all').click(function() {
        $(this).attr('disabled', true)
        $.ajax({
            url:`https://api-eec.ut.ac.ir/adobe-connect/archive-assignments/` + $(this).attr('data-id'),
            type:'POST',
            data:{},
            success:function(data) {
                $('.download-all').attr('disabled', false)
                // const main_data = JSON.parse(data);
                alert(data.data)
            }
        });
    });

    $('.ajax-upload').on('submit', function(e) {
        e.preventDefault();
        console.log($(this).id)
        const selector = "#" + $(this).attr('id') + " "
        const fileData = document.querySelector(selector + '.drop-file')?.files[0];
        const formData = new FormData();
        formData.append('file', fileData);
        formData.append('title', $(selector + ".file-name").val());
        formData.append('save_to_db', 'no');

        $(selector + "button[type=submit]").attr("disabled", true);
        $.ajax({
        xhr: function() {
            const xhr = new window.XMLHttpRequest();

            xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
                let percentComplete = evt.loaded / evt.total;
                percentComplete = parseInt(percentComplete * 100);
                console.log(percentComplete);
                $(".progress-bar").css('width', percentComplete + "%")
                $(".progress-bar").text(percentComplete + "%")

                if (percentComplete === 100) {
                    $(".progress-bar").css('width', 99 + "%")
                    $(".progress-bar").text(99 + "%")
                }
            }
            }, false);

            return xhr;
        },
        url: "https://api-eec.ut.ac.ir/adobe-connect/upload-center",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(result) {
            console.log(result);
            toastr.success("فایل با موفقیت آپلود شد", {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });
            $(".progress-bar").css('width', 100 + "%")
            $(".progress-bar").text(100 + "%")
            $(selector + ".drop-file").attr('type', 'text');
            $(selector + ".drop-file").attr('name', 'file_name');
            $(selector + ".drop-file").val(result?.data?.filename);
            $('.ajax-upload').off('submit');
            $(selector + "button[type=submit]").attr("disabled", false);
            $(selector + "button[type=submit]").click();
        }
    });
    });
JS;

$this->registerJs($downloadScript);


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


    .progress-bar {
        transition: all 0.4s ease;
        border-radius: 20px;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت دوره</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">دوره ها</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت درس</a>
            </li>
        </ol>
    </nav>

    <div class="card">
        <nav class="navbar navbar-expand-lg bg-white">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="navbar-ex-6">
                    <div class="navbar-nav me-auto">
                        <a class="nav-item nav-link active" href="javascript:void(0)">مدیریت محتوای درس <?= Html::encode($lessonDetail->title) ?></a>
                    </div>
                    <ul class="navbar-nav ms-lg-auto">
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create-new-headline"><i class="tf-icons navbar-icon bx bx-plus"></i> افزودن سرفصل جدید</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/teacher-part', '_id' => Yii::$app->request->get('courseId')]) ?>"> بازگشت</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="card-body">
            <?php
            if ($lessonsContents != null) {
            ?>
                <ul class="timeline timeline-dashed mt-4">
                    <?php
                    foreach ($lessonsContents as $headline)
                    {
                        $newVideoFromUploadCenter = new CoursesContents();
                        $newVideoFromUploadCenterId = 'upload_center' . rand();
                        $newVideoDirectId = 'new_upload_center' . rand();
                        $newExam = 'new_exam' . rand();
                        $newSurvey = 'new_survey' . rand();
                        $newWorkoutFromUploadCenterId = 'new_workout_from_upload_center' . rand();
                        $newDirectWorkoutId = 'new_direct_workout' . rand();
                        $newFileFromUploadCenter = 'new_file_from_upload_center' . rand();
                        $newFileDirect = 'new_file_direct' . rand();
                        $newMessage = 'new_message' . rand();
                        $editTitle = 'editTitle' . rand();
                    ?>
                        <li class="timeline-item timeline-item-dark mb-4 border-1">
                            <span class="timeline-indicator timeline-indicator-primary">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </span>
                            <div class="timeline-event">
                                <div class="timeline-header border-bottom mb-3 mt-n1">
                                    <h5 class="mb-1 mb-sm-2"><?= Html::encode($headline->title) ?></h5>
                                    <a href="javascript:void(0);" class="badge bg-label-dark" data-bs-toggle="modal" data-bs-target="#<?= $editTitle ?>">ویرایش عنوان</a>
                                </div>
                                <div class="d-flex justify-content-between flex-wrap mb-2 lh-1-85">
                                    <div class="demo-inline-spacing">
                                        <div class="btn-group" role="group" aria-label="Basic example">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    ویدئو
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newVideoFromUploadCenterId ?>">انتخاب از آپلودسنتر</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newVideoDirectId ?>">بارگزاری ویدئو جدید</a></li>
                                                </ul>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success" data-bs-toggle="modal" data-bs-target="#<?= $newExam ?>">
                                                    آزمون
                                                </button>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success" data-bs-toggle="modal" data-bs-target="#<?= $newSurvey ?>">
                                                    نظرسنجی
                                                </button>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    تمرین
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newWorkoutFromUploadCenterId ?>">انتخاب فایل از آپلودسنتر</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newDirectWorkoutId ?>">بارگزاری فایل تمرین جدید</a></li>
                                                </ul>
                                            </div>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    فایل
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newFileFromUploadCenter ?>">انتخاب فایل از آپلودسنتر</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newFileDirect ?>">بارگزاری فایل جدید</a></li>
                                                </ul>
                                            </div>

                                            <div class="btn-group">
                                                <button type="button" class="btn btn-lg btn-success" data-bs-toggle="modal" data-bs-target="#<?= $newMessage ?>">
                                                    پیغام
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="divider">
                                    <div class="divider-text">محتوای ثبت شده برای <?= Html::encode($headline->title) ?></div>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php
                                    if ($headline->content != null) {
                                        $counter = 1;
                                        $eventRow = 0;
                                        foreach ($headline->content as $value) {
                                            if ($value['type'] == '1')
                                                $type = 'ویدئو';
                                            else if ($value['type'] == '2')
                                                $type = 'آزمون';
                                            else if ($value['type'] == '3')
                                                $type = 'نظرسنجی';
                                            else if ($value['type'] == '4')
                                                $type = 'تمرین';
                                            else if ($value['type'] == '5')
                                                $type = 'پیغام';
                                            else if ($value['type'] == '6')
                                                $type = 'فایل';
                                            $deleteEvent = 'deleteEvent' . rand();
                                            $viewExamResults = 'viewExamResults' . rand();
                                            $viewPracticeResults = 'viewPracticeResults' . rand();
                                            $viewPollResults = 'viewPollResults' . rand();
                                    ?>
                                            <nav class="navbar navbar-expand-lg bg-white mb-2 rounded cursor-move d-flex" draggable="false">
                                                <div class="container-fluid">
                                                    <a class="navbar-brand" href="javascript:void(0)"><span class="badge badge-center bg-secondary"><?= $counter++ ?></span></a>
                                                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-ex-6">
                                                        <span class="navbar-toggler-icon"></span>
                                                    </button>

                                                    <div class="collapse navbar-collapse" id="navbar-ex-6">
                                                        <div class="navbar-nav me-auto">
                                                            <a class="nav-item nav-link" href="javascript:void(0)"><?= Html::encode($value['title']) ?></a>
                                                            <a class="nav-item nav-link disabled" href="javascript:void(0)"><?= $type ?> ثبت شده در تاریخ <?= jdate('Y/m/d', $value['date']) ?></a>
                                                        </div>
                                                        <ul class="navbar-nav ms-lg-auto">
                                                            <?php
                                                            if ($value['type'] == '2') {
                                                            ?>
                                                                <a class="nav-link" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewExamResults ?>">
                                                                    <span class="badge bg-label-dark">مشاهده نتایج</span>
                                                                </a>
                                                                <?php
                                                            }
                                                            if ($value['type'] == '3') {
                                                                $poolAccess = true;
                                                                if (Yii::$app->user->identity->role == 'teacher') {
                                                                    $teacherDetail = $this->context->teacher_detail(Yii::$app->user->identity->username);
                                                                    if ($teacherDetail != null) {
                                                                        if ($courseDetail->other_teachers != null) {
                                                                            if ((array_search((string) $teacherDetail->_id, $courseDetail->other_teachers)) !== false)
                                                                                $poolAccess = true;
                                                                            else
                                                                                $poolAccess = false;
                                                                        } else
                                                                            $poolAccess = false;
                                                                    } else
                                                                        $poolAccess = false;
                                                                }
                                                                if ($poolAccess) {
                                                                ?>
                                                                    <a class="nav-link" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewPollResults ?>">
                                                                        <span class="badge bg-label-dark">مشاهده نتایج</span>
                                                                    </a>
                                                                <?php
                                                                }
                                                            }
                                                            if ($value['type'] == '4') {
                                                                ?>
                                                                <a class="nav-link" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewPracticeResults ?>">
                                                                    <span class="badge bg-label-dark">مشاهده پاسخ تمرین ها</span>
                                                                </a>
                                                            <?php
                                                            }
                                                            ?>
                                                            <li class="nav-item">
                                                                <a class="nav-link" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $deleteEvent ?>">
                                                                    <svg class="tf-icons navbar-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path opacity="0.5" d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#1C274C" />
                                                                        <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C" />
                                                                    </svg>
                                                                    حذف</a>
                                                            </li>
                                                            <?php
                                                            if($value['type'] == '1' || $value['type'] == '4' || $value['type'] == '6')
                                                            {
                                                                ?>
                                                                <li class="nav-item">
                                                                    <a class="nav-link" href="<?= Yii::$app->urlManager->createUrl(['manage-course-contents/download_file','filename' => $value['content']])  ?>">
                                                                        <svg class="tf-icons navbar-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                            <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M10 22H14C17.7712 22 19.6569 22 20.8284 20.8284C22 19.6569 22 17.7712 22 14V13.5629C22 12.6901 22 12.0344 21.9574 11.5001H18L17.9051 11.5001C16.808 11.5002 15.8385 11.5003 15.0569 11.3952C14.2098 11.2813 13.3628 11.0198 12.6716 10.3285C11.9803 9.63726 11.7188 8.79028 11.6049 7.94316C11.4998 7.16164 11.4999 6.19207 11.5 5.09497L11.5092 2.26057C11.5095 2.17813 11.5166 2.09659 11.53 2.01666C11.1214 2 10.6358 2 10.0298 2C6.23869 2 4.34315 2 3.17157 3.17157C2 4.34315 2 6.22876 2 10V14C2 17.7712 2 19.6569 3.17157 20.8284C4.34315 22 6.22876 22 10 22Z" fill="#1C274C"/>
                                                                            <path d="M9.01296 19.0472C8.72446 19.3176 8.27554 19.3176 7.98705 19.0472L5.98705 17.1722C5.68486 16.8889 5.66955 16.4142 5.95285 16.112C6.23615 15.8099 6.71077 15.7945 7.01296 16.0778L7.75 16.7688V13.5C7.75 13.0858 8.08579 12.75 8.5 12.75C8.91422 12.75 9.25 13.0858 9.25 13.5L9.25 16.7688L9.98705 16.0778C10.2892 15.7945 10.7639 15.8099 11.0472 16.112C11.3305 16.4142 11.3151 16.8889 11.013 17.1722L9.01296 19.0472Z" fill="#1C274C"/>
                                                                            <path d="M11.5092 2.2601L11.5 5.0945C11.4999 6.1916 11.4998 7.16117 11.6049 7.94269C11.7188 8.78981 11.9803 9.6368 12.6716 10.3281C13.3629 11.0193 14.2098 11.2808 15.057 11.3947C15.8385 11.4998 16.808 11.4997 17.9051 11.4996L21.9574 11.4996C21.9698 11.6552 21.9786 11.821 21.9848 11.9995H22C22 11.732 22 11.5983 21.9901 11.4408C21.9335 10.5463 21.5617 9.52125 21.0315 8.79853C20.9382 8.6713 20.8743 8.59493 20.7467 8.44218C19.9542 7.49359 18.911 6.31193 18 5.49953C17.1892 4.77645 16.0787 3.98536 15.1101 3.3385C14.2781 2.78275 13.862 2.50487 13.2915 2.29834C13.1403 2.24359 12.9408 2.18311 12.7846 2.14466C12.4006 2.05013 12.0268 2.01725 11.5 2.00586L11.5092 2.2601Z" fill="#1C274C"/>
                                                                        </svg>
                                                                        دانلود</a>
                                                                </li>
                                                                    <?php
                                                            }
                                                            ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </nav>
                                            <div class="modal fade" id="<?= $deleteEvent ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف <?= $type ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php $form = ActiveForm::begin(
                                                                [
                                                                    'action' => ['delete_event'],
                                                                    "method" => "post",
                                                                    'options' => [
                                                                        'enctype' => 'multipart/form-data'
                                                                    ],
                                                                ]
                                                            ); ?>
                                                            <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                                            <input type="hidden" name="row" value="<?= $eventRow++ ?>">
                                                            <div class="alert alert-danger" role="alert">آیا از حذف <?= $type ?> با عنوان <b><u><?= Html::encode($value['title']) ?></u></b> مطمئن هستید؟</div>
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
                                            if ($value['type'] == '2') {
                                            ?>
                                                <div class="modal fade" id="<?= $viewExamResults ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                    <div class="modal-dialog modal-xl modal-dialog-centered modal-add-new-role">
                                                        <div class="modal-content p-3 p-md-5">
                                                            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            <div class="modal-body">
                                                                <!-- Add role form -->
                                                                <div id="addRoleForm" class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework" onsubmit="return false" novalidate="novalidate">
                                                                    <div class="col-12">
                                                                        <h5>مشاهده نتایج آزمون <?= $value['title'] ?> از سرفصل <?= Html::encode($headline->title) ?></h5>
                                                                        <?php
                                                                        echo '<span class="badge bg-label-success h4"><a href="' . Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/exam_report', 'assignments_id' => $value['_id']], 'https') . '">گزارش اکسل</a></span>';
                                                                        ?>
                                                                        <?php
                                                                        $examResult = $this->context->exam_result($value['_id']);
                                                                        ?>
                                                                        <div class="table-responsive text-nowrap" id="sessionDetail">
                                                                            <?php
                                                                            if ($examResult != null) {
                                                                            ?>
                                                                                <div class="table-responsive text-nowrap">
                                                                                    <table class="table">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th>#</th>
                                                                                                <th>دانشپذیر</th>
                                                                                                <th>نمرات در تعداد دفعات آزمون</th>
                                                                                                <th>نمره نهایی</th>
                                                                                                <th>سوالات تشریحی</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="table-border-bottom-0">
                                                                                            <?php
                                                                                            $row = 1;
                                                                                            foreach ($examResult as $result) {
                                                                                                $userDetail = $this->context->user_detail($result->username);
                                                                                                if ($userDetail != null) {
                                                                                            ?>
                                                                                                    <tr>
                                                                                                        <td><span class="badge badge-center bg-label-secondary"><?= $row++ ?></span></td>
                                                                                                        <td><?= Html::encode($userDetail->first_name . ' ' . $userDetail->last_name) ?></td>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            if ($result->questions != null) {
                                                                                                            ?>
                                                                                                                <ul class="p-0 m-0">
                                                                                                                    <?php
                                                                                                                    $tryCounter = 1;
                                                                                                                    $sumScore = 0;
                                                                                                                    $maxScore = 0;
                                                                                                                    foreach ($result->questions as $try) {
                                                                                                                        $testDetail = $this->context->test_detail($result->exam_id);
                                                                                                                        $scoreBg = 'bg-danger';
                                                                                                                        if ($testDetail->pass_score != null)
                                                                                                                            if ($testDetail->pass_score <= $try['score'])
                                                                                                                                $scoreBg = 'bg-success';
                                                                                                                        if ($testDetail->score_type != null) {
                                                                                                                            if ($testDetail->score_type == '1')
                                                                                                                                $sumScore += $try['score'];
                                                                                                                            else if ($try['score'] > $maxScore)
                                                                                                                                $maxScore = $try['score'];
                                                                                                                        }
                                                                                                                    ?>
                                                                                                                        <li class="mb-0 d-flex justify-content-between">
                                                                                                                            <div class="d-flex align-items-center me-3">
                                                                                                                                <small class="text-muted mt-1 mt-sm-0 mb-1 mb-sm-0">تلاش <?= $this->context->digit2word($tryCounter++) ?></small>
                                                                                                                            </div>
                                                                                                                            <div class="d-flex align-items-center me-3">
                                                                                                                                <span class="badge badge-dot <?= $scoreBg ?> me-2"></span> <?= round($try['score']) ?>
                                                                                                                            </div>
                                                                                                                            <?php
                                                                                                                            $flag = 1;
                                                                                                                            if ($try['questions'] != null) {
                                                                                                                                $descriptiveFlag = false;
                                                                                                                                foreach ($try['questions'] as $examQuestion)
                                                                                                                                    if (is_array($examQuestion))
                                                                                                                                        if (array_key_exists('type', $examQuestion))
                                                                                                                                            if ($examQuestion['type'] == '3') {
                                                                                                                                                $flag = 2;
                                                                                                                                                $descriptiveFlag = true;
                                                                                                                                            }
                                                                                                                            }
                                                                                                                            ?>
                                                                                                                        </li>
                                                                                                                    <?php
                                                                                                                    }
                                                                                                                    ?>
                                                                                                                </ul>
                                                                                                            <?php
                                                                                                            }
                                                                                                            ?>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <div class="avatar avatar-sm flex-shrink-0 me-2">
                                                                                                                <?php
                                                                                                                if ($testDetail->score_type == '1')
                                                                                                                    $finalScore = $sumScore / count($result->questions);
                                                                                                                else
                                                                                                                    $finalScore = $maxScore;
                                                                                                                $finalScoreBg = 'bg-label-danger';
                                                                                                                if ($finalScore >= $testDetail->pass_score)
                                                                                                                    $finalScoreBg = 'bg-label-success';
                                                                                                                ?>
                                                                                                                <span class="avatar-initial rounded <?= $finalScoreBg ?>"><?= round($finalScore) ?></span>
                                                                                                            </div>
                                                                                                        </td>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            if ($descriptiveFlag === true) {
                                                                                                            ?>
                                                                                                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/correct-questions', '_id' => (string) $result->_id, 'url' =>  Yii::$app->request->url]) ?>"> تصحیح سوالات تشریحی</a>
                                                                                                            <?php
                                                                                                            }
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                            <?php
                                                                                                }
                                                                                            }
                                                                                            ?>
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            <?php
                                                                            } else
                                                                                echo '<div class="alert alert-warning" role="alert">هیج دانشپذیری تا کنون به این آزمون پاسخ نداده است</div>';
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                    <!--/ Add role form -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                            }
                                            if ($value['type'] == '3') {
                                                $examResult = $this->context->exam_result($value['_id']);
                                            ?>
                                                <div class="modal fade" id="<?= $viewPollResults ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-add-new-role">
                                                        <div class="modal-content p-3 p-md-5">
                                                            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            <div class="modal-body">
                                                                <!-- Add role form -->
                                                                <div id="addRoleForm" class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework" onsubmit="return false" novalidate="novalidate">
                                                                    <div class="col-12">
                                                                        <div class="card-header d-flex justify-content-between align-items-center">
                                                                            <h5>مشاهده نتایج نظرسنجی <?= Html::encode($value['title']) ?> از سرفصل <?= Html::encode($headline->title) ?></h5>
                                                                            <?php
                                                                            echo '<span class="badge bg-label-success h4"><a href="' . Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/poll_report', 'assignments_id' => $value['_id']], 'https') . '">گزارش اکسل</a></span>';
                                                                            ?>
                                                                        </div>
                                                                        <div class="table-responsive text-nowrap" id="sessionDetail">
                                                                            <?php
                                                                            if ($examResult != null)
                                                                            {
                                                                                $idCounter = 1;
                                                                                foreach ($examResult as $case)
                                                                                {
                                                                                    $id = 'accordionStyle1-' . $idCounter++;
                                                                                    $userDetail = $this->context->user_id($case->user_id);
                                                                                    if ($userDetail != null)
                                                                                    {
                                                                                    ?>
                                                                                        <div class="col-md">
                                                                                            <div class="accordion mt-3 accordion-header-primary" id="accordionStyle1">

                                                                                                <div class="accordion-item card">
                                                                                                    <h2 class="accordion-header">
                                                                                                        <button type="button" class="accordion-button collapsed bg-label-secondary mb-3" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="false">
                                                                                                            <?= Html::encode($userDetail->first_name . ' ' . $userDetail->last_name) ?>
                                                                                                        </button>
                                                                                                    </h2>

                                                                                                    <div id="<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#accordionStyle1">
                                                                                                        <div class="accordion-body lh-2">
                                                                                                            <?php
                                                                                                            if ($case->questions != null) {
                                                                                                                foreach ($case->questions[0]['questions'] as $question) {
                                                                                                            ?>
                                                                                                                    <div class="alert alert-success" role="alert">
                                                                                                                        <div>
                                                                                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                                                <path opacity="0.5" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" fill="#1C274C" />
                                                                                                                                <path d="M12 7.75C11.3787 7.75 10.875 8.25368 10.875 8.875C10.875 9.28921 10.5392 9.625 10.125 9.625C9.71079 9.625 9.375 9.28921 9.375 8.875C9.375 7.42525 10.5503 6.25 12 6.25C13.4497 6.25 14.625 7.42525 14.625 8.875C14.625 9.58584 14.3415 10.232 13.883 10.704C13.7907 10.7989 13.7027 10.8869 13.6187 10.9708C13.4029 11.1864 13.2138 11.3753 13.0479 11.5885C12.8289 11.8699 12.75 12.0768 12.75 12.25V13C12.75 13.4142 12.4142 13.75 12 13.75C11.5858 13.75 11.25 13.4142 11.25 13V12.25C11.25 11.5948 11.555 11.0644 11.8642 10.6672C12.0929 10.3733 12.3804 10.0863 12.6138 9.85346C12.6842 9.78321 12.7496 9.71789 12.807 9.65877C13.0046 9.45543 13.125 9.18004 13.125 8.875C13.125 8.25368 12.6213 7.75 12 7.75Z" fill="#1C274C" />
                                                                                                                                <path d="M12 17C12.5523 17 13 16.5523 13 16C13 15.4477 12.5523 15 12 15C11.4477 15 11 15.4477 11 16C11 16.5523 11.4477 17 12 17Z" fill="#1C274C" />
                                                                                                                            </svg>
                                                                                                                            <?= Html::encode($question['question_text']); ?>
                                                                                                                        </div>
                                                                                                                        <div>
                                                                                                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                                                <path opacity="0.5" d="M12 2C7.28595 2 4.92893 2 3.46447 3.46447C2 4.92893 2 7.28595 2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12C22 7.28595 22 4.92893 20.5355 3.46447C19.0711 2 16.714 2 12 2Z" fill="#1C274C" />
                                                                                                                                <path d="M12 6.25C12.4142 6.25 12.75 6.58579 12.75 7V13C12.75 13.4142 12.4142 13.75 12 13.75C11.5858 13.75 11.25 13.4142 11.25 13V7C11.25 6.58579 11.5858 6.25 12 6.25Z" fill="#1C274C" />
                                                                                                                                <path d="M12 17C12.5523 17 13 16.5523 13 16C13 15.4477 12.5523 15 12 15C11.4477 15 11 15.4477 11 16C11 16.5523 11.4477 17 12 17Z" fill="#1C274C" />
                                                                                                                            </svg>
                                                                                                                            <?php
                                                                                                                            if ($question['user_answer'] == 1)
                                                                                                                                echo Html::encode($question['first_option']);
                                                                                                                            else if ($question['user_answer'] == 2)
                                                                                                                                echo Html::encode($question['second_option']);
                                                                                                                            else if ($question['user_answer'] == 3)
                                                                                                                                echo Html::encode($question['third_option']);
                                                                                                                            else if ($question['user_answer'] == 4)
                                                                                                                                echo Html::encode($question['fourth_option']);
                                                                                                                            ?>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                            <?php
                                                                                                                }
                                                                                                            }
                                                                                                            ?>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>

                                                                                            </div>
                                                                                        </div>
                                                                            <?php
                                                                                    }
                                                                                }
                                                                            }
                                                                            else
                                                                                echo '<div class="alert alert-warning" role="alert">هیج دانشپذیری تا کنون به این نظرسنجی پاسخ نداده است</div>';
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                    <!--/ Add role form -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                            }
                                            if ($value['type'] == '4') {
                                            ?>
                                                <div class="modal fade" id="<?= $viewPracticeResults ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-add-new-role">
                                                        <div class="modal-content p-3 p-md-5">
                                                            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            <div class="modal-body">
                                                                <!-- Add role form -->
                                                                <div id="addRoleForm" class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework" onsubmit="return false" novalidate="novalidate">
                                                                    <div class="col-12">
                                                                        <h5 class="d-flex align-items-center justify-content-between">
                                                                            مشاهده پاسخ های تمرین <?= Html::encode($value['title']) ?> از سرفصل <?= Html::encode($headline->title) ?>
                                                                            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/file', 'assignment_id' => $value['_id'], 'course_id' => (string) $courseDetail->_id])  ?>" class="btn btn-primary" data-id="659480a1cb961">دانلود همه</a>
                                                                        </h5>
                                                                        <?php
                                                                        $particleResult = $this->context->particle_result($value['_id']);
                                                                        ?>
                                                                        <div class="table-responsive text-nowrap" id="sessionDetail">
                                                                            <?php
                                                                            if ($particleResult != null) {
                                                                            ?>
                                                                                <div class="table-responsive text-nowrap">
                                                                                    <table class="table">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th>#</th>
                                                                                                <th>دانشپذیر</th>
                                                                                                <th>تاریخ ارسال</th>
                                                                                                <th>دانلود تمرین</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody class="table-border-bottom-0">
                                                                                            <?php
                                                                                            $row = 1;
                                                                                            foreach ($particleResult as $case)
                                                                                            {
                                                                                                $userDetail = $this->context->user_id($case->user_id);
                                                                                                if ($userDetail != null) {
                                                                                            ?>
                                                                                                    <tr>
                                                                                                        <td><span class="badge badge-center bg-label-secondary"><?= $row++ ?></span></td>
                                                                                                        <td><?= Html::encode($userDetail->first_name . ' ' . $userDetail->last_name) ?></td>
                                                                                                        <td><?= jdate('H:i - Y/m/d', hexdec(substr($case->_id, 0, 8))) ?></td>
                                                                                                        <td>
<!--                                                                                                            <a download="" href="--><?php //= $front . '/uploads/' . $case->content ?><!--">-->
                                                                                                            <a download="" href="<?= Yii::$app->urlManager->createUrl(['manage-course-contents/download_exercise','filename' => $case['content'], 'course_id' => (string) $courseDetail->_id, 'assignment_id' => (string) $value['_id']])  ?>">
                                                                                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                                    <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M3 14.25C3.41421 14.25 3.75 14.5858 3.75 15C3.75 16.4354 3.75159 17.4365 3.85315 18.1919C3.9518 18.9257 4.13225 19.3142 4.40901 19.591C4.68577 19.8678 5.07435 20.0482 5.80812 20.1469C6.56347 20.2484 7.56459 20.25 9 20.25H15C16.4354 20.25 17.4365 20.2484 18.1919 20.1469C18.9257 20.0482 19.3142 19.8678 19.591 19.591C19.8678 19.3142 20.0482 18.9257 20.1469 18.1919C20.2484 17.4365 20.25 16.4354 20.25 15C20.25 14.5858 20.5858 14.25 21 14.25C21.4142 14.25 21.75 14.5858 21.75 15V15.0549C21.75 16.4225 21.75 17.5248 21.6335 18.3918C21.5125 19.2919 21.2536 20.0497 20.6517 20.6516C20.0497 21.2536 19.2919 21.5125 18.3918 21.6335C17.5248 21.75 16.4225 21.75 15.0549 21.75H8.94513C7.57754 21.75 6.47522 21.75 5.60825 21.6335C4.70814 21.5125 3.95027 21.2536 3.34835 20.6517C2.74643 20.0497 2.48754 19.2919 2.36652 18.3918C2.24996 17.5248 2.24998 16.4225 2.25 15.0549C2.25 15.0366 2.25 15.0183 2.25 15C2.25 14.5858 2.58579 14.25 3 14.25Z" fill="#1C274C" />
                                                                                                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 16.75C12.2106 16.75 12.4114 16.6615 12.5535 16.5061L16.5535 12.1311C16.833 11.8254 16.8118 11.351 16.5061 11.0715C16.2004 10.792 15.726 10.8132 15.4465 11.1189L12.75 14.0682V3C12.75 2.58579 12.4142 2.25 12 2.25C11.5858 2.25 11.25 2.58579 11.25 3V14.0682L8.55353 11.1189C8.27403 10.8132 7.79963 10.792 7.49393 11.0715C7.18823 11.351 7.16698 11.8254 7.44648 12.1311L11.4465 16.5061C11.5886 16.6615 11.7894 16.75 12 16.75Z" fill="#1C274C" />
                                                                                                                </svg>
                                                                                                            </a>
                                                                                                        </td>
                                                                                                    </tr>
                                                                                            <?php
                                                                                                }
                                                                                            }
                                                                                            ?>
                                                                                        </tbody>
                                                                                    </table>
                                                                                </div>
                                                                            <?php
                                                                            } else
                                                                                echo '<div class="alert alert-warning" role="alert">هیج دانشپذیری تا کنون به این تمرین پاسخ نداده است</div>';
                                                                            ?>
                                                                        </div>
                                                                    </div>
                                                                    <!--/ Add role form -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                    <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </li>
                        <div class="modal fade" id="<?= $newVideoFromUploadCenterId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">انتخاب فایل ویدئو از آپلود سنتر</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_video_from_upload_center'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '1'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان ویدئو *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">فایل های آپلود سنتر شما *</label>
                                                <?php
                                                echo $form->field($model, 'content[content]')->dropDownList(
                                                    $uploadCenter,
                                                    [
                                                        'prompt' => 'لطفا فایل را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'id' => '',
                                                    ]
                                                )->label(false);
                                                ?>
                                            </div>
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label class="switch switch-square">
                                                    <input type="checkbox" class="switch-input" name="download">
                                                    <span class="switch-toggle-slider">
                                                        <span class="switch-on"></span>
                                                        <span class="switch-off"></span>
                                                    </span>
                                                    <span class="switch-label">ویدئو قابلیت دانلود داشته باشد</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">افزودن ویدئو</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade ajax-upload" id="<?= $newVideoDirectId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">بارگزاری ویدئو</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_direct_video'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '1'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان ویدئو *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start file-name',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                        </div>
                                        <div class="card mb-4 relative">
                                            <div class="card-body">
                                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                    <div class="dz-message needsclick">
                                                        <?= $form->field($model, 'content[content]')->fileInput(
                                                            [
                                                                'class' => 'form-control text-start drop-file',
                                                                'required' => true
                                                            ]
                                                        )->label(false); ?>
                                                        <span class="drop-title"></span>
                                                        <span class="note needsclick">فایل ویدئو *</span>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="switch switch-square">
                                                <input type="checkbox" class="switch-input" name="download">
                                                <span class="switch-toggle-slider">
                                                    <span class="switch-on"></span>
                                                    <span class="switch-off"></span>
                                                </span>
                                                <span class="switch-label">ویدئو قابلیت دانلود داشته باشد</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">افزودن ویدئو</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $newExam ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن آزمون</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_exam'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '2'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان آزمون *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">آزمون های تعریف شده *</label>
                                                <?php
                                                echo $form->field($model, 'content[content]')->dropDownList(
                                                    $exams,
                                                    [
                                                        'prompt' => 'لطفا آزمون را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'id' => '',
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
                                        <button type="submit" class="btn btn-primary">افزودن آزمون</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $newSurvey ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن نظرسنجی</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_survey'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '3'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان نظرسنجی *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">نظرسنجی های تعریف شده *</label>
                                                <?php
                                                echo $form->field($model, 'content[content]')->dropDownList(
                                                    $surveys,
                                                    [
                                                        'prompt' => 'لطفا نظرسنجی را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'id' => '',
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
                                        <button type="submit" class="btn btn-primary">افزودن نظرسنجی</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $newWorkoutFromUploadCenterId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">انتخاب فایل تمرین از آپلود سنتر</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_workout_from_upload_center'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '4'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان تمرین *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <div class="text-light small fw-semibold mb-3">نیاز به پاسخ تمرین</div>
                                                <label class="switch switch-square">
                                                    <input type="checkbox" class="switch-input" name="reaction">
                                                    <span class="switch-toggle-slider">
                                                        <span class="switch-on"><i class="bx bx-check"></i></span>
                                                        <span class="switch-off"><i class="bx bx-x"></i></span>
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="select2Basic" class="form-label">فایل های آپلود سنتر شما *</label>
                                                <?php
                                                echo $form->field($model, 'content[content]')->dropDownList(
                                                    $uploadCenter,
                                                    [
                                                        'prompt' => 'لطفا فایل را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'id' => '',
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
                                        <button type="submit" class="btn btn-primary">افزودن تمرین</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade ajax-upload" id="<?= $newDirectWorkoutId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">اضافه کردن تمرین</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_direct_workout'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان تمرین *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <div class="text-light small fw-semibold mb-3">نیاز به پاسخ تمرین</div>
                                                    <label class="switch switch-square">
                                                        <input type="checkbox" class="switch-input" name="reaction">
                                                        <span class="switch-toggle-slider">
                                                            <span class="switch-on"><i class="bx bx-check"></i></span>
                                                            <span class="switch-off"><i class="bx bx-x"></i></span>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="card mb-4 relative">
                                                <div class="card-body">
                                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                        <div class="dz-message needsclick">
                                                            <?= $form->field($model, 'content[content]')->fileInput(
                                                                [
                                                                    'class' => 'form-control text-start drop-file',
                                                                    'required' => true
                                                                ]
                                                            )->label(false); ?>
                                                            <span class="drop-title"></span>
                                                            <span class="note needsclick">فایل تمرین</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                                                    0%
                                                </div>
                                            </div>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">افزودن تمرین</button>
                                            <?php ActiveForm::end(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $newFileFromUploadCenter ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">انتخاب فایل از آپلود سنتر</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_file_from_upload_center'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '6'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان فایل *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">فایل های آپلود سنتر شما *</label>
                                                <?php
                                                echo $form->field($model, 'content[content]')->dropDownList(
                                                    $uploadCenter,
                                                    [
                                                        'prompt' => 'لطفا فایل را مشخص کنید',
                                                        'class' => 'select2 form-select',
                                                        'required' => true,
                                                        'data-allow-clear' => true,
                                                        'id' => '',
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
                                        <button type="submit" class="btn btn-primary">افزودن فایل</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade ajax-upload" id="<?= $newFileDirect ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">بارگزاری فایل</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_direct_file'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[type]')->hiddenInput(
                                            [
                                                'value' => '6'
                                            ]
                                        )->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان فایل *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start file-name',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                        </div>
                                        <div class="card mb-4 relative">
                                            <div class="card-body">
                                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                    <div class="dz-message needsclick">
                                                        <?= $form->field($model, 'content[content]')->fileInput(
                                                            [
                                                                'class' => 'form-control text-start drop-file',
                                                                'required' => true
                                                            ]
                                                        )->label(false); ?>
                                                        <span class="drop-title"></span>
                                                        <span class="note needsclick">فایل *</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
                                            0%
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">افزودن فایل</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $newMessage ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن پیغام جدید</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_message'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <?= $form->field($headline, 'content[teacher_id]')->hiddenInput(
                                            [
                                                'value' => $lesson['teachers']
                                            ]
                                        )->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان پیغام *</label>
                                                <?= $form->field($headline, 'content[title]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">متن پیغام *</label>
                                                <?= $form->field($headline, 'content[content]')->textarea(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">افزودن پیغام</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $editTitle ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش عنوان <?= Html::encode($headline->title) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['edit_title'],
                                                "method" => "post",
                                                'options' => [
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($headline, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان *</label>
                                                <?= $form->field($headline, 'title')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false); ?>
                                            </div>
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
                    }
                    ?>
                </ul>
            <?php
            } else {
                echo '<div class="alert alert-warning text-dark" role="alert">برای درس ' . Html::encode($lessonDetail->title) . ' تا کنون محتوایی ثبت نشده است</div>';
            }
            ?>
        </div>
    </div>
</div>




<div class="modal fade" id="create-new-headline" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سرفصل جدید برای درس <?= Html::encode($lessonDetail->title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new_title'],
                        "method" => "post",
                    ]
                ); ?>
                <?= $form->field($model, 'course_id')->hiddenInput(
                    [
                        'value' => (string) $courseDetail->_id,
                    ]
                )->label(false); ?>
                <?= $form->field($model, 'lesson_id')->hiddenInput(
                    [
                        'value' => $lesson['_id'],
                    ]
                )->label(false); ?>
                <div class="row">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان سرفصل *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت سرفصل</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>