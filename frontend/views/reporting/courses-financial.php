<?php
$this->title = 'تراکنش های صورت حساب مالی';

use yii\helpers\Html;
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
                <a href="javascript:void(0);">تراکنش های صورت حساب مالی</a>
            </li>
        </ol>
    </nav>
    <?php
    echo $this->render('___search', [
        'model' => $searchModel,
        'colleges' => $colleges,
        'brokers' => $brokers,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">تراکنش های موفق</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th class="text-center">ثبت کننده</th>
                    <th class="text-center">تاریخ پرداخت</th>
                    <th class="text-center">مقدار (تومان)</th>
                    <th class="text-center">دوره</th>
                    <th class="text-center"> کد رهگیری</th>
                    <?php if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') echo '<th class="text-center">دانشکده</th>'; ?>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $transaction)
                {
                    $registrant = $this->context->registrant($transaction->registrant);
                    $collegeDetail = $this->context->college_detail($transaction->college);
                    $course = $this->context->course_detail($transaction->course_id);
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center rounded-pill success"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                        <td><?= Html::encode($registrant) ?></td>
                        <td><?= $transaction->payment_info['date'] ?></td>
                        <td><?= number_format($transaction->amount) ?></td>
                        <td>
                            <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $course->title['main_fa'] ?>">
                                <?= Html::encode(substr($course->title['main_fa'], 0, 27)) . '...' ?>
                            </button>
                        </td>
                        <td>
                            <?php
                            $tref = '-';
                            if($transaction->payment_info != null)
                                if(array_key_exists('tref',$transaction->payment_info))
                                    $tref = $transaction->payment_info['tref'];
                            echo $tref;
                            ?>
                        </td>
                        <?php
                        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                        {
                            if (strlen($collegeDetail->title) <= 30)
                                echo '<td>' . Html::encode($collegeDetail->title) . '</td>';
                            else {
                                ?>
                                <td>
                                    <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= Html::encode($collegeDetail->title) ?>">
                                        <?= Html::encode(substr($collegeDetail->title, 0, 27)) . '...' ?>
                                    </button>
                                </td>
                                <?php
                            }
                        }
                        ?>
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
        </div>
    </div>
</div>
