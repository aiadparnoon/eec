<?php
$this->title = 'آزمون ساز';

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
    toastr.success("آزمون مورد نظر ویرایش گردید", {
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
    toastr.success("وضعیت آزمون مرود نظر تغییر یافت", {
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
$url = Yii::$app->urlManager->createAbsoluteUrl('test-maker/get_exam_details','https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.edit-exam',function(e) {
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
                <a href="javascript:void(0);">سایر موارد</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">آزمون ساز</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">آزمون های ثبت شده</h5>
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
                    <th>عنوان</th>
                    <?php
                    if(Yii::$app->user->identity->role == 'user')
                        echo '<th>دانشکده</th>';
                    ?>
                    <th>تعداد سوالات</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $exam)
                {
                    $edit = 'edit'.rand();
                    $changeStatus = 'changeStatus'.rand();
                    $resetPassword = 'resetPassword'.rand();
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center bg-primary"><?= $dataProvider->pagination->page * 30 + $i++ ?></span></th>
                        <td><?= $exam->title ?></td>
                        <?php
                        if(Yii::$app->user->identity->role == 'user')
                        {
                            ?>
                            <td>
                                <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $this->context->college_detail($exam->college)->title ?>">
                                    <?= substr($this->context->college_detail($exam->college)->title, 0, 27) . '...' ?>
                                </button>
                            </td>
                            <?php
                        }

                        ?>
                        <td>
                            <?php
                            if($exam->type == '1')
                            {
                                if($exam->questions != null)
                                    echo count($exam->questions);
                                else
                                    echo '0';
                            }
                            else
                                echo $exam->question_count['easy'] + $exam->question_count['medium'] + $exam->question_count['hard'];
                            ?>
                        </td>

                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <?php
                                if($exam->type == '1')
                                {
                                    ?>
                                    <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['test-maker/manage-questions','_id'=>(string) $exam->_id]) ?>">مدیریت سوالات</a>
                                <?php
                                }
                                ?>
                                <a class="dropdown-item" href="#"  data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش آزمون</a>
<!--                                <a class="dropdown-item edit-exam" href="#"  data-bs-toggle="modal" data-bs-target="#edit_exam" id="--><?php //echo (string) $exam->_id; ?><!--">ویرایش آزمون</a>-->
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش آزمون <?= $exam->title ?></h5>
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
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                            <label for="nameWithTitle" class="form-label">عنوان  *</label>
                                            <?= $form->field($exam, 'title')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان  را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <?php
                                        if (Yii::$app->user->identity->role == 'user')
                                        {
                                            ?>
                                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                                <label for="select2Basic" class="form-label">دانشکده *</label>
                                                <?php
                                                echo $form->field($exam, 'college')->dropDownList(
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
                                            echo $form->field($exam, 'college')->hiddenInput(
                                                [
                                                    'required' => true,
                                                    'id' => '',
                                                    'data-allow-clear' => true,
                                                    'value' => Yii::$app->user->identity->college,
                                                ]
                                            )->label(false);
                                        }
                                        ?>
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                            <label for="select2Basic" class="form-label">نوع سوالات آزمون *</label>
                                            <?php
                                            $type = array(
                                                '1' => 'سوالات انتخابی',
                                                '2' => 'سوالات رندم'
                                            );
                                            echo $form->field($exam, 'type')->dropDownList(
                                                $type,
                                                [
                                                    'class' => 'form-select',
                                                    'required' => true,
                                                    'data-allow-clear' => true,
                                                    'id' => '',
                                                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/random_questions1') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#random-questions\').html(data);
                                                                                        }
                                                                                    );'
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="row" id="random-questions">
                                            <?php
                                            if($exam->type == '2')
                                            {
                                                ?>
                                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                                    <label for="nameWithTitle" class="form-label">گروه  *</label>
                                                    <?php
                                                    echo $form->field($exam, 'group')->dropDownList(
                                                        $groups,
                                                        [
                                                            'prompt' => 'لطفا گروه سوالات را مشخص کنید',
                                                            'class' => 'select2 form-select',
                                                            'required' => true,
                                                            'data-allow-clear' => true,
                                                            'id' => rand(),
                                                        ]
                                                    )->label(false);
                                                    ?>
                                                </div>
                                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                                    <label for="nameWithTitle" class="form-label">تعداد سوالات آسان *</label>
                                                    <?= $form->field($exam, 'question_count[easy]')->textInput(
                                                        [
                                                            'type' => 'number',
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                                                            'oninput' => 'setCustomValidity(\'\')',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                                    <label for="nameWithTitle" class="form-label">تعداد سوالات متوسط *</label>
                                                    <?= $form->field($exam, 'question_count[medium]')->textInput(
                                                        [
                                                            'type' => 'number',
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات متوسط  را وارد کنید\')',
                                                            'oninput' => 'setCustomValidity(\'\')',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                                    <label for="nameWithTitle" class="form-label">تعداد سوالات سخت *</label>
                                                    <?= $form->field($exam, 'question_count[hard]')->textInput(
                                                        [
                                                            'type' => 'number',
                                                            'class' => 'form-control text-start',
                                                            'required' => true,
                                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات سخت  را وارد کنید\')',
                                                            'oninput' => 'setCustomValidity(\'\')',
                                                        ]
                                                    )->label(false); ?>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                            <label for="nameWithTitle" class="form-label">نوع پاسخ *</label>
                                            <?php
                                            $randomAnswers = array(
                                                true => 'پاسخ ها مرتب',
                                                false => 'پاسخ ها رندم',
                                            );
                                            echo $form->field($exam, 'random_answers')->dropDownList(
                                                $randomAnswers,
                                                [
                                                    'class' => 'form-select',
                                                    'required' => true,
                                                    'data-allow-clear' => true,
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                            <label for="nameWithTitle" class="form-label">زمان آزمون (دقیقه) *</label>
                                            <?= $form->field($exam, 'time')->textInput(
                                                [
                                                    'type' => 'number',
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                            <label for="nameWithTitle" class="form-label">تعداد دفعات شرکت در آزمون *</label>
                                            <?= $form->field($exam, 'repeat')->textInput(
                                                [
                                                    'type' => 'number',
                                                    'class' => 'form-control text-start',
                                                ]
                                            )->label(false); ?>
                                            <div id="floatingInputHelp" class="form-text">
                                                مقدار خالی یعنی بی نهایت بار
                                            </div>
                                        </div>
                                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                            <label for="nameWithTitle" class="form-label">نحوه نمره آزمون *</label>
                                            <?php
                                            $scoreType = array(
                                                '1' => 'میانگین نمرات',
                                                '2' => 'بیشترین نمره',
                                            );
                                            echo $form->field($exam, 'score_type')->dropDownList(
                                                $scoreType,
                                                [
                                                    'prompt' => 'نحوه نمره آزمون',
                                                    'class' => 'form-select',
                                                    'required' => true,
                                                    'data-allow-clear' => true,
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                            <label for="nameWithTitle" class="form-label">نمایش آزمون در سایت *</label>
                                            <?php
                                            $saleStatus = array(
                                                '2' => 'خیر',
                                                '1' => 'بله',
                                            );
                                            echo $form->field($exam, 'sale_status')->dropDownList(
                                                $saleStatus,
                                                [
                                                    'class' => 'form-select',
                                                    'required' => true,
                                                    'data-allow-clear' => true,
                                                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/show_in_site1') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                        $(\'#part1\').html(main_data.part1);
                                                                                        $(\'#part2\').html(main_data.part2);
                                                                                        $(\'#part3\').html(main_data.part3);
                                                                                        }
                                                                                    );'
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                            <label for="nameWithTitle" class="form-label">نمره کلی آزمون *</label>
                                            <?= $form->field($exam, 'total_score')->textInput(
                                                [
                                                    'type' => 'number',
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نمره کلی آزمون را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                            <label for="nameWithTitle" class="form-label">حداقل نمره قبولی *</label>
                                            <?= $form->field($exam, 'pass_score')->textInput(
                                                [
                                                    'type' => 'number',
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا حداقل نمره قبولی در آزمون را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3" id="part1">
                                            <?php
                                            if($exam->sale_status == '1')
                                            {
                                                ?>
                                                <label for="nameWithTitle" class="form-label">قیمت آزمون (تومان) *</label>
                                                <?php
                                                echo $form->field($exam, 'price')->textInput(
                                                    [
                                                        'type' => 'number',
                                                        'class' => 'form-control text-start',
                                                        'required' => true,
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت آزمون را وارد کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false);
                                                ?>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="col-8 col-md-8 col-sm-12 dol-lg-8 col-xl-4 mb-3" id="part2">
                                            <?php
                                            if($exam->sale_status == '1')
                                            {
                                                ?>
                                                <div class="card mb-4 relative">
                                                    <div class="card-body">
                                                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                                            <div class="dz-message needsclick">
                                                                <?php
                                                                if($exam->preview_image != null)
                                                                    echo '<img src="'.$front.'/tests_images/'.$exam->preview_image.'" class="upload-preview img-fluid">';
                                                                ?>
                                                                <?= $form->field($exam, 'preview_image')->fileInput(
                                                                    [
                                                                        'class' => 'form-control text-start drop-file',
                                                                    ]
                                                                )->label(false); ?>
                                                                <span class="drop-title"></span>
                                                                <span class="note needsclick">تصویر پیش نمایش </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3" id="part3">
                                            <?php
                                            if($exam->sale_status == '1')
                                            {
                                                ?>
                                                <label for="nameWithTitle" class="form-label">توضیحات  *</label>
                                                <?= $form->field($exam, 'description')->textarea(
                                                [
                                                    'type' => 'number',
                                                    'class' => 'form-control text-start',
                                                ]
                                            )->label(false); ?>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-success">ویرایش آزمون</button>
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
    <div class="modal-dialog modal-lg" role="document">
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
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان *</label>
                        <?= $form->field($model, 'title')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <?php
                    if (Yii::$app->user->identity->role == 'user')
                    {
                        ?>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
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
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="select2Basic" class="form-label">نوع سوالات آزمون *</label>
                        <?php
                        $type = array(
                                '1' => 'سوالات انتخابی',
                                '2' => 'سوالات رندم'
                        );
                            echo $form->field($model, 'type')->dropDownList(
                                $type,
                                [
                                    'class' => 'form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/random_questions') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#random-questions1\').html(data);
                                                                                        }
                                                                                    );'
                                ]
                            )->label(false);
                        ?>
                    </div>
                    <div class="row" id="random-questions1">

                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع پاسخ *</label>
                        <?php
                        $randomAnswers = array(
                                true => 'پاسخ ها مرتب',
                                false => 'پاسخ ها رندم',
                        );
                        echo $form->field($model, 'random_answers')->dropDownList(
                            $randomAnswers,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">زمان آزمون (دقیقه) *</label>
                        <?= $form->field($model, 'time')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد سوالات آسان  را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">تعداد دفعات شرکت در آزمون *</label>
                        <?= $form->field($model, 'repeat')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                            ]
                        )->label(false); ?>
                        <div id="floatingInputHelp" class="form-text">
                           مقدار خالی یعنی بی نهایت بار
                        </div>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نحوه نمره آزمون *</label>
                        <?php
                        $scoreType = array(
                            '1' => 'میانگین نمرات',
                            '2' => 'بیشترین نمره',
                        );
                        echo $form->field($model, 'score_type')->dropDownList(
                            $scoreType,
                            [
                                'prompt' => 'نحوه نمره آزمون',
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">نمایش آزمون در سایت *</label>
                        <?php
                        $saleStatus = array(
                            '2' => 'خیر',
                            '1' => 'بله',
                        );
                        echo $form->field($model, 'sale_status')->dropDownList(
                            $saleStatus,
                            [
                                'class' => 'form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/test-maker/show_in_site') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                        console.log(main_data);
                                                                                        $(\'#part11\').html(main_data.part1);
                                                                                        $(\'#part22\').html(main_data.part2);
                                                                                        $(\'#part33\').html(main_data.part3);
                                                                                        }
                                                                                    );'
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">نمره کلی آزمون *</label>
                        <?= $form->field($model, 'total_score')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نمره کلی آزمون را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">حداقل نمره قبولی *</label>
                        <?= $form->field($model, 'pass_score')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا حداقل نمره قبولی در آزمون را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3" id="part11"></div>
                    <div class="col-8 col-md-8 col-sm-12 dol-lg-8 col-xl-8 mb-3" id="part22"></div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3" id="part33"></div>
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


<div class="modal fade" id="edit_exam" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
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

            </div>
            <div class="row container" id="body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ویرایش آزمون</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>