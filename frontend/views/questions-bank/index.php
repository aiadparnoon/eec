<?php
$this->title = 'بانک سوالات';

use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\QuestionsBank;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new QuestionsBank();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("سوال مورد نظر ثبت گردید", {
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
    toastr.error("لطفا حداقل یک گزینه برای سوال انتخاب کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.error("حداقل یکی از گزینه ها را به عنوان پاسخ انتخاب نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("سوال مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("گزینه مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.success("گزینه مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.error("امکان ویرایش وجود ندارد. با ویرایش این گزینه سوالات دیگر هیچکدام دارای حداقل یک پاسخ صحیح نمی باشند", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.error("امکان حذف وجود ندارد. با حذف این گزینه سوالات دیگر هیچکدام دارای حداقل یک پاسخ صحیح نمی باشند", {
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
$url = Yii::$app->urlManager->createAbsoluteUrl('questions-bank/show_question_detail', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.edit-question',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('#edit-body').html("در حال دریافت...");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('#edit-body').html(main_data.body);
            const formRepeater = $(".form-repeater");
            if (formRepeater.length) {
                var row = 2;
                var col = 1;
                formRepeater.on("submit", function (e) {
                e.preventDefault();
                });
                formRepeater.repeater({
                initEmpty: true,
                show: function (e) {
                    var fromControl = $(this).find(".form-control, .form-select");
                    var formLabel = $(this).find(".form-label");

                    fromControl.each(function (i) {
                    var id = "form-repeater-" + row + "-" + col;
                    $(fromControl[i]).attr("id", id);
                    $(formLabel[i]).attr("for", id);

                    // Check if Flatpickr is already initialized on this element
                    if (!$(fromControl[i]).hasClass("flatpickr-initialized")) {
                        if ($(fromControl[i]).hasClass("dob-picker")) {
                        $(fromControl[i]).flatpickr({
                            monthSelectorType: "static",
                            locale: "fa",
                            altInput: false,
                            altFormat: "Y/m/d",
                            disableMobile: true,
                            allowInput: true,
                        });

                        $(fromControl[i]).addClass("flatpickr-initialized");
                        }
                    }

                    col++;
                    });

                    row++;

                    $(this).slideDown();
                },
                hide: function (e) {
                    confirm("آیا از حذف این المان اطمینان دارید؟") && $(this).slideUp(e);
                },
                });
            }
        }
    });
});
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
                <a href="javascript:void(0);">بانک سوالات</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'groups' => $groups,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سوالات ثبت شده</h5>
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
                        <th>عنوان سوال</th>
                        <th>نوع</th>
                        <th>سطح</th>
                        <th>گروه</th>
                        <th>تعداد گزینه ها</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php
                    foreach ($dataProvider->models as $question)
                    {
                        $edit = 'edit' . rand();
                        $changeStatus = 'changeStatus' . rand();
                        $resetPassword = 'resetPassword' . rand();
                    ?>
                        <tr>
                            <th scope="row"><span class="badge badge-center bg-primary"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                            <td><?= $question->question_text ?></td>
                            <td>
                                <?php
                                if ($question->type == '1')
                                    echo 'تک جوابی';
                                else if ($question->type == '2')
                                    echo 'چند جوابی';
                                else
                                    echo 'تشریحی';
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($question->level == '1')
                                    echo 'ساده';
                                else if ($question->level == '2')
                                    echo 'متوسط';
                                else
                                    echo 'سخت';
                                ?>
                            </td>
                            <td><?= $this->context->group_detail($question->group) ?></td>
                            <td>
                                <?php
                                if($question->type == '3')
                                    echo '-';
                                else
                                {
                                    if ($question->options != null)
                                        echo count($question->options);
                                    else
                                        echo '0';
                                }
                                ?>
                            </td>

                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <a class="dropdown-item edit-question" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#edit" id="<?php echo (string) $question->_id; ?>">ویرایش سوال</a>
                                </div>
                            </td>
                        </tr>
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
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سوال تستی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                if($groups != null)
                {
                    ?>
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
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان *</label>
                            <?= $form->field($model, 'question_text')->textarea(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان سوال  را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                            <input name="group" value="1" type="hidden">
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">سطح سوال *</label>
                            <?php
                            $level = array(
                                '1' => 'آسان',
                                '2' => 'متوسط',
                                '3' => 'سحت'
                            );
                            echo $form->field($model, 'level')->dropDownList(
                                $level,
                                [
                                    'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">گروه *</label>
                            <?php
                            echo $form->field($model, 'group')->dropDownList(
                                $groups,
                                [
                                    'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                        <div class="dz-message needsclick">
                                            <?= $form->field($model, 'image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر سوال</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <div data-repeater-list="options">
                                <div data-repeater-item="">
                                    <div class="row mb-2">
                                        <div class="mb-12 col-lg-12 col-xl-12 col-12 mb-0">
                                            <div class="input-group">
                                                <div class="input-group-text form-check-success">
                                                    <input name="is_correct" class="form-check-input mt-0" type="checkbox">
                                                </div>
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
                <?php
                }
                else
                    echo '<p align="center">برای ثبت سوال باید حداقل یک گروه ثبت نمایید</p>';
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ثبت سوال</button>
                <?php
                if($groups != null)
                    ActiveForm::end();
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter1" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog  modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت سوال تشریحی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                if($groups != null)
                {
                    ?>
                    <?php

                    $form = ActiveForm::begin(
                        [
                            'action' => ['create'],
                            "method" => "post",
                            'options' => [
                                'class' => '',
                                'enctype' => 'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <div class="row">
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان *</label>
                            <?= $form->field($model, 'question_text')->textarea(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان سوال  را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                            <input name="group" value="1" type="hidden">
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">سطح سوال *</label>
                            <?php
                            $level = array(
                                '1' => 'آسان',
                                '2' => 'متوسط',
                                '3' => 'سحت'
                            );
                            echo $form->field($model, 'level')->dropDownList(
                                $level,
                                [
                                    'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">گروه *</label>
                            <?php
                            echo $form->field($model, 'group')->dropDownList(
                                $groups,
                                [
                                    'prompt' => 'لطفا سطح سوال را مشخص کنید',
                                    'class' => 'select2 form-select',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    'id' => '',
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                        <div class="dz-message needsclick">
                                            <?= $form->field($model, 'image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر سوال</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                else
                    echo '<p align="center">برای ثبت سوال باید حداقل یک گروه ثبت نمایید</p>';
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-success">ثبت سوال</button>
                <?php
                if($groups != null)
                    ActiveForm::end();
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="edit" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش سوال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="edit-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>