<?php


use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\CoursesContents;
use yii\widgets\ListView;

$this->title = 'مدیریت دوره ' . Html::encode($courseDetail->title['main_fa']);
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
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$tab = <<< JS
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get('tab');

    if (tabValue) {
        $("#" + tabValue + " > button").click();
    } else {
        $(".course-tab:first > button").click();
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

    button.active {
        background-color: #5a8dee !important;
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
                <a href="javascript:void(0);">مدیریت محتوای دوره</a>
            </li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">مدیریت دوره <?= $courseDetail->title['main_fa'] ?></h5>
        </div>
        <div class="card mb-4">
            <div class="nav-align-left">
                <ul class="nav nav-pills border-end p-4" role="tablist">
                    <?php
                    if ($lessons != null) {
                        $i = 0;
                        $activeClass = 'active';
                        $selected = 'true';
                        foreach ($lessons as $lesson) {
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                            $id = 'id' . $i;
                            if ($i++ != 0) {
                                $activeClass = '';
                                $selected = 'false';
                            }
                    ?>
                            <li class="nav-item course-tab" id="tab-<?= $id ?>">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#<?= $id ?>" aria-controls="<?= $id ?>" aria-selected="<?= $selected ?>">
                                    <?= Html::encode($lessonDetail->title) ?>
                                </button>
                            </li>
                    <?php
                        }
                    }
                    ?>
                </ul>
                <div class="tab-content shadow-none">
                    <?php
                    if ($lessons != null) {
                        $j = 0;
                        $activeClass = 'show active';
                        foreach ($lessons as $lesson) {
                            $id = 'id' . $j;
                            if ($j++ != 0)
                                $activeClass = '';
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                            $lessonsContents = $this->context->lesson_contents((string) $courseDetail->_id, (string) $lesson['_id']);
                            $createTitleModal = 'create_title_modal' . rand();
                    ?>
                            <div class="tab-pane fade" id="<?= $id ?>" role="tabpanel">
                                <?php
                                if(array_key_exists('meeting', $lesson))
                                {
                                    ?>
                                    <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/go-to-class','courseUrl'=>$lesson['meeting']['url']]) ?>" target="_blank" class="btn btn-label-linkedin btn-block">
                                        ورود به کلاس
                                    </a>
                                    <?php
                                }
                                ?>
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <span class="card-title m-0 me-2">مدیریت محتوای درس <?= Html::encode($lessonDetail->title) ?></span>
                                    <button class="btn btn-dark badge bg-dark" data-bs-toggle="modal" data-bs-target="#<?= $createTitleModal ?>">افزودن سرفصل جدید</button>
                                </div>
                                <?php
                                if ($lessonsContents != null) {
                                ?>
                                    <div class="card-body">
                                        <ul class="timeline timeline-dashed mt-4">
                                            <?php
                                            foreach ($lessonsContents as $headline)
                                            {
                                                $newVideoFromUploadCenter = new CoursesContents();
                                                $newVideoFromUploadCenterId = 'upload_center' . rand();
                                                $newVideoDirectId = 'new_upload_center' . rand();
                                                $newExam = 'new_exam' . rand();
                                                $newWorkoutFromUploadCenterId = 'new_workout_from_upload_center' . rand();
                                                $newDirectWorkoutId = 'new_direct_workout' . rand();
                                                $newFileFromUploadCenter = 'new_file_from_upload_center' . rand();
                                                $newFileDirect = 'new_file_direcr' . rand();
                                                $newMessage = 'new_message' . rand();
                                                $editTitle = 'editTitle' . rand();
                                            ?>
                                                <li class="timeline-item timeline-item-success mb-4">
                                                    <span class="timeline-indicator timeline-indicator-primary">
                                                        <i class="fa-solid fa-circle-exclamation"></i>
                                                    </span>
                                                    <div class="timeline-event">
                                                        <div class="timeline-header border-bottom mb-3 mt-n1">
                                                            <h6 class="mb-1 mb-sm-2"><?= Html::encode($headline->title) ?></h6>
                                                            <a href="javascript:void(0);" class="badge bg-label-dark" data-bs-toggle="modal" data-bs-target="#<?= $editTitle ?>">ویرایش عنوان</a>
                                                        </div>
                                                        <div class="d-flex justify-content-between flex-wrap mb-2 lh-1-85">
                                                            <div class="demo-inline-spacing">
                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        ویدئو
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newVideoFromUploadCenterId ?>">انتخاب از آپلودسنتر</a></li>
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newVideoDirectId ?>">بارگزاری ویدئو جدید</a></li>
                                                                    </ul>
                                                                </div>

                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-success" data-bs-toggle="modal" data-bs-target="#<?= $newExam ?>">
                                                                        آزمون
                                                                    </button>
                                                                </div>

                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        نظرسنجی
                                                                    </button>
                                                                </div>

                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        تمرین
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newWorkoutFromUploadCenterId ?>">انتخاب فایل از آپلودسنتر</a></li>
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newDirectWorkoutId ?>">بارگزاری فایل تمرین جدید</a></li>
                                                                    </ul>
                                                                </div>

                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        فایل
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newFileFromUploadCenter ?>">انتخاب فایل از آپلودسنتر</a></li>
                                                                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newFileDirect ?>">بارگزاری فایل جدید</a></li>
                                                                    </ul>
                                                                </div>

                                                                <div class="btn-group">
                                                                    <button type="button" class="btn btn-label-success" data-bs-toggle="modal" data-bs-target="#<?= $newMessage ?>">
                                                                        پیغام
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="divider">
                                                            <div class="divider-text">محتوای ثبت شده برای <?= Html::encode($headline->title) ?></div>
                                                        </div>
                                                        <?php
                                                        if ($headline->content != null) {
                                                            $counter = 1;
                                                            $row = 0;
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
                                                        ?>
                                                                <div class="d-flex justify-content-between flex-wrap mb-2 lh-1-85">
                                                                    <div>
                                                                        <span><?= $counter++ ?></span>
                                                                        <i class="bx bx-right-arrow-alt scaleX-n1-rtl mx-3"></i>
                                                                        <span>(<?= $type ?>)</span>
                                                                        <span><?= Html::encode($value['title']) ?></span>
                                                                    </div>
                                                                    <div>
                                                                        <span><?= jdate('Y/m/d', $value['date']) ?></span>
                                                                        <button type="button" data-bs-toggle="modal" data-bs-target="#<?= $deleteEvent ?>" class="mt-1 btn btn-icon btn-label-danger">
                                                                            <i class="bx bx-trash-alt"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
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
                                                                                <input type="hidden" name="row" value="<?= $row++ ?>">
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
                                                            }
                                                        }
                                                        ?>
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
                                                <div class="modal fade" id="<?= $newVideoDirectId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
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
                                                                                'class' => 'form-control text-start',
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
                                                                                'prompt' => 'لطفا دانشکده را مشخص کنید',
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
                                                <div class="modal fade" id="<?= $newDirectWorkoutId ?>" tabindex="-1" style="display: none;" aria-hidden="true">
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
                                                <div class="modal fade" id="<?= $newFileDirect ?>" tabindex="-1" style="display: none;" aria-hidden="true">
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
                                                                                'class' => 'form-control text-start',
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
                                    </div>
                                <?php
                                } else {
                                    echo '<div class="alert alert-warning text-dark" role="alert">برای درس ' . Html::encode($lessonDetail->title) . ' تا کنون محتوایی ثبت نشده است</div>';
                                }
                                ?>
                            </div>
                            <div class="modal fade" id="<?= $createTitleModal ?>" tabindex="-1" style="display: none;" aria-hidden="true">
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
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت آزمون جدید</h5>
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
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان آزمون *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان آزمون را وارد کنید\')',
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
                <button type="submit" class="btn btn-success">ثبت ازمون و مدیریت سوالات</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>