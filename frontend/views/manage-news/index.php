<?php
$this->title = 'مدیریت اخبار';

use frontend\controllers\DashboardController;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\News;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

Select2Asset::register($this);
$model = new News();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->getFlash('status') == '1')
        $script = <<< JS
    toastr.success("خبر مورد با موفقیت ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '2')
        $script = <<< JS
    toastr.warning("خطایی رخ داده، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '3')
        $script = <<< JS
    toastr.success("آئین نامه مورد با موفقیت ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.success("ویرایش انجام گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->getFlash('status') == '5')
        $script = <<< JS
    toastr.success("حذف با موفقیت انجام پذیرفت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;


    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$editorScript = <<< JS

    $("#add-news-form").on("submit", function () {
        $("#editor-content-add").val($("#add-news-text .ql-editor").html());
    });

    $(".edit-news-form").on("submit", function () {
        const caseId = $(this).attr('data-id');
        $("#editor-content-input-"+caseId).val($("#news-editor-" + caseId + " .ql-editor").html());
    });
JS;

$this->registerJs($editorScript);
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
    /* Style the CKEditor element to look like a textfield */
    .cke_textarea_inline
    {
        padding: 10px;
        height: 200px;
        overflow: auto;
        font-family:IRANYekanWeb;
        border: 1px solid gray;
        -webkit-appearance: textfield;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت مطالب سایت</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">مطالب ثبت شده</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    ایجاد مطلب
                </button>
                <ul class="dropdown-menu" style="">
                    <li><a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-news/new') ?>">افزودن خبر</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add-rule">افزودن آئین نامه</a></li>
                </ul>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <?php
            $i = 1;
            if ($dataProvider->models != null) {
            ?>
                <table class="table">
                    <thead>
                        <tr class="text-nowrap">
                            <th>#</th>
                            <th>تصویر</th>
                            <th>عنوان</th>
                            <th>نوع</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php
                        foreach ($dataProvider->models as $case)
                        {
                            $edit = 'edit' . rand();
                            $delete = 'delete' . rand();
                        ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 50 + $i++ ?></th>
                                <td>
                                    <?php
                                    if($case->type == '1')
                                    {
                                        ?>
                                        <div class="avatar avatar-lg me-2">
                                            <img src="<?= $front . '/news_images/' . $case->image ?>" alt="<?= $case->title ?>" class="rounded-circle">
                                        </div>
                                    <?php
                                    }
                                    else
                                        echo '-';
                                    ?>
                                </td>
                                <td class="text-wrap w-25"><?= $case->title ?></td>
                                <td class="text-wrap w-25">
                                    <?php
                                    if ($case->type == '1')
                                        echo 'خبر';
                                    else if ($case->type == '2')
                                        echo 'آئین نامه';
                                    else if ($case->type == '3')
                                        echo 'نوشتار علمی';
                                    ?>
                                </td>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                        <?php
                                        if($case->type == '1' || $case->type == '3')
                                        {
                                            ?>
                                            <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-news/edit-news','_id' => (string) $case->_id]) ?>">ویرایش</a>
                                        <?php
                                        }
                                        else
                                        {
                                            ?>
                                            <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش</a>
                                        <?php
                                        }
                                        ?>
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $delete ?>">حذف</a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-xl" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش آئین نامه <?= $case->title ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php $form = ActiveForm::begin(
                                                [
                                                    'action' => ['edit_type_2'],
                                                    "method" => "post",
                                                    'options' => [
                                                        'class' => 'edit-news-form',
                                                        'enctype' => 'multipart/form-data',
                                                        "data-id" => $case->_id
                                                    ],
                                                ]
                                            ); ?>
                                            <?= $form->field($case, '_id')->hiddenInput()->label(false); ?>
                                            <div class="row">
                                                <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                    <label for="nameWithTitle" class="form-label">عنوان آئین نامه *</label>
                                                    <?= $form->field($case, 'title')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                            </div>

                                            <div class="card mb-4 relative">
                                                <div class="card-body">
                                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                        <div class="dz-message needsclick">
                                                            <?= $form->field($case, 'file')->fileInput(
                                                                [
                                                                    'class' => 'form-control text-start drop-file',
                                                                ]
                                                            )->label(false); ?>
                                                            <span class="drop-title"></span>
                                                            <span class="note needsclick">فایل</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">ویرایش آئین نامه</button>
                                            <?php ActiveForm::end(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="<?= $delete ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف  <?= $case->title ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php $form = ActiveForm::begin(
                                                [
                                                    'action' => ['delete'],
                                                    "method" => "post",
                                                    'options' => [
                                                        'class' => 'edit-news-form',
                                                        'enctype' => 'multipart/form-data',
                                                        "data-id" => $case->_id
                                                    ],
                                                ]
                                            ); ?>
                                            <?= $form->field($case, '_id')->hiddenInput()->label(false); ?>
                                            <div class="row">
                                                <?php
                                                if($case->type == '1')
                                                    echo 'آیا از حذف خبر با عنوان '.$case->title.' مطمئن هستید؟';
                                                else
                                                    echo 'آیا از حذف آئین نامه با عنوان '.$case->title.' مطمئن هستید؟';
                                                ?>
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
                        <div class="alert alert-danger" role="alert">تا کنون مطلبی ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<div class="modal fade" id="add-news" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت خبر جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new_news'],
                        "method" => "post",
                        'options' => [
                            'id' => 'add-news-form',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان خبر *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">توضیحات </label>
                        <?php
                        echo $form->field($model, 'content')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'full',
                                'inline' => false,
                            ],
                        ])->label(false); ?>
                    </div>
                </div>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'image')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر *</span>
                                <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize') / 1000 ?> کیلوبایت باشد</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت خبر</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add-rule" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت آئین نامه جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new_file'],
                        "method" => "post",
                        'options' => [
                            'id' => 'add-news-form',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان آئین نامه *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test09">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'file')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        'required' => true,
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">فایل *</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت آئین نامه</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
