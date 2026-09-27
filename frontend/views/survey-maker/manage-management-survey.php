<?php
$this->title = 'مدیریت نظرسنجی';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Exams;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Exams();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("سوال مورد نظر ثبت گردید", {
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
    toastr.danger("عنوان سوال وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("سوال مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("سوال مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت نظرسنجی مرود نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$answer = array(
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4'
);
$type = array(
    '1' => 'چند گزینه ای',
    '2' => 'تک گزینه ای',
);

$req = array(
    '1' => 'بله',
    '2' => 'خیر',
);

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
                <a href="javascript:void(0);">نظرسنجی ساز</a>
            </li>
            <li class="breadcrumb-item active">مدیریت سوالات نظرسنجی</li>
        </ol>
    </nav>
    <!--    --><?php //echo $this->render('_search', [
    //        'model' => $searchModel,
    //    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سوالات ثبت شده نظرسنجی <?= $examDetails->title ?></h5>
            <div class="btn-group" role="group">
                <button id="btnGroupDrop1" type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    ثبت سوال جدید
                </button>
                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#modalCenter">سوال تستی</a>
                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#modalCenter1">سوال تشریحی</a>
                </div>
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
                    <th>متن سوال</th>
                    <th>نوع پاسخ</th>
                    <th>اجبار در پاسخ</th>
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
                        ?>
                        <tr>
                            <th scope="row"><span class="badge badge-center bg-primary"><?= $counter++ ?></span></th>
                            <td><?= Html::encode($question['question_text']) ?></td>
                            <td>
                                <?php
                                if($question['type'] == '1')
                                    echo 'تک گزینه ای';
                                else if($question['type'] == '2')
                                    echo 'چند گزینه ای';
                                else if($question['type'] == '3')
                                    echo 'تشریحی';
                                ?>
                            </td>
                            <td>
                                <?php
                                if($question['required'] == '1')
                                    echo 'اجباری';
                                else if($question['required'] == '2')
                                    echo 'اختیاری';
                                ?>
                            </td>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش سوال</a>
                                    <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $delete ?>">حذف سوال</a>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش سوال</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['edit_s_question'],
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
                                            <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                <label for="nameWithTitle" class="form-label">متن سوال *</label>
                                                <?= $form->field($examDetails, 'questions['.$i.'][question_text]')->textarea(
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا متن سوال را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">نوع پاسخ *</label>
                                                <?= $form->field($examDetails, 'questions['.$i.'][type]')->dropDownList(
                                                    $type,
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="nameWithTitle" class="form-label">اجبار در پاسخ *</label>
                                                <?= $form->field($examDetails, 'questions['.$i.'][required]')->dropDownList(
                                                    $req,
                                                    [
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                    ]
                                                )->label(false); ?>
                                            </div>
                                            <?php
                                            if($question['options'] != null)
                                            {
                                                if(count($question['options']) > 0)
                                                {
                                                    $j = 0;
                                                    $k = 1;
                                                    foreach ($question['options'] as $option)
                                                    {
                                                        ?>
                                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                            <label for="nameWithTitle" class="form-label">متن گزینه <?= $this->context->digit2word($k) ?> *</label>
                                                            <?= $form->field($examDetails, 'questions['.$i.'][options]['.$j.'][title]')->textarea(
                                                                [
                                                                    'class' => 'form-control text-start',
                                                                    'required' => true,
                                                                    'oninvalid' => 'this.setCustomValidity(\'برای متن سوال را وارد کنید\')',
                                                                    'oninput' => 'setCustomValidity(\'\')',
                                                                ]
                                                            )->label(false); ?>
                                                        </div>
                                            <?php
                                                        $k++;
                                                        $j++;
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary">ویرایش سوال</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                            <div class="alert alert-danger" role="alert">آیا از حذف سوال با متن <?= Html::encode($question['question_text']) ?> مطمئن هستید؟</div>
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
    <div class="modal-dialog  modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سوال تستی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                $form = ActiveForm::begin(
                    [
                        'action' => ['new_s_question'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <?= $form->field($examDetails, '_id')->hiddenInput()->label(false); ?>
                <div class="row">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان *</label>
                        <?= $form->field($examDetails, 'questions[questions_text]')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان سوال  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">نوع پاسخ *</label>
                        <?php
                        echo $form->field($examDetails, 'questions[type]')->dropDownList(
                            $type,
                            [
                                'prompt' => 'لطفا نوع پاسخ را مشخص کنید',
                                'class' => 'select2 form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'id' => '',
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">اجبار در پاسخ *</label>
                        <?php
                        echo $form->field($examDetails, 'questions[required]')->dropDownList(
                            $req,
                            [
                                'prompt' => 'لطفا انتخاب کنید',
                                'class' => 'select2 form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'id' => '',
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="form-repeater">
                        <hr>
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">گزینه های سوال</h6>
                            <div class="form-send-message d-flex justify-content-between align-items-center">
                                <div class="message-actions d-flex align-items-center">
                                                <span class="badge bg-label-info" data-repeater-create="" type="button" style="width: 150px;">
                                                    <i class="bx bx-plus me-1"></i>
                                                    <span class="align-middle grow">افزودن گزینه</span>
                                                </span>
                                </div>
                            </div>
                        </div>
                        <div data-repeater-list="questions" class="mt-3">
                            <div data-repeater-item="">
                                <div class="row mb-2">
                                    <div class="mb-12 col-lg-12 col-xl-12 col-12 mb-0">
                                        <div class="input-group">
                                            <textarea name="title" type="text" class="form-control" aria-label="Text input with radio button"></textarea>
                                            <button class="btn btn-outline-danger" type="button" data-repeater-delete="" id="inputGroupFileAddon04">
                                                <svg class="tf-icons navbar-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.5" d="M11.6068 21.9998H12.3937C15.1012 21.9998 16.4549 21.9998 17.3351 21.1366C18.2153 20.2734 18.3054 18.8575 18.4855 16.0256L18.745 11.945C18.8427 10.4085 18.8916 9.6402 18.45 9.15335C18.0084 8.6665 17.2628 8.6665 15.7714 8.6665H8.22905C6.73771 8.6665 5.99204 8.6665 5.55047 9.15335C5.10891 9.6402 5.15777 10.4085 5.25549 11.945L5.515 16.0256C5.6951 18.8575 5.78515 20.2734 6.66534 21.1366C7.54553 21.9998 8.89927 21.9998 11.6068 21.9998Z" fill="#1C274C"></path>
                                                    <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0 add-item">

                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ثبت سوال</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>