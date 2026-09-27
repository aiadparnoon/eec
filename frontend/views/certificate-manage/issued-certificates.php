<?php
$this->title = 'مدارک صادر شده';

use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use frontend\controllers;

Select2Asset::register($this);
$front = Yii::getAlias('@front');
if(Yii::$app->session->has('status'))
{
    if(Yii::$app->session->getFlash('status') == '1')
        $script = <<< JS
    toastr.success("درس مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '2')
        $script = <<< JS
    toastr.warning("خطایی رخ داده، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '3')
        $script = <<< JS
    toastr.warning("عنوان درس وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.warning("درس مورد نظر ویرایش گردید", {
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
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت درخواست های صدور مدرک</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('__search', [
        'model' => $searchModel,
        'colleges' => $colleges,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">مدارک ثبت شده</h5>
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
                        <th>نام دانشپذیر</th>
                        <th>نام کاربری</th>
                        <th>نام دوره</th>
                        <th>شماره سریال</th>
                        <?php if(Yii::$app->user->identity->role == 'user') echo '<th>دانشکده</th>'; ?>
                        <th>چاپ مدرک</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    <?php
                    foreach ($dataProvider->models as $request)
                    {
                        $viewDetail = 'viewDetail'.rand();
                        $addSerial = 'addSerial'.rand();
                        $collegeDetail = DashboardController::college_detail($request->college);
                        $userDetail = $this->context->user_detail($request->username);
                        $courseDetail = $this->context->course_detail($request->course_id);
                        ?>
                        <tr>
                            <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                            <td><?= Html::encode($userDetail->first_name.' '.$userDetail->last_name) ?></td>
                            <td><?= Html::encode($request->username) ?></td>
                            <td><?= Html::encode($courseDetail->title['main_fa']) ?></td>
                            <td><?= Html::encode($request->serial_number) ?></td>
                            <?php if(Yii::$app->user->identity->role == 'user') echo '<td>'.Html::encode($collegeDetail->title).'</td>'; ?>
                            <td>
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['certificate-manage/print-certificate', '_id' => (string) $request->_id]) ?>">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.1213 21.1213C18 20.2426 18 18.8284 18 16L18 12.6595C16.5233 12.1579 14.5419 11.7498 12 11.7498C9.45812 11.7498 7.47667 12.1579 6 12.6595V16C6 18.8284 6 20.2426 6.87868 21.1213C7.75736 22 9.17157 22 12 22C14.8284 22 16.2426 22 17.1213 21.1213Z" fill="#1C274C"/>
                                        <path d="M17.1209 2.87868C16.2422 2 14.828 2 11.9995 2C9.17112 2 7.75691 2 6.87823 2.87868C6.38586 3.37105 6.16939 4.03157 6.07422 5.01484C6.63346 4.99996 7.25161 4.99998 7.92921 5H16.0704C16.7478 4.99998 17.3658 4.99996 17.9249 5.01483C17.8297 4.03156 17.6132 3.37105 17.1209 2.87868Z" fill="#1C274C"/>
                                        <path opacity="0.5" d="M16 6H8C5.17157 6 3.75736 6 2.87868 6.87868C2 7.75736 2 9.17157 2 12C2 14.8284 2 16.2426 2.87868 17.1213C3.37105 17.6137 4.03157 17.8302 5.01484 17.9253C4.99996 17.3662 4.99998 16.7481 5 16.0706L5 13.0424C4.93434 13.0706 4.87007 13.0988 4.8072 13.1271C4.42933 13.2967 3.98546 13.1279 3.8158 12.7501C3.64614 12.3722 3.81493 11.9283 4.1928 11.7587C5.91455 10.9856 8.4805 10.2498 12 10.2498C15.5195 10.2498 18.0854 10.9856 19.8072 11.7587C20.1851 11.9283 20.3539 12.3722 20.1842 12.7501C20.0145 13.1279 19.5707 13.2967 19.1928 13.1271C19.1299 13.0988 19.0657 13.0706 19 13.0424L19 16.0706C19 16.748 19 17.3662 18.9852 17.9253C19.9684 17.8302 20.629 17.6137 21.1213 17.1213C22 16.2426 22 14.8284 22 12C22 9.17157 22 7.75736 21.1213 6.87868C20.2426 6 18.8284 6 16 6Z" fill="#1C274C"/>
                                    </svg>
                                </a>
                            </td>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewDetail ?>">مشاهده اطلاعات کامل </a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $addSerial ?>">ثبت مجدد سریال </a>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $viewDetail ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده مشخصات <?= Html::encode($userDetail->first_name.' '.$userDetail->last_name) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام فارسی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['first_name_fa']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام خانوادگی فارسی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['last_name_fa']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام انگلیسی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['first_name_en']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام خانوادگی انگلیسی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['last_name_en']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">تلفن ثابت</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['phone']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">کد ملی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['id']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">تاریخ تولد</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['birth_day']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">نام پدر</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['father_name']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">شغل</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['job']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">رشته تحصیلی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['field']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">ایمیل</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['email']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">استان</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['province']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">شهرستان</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['city']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">کدپستی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['zip_code']) ?></p>
                                            </div>
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">آدرس</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['address']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">دانلود کارت ملی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['id_file']) ?></p>
                                            </div>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="nameWithTitle" class="form-label">دانلود آخرین مدرک تحصیلی</label>
                                                <p><?= Html::encode($userDetail->issuance_certificate_information['degree_education_file']) ?></p>
                                            </div>
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
                        <div class="modal fade" id="<?= $addSerial ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سریال برای درخواست صدور مدرک <?= Html::encode($userDetail->first_name.' '.$userDetail->last_name) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['add_serial'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($request, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">شماره سریال *</label>
                                                <?= $form->field($request, 'serial_number')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا شماره سریال را وارد کنید\')',
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
                                        <button type="submit" class="btn btn-primary">ثبت سریال</button>
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
                <?php
            } else {
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">تا کنون درخواستی ثبت نشده است</div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
