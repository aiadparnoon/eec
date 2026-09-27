<?php
$this->title = 'گزارش پرداخت اقساط';

use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use frontend\controllers;
use yii\widgets\ListView;
date_default_timezone_set("Asia/Tehran");
require_once(Yii::$app->basePath . '/web/jdf.php');
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
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">پرداخت اقساط</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('__search', [
        'model' => $searchModel,
        'colleges' => $colleges,
        'brokers' => $brokers,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">لیست اقساط</h5>
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
                        <th>دوره</th>
                        <th>اقساط</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    <?php
                    foreach ($dataProvider->models as $installment)
                    {
                        $viewShare = 'viewShare'.rand();
                        $userDetail = $this->context->user_detail($installment->username);
                        $courseDetail = $this->context->course_detail($installment->course_id);
                        ?>
                        <tr>
                            <th scope="row"><?= $dataProvider->pagination->page * 50 + $c++ ?></th>
                            <td class="text-wrap w-25">
                                <?php
                                if($userDetail != null)
                                    echo Html::encode($userDetail->first_name.' '.$userDetail->last_name.'('.$installment['username'].')');
                                else
                                    echo 'نامشخص';
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= Html::encode($courseDetail->title['main_fa']) ?>">
                                    <?= Html::encode(substr($courseDetail->title['main_fa'], 0, 27)) . '...' ?>
                                </button>
                            </td>
                            <td>
                                <?php
                                if($installment->maturities != null)
                                {
                                    ?>
                                    <ul class="timeline mt-3">
                                        <?php
                                        foreach ($installment->maturities as $maturity)
                                            {
                                                $statusBg = 'timeline-point-danger';
                                                $status = 'پرداخت نشده';
                                                if($maturity['status'] == '1')
                                                {
                                                    $statusBg = 'timeline-point-success';
                                                    $status = 'پرداخت شده';
                                                }
                                                ?>
                                                <li class="timeline-item timeline-item-transparent">
                                                    <span class="timeline-point <?= $statusBg ?>"></span>
                                                    <div class="timeline-event">
                                                        <div class="timeline-header border-bottom mb-3 mt-n1">
                                                            <h6 class="mb-1 mb-sm-2"><?= number_format($maturity['amount']) ?></h6>
                                                            <small class="text-muted mt-1 mt-sm-0 mb-2 mb-sm-0">سررسید: <?= $maturity['date'] ?></small>
                                                        </div>
                                                        <?php
                                                        if($maturity['status'] == '1')
                                                        {
                                                            if(array_key_exists('payment_info', $maturity))
                                                            {
                                                                ?>
                                                                <div class="d-flex justify-content-between flex-wrap mb-2 lh-1-85">
                                                                    <div>
                                                                        <span>تاریخ پرداخت</span>
                                                                        <i class="bx bx-right-arrow-alt scaleX-n1-rtl mx-3"></i>
                                                                        <span><?= $maturity['payment_info']['date'] ?></span>
                                                                    </div>
                                                                </div>
                                                        <?php
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </li>
                                        <?php
                                            }
                                        ?>
                                    </ul>
                                <?php
                                }
                                ?>
                            </td>
                        </tr>
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
