<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\UserTests;
use frontend\assets\Select2Asset;
Select2Asset::register($this);
$this->title = 'تصحیح سوالات تشریحی';
if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("نتیجه تصحیح ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.danger("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
$select2 = <<< JS
    $('#users').select2({
    placeholder: "فیلتر بر اساس کاربر"
});
JS;
$this->registerJs($select2);
$model = new UserTests();
?>
<div class="card">
    <nav class="navbar navbar-expand-lg bg-white">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="navbar-ex-6">
                <div class="navbar-nav me-auto">
                    <a class="nav-item nav-link active" href="javascript:void(0)">تصحیح سوالات آزمون <?= Html::encode($testDetail->title) ?> برای <?= Html::encode($userDetail->first_name.' '.$userDetail->last_name).' - نمره دانشپذیر تا کنون: '.$finalScore ?></a>
                </div>
                <ul class="navbar-nav ms-lg-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#other-user">تصحیح آزمون سایر کاربران این آزمون</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/manage-lesson', 'courseId' => $userTest->course_id, 'lessonId' => $userTest->lesson_id]) ?>"> بازگشت</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="card-body">
        <ul class="timeline timeline-dashed mt-4">
            <?php
            $tryCounter = 1;
            $row1 = 0;
            foreach ($userTest->questions as $try)
            {
                ?>
            <li class="timeline-item timeline-item-dark mb-4 border-1">
                <span class="timeline-indicator timeline-indicator-primary">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </span>
                    <div class="timeline-event">
                        <div class="timeline-header border-bottom mb-3 mt-n1">
                            <h5 class="mb-1 mb-sm-2">تلاش <?= $this->context->digit2word($tryCounter) ?> - نمره <?= $try['score'] ?></h5>
                        </div>
                        <?php $form = ActiveForm::begin(
                            [
                                'action' => ['add_question_correct'],
                                "method" => "post",
                            ]
                        ); ?>
                    <?php
                    $questionCounter = 1;
                    $row2 = 0;
                    foreach ($try['questions'] as $question)
                    {
                        if($question['type'] == '3')
                        {
                            $correctId = 'id'.rand();
                            $incorrectId = 'id'.rand();
                            $id = 'id'.rand();
                            $correct = '';
                            $incorrect = '';
                            if(array_key_exists('result',$question))
                                if($question['result'] == 'false')
                                    $incorrect = 'checked';
                                else
                                    $correct = 'checked';
                            ?>
                                <input type="hidden" name="_id" value="<?= (string) $userTest->_id ?>">
                                <input type="hidden" name="row1" value="<?= $row1 ?>">
                                <input type="hidden" name="row2" value="<?= $row2 ?>">
                            <div class="list-group list-group-flush">
                                <div class="navbar-expand-lg bg-white mb-2 rounded cursor-move d-flex p-2" draggable="false">
                                    <div class="container-fluid">
                                        <div class="disabled" href="javascript:void(0)">
                                            متن سوال: <?= Html::encode($question['question_text']) ?>
                                        </div>
                                        <div class="input-group">
                                            پاسخ کاربر: <?= Html::encode($question['user_answer']) ?>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md mb-md-0 mb-2">
                                                <div class="form-check form-check-success custom-option custom-option-basic <?= $correct ?>">
                                                    <label class="form-check-label custom-option-content" for="customRadioTemp1">
                                                        <input name="<?= $question['_id'] ?>" class="form-check-input" type="radio" value="true" id="<?= $correctId ?>" <?= $correct ?>>
                                                        <span class="custom-option-header">
                                                                    <span class="h6 mb-0">صحیح</span>
                                                                </span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md">
                                                <div class="form-check form-check-danger custom-option custom-option-basic <?= $incorrect ?>">
                                                    <label class="form-check-label custom-option-content" for="customRadioTemp2">
                                                        <input name="<?= $question['_id'] ?>" class="form-check-input" type="radio" value="false" id="<?= $incorrectId ?>" <?= $incorrect ?>>
                                                        <span class="custom-option-header">
                                                                    <span class="h6 mb-0">غلط</span>
                                                                </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        $row2++;
                    }
                    ?>
                        <button type="submit" class="btn btn-success text-black btn-block" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#create-new-headline">ثبت پاسخ های سوالات تشریحی تلاش <?= $this->context->digit2word($tryCounter++) ?></button>
                   <?php ActiveForm::end(); ?>
                    </div>
            </li>
            <?php
                $row1++;
            }
            ?>
        </ul>
    </div>
</div>

<div class="modal fade" id="other-user" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده پاسخ سایر کاربران این آزمون</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['go_to_user_exam'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">کاربر *</label>
                        <?php
                        echo $form->field($model, '_id')->dropDownList(
                            $otherUser,
                            [
                                'prompt' => 'لطفا کاربر را مشخص کنید',
                                'class' => 'select2 form-select',
                                'required' => true,
                                'data-allow-clear' => true,
                                'id' => rand(),
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
                <button type="submit" class="btn btn-primary">مشاهده پاسخ سوالات</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>