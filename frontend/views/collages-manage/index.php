<?php
$this->title = 'مدیریت دانشکده';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\SingleAsset;
use app\models\Colleges;
use yii\widgets\ListView;

SingleAsset::register($this);
$model = new Colleges();
$front = Yii::getAlias('@front');
if(Yii::$app->session->has('status'))
{
    if(Yii::$app->session->getFlash('status') == '1')
        $script = <<< JS
    toastr.success("دانشکده مورد نظر ثبت گردید", {
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
    toastr.warning("عنوان دانشکده وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.success("دانشکده مورد نظر ویرایش گردید", {
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
                <a href="javascript:void(0);"> مدیریت دانشکده </a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">دانشکده های ثبت شده</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت دانشکده جدید
            </button>
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
                            <th>لوگو دانشکده</th>
                            <th>عنوان دانشکده</th>
                            <th>کد مجوز</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php
                        foreach ($dataProvider->models as $college)
                        {
                            $edit = 'edit'.rand();
                            $report = 'report'.rand();
                            $logo = $front . '/college_logos/' . $college->logo;
                        ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                                <td>
                                    <div class="avatar avatar-lg me-2">
                                        <img src="<?= $front.'/college_logos/'.$college->logo ?>" alt="دانشکده <?= $college->title ?>" class="rounded-circle">
                                    </div>
                                </td>
                                <td><?= Html::encode($college->title) ?></td>
                                <td><?= Html::encode($college->prefix) ?></td>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش </a>
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $report ?>">گزارش سالانه</a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش دانشکده <?= Html::encode($college->title) ?></h5>
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
                                            <?= $form->field($college, '_id')->hiddenInput()->label(false); ?>
                                            <div class="row">
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">عنوان دانشکده *</label>
                                                    <?= $form->field($college, 'title')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">کد مجوز *</label>
                                                    <?= $form->field($college, 'prefix')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">امضا خط اول *</label>
                                                    <?= $form->field($college, 'first_line_signature_fa')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">امضا خط دوم *</label>
                                                    <?= $form->field($college, 'second_line_signature_fa')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">Title *</label>
                                                    <?= $form->field($college, 'title_en')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">Name *</label>
                                                    <?= $form->field($college, 'name')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">Last Name *</label>
                                                    <?= $form->field($college, 'last_name')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">First Signature *</label>
                                                    <?= $form->field($college, 'first_line_signature_en')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">Second Signature *</label>
                                                    <?= $form->field($college, 'second_line_signature_en')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <label for="nameWithTitle" class="form-label">شناسه واریز *</label>
                                                    <?= $form->field($college, 'financial_info[id]')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
//                                                            'required' => true
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <label for="nameWithTitle" class="form-label">ساب سرویس آی دی *</label>
                                                    <?= $form->field($college, 'financial_info[sub_service_id]')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'readonly' => true,
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                    <label for="nameWithTitle" class="form-label">شماره تماس </label>
                                                    <?= $form->field($college, 'phone')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-<?= rand() ?>">
                                                        <div class="dz-message needsclick">
                                                            <img src="<?= $front.'/college_logos/'.$college->logo ?>" class="upload-preview img-fluid">
                                                            <?= $form->field($college, 'logo')->fileInput(
                                                                [
                                                                    'class' => 'form-control text-start drop-file',
                                                                    'id' => 'id'.rand()
                                                                ]
                                                            )->label(false); ?>
                                                            <span class="drop-title"></span>
                                                            <span class="note needsclick">تصویر لوگوی دانشکده </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-<?= rand() ?>">
                                                        <div class="dz-message needsclick">
                                                            <img src="<?= $front.'/college_logos/'.$college->signature_file ?>" class="upload-preview img-fluid" alt="upload-preview">
                                                            <?= $form->field($model, 'signature_file')->fileInput(
                                                                [
                                                                    'class' => 'form-control text-start drop-file',
                                                                ]
                                                            )->label(false); ?>
                                                            <span class="drop-title"></span>
                                                            <span class="note needsclick">تصویر امضا </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">ویرایش دانشکده</button>
                                            <?php ActiveForm::end(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="<?= $report ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">گزارش سالانه دوره های دانشکده <?= Html::encode($college->title) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <?php $form = ActiveForm::begin(
                                                [
                                                    'action' => ['report'],
                                                    "method" => "post",
                                                    'options' => [
                                                        'class' => '',
                                                        'enctype' => 'multipart/form-data'
                                                    ],
                                                ]
                                            ); ?>
                                            <?= $form->field($college, '_id')->hiddenInput()->label(false); ?>
                                            <input type="hidden" name="college" value="<?= (string) $college->_id ?>">
                                            <div class="row">
                                                <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                    <label for="nameWithTitle" class="form-label">سال *</label>
                                                    <select class="form-select">
                                                        <?php
                                                        foreach ($years as $year)
                                                            echo '<option>'.$year.'</option>';
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">گزارش</button>
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
                        <div class="alert alert-danger" role="alert">تا کنون دانشکده ای ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت دانشکده جدید</h5>
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
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان دانشکده *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">کد مجوز *</label>
                        <?= $form->field($model, 'prefix')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">امضا خط اول *</label>
                        <?= $form->field($model, 'first_line_signature_fa')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">امضا خط دوم *</label>
                        <?= $form->field($model, 'second_line_signature_fa')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">Title *</label>
                        <?= $form->field($model, 'title_en')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">Name *</label>
                        <?= $form->field($model, 'name')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">Last Name *</label>
                        <?= $form->field($model, 'last_name')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">First Signature *</label>
                        <?= $form->field($model, 'first_line_signature_en')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">Second Signature *</label>
                        <?= $form->field($model, 'second_line_signature_en')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">شناسه حساب ۳۰ رقمی *</label>
                        <?= $form->field($model, 'financial_info[id]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)"
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">ساب سرویس آی دی *</label>
                        <?= $form->field($model, 'financial_info[sub_service_id]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'readonly' => true,
                                'value' => '1000101',
//                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">شماره تماس </label>
                        <?= $form->field($model, 'phone')->textInput(
                            [
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-20">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'logo')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر لوگوی دانشکده *</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-30">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'signature_file')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
//                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر امضا *</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت دانشکده</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>