<?php

use yii\widgets\ActiveForm;

$this->title = 'ثبت نمرات';
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <div class="card-header d-flex justify-content-between align-items-center">
            <ol class="lh-1-85 breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">مدیریت دوره</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">دوره های جامع و یکساله</a>
                </li>
                <li class="breadcrumb-item active">ثبت نمرات دوره (<?= $model->title['main_fa'] ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2">
                <?php
                if($model->type == '1')
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl(['courses/edit-course','_id' => (string) $model->_id]).'">بازگشت به دوره</a>';
                else
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl(['packages/edit-package','_id' => (string) $model->_id]).'">بازگشت به دوره</a>';
                ?>
            </span>
        </div>
    </nav>
    <!-- Basic Bootstrap Table -->
    <div class="card">
        <h5 class="card-header heading-color">لیست اعضای دوره <?= $model->title['main_fa'] ?></h5>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                <tr>
                    <th>نام عضو</th>
                    <?php
                    if($model->lessons != null)
                    {
                        foreach ($model->lessons as $lesson)
                        {
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                            if($lessonDetail != null)
                                echo '<td>'.$lessonDetail->title.'</td>';
                        }
                    }
                    ?>
                </tr>
                </thead>
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['register_grades'],
                        "method" => "post",
                    ]
                ); ?>
                <input type="hidden" name="course_id" value="<?= (string)$model->_id ?>">
                <tbody class="table-border-bottom-0">
                    <?php
                    if($users != null)
                    {
                        foreach ($users as $user)
                        {
                            $memberDetail = $this->context->member_detail($user->username);
                            if($memberDetail != null)
                            {
                                $score = $this->context->score((string) $model->_id, (string) $user->_id);
                                echo '<tr>';
                                echo '<td>'.$memberDetail->first_name.' '. $memberDetail->last_name.'</td>';
                                if($model->lessons != null)
                                {
                                    foreach ($model->lessons as $lesson)
                                    {
                                        $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                                        $name = (string) $memberDetail->_id.'-'.(string) $lessonDetail->_id;
                                        $value = '';
                                        if($score != null)
                                        {
                                            $i = 0;
                                            foreach ($score->scores as $item)
                                                if($item['lesson'] == (string) $lessonDetail->_id)
                                                    $value = $item['score'];
                                        }
                                        if($lessonDetail != null)
                                            echo '<td><input onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode === 46" class="form-control" name="'.$name.'" value="'.$value.'"></td>';
                                    }
                                }
                                echo '</tr>';
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
            <div class="card-body">
                <?php
                if($users != null)
                    echo '<button type="submit" class="btn btn-block btn-label-success">ثبت نهایی نمرات</button>';
                ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
    <!--/ Basic Bootstrap Table -->
</div>