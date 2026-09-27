<?php
$this->title = 'مدیریت درخواست های انصراف';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use common\models\Admin;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Admin();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("ثبت درخواست انجام پذیرفت", {
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
    toastr.danger("رد درخواست انجام پذیرفت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("کارمند مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور کارمند مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت کارمند مرود نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$url = Yii::$app->urlManager->createAbsoluteUrl('courses/show_course_users', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('change','#college',function(e) {
    e.preventDefault();
    var college = $(this).val() 
    if(college == 0)
        {
            document.getElementById("zkh").style.display = 'block';
            document.getElementById("zkh2").style.display = 'block';
        }
    else 
        {
            document.getElementById("zkh").style.display = 'none';
            document.getElementById("zkh2").style.display = 'none';
        }
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

    .summary
    {
        display: none;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        درخواست های انصراف
    </h4>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">درخواست های ثبت شده</h5>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>دانشپذیر</th>
                    <th>دوره</th>
                    <th>وضعیت</th>
                    <th>ثبت کننده</th>
                    <?php
                    if(Yii::$app->user->identity->role == 'user')
                        echo '<th>دانشکده</th>';
                    ?>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $request)
                {
                    $collegeDetail = $this->context->college_detail($request->college);
                    $user = $this->context->user_detail($request->username);
                    $course = $this->context->course_detail($request->course_id);
                    $registrant = $this->context->registrant_detail($request->registrant, $request->registrant_role);
                    $order = $this->context->order_detail($request->order_id);
                    $accept = 'accept'.rand();
                    $reject = 'reject'.rand();
                    $requestStatus = 'بررسی نشده';
                    $bg = 'bg-warning';
                    $editedBy = null;
                    $whoEdited = '';
                    if($request->edited_by != null)
                        $whoEdited = $this->context->who_edited($request->edited_by);
                    if($request->status == 'approved')
                    {
                        $requestStatus = 'تائید شده';
                        $bg = 'bg-success';
                    }
                    else if($request->status == 'rejected')
                    {
                        $requestStatus = 'رد شده';
                        $bg = 'bg-danger';
                    }
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center <?= $bg ?>"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                        <td><?= Html::encode($user->first_name.' '.$user->last_name).'<hr>'.Html::encode($request->username) ?></td>
                        <td>
                            <?php
                            if(strlen($course->title['main_fa']) <= 30)
                                echo $course->title['main_fa'];
                            else
                                echo '<button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="'.Html::encode($course->title['main_fa']).'">'.Html::encode(substr($course->title['main_fa'],0,27)).'...'.'</button>';
                            ?>
                        </td>
                        <td>
                            <?php
                            echo $requestStatus;
                            if($request->status != '0')
                                if($whoEdited != null)
                                    echo '<hr>توسط '.Html::encode($whoEdited->first_name.' '.$whoEdited->last_name);
                            ?>
                        </td>
                        <td>
                            <?php
                            if($registrant != null)
                            {
                                $registrant = explode('-', $registrant);
                                if($registrant != null)
                                    echo Html::encode($registrant[0]).'<hr>'.Html::encode($registrant['1']);
                                else
                                    echo 'نامشخص';
                            }
                            else
                                echo 'نامشخص';
                            ?>
                        </td>
                        <?php
                        if(Yii::$app->user->identity->role == 'user')
                        {
                            if(strlen($collegeDetail->title) <= 30)
                                echo '<td>'.Html::encode($collegeDetail->title).'</td>';
                            else
                                echo '<td><button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="'.Html::encode($collegeDetail->title).'">'.substr(Html::encode($collegeDetail->title),0,27).'...'.'</button></td>';
                        }
                        ?>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <?php
                                if($request->status == '0' || $request->status == 'rejected')
                                    echo '<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#'.$accept.'">تائید درخواست</a>';
                                if($request->status == '0')
                                    echo '<a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#'.$reject.'">رد درخواست</a>';
                                ?>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $accept ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">موافقت با درخواست انصراف </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['accept'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($request, '_id')->hiddenInput()->label(false); ?>
                                    <p>آیا با درخواست انصراف <?= Html::encode($user->first_name.' '.$user->last_name) ?> موافقت می کنید؟</p>
                                    <p>با قبول درخواست مبلغ <?= Html::encode(number_format($order->shares[0]['college_share'])) ?> تومان به حساب کیف پول کارگزار واریز می گردد.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">بله موافقم</button>
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
