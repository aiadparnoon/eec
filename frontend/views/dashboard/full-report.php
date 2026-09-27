<?php
$this->title = 'گزارش کامل عملکرد مالی';
?>
<div class="card">
    <h5 class="card-header heading-color">گزارش کامل عملکرد مالی سامانه</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>عنوان</th>
                <th>سهم دانشکده ها</th>
                <th>سهم کارگزاران</th>
                <th>مبلغ (تومان)</th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0">
            <tr>
                <td>مبلغ کل پرداخت شده توسط دانشپذیران</td>
                <td><?= number_format($finalCollege) ?></td>
                <td><?= number_format($finalBroker) ?></td>
                <td><?= number_format($finalAmount) ?></td>
            </tr>
            <tr>
                <td>مبلغ کل اقساط پرداخت شده توسط دانشپذیران</td>
                <td><?= number_format($collegeInstallment) ?></td>
                <td><?= number_format($brokerAmount) ?></td>
                <td><?= number_format($totalInstallment) ?></td>
            </tr>
            <tr>
                <td>مبلغ کل واریز شده به کیف پول</td>
                <td><?= number_format($finalWallet) ?></td>
                <td><?= number_format(0) ?></td>
                <td><?= number_format($finalWallet) ?></td>
            </tr>
            <tr>
                <td>مبلغ کل واریز شده صورت حساب مالی</td>
                <td><?= number_format($finalCoursesFinancial) ?></td>
                <td><?= number_format(0) ?></td>
                <td><?= number_format($finalCoursesFinancial) ?></td>
            </tr>
            <tr>
                <td colspan="1">مجموع پرداختی</td>
                <td colspan="3" align="center"><?= number_format($totalInstallment + $finalAmount + $finalWallet + $finalCoursesFinancial) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
