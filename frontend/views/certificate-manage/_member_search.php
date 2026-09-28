<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Users;
use app\models\UploadedExcels;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');

use MongoDB\BSON\ObjectId;
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
)
?>
<style>
    #tbl {
        max-height:200px;
    }
</style>

<nav class="navbar navbar-example navbar-expand-lg bg-light">
    <div class="container-fluid">
        <?php $form = ActiveForm::begin([
            'action'=>['certificate-manage/course-members'],
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
        <button class="btn btn-info" style="margin-right: 5px;" type="submit">جستجو</button>
        <?php ActiveForm::end(); ?>
        <div>
            <div class="btn-group" role="group" aria-label="Basic example">
                <?php
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                {
                    $targetTimestamp = strtotime('2026-03-21 00:00:00');

                    $type = (string)$packageDetail->type;
                    $courseDate = null;
                    if ($type === '1') {
                        $courseDate = $packageDetail->lessons[0]['date']['from'] ?? null;
                    } elseif ($type === '2') {
                        $courseDate = $packageDetail->date['from'] ?? null;
                    }
                    $docTimestamp = null;

                    if ($courseDate)
                    {
                        list($year, $month, $day) = explode('-', $courseDate);
                        $docTimestamp = jmktime(0, 0, 0, (int)$month, (int)$day, (int)$year,1);
                    }


                    if ($docTimestamp !== null && $docTimestamp >= $targetTimestamp) {
                        ?>
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['certificate-manage/new-print-all-certificate', '_id' => (string) $packageDetail->_id]) ?>" class="btn btn-info" style="margin-right: 5px;" type="submit">چاپ تمام درخواست ها</a>
                        <?php
                    }
                    else
                    {
                        ?>
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['certificate-manage/print-all-certificate', '_id' => (string) $packageDetail->_id]) ?>" class="btn btn-info" style="margin-right: 5px;" type="submit">چاپ تمام درخواست ها</a>
                        <?php
                    }
                }
                ?>
                <?php
                if(Yii::$app->user->identity->role == 'user')
                {
                    $digitalCert = false;
                    if($packageDetail->digital_cert != null)
                        if($packageDetail->digital_cert == true)
                            $digitalCert = true;
                    if($digitalCert)
                        echo '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#basicModal">گواهی دیجیتال: فعال</button>';
                    else
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#basicModal">گواهی دیجیتال: غیرفعال</button>';
                }
                ?>
            </div>
        </div>
        <div class="collapse navbar-collapse" id="navbar-ex-4">
            <div class="navbar-nav me-auto">
                <a class="nav-item nav-link active" href="javascript:void(0)"></a>
                <a class="nav-item nav-link" href="javascript:void(0)"></a>
                <a class="nav-item nav-link" href="javascript:void(0)"></a>
            </div>

            <?php $form = ActiveForm::begin([
                'action'=>['certificate-manage/change_preview'],
                'method'=>'post',
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
            $print = array(
                '1' => 'حالت عمودی',
//                '2' => 'حالت افقی',
                '3' => 'افقی نستعلیق',
//                '4' => 'دانشکده زبان افقی',
                '5' => 'توانمندسازی زبان',
                '6' => 'کارنامه فارسی',
                '7' => 'کارنامه انگلیسی',
            );
            echo $form->field($packageDetail, 'print_preview')->dropDownList(
                $print,
                [
                    'placeholder' => 'فیلتر بر اساس نام خانوادگی',
                    'class' => 'form-control form-control-sm',
                    'id' => '',
                    'data-allow-clear' => true
                ]
            )->label(false);
            ?>
            <button class="btn btn-info" style="margin-right: 5px;" type="submit">تغییر </button>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</nav>


<div class="modal fade" id="basicModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="exampleModalLabel1">تغییر گواهی دیجیتال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin([
                    'action'=>['certificate-manage/change_digital_cert'],
                    'method'=>'post',
                    'options' => [
                        'class' => 'd-flex',
                    ],
                    'fieldConfig' => [
                        'options' => [
                            'tag' => false,
                        ],
                    ],
                ]);
                echo $form->field($packageDetail, '_id')->hiddenInput()->label(false);
                ?>

                آیا از تغییر صدور گواهی دیجیتال مطمئن هستید؟
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