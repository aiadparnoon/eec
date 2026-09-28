<?php

/* @var $this \yii\web\View */
/* @var $content string */


use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use frontend\controllers\DashboardController;
use common\widgets\Alert;
use yii\widgets\Menu;
use yii\helpers\Url;
require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');
AppAsset::register($this);

$front = Yii::getAlias('@front');
$userDetail = DashboardController::user_detail();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="fa" class="light-style layout-navbar-fixed layout-menu-fixed" dir="rtl" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href="assets/images/fav.png">
    <meta name="theme-color" content="#5867dd">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>

<body>

<?php
$ac1 = ' active open';
$ac2 = '';
$active0 = $active1 = $active2 = $active3 = $active4 = $active5 = $active6 = $active7 = $active8 = $active88 = $active9 = $active10 = $active11 = $active12 = $active13 = $active14 = $active15  = $active16 = $active17 = $active18 = $active19 = $active20 = $active21 = $active22 = '';
switch (Yii::$app->controller->id)
{
    case 'dashboard': $active0 = 'active'; break;
    case 'users-manage': $active1 = 'active'; break;
    case 'collages-manage': $active2 = 'active'; break;
    case 'lessons': $active3 = 'active'; break;
    case 'courses': $active4 = 'active'; break;
    case 'packages': $active5 = 'active'; break;
    case 'teacher-manage': $active7 = 'active'; break;
    case 'certificate-manage': $active8 = 'active'; break;
    case 'course-topic': $active9 = 'active'; break;
    case 'reporting': $active10 = 'active'; break;
    case 'manage-brokers': $active11 = 'active'; break;
    case 'manage-members': $active12 = 'active'; break;
    case 'test-maker': $active13 = 'active'; $ac2 = 'active open'; $ac1 = ''; break;
    case 'upload-center': $active14 = 'active'; $ac2 = 'active open'; $ac1 = ''; break;
    case 'questions-bank': $active15 = 'active'; $ac2 = 'active open'; $ac1 = ''; break;
    case 'tests-groups': $active16 = 'active'; $ac2 = 'active open'; $ac1 = ''; break;
    case 'survey-maker': $active17 = 'active'; $ac2 = 'active open'; $ac1 = ''; break;
    case 'manage-news': $active18 = 'active'; break;
    case 'offline-exams': $active19 = 'active'; break;
    case 'wallet': $active20 = 'active'; break;
    case 'manage-financial': $active21 = 'active'; break;
    case 'canceling-requests': $active22 = 'active'; break;
}
?>
<?php $this->beginBody() ?>
<!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Menu -->

        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
                <img src="<?= $front ?>/assets/images/logo.png" style="max-height: 50px;max-width: 50px;">

                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <i class="bx menu-toggle-icon d-none d-xl-block fs-4 align-middle"></i>
                    <i class="bx bx-x d-block d-xl-none bx-sm align-middle"></i>
                </a>
            </div>

            <div class="menu-divider mt-0"></div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <!-- Dashboards -->
                <li class="menu-item <?= $active0 ?>">
                    <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('dashboard') ?>" class="menu-link">
                        <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                            <path fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2 18c0-1.54 0-2.31.347-2.876c.194-.317.46-.583.777-.777C3.689 14 4.46 14 6 14s2.31 0 2.876.347c.317.194.583.46.777.777C10 15.689 10 16.46 10 18s0 2.31-.347 2.877c-.194.316-.46.582-.777.776C8.311 22 7.54 22 6 22s-2.31 0-2.876-.347a2.35 2.35 0 0 1-.777-.776C2 20.31 2 19.54 2 18m12 0c0-1.54 0-2.31.347-2.876c.194-.317.46-.583.777-.777C15.689 14 16.46 14 18 14s2.31 0 2.877.347c.316.194.582.46.776.777C22 15.689 22 16.46 22 18s0 2.31-.347 2.877a2.36 2.36 0 0 1-.776.776C20.31 22 19.54 22 18 22s-2.31 0-2.876-.347a2.35 2.35 0 0 1-.777-.776C14 20.31 14 19.54 14 18M2 6c0-1.54 0-2.31.347-2.876c.194-.317.46-.583.777-.777C3.689 2 4.46 2 6 2s2.31 0 2.876.347c.317.194.583.46.777.777C10 3.689 10 4.46 10 6s0 2.31-.347 2.876c-.194.317-.46.583-.777.777C8.311 10 7.54 10 6 10s-2.31 0-2.876-.347a2.35 2.35 0 0 1-.777-.777C2 8.311 2 7.54 2 6m12 0c0-1.54 0-2.31.347-2.876c.194-.317.46-.583.777-.777C15.689 2 16.46 2 18 2s2.31 0 2.877.347c.316.194.582.46.776.777C22 3.689 22 4.46 22 6s0 2.31-.347 2.876c-.194.317-.46.583-.776.777C20.31 10 19.54 10 18 10s-2.31 0-2.876-.347a2.35 2.35 0 0 1-.777-.777C14 8.311 14 7.54 14 6" color="#42454b" />
                        </svg>
                        <div>میزکار</div>
                    </a>
                </li>
                <?php
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker' || (in_array('users-manage',Yii::$app->user->identity->access) !== false))
                {
                    ?>
                    <li class="menu-item <?= $active1 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('users-manage') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <path fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19 5l-7-3l-7 3l3.5 1.5v2S9.667 8 12 8s3.5.5 3.5.5v-2zm0 0v4m-3.5-.5v1a3.5 3.5 0 1 1-7 0v-1m-.717 8.203c-1.1.685-3.986 2.082-2.229 3.831C6.413 21.39 7.37 22 8.571 22h6.858c1.202 0 2.158-.611 3.017-1.466c1.757-1.749-1.128-3.146-2.229-3.83a7.99 7.99 0 0 0-8.434 0" color="#42454b" />
                            </svg>
                            <div>مدیریت کاربران</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('collages-manage',Yii::$app->user->identity->access))
                {
                    ?>
                    <li class="menu-item <?= $active2 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('collages-manage') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-width="1.5">
                                    <path stroke-linejoin="round" d="m16 10l2.15.645c1.373.412 2.06.618 2.455 1.15c.395.53.395 1.248.395 2.681V22" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h3m-3 4h3" />
                                    <path stroke-linejoin="round" d="M12 22v-3c0-.943 0-1.414-.293-1.707S10.943 17 10 17H9c-.943 0-1.414 0-1.707.293S7 18.057 7 19v3" />
                                    <path stroke-linecap="round" d="M2 22h20" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 22V6.717c0-2.51 0-3.766.791-4.389s1.956-.284 4.287.392l5 1.451c1.406.408 2.109.612 2.515 1.169C16 5.896 16 6.653 16 8.169V22" />
                                </g>
                            </svg>
                            <div>مدیریت دانشکده</div>
                        </a>
                    </li>
                    <?php
                }
                ?>
                <li class="menu-item <?= $ac1 ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                            <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                <path d="M2 2h14c1.886 0 2.828 0 3.414.586S20 4.114 20 6v6c0 1.886 0 2.828-.586 3.414S17.886 16 16 16H9m1-9.5h6M2 17v-4c0-.943 0-1.414.293-1.707S3.057 11 4 11h2m-4 6h4m-4 0v5m4-5v-6m0 6v5m0-11h6" />
                                <path d="M6 6.5a2 2 0 1 1-4 0a2 2 0 0 1 4 0" />
                            </g>
                        </svg>
                        <div>مدیریت دوره</div>
                    </a>
                    <ul class="menu-sub">
                        <?php
                        if(Yii::$app->user->identity->role == 'user' || in_array('lessons',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active3 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('lessons') ?>" class="menu-link">
                                    <div>دروس</div>
                                </a>
                            </li>
                            <?php
                        }
                        if(Yii::$app->user->identity->role == 'user' || in_array('courses',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active4 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('courses') ?>" class="menu-link">
                                    <div>دوره های کوتاه مدت</div>
                                </a>
                            </li>
                            <?php
                        }
                        if(Yii::$app->user->identity->role == 'user' || in_array('packages',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active5 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('packages') ?>" class="menu-link">
                                    <div>دوره های میان مدت</div>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </li>
                <?php
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker' || in_array('teacher-manage',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active7 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('teacher-manage') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                    <path d="M20 22v-5c0-1.886 0-2.828-.586-3.414S17.886 13 16 13l-4 9l-4-9c-1.886 0-2.828 0-3.414.586S4 15.114 4 17v5" />
                                    <path d="m12 15l-.5 4l.5 1.5l.5-1.5zm0 0l-1-2h2zm3.5-8.5v-1a3.5 3.5 0 1 0-7 0v1a3.5 3.5 0 1 0 7 0" />
                                </g>
                            </svg>
                            <div>مدیریت اساتید</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'broker' || Yii::$app->user->identity->username == '09122388496' || in_array('certificate-manage',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active8 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('certificate-manage') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                    <path d="M14.98 7.016s.5.5 1 1.5c0 0 1.589-2.5 3-3M9.995 2.021c-2.499-.105-4.429.182-4.429.182c-1.219.088-3.555.77-3.555 4.762c0 3.956-.025 8.834 0 10.779c0 1.188.736 3.96 3.282 4.108c3.095.18 8.67.219 11.228 0c.684-.039 2.964-.576 3.252-3.056c.299-2.57.24-4.355.24-4.78" />
                                    <path d="M22 7.016c0 2.761-2.24 5-5.005 5a5 5 0 0 1-5.005-5c0-2.762 2.241-5 5.005-5a5 5 0 0 1 5.005 5m-15.02 6h4m-4 4h8" />
                                </g>
                            </svg>
                            <div>مدیریت صدور مدرک</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker' || in_array('reporting',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active20 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('wallet') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                    <path d="M13 3.5h1c.93 0 1.395 0 1.777.102a3 3 0 0 1 2.12 2.122C18 6.105 18 6.57 18 7.5H5a2 2 0 1 1 0-4h3" />
                                    <path d="M3 5.5v10c0 2.828 0 4.243.879 5.121c.878.879 2.293.879 5.121.879h6c2.828 0 4.243 0 5.121-.879C21 19.743 21 18.328 21 15.5v-2c0-2.828 0-4.243-.879-5.121C19.243 7.5 17.828 7.5 15 7.5H7" />
                                    <path d="M21 12.5h-2c-.465 0-.698 0-.888.051a1.5 1.5 0 0 0-1.06 1.06c-.052.191-.052.424-.052.889s0 .697.051.888a1.5 1.5 0 0 0 1.06 1.06c.191.052.424.052.889.052h2m-10.5-14a3.5 3.5 0 0 1 3.163 5H7.337a3.5 3.5 0 0 1 3.163-5" />
                                </g>
                            </svg>
                            <div>کیف پول</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('reporting',Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'broker')
                {
                    ?>
                    <li class="menu-item <?= $active10 ?>">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <svg class="menu-icon xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                            <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                <path d="M12 3h.009M12 6h.009M12 9h.009M12 12h.009M12 15h.009M12 18h.009M6 7c.673-1.122 1.587-2 2.993-2c5.943 0 2.602 12 8.989 12c1.416 0 2.324-.884 3.018-2" />
                                <path d="M21 21H10c-3.3 0-4.95 0-5.975-1.025S3 17.3 3 14V3" />
                            </g>
                            </svg>
                            <div>گزارشگیری</div>
                        </a>
                        <ul class="menu-sub">
                            <li class="menu-item <?= $active10 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('reporting') ?>" class="menu-link">
                                    <div>دوره ها</div>
                                </a>
                            </li>
                            <li class="menu-item <?= $active10 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('reporting/installments') ?>" class="menu-link">
                                    <div>اقساط</div>
                                </a>
                            </li>
                            <li class="menu-item <?= $active10 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('reporting/courses-financial') ?>" class="menu-link">
                                    <div>صورت حساب مالی</div>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('manage-brokers',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active11 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-brokers') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-width="1.5">
                                    <path d="M6.5 9h-1m5 0h-1m-3-3h-1m5 0h-1m9 9h-1m1-4h-1" />
                                    <path stroke-linejoin="round" d="M14 8v14h4c1.886 0 2.828 0 3.414-.586S22 19.886 22 18v-6c0-1.886 0-2.828-.586-3.414S19.886 8 18 8zm0 0c0-2.828 0-4.243-.879-5.121C12.243 2 10.828 2 8 2s-4.243 0-5.121.879C2 3.757 2 5.172 2 8v2" />
                                    <path d="M8.025 13.955a2 2 0 1 1-3.999-.002a2 2 0 0 1 3.999.002ZM2.07 20.21c1.058-1.628 2.739-2.238 3.955-2.237s2.847.609 3.906 2.237c.068.105.087.235.025.344c-.247.439-1.016 1.31-1.57 1.368c-.639.068-2.307.078-2.36.078s-1.773-.01-2.41-.078c-.556-.059-1.324-.929-1.572-1.368a.33.33 0 0 1 .026-.344Z" />
                                </g>
                            </svg>
                            <div>مدیریت کارگزاران</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('manage-members',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active12 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-members') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <path fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M22 12H2m15 0V8c0-.827.173-1 1-1h1c.827 0 1 .173 1 1v4m0 5h-4c-1.886 0-2.828 0-3.414-.586S12 14.886 12 13v-1m-8 0v10m16-10v10M3 6V5c0-1.414 0-2.121.44-2.56C3.878 2 4.585 2 6 2h4c1.414 0 2.121 0 2.56.44C13 2.878 13 3.585 13 5v1c0 1.414 0 2.121-.44 2.56C12.122 9 11.415 9 10 9H6c-1.414 0-2.121 0-2.56-.44C3 8.122 3 7.415 3 6m6.5 3l.5 3M6.5 9L6 12" color="#42454b" />
                            </svg>
                            <div>مدیریت کارکنان</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('manage-news',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active18 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-news') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                    <path d="M2.5 12c0-4.478 0-6.718 1.391-8.109S7.521 2.5 12 2.5c4.478 0 6.718 0 8.109 1.391S21.5 7.521 21.5 12c0 4.478 0 6.718-1.391 8.109S16.479 21.5 12 21.5c-4.478 0-6.718 0-8.109-1.391S2.5 16.479 2.5 12m7.5-2h1m-1 5h4" />
                                    <path d="M14.958 11.462v-.953c0-1.873 0-2.81-.476-3.466c-.9-1.243-2.649-1.029-4.003-1.029S7.376 5.8 6.475 7.044C6 7.7 6 8.635 6 10.508v2.497c0 2.354 0 3.531.729 4.263S8.63 18 10.977 18h3.71c2.598 0 3.637-1.828 3.226-4.431c-.245-1.55-1.582-2.107-2.955-2.107" />
                                </g>
                            </svg>
                            <div>مدیریت اخبار</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || in_array('manage-financial',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active21 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('manage-financial') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <g fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" color="#42454b">
                                    <path d="M20.943 16.835a15.76 15.76 0 0 0-4.476-8.616c-.517-.503-.775-.754-1.346-.986C14.55 7 14.059 7 13.078 7h-2.156c-.981 0-1.472 0-2.043.233c-.57.232-.83.483-1.346.986a15.76 15.76 0 0 0-4.476 8.616C2.57 19.773 5.28 22 8.308 22h7.384c3.029 0 5.74-2.227 5.25-5.165"></path>
                                    <path d="M7.257 4.443c-.207-.3-.506-.708.112-.8c.635-.096 1.294.338 1.94.33c.583-.009.88-.268 1.2-.638C10.845 2.946 11.365 2 12 2s1.155.946 1.491 1.335c.32.37.617.63 1.2.637c.646.01 1.305-.425 1.94-.33c.618.093.319.5.112.8l-.932 1.359c-.4.58-.599.87-1.017 1.035S13.837 7 12.758 7h-1.516c-1.08 0-1.619 0-2.036-.164S8.589 6.38 8.189 5.8zm6.37 8.476c-.216-.799-1.317-1.519-2.638-.98s-1.53 2.272.467 2.457c.904.083 1.492-.097 2.031.412c.54.508.64 1.923-.739 2.304c-1.377.381-2.742-.214-2.89-1.06m1.984-5.06v.761m0 5.476v.764"></path>
                                </g>
                            </svg>
                            <div>مالی</div>
                        </a>
                    </li>
                    <?php
                }
                if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker' || in_array('canceling-requests',Yii::$app->user->identity->access) !== false)
                {
                    ?>
                    <li class="menu-item <?= $active22 ?>">
                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('canceling-requests') ?>" class="menu-link">
                            <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                                <path fill="none" stroke="#42454b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12s4.477 10 10 10s10-4.477 10-10m-7 3L9 9m0 6l6-6" />
                            </svg>
                            <div>درخواست انصراف</div>
                        </a>
                    </li>
                    <?php
                }
                ?>





                <li class="menu-item <?= $ac2 ?>">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <svg class="menu-icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24">
                            <g fill="none" stroke="#42454b" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.992 12h.009m-.017 4h.01M12 8h.009" />
                                <path stroke-width="1.5" d="M2.484 12c0-4.478 0-6.718 1.392-8.109C5.266 2.5 7.506 2.5 11.984 2.5s6.718 0 8.11 1.391c1.39 1.391 1.39 3.63 1.39 8.109c0 4.478 0 6.718-1.39 8.109c-1.392 1.391-3.631 1.391-8.11 1.391s-6.717 0-8.108-1.391S2.484 16.479 2.484 12Z" />
                            </g>
                        </svg>
                        <div>سایر موارد</div>
                    </a>
                    <ul class="menu-sub">
                        <?php
                        if(Yii::$app->user->identity->role == 'user' || in_array('test-maker',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active13 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('test-maker') ?>" class="menu-link">
                                    <div>آزمون آنلاین ساز</div>
                                </a>
                            </li>
                            <li class="menu-item <?= $active17 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('survey-maker') ?>" class="menu-link">
                                    <div>نظرسنجی</div>
                                </a>
                            </li>
                            <?php
                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                            {
                                ?>
                                <li class="menu-item <?= $active17 ?>">
                                    <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('survey-maker/management-survey') ?>" class="menu-link">
                                        <div>نظرسنجی مدیریتی</div>
                                    </a>
                                </li>
                                    <?php
                            }
                            ?>
                            <?php
                        }
//                        if(Yii::$app->user->identity->role == 'user' || in_array('offline-exams',Yii::$app->user->identity->access))
                        if (Yii::$app->user->identity->username == '09351306525' || Yii::$app->user->identity->username == '09122881335' || Yii::$app->user->identity->username == '09121539367')
                        {
                            ?>
                            <li class="menu-item <?= $active13 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('offline-exams') ?>" class="menu-link">
                                    <div>آزمون حضوری</div>
                                </a>
                            </li>
                            <?php
                        }
                        if(Yii::$app->user->identity->role == 'user' || in_array('test-maker',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active15 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('questions-bank') ?>" class="menu-link">
                                    <div>بانک سوالات</div>
                                </a>
                            </li>
                            <?php
                        }
                        if(Yii::$app->user->identity->role == 'user' || in_array('test-maker',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active16 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('tests-groups') ?>" class="menu-link">
                                    <div>گروهبندی سوالات</div>
                                </a>
                            </li>
                            <?php
                        }
                        if(Yii::$app->user->identity->role == 'user' || in_array('upload-center',Yii::$app->user->identity->access) !== false)
                        {
                            ?>
                            <li class="menu-item <?= $active14 ?>">
                                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('upload-center') ?>" class="menu-link">
                                    <div>آپلود سنتر</div>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </li>


            </ul>
        </aside>
        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
            <!-- Navbar -->

            <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                <div class="container-fluid">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="bx bx-menu bx-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item navbar-search-wrapper mb-0">
                                <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                                    <i class="bx bx-search-alt bx-sm"></i>
                                    <span class="d-none d-md-inline-block text-muted">جستجو <span class="d-inline-block" dir="ltr">(Ctrl+/)</span></span>
                                </a>
                            </div>
                        </div>
                        <!-- /Search -->

                        <ul class="navbar-nav flex-row align-items-center ms-auto">


                            <!-- Style Switcher -->
                            <li class="nav-item me-2 me-xl-0">
                                <a class="nav-link style-switcher-toggle hide-arrow" href="javascript:void(0);">
                                    <i class="bx bx-sm"></i>
                                </a>
                            </li>
                            <!--/ Style Switcher -->


                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="<?= $front.'/'.$userDetail['profile'] ?>" alt class="rounded-circle">
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="pages-account-settings-account.html">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="<?= $front.'/'.$userDetail['profile'] ?>" alt class="rounded-circle">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-semibold d-block"><?= $userDetail['fullName'] ?></span>
                                                    <small><?= $userDetail['role'] ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('profile'); ?>">
                                            <i class="bx bx-user me-2"></i>
                                            <span class="align-middle">پروفایل من</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('setting'); ?>">
                                            <i class="bx bx-cog me-2"></i>
                                            <span class="align-middle">تنظیمات</span>
                                        </a>
                                    </li>



                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl('site/logout'); ?>">
                                            <i class="bx bx-power-off me-2"></i>
                                            <span class="align-middle">خروج</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>

                    <!-- Search Small Screens -->
                    <div class="navbar-search-wrapper search-input-wrapper d-none">
                        <input type="text" class="form-control search-input container-fluid border-0" placeholder="جستجو ..." aria-label="Search...">
                        <i class="bx bx-x bx-sm search-toggler cursor-pointer"></i>
                    </div>
                </div>
            </nav>

            <!-- / Navbar -->

            <!-- Content wrapper -->
            <div class="content-wrapper">
    <?= $content ?>

                <!-- Footer -->
                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-fluid d-flex flex-wrap justify-content-between py-3 flex-md-row flex-column">
                        <div class="mb-2 mb-md-0">
                           تمامی حقوق برای معاونت فناوری های دیجیتالی دانشگاه تهران محفوظ می باشد. طراحی و توسعه توسط تیم <a href="https://karzaan.com" target="_blank">کارزان</a>
                        </div>
                    </div>
                </footer>
                <!-- / Footer -->

                <div class="content-backdrop fade"></div>
            </div>
            <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>
</div>
<!-- / Layout wrapper -->
<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
