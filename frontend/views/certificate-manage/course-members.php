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

$newLesson = new Courses();
SingleAsset::register($this);
Select2Asset::register($this);
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
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("پروفایل مورد نظر ویرایش گردید", {
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
    toastr.success("درخواست صدور مدرک صادر گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("درخواست مورد نظر تائید گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("پیش فرض پرینت تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("سریال مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.error("فایل مورد نظر یافت نشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.success("وضعیت صدور گواهی دیجیتال تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;

    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
$type = array(
    '2' => 'دوره جامع (بین ۲۰ تا ۲۵۰ ساعت)',
    '3' => 'دوره یکساله (بین ۲۵۰ تا ۳۵۰ ساعت)',
);

?>
<?php
// آدرس نسبی (root-relative) تا AJAX همیشه روی همان scheme و host صفحه‌ی جاری برود؛
// createAbsoluteUrl(..., 'https') روی سرور محلی که http است باعث شکست درخواست می‌شد.
$url = Yii::$app->urlManager->createUrl('certificate-manage/show_profile_form');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-profile-form',function(e) {
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
            $('#modalCenterTitle').html(main_data.title);
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <ol class="lh-1-85 breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">مدیریت مدارک</a>
                </li>
                <li class="breadcrumb-item active">مدیریت مدارک دوره (<?= Html::encode($model->title['main_fa']) ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2"><a class="h6" href="<?= Yii::$app->urlManager->createAbsoluteUrl('certificate-manage') ?>">بازگشت</a></span>
        </div>
    </nav>
    <div class="card text-center mb-3">
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
                    <th>شماره سریال</th>
                    <th>وضعیت پروفایل</th>
                    <th>وضعیت درخواست</th>
                    <th></th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                $i = 1;
                $ii = 1;
                foreach ($dataProvider->models as $member)
                {
                    $addRequest = 'addRequest'.rand();
                    $addSerial = 'addSerial'.rand();
                    $confirmRequest = 'confirmRequest'.rand();
                    $completeProfile = 'completeProfile' . rand();
                    $statusProfile = 'تکمیل نشده';
                    $statusProfileFlag = 1;
                    $statusProfileBg = 'bg-label-warning';
                    $statusRequest = '<span class="badge bg-label-warning">درخواست داده نشده</span>';
                    $userRequest = $this->context->user_request($member->username, (string) $model->_id);
                    $allowRequest = true;
                    if($userRequest != null)
                        if($userRequest->status == '1' || $userRequest->status == '2')
                            $allowRequest = false;
                    if($userRequest != null)
                    {
                        $flag = 'نامشخص';
                        if($userRequest->status == '1')
                            $flag = 'در انتظار بررسی دانشکده';
                        else if($userRequest->status == '2')
                            $flag = 'تائید دانشکده در انتظار صدور';
                        else if($userRequest->status == '3')
                            $flag = 'رد شده توسط دانشکده';
                        else if($userRequest->status == '4')
                            $flag = 'صادر شده';
                        else if($userRequest->status == '5')
                            $flag = 'رد شده توسط صدور مدرک';
                        $statusRequest = 'درخواست '.$userRequest->request.' ('.$flag.')';
                    }
                    if ($member->issuance_certificate_information != null)
                        if (array_key_exists('first_name_fa', $member->issuance_certificate_information) && array_key_exists('last_name_fa', $member->issuance_certificate_information)  && array_key_exists('first_name_en', $member->issuance_certificate_information)  && array_key_exists('last_name_en', $member->issuance_certificate_information)  && array_key_exists('id', $member->issuance_certificate_information))
                            if ($member->issuance_certificate_information['first_name_fa'] != '' && $member->issuance_certificate_information['last_name_fa'] != '' && $member->issuance_certificate_information['first_name_en'] != '' && $member->issuance_certificate_information['last_name_en'] != '' && $member->issuance_certificate_information['id'] != '')
                            {
                                $statusProfile = 'تکمیل شده';
                                $statusProfileFlag = 0;
                                $statusProfileBg = 'bg-label-success';
                            }
                    ?>
                    <tr>
                        <th scope="row"><?= $dataProvider->pagination->page * 50 + $ii++ ?></th>
                        <td class="text-wrap w-25"><?= Html::encode($member->first_name) ?></td>
                        <td class="text-wrap w-25"><?= Html::encode($member->last_name) ?></td>
                        <td class="text-wrap w-25">
                            <?php
                            if($userRequest != null)
                            {
                                if($userRequest->status == '4')
                                {
                                    echo $userRequest->serial_number;
                                    if($userRequest->pre_serial_number != null && $userRequest->pre_serial_number != '' && (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt'))
                                        echo '<br><span class="badge bg-label-dark">سریال قبلی:  '.Html::encode($userRequest->pre_serial_number).'</span>';
                                }
                                else
                                    echo '-';
                            }
                            else
                                echo '-';
                            ?>
                        </td>
                        <td class="text-wrap w-25"><span class="badge <?= $statusProfileBg ?>"><?= $statusProfile ?></span></td>
                        <td class="text-wrap w-25"><?= $statusRequest ?></td>
                        <td class="text-wrap w-25">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <a  href="#" data-bs-toggle="modal" data-bs-target="#profile_form" id="<?= (string) $member->_id.'-0' ?>" class="btn btn-label-info show-profile-form">ویرایش پروفایل</a>
                                <?php
                                if($userRequest != null)
                                {
                                    if($userRequest->status == '1')
                                    {
                                        echo '<a class="btn btn-label-secondary" href="#" data-bs-toggle="modal" data-bs-target="#'.$confirmRequest.'">
تائید درخواست
</a>';
                                    }
//                                    else if($userRequest->status == '4')
//                                        echo 'مدرک صادر شده است';
                                }
                                else
                                {
                                    if($statusProfileFlag == 0)
                                        echo '<a class="btn btn-label-secondary" href="#" data-bs-toggle="modal" data-bs-target="#'.$addRequest.'">
درخواست مدرک
</a>';
                                    else
                                            echo '<a  href="#" data-bs-toggle="modal" data-bs-target="#profile_form" id="'. (string) $member->_id.'-'.(string) $model->_id .'" class="btn btn-label-secondary show-profile-form">
درخواست مدرک
</a>';
                                }
                                ?>
                                <?php
                                if(Yii::$app->user->identity->role == 'user' || DashboardController::access('certificate-manage'))
                                {
                                    if($userRequest != null)
                                    {
                                        if(($userRequest->status == '2' || $userRequest->status == '4') && $statusProfileFlag == 0)
                                        {
                                            if($model->lessons != null)
                                                if(count($model->lessons) > 0)
                                                {
                                                    ?>
                                                    <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['certificate-manage/new-print-certificate', '_id' => (string) $userRequest->_id]) ?>">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M17.1211 2.87868C16.2424 2 14.8282 2 11.9998 2C9.17134 2 7.75712 2 6.87844 2.87868C6.38608 3.37105 6.16961 4.03157 6.07444 5.01484C6.63368 4.99996 7.25183 4.99998 7.92943 5H16.0706C16.748 4.99998 17.366 4.99996 17.9251 5.01483C17.8299 4.03156 17.6135 3.37105 17.1211 2.87868Z" fill="#1C274C"/>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M18 14.5C18 17.3284 18 20.2426 17.1213 21.1213C16.2426 22 14.8284 22 12 22C9.17158 22 7.75736 22 6.87868 21.1213C6 20.2426 6 17.3284 6 14.5H18ZM15.75 16.75C15.75 17.1642 15.4142 17.5 15 17.5H9C8.58579 17.5 8.25 17.1642 8.25 16.75C8.25 16.3358 8.58579 16 9 16H15C15.4142 16 15.75 16.3358 15.75 16.75ZM13.75 19.75C13.75 20.1642 13.4142 20.5 13 20.5H9C8.58579 20.5 8.25 20.1642 8.25 19.75C8.25 19.3358 8.58579 19 9 19H13C13.4142 19 13.75 19.3358 13.75 19.75Z" fill="#1C274C"/>
                                                            <g opacity="0.5">
                                                                <path d="M15 17.5C15.4142 17.5 15.75 17.1642 15.75 16.75C15.75 16.3358 15.4142 16 15 16H9C8.58579 16 8.25 16.3358 8.25 16.75C8.25 17.1642 8.58579 17.5 9 17.5H15Z" fill="#1C274C"/>
                                                                <path d="M13 20.5C13.4142 20.5 13.75 20.1642 13.75 19.75C13.75 19.3358 13.4142 19 13 19H9C8.58579 19 8.25 19.3358 8.25 19.75C8.25 20.1642 8.58579 20.5 9 20.5H13Z" fill="#1C274C"/>
                                                            </g>
                                                            <path opacity="0.5" d="M16 6H8C5.17157 6 3.75736 6 2.87868 6.87868C2 7.75736 2 9.17157 2 12C2 14.8284 2 16.2426 2.87868 17.1213C3.37323 17.6159 4.03743 17.8321 5.02795 17.9266C4.99998 17.2038 4.99999 15.3522 5 14.5C4.72386 14.5 4.5 14.2761 4.5 14C4.5 13.7239 4.72386 13.5 5 13.5H19C19.2761 13.5 19.5 13.7239 19.5 14C19.5 14.2761 19.2761 14.5003 19 14.5003C19 15.3525 19 17.2039 18.9721 17.9266C19.9626 17.8321 20.6268 17.6159 21.1213 17.1213C22 16.2426 22 14.8284 22 12C22 9.17157 22 7.75736 21.1213 6.87868C20.2426 6 18.8284 6 16 6Z" fill="#1C274C"/>
                                                            <path d="M9 10.75C9.41421 10.75 9.75 10.4142 9.75 10C9.75 9.58579 9.41421 9.25 9 9.25H6C5.58579 9.25 5.25 9.58579 5.25 10C5.25 10.4142 5.58579 10.75 6 10.75H9Z" fill="#1C274C"/>
                                                            <path d="M18 10C18 10.5523 17.5523 11 17 11C16.4477 11 16 10.5523 16 10C16 9.44772 16.4477 9 17 9C17.5523 9 18 9.44772 18 10Z" fill="#1C274C"/>
                                                        </svg>

                                                    </a>
                                                        <?php
                                                }
                                            ?>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#<?= $addSerial ?>">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
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
                                            </a>
                                            <div class="modal fade" id="<?= $addSerial ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سریال برای درخواست صدور مدرک <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
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
                                                            <?= $form->field($userRequest, '_id')->hiddenInput()->label(false); ?>
                                                            <div class="row">
                                                                <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                                    <label for="nameWithTitle" class="form-label">شماره سریال *</label>
                                                                    <?= $form->field($userRequest, 'serial_number')->textInput(
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
                                    }
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $addRequest ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle"> درخواست صدور مدرک برای <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    if($allowRequest)
                                    {
                                        ?>
                                        <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['add_single_request'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                        <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                        <input type="hidden" name="course_id" value="<?= (string) $model->_id ?>">
                                        <input type="hidden" name="college" value="<?= (string) $model->college ?>">
                                        <div class="row">
                                            آیا از ارسال درخواست صدور مدرک برای <?= Html::encode($member->first_name.' '.$member->last_name) ?> مطمئن هستید؟
                                        </div>
                                        <?php
                                    }
                                    else
                                    {
                                        echo '<p>برای دانشپذیر مورد نظر قبلا درخواست صدور مدرک داده شده است</p>';
                                    }
                                    ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <?php
                                    if($allowRequest)
                                    {
                                        ?>
                                        <button type="submit" class="btn btn-primary">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $confirmRequest ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle"> تائید درخواست صدور مدرک  <?= Html::encode($member->first_name.' '.$member->last_name) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    if($userRequest != null)
                                    {
                                        if($userRequest->status == '1')
                                        {
                                            ?>
                                            <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['confirm_request'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                            <input type="hidden" name="request_id" value="<?= (string) $userRequest->_id ?>">
                                            <div class="row">
                                                آیا از تائید درخواست صدور مدرک برای <?= Html::encode($member->first_name.' '.$member->last_name) ?> مطمئن هستید؟
                                            </div>
                                            <?php
                                        }
                                    }

                                    ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <?php
                                    if($userRequest != null)
                                    {
                                       if($userRequest->status == '1')
                                       {
                                           ?>
                                           <button type="submit" class="btn btn-primary">بله مطمئنم</button>
                                           <?php ActiveForm::end(); ?>
                                           <?php
                                       }
                                    }
                                    ?>
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



<div class="modal fade" id="profile_form" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="body">

        </div>
    </div>
</div>