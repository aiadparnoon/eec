<?php
$this->title = 'مدیریت صدور مدرک';

use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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
if (Yii::$app->user->identity->role != 'user') {
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


$serialNumberCheck = <<< JS
// رویداد کلیک روی دکمه جستجو
$('#check-serial-number-btn').on('click', function() {
    checkSerialNumber();
});

// رویداد فشار دکمه Enter در فیلد شماره سریال
$('#serial-number').on('keypress', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        checkSerialNumber();
    }
});

function checkSerialNumber() {
    var serialNumber = $('#serial-number').val().trim();
    var resultDiv = $('#serial-check-result');
    
    if (!serialNumber) {
        resultDiv.html('<div class="alert alert-warning">لطفا شماره سریال را وارد کنید</div>');
        return;
    }
    
    // نمایش لودینگ
    resultDiv.html('<div class="text-info"><i class="fa-solid fa-spinner fa-spin"></i> در حال بررسی...</div>');
    
    $.ajax({
        url: '/certificate-manage/check-serial-number', // آدرس کنترلر
        type: 'POST',
        data: {
            serial_number: serialNumber,
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                if (response.exists) {
                    var message = '<div class="alert alert-success mt-6">';
                    message += '<i class="fa-solid fa-circle-check"></i> ';
                    message += 'این شماره سریال مربوط به مدرک صادر شده برای <strong>';
                    message += response.userInfo.first_name + ' ' + response.userInfo.last_name;
                    message += '</strong> می‌باشد<br>';
                    
                    if (response.userInfo.course_title) {
                        message += 'عنوان دوره: ' + response.userInfo.course_title + '<br>';
                    }
                    
                    // دکمه مشاهده
                    if (response.userInfo.course_id) {
                        message += '<a href="/certificate-manage/course-members?_id=' + response.userInfo.course_id + '" class="btn btn-info btn-sm mt-2">مشاهده دوره</a>';
                    }
                    
                    message += '</div>';
                    resultDiv.html(message);
                } else {
                    resultDiv.html('<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> شماره سریال وارد شده اشتباه می‌باشد</div>');
                }
            } else {
                resultDiv.html('<div class="alert alert-danger">خطا در بررسی شماره سریال</div>');
            }
        },
        error: function() {
            resultDiv.html('<div class="alert alert-danger">خطا در ارتباط با سرور</div>');
        }
    });
}

// پاک کردن نتیجه وقتی کاربر تایپ می‌کند
$('#serial-number').on('input', function() {
    $('#serial-check-result').empty();
});
JS;
$this->registerJs($serialNumberCheck);

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
                <a href="javascript:void(0);">مدیریت صدور مدرک</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">دوره های اتمام یافته</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"> دوره های اتمام یافته</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>تصویر</th>
                    <th>عنوان دوره</th>
                    <?php if (Yii::$app->user->identity->role == 'user') echo '<th>دانشکده</th>'; ?>
                    <th>کد مجوز</th>
                    <th>طول دوره</th>
                    <th>تاریخ شروع</th>
                    <th>تاریخ اتمام</th>
                    <th>ویرایش</th>
                    <th>مشاهده</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                $i = 1;
                foreach ($dataProvider->models as $course) {
                    $viewRejectionReason = 'viewRejectionReason' . rand();
                    $changeStatus = 'changeStatus' . rand();
                    $collegeDetail = DashboardController::college_detail($course->college);
                    $status = 'نامشخص';
                    $licenseCode = '-';
                    $url = 'packages/edit-package';
                    if($course->type == '1')
                        $url = 'courses/edit-course';
                    ?>
                    <tr>
                        <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                        <td>
                            <div class="avatar avatar-sm me-2">
                                <?php
                                if($course->type == '1')
                                {
                                    $image = 'default_lesson.png';
                                    if($course->lessons != null)
                                    {
                                        $lessonDetail = $this->context->lesson_detail($course->lessons[0]['_id']);
                                        $image = $lessonDetail->imagePreview;
                                    }
                                    ?>
                                    <img src="<?= $front . '/lesson_images/' . $image ?>" alt="" class="rounded-circle">
                                        <?php
                                }
                                else
                                {
                                    ?>
                                    <img src="<?= $front . '/package_images/' . $course->preview_image ?>" alt="" class="rounded-circle">
                                <?php
                                }
                                ?>
                            </div>
                        </td>
                        <td class="text-wrap w-25"><?= Html::encode($course->title['main_fa']) ?></td>
                        <?php
                        if (Yii::$app->user->identity->role == 'user')
                        {
                            if($collegeDetail != null)
                            {
                                if (strlen($collegeDetail->title) <= 30)
                                    echo '<td>' . Html::encode($collegeDetail->title) . '</td>';
                                else
                                {
                                    ?>
                                    <td>
                                        <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $collegeDetail->title ?>">
                                            <?= Html::encode(substr($collegeDetail->title, 0, 27)) . '...' ?>
                                        </button>
                                    </td>
                                    <?php
                                }
                            }
                            else
                                echo '<td>-</td>';
                        }
                        ?>
                        <td class="text-wrap w-25"><?= Html::encode($course->license_code) ?></td>
                        <td class="text-wrap w-25"><?= Html::encode($course->duration) ?></td>
                        <td class="text-wrap w-25">
                            <?php
                            {
                                if($course->lessons != null)
                                {
                                    if($course->type == '1')
                                    {
                                        if($course->lessons != null)
                                        {
                                            if(array_key_exists('date', $course->lessons))
                                            {
                                                if(array_key_exists('from',$course->lessons[0]['date']))
                                                {
                                                    echo Html::encode($course->lessons[0]['date']['from']);
                                                }
                                                else
                                                    echo '-';
                                            }
                                            else
                                                echo '-';
                                        }
                                        else
                                            echo '-';
                                    }
                                    else
                                        echo $course->date['from'];
                                }
                                else
                                    echo '-';
                            }
                            ?>
                        </td>
                        <td class="text-wrap w-25">
                            <?php
                            if($course->lessons != null)
                            {
                                if($course->type == '1')
                                {
                                    if($course->lessons != null)
                                    {
                                        if(array_key_exists('date', $course->lessons))
                                        {
                                            if(array_key_exists('to', $course->lessons[0]['date']))
                                                echo Html::encode($course->lessons[0]['date']['to']);
                                            else
                                                echo '-';
                                        }
                                        else
                                            echo '-';
                                    }
                                    else
                                        echo '-';
                                }
                                else
                                    {
                                        if(is_array($course->date))
                                            {
                                                if(array_key_exists('to', $course->date,))
                                                    echo $course->date['to'];
                                                else
                                                    echo '-';
                                            }
                                        else
                                            echo '-';
                                    }
                            }
                            else
                                echo '-';
                            ?>
                        </td>
                        <td>
                            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl([$url, '_id' => (string) $course->_id]) ?>">ویرایش</a>
                        </td>
                        <td>
                            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['certificate-manage/course-members', '_id' => (string) $course->_id]) ?>" class="badge bg-info">مشاهده</a>
                        </td>
                    </tr>
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