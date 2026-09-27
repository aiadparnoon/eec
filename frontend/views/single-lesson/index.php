<?php
$this->title = 'دروس دوره';
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light">مدیریت دوره /</span> دروس دوره
    </h4>


    <!-- Responsive Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">دروس دوره ثبت شده</h5>
            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('create-single-course') ?>" class="btn btn-primary">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت درس دوره جدید
            </a>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            if($dataProvider->models != null)
            {
                ?>
                <table class="table">
                    <thead>
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                        <th>سرتیتر جدول</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    <tr>
                        <th scope="row">1</th>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                    </tr>
                    <tr>
                        <th scope="row">2</th>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                    </tr>
                    <tr>
                        <th scope="row">3</th>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                        <td>سلول جدول</td>
                    </tr>
                    </tbody>
                </table>
            <?php
            }
            else
            {
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">تا کنون دوره ای ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
    <!--/ Responsive Table -->
</div>