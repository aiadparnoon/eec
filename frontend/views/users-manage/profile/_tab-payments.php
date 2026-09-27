<?php
/**
 * تب «پرداختی‌ها»: همه‌ی سفارش‌ها و اقساط در همه‌ی دوره‌ها.
 *
 * @var $this yii\web\View
 * @var $profile app\components\StudentProfile
 */
use app\components\StudentProfile;
use app\components\UsersImport;

$orders = $profile->orders();
$installments = $profile->installments();
$successful = 0;
foreach ($orders as $order)
    if ((string) $order->status === '1' && !$order->is_canceled)
        $successful++;
$overdue = 0;
foreach ($installments as $installment)
    if (is_array($installment->maturities))
        foreach ($installment->maturities as $m)
            if (StudentProfile::maturityState($m) === 'overdue')
                $overdue++;
?>
<div class="card">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="border rounded p-3">
                    <small class="text-muted d-block">جمع پرداختی‌ها</small>
                    <h5 class="mb-0"><?= UsersImport::faDigits(number_format($profile->totalPaid())) ?> <small class="text-muted">تومان</small></h5>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border rounded p-3">
                    <small class="text-muted d-block">سفارش موفق</small>
                    <h5 class="mb-0"><?= UsersImport::faDigits($successful) ?></h5>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border rounded p-3">
                    <small class="text-muted d-block">قسط سررسید گذشته</small>
                    <h5 class="mb-0 <?= $overdue ? 'text-danger' : '' ?>"><?= UsersImport::faDigits($overdue) ?></h5>
                </div>
            </div>
        </div>
        <?= $this->render('_finance', ['profile' => $profile, 'orders' => $orders, 'installments' => $installments, 'showCourse' => true]) ?>
    </div>
</div>
