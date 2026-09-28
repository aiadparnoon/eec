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
<?php // آمار، جست‌وجو، خروجی و افزودن از اکسل: course-members/_toolbar ?>
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

    
<?php
}
?>
