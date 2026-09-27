<?php
$this->title = 'شرکت کنندگان در آزمون '.$examDetail->title['fa'];

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\OfflineExams;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new OfflineExams();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("مشخصات کاربر مورد نظر ویرایش گردید", {
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
    toastr.success("نمره مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("آزمون مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("کاربر مورد نظر از آزمون حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت آزمون مرود نظر تغییر یافت", {
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
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        شرکت کنندگان در آزمون <?= $examDetail->title['fa'] ?>
    </h4>
    <?php echo $this->render('participants_search', [
        'examDetail' => $examDetail,
        'model' => $searchModel,
    ]); ?>
    <div class="card">
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>تصویر</th>
                    <th>نام و نام خانوادگی</th>
                    <th>نام کاربری</th>
                    <th>نام پدر</th>
                    <th>شماره داوطلبی</th>
                    <th>نمره</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                $k = 1;
                foreach ($dataProvider->models as $order)
                {
                    $edit = 'edit'.rand();
                    $editScore = 'score'.rand();
                    $viewShare = 'viewShare'.rand();
                    $delete = 'delete'.rand();
                    $userDetail = $this->context->user_detail($order->username);
                    $score = null;
                    $row = null;
                    $statusIcon = 'badge bg-label-success';
                    if($order->orders != null)
                    {
                        $i = 0;
                        foreach ($order->orders as $item)
                        {
                            if($item['type'] == '3' && $item['_id'] == (string) $examDetail->_id)
                                $row = $i;
                            $i++;
                        }
                    }
                    if($row !== null && $order->status == '1')
                    {
                        if(array_key_exists('score', $order->orders[$row]))
                        {
                            if($order->orders[$row]['score'] != null && $order->orders[$row]['score'] != '')
                                $score = $order->orders[$row]['score'];
                        }
                        if(array_key_exists('removed', $order->orders[$row]))
                        {
                            if($order->orders[$row]['removed'] == '1')
                                $statusIcon = 'badge bg-label-danger';
                        }
                    }
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center rounded-pill <?= $statusIcon ?>"><?= $dataProvider->pagination->page * 20 + $k++ ?></span></th>
                        <td>
                            <div class="avatar avatar-lg me-2">
                                <?php
                                if($userDetail->applicant_info != null)
                                    if(array_key_exists('profile_image', $userDetail->applicant_info))
                                        echo '<img class="rounded-circle" src="'.$front.'/users_profile/'.$userDetail->applicant_info['profile_image'].'">';
                                ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            if($userDetail->applicant_info != null)
                            {
                                if(array_key_exists('first_name', $userDetail->applicant_info))
                                    echo Html::encode($userDetail->applicant_info['first_name']);
                                if(array_key_exists('last_name', $userDetail->applicant_info))
                                    echo ' '.Html::encode($userDetail->applicant_info['last_name']);
                            }
                            ?>
                        </td>
                        <td><?= Html::encode($order->username) ?></td>
                        <td>
                            <?php
                            if($userDetail->applicant_info != null)
                            {
                                if(array_key_exists('father_name', $userDetail->applicant_info))
                                    echo Html::encode($userDetail->applicant_info['father_name']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $row = null;
                            if($order->orders != null)
                            {
                                $i = 0;
                                foreach ($order->orders as $item)
                                {
                                    if($item['type'] == '3' && $item['_id'] == (string) $examDetail->_id)
                                        $row = $i;
                                    $i++;
                                }
                            }
                            if($row !== null && $order->status == '1')
                            {
                                if(array_key_exists('applicant_id', $order->orders[$row]))
                                    echo $order->orders[$row]['applicant_id'];
                                else
                                    echo '-';
                            }
                            else
                                echo '-';
                            ?>
                        </td>
                        <td>
                            <?php
                            if($score !== null)
                                echo $score;
                            else
                                echo '-';
                            ?>
                        </td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش مشخصات</a>
                                <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['offline-exams/print','_id' => (string) $order->_id]) ?>">پرینت کارت ورود به جلسه</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $editScore ?>">ثبت نمره</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewShare ?>">مشاهده اطلاعات پرداخت</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $delete ?>">حذف از آزمون</a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $viewShare ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle"> اطلاعات پرداخت</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-secondary alert-dismissible" role="alert">
                                        <div>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M6.94028 2C7.35614 2 7.69326 2.32421 7.69326 2.72414V4.18487C8.36117 4.17241 9.10983 4.17241 9.95219 4.17241H13.9681C14.8104 4.17241 15.5591 4.17241 16.227 4.18487V2.72414C16.227 2.32421 16.5641 2 16.98 2C17.3958 2 17.733 2.32421 17.733 2.72414V4.24894C19.178 4.36022 20.1267 4.63333 20.8236 5.30359C21.5206 5.97385 21.8046 6.88616 21.9203 8.27586L22 9H2.92456H2V8.27586C2.11571 6.88616 2.3997 5.97385 3.09665 5.30359C3.79361 4.63333 4.74226 4.36022 6.1873 4.24894V2.72414C6.1873 2.32421 6.52442 2 6.94028 2Z" fill="#1C274C"/>
                                                <path opacity="0.5" d="M21.9995 14.0001V12.0001C21.9995 11.161 21.9963 9.66527 21.9834 9H2.00917C1.99626 9.66527 1.99953 11.161 1.99953 12.0001V14.0001C1.99953 17.7713 1.99953 19.6569 3.1711 20.8285C4.34267 22.0001 6.22829 22.0001 9.99953 22.0001H13.9995C17.7708 22.0001 19.6564 22.0001 20.828 20.8285C21.9995 19.6569 21.9995 17.7713 21.9995 14.0001Z" fill="#1C274C"/>
                                                <path d="M18 17C18 17.5523 17.5523 18 17 18C16.4477 18 16 17.5523 16 17C16 16.4477 16.4477 16 17 16C17.5523 16 18 16.4477 18 17Z" fill="#1C274C"/>
                                                <path d="M18 13C18 13.5523 17.5523 14 17 14C16.4477 14 16 13.5523 16 13C16 12.4477 16.4477 12 17 12C17.5523 12 18 12.4477 18 13Z" fill="#1C274C"/>
                                                <path d="M13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17Z" fill="#1C274C"/>
                                                <path d="M13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13Z" fill="#1C274C"/>
                                                <path d="M8 17C8 17.5523 7.55228 18 7 18C6.44772 18 6 17.5523 6 17C6 16.4477 6.44772 16 7 16C7.55228 16 8 16.4477 8 17Z" fill="#1C274C"/>
                                                <path d="M8 13C8 13.5523 7.55228 14 7 14C6.44772 14 6 13.5523 6 13C6 12.4477 6.44772 12 7 12C7.55228 12 8 12.4477 8 13Z" fill="#1C274C"/>
                                            </svg>
                                            تاریخ:  <?= $order->payment_info['date'] ?>
                                        </div>
                                        <div>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g opacity="0.5">
                                                    <path d="M14 2.75C15.9068 2.75 17.2615 2.75159 18.2892 2.88976C19.2952 3.02503 19.8749 3.27869 20.2981 3.7019C20.7213 4.12511 20.975 4.70476 21.1102 5.71085C21.2484 6.73851 21.25 8.09318 21.25 10C21.25 10.4142 21.5858 10.75 22 10.75C22.4142 10.75 22.75 10.4142 22.75 10V9.94359C22.75 8.10583 22.75 6.65019 22.5969 5.51098C22.4392 4.33856 22.1071 3.38961 21.3588 2.64124C20.6104 1.89288 19.6614 1.56076 18.489 1.40314C17.3498 1.24997 15.8942 1.24998 14.0564 1.25H14C13.5858 1.25 13.25 1.58579 13.25 2C13.25 2.41421 13.5858 2.75 14 2.75Z" fill="#1C274C"/>
                                                    <path d="M9.94358 1.25H10C10.4142 1.25 10.75 1.58579 10.75 2C10.75 2.41421 10.4142 2.75 10 2.75C8.09318 2.75 6.73851 2.75159 5.71085 2.88976C4.70476 3.02503 4.12511 3.27869 3.7019 3.7019C3.27869 4.12511 3.02503 4.70476 2.88976 5.71085C2.75159 6.73851 2.75 8.09318 2.75 10C2.75 10.4142 2.41421 10.75 2 10.75C1.58579 10.75 1.25 10.4142 1.25 10V9.94358C1.24998 8.10583 1.24997 6.65019 1.40314 5.51098C1.56076 4.33856 1.89288 3.38961 2.64124 2.64124C3.38961 1.89288 4.33856 1.56076 5.51098 1.40314C6.65019 1.24997 8.10583 1.24998 9.94358 1.25Z" fill="#1C274C"/>
                                                    <path d="M22 13.25C22.4142 13.25 22.75 13.5858 22.75 14V14.0564C22.75 15.8942 22.75 17.3498 22.5969 18.489C22.4392 19.6614 22.1071 20.6104 21.3588 21.3588C20.6104 22.1071 19.6614 22.4392 18.489 22.5969C17.3498 22.75 15.8942 22.75 14.0564 22.75H14C13.5858 22.75 13.25 22.4142 13.25 22C13.25 21.5858 13.5858 21.25 14 21.25C15.9068 21.25 17.2615 21.2484 18.2892 21.1102C19.2952 20.975 19.8749 20.7213 20.2981 20.2981C20.7213 19.8749 20.975 19.2952 21.1102 18.2892C21.2484 17.2615 21.25 15.9068 21.25 14C21.25 13.5858 21.5858 13.25 22 13.25Z" fill="#1C274C"/>
                                                    <path d="M2.75 14C2.75 13.5858 2.41421 13.25 2 13.25C1.58579 13.25 1.25 13.5858 1.25 14V14.0564C1.24998 15.8942 1.24997 17.3498 1.40314 18.489C1.56076 19.6614 1.89288 20.6104 2.64124 21.3588C3.38961 22.1071 4.33856 22.4392 5.51098 22.5969C6.65019 22.75 8.10583 22.75 9.94359 22.75H10C10.4142 22.75 10.75 22.4142 10.75 22C10.75 21.5858 10.4142 21.25 10 21.25C8.09318 21.25 6.73851 21.2484 5.71085 21.1102C4.70476 20.975 4.12511 20.7213 3.7019 20.2981C3.27869 19.8749 3.02503 19.2952 2.88976 18.2892C2.75159 17.2615 2.75 15.9068 2.75 14Z" fill="#1C274C"/>
                                                </g>
                                                <path d="M5.52721 5.52721C5 6.05442 5 6.90294 5 8.6C5 9.73137 5 10.2971 5.35147 10.6485C5.70294 11 6.26863 11 7.4 11H8.6C9.73137 11 10.2971 11 10.6485 10.6485C11 10.2971 11 9.73137 11 8.6V7.4C11 6.26863 11 5.70294 10.6485 5.35147C10.2971 5 9.73137 5 8.6 5C6.90294 5 6.05442 5 5.52721 5.52721Z" fill="#1C274C"/>
                                                <path d="M5.52721 18.4728C5 17.9456 5 17.0971 5 15.4C5 14.2686 5 13.7029 5.35147 13.3515C5.70294 13 6.26863 13 7.4 13H8.6C9.73137 13 10.2971 13 10.6485 13.3515C11 13.7029 11 14.2686 11 15.4V16.6C11 17.7314 11 18.2971 10.6485 18.6485C10.2971 19 9.73138 19 8.60002 19C6.90298 19 6.05441 19 5.52721 18.4728Z" fill="#1C274C"/>
                                                <path d="M13 7.4C13 6.26863 13 5.70294 13.3515 5.35147C13.7029 5 14.2686 5 15.4 5C17.0971 5 17.9456 5 18.4728 5.52721C19 6.05442 19 6.90294 19 8.6C19 9.73137 19 10.2971 18.6485 10.6485C18.2971 11 17.7314 11 16.6 11H15.4C14.2686 11 13.7029 11 13.3515 10.6485C13 10.2971 13 9.73137 13 8.6V7.4Z" fill="#1C274C"/>
                                                <path d="M13.3515 18.6485C13 18.2971 13 17.7314 13 16.6V15.4C13 14.2686 13 13.7029 13.3515 13.3515C13.7029 13 14.2686 13 15.4 13H16.6C17.7314 13 18.2971 13 18.6485 13.3515C19 13.7029 19 14.2686 19 15.4C19 17.097 19 17.9456 18.4728 18.4728C17.9456 19 17.0971 19 15.4 19C14.2687 19 13.7029 19 13.3515 18.6485Z" fill="#1C274C"/>
                                            </svg>
                                            شماره سفارش:  <?= $order->payment_info['order_id'] ?>
                                        </div>
                                        <div>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M22 8.29344C22 11.7692 19.1708 14.5869 15.6807 14.5869C15.0439 14.5869 13.5939 14.4405 12.8885 13.8551L12.0067 14.7333C11.4883 15.2496 11.6283 15.4016 11.8589 15.652C11.9551 15.7565 12.0672 15.8781 12.1537 16.0505C12.1537 16.0505 12.8885 17.075 12.1537 18.0995C11.7128 18.6849 10.4783 19.5045 9.06754 18.0995L8.77362 18.3922C8.77362 18.3922 9.65538 19.4167 8.92058 20.4412C8.4797 21.0267 7.30403 21.6121 6.27531 20.5876L5.2466 21.6121C4.54119 22.3146 3.67905 21.9048 3.33616 21.6121L2.45441 20.7339C1.63143 19.9143 2.1115 19.0264 2.45441 18.6849L10.0963 11.0743C10.0963 11.0743 9.3615 9.90338 9.3615 8.29344C9.3615 4.81767 12.1907 2 15.6807 2C19.1708 2 22 4.81767 22 8.29344Z" fill="#1C274C"/>
                                                <path d="M17.8853 8.29353C17.8853 9.50601 16.8984 10.4889 15.681 10.4889C14.4635 10.4889 13.4766 9.50601 13.4766 8.29353C13.4766 7.08105 14.4635 6.09814 15.681 6.09814C16.8984 6.09814 17.8853 7.08105 17.8853 8.29353Z" fill="#1C274C"/>
                                            </svg>
                                            شماره مرجع:  <?= $order->payment_info['reference_id'] ?>
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
                    <div class="modal fade" id="<?= $delete ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف از آزمون</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-secondary alert-dismissible" role="alert">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['delete_user_from_exam'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($order, '_id')->hiddenInput()->label(false); ?>
                                        آیا از حذف کاربر از آزمون مطمئن هستید؟
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">بله مطمئنم </button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش مشخصات <?= Html::encode($userDetail->applicant_info['first_name'].' '.$userDetail->applicant_info['last_name']) ?></h5>
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
                                    <?= $form->field($userDetail, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">نام *</label>
                                            <?= $form->field($userDetail, 'applicant_info[first_name]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">نام خانوادگی *</label>
                                            <?=
                                            $form->field($userDetail, 'applicant_info[last_name]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">نام پدر *</label>
                                            <?=
                                            $form->field($userDetail, 'applicant_info[father_name]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">جنسیت *</label>
                                            <?php
                                            $assistant = array(
                                                '1' => 'آقا',
                                                '0' => 'خانم'
                                            );
                                            echo $form->field($userDetail, 'applicant_info[gender]')->dropDownList(
                                                $assistant,
                                                [
                                                    'class' => 'form-select text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">کد ملی *</label>
                                            <?=
                                            $form->field($userDetail, 'applicant_info[id]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">نیازمند منشی *</label>
                                            <?php
                                            $assistant = array(
                                                '0' => 'نیستم',
                                                '1' => 'هستم'
                                            );
                                            echo $form->field($userDetail, 'applicant_info[needs_assistant]')->dropDownList(
                                                $assistant,
                                                [
                                                    'class' => 'form-select text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="select2Basic" class="form-label">رشته تحصیلی *</label>
                                            <?=
                                            $form->field($userDetail, 'applicant_info[major]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">دانشجوی دکتری دانشگاه تهران *</label>
                                            <?php
                                            $ut = array(
                                                '0' => 'نیستم',
                                                '1' => 'هستم'
                                            );
                                            echo $form->field($userDetail, 'applicant_info[ut_student]')->dropDownList(
                                                $ut,
                                                [
                                                    'class' => 'form-select text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <?php
                                        if($userDetail->applicant_info['ut_student'] == true)
                                        {
                                            ?>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">شماره دانشجویی دکترا</label>
                                                <?=
                                                $form->field($userDetail, 'applicant_info[student_id]')->textInput(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true
                                                    ]
                                                )->label(false);
                                                ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="select2Basic" class="form-label">دانشجوی کارشناسی ارشد دانشگاه تهران *</label>
                                            <?php
                                            $ut = array(
                                                '0' => 'نیستم',
                                                '1' => 'هستم'
                                            );
                                            echo $form->field($userDetail, 'applicant_info[master_student]')->dropDownList(
                                                $ut,
                                                [
                                                    'class' => 'form-select text-start',
                                                    'required' => true
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <?php
                                        if(is_array($userDetail->applicant_info))
                                        {
                                            if(in_array('master_student',$userDetail->applicant_info))
                                            {
                                                if($userDetail->applicant_info['master_student'] == true)
                                                {
                                                    ?>
                                                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                        <label for="select2Basic" class="form-label">شماره دانشجویی کارشناسی ارشد</label>
                                                        <?=
                                                        $form->field($userDetail, 'applicant_info[master_student_id]')->textInput(
                                                            [
                                                                'class' => 'form-control text-start',
                                                                'required' => true
                                                            ]
                                                        )->label(false);
                                                        ?>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                        }
                                        if(is_array($userDetail->applicant_info))
                                        {
                                            if (array_key_exists('emergency_phone_number', $userDetail->applicant_info)) {
                                                $emergencyPhone = $userDetail->applicant_info['emergency_phone_number'] ?? 'ثبت نشده';
                                                ?>
                                                <div class="col-6 col-md-6 col-sm-12 col-lg-6 col-xl-6 mb-3">
                                                    <label for="emergencyPhone" class="form-label">شماره تماس اضطراری</label>
                                                    <input type="text"
                                                           id="emergencyPhone"
                                                           class="form-control"
                                                           disabled
                                                           readonly
                                                           value="<?= Html::encode($emergencyPhone) ?>">
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>

                                        <div class="card mb-4 relative">
                                            <div class="card-body">
                                                <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                    <div class="dz-message needsclick">
                                                        <?= $form->field($userDetail, 'applicant_info[profile_image]')->fileInput(
                                                            [
                                                                'class' => 'form-control text-start drop-file',
                                                            ]
                                                        )->label(false); ?>
                                                        <span class="drop-title">تصویر داوطلب</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">ویرایش </button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $editScore ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت نمره برای <?= Html::encode($userDetail->applicant_info['first_name'].' '.$userDetail->applicant_info['last_name']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">

                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['edit_score'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($order, '_id')->hiddenInput()->label(false); ?>
                                    <input type="hidden" name="exam_id" value="<?= (string) $examDetail->_id ?>">
                                    <div class="row">
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">نمره *</label>
                                            <input type="number" value="<?= $score ?>" name="score" class="form-control text-start">
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">ثبت / ویرایش نمره </button>
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
        </div>
    </div>
</div>
