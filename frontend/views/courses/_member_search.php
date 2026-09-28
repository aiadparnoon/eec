<?php
/**
 * مودال «افزودن با مشخصات» (کارتخوان) دوره‌ی کوتاه‌مدت. آمار، جست‌وجو، خروجی و افزودن از اکسل در
 * course-members/_toolbar است.
 *
 * @var $this yii\web\View
 * @var $packageDetail app\models\Courses
 */
use app\models\Colleges;
use app\models\Users;
use yii\widgets\ActiveForm;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');
?>
<?php
//$allowDeadlineDate = true;
//if($packageDetail->deadline_date != null)
//{
//    $deadlineDate = str_replace('-','', $packageDetail->deadline_date);
//    if($deadlineDate < jdate('Ymd'))
//        $allowDeadlineDate = false;
//}
$allowDeadlineDate = true;
if($packageDetail->date != null)
{
    if($packageDetail->lessons != null)
    {
        if(count($packageDetail->lessons) > 0)
        {
            if(array_key_exists('date', $packageDetail->lessons[0]))
            {
                if(array_key_exists('from', $packageDetail->lessons[0]['date']) && array_key_exists('to', $packageDetail->lessons[0]['date']))
                {
                    $cal_date = $this->context->check_date($packageDetail->lessons[0]['date']['from'], $packageDetail->lessons[0]['date']['to']);
                    $cal_date = json_decode($cal_date);
                    $allowDeadlineDate = $cal_date->allowDeadlineDate;
                    $deadLineDate = $cal_date->deadlineTimestamp;
                }
            }
        }
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
                            'action' => ['packages/new_user'],
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
                        <div class="col-6 col-md-6 col-sm-12 col-lg-6 col-xl-6 mb-3">
                            <label for="nameWithTitle" class="form-label">
                                نام کاربری * (شماره همراه یا ایمیل)
                                <svg id="check-username-btn" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="none" stroke="#872c02" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 15l1.5 1.5m.433 2.525a1.48 1.48 0 1 1 2.092-2.092l2.042 2.042a1.48 1.48 0 1 1-2.092 2.092zM16.5 9.5a7 7 0 1 0-14 0a7 7 0 0 0 14 0" />
                                </svg>
                            </label>
                            <?= $form->field($newUser, 'username')->textInput([
                                'class' => 'form-control text-start',
                                'id' => 'student-username',
                                'required' => true,
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46) || (event.charCode >= 32 && event.charCode <= 90) || (event.charCode >= 97 && event.charCode <= 122)"
                            ])->label(false); ?>
                            <div id="username-check-result" class="form-text"></div>
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
                        echo '<button type="submit" class="btn btn-primary pos-submit">پرداخت و ثبت عضو</button>';
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
