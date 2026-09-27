<?php
$this->title = 'مدیریت کارگزاران';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Brokers;
use yii\widgets\ListView;
date_default_timezone_set("Asia/Tehran");
require_once(Yii::$app->basePath . '/web/jdf.php');
Select2Asset::register($this);
$model = new Brokers();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("کارگزار مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.error("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.error("شماره همراه وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("کارگزار مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور کارگزار مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت کارگزار مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.success("قرارداد کارگزار مرود نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.success("قرارداد کارگزار مرود نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.success("دسترسی کارگزار مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.error ("حجم فایل های بارگزاری شده نباید بیشتر از ۴۰ مگابایت باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.error("امکان تغییر برای این کارگزار امکانپذیر نمی باشد", {
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

    .summary
    {
        display: none;
    }

    .copy-link-btn {
        cursor: pointer;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت کارگزاران</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">کارگزاران ثبت شده</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    ایجاد کارگزار
                </button>
                <ul class="dropdown-menu" style="">
                    <li><a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-brokers/create-natural-broker') ?>">کارگزار حقیقی</a></li>
                    <li><a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-brokers/create-legal-broker') ?>">کارگزار حقوقی</a></li>
                </ul>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th class="text-center">نام</th>
                    <th class="text-center">نام خانوادگی</th>
                    <th class="text-center">شماره همراه / لینک</th>
                    <th class="text-center">کد ملی</th>
                    <th class="text-center">نوع/تاریخ ثبت</th>
                    <?php if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') echo '<th class="text-center">دانشکده</th>'; ?>
                    <th class="text-center">وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $broker)
                {
                    $admin = $this->context->admin($broker->connector_info['mobile']);
                    $collegeDetail = $this->context->college_detail($broker->college);
                    $status = 'فعال';
                    $statusIcon = 'badge bg-label-success';
                    $accessStatus = ' باز';
                    $accessStatusBg = 'badge bg-label-success';
                    $brokerType = 'حقوقی';
                    if($broker->type == '2')
                        $brokerType = 'حقیقی';
                    $subServiceIdStatus = false;
                    if($broker->financial_info != null)
                        if(array_key_exists('sub_service_id', $broker->financial_info))
                            if($broker->financial_info['sub_service_id'] != null && $broker->financial_info['sub_service_id'] != '')
                                $subServiceIdStatus = true;
                    if($admin != null)
                    {
                        if($admin->status == 9)
                        {
                            $accessStatus = ' مسدود';
                            $accessStatusBg = 'badge bg-label-danger';
                        }
                    }
                    else
                    {
                        $accessStatus = ' *****';
                        $accessStatusBg = 'badge bg-label-danger';
                    }
                    $edit = 'edit'.rand();
                    $changeStatus = 'changeStatus'.rand();
                    $changeAccess = 'changeAccess'.rand();
                    $confirm = 'confirm'.rand();
                    $unConfirm = 'UnConfirm'.rand();
                    $resetPassword = 'resetPassword'.rand();
                    $viewContracts = 'viewContracts'.rand();
                    $newContracts = 'newContracts'.rand();
                    $viewFiles = 'viewFiles'.rand();
                    if($broker->status == 0)
                    {
                        $statusIcon = 'badge bg-label-danger';
                        $status = 'غیر فعال';
                    }
                    if($broker->status == '2')
                    {
                        $status = 'در انتظار تائید';
                        $statusIcon = 'badge bg-label-danger';
                    }
                    if($broker->status == '4')
                    {
                        $status = 'نیاز به اصلاح';
                        $statusIcon = 'badge bg-label-danger';
                    }
                    if($broker->status == '5')
                    {
                        $status = 'رد شده';
                        $statusIcon = 'badge bg-label-danger';
                    }
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center rounded-pill <?= $statusIcon ?>"><?= $dataProvider->pagination->page * 20 + $i++ ?></span></th>
                        <td><?= Html::encode($broker->connector_info['first_name']) ?></td>
                        <td><?= Html::encode($broker->connector_info['last_name']) ?></td>
                        <td>
                            <?= Html::encode($broker->connector_info['mobile']) ?><hr>
                            <button type="button" class="btn btn-label-primary copy-link-btn" data-link="https://eec.ut.ac.ir/pay/<?= $broker->link ?>" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="برای کپی کردن لینک کامل کلیک کنید">
                                <?= $broker->link ?>
                            </button>
                            <hr>
                            <?php
                            $walletAmount = 0;
                            if($broker->wallet_amount != null)
                                $walletAmount = $broker->wallet_amount;
                            echo number_format($walletAmount),' تومان';
                            ?>
                        </td>
                        <td><?= Html::encode($broker->connector_info['id']) ?></td>
                        <td>
                            <?php
                            echo $brokerType.'<hr>'.jdate('Y/m/d', hexdec(substr($broker->_id, 0, 8)));
                            ?>
                        </td>
                        <?php
                        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                        {
                            if(strlen($collegeDetail->title) <= 30)
                                echo '<td>'. Html::encode($collegeDetail->title).'</td>';
                            else
                            {
                                ?>
                                <td>
                                    <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= Html::encode($collegeDetail->title) ?>">
                                        <?= Html::encode(substr($collegeDetail->title,0,27)).'...' ?>
                                    </button>
                                </td>
                                <?php
                            }
                        }
                        ?>
                        <td class="demo-inline-spacing text-wrap w-25"><span class="<?= $statusIcon ?>"><?= 'وضعیت: '.$status ?></span>
                            <?php
                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                            {
                                ?>
                                <span class="badge <?= $accessStatusBg ?>"><?= 'دسترسی '.$accessStatus ?></span>
                                <?php
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <?php
                                $editTitle = 'ویرایش مشخصات';
                                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                    $editTitle = 'بررسی و تائید مشخصات';
                                if($broker->type == '1')
                                    echo '<a class="dropdown-item" href="'.Yii::$app->urlManager->createAbsoluteUrl(['manage-brokers/edit-legal-broker','_id'=>(string) $broker->_id]) .'">'.$editTitle.'</a>';
                                else
                                    echo '<a class="dropdown-item" href="'.Yii::$app->urlManager->createAbsoluteUrl(['manage-brokers/edit-natural-broker','_id'=>(string) $broker->_id]) .'">'.$editTitle.'</a>';
                                ?>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewFiles ?>">مشاهده مدارک</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewContracts ?>">مشاهده قراردادها</a>
                                <?php
                                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || (!$subServiceIdStatus))
                                {
                                    ?>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $newContracts ?>">افزودن قرارداد</a>
                                    <?php
                                }
                                ?>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $resetPassword ?>">بازنشانی رمز عبور</a>
                                <?php
                                if(($broker->status == '1' || $broker->status == '0'))
                                {
                                    ?>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">
                                        <?php
                                        if($broker->status == '1')
                                            echo 'غیر فعال کردن';
                                        else
                                            echo 'فعال کردن';
                                        ?>
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $changeAccess ?>">
                                        <?php
                                        if($admin->status == 10)
                                            echo 'مسدود کردن دسترسی';
                                        else
                                            echo 'باز کردن دسترسی';
                                        ?>
                                    </a>
                                    <?php
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $resetPassword ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">بازنشانی رمز عبور <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
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
                                    <?= $form->field($broker, '_id')->hiddenInput()->label(false); ?>
                                    <p>آیا از بازنشانی رمز عبور <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?> به کد ملی وی (<?= $broker->connector_info['id'] ?>) اطمبنان دارید؟</p>
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
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
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
                                    <?= $form->field($broker, '_id')->hiddenInput()->label(false); ?>
                                    <p>وضعیت <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?> در حال حاضر  (<?= $status ?>) می باشد</p>
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
                    <div class="modal fade" id="<?= $changeAccess ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت دسترسی <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['change_access'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($broker, '_id')->hiddenInput()->label(false); ?>
                                    <p>وضعیت دسترسی <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?> در حال حاضر  (<?= $accessStatus ?>) می باشد</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">تغییر دسترسی</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $viewFiles ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده مدارک <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <ul class="timeline">
                                        <?php
                                        if($broker->type == 1)
                                        {
                                            ?>
                                            <li class="timeline-item timeline-item-transparent ps-4">
                                                <span class="timeline-point timeline-point-primary"></span>
                                                <div class="timeline-event pb-2">
                                                    <div class="timeline-header mb-1">
                                                        <!--                                                        <h6 class="mb-0 mt-n1"><a download="true" href="--><?php //= $front.'/broker_files/'.$broker->statute_file ?><!--">دانلود فایل اساسنامه شرکت</a></h6>-->
                                                        <h6 class="mb-0 mt-n1"><a download="true" href="<?= Yii::$app->urlManager->createUrl(['manage-brokers/file','filename' => $broker->statute_file])  ?>">دانلود فایل اساسنامه شرکت</a></h6>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="timeline-item timeline-item-transparent ps-4">
                                                <span class="timeline-point timeline-point-primary"></span>
                                                <div class="timeline-event pb-2">
                                                    <div class="timeline-header mb-1">
                                                        <!--                                                        <h6 class="mb-0 mt-n1"><a download="true" href="--><?php //= $front.'/broker_files/'.$broker->newspaper_file ?><!--">دانلود روزنامه رسمی شرکت</a></h6>-->
                                                        <h6 class="mb-0 mt-n1"><a download="true" href="<?= Yii::$app->urlManager->createUrl(['manage-brokers/file','filename' => $broker->newspaper_file])  ?>">دانلود روزنامه رسمی شرکت</a></h6>
                                                    </div>
                                                </div>
                                            </li>
                                            <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <li class="timeline-item timeline-item-transparent ps-4">
                                                <span class="timeline-point timeline-point-primary"></span>
                                                <div class="timeline-event pb-2">
                                                    <div class="timeline-header mb-1">
                                                        <!--                                                        <h6 class="mb-0 mt-n1"><a download="true" href="--><?php //= $front.'/broker_files/'.$broker->id_file ?><!--">دانلود فایل کارت ملی</a></h6>-->
                                                        <h6 class="mb-0 mt-n1"><a download="true" href="<?= Yii::$app->urlManager->createUrl(['manage-brokers/file','filename' => $broker->id_file])  ?>">دانلود فایل کارت ملی</a></h6>
                                                        <!--                                                        <h6 class="mb-0 mt-n1"><a download="true" href="--><?php //= Yii::$app->urlManager->createAbsoluteUrl(['manage-brokers/file','filename' => $broker->id_file])  ?><!--">دانلود فایل کارت ملی</a></h6>-->
                                                    </div>
                                                </div>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                        <li class="timeline-item timeline-item-transparent ps-4">
                                            <span class="timeline-point timeline-point-primary"></span>
                                            <div class="timeline-event pb-2">
                                                <div class="timeline-header mb-1">
                                                    <!--                                                    <h6 class="mb-0 mt-n1"><a download="true" href="--><?php //= $front.'/broker_files/'.$broker->contract_file ?><!--">دانلود فایل قرارداد</a></h6>-->
                                                    <h6 class="mb-0 mt-n1">
                                                        <?php
                                                        if($broker->type == '1')
                                                        {
                                                            ?>
                                                            <a download="true" href="<?= Yii::$app->urlManager->createUrl(['manage-brokers/file','filename' => $broker->id_file])  ?>">دانلود فایل قرارداد</a>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                            <a download="true" href="<?= Yii::$app->urlManager->createUrl(['manage-brokers/file','filename' => $broker->contract_file])  ?>">دانلود فایل قرارداد</a>
                                                            <?php
                                                        }
                                                        ?>
                                                    </h6>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $viewContracts ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده قراردادهای <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="demo-inline-spacing mt-3">
                                        <div class="list-group list-group-flush">
                                            <ul class="list-group list-group-flush">
                                                <?php
                                                $row = 0;
                                                foreach ($broker->contracts as $contract)
                                                {
                                                    $form = ActiveForm::begin(
                                                        [
                                                            'action' => ['change_contract_status'],
                                                            "method" => "post",
                                                        ]
                                                    );
                                                    $contractStatus = 'غیر فعال کردن';
                                                    $contractBg = 'alert-success';
                                                    if($contract['status'] == '0')
                                                    {
                                                        $contractStatus = 'فعال کردن';
                                                        $contractBg = 'alert-danger';
                                                    }
                                                    ?>
                                                    <input type="hidden" name="row" value="<?= $row++ ?>">
                                                    <input type="hidden" name="_id" value="<?= (string) $broker->_id ?>">
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div class="alert <?= $contractBg ?>" role="alert">
                                                            <?php
                                                            if(array_key_exists('title',$contract))
                                                                echo Html::encode($contract['title']).' - '.$contract['share'].' درصد';
                                                            else
                                                                echo '-';
                                                            ?>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-dark">
                                                            <?= $contractStatus ?>
                                                        </button>
                                                    </li>
                                                    <?php
                                                    ActiveForm::end();
                                                }
                                                ?>
                                            </ul>
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
                    <?php
                    if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || (!$subServiceIdStatus))
                    {
                        ?>
                        <div class="modal fade" id="<?= $newContracts ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن قرارداد برای <?= Html::encode($broker->connector_info['first_name'].' '.$broker->connector_info['last_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['new_contract'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <div class="row">
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">عنوان قرارداد *</label>
                                                <input type="text" required name="contractTitle" class="form-control text-start">
                                                <input type="hidden" required name="_id" value="<?= (string) $broker->_id ?>" class="form-control text-start">
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">درصد سهم کارگزار *</label>
                                                <input type="text" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)" required name="contractShare" class="form-control text-start">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-success">ثبت قرارداد</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
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

<script>
    function copyToClipboard(text) {
        // روش مدرن با Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess();
            }).catch(function(err) {
                // اگر روش مدرن کار نکرد، از روش قدیمی استفاده کن
                fallbackCopyTextToClipboard(text);
            });
        } else {
            // استفاده از روش قدیمی
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;

        // جلوگیری از اسکرول به پایین
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess();
            } else {
                showCopyError();
            }
        } catch (err) {
            showCopyError();
        }

        document.body.removeChild(textArea);
    }

    function showCopySuccess() {
        // نمایش پیام موفقیت با toastr
        toastr.success("لینک با موفقیت در کلیپ‌بورد کپی شد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": true,
            "progressBar": true,
            "timeOut": 3000
        });
    }

    function showCopyError() {
        toastr.error("خطا در کپی کردن لینک", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": true,
            "progressBar": true,
            "timeOut": 3000
        });
    }

    // فقط از event listener استفاده می‌کنیم (onclick حذف شده)
    document.addEventListener('DOMContentLoaded', function() {
        var copyButtons = document.querySelectorAll('.copy-link-btn');

        copyButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var linkText = this.getAttribute('data-link');
                copyToClipboard(linkText);
            });
        });
    });
</script>