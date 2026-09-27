<?php
$this->title = 'اخبار جدید';

use frontend\controllers\DashboardController;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\News;
use app\models\NewsFiles;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

Select2Asset::register($this);
$model = new News();
$upload = new NewsFiles();
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
    toastr.success("خبر مورد نظر ویرایش گردید", {
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
    else if (Yii::$app->session->getFlash('status') == '6')
        $script = <<< JS
    toastr.success("فایل مورد نظر بارکزاری گردید", {
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

$type = array(
        '1' => 'خبر',
        '3' => 'نوشتار علمی',
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
                <a href="javascript:void(0);">اخبار جدید</a>
            </li>
        </ol>
    </nav>
    <div class="card">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">فرم ثبت خبر جدید</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        ایجاد مطلب
                    </button>
                    <ul class="dropdown-menu" style="">
                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add-file">بارگزاری فایل جدید</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#view-file">فایل های بارگزاری شده</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
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
                        <label for="nameWithTitle" class="form-label">عنوان اصلی خبر *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان فرعی خبر *</label>
                        <?= $form->field($model, 'sub_title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع *</label>
                        <?= $form->field($model, 'type')->dropDownList(
                                $type,
                            [
                                'class' => 'form-select text-start',
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
                <button type="submit" class="btn btn-primary">ثبت خبر</button>
                <?php ActiveForm::end(); ?>
            </div>

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

<div class="modal fade" id="view-file" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">فایل های بارگزاری شده</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php
                    if($files != null)
                    {
                        foreach ($files as $file)
                        {
                            ?>
                            <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                            <figure>
                                <img src="<?= $front.'/news_files/'.$file->file ?>" width="100" height="100">
                                <figcaption>https://eec1.ut.ac.ir/news_files/<?= $file->file ?></figcaption>
                            </figure>
                            </div>
                    <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="add-file" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن فایل جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add_new_file'],
                        "method" => "post",
                        'options' => [
                            'id' => 'add-news-form',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($upload, 'file')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        'required' => true
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
                <button type="submit" class="btn btn-primary">بارگزاری فایل</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
