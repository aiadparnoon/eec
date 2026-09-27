<?php


use frontend\controllers\DashboardController;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use app\models\Lessons;
use frontend\controllers;

$this->title = 'مدیریت دروس دوره '.Html::encode($courseDetail->title['main_fa']);
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');
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
    toastr.warning("عنوان درس وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if(Yii::$app->session->getFlash('status') == '4')
        $script = <<< JS
    toastr.warning("درس مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
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
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <div class="card-header d-flex justify-content-between align-items-center">
            <ol class="lh-1-85 breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">مدیریت دوره</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">دوره ها</a>
                </li>
                <li class="breadcrumb-item active">مدیریت دوره (<?= Html::encode($courseDetail->title['main_fa']) ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2"><a class="h6" href="<?= Yii::$app->urlManager->createAbsoluteUrl('packages') ?>">بازگشت</a></span>
        </div>
    </nav>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">دروس دوره ی <?= Html::encode($courseDetail->title['main_fa']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <?php
                if($courseDetail != null)
                {
                    if($lessons != null)
                    {
                        foreach ($lessons as $lesson)
                        {
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                            $teacherDetail = null;
                            $from = null;
                            $to = null;
                            if(array_key_exists('teachers',$lesson))
                                $teacherDetail = DashboardController::teacher_detail($lesson['teachers']);
                            $lessonSessions = $this->context->lesson_sessions((string) $courseDetail->_id, $lesson['_id']);
                            if(array_key_exists('date' , $lesson))
                            {
                                $from = explode('-',$lesson['date']['from']);
                                $to = explode('-',$lesson['date']['to']);
                            }
                            $accId = 'accId'.rand();
                            $archiveId = 'archiveId'.rand();
                            ?>
                            <div class="col-xl-4 col-lg-6 col-md-6 d-flex align-items-stretch">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mx-auto mb-3">
                                            <img src="<?= $front.'/lesson_images/'.$lessonDetail->imagePreview ?>" alt="Avatar Image" class="rounded-circle w-px-100">
                                        </div>
                                        <h5 class="mb-1 card-title primary-font"><?= Html::encode($lessonDetail->title) ?></h5>
                                        <span class="lh-1-85">استاد:
                                            <?php
                                            if($teacherDetail != null)
                                                echo Html::encode($teacherDetail->first_name.' '.$teacherDetail->last_name);
                                            else
                                                echo 'مشخص نشده';
                                            ?>
                                        </span>
                                        <?php
                                        if($from != null && $to != null)
                                        {
                                            ?>
                                            <div class="d-flex align-items-center justify-content-center my-3 gap-2">
                                                <a href="javascript:;" class="me-1"><span class="badge bg-label-secondary">شروع: <?= $from[0].'/'.$from[1].'/'.$from[2] ?></span></a>
                                                <a href="javascript:;"><span class="badge bg-label-warning">اتمام: <?= $to[0].'/'.$to[1].'/'.$to[2] ?></span></a>
                                            </div>
                                                <?php
                                        }
                                        ?>
                                        <div class="demo-inline-spacing">
                                            <?php
                                            if(array_key_exists('meeting', $lesson) && Yii::$app->user->identity->role && Yii::$app->user->identity->role != 'broker' && $courseDetail->status == '1')
                                            {
                                                ?>
                                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/go-to-class','courseUrl'=>$lesson['meeting']['url']],'https') ?>" target="_blank" class="btn btn-xl btn-label-linkedin">
                                                    ورود به کلاس
                                                </a>
                                                    <?php
                                            }
                                            ?>
                                            <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/manage-lesson', 'courseId' => (string) $courseDetail->_id, 'lessonId' => $lesson['_id']],'https') ?>" type="button" class="btn btn-xl btn-label-github">
                                                 محتوای درس
                                            </a>
                                        </div>
                                        <div class="divider">
                                            <?php
                                            if($lessonSessions != null)
                                            {
                                                if($lessonSessions->sessions != null)
                                                {
                                                    if(count($lessonSessions->sessions) > 0)
                                                    {
                                                        ?>
                                                        <div class="divider-text">
                                                            <a class="btn btn-label-info me-1 mb-2" data-bs-toggle="collapse" href="#<?= $accId ?>" role="button" aria-expanded="true" aria-controls="collapseExample">
                                                                آمار جلسات
                                                            </a>
                                                        </div>
                                                        <div class="collapse" id="<?= $accId ?>" style="">
                                                            <div class="d-grid d-sm-flex p-3 border">
                                                                <ul class="timeline">
                                                                    <?php
                                                                    $sessionCounter = 1;
                                                                    $counter = 1;
                                                                    $sessionRow = 0;
                                                                    foreach ($lessonSessions->sessions as $session)
                                                                    {
                                                                        $showSessionDetail = 'showSessionDetail'.rand();
                                                                        $sessionFrom = strtotime($session['from']);
                                                                        $sessionTo = strtotime($session['to']);
                                                                        $sessionTitle = $this->context->digit2word($sessionCounter++);
                                                                        $lessonsTitle =  'جزئیات جلسه <span class="badge bg-label-secondary">'.$counter.'</span> درس <span class="badge bg-label-secondary">'.$lessonDetail->title.'</span></span> از دوره '.$courseDetail->title['main_fa'];
                                                                        ?>
                                                                        <li class="timeline-item timeline-item-transparent ps-4">
                                                                            <span class="timeline-point timeline-point-info"></span>
                                                                            <div class="timeline-event pb-0">
                                                                                <div class="timeline-header mb-1">
                                                                                    <!--                                                                                   <h6 class="mb-0 mt-n1">جلسه --><?php //= $sessionTitle ?><!-- </h6>-->
                                                                                    <h6 class="mb-0 mt-n1">جلسه <?= $counter ?> </h6>
                                                                                    <small class="text-muted mt-1 mt-sm-0 mb-1 mb-sm-0"><?= jdate('Y/m/d', $sessionFrom) ?></small>
                                                                                </div>
                                                                                <div class="timeline-header mb-2">شروع از <?= jdate('H:i', $sessionFrom) ?> تا <?= jdate('H:i', $sessionTo) ?></div>
                                                                                <div class="timeline-header mb-2"><?= $session['num_participants'] ?> دانشپذیر حاضر &nbsp;&nbsp;&nbsp;&nbsp;<a href="#" class="badge bg-label-dark" data-bs-target="#<?= $showSessionDetail ?>" data-bs-toggle="modal">مشاهده جزئیات</a></div>
                                                                            </div>
                                                                        </li>
                                                                        <div class="modal fade" id="<?= $showSessionDetail ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                                                            <div class="modal-dialog modal-lg modal-dialog-centered modal-add-new-role">
                                                                                <div class="modal-content p-3 p-md-5">
                                                                                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                    <div class="modal-body">
                                                                                        <!-- Add role form -->
                                                                                        <div id="addRoleForm" class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework" onsubmit="return false" novalidate="novalidate">
                                                                                            <div class="col-12">
                                                                                                <h5><?= $lessonsTitle ?></h5>
                                                                                                <span class="badge bg-label-success h4"><a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/session_report','_id'=>(string) $lessonSessions->_id, 'row' => $sessionRow, 'counter' => $counter],'https') ?>">گزارش اکسل</a></span>
                                                                                                <div class="table-responsive text-nowrap" id="sessionDetail">
                                                                                                    <?php
                                                                                                    if($session['participants'] != null)
                                                                                                    {
                                                                                                        ?>
                                                                                                        <div class="table-responsive text-nowrap">
                                                                                                            <table class="table">
                                                                                                                <thead>
                                                                                                                <tr>
                                                                                                                    <th>#</th>
                                                                                                                    <th>دانشپذیر</th>
                                                                                                                    <th>نام کاربری</th>
                                                                                                                    <th>ساعت ورود</th>
                                                                                                                    <th>ساعت خروج</th>
                                                                                                                </tr>
                                                                                                                </thead>
                                                                                                                <tbody class="table-border-bottom-0">
                                                                                                                <?php
                                                                                                                $row = 1;
                                                                                                                foreach ($session['participants'] as $participant)
                                                                                                                {
                                                                                                                    $enter = strtotime($participant['enter']);
                                                                                                                    $exit = strtotime($participant['exit']);
                                                                                                                    $participantUsername = $this->context->participant_username($participant['_id']);
                                                                                                                    if($participant['name'] == 'eec admin')
                                                                                                                        $name = 'ادمین ادوبی';
                                                                                                                    else
                                                                                                                        $name = $participant['name'];
                                                                                                                    ?>
                                                                                                                    <tr>
                                                                                                                        <td><span class="badge badge-center bg-label-secondary"><?= $row ?></span></td>
                                                                                                                        <td><?= Html::encode($name) ?></td>
                                                                                                                        <td>
                                                                                                                            <?php
                                                                                                                            if($participantUsername != null)
                                                                                                                                echo Html::encode($participantUsername->username);
                                                                                                                            else
                                                                                                                                echo '-';
                                                                                                                            ?>
                                                                                                                        </td>
                                                                                                                        <td><?= jdate('H:i:s', $enter) ?></td>
                                                                                                                        <td><?= jdate('H:i:s', $exit) ?></td>
                                                                                                                    </tr>
                                                                                                                    <?php
                                                                                                                    $row++;
                                                                                                                }
                                                                                                                ?>
                                                                                                                </tbody>
                                                                                                            </table>
                                                                                                        </div>
                                                                                                        <?php
                                                                                                    }
                                                                                                    ?>
                                                                                                </div>
                                                                                            </div>
                                                                                            <!--/ Add role form -->
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <?php
                                                                        $counter++;
                                                                        $sessionRow++;
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    else
                                                        echo '<div class="alert alert-secondary" role="alert">برای این درس جلسه ای ثبت نشده است</div>';
                                                }
                                                else
                                                    echo '<div class="alert alert-secondary" role="alert">برای این درس جلسه ای ثبت نشده است</div>';
                                            }
                                            else
                                                echo '<div class="alert alert-secondary" role="alert">برای این درس جلسه ای ثبت نشده است</div>';
                                            ?>
                                        </div>
                                        <div class="divider">
                                            <?php
                                            if($lessonSessions != null && Yii::$app->user->identity->role != 'cnt')
                                            {
                                                if($lessonSessions->archives != null)
                                                {
                                                    if(count($lessonSessions->archives) > 0)
                                                    {
                                                        ?>
                                                        <div class="divider-text">
                                                            <a class="btn btn-label-info me-1 mb-2" data-bs-toggle="collapse" href="#<?= $archiveId ?>" role="button" aria-expanded="true" aria-controls="collapseExample">
                                                                آرشیو جلسات
                                                            </a>
                                                        </div>
                                                        <div class="collapse" id="<?= $archiveId ?>" style="">
                                                            <div class="d-grid d-sm-flex p-3 border">
                                                                <ul class="timeline">
                                                                    <?php
                                                                    $archiveCounter = 1;
                                                                    $aCounter = 1;
                                                                    foreach ($lessonSessions->archives as $archive)
                                                                    {
                                                                        $showArchiveDetail = 'showArchiveDetail'.rand();
                                                                        $lessonsTitle =  'آرشیو جلسه <span class="badge bg-label-secondary">'.$aCounter.'</span> درس <span class="badge bg-label-secondary">'.Html::encode($lessonDetail->title).'</span></span> از دوره '.Html::encode($courseDetail->title['main_fa']);
                                                                        ?>
                                                                        <li class="timeline-item timeline-item-transparent ps-4">
                                                                            <span class="timeline-point timeline-point-info"></span>
                                                                            <div class="timeline-event pb-0">
                                                                                <div class="timeline-header mb-1">
                                                                                    <!--                                                                                   <h6 class="mb-0 mt-n1">جلسه --><?php //= $sessionTitle ?><!-- </h6>-->
                                                                                    <h6 class="mb-0 mt-n1">جلسه <?= $aCounter ?> </h6>
                                                                                    <small class="text-muted mt-1 mt-sm-0 mb-1 mb-sm-0"><?= (int) ($archive['duration']/3600).' ساعت و '.(int)(($archive['duration'] % 3600) / 60).' دقیقه و '.(int)(($archive['duration'] % 3600) % 60).' ثانیه' ?></small>
                                                                                </div>
                                                                                <div class="timeline-header mb-2"><a target="_blank" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/go-to-class','courseUrl'=>$archive['url']],'https') ?>" class="badge bg-label-dark" >مشاهده آرشیو</a></div>
                                                                            </div>
                                                                        </li>
                                                                        <?php
                                                                        $aCounter++;
                                                                    }
                                                                    ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    else
                                                        echo '<div class="alert alert-secondary" role="alert">برای این درس آرشیوی ثبت نشده است</div>';
                                                }
                                                else
                                                    echo '<div class="alert alert-secondary" role="alert">برای این درس آرشیوی ثبت نشده است</div>';
                                            }
                                            else
                                                echo '<div class="alert alert-secondary" role="alert">برای این درس آرشیوی ثبت نشده است</div>';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>



