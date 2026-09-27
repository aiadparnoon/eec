<?php
$this->title = 'مدیریت آزمون های حضوری';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\OfflineExams;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new OfflineExams();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("آزمون مورد نظر ثبت گردید", {
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
    toastr.danger("حجم فایل عکس وارد شده بیشتر از حد مجاز می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("آزمون مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("متن کارت ورود به جلسه ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت آزمون مرود نظر تغییر یافت", {
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

    .summary
    {
        display: none;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        مدیریت آزمون های حضوری
    </h4>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">آزمون های حضوری ثبت شده</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت آزمون جدید
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
                    <th>تصویر</th>
                    <th>عنوان آزمون</th>
                    <th>تاریخ برگزاری</th>
                    <th>مهلت ثبت نام</th>
                    <th>تعداد شرکت کنندگان</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $exam)
                {
                    $edit = 'edit'.rand();
                    $editCardText = 'editCardText'.rand();
                    $changeStatus = 'changeStatus'.rand();
                    $change = 'changeStatus'.rand();
                    $terminate = 'terminate'.rand();
                    $members = 'members'.rand();
                    $participants = $this->context->number_of_participants((string) $exam->_id);
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center"><?= $dataProvider->pagination->page * 30 + $i++ ?></span></th>
                        <td>
                            <div class="avatar avatar-sm me-2">
                                <img src="<?= $front . '/offline_exams_images/' . $exam->preview_image ?>" alt="" class="rounded-circle">
                            </div>
                        </td>
                        <td><?= $exam->title['fa'] ?></td>
                        <td><?= $exam->start_date ?></td>
                        <td><?= $exam->deadline ?></td>
                        <td><?= $participants ?></td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش مشخصات</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $editCardText ?>">متن کارت ورود به جلسه</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $terminate ?>">اتمام ثبت نام آزمون</a>
                                <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['offline-exams/participants','_id' => (string) $exam->_id]) ?>">شرکت کنندگان</a>
                                <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['offline-exams/financial-print','_id' => (string) $exam->_id]) ?>">گزارش مالی</a>
                                <a class="dropdown-item" href="http://api-eec.ut.ac.ir/offline-exams/applicants-excel/<?= (string) $exam->_id ?>" target="_blank">دانلود اکسل کلیه شرکت کنندگان</a>
                                <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['offline-exams/archive_profiles','_id' => (string) $exam->_id]) ?>" target="_blank">دانلود تصاویر پروفایل</a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش آزمون <?= $exam->title['fa'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['edit'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($exam, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">عنوان فارسی آزمون *</label>
                                            <?= $form->field($exam, 'title[fa]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">عنوان انگلیسی آزمون *</label>
                                            <?= $form->field($exam, 'title[en]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
                                            <?= $form->field($exam, 'price')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">حداکثر نمره آزمون *</label>
                                            <?= $form->field($exam, 'max_score')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">تاریخ شروع آزمون *</label>
                                            <?= $form->field($exam, 'start_date')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">حداکثر مهلت ثبت نام آزمون *</label>
                                            <?= $form->field($exam, 'deadline')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">توضیحات آزمون </label>
                                            <?= $form->field($exam, 'description')->textarea(
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
                                                    <?= $form->field($exam, 'preview_image')->fileInput(
                                                        [
                                                            'class' => 'form-control text-start drop-file',
                                                        ]
                                                    )->label(false); ?>
                                                    <span class="drop-title"></span>
                                                    <span class="note needsclick">تصویر پیش نمایش آزمون (کمتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">ویرایش آزمون</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $editCardText ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle"> متن کارت ورود به جلسه آزمون <?= $exam->title['fa'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['edit_card_tips'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => 'edit-news-form',
                                                'enctype' => 'multipart/form-data',
                                                "data-id" => $exam->_id
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($exam, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="nameWithTitle" class="form-label">توضیحات کارت ورود به جلسه </label>
                                            <div class="full-editor" id="news-editor-<?= $exam->_id ?>"><?= $exam->tips ?></div>
                                            <?= $form->field($exam, 'tips')->hiddenInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => false,
                                                    'id' => 'editor-content-input-' . $exam->_id
                                                ]
                                            )->label(false); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">ثبت / ویرایش توضیحات </button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $terminate ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">اتمام ثبت نام آزمون <?= $exam->title['fa'] ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['terminate_exam'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($exam, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <p>آیا از اتمام ثبت نام آزمون <?= $exam->title['fa'] ?> اطمینان دارید؟</p>
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
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت آزمون جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
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
                        <label for="nameWithTitle" class="form-label">عنوان فارسی آزمون *</label>
                        <?= $form->field($model, 'title[fa]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان انگلیسی آزمون *</label>
                        <?= $form->field($model, 'title[en]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
                        <?= $form->field($model, 'price')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">حداکثر نمره آزمون *</label>
                        <?= $form->field($model, 'max_score')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">تاریخ شروع آزمون *</label>
                        <?= $form->field($model, 'start_date')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">حداکثر مهلت ثبت نام آزمون *</label>
                        <?= $form->field($model, 'deadline')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">توضیحات آزمون </label>
                        <?= $form->field($model, 'description')->textarea(
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
                                <?= $form->field($model, 'preview_image')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
//                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر پیش نمایش آزمون * (کمتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت)</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت آزمون</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>