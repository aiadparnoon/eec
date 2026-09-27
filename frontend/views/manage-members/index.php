<?php
$this->title = 'مدیریت کارکنان';

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use common\models\Admin;
use yii\widgets\ListView;

Select2Asset::register($this);
$model = new Admin();
$front = Yii::getAlias('@front');
if (Yii::$app->session->has('status'))
{
    if(Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("کارمند مورد نظر ثبت گردید", {
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
    toastr.danger("شماره همراه وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("کارمند مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور کارمند مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت کارمند مرود نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$url = Yii::$app->urlManager->createAbsoluteUrl('courses/show_course_users', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('change','#college',function(e) {
    e.preventDefault();
    var college = $(this).val() 
    if(college == 0)
        {
            document.getElementById("zkh").style.display = 'block';
            document.getElementById("zkh2").style.display = 'block';
        }
    else 
        {
            document.getElementById("zkh").style.display = 'none';
            document.getElementById("zkh2").style.display = 'none';
        }
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
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        مدیریت کارکنان
    </h4>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">کارمندان ثبت شده</h5>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter">
                <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت کارمند جدید
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
                    <th>نام</th>
                    <th>نام خانوادگی</th>
                    <th>شماره همراه</th>
                    <th>کد ملی</th>
                    <th>دانشکده</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php
                foreach ($dataProvider->models as $employee)
                {
                    $collegeDetail = $this->context->college_detail($employee->college);
                    $edit = 'edit'.rand();
                    $changeStatus = 'changeStatus'.rand();
                    $resetPassword = 'resetPassword'.rand();
                    $employeeGender = 'آقای';
                    $employeeStatus = 'فعال';
                    $bg = 'bg-success';
                    if($employee->gender == 1)
                        $employeeGender = 'خانم';
                    if($employee->status == 9)
                    {
                        $employeeStatus = 'غیر فعال';
                        $statusIcon = 'avatar-busy';
                        $bg = 'bg-danger';
                    }
                    ?>
                    <tr>
                        <th scope="row"><span class="badge badge-center <?= $bg ?>"><?= $dataProvider->pagination->page * 50 + $i++ ?></span></th>
                        <td><?= $employee->first_name ?></td>
                        <td><?= $employee->last_name ?></td>
                        <td><?= $employee->username ?></td>
                        <td><?= $employee->national_code ?></td>
                        <td class="text-wrap w-25">
                            <?php
                            if($employee->college == '0')
                                echo 'کارمند مرکز';
                            else
                                echo $collegeDetail->title;
                            ?>
                        </td>
                        <td>
                            <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $edit ?>">ویرایش مشخصات</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $resetPassword ?>">بازنشانی رمز عبور</a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">
                                    <?php
                                    if($employee->status == 10)
                                        echo 'غیر فعال کردن';
                                    else
                                        echo 'فعال کردن';
                                    ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="<?= $edit ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ویرایش مشخصات <?= $employee->first_name.' '.$employee->last_name ?></h5>
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
                                    <?= $form->field($employee, '_id')->hiddenInput()->label(false); ?>
                                    <div class="row">
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">نام *</label>
                                            <?= $form->field($employee, 'first_name')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام کارمند را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">نام خانوادگی *</label>
                                            <?= $form->field($employee, 'last_name')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی کارمند را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">شماره همراه (نام کاربری) *</label>
                                            <div class="form-control bg-label-secondary"><?= $employee->username ?></div>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">کد ملی (رمز عبور) *</label>
                                            <?= $form->field($employee, 'national_code')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی کارمند را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>
                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label for="nameWithTitle" class="form-label">جنسیت *</label>
                                            <?php
                                            $gender = array(
                                                '0' => 'مرد',
                                                '1' => 'زن'
                                            )
                                            ?>
                                            <?= $form->field($employee, 'gender')->dropDownList(
                                                $gender,
                                                [
                                                    'class' => 'form-select text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا جنسیت کارمند را مشخص کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false); ?>
                                        </div>

                                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                                            <label class="form-label">دانشکده *</label>
                                            <?php
                                            echo $form->field($employee, 'college')->dropDownList(
                                                $colleges,
                                                [
                                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                                    'class' => 'form-select',
                                                    'required' => true,
                                                    'data-allow-clear' => true,
                                                    'id' => 'college'
                                                ]
                                            )->label(false);
                                            ?>
                                        </div>
                                        <?php
                                        $checked = '';
                                        $display = 'none';
                                        if($employee->serving == true)
                                        {
                                            $checked = 'checked';
                                            $display = 'block';
                                        }
                                        ?>
                                        <div class="form-check form-check-success" id="zkh2" style="display: <?= $display ?>">
                                            <input class="form-check-input" name="serving" type="checkbox" value="1" id="defaultCheck3" <?= $checked ?>>
                                            <label class="form-check-label" for="defaultCheck3">تعریف دوره های ضمن خدمت</label>
                                        </div>
                                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                                            <label for="select2Basic" class="form-label">دسترسی ها *</label>
                                            <?php
//                                            echo $form->field($employee, 'access')->dropDownList(
//                                                $access,
//                                                [
//                                                    'class' => 'form-select',
//                                                    'required' => true,
//                                                    'data-allow-clear' => true,
//                                                    'multiple' => true
//                                                ]
//                                            )->label(false);
                                            ?>
<!--                                            --><?php //= $form->field($model, 'access')->checkboxList($access,[
//
//                                                'itemOptions' => [
//
//                                                    'labelOptions' => ['class' => 'col-md-1']
//
//                                                ]
//
//                                            ]) ?>

<!--                                            --><?php //= Html::checkboxList('access', null, $access, [
//                                                'item' => function($index, $label, $name, $checked, $value) use ($employee) {
//                                                    return "<div class='form-check form-check-success mt-3'>
//                                                    <input class='form-check-input' type='checkbox'
//                                                           {$checked}
//                                                           name='{$name}'
//                                                           value='{$value}'
//                                                           >
//                                                    {$label}
//                                               <label class='form-check-label'></label></div>";
//                                                }
//                                            ]);
//                                            ?>

                                            <?=
                                            $form->field($employee, 'access')->checkboxList(
                                                    $access,
                                                        [

                                                            'item' => function ($index, $label, $name, $checked, $value){
                                                        $ch = '';
                                                        if ($checked == 1)
                                                            $ch = 'checked';

                                                                return '<div class="form-check form-check-success mt-3">

                                         <input class="form-check-input" type="checkbox" value="'.$value.'" name="access[]"  '.$ch.' /><label class="form-check-label">'.$label.'</label>

                                    </div>';
                                                            }
                                                        ]
                                                    )->label(false);
                                            ?>

                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">ویرایش کارمند</button>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="<?= $resetPassword ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">بازنشانی رمز عبور <?= $employee->first_name.' '.$employee->last_name ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['reset_password'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($employee, '_id')->hiddenInput()->label(false); ?>
                                    <p>آیا از بازنشانی رمز عبور <?= $employeeGender.' '.$employee->first_name.' '.$employee->last_name ?> به کد ملی وی (<?= $employee->national_code ?>) اطمینان دارید؟</p>
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
                    <div class="modal fade" id="<?= $changeStatus ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= $employeeGender.' '.$employee->first_name.' '.$employee->last_name ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php $form = ActiveForm::begin(
                                        [
                                            'action' => ['change_status'],
                                            "method" => "post",
                                            'options' => [
                                                'class' => '',
                                                'enctype' => 'multipart/form-data'
                                            ],
                                        ]
                                    ); ?>
                                    <?= $form->field($employee, '_id')->hiddenInput()->label(false); ?>
                                    <p>وضعیت <?= $employeeGender.' '.$employee->first_name.' '.$employee->last_name ?> در حال حاضر  (<?= $employeeStatus ?>) می باشد</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                        بستن
                                    </button>
                                    <button type="submit" class="btn btn-primary">تغییر وضعیت</button>
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
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت کارمند جدید</h5>
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
                        <label for="nameWithTitle" class="form-label">نام *</label>
                        <?= $form->field($model, 'first_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نام کارمند را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">نام خانوادگی *</label>
                        <?= $form->field($model, 'last_name')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نام خانوادگی کارمند را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">شماره همراه (نام کاربری) *</label>
                        <?= $form->field($model, 'username')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا شماره همراه کارمند را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 46 || (event.charCode >= 65 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">کد ملی (رمز عبور) *</label>
                        <?= $form->field($model, 'national_code')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا کد ملی کارمند را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">جنسیت *</label>
                        <?php
                        $gender = array(
                            '0' => 'مرد',
                            '1' => 'زن'
                        )
                        ?>
                        <?= $form->field($model, 'gender')->dropDownList(
                            $gender,
                            [
                                'class' => 'form-select text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا جنسیت کارمند را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده *</label>
                        <?php
                        echo $form->field($model, 'college')->dropDownList(
                            $colleges,
                            [
                                'prompt' => 'لطفا دانشکده را مشخص کنید',
                                'class' => 'select2 form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'id' => 'college'
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="select2Basic" class="form-label">دسترسی ها *</label>
<!--                        --><?php
//                        echo $form->field($model, 'access')->dropDownList(
//                            $access,
//                            [
//                                'class' => 'form-select',
//                                'required' => true,
//                                'data-allow-clear' => true,
//                                'multiple' => true
//                            ]
//                        )->label(false);
//                        ?>
                        <input id="TagifyUserList" name="TagifyUserList" class="form-control">
                    </div>
                    <div class="form-check form-check-success" id="zkh" style="display: none;">
                        <input class="form-check-input" name="serving" type="checkbox" value="1" id="defaultCheck3" checked="">
                        <label class="form-check-label" for="defaultCheck3">تعریف دوره های ضمن خدمت</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت کارمند</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>