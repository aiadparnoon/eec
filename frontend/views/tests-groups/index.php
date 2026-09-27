<?php
$this->title = 'گروهبندی سوالات';

use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\TestsGroups;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new TestsGroups();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("گروه مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.error("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.error("عنوان وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("گروه مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("سوالات موجود در فایل به گروه مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("خطایی در بارگزاری فایل رخ داده است، لطفا دوباره امتحان کنید", {
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

    .summary {
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
                <a href="javascript:void(0);">گروهبندی سوالات</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سوالات ثبت شده</h5>
            <div class="btn-group" role="group">
                <button id="btnGroupDrop1" type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                    ثبت گروه جدید
                </button>
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
                    <th>عنوان گروه</th>
                    <th>تعداد سوالات</th>
                    <?php
                    if(Yii::$app->user->identity->role == 'user')
                        echo '<th>دانشکده</th>';
                    ?>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $group)
                {
                    $edit = 'edit' . rand();
                    $xml = 'xml' . rand();
                    $changeStatus = 'changeStatus' . rand();
                    $resetPassword = 'resetPassword' . rand();
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center bg-primary"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                        <td><?= $group->title ?></td>
                        <td>
                            <?php
                                echo $this->context->number_questions((string) $group->_id);
                            ?>
                        </td>
                        <?php
                        if(Yii::$app->user->identity->role == 'user')
                        {
                            $collegeDetail = $this->context->college_detail($group->college);
                            if($collegeDetail != null)
                            {
                                ?>
                                <td>
                                    <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $collegeDetail->title ?>">
                                        <?= substr($collegeDetail->title, 0, 27) . '...' ?>
                                    </button>
                                </td>
                        <?php
                            }
                            else
                                echo '<td>-</td>';
                        }
                        ?>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
<!--                                <a class="dropdown-item edit-question" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#--><?php //= $edit ?><!--">ویرایش سوال</a>-->
                                <a class="dropdown-item edit-question" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $xml ?>">بارگزاری سوال از فایل XML</a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog  modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش گروه <?= $group->title ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php

                                    $form = ActiveForm::begin(
                                        [
                                            'action' => ['new'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($group, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">عنوان *</label>
                                            <?= $form->field($group, 'title')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان گروه  را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                            <input name="group" value="1" type="hidden">
                                        </div>
                                        <?php
                                        if (Yii::$app->user->identity->role == 'user')
                                        {
                                            ?>
                                            <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                <label for="select2Basic" class="form-label">دانشکده *</label>
                                                <?php
                                                echo $form->field($group, 'college')->dropDownList(
                                                    $colleges,
                                                    [
                                                        'prompt' => 'لطفا دانشکده را مشخص کنید',
                                                        'class' => 'select2 form-select form-select-lg',
                                                        'required' => true,
                                                        'id' => '',
                                                        'data-allow-clear' => true,
                                                    ]
                                                )->label(false);
                                                ?>
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
                                    <button type="submit" class="btn btn-success">ویرایش گروه</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $xml ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog  modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">بارگزاری سوال از فایل XML به گروه <?= $group->title ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php

                                    $form = ActiveForm::begin(
                                        [
                                            'action' => ['read-xml-file'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($group, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">فایل XML *</label>
                                            <?= $form->field($group, 'xml')->fileInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا فایل را انتخاب کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-success">بارگزاری فایل</button>
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

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog  modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت گروه جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php

                $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان گروه  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                        <input name="group" value="1" type="hidden">
                    </div>
                        <?php
                        if (Yii::$app->user->identity->role == 'user')
                        {
                            ?>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده *</label>
                        <?php
                        echo $form->field($model, 'college')->dropDownList(
                            $colleges,
                            [
                                'prompt' => 'لطفا دانشکده را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                        <?php
                        }
                        else
                        {
                            echo $form->field($model, 'college')->hiddenInput(
                                [
                                    'required' => true,
                                    'id' => '',
                                    'data-allow-clear' => true,
                                    'value' => Yii::$app->user->identity->college,
                                ]
                            )->label(false);
                        }
                        ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ثبت گروه</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
