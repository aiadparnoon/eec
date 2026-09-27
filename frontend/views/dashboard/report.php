<?php

use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;

$this->title = 'گزارش مالی';
SingleAsset::register($this);
Select2Asset::register($this);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">داشبورد</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">گزارش مالی</a>
            </li>
        </ol>
    </nav>
    <div class="card mb-4">
        <h5 class="card-header heading-color">گزارش مالی</h5>
        <?php $form = ActiveForm::begin(
            [
                'action' => ['report'],
                "method" => "post",
                'options' => [
                    'class' => 'card-body',
                    'enctype' => 'multipart/form-data'
                ],
            ]
        ); ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">تاریخ شروع گزارش</label>
                    <input type="text" name="start_date" class="form-control dob-picker text-start" dir="ltr">
                </div>
                <div class="col-md-4">
                    <label class="form-label">تاریخ اتمام گزارش</label>
                    <div class="input-group input-group-merge">
                        <input name="end_date" type="text" class="form-control dob-picker text-start" dir="ltr">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">دانشکده</label>
                    <div class="input-group input-group-merge">
                        <select name="college" id="select2Basic" class="select2 form-select form-select-lg" data-allow-clear="true">
                            <?php
                            foreach ($colleges as $item)
                                echo '<option value="'.(string) $item->_id.'">'.$item->title.'</option>';
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="pt-4">
                <button type="submit" class="btn btn-primary me-sm-3 me-1">دانلود فایل اکسل</button>
            </div>
        <?php ActiveForm::end(); ?>
        <div class="col-md-12 col-lg-12 col-xl-12 col-xxl-12 mb-4">
            <?php
            if($totalOrderCollegeShare != null)
            {
                ?>
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">آمار
                            <?php
                            if($college != null)
                            {
                                $collegeDetail = $this->context->college_detail($college);
                                echo $collegeDetail->title;
                            }
                            else
                                echo 'تمامی دانشکده ها ';
                            echo 'از تاریخ '.str_replace('-','/',$start_date).' تا تاریخ '.str_replace('-','/',$end_date);
                            ?>
                            <a href="<?= $downloadUrl ?>">دانلود فایل اکسل</a>
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="p-0 m-0">
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>مجموع تمامی پرداخت ها</span>
                                        <span class="text-muted"><?= number_format($totalIncome) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>مجموع تمامی دوره های خریداری شده</span>
                                        <span class="text-muted"><?= number_format($totalOrder) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>سهم دانشکده از دوره های خریداری شده</span>
                                        <span class="text-muted"><?= number_format($totalOrderCollegeShare) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>سهم کارگزار از دوره های خریداری شده</span>
                                        <span class="text-muted"><?= number_format($totalOrderBrokerShare) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>مجموع اقساط پرداخت شده</span>
                                        <span class="text-muted"><?= number_format($totalInstallment) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>سهم دانشکده از اقساط پرداخت شده</span>
                                        <span class="text-muted"><?= number_format($totalINSCollegeShare) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>سهم کارگزار از اقساط پرداخت شده</span>
                                        <span class="text-muted"><?= number_format($totalINSBrokerShare) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>مجموع پرداختی در کیف پول</span>
                                        <span class="text-muted"><?= number_format($totalWalletTRX) ?></span>
                                    </div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-4 pb-2">
                                <div class="avatar avatar-sm flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-cube"></i></span>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>مجموع صورت حساب های مالی پرداخت شده</span>
                                        <span class="text-muted"><?= number_format($totalFiCourse) ?></span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<?php if (!empty($downloadUrl)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // تاخیر ۱ ثانیه برای بارگذاری کامل صفحه
            setTimeout(function() {
                // ایجاد یک iframe مخفی برای دانلود
                var iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = '<?= $downloadUrl ?>';
                document.body.appendChild(iframe);

            }, 1000);
        });
    </script>
<?php endif; ?>