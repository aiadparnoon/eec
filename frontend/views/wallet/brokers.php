<?php
$this->title = 'تراکنش های کیف پول';

use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\WalletTransactions;
use yii\widgets\ListView;
date_default_timezone_set("Asia/Tehran");
require_once(Yii::$app->basePath . '/web/jdf.php');
Select2Asset::register($this);
$model = new WalletTransactions();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("مبلغ مورد نظر به کیف پول اضافه گردید", {
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
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.error("خطایی در دریافت توکن بانک رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.error("خطایی در پرداخت شما رخ داده است. اگر مبلغی از حساب شما کسر گردیده حداکثر تا ۷۲ ساعت به حساب شما عودت داده خواهد شد", {
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
                <a href="javascript:void(0);">تراکنش های کیف پول</a>
            </li>
        </ol>
    </nav>
    <?php
    echo $this->render('__search', [
        'model' => $searchModel,
        'colleges' => $colleges,
        'brokers' => $brokers
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">تراکنش های موفق</h5>
            <?php
            if(Yii::$app->user->identity->role == 'broker')
            {
                $wallet = $this->context->wallet(Yii::$app->user->identity->username);
                echo '<h5 class="card-title mb-0">'.number_format($wallet).' تومان موجودی کیف پول</h5>';
            }
            ?>
            <div class="btn-group">
                <?php
                if(Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker')
                {
                    ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#increase_wallet">
                        افزایش کیف پول
                    </button>
                    <div class="modal fade" id="increase_wallet" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن کیف پول</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $form = ActiveForm::begin(
                                        [
                                            'action' => ['increase_wallet'],
                                            "method" => "post",
                                        ]
                                    ); ?>

                                    <div class="row">
                                        <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">مبلغ (تومان) * </label>
                                            <?= $form->field($model, 'amount')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا مبلغ را صحیح وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'onkeypress' => 'return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)'
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <?php
                                        if(Yii::$app->user->identity->role == 'emp')
                                        {
                                            ?>
                                            <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">کارگزار * </label>
                                                <?= $form->field($model, 'broker_id')->dropDownList(
                                                    $brokers,
                                                    [
                                                        'class' => 'select2 form-select text-start',
                                                        'required' => true,
                                                        'prompt' => 'لطفا کارگزار را انتخاب کنید',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">پرداخت مبلغ</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
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
                    <th class="text-center">واریز/برداشت کننده</th>
                    <th class="text-center">شماره همراه</th>
                    <th class="text-center">مقدار (تومان)</th>
                    <th class="text-center">تاریخ / کد رهگیری</th>
                    <?php if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp') echo '<th class="text-center">دانشکده</th>'; ?>
                    <th class="text-center">نوع</th>
                    <th class="text-center">توضیحات</th>
                    <th class="text-center">عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $transaction)
                {
                    $broker = $this->context->broker_detail($transaction->broker_id);
                    $college = $this->context->college_detail($transaction->college);
                    $viewTransaction = 'viewTransaction'.rand();
                    $type = 'پرداخت از لینک';
                    $statusIcon = 'badge bg-label-success';
                    $typeStatus = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
	<g fill="none" stroke="#37b10c" stroke-width="2.5">
		<path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12Z" />
		<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v7m3.5-4.5S12.922 8 12 8s-3.5 3.5-3.5 3.5" />
	</g>
</svg>';
                    if($transaction->type == '2')
                        $type = 'پرداخت از وب سرویس';
                    else if($transaction->type == '3')
                    {
                        $type = 'افزودن دانشپذیر';
                        $typeStatus = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
	<g fill="none" stroke="#b1410c" stroke-width="2.5">
		<path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12Z" />
		<path stroke-linecap="round" stroke-linejoin="round" d="M12 15V8m3.5 4.5S12.922 16 12 16s-3.5-3.5-3.5-3.5" />
	</g>
</svg>';
                        $statusIcon = 'badge bg-label-danger';
                    }
                    else if($transaction->type == '4')
                        $type = 'تائید صورت حساب مالی';
                    else if($transaction->type == '5')
                        $type = 'پرداخت مستقیم';
                    else if($transaction->type == '6')
                        $type = 'انصراف دانشپذیر';
                    else if($transaction->type == '7')
                        $type = 'واریز دستی';
                    else if($transaction->type == '8')
                    {
                        $type = 'برداشت دستی';
                        $typeStatus = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
	<g fill="none" stroke="#b1410c" stroke-width="2.5">
		<path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12Z" />
		<path stroke-linecap="round" stroke-linejoin="round" d="M12 15V8m3.5 4.5S12.922 16 12 16s-3.5-3.5-3.5-3.5" />
	</g>
</svg>';
                    }
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center rounded-pill <?= $statusIcon ?>"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                        <td>
                            <?php
                            if($transaction->type == '6' || $transaction->type == '3')
                                echo 'سامانه';
                            else
                            {
                                $payerName = null;
                                $payerMobile = null;
                                if($transaction->payer != null)
                                {
                                    if(is_array($transaction->payer))
                                    {
                                        if(array_key_exists('first_name', $transaction->payer) && array_key_exists('last_name', $transaction->payer))
                                            $payerName = $transaction->payer['first_name'].' '.$transaction->payer['last_name'];
                                        if(array_key_exists('mobile', $transaction->payer))
                                            $payerMobile = $transaction->payer['mobile'];
                                    }
                                }
                                if($payerName !== null)
                                    echo $payerName;
                                else
                                    echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $payerMobile = null;
                            if($transaction->payer != null)
                            {
                                if(is_array($transaction->payer))
                                    if(array_key_exists('mobile', $transaction->payer))
                                        $payerMobile = $transaction->payer['mobile'];
                            }
                            if($payerMobile !== null)
                                echo $payerMobile;
                            else
                                echo '-';
                            ?>
                        </td>
                        <td class="demo-inline-spacing text-wrap w-25">
                            <?= number_format($transaction->amount) ?>
                            <?= $typeStatus ?>
                        </td>
                        <td>
                            <?php
                            echo $transaction->date;
                            if($transaction->payment_info != null)
                                if(array_key_exists('tref',$transaction->payment_info))
                                    echo '<hr>'.$transaction->payment_info['tref'];
                            ?>
                        </td>
                        <td>
                            <?php
                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp')
                            {
                                if($broker != null)
                                    echo $broker->connector_info['first_name'] . ' ' . $broker->connector_info['last_name'];
                                else
                                    echo '-';
                            }
                            ?>
                        </td>
                        <td class="demo-inline-spacing text-wrap w-25"><?= $type ?></td>
                        <td>
                            <?php
                            if($transaction->type == '6')
                            {
                                $reference = $this->context->canceling_request($transaction->reference_id);
                                $text = 'انصراف دانشپذیر '.$reference;
                                echo '<button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="'.$text.'">'.substr($text,0,27).'...'.'</button>';
                            }
                            else
                            {
                                if($transaction->description != null)
                                {
                                    if(strlen($transaction->description) <= 30)
                                        echo $transaction->description;
                                    else
                                        echo '<button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="'.$transaction->description.'">'.substr($transaction->description,0,27).'...'.'</button>';
                                }
                                else
                                    echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewTransaction ?>">مشاهده جزئیات پرداخت</a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $viewTransaction ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده جزئیات پرداخت</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div>
                                        <svg id="Scan" width="18" height="18" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94506 11.1146H18.7551C18.3551 7.62463 16.5251 6.33463 12.3551 6.33463C8.18506 6.33463 6.35506 7.62463 5.94506 11.1146Z" fill="#000000"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M22.8151 12.6146H1.68506C1.27506 12.6146 0.935059 12.9546 0.935059 13.3646C0.935059 13.7746 1.27506 14.1146 1.68506 14.1146H2.30506C2.34506 14.7546 2.39506 15.3746 2.49506 15.9146V15.9546C3.12506 19.7146 5.09506 21.6646 8.87506 22.2946C8.91506 22.3046 8.96506 22.3046 9.00506 22.3046C9.36506 22.3046 9.67506 22.0446 9.74506 21.6746C9.80506 21.2746 9.53506 20.8846 9.12506 20.8146C5.93506 20.2846 4.49506 18.8346 3.97506 15.6646C3.96506 15.6446 3.96506 15.6346 3.96506 15.6146C3.88506 15.1646 3.84506 14.6546 3.80506 14.1146H5.90506C6.21506 17.9346 8.01506 19.3346 12.3551 19.3346C16.6951 19.3346 18.4951 17.9346 18.8051 14.1146H20.6851C20.6551 14.6646 20.6051 15.1946 20.5251 15.6646V15.7046C19.9951 18.8546 18.5451 20.2846 15.3751 20.8146C14.9651 20.8846 14.6951 21.2746 14.7651 21.6746C14.8251 22.0446 15.1351 22.3046 15.4951 22.3046C15.5451 22.3046 15.5851 22.3046 15.6251 22.2946C19.4251 21.6646 21.3951 19.6946 22.0151 15.9046V15.8646C22.1051 15.3346 22.1551 14.7346 22.1951 14.1146H22.8151C23.2351 14.1146 23.5651 13.7746 23.5651 13.3646C23.5651 12.9546 23.2351 12.6146 22.8151 12.6146Z" fill="#000000"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.11006 10.0246C3.15106 10.0316 3.19206 10.0346 3.23206 10.0346C3.59306 10.0346 3.91106 9.77361 3.97106 9.40561C4.48606 6.26161 5.97906 4.76961 9.12206 4.25461C9.53106 4.18761 9.80806 3.80261 9.74106 3.39361C9.67506 2.98461 9.28606 2.70861 8.88006 2.77461C5.08306 3.39561 3.11306 5.36661 2.49106 9.16361C2.42406 9.57161 2.70106 9.95761 3.11006 10.0246Z" fill="#000000"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M15.3801 4.255C18.5241 4.77 20.0161 6.26201 20.5311 9.405C20.5911 9.774 20.9091 10.034 21.2701 10.034C21.3111 10.034 21.3511 10.031 21.3921 10.025C21.8011 9.957 22.0781 9.571 22.0111 9.163C21.3891 5.366 19.4191 3.396 15.6221 2.775C15.2141 2.707 14.8281 2.984 14.7611 3.394C14.6941 3.803 14.9711 4.188 15.3801 4.255Z" fill="#000000"/>
                                        </svg>
                                        شماره پیگیری:  <?= $transaction->payment_info['tref'] ?>
                                    </div>
                                    <div>
                                        <svg id="Paper" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.3053 15.4498H8.90527" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M12.2604 11.4385H8.90442" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.1598 8.29988L14.4898 2.89988C13.7598 2.79988 12.9398 2.74988 12.0398 2.74988C5.74978 2.74988 3.64978 5.06988 3.64978 11.9999C3.64978 18.9399 5.74978 21.2499 12.0398 21.2499C18.3398 21.2499 20.4398 18.9399 20.4398 11.9999C20.4398 10.5799 20.3498 9.34988 20.1598 8.29988Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M13.9342 2.83252V5.49352C13.9342 7.35152 15.4402 8.85652 17.2982 8.85652H20.2492" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        شماره سفارش:  <?= $transaction->payment_info['order_id'] ?>
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
                                        تاریخ پرداخت:  <?= $transaction->payment_info['date'] ?>
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
