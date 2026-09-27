<?php
$this->title = 'مدیریت کاربران';
use app\models\Users;
use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;
use app\models\UploadedExcels;
$front = Yii::getAlias('@front');
 ?>

<?php
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("دانشپذیر مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.danger("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.danger("نام کاربری وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("کاربران موجود در لیست اضافه گردیدند", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور استاد مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت کاربر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.warning("در برقراری با وب سرویس ادوبی کانکت خطایی رخ داده، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.success("مشخصات دانشپذیر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.error("ارتباط با سرور Adobe برقرار نشد، تغییرات ذخیره نشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("وضعیت دانشپذیر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.success("رمز عبور مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '12')
        $script = <<< JS
    toastr.error("دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$excelUrl = Yii::$app->urlManager->createAbsoluteUrl('users-manage/check_excel_file','https');
$excel = <<<JS
$(document).on('click','#excel',function(e) {
    e.preventDefault();
    var id = $('#packageId').val();
    var fd = new FormData();
    fd.append('file',$('#excel-file')[0].files[0]);
    fd.append('_csrf', yii.getCsrfToken());

    console.log(fd)
    $.ajax({
        url:'$excelUrl',
        type:'POST',
        processData: false,  // Don't process the data
        contentType: false,  // Don't set content type
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-CSRF-Token', yii.getCsrfToken());
        },
        data:fd,
        success:function(data) {
            var main_data = JSON.parse(data);
            $('#message').html(main_data.message);  
            $('#final-ok').html(main_data.footer);  
        }
    });
});
JS;
$this->registerJs($excel);
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

    .summary
    {
        display: none;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت دانشپذیران</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"> کاربران</h5>
            <div class="btn-group" style="margin-right: 100px;" role="group" aria-label="Button group with nested dropdown">
                <div class="btn-group" role="group">
                    <button id="btnGroupDrop1" type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        افزودن عضو
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-user">افزودن با مشخصات</a>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#from-excel">افزودن از فایل اکسل</a>
                    </div>
                </div>
            </div>
        </div>
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
                foreach ($dataProvider->models as $member)
                {
                    $edit = 'edit' . rand();
                    $viewCourses = 'viewCourses' . rand();
                    $changeStatus = 'changeStatus' . rand();
                    $editUser = 'editUser' . rand();
                    $changePassword = 'changePassword' . rand();
                    $userRole = 'دانشپذیر';
                    if ($member->role == 'mentor')
                        $userRole = 'دستیار استاد';
                    $memberStatus = 'خطا در ادوبی';
                    $statusClass = 'bg-label-success';
                    $status = 'فعال';
                    if($member->status == 9)
                    {
                        $status = 'غیر فعال';
                        $statusClass = 'bg-label-danger';
                    }
                    $registrant = 'نامشخص';
                    $role = '';
                    if ($member->registrant != null)
                    {
                        if($member->registrant == $member->username)
                            $registrant = 'دانشپذیر';
                        else
                        {
                            $registrantDetail = DashboardController::registrant_detail($member->registrant);
                            if($registrantDetail != null)
                            {
                                if($registrantDetail->role == 'user')
                                    $role = '';
                                if ($registrantDetail->role == 'emp')
                                    $role = 'کارشناس دانشکده';
                                else if ($registrantDetail->role == 'broker')
                                    $role = 'کارگزار';
                                $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <th scope="row"><?= $dataProvider->pagination->page * 50 + $i++ ?></th>
                        <td><?= Html::encode($member->first_name) ?></td>
                        <td><?= Html::encode($member->last_name) ?></td>
                        <td><?= Html::encode($member->username) ?></td>
                        <td>
                            <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $role ?>">
                                <?= Html::encode($registrant) ?>
                            </button>
                        </td>
                        <td><span class="badge <?= $statusClass ?>"><?= Html::encode($status) ?></span></td>
                        <td><?= Html::encode($userRole) ?></td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions">
                                <?php
                                if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'cnt')
                                {
                                    ?>
                                    <a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">تغییر وضعیت</a>
                                    <a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#<?= $viewCourses ?>">مشاهده دوره ها</a>
                                    <a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#<?= $editUser ?>">ویرایش مشخصات</a>
                                    <a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#<?= $changePassword ?>">تغییر رمز عبور</a>
                                    <?php
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $changeStatus ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
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
                                    <p>وضعیت <?= Html::encode($member->first_name.' '.$member->last_name) ?> در حال حاضر  (<?= $status ?>) می باشد</p>
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
                    <div class="modal fade" id="<?= $viewCourses ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده دوره های <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="card-body">
                                        <?php
                                        if($member->courses != null)
                                        {
                                            if(count($member->courses) != 0)
                                            {
                                                $userCourseRow = 0;
                                                foreach ($member->courses as $value)
                                                {
                                                    $courseDetail = $this->context->course_detail($value['_id']);
                                                    $courseCategory = 'دوره تک درس';
                                                    $courseStatus = 'خطا در ادوبی';
                                                    $courseStatusBg = 'danger';
                                                    $courseStatusText = 'درخواست تصحیح وضعیت در ادوبی';
                                                    if($value['status'] == '1')
                                                    {
                                                        $courseStatus = 'فعال';
                                                        $courseStatusBg = 'success';
                                                        $courseStatusText = 'غیر فعال کردن';
                                                    }
                                                    else if($value['status'] == '2')
                                                    {
                                                        $courseStatus = 'غیرفعال';
                                                        $courseStatusBg = 'warning';
                                                        $courseStatusText = 'فعال کردن';
                                                    }
                                                    if($courseDetail != null)
                                                    {
                                                        if($courseDetail->type == '2')
                                                            $courseCategory = 'دوره جامع';
                                                        else if($courseDetail->type == '3')
                                                            $courseCategory = 'دوره یکساله';
                                                    }
                                                    if($courseDetail != null)
                                                    {
                                                        $courseRegistrant = 'نامشخص';
                                                        if(array_key_exists('registrant', $value))
                                                        {
                                                            if ($value['registrant'] != null)
                                                            {
                                                                $CourseRegistrantRole = '';
                                                                if($value['registrant'] == $member->username)
                                                                    $courseRegistrant = 'دانشپذیر';
                                                                else
                                                                {
                                                                    $CourseRegistrantDetail = DashboardController::registrant_detail($value['registrant']);
                                                                    if($CourseRegistrantDetail != null)
                                                                    {
                                                                        if ($CourseRegistrantDetail->role == 'user')
                                                                            $CourseRegistrantRole = 'ادمین';
                                                                        else if ($CourseRegistrantDetail->role == 'emp')
                                                                            $CourseRegistrantRole = 'کارشناس دانشکده';
                                                                        else if ($CourseRegistrantDetail->role == 'broker')
                                                                            $CourseRegistrantRole = 'کارگزار';
                                                                        $courseRegistrant = $CourseRegistrantDetail->first_name . ' ' . $CourseRegistrantDetail->last_name.' ('.$CourseRegistrantRole.')';
                                                                    }
                                                                    else
                                                                        $courseRegistrant = 'نامشخص';
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <div class="added-cards">
                                                            <div class="cardMaster border p-3 rounded mb-3">
                                                                <div class="d-flex justify-content-between flex-sm-row flex-column">
                                                                    <div class="card-information">
                                                                        <?php
                                                                        if($courseDetail->type == '1')
                                                                            echo '<div class="avatar avatar-md me-2"><img class="rounded-circle" src="'.$front . '/lesson_images/' . $courseDetail->preview_image.'"></div>';
                                                                        else
                                                                            echo '<div class="avatar avatar-md me-2"><img class="rounded-circle" src="'.$front . '/package_images/' . $courseDetail->preview_image.'"></div>';
                                                                        ?>
                                                                        <div class="d-flex align-items-center mb-1">
                                                                            <h6 class="mb-0 me-3"><?= Html::encode($courseDetail->title['main_fa']) ?></h6>
                                                                            <span class="badge bg-label-primary me-1"><?= Html::encode($courseCategory) ?></span>
                                                                        </div>
                                                                        <small class="mt-sm-auto mt-2 order-sm-1 order-0"><?= 'ثبت کننده '.Html::encode($courseRegistrant) ?></small>
                                                                    </div>
                                                                    <div class="d-flex flex-column text-start text-lg-end">
                                                                        <div class="d-flex order-sm-0 order-1 mt-3">
                                                                            <?php $form = ActiveForm::begin(
                                                                                [
//                                                                                    'action' => ['change_user_course_status'],
                                                                                    'action' => ['packages/change_status'],
                                                                                    "method" => "post",
                                                                                    'options' => [
                                                                                        'class' => '',
                                                                                        'enctype' => 'multipart/form-data'
                                                                                    ],
                                                                                ]
                                                                            ); ?>
                                                                            <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                                                            <input type="hidden" name="courseId" value="<?= $value['_id'] ?>">
                                                                            <input type="hidden" name="row" value="<?= $userCourseRow ?>">
                                                                            <button class="btn btn-label-primary me-3" data-bs-toggle="modal" data-bs-target="#editCCModal">
                                                                                <?= Html::encode($courseStatusText) ?>
                                                                            </button>
                                                                            <?php ActiveForm::end(); ?>
                                                                        </div>
                                                                        <span class="badge bg-label-<?= $courseStatusBg ?> mt-sm-auto mt-2 order-sm-1 order-0">وضعیت کاربر در این دوره <?= Html::encode($courseStatus) ?> می باشد</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    else
                                                        echo Html::encode($value['_id']).'<br>';
                                                    $userCourseRow++;
                                                }
                                            }
                                            else
                                                echo '<div class="alert alert-danger" role="alert">دانشپذیر مورد نظر فاقد دوره می باشد</div>';
                                        }
                                        else
                                            echo '<div class="alert alert-danger" role="alert">دانشپذیر مورد نظر فاقد دوره می باشد</div>';
                                        ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $editUser ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش مشخصات <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">

                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['edit_user'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">نام *</label>
                                            <?= $form->field($member, 'first_name')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">نام خانوادگی *</label>
                                            <?=
                                            $form->field($member, 'last_name')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
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
                                    <button type="submit" class="btn btn-primary">ویرایش دانشپذیر</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $changePassword ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر رمز عبور  <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">

                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['change_password'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">رمز عبور جدید *</label>
                                            <?= $form->field($member, 'password_hash')->passwordInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'value' => '',
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
                                    <button type="submit" class="btn btn-primary">تغییر رمز عبور</button>
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
</div>


<div class="modal fade" id="new-user" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت عضو جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $newUser = new Users(); ?>
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new_user'],
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
                        <?= $form->field($newUser, 'first_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">نام خانوادگی *</label>
                        <?=
                        $form->field($newUser, 'last_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">نام کاربری * (شماره همراه یا ایمیل)</label>
                        <?= $form->field($newUser, 'username')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode >= 64 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">رمز عبور * </label>
                        <?= $form->field($newUser, 'password_hash')->textInput(
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
                <button type="submit" class="btn btn-primary">ثبت عضو</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="from-excel" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت اعضا از فایل اکسل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $excelModel = new UploadedExcels(); ?>
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add_user_from_excel'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($excelModel, 'file')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        'required' => true,
                                        'id' => 'excel-file'
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">فایل اکسل *</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="excel">بررسی فایل</button>
                <?php ActiveForm::end(); ?>
                <hr>
                <div id="message"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>

            </div>
        </div>
    </div>
</div>