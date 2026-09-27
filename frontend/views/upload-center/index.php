<?php
$this->title = 'مدیریت اپلود سنتر';

use frontend\controllers\DashboardController;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\UploadCenter;
use frontend\controllers;
use yii\widgets\ListView;

require_once(Yii::$app->basePath . '/web/jdf.php');
Select2Asset::register($this);
$model = new UploadCenter();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->getFlash('status') == '1')
        $script = <<< JS
    toastr.success("فایل مورد نظر بارگزاری گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '2')
        $script = <<< JS
    toastr.danger("خطایی رخ داده، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '3')
        $script = <<< JS
    toastr.danger("عنوان فایل وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.success("عنوان فایل ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '5')
        $script = <<< JS
    toastr.success(" فایل مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
$username = Yii::$app->user->identity->username;

$upload = <<< JS
let isUploading = false;



$(window).on('beforeunload', function() {
    if(isUploading) return 'آپلود فایل لغو خواهد شد آیا مطمئن هستید؟';
});

$(".upload-center-form").submit(function (e) {
        e.preventDefault();
        const fileData = document.getElementById("selected-file").files[0];
        const formData = new FormData();
        formData.append('file', fileData);
        formData.append('title', $("#file-name").val());
        formData.append('registrant', "{$username}");

        $("#upload-file").attr("disabled", true);

    let startTime; 
    let totalUploadedSize = 0;
    const config = {
        onUploadProgress: function (progressEvent) {
            console.log('here', progressEvent)
            if (true) {
            const currentTime = new Date().getTime();
            const elapsedTimeInSeconds = (currentTime - startTime) / 1000;

            let percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
            percentComplete = parseInt(percentComplete);

            document.getElementById('upload-status').innerText = percentComplete === 0 ? 'درحال پردازش فایل...' : 'درحال آپلود فایل...';
            document.querySelector('.progress-bar').style.width = percentComplete + '%';
            document.querySelector('.progress-bar').innerText = percentComplete + '%';

            if (percentComplete > 0) {
                document.querySelector('.progress-bar').style.minWidth = '50px';
            }

            if (percentComplete === 100) {
                document.querySelector('.progress-bar').style.width = '99%';
                document.querySelector('.progress-bar').innerText = '99%';
                document.getElementById('upload-status').innerText = 'درحال نهایی سازی...';
            }

            const uploadSpeed = progressEvent.loaded / elapsedTimeInSeconds / (1024 * 1024); // Speed in MB per second
            totalUploadedSize = progressEvent.loaded / (1024 * 1024); // Accumulate the uploaded size

            $("#upload-rate").text(uploadSpeed.toFixed(2) + " MB - (" + totalUploadedSize.toFixed(2) + " MB)")
          }
        },
    };

    startTime = new Date().getTime();

    axios.post('https://api-eec.ut.ac.ir/adobe-connect/upload-center', formData, config)
        .then((response) => {
            toastr.success('فایل با موفقیت آپلود شد', {
                positionClass: 'toast-top-center',
                containerId: 'toast-top-center',
                closeButton: 'true',
            });

            document.querySelector('.progress-bar').style.width = '100%';
            document.querySelector('.progress-bar').innerText = '100%';
            document.getElementById('upload-status').innerText = '';
            isUploading = false;

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch((error) => {
            toastr.error('آپلود با خطا موجه شد', {
                positionClass: 'toast-top-center',
                containerId: 'toast-top-center',
                closeButton: 'true',
            });

            document.getElementById('upload-status').innerText = 'آپلود فایل با خطا مواجه شد!';
            document.getElementById('upload-status').style.color = 'red';
            isUploading = false;
        });

        return;
        $.ajax({
        xhr: function() {
            const xhr = new window.XMLHttpRequest();

            xhr.upload.addEventListener("progress", function(evt) {
            if (evt.lengthComputable) {
                isUploading = true;
                let percentComplete = evt.loaded / evt.total;
                percentComplete = parseInt(percentComplete * 100);
                console.log(percentComplete);

                $("#upload-status").text(percentComplete == 0 ? "درحال پردازش فایل..." : "درحال آپلود فایل...");
                $(".progress-bar").css('width', percentComplete + "%")
                $(".progress-bar").text(percentComplete + "%")

                if(percentComplete > 0) {
                    $(".progress-bar").css('min-width', '50px');
                }

                if (percentComplete === 100) {
                    $(".progress-bar").css('width', 99 + "%");
                    $(".progress-bar").text(99 + "%");
                    $("#upload-status").text("درحال نهایی سازی...");
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
        error: function (request, status, error) {
            toastr.error("آپلود با خطا موجه شد", {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });

            $("#upload-status").text("آپلود فایل با خطا مواجه شد!");
            $("#upload-status").css("color", "red");
            isUploading = false;
        },
        success: function(result) {
            toastr.success("فایل با موفقیت آپلود شد", {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });

            $(".progress-bar").css('width', 100 + "%");
            $(".progress-bar").text(100 + "%");
            $("#upload-status").text("");
            isUploading = false;
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });

});
JS;

$this->registerJs($upload);


$url = Yii::$app->urlManager->createAbsoluteUrl('upload-center/show_file_uses', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_file_uses = <<<JS
$(document).on('click','.show-file-uses',function(e) {
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
$this->registerJs($show_file_uses);

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

    #upload-status {
        display: block;
        margin-top: 8px;
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
                <a href="javascript:void(0);">سایر موراد</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">آپلود سنتر</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">فایل های بارگزاری شده</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت فایل جدید
            </button>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            if ($dataProvider->models != null) {
            ?>
                <table class="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th>عنوان فایل</th>
                            <?php if (Yii::$app->user->identity->role == 'user') echo ' <th>ثبت کننده</th>'; ?>
                            <th>تاریخ بارگزاری</th>
                            <th>حجم</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php
                        foreach ($dataProvider->models as $file) {
                            $edit = 'edit' . rand();
                            $ext = explode('.', $file->file);
                            $registrant = 'نامشخص';
                            $role = '';
                            if ($file->registrant == Yii::getAlias('@adminUsername'))
                                $registrant = 'ادمین اصلی';
                            else {
                                $registrantDetail = DashboardController::registrant_detail($file->registrant);
                                $role = '';
                                if ($registrantDetail->role == 'emp')
                                    $role = 'کارشناس دانشکده';
                                else if ($registrantDetail->role == 'broker')
                                    $role = 'کارگزار';
                                else if ($registrantDetail->role == 'teacher')
                                    $role = 'استاد';
                                $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name;
                            }
                        ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                                <td class="text-wrap w-25"><?= $file->title . ' (' . $ext[1] . ')' ?></td>
                                <?php
                                if (Yii::$app->user->identity->role == 'user') {
                                ?>
                                    <td>
                                        <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $role ?>">
                                            <?= $registrant ?>
                                        </button>
                                    </td>
                                <?php
                                }
                                ?>
                                <td><?= jdate('H:i - Y/m/d', hexdec(substr($file->_id, 0, 8))) ?></td>
                                <td dir="ltr"><?= $this->context->filesize_formatted('../../frontend/web/upload_center/' . $file->file) ?></td>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش </a>
                                        <a class="dropdown-item show-file-uses" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete" id="<?php echo (string) $file->_id; ?>">حذف فایل</a>
                                        <a class="dropdown-item" download="true" href="<?= Yii::$app->urlManager->createUrl(['upload-center/file','filename' => $file->file])  ?>">دانلود فایل</a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش عنوان فایل <?= $file->title ?></h5>
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
                                            <?= $form->field($file, '_id')->hiddenInput()->label(false); ?>
                                            <div class="row">
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <label for="nameWithTitle" class="form-label">عنوان فایل *</label>
                                                    <?= $form->field($file, 'title')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فایل را وارد کنید\')',
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
                                            <button type="submit" class="btn btn-primary">ویرایش عنوان</button>
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
            <?php
            } else {
            ?>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">تا کنون فایلی ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">بارگزاری فایل جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => 'upload-center-form',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان فایل *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                "id" => "file-name",
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="alert alert-danger h5" role="alert">لطفا تا اتمام آپلود فایل و مشاهده پیغام "فایل مورد نظر بارگزاری گردید" صفحه را رفرش نکنید</div>
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'file')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        "id" => "selected-file",
                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">فایل *</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    0%
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <span id="upload-status"></span>
                    <span id="upload-rate" dir="ltr"></span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary" id="upload-file">بارگزاری فایل</button>
                <?php ActiveForm::end(); ?>
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