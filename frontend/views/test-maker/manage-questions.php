<?php
$this->title = 'مدیریت آزمون';

use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Tests;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Tests();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("سوالات مورد نظر به آزمون اضافه گردید", {
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
    toastr.success("سوال مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$url = Yii::$app->urlManager->createAbsoluteUrl('test-maker/group_questions', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$ajax = <<<JS
$(document).on('change','#groups',function(e) {
    e.preventDefault();
    var id = $(this).val();
    var exam_id = $('#exam_id').val();

    $.ajax({
        url:'$url',
        type:'POST',
        data:{id:id, exam_id:exam_id, _csrf: yii.getCsrfToken()},
        success:function(data) {
           $('#questions').html(data);
        }
    });

})
JS;
$this->registerJs($ajax);
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
                <a href="javascript:void(0);">سایر موارد</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">آزمون ساز</a>
            </li>
            <li class="breadcrumb-item active">مدیریت سوالات آزمون</li>
        </ol>
    </nav>
<!--    --><?php //echo $this->render('_search', [
//        'model' => $searchModel,
//    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سوالات ثبت شده آزمون <?= $examDetails->title ?></h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>افزودن سوال جدید
            </button>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            ?>
            <table class="table">
                <thead>
                <tr class="text-nowrap">
                    <th>#</th>
                    <th>متن سوال</th>
                    <th>گروه سوال</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                if($examDetails->questions != null)
                {
                    $counter = 1;
                    $i = 0;
                    foreach ($examDetails->questions as $question)
                    {
                        $edit = 'edit'.rand();
                        $delete = 'delete'.rand();
                        $questionDetail = $this->context->question_detail($question);
                        $groupDetail = $this->context->group_detail($questionDetail->group);
                        ?>
                        <tr>
                            <th scope="row"><span class="badge badge-center bg-primary"><?= $counter++ ?></span></th>
                            <td><?= $questionDetail->question_text ?></td>
                            <td><?= $groupDetail->title ?></td>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $delete ?>">حذف سوال</a>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $delete ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف سوال</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['delete_question'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <input type="hidden" name="_id" value="<?= $examDetails->_id ?>">
                                        <input type="hidden" name="row" value="<?= $i ?>">
                                        <div class="row">
                                            <div class="alert alert-danger" role="alert">آیا از حذف سوال با متن <?= $questionDetail->question_text ?> مطمئن هستید؟</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $i++;
                    }
                }
                else
                {
                    ?>
                    <tr>
                        <td colspan="4" align="center">
                            <div class="alert alert-warning" role="alert"> تا کنون سوالی در این آزمون ثبت نشده است</div>
                        </td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
            <div class="demo-inline-spacing">
<!--                <nav aria-label="Page navigation">-->
<!--                    --><?php //=
//                    ListView::widget([
//                        'dataProvider' => $dataProvider,
//                        'emptyText' => '<div class="card">
//                    <div class="card-body">
//                        <div class="alert alert-danger" role="alert">نتیجه ای یافت نشد</div>
//                    </div>
//                </div>',
//                        'pager' => [
//                            'prevPageLabel' => ' <i class="tf-icon bx bx-chevrons-left"></i>',
//                            'nextPageLabel' => ' <i class="tf-icon bx bx-chevrons-right"></i>',
//                            'maxButtonCount' => 10,
//
//                            'options' => [
//                                'tag' => 'ul',
//                                'class' => 'pagination justify-content-center',
//                                'id' => 'pager-container',
//                            ],
//                            'linkOptions' => ['class' => 'page-item page-link'],
//                            'activePageCssClass' => 'page-item active',
//                            'disabledPageCssClass' => 'disable',
//                            'prevPageCssClass' => 'paginate_button page-item previous',
//                            'nextPageCssClass' => 'paginate_button page-item next',
//                        ],
//                    ]);
//                    ?>
<!--                </nav>-->
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenter">ثبت سوال جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add_question_to_test'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <input type="hidden" id="exam_id" name="_id" value="<?= (string) $examDetails->_id ?>">
                <div class="row">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="select2Basic" class="form-label">گروه سوالات *</label>
                            <?php
                            echo $form->field($model, 'group')->dropDownList(
                                $groups,
                                [
                                    'prompt' => 'لطفا گروه را مشخص کنید',
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'id' => 'groups',
                                    'data-allow-clear' => true,
//                                    'onchange' => '
//                                                                                        $.get( "' . Url::toRoute('/test-maker/group_questions') . '", { id: $(this).val() } )
//                                                                                        .done(function( data ) {
//                                                                                           $(\'#questions\').html(data);
//                                                                                        }
//                                                                                    );'
                                ]
                            )->label(false);
                            ?>
                        </div>
                    <div class="row" id="questions">

                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ثبت  و مدیریت سوالات</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>