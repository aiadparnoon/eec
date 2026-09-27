<?php
$this->title = 'گزارش خریدها';

use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use frontend\controllers;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Lessons();
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
    toastr.warning("اندازه تصویر انتخاب شده بیشتر از اندازه تعیین شده است", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.success("درس مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '5')
        $script = <<< JS
    toastr.danger("عنوان درس وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '6')
        $script = <<< JS
    toastr.danger("درس مورد نظر حذف گردید", {
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
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">گزارشگیری</a>
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
            <h5 class="card-title mb-0">لیست خریدها</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $c = 1;
            if ($dataProvider->models != null)
            {
            ?>
                <table class="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th>کاربر</th>
                            <th>دوره ها</th>
                            <th>تاریخ</th>
                            <th>مبلغ پرداخت شده (تومان)</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php
                        foreach ($dataProvider->models as $order)
                        {
                            $viewShare = 'viewShare'.rand();
                            $userDetail = $this->context->user_detail($order->username);
                            $finalAmount = $order->amount;
                            if($order->payments != null)
                            {
                                $i = 0;
                                foreach ($order->payments as $payment)
                                {
                                    if($i != 0)
                                        $finalAmount += $payment['amount'];
                                    $i++;
                                }
                            }
                            else
                            {
                                if($order->prepayment_settlement != null)
                                    if(array_key_exists('status', $order->prepayment_settlement))
                                        $finalAmount += $order->prepayment_settlement['amount'];
                                if($order->settlement_payment != null)
                                    if(array_key_exists('status', $order->settlement_payment))
                                        $finalAmount += $order->settlement_payment['amount'];
                            }
                        ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 50 + $c++ ?></th>
                                <td class="text-wrap w-25">
                                    <?php
                                    if($userDetail != null)
                                        echo Html::encode($userDetail->first_name.' '.$userDetail->last_name.'('.$order->username.')');
                                    else
                                        echo 'نامشخص';
                                    ?>
                                </td>
                                <td class="text-wrap w-25">
                                    <?php
                                    if($order->orders != null)
                                    {
                                        $row = 0;
                                        echo '<ul class="timeline">';
                                        foreach ($order->orders as $item)
                                        {
                                            $collegeDetail = DashboardController::college_detail($order->shares[$row]['college']);
                                            $courseDetail = $this->context->course_detail($item['_id']);
                                            if($courseDetail != null)
                                            {
                                                $viewInstallment = 'viewInstallment'.rand();
                                                ?>

                                                    <li class="timeline-item timeline-item-transparent ps-4">
                                                        <span class="timeline-point timeline-point-success"></span>
                                                        <div class="timeline-event pb-2">
                                                            <div class="timeline-header mb-1">
                                                                <h6 class="mb-0 mt-n1"><?= Html::encode($courseDetail->title['main_fa']) ?></h6>
                                                            </div>
                                                            <?php
                                                            if(Yii::$app->user->identity->role == 'user')
                                                                echo '<p class="mb-2">دانشکده: '.Html::encode($collegeDetail->title).'</p>';
                                                            ?>
                                                            <p class="mb-2">
                                                                نوع پرداخت:
                                                                <?php
                                                                if($item['payment_method'] == '1')
                                                                    echo 'نقدی';
                                                                else
                                                                    echo 'اقساطی';
                                                                ?>
                                                            </p>
                                                            <p class="mb-2">
                                                                مرجع پرداخت:
                                                                <?php
                                                                if($order->payment_info['order_id'] == 'wallet')
                                                                    echo 'کیف پول';
                                                                else
                                                                {
                                                                    if($order->is_pos != null && $order->is_pos == true)
                                                                        echo 'PC POS';
                                                                    else
                                                                        echo 'درگاه اینترنتی';
                                                                }
                                                                ?>
                                                            </p>
                                                            <?php
                                                            if($item['payment_method'] == '2')
                                                            {
                                                                ?>
                                                                <button type="button"  data-bs-toggle="modal" data-bs-target="#<?= $viewInstallment ?>" class="btn btn-label-secondary">مشاهده اقساط</button>
                                                                <div class="modal fade" id="<?= $viewInstallment ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title secondary-font" id="modalCenterTitle"> اطلاعات اقساط</h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <?php
                                                                                $installmentInfo = $this->context->installment_info((string) $order->_id, (string) $courseDetail->_id);
                                                                                if($installmentInfo != null)
                                                                                {
                                                                                    if($installmentInfo->maturities != null)
                                                                                    {
                                                                                        foreach ($installmentInfo->maturities as $maturity)
                                                                                        {
                                                                                            $bgInstallment = 'alert-secondary';
                                                                                            $installmentStatus = 'سررسید نشده';
                                                                                            if($maturity['status'] == '1')
                                                                                            {
                                                                                                $bgInstallment = 'alert-success';
                                                                                                $installmentStatus = 'پرداخت شده';
                                                                                            }
                                                                                            else if(jdate('Ymd') > $maturity['deadline'])
                                                                                            {
                                                                                                $bgInstallment = 'alert-danger';
                                                                                                $installmentStatus = 'پرداخت نشده';
                                                                                            }
                                                                                            ?>
                                                                                            <div class="alert <?= $bgInstallment ?> alert-dismissible" role="alert">
                                                                                                <div>
                                                                                                    <svg id="Calendar" width="18" height="18" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                        <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M21.8696 10.6843H2.63059C2.52859 11.5043 2.47559 12.3973 2.47559 13.3863C2.47559 20.6013 5.03359 23.1593 12.2496 23.1593C19.4666 23.1593 22.0246 20.6013 22.0246 13.3863C22.0246 12.3973 21.9716 11.5043 21.8696 10.6843Z" fill="#000000"/>
                                                                                                        <path d="M15.9086 13.8713C15.9086 14.2853 16.2486 14.6213 16.6626 14.6213C17.0766 14.6213 17.4126 14.2853 17.4126 13.8713C17.4126 13.4573 17.0766 13.1213 16.6626 13.1213H16.6536C16.2396 13.1213 15.9086 13.4573 15.9086 13.8713Z" fill="#000000"/>
                                                                                                        <path d="M15.9086 17.7243C15.9086 18.1383 16.2486 18.4743 16.6626 18.4743C17.0766 18.4743 17.4126 18.1383 17.4126 17.7243C17.4126 17.3093 17.0766 16.9743 16.6626 16.9743H16.6536C16.2396 16.9743 15.9086 17.3093 15.9086 17.7243Z" fill="#000000"/>
                                                                                                        <path d="M11.5086 13.8713C11.5086 14.2853 11.8496 14.6213 12.2636 14.6213C12.6776 14.6213 13.0136 14.2853 13.0136 13.8713C13.0136 13.4573 12.6776 13.1213 12.2636 13.1213H12.2546C11.8406 13.1213 11.5086 13.4573 11.5086 13.8713Z" fill="#000000"/>
                                                                                                        <path d="M11.5086 17.7243C11.5086 18.1383 11.8496 18.4743 12.2636 18.4743C12.6776 18.4743 13.0136 18.1383 13.0136 17.7243C13.0136 17.3093 12.6776 16.9743 12.2636 16.9743H12.2546C11.8406 16.9743 11.5086 17.3093 11.5086 17.7243Z" fill="#000000"/>
                                                                                                        <path d="M7.10059 13.8713C7.10059 14.2853 7.44159 14.6213 7.85559 14.6213C8.26959 14.6213 8.60559 14.2853 8.60559 13.8713C8.60559 13.4573 8.26959 13.1213 7.85559 13.1213H7.84659C7.43259 13.1213 7.10059 13.4573 7.10059 13.8713Z" fill="#000000"/>
                                                                                                        <path d="M7.10059 17.7243C7.10059 18.1383 7.44159 18.4743 7.85559 18.4743C8.26959 18.4743 8.60559 18.1383 8.60559 17.7243C8.60559 17.3093 8.26959 16.9743 7.85559 16.9743H7.84659C7.43259 16.9743 7.10059 17.3093 7.10059 17.7243Z" fill="#000000"/>
                                                                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2033 4.26291V2.65991C17.2033 2.24591 16.8673 1.90991 16.4533 1.90991C16.0393 1.90991 15.7033 2.24591 15.7033 2.65991V5.92291C15.7033 6.18291 15.8443 6.40191 16.0463 6.53591C15.9273 6.61591 15.7923 6.67291 15.6383 6.67291C15.2243 6.67291 14.8883 6.33691 14.8883 5.92291V3.75691C14.0863 3.66091 13.2123 3.61191 12.2493 3.61191C11.1153 3.61191 10.1043 3.67991 9.19534 3.81691V2.65991C9.19534 2.24591 8.85934 1.90991 8.44534 1.90991C8.03134 1.90991 7.69534 2.24591 7.69534 2.65991V5.92291C7.69534 6.18291 7.83634 6.40191 8.03834 6.53591C7.91934 6.61591 7.78434 6.67291 7.63034 6.67291C7.21634 6.67291 6.88034 6.33691 6.88034 5.92291V4.39291C4.74634 5.21591 3.49434 6.74691 2.90234 9.18491H21.5973C20.9693 6.59891 19.5813 5.04691 17.2033 4.26291Z" fill="#000000"/>
                                                                                                    </svg>
                                                                                                    تاریخ سررسید:  <?= $maturity['date'] ?>
                                                                                                </div>
                                                                                                <div>
                                                                                                    <svg id="Wallet" width="18" height="18" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                        <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M17.5207 15.1719C15.6937 15.1719 14.2077 13.6859 14.2077 11.8599C14.2077 10.0329 15.6937 8.54594 17.5207 8.54594H21.7307C20.8527 4.49194 18.0597 2.96094 12.2507 2.96094C5.01471 2.96094 2.44971 5.32694 2.44971 11.9999C2.44971 18.6739 5.01471 21.0389 12.2507 21.0389C18.1927 21.0389 20.9807 19.4389 21.7887 15.1719H17.5207Z" fill="#000000"/>
                                                                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M11.9017 8.64099H7.84167C7.42767 8.64099 7.09167 8.30499 7.09167 7.89099C7.09167 7.47699 7.42767 7.14099 7.84167 7.14099H11.9017C12.3157 7.14099 12.6517 7.47699 12.6517 7.89099C12.6517 8.30499 12.3157 8.64099 11.9017 8.64099Z" fill="#000000"/>
                                                                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.5205 13.6719C16.5215 13.6719 15.7075 12.8589 15.7075 11.8599C15.7075 10.8599 16.5215 10.0459 17.5205 10.0459H21.9565C22.0155 10.6549 22.0505 11.2979 22.0505 11.9999C22.0505 12.5949 22.0225 13.1439 21.9805 13.6719H17.5205ZM18.4106 11.7998C18.4106 12.2138 18.0746 12.5498 17.6606 12.5498C17.2466 12.5498 16.9106 12.2138 16.9106 11.7998C16.9106 11.3858 17.2466 11.0498 17.6606 11.0498C18.0746 11.0498 18.4106 11.3858 18.4106 11.7998Z" fill="#000000"/>
                                                                                                    </svg>
                                                                                                    مبلغ قسط:  <?= number_format($maturity['amount']) ?>
                                                                                                </div>
                                                                                                <div>
                                                                                                    <svg id="Info Square" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.74976 12.0001C2.74976 5.06312 5.06276 2.75012 11.9998 2.75012C18.9368 2.75012 21.2498 5.06312 21.2498 12.0001C21.2498 18.9371 18.9368 21.2501 11.9998 21.2501C5.06276 21.2501 2.74976 18.9371 2.74976 12.0001Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                        <path d="M11.9998 8.10498V12" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                        <path d="M11.9955 15.5H12.0045" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                    </svg>
                                                                                                    وضعیت:  <?= $installmentStatus ?>
                                                                                                </div>
                                                                                                <?php
                                                                                                if($maturity['status'] == '1')
                                                                                                {
                                                                                                    ?>
                                                                                                    <div>
                                                                                                        <svg id="Scan" width="18" height="18" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94506 11.1146H18.7551C18.3551 7.62463 16.5251 6.33463 12.3551 6.33463C8.18506 6.33463 6.35506 7.62463 5.94506 11.1146Z" fill="#000000"/>
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M22.8151 12.6146H1.68506C1.27506 12.6146 0.935059 12.9546 0.935059 13.3646C0.935059 13.7746 1.27506 14.1146 1.68506 14.1146H2.30506C2.34506 14.7546 2.39506 15.3746 2.49506 15.9146V15.9546C3.12506 19.7146 5.09506 21.6646 8.87506 22.2946C8.91506 22.3046 8.96506 22.3046 9.00506 22.3046C9.36506 22.3046 9.67506 22.0446 9.74506 21.6746C9.80506 21.2746 9.53506 20.8846 9.12506 20.8146C5.93506 20.2846 4.49506 18.8346 3.97506 15.6646C3.96506 15.6446 3.96506 15.6346 3.96506 15.6146C3.88506 15.1646 3.84506 14.6546 3.80506 14.1146H5.90506C6.21506 17.9346 8.01506 19.3346 12.3551 19.3346C16.6951 19.3346 18.4951 17.9346 18.8051 14.1146H20.6851C20.6551 14.6646 20.6051 15.1946 20.5251 15.6646V15.7046C19.9951 18.8546 18.5451 20.2846 15.3751 20.8146C14.9651 20.8846 14.6951 21.2746 14.7651 21.6746C14.8251 22.0446 15.1351 22.3046 15.4951 22.3046C15.5451 22.3046 15.5851 22.3046 15.6251 22.2946C19.4251 21.6646 21.3951 19.6946 22.0151 15.9046V15.8646C22.1051 15.3346 22.1551 14.7346 22.1951 14.1146H22.8151C23.2351 14.1146 23.5651 13.7746 23.5651 13.3646C23.5651 12.9546 23.2351 12.6146 22.8151 12.6146Z" fill="#000000"/>
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.11006 10.0246C3.15106 10.0316 3.19206 10.0346 3.23206 10.0346C3.59306 10.0346 3.91106 9.77361 3.97106 9.40561C4.48606 6.26161 5.97906 4.76961 9.12206 4.25461C9.53106 4.18761 9.80806 3.80261 9.74106 3.39361C9.67506 2.98461 9.28606 2.70861 8.88006 2.77461C5.08306 3.39561 3.11306 5.36661 2.49106 9.16361C2.42406 9.57161 2.70106 9.95761 3.11006 10.0246Z" fill="#000000"/>
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.3801 4.255C18.5241 4.77 20.0161 6.26201 20.5311 9.405C20.5911 9.774 20.9091 10.034 21.2701 10.034C21.3111 10.034 21.3511 10.031 21.3921 10.025C21.8011 9.957 22.0781 9.571 22.0111 9.163C21.3891 5.366 19.4191 3.396 15.6221 2.775C15.2141 2.707 14.8281 2.984 14.7611 3.394C14.6941 3.803 14.9711 4.188 15.3801 4.255Z" fill="#000000"/>
                                                                                                        </svg>
                                                                                                        شماره سفارش:  <?= $maturity['payment_info']['order_id'] ?>
                                                                                                    </div>
                                                                                                    <div>
                                                                                                        <svg id="Paper" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                            <path d="M14.3053 15.4498H8.90527" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                            <path d="M12.2604 11.4385H8.90442" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.1598 8.29988L14.4898 2.89988C13.7598 2.79988 12.9398 2.74988 12.0398 2.74988C5.74978 2.74988 3.64978 5.06988 3.64978 11.9999C3.64978 18.9399 5.74978 21.2499 12.0398 21.2499C18.3398 21.2499 20.4398 18.9399 20.4398 11.9999C20.4398 10.5799 20.3498 9.34988 20.1598 8.29988Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                            <path d="M13.9342 2.83252V5.49352C13.9342 7.35152 15.4402 8.85652 17.2982 8.85652H20.2492" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                                                        </svg>
                                                                                                        شماره مرجع:  <?= $maturity['payment_info']['reference_id'] ?>
                                                                                                    </div>
                                                                                                    <div>
                                                                                                        <svg id="Calendar" width="18" height="18" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                                                            <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd" d="M21.8696 10.6843H2.63059C2.52859 11.5043 2.47559 12.3973 2.47559 13.3863C2.47559 20.6013 5.03359 23.1593 12.2496 23.1593C19.4666 23.1593 22.0246 20.6013 22.0246 13.3863C22.0246 12.3973 21.9716 11.5043 21.8696 10.6843Z" fill="#000000"/>
                                                                                                            <path d="M15.9086 13.8713C15.9086 14.2853 16.2486 14.6213 16.6626 14.6213C17.0766 14.6213 17.4126 14.2853 17.4126 13.8713C17.4126 13.4573 17.0766 13.1213 16.6626 13.1213H16.6536C16.2396 13.1213 15.9086 13.4573 15.9086 13.8713Z" fill="#000000"/>
                                                                                                            <path d="M15.9086 17.7243C15.9086 18.1383 16.2486 18.4743 16.6626 18.4743C17.0766 18.4743 17.4126 18.1383 17.4126 17.7243C17.4126 17.3093 17.0766 16.9743 16.6626 16.9743H16.6536C16.2396 16.9743 15.9086 17.3093 15.9086 17.7243Z" fill="#000000"/>
                                                                                                            <path d="M11.5086 13.8713C11.5086 14.2853 11.8496 14.6213 12.2636 14.6213C12.6776 14.6213 13.0136 14.2853 13.0136 13.8713C13.0136 13.4573 12.6776 13.1213 12.2636 13.1213H12.2546C11.8406 13.1213 11.5086 13.4573 11.5086 13.8713Z" fill="#000000"/>
                                                                                                            <path d="M11.5086 17.7243C11.5086 18.1383 11.8496 18.4743 12.2636 18.4743C12.6776 18.4743 13.0136 18.1383 13.0136 17.7243C13.0136 17.3093 12.6776 16.9743 12.2636 16.9743H12.2546C11.8406 16.9743 11.5086 17.3093 11.5086 17.7243Z" fill="#000000"/>
                                                                                                            <path d="M7.10059 13.8713C7.10059 14.2853 7.44159 14.6213 7.85559 14.6213C8.26959 14.6213 8.60559 14.2853 8.60559 13.8713C8.60559 13.4573 8.26959 13.1213 7.85559 13.1213H7.84659C7.43259 13.1213 7.10059 13.4573 7.10059 13.8713Z" fill="#000000"/>
                                                                                                            <path d="M7.10059 17.7243C7.10059 18.1383 7.44159 18.4743 7.85559 18.4743C8.26959 18.4743 8.60559 18.1383 8.60559 17.7243C8.60559 17.3093 8.26959 16.9743 7.85559 16.9743H7.84659C7.43259 16.9743 7.10059 17.3093 7.10059 17.7243Z" fill="#000000"/>
                                                                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M17.2033 4.26291V2.65991C17.2033 2.24591 16.8673 1.90991 16.4533 1.90991C16.0393 1.90991 15.7033 2.24591 15.7033 2.65991V5.92291C15.7033 6.18291 15.8443 6.40191 16.0463 6.53591C15.9273 6.61591 15.7923 6.67291 15.6383 6.67291C15.2243 6.67291 14.8883 6.33691 14.8883 5.92291V3.75691C14.0863 3.66091 13.2123 3.61191 12.2493 3.61191C11.1153 3.61191 10.1043 3.67991 9.19534 3.81691V2.65991C9.19534 2.24591 8.85934 1.90991 8.44534 1.90991C8.03134 1.90991 7.69534 2.24591 7.69534 2.65991V5.92291C7.69534 6.18291 7.83634 6.40191 8.03834 6.53591C7.91934 6.61591 7.78434 6.67291 7.63034 6.67291C7.21634 6.67291 6.88034 6.33691 6.88034 5.92291V4.39291C4.74634 5.21591 3.49434 6.74691 2.90234 9.18491H21.5973C20.9693 6.59891 19.5813 5.04691 17.2033 4.26291Z" fill="#000000"/>
                                                                                                        </svg>
                                                                                                        تاریخ پرداخت:  <?= $maturity['payment_info']['date'] ?>
                                                                                                    </div>
                                                                                                    <?php
                                                                                                }
                                                                                                ?>
                                                                                            </div>
                                                                                            <?php
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>
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
                                                            }
                                                            ?>

                                                        </div>
                                                    </li>

                                                <?php
                                            }
                                            $row++;
                                        }
                                        echo '</ul>';
                                    }
                                    ?>
                                </td>
                                <td><?= jdate('Y/m/d',intval(substr($order->_id, 0, 8), 16)) ?></td>
                                <td><?= number_format($finalAmount) ?></td>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewShare ?>">مشاهده اطلاعات پرداخت</a>
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
                                                    شماره سفارش:
                                                    <?php
                                                    if($order->payment_info['order_id'] != 'wallet')
                                                        echo $order->payment_info['order_id'];
                                                    else
                                                        echo '-';
                                                    ?>
                                                </div>
                                                <div>
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd" d="M22 8.29344C22 11.7692 19.1708 14.5869 15.6807 14.5869C15.0439 14.5869 13.5939 14.4405 12.8885 13.8551L12.0067 14.7333C11.4883 15.2496 11.6283 15.4016 11.8589 15.652C11.9551 15.7565 12.0672 15.8781 12.1537 16.0505C12.1537 16.0505 12.8885 17.075 12.1537 18.0995C11.7128 18.6849 10.4783 19.5045 9.06754 18.0995L8.77362 18.3922C8.77362 18.3922 9.65538 19.4167 8.92058 20.4412C8.4797 21.0267 7.30403 21.6121 6.27531 20.5876L5.2466 21.6121C4.54119 22.3146 3.67905 21.9048 3.33616 21.6121L2.45441 20.7339C1.63143 19.9143 2.1115 19.0264 2.45441 18.6849L10.0963 11.0743C10.0963 11.0743 9.3615 9.90338 9.3615 8.29344C9.3615 4.81767 12.1907 2 15.6807 2C19.1708 2 22 4.81767 22 8.29344Z" fill="#1C274C"/>
                                                        <path d="M17.8853 8.29353C17.8853 9.50601 16.8984 10.4889 15.681 10.4889C14.4635 10.4889 13.4766 9.50601 13.4766 8.29353C13.4766 7.08105 14.4635 6.09814 15.681 6.09814C16.8984 6.09814 17.8853 7.08105 17.8853 8.29353Z" fill="#1C274C"/>
                                                    </svg>
                                                    شماره پیگیری:
                                                    <?php
                                                    if(array_key_exists('tref', $order->payment_info))
                                                        echo $order->payment_info['tref'];
                                                    else
                                                        echo '-';
                                                    ?>
                                                </div>
                                            </div>
                                            <ul class="timeline">
                                                <?php
                                                if($order->payment_info != null)
                                                {
                                                    $paymentType = 'cache';
                                                    if(isset($order->payment_info['order_id']))
                                                        if($order->payment_info['order_id'] == 'wallet')
                                                            $paymentType = 'wallet';
                                                    if($paymentType == 'wallet')
                                                    {
                                                        ?>
                                                        <li class="timeline-item timeline-item-transparent ps-4">
                                                            <span class="timeline-point timeline-point-primary"></span>
                                                            <div class="timeline-event pb-2">
                                                                <div class="timeline-header mb-1">
                                                                    <h6 class="mb-0 mt-n1"><?= $order->shares[0]['item'] ?></h6>
                                                                    <small class="text-muted mt-1 mt-sm-0 mb-1 mb-sm-0">مبلغ پرداختی: <?= number_format(round($order->amount)).'تومان (پرداخت شده از کیف پول)' ?></small>
                                                                </div>
                                                                <div class="d-flex align-items-center me-3">
                                                                    <span class="badge badge-dot bg-success me-2"></span> سهم دانشگاه <?= number_format(round($order->amount)).' تومان' ?>
                                                                </div>
                                                            </div>
                                                        </li>
                                                            <?php
                                                    }
                                                    else
                                                    {
                                                        if($order->shares != null)
                                                        {
                                                            $i = 0;
                                                            foreach ($order->shares as $share)
                                                            {
                                                                $collegeDetail = DashboardController::college_detail($share['college']);
                                                                ?>
                                                                <li class="timeline-item timeline-item-transparent ps-4">
                                                                    <span class="timeline-point timeline-point-primary"></span>
                                                                    <div class="timeline-event pb-2">
                                                                        <div class="timeline-header mb-1">
                                                                            <h6 class="mb-0 mt-n1"><?= $share['item'] ?></h6>
                                                                            <small class="text-muted mt-1 mt-sm-0 mb-1 mb-sm-0">مبلغ پرداختی: <?= number_format($order->orders[$i]['price']).' تومان' ?></small>
                                                                        </div>
                                                                        <div class="d-flex align-items-center me-3">
                                                                            <span class="badge badge-dot bg-success me-2"></span> سهم <?= Html::encode($collegeDetail->title).': '.number_format($share['college_share']).' تومان' ?>
                                                                        </div>
                                                                        <?php
                                                                        if(array_key_exists('broker',$share))
                                                                        {
                                                                            $brokerDetail = $this->context->broker_detail($share['broker']);
                                                                            if($brokerDetail != null)
                                                                            {
                                                                                echo '<div class="d-flex align-items-center me-3">';
                                                                                echo '<span class="badge badge-dot bg-success me-2"></span> سهم کارگزار '.Html::encode($brokerDetail->connector_info['first_name'].' '.$brokerDetail->connector_info['last_name']).': '.number_format($share['broker_share']).' تومان</p>';
                                                                                echo '</div>';
                                                                            }
                                                                        }
                                                                        ?>
                                                                    </div>
                                                                </li>
                                                                <?php
                                                                $i++;
                                                            }
                                                        }
                                                    }
                                                }
                                                ?>
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
                        <div class="alert alert-danger" role="alert">تا کنون پرداختی ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>
