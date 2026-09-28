<?php
$this->title = 'مدیریت دروس';

use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use frontend\controllers;
use yii\widgets\ListView;

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
<?php
$url = Yii::$app->urlManager->createAbsoluteUrl('lessons/get_lesson_detail','https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-lesson-detail',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("در حال دریافت...");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('.title').html(main_data.title);
            $('#body').html(main_data.body);
            $('#submit').html(main_data.submit);
        }
        })
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
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت دروس</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">دروس ثبت شده</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت درس جدید
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
                            <th>تصویر</th>
                            <th>عنوان فارسی درس</th>
                            <th>عنوان انگلیسی درس</th>
                            <?php if(Yii::$app->user->identity->role == 'user') echo '<th>دانشکده</th>'; ?>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php
                        foreach ($dataProvider->models as $lesson)
                        {
                            $edit = 'edit'.rand();
                            $collegeDetail = DashboardController::college_detail($lesson->college);
                        ?>
                            <tr>
                                <th scope="row"><?= $dataProvider->pagination->page * 30 + $i++ ?></th>
                                <td>
                                    <div class="avatar avatar-lg me-2">
                                        <img src="<?= $front . '/lesson_images/' . $lesson->imagePreview ?>" alt="<?= $lesson->title ?>" class="rounded-circle">
                                    </div>
                                </td>
                                <td class="text-wrap w-25"><?= Html::encode($lesson->title) ?></td>
                                <td class="text-wrap w-25"><?= Html::encode($lesson->en_title) ?></td>
                                <?php
                                if(Yii::$app->user->identity->role == 'user')
                                {
                                    if(strlen($collegeDetail->title) <= 30)
                                        echo '<td>'. Html::encode($collegeDetail->title).'</td>';
                                    else
                                    {
                                        ?>
                                        <td>
                                            <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= Html::encode($collegeDetail->title) ?>">
                                                <?= Html::encode(substr($collegeDetail->title,0,27)).'...' ?>
                                            </button>
                                        </td>
                                        <?php
                                    }
                                }

                                ?>
                                <td>
                                    <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش درس </a>
                                        <a class="dropdown-item show-lesson-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete" id="<?php echo (string) $lesson->_id; ?>">حذف درس</a>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش مشخصات درس <?= Html::encode($lesson->title) ?></h5>
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
                                            <?= $form->field($lesson, '_id')->hiddenInput()->label(false); ?>
                                            <div class="row">
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <label for="nameWithTitle" class="form-label">عنوان فارسی *</label>
                                                    <?= $form->field($lesson, 'title')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فارسی درس را وارد کنید\')',
                                                            'oninput' => 'setCustomValidity(\'\')',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                                    <label for="nameWithTitle" class="form-label">عنوان انگلیسی *</label>
                                                    <?= $form->field($lesson, 'en_title')->textInput(
                                                        [
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان انگلیسی را وارد کنید\')',
                                                            'oninput' => 'setCustomValidity(\'\')',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                                    <label for="nameWithTitle" class="form-label">توضیحات درس</label>
                                                    <?= $form->field($lesson, 'comment')->textarea(
                                                        [
                                                            'class' => 'form-control text-start',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                            </div>

                                            <div class="card mb-4 relative">
                                                <div class="card-body">
                                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                        <div class="dz-message needsclick">
                                                            <img src="<?= $front.'/lesson_images/'.$lesson->imagePreview ?>" class="upload-preview img-fluid">
                                                            <?= $form->field($lesson, 'imagePreview')->fileInput(
                                                                [
                                                                    'class' => 'form-control text-start drop-file',
                                                                ]
                                                            )->label(false); ?>
                                                            <span class="drop-title"></span>
                                                            <span class="note needsclick">تصویر درس</span>
                                                            <div class="alert alert-danger note needsclick" role="alert"><p>* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت باشد</p></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                بستن
                                            </button>
                                            <button type="submit" class="btn btn-primary">ویرایش درس</button>
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
                        <div class="alert alert-danger" role="alert">تا کنون درسی ثبت نشده است</div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت درس جدید</h5>
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
                        <label for="nameWithTitle" class="form-label">عنوان فارسی درس *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان انگلیسی درس *</label>
                        <?= $form->field($model, 'en_title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده *</label>
                        <?php
                        if(Yii::$app->user->identity->role == 'user')
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                        else
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                ]
                            )->label(false);
                        ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">توضیحات درس</label>
                        <?= $form->field($model, 'comment')->textarea(
                            [
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <?= $form->field($model, 'imagePreview')->fileInput(
                                    [
                                        'class' => 'form-control text-start drop-file',
//                                        'required' => true
                                    ]
                                )->label(false); ?>
                                <span class="drop-title"></span>
                                <span class="note needsclick">تصویر پیش نمایش درس</span>
                                <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize')/1000 ?> کیلوبایت باشد</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت درس</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="delete" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <div id="submit"></div>
            </div>
        </div>
    </div>
</div>