<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Users;
use app\models\Colleges;
use app\models\UploadedExcels;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');
/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$status = array(
    '0' => 'خطا در ادوبی',
    '1' => 'فعال',
    '2' => 'غیر فعال',
);
$roles = array(
        'user' => 'دانشپذیر',
        'mentor' => 'دستیار استاد'
);

?>
<style>
    #tbl {
        max-height:200px;
    }
</style>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-5">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <?php $form = ActiveForm::begin([
                'action'=>['packages/edit-package'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <input name="_id" type="hidden" value="<?= (string) $packageDetail->_id ?>">
            <?php
            echo $form->field($model, 'last_name')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام خانوادگی',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'username')->textInput(
                [
                    'placeholder' => 'فیلتر بر اساس نام کاربری',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'courses[status]')->dropDownList(
                $status,
                [
                    'prompt' => 'فیلتر بر اساس وضعیت عضو',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <?php
            echo $form->field($model, 'courses[role]')->dropDownList(
                $roles,
                [
                    'prompt' => 'فیلتر بر اساس نقش عضو',
                    'class' => 'form-select none-parent',
                    'id' => 'status',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
            <?php ActiveForm::end(); ?>
            <?php
            if (isset(Yii::$app->request->queryParams['CoursesMembers'])) {
                $last_name = Yii::$app->request->queryParams['CoursesMembers']['last_name'];
                $username = Yii::$app->request->queryParams['CoursesMembers']['username'];
                $status = Yii::$app->request->queryParams['CoursesMembers']['courses']['status'];
                $role = Yii::$app->request->queryParams['CoursesMembers']['courses']['role'];
            } else {
                $last_name = '';
                $username = '';
                $status = '';
                $role = '';
            }
            ?>
            <?php $form = ActiveForm::begin([
                'action'=>['members_report'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>
            <?= $form->field($model, 'last_name')->hiddenInput(['value' => $last_name])->label(false); ?>
            <?= $form->field($model, 'username')->hiddenInput(['value' => $username])->label(false); ?>
            <?= $form->field($model, 'courses[status]')->hiddenInput(['value' => $status])->label(false); ?>
            <?= $form->field($model, 'courses[role]')->hiddenInput(['value' => $role])->label(false); ?>
            <input name="_id" type="hidden" value="<?= (string) $packageDetail->_id ?>">
            <button class="btn btn-secondary" style="margin-right: 5px;" type="submit"><i class="fa-solid fa-file-excel"></i></button>
            <?php ActiveForm::end(); ?>
            <div class="btn-group" style="margin-right: 100px;" role="group" aria-label="Button group with nested dropdown">
                <div class="btn-group" role="group">
                    <button id="btnGroupDrop1" type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                       افزودن عضو
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" style="">
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-user">افزودن با مشخصات</a>
<!--                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#from-list">افزودن از لیست</a>-->
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#from-excel">افزودن از فایل اکسل</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>



<div class="modal fade" id="from-list" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن عضو از لیست اعضای سیستم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input name="packageId" id="packageId" value="<?= (string) $packageDetail->_id ?>" type="hidden">
                <div class="row">
                    <div class="mb-3 col-lg-8 col-xl-8 col-8 col-sm-12 mb-0">
                        <label class="form-label" for="form-repeater-1-1">نام کاربری</label>
                        <input type="text" id="username" class="form-control text-start"  dir="ltr" onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46) || (event.charCode >= 32 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)">
                    </div>
                    <div class="mb-3 col-lg-4 col-xl-4 col-4 col-sm-12 mb-0">
                        <button id="add_from-list" type="submit" class="btn btn-label-info mt-4">
                            <span class="align-middle">جستجو</span>
                        </button>
                    </div>
                </div>
                <div id="selected-user"></div>
            </div>
            <div class="modal-footer" id="final-ok">

            </div>
        </div>
    </div>
</div>
<?php
//$allowDeadlineDate = true;
//if($packageDetail->deadline_date != null)
//{
//    $deadlineDate = str_replace('-','', $packageDetail->deadline_date);
//    if($deadlineDate < jdate('Ymd'))
//        $allowDeadlineDate = false;
//}
$allowDeadlineDate = true;
$deadLineDate = '';
if($packageDetail->date != null)
{
    if(array_key_exists('from', $packageDetail->date) && array_key_exists('to', $packageDetail->date))
    {
        $cal_date = $this->context->check_date($packageDetail->date['from'], $packageDetail->date['to']);
        $cal_date = json_decode($cal_date);
        $allowDeadlineDate = $cal_date->allowDeadlineDate;
        $deadLineDate = $cal_date->deadlineTimestamp;
    }
}
if($allowDeadlineDate)
{
    ?>
    <div class="modal fade" id="new-user" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت عضو جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $college = Colleges::findOne($packageDetail->college);
                    ?>
                    <?php $newUser = new Users(); ?>
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['new_user'],
                            "method" => "post",
                            'options' => [
                                'class' => '',
                                'id' => 'add-new-member',
                                'enctype' => 'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <input name="packageId" value="<?= (string) $packageDetail->_id ?>" type="hidden">
                    <div class="row">
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">نام *</label>
                            <?= $form->field($newUser, 'first_name')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'id' => 'student-first-name',
                                    'required' => true
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="select2Basic" class="form-label">نام خانوادگی *</label>
                            <?=
                            $form->field($newUser, 'last_name')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'id' => 'student-last-name',
                                    'required' => true
                                ]
                            )->label(false);
                            ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">نام کاربری * (شماره همراه یا ایمیل)</label>
                            <?= $form->field($newUser, 'username')->textInput(
                                [
                                    'id' => 'student-username',
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46) || (event.charCode >= 32 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)"
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">رمز عبور * </label>
                            <?= $form->field($newUser, 'password_hash')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true
                                ]
                            )->label(false); ?>
                        </div>
                    </div>
                    <p>نکته: پرداخت در این قسمت بین دانشکده و کارگزار تسهیم خواهد شد</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <?php
                    if(array_key_exists('pos_account_id', $college->financial_info))
                        echo '<button type="submit" class="btn btn-primary pos-submit">ثبت عضو</button>';
                    else
                        echo '<button type="button" class="btn btn-danger pos-submit">به دلیل نداشتن پی سی پوز مجاز به ثبت عضو نمی باشید</button>';
                    ?>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="from-excel" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت اعضا از فایل اکسل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $excelModel = new UploadedExcels(); ?>
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['add_user_from_excel'],
                            "method" => "post",
                            'options' => [
                                'class' => '',
                                'enctype' => 'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <input name="packageId" id="packageId" value="<?= (string) $packageDetail->_id ?>" type="hidden">
                    <div class="card mb-4 relative">
                        <div class="card-body">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                                <div class="dz-message needsclick">
                                    <?= $form->field($excelModel, 'file')->fileInput(
                                        [
                                            'class' => 'form-control text-start drop-file',
                                            'required' => true,
                                            'id' => 'excel-file'
                                        ]
                                    )->label(false); ?>
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">فایل اکسل *</span>
                                </div>
                            </div>
                        </div>
                        <p> نکته: هزینه ثبت عضو در این قسمت فقط به اندازه سهم دانشکده از کیف پول کارگزار کسر می گردد</p>
                    </div>
                    <button type="button" class="btn btn-primary" id="excel">بررسی فایل</button>
                    <img src="<?= Yii::getAlias('@front') ?>/assets/img/loading.gif" id="loading-image" style="visibility: hidden;">
                    <?php ActiveForm::end(); ?>
                    <hr>
                    <div id="message"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>

                </div>
            </div>
        </div>
    </div>
<?php
}
else
{
    ?>
    <div class="modal fade" id="new-user" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت عضو جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">متاسفانه به دلیل اتمام تاریخ ثبت عضو شما قادر به ثبت عضو نمی باشید <br>آخرین مهلت ثبت عضو <?= jdate('Y/m/d', $deadLineDate) ?> بوده است</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="from-excel" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت اعضا از فایل اکسل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">متاسفانه به دلیل اتمام تاریخ ثبت عضو شما قادر به ثبت عضو نمی باشید <br>آخرین مهلت ثبت عضو <?= jdate('Y/m/d', $deadLineDate) ?> بوده است</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>
