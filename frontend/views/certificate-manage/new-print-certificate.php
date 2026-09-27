<?php

use yii\helpers\Html;

require_once(Yii::$app->basePath . '/web/jdf.php');
date_default_timezone_set('Asia/Tehran');
$front = Yii::getAlias('@front');
$this->title = 'چاپ مدرک';

$script = <<< JS
    $('.change-mode').click(function() {
        const mode = $(this).attr('id');
        $('.active-tab').removeClass('active-tab')

        if(mode == 'vertical') {
            $('.basic').removeClass('landscape')
            $('.basic').addClass('active-tab')
            $('#print-style').text("@page {size: 8.3in 11.7in; margin: 0;}")
        }

        if(mode == 'landscape') {
            $('.basic').addClass('active-tab')
            $('.basic').addClass('landscape')
            $('#print-style').text("@page {size: 9.25in 7in; margin: 0;}")
        }

        if(mode == 'management-college') {
            $('.management-college').addClass('active-tab')
            $('#print-style').text("@page {size: 9.25in 7in; margin: 0;}")
        }

        if(mode == 'lang-college') {
            $('.lang-college').addClass('active-tab')
            $('#print-style').text("@page {size: 9.25in 7in; margin: 0;}")
        }

        if(mode == 'lang-vertical') {
            $('.lang-vertical').addClass('active-tab')
            $('#print-style').text("@page {size: 8.3in 11.7in; margin: 0;}")
        }

        if(mode == 'table-fa') {
            $('.table-fa').addClass('active-tab')
            $('#print-style').text("@page {size: 8.3in 11.7in; margin: 0;}")
        }

        if(mode == 'table-en') {
            $('.table-en').addClass('active-tab')
            $('#print-style').text("@page {size: 8.3in 11.7in; margin: 0;}")
        }

    });
JS;

$this->registerJs($script);
$active1 = $active2 = $active3 = $active4 = $active5 = $active6 = '';
$btn1 = $btn2 = $btn3 = $btn4 = $btn5 = $btn6 = $btn7 = 'btn-primary';
if ($courseDetail->print_preview != null) {
    if ($courseDetail->print_preview == '1') {
        $active1 = 'active-tab';
        $btn1 = 'btn-success';
    } else if ($courseDetail->print_preview == '2') {
        $active1 = 'active-tab';
        $btn2 = 'btn-success';
    } else if ($courseDetail->print_preview == '3') {
        $active2 = 'active-tab';
        $btn3 = 'btn-success';

        $scriptClick = <<< JS
            $('#management-college').click();
JS;

        $this->registerJs($scriptClick);
    } else if ($courseDetail->print_preview == '4') {
        $active3 = 'active-tab';
        $btn4 = 'btn-success';
    } else if ($courseDetail->print_preview == '5') {
        $active4 = 'active-tab';
        $btn5 = 'btn-success';
    } else if ($courseDetail->print_preview == '6') {
        $active5 = 'active-tab';
        $btn6 = 'btn-success';
    } else if ($courseDetail->print_preview == '7') {
        $active6 = 'active-tab';
        $btn7 = 'btn-success';
    }
} else
    $active1 = 'active-tab';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرینت مدرک</title>

    <style>
        @font-face {
            font-family: "nastaliq";
            src: url("/assets/vendor/fonts/farsi-fonts-en-num/IranNastaliq.ttf") format("truetype");
            font-weight: 400;
        }

        body,
        html {
            background-color: #eee !important;
        }

        .container {
            padding: 10px 20px;
            background-color: white;
            border: 0px solid #000;
            max-width: 1000px;
            margin: 0 auto;
        }

        h4 {
            width: 100%;
            font-weight: bold;
            text-align: center;
        }

        h5 {
            width: 100%;
            font-weight: bold;
            text-align: center;
        }

        h6 {
            width: 100%;
            font-weight: bold;
            text-align: center;
        }

        .basic .top-info {
            /* display: flex;
            justify-content: center;
            align-items: center;
            gap: 25px;
            margin-top: 0; */
        }

        .top-info {
            font-size: 12px;
            margin-top: 35px;
            text-align: left;
        }

        .top-info span {
            display: block;
            margin-bottom: 10px;
        }


        h1 {
            font-weight: black;
            margin-top: 40px;
            margin-bottom: 60px;
            text-align: center;
        }

        .text {
            margin: 60px auto;
            text-align: right;
        }

        .landscape .text {
            margin: 40px auto;
        }

        .text p {
            margin: 7px 0;
            line-height: 30px;
            font-size: 16px;
        }

        .text p span {
            font-weight: bold;
        }

        .en-text {
            direction: ltr;
            text-align: left;
            margin-top: 40px;
        }

        .en-text p {
            margin: 0;
            line-height: 28px;
        }

        .signatures {
            margin-top: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 60px;
        }

        .signatures>div {
            position: relative;
            font-size: 12px;
        }

        .signatures>div>img {
            position: absolute;
            width: 100px;
            height: 100px;
            bottom: -20px;
            left: 50%;
            transform: translate(-50%, 0);
        }

        .signatures span {
            display: block;
            text-align: center;
            margin-bottom: 7px;
        }

        .signatures .third-line {
            margin-top: 30px;
        }

        .uni-logo {
            width: 100px;
            display: block;
            margin: 20px auto 0;
        }

        .besmeh {
            padding-top: 70px;
            color: black
        }

        .landscape .besmeh {
            padding-top: 10px !important;
        }

        @media print {
            button {
                display: none !important;
            }
        }

        .tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .active-tab {
            display: block !important;
        }

        .management-college {
            font-family: nastaliq;
            color: black;
            padding-top: -100px;
        }

        .college-info .college-title {
            margin-top: 50px;
            display: block;
            text-align: center;
            font-size: 20px;
        }

        .college-info .cert-title {
            display: block;
            text-align: center;
            font-size: 26px;
            margin-top: 12px;
        }

        .management-college .text p {
            font-size: 20px;
            word-spacing: 12px;
            line-height: 40px;
        }

        .management-college .signatures {
            margin-top: 120px !important;
            gap: 200px 400px !important;
        }

        .dates {
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dates>div {
            padding-right: 90px;
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .management-college .dates .cert-date {
            font-size: 12px;
            color: rgba(0, 0, 0, 0.7)
        }

        .management-college .besmeh {
            padding-top: 40px !important;
        }

        .basic {
            padding-top: 1cm;
            padding-right: 2cm;
            padding-left: 2cm;
            padding-bottom: 2cm;
        }

        .basic .besmeh {
            padding-top: 3cm;
        }

        /* .basic h5 {
            margin-bottom: 20px;
        }

        .basic .text {
            margin: 10px auto 10px;
        }

        .basic .signatures {
            gap: 30px;
        } */

        .basic.landscape .basic .text {
            margin: 60px auto;
        }

        .basic.landscape .signatures {
            gap: 60px;
        }

        .basic.landscape .besmeh {
            padding-top: 70px;
        }

        .basic.landscape {
            padding-top: 20px;
            padding-right: 20px;
            padding-left: 20px;
            padding-bottom: 0;
        }

        .basic.landscape .en-text {
            margin-bottom: 0px !important;
        }

        .basic.landscape .signatures {
            margin-top: 30px !important;
        }

        .basic.landscape .third-line {
            margin-top: 15px !important;
        }

        .basic.landscape .top-info {
            display: none;
        }

        .basic.landscape .dates {
            display: flex !important;
        }

        .table-header {
            margin-top: 20px;
            border: 2px solid black;
            border-radius: 4px;
            padding: 12px;
        }

        .course-info {
            text-align: center;
        }

        .course-info {
            text-align: center;
        }

        .course-info>span:first-child {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .user-info {
            margin-top: 10px;
        }

        .user-info>div:first-child {
            width: 50%;
        }

        .user-info span {
            display: block;
            margin-top: 5px;
        }

        .table-content {
            margin-top: 15px;
            border: 2px solid black;
            border-radius: 4px;
        }

        .table-content .table-titles {
            padding: 5px 10px;
            border-bottom: 1px solid black;
        }

        .table-content .table-titles>span {
            font-weight: bold;
            font-size: 16px;
        }

        .gpa-row {
            padding: 5px 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.7);
        }

        .gpa-score {
            padding: 5px 10px;
        }

        .lang-vertical {
            padding: 0 2.5cm;
        }

        .lang-vertical .besmeh {
            padding-top: 3.5cm;
            line-height: 0;
        }

        .lang-vertical .signatures {
            margin-top: 2.5cm;
        }

        .lang-college #bsm {
            margin-top: 500px !important;
            display: block;
        }

        .lang-vertical #bsm {
            margin-top: 70px !important;

        }

        .info1 {
            text-align: left;
            padding-left: 80px;
        }

        .info2 {
            text-align: left;
            padding-left: 50px;
        }

        .result-table-en {
            margin-top: 8.3in;
            padding-top: 20px;
        }
    </style>

    <style id="print-style">
        @page {
            size: 8.3in 11.7in;
            margin: 0;
        }
    </style>
</head>

<body>
<?php
$gpa = $this->context->user_score((string) $courseDetail->_id, (string) $userDetail->_id);
$studentNumber = false;
if ($userDetail->issuance_certificate_information != null)
    if (array_key_exists('student_number', $userDetail->issuance_certificate_information))
        if ($userDetail->issuance_certificate_information['student_number'] != null && $userDetail->issuance_certificate_information['student_number'] != '')
            $studentNumber = true;
$online1 = '';
$online2 = '';
if ($courseDetail->content_type == '1' || $courseDetail->content_type == '2') {
    $online1 = 'به صورت برخط';
    if ($courseDetail->content_type == '1')
        $online2 = 'online';
    if ($courseDetail->content_type == '2')
        $online2 = 'hybrid';
}
$courseType = 'غیرحضوری';
if ($courseDetail->content_type == '2')
    $courseType = 'نیمه حضوری';
else if ($courseDetail->content_type == '3')
{
    $courseType = 'محتوا محور';
    $online2 = 'Content-driven';
}
else if ($courseDetail->content_type == '4')
    $courseType = 'حضوری';
$gender = 'Mr';
$gender1 = 'آقای';
if ($userDetail->issuance_certificate_information != null)
    if (array_key_exists('gender', $userDetail->issuance_certificate_information))
        if ($userDetail->issuance_certificate_information['gender'] == '0') {
            $gender = 'Ms';
            $gender1 = 'خانم';
        }
$beginDate = $endDate = '-';
if($courseDetail->type == '1')
{
    if($courseDetail->lessons != null)
        if(array_key_exists('date', $courseDetail->lessons[0]))
            if(array_key_exists('from', $courseDetail->lessons[0]['date']) && array_key_exists('to', $courseDetail->lessons[0]['date']))
            {
                $beginDate = str_replace('-','/', $courseDetail->lessons[0]['date']['from']);
                $endDate = str_replace('-','/', $courseDetail->lessons[0]['date']['to']);
            }
}
else
{
    if($courseDetail->date != null)
    {
        $beginDate = str_replace('-','/', $courseDetail->date['from']);
        $endDate = str_replace('-','/', $courseDetail->date['to']);
    }
}
?>
<div class="container">
    <div class="tabs">
        <button id="vertical" class="btn <?= $btn1 ?> change-mode">حالت عمودی</button>
        <!--            <button id="landscape" class="btn --><?php //= $btn2
        ?><!-- change-mode">حالت افقی</button>-->
        <button id="management-college" class="btn <?= $btn3 ?> change-mode">افقی نستعلیق</button>
        <!--            <button id="lang-college" class="btn --><?php //= $btn4
        ?><!-- change-mode">دانشکده زبان</button>-->
        <button id="lang-vertical" class="btn <?= $btn5 ?> change-mode">توانمندسازی زبان</button>
        <button id="table-fa" class="btn <?= $btn6 ?> change-mode">کارنامه فارسی</button>
        <button id="table-en" class="btn <?= $btn7 ?> change-mode">کارنامه انگلیسی</button>
    </div>

    <button onclick="printElement()" class="btn btn-warning">پرینت مدرک</button>

    <div class="d-none basic <?= $active1 ?>" id="printElement">
        <h6 class="besmeh">بسمه تعالی</h6>

        <div class="top-info">
                    <span>
                        شماره مجوز: <?= Html::encode($license) ?>
                    </span>
            <span>
                        تاریخ: <?= jdate('Y/m/d', time()) ?>
                    </span>
        </div>

        <h5><< گواهی پایان دوره آموزشی کاربردی و حرفه ای >></h5>


        <div class="dates d-none">
            <span></span>
            <div class="">
                        <span>
                            شماره مجوز: <?= Html::encode($license) ?>
                        </span>
                <!-- <span>تاریخ: ۱۳۹۸/۰۲/۰۴</span> -->
            </div>
            <span class="cert-date">تاریخ صدور: <?= jdate('Y/m/d', time()) ?></span>
        </div>



        <div class="text" style="margin-top: 50px;">
            <span>گواهی می شود</span>
            <p style="text-align: justify;">
                <?= Html::encode($gender1) ?> <span><?= Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?></span> دارای کد ملی <?= Html::encode($userDetail->issuance_certificate_information['id']) ?> <span><?= Html::encode($courseDetail->title['degree_fa'])  ?></span> را به شیوه <?= Html::encode($courseType) ?> به مدت <?= Html::encode($courseDetail->duration) ?> ساعت از تاریخ <?= $beginDate ?> تا تاریخ <?= $endDate ?> در <?= Html::encode($collegeDetail->title) ?> با موفقیت به پایان رسانده است.
            </p>
        </div>

        <div class="text en-text" style="margin-top: 50px;">
            <p style="text-align: center;">
                * این گواهی معادل هیچ یک از مدارک فراغت از تحصیل دوره های رسمی دانشگاهی نیست *
            </p>
        </div>

        <div class="signatures">
            <div>
                <img src="<?= $front . '/college_logos/' . $collegeDetail->signature_file ?>" alt="college-signature-image">
                <span><?= ($collegeDetail->first_line_signature_fa) ?></span>
                <span><?= ($collegeDetail->second_line_signature_fa) ?></span>
                <span class="third-line">
                            <?= ($collegeDetail->first_line_signature_en) ?>
                        </span>
                <span><?= ($collegeDetail->second_line_signature_en) ?></span>
            </div>
            <div>
                <span><?= ($general->data['first_line_signature_fa']) ?></span>
                <span><?= ($general->data['second_line_signature_fa']) ?></span>
                <span class="third-line">
                            <?= ($general->data['first_line_signature_en']) ?>
                        </span>
                <span><?= ($general->data['second_line_signature_en']) ?></span>
            </div>
        </div>

        <!--        <img src="/frontend/web/assets/images/logo.png" alt="logo" class="uni-logo" />-->
    </div>

    <div class="d-none management-college <?= $active2 ?>" id="printElement">



        <?php
        if ($gpa != null) {
            ?>
            <h6 class="besmeh">بسمه تعالی</h6>
            <div class="college-info">
                <span class="college-title"><?= Html::encode($collegeDetail->title) ?> دانشگاه تهران</span>
                <span class="cert-title">گواهینامه آموزش های کاربردی و حرفه ای</span>
                <div class="dates">
                    <span></span>
                    <div class="">
                        <span></span>
                        <span></span>
                    </div>
                    <span class="cert-date">تاریخ صدور: <?= $this->context->english_convert(jdate('Y/m/d', time())) ?></span>
                </div>
            </div>
            <div class="text">
                <span>گواهی می شود</span>
                <p>
                    <?= Html::encode($gender1) ?> <span><?= Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?></span> دارای کد ملی <?= Html::encode($this->context->english_convert($userDetail->issuance_certificate_information['id'])) ?> <span><?= Html::encode($courseDetail->title['degree_fa']) ?></span> را به صورت <?= Html::encode($courseType) ?> به مدت <?= Html::encode($this->context->english_convert($courseDetail->duration)) ?> ساعت با امتیاز <?= $this->context->english_convert(round($gpa, 2)) ?>، در <?= Html::encode($collegeDetail->title) ?> با موفقیت به پایان رسانده است.
                    <!--                        --><?php //= Html::encode($gender1) ?><!-- <span>--><?php //= Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?><!--</span> دارای کد ملی --><?php //= Html::encode($this->context->english_convert($userDetail->issuance_certificate_information['id'])) ?><!-- <span>--><?php //= Html::encode($courseDetail->title['degree_fa']) ?><!--</span> را به صورت --><?php //= Html::encode($courseType) ?><!-- به مدت --><?php //= Html::encode($this->context->english_convert($courseDetail->duration)) ?><!-- ساعت، در --><?php //= Html::encode($collegeDetail->title) ?><!-- با موفقیت به پایان رسانده است.-->
                </p>
            </div>

            <div class="signatures">
                <div>
                    <img src="<?= $front . '/college_logos/' . $collegeDetail->signature_file ?>" alt="college-signature-image">
                    <span><?= $collegeDetail->first_line_signature_fa ?></span>
                    <span><?= $collegeDetail->second_line_signature_fa ?></span>
                </div>
                <div>
                    <!--                    <img src="--><?php //= $front . '/college_logos/' . $general->data['signature_file']
                    ?><!--" alt="admin-signature-image">-->
                    <span><?= $general->data['first_line_signature_fa'] ?></span>
                    <span><?= $general->data['second_line_signature_fa'] ?></span>
                </div>
            </div>
            <?php
        } else
            echo '<p align="center">به دلیل مشخص نبودن معدل قادر به پرینت این نوع مدرک نمی باشید</p>';
        ?>



    </div>

    <div class="d-none lang-college landscape <?= $active3 ?>" id="printElement">
        <?php
        if ($studentNumber) {
            if ($gpa !== null) {
                ?>
                <h6 class="besmeh" id="bsm">بسمه تعالی</h6>

                <div class="top-info">
                        <span>
                            شماره مجوز: <?= $license ?>
                        </span>
                    <span>
                            تاریخ: <?= jdate('Y/m/d', time()) ?>
                        </span>
                    <span style="font-family: Sans-serif;">
                            Date: <?= date('Y/m/d', time()) ?>
                        </span>
                </div>

                <h5>گواهی پایان دوره آموزشی کاربردی و حرفه ای</h5>

                <div class="text">
                    <span>گواهی می شود</span>
                    <p>
                        به استناد شیوه نامه اجرایی احراز بسندگی زبان خارجی دانشجویان دکتری تخصصی دانشگاه تهران مورخ ۱۴۰۲/۰۴/۲۰ گواهی می گردد <span><?= Html::encode($gender1) . ' ' . Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?></span> به شماره دانشجویی <?= Html::encode($userDetail->issuance_certificate_information['student_number']) ?> در دوره های توانمند سازی زبان انگلیسی دانشجویان دکتری دانشگاه تهران شرکت نموده و این دوره را با میانگین <span><?= round($gpa, 2) ?></span> از صد با موفقیت گذرانده است.
                    </p>
                </div>

                <div class="signatures">
                    <div>
                        <img src="<?= $front . '/college_logos/' . $collegeDetail->signature_file ?>" alt="college-signature-image">
                        <span><?= $collegeDetail->first_line_signature_fa ?></span>
                        <span><?= $collegeDetail->second_line_signature_fa ?></span>
                        <span class="third-line">
                                <?= $collegeDetail->first_line_signature_en ?>
                            </span>
                        <span><?= $collegeDetail->second_line_signature_en ?></span>
                    </div>
                    <div>
                        <span><?= $general->data['first_line_signature_fa'] ?></span>
                        <span><?= $general->data['second_line_signature_fa'] ?></span>
                        <span class="third-line">
                                <?= $general->data['first_line_signature_en'] ?>
                            </span>
                        <span><?= $general->data['second_line_signature_en'] ?></span>
                    </div>
                </div>
                <?php
            } else {
                ?>
                <p align="center">به علت نامشخص بودن نمرات دانشپذیر این مدرک قابل چاپ کردن نمی باشد</p>
                <?php
            }
        } else
            echo '<p align="center">به علت نامشخص بودن شماره دانشجویی دانشپذیر این مدرک قابل چاپ کردن نمی باشد</p>';
        ?>
    </div>

    <div class="d-none lang-vertical <?= $active4 ?>" id="printElement">
        <?php
        $gpa = $this->context->user_score((string) $courseDetail->_id, (string) $userDetail->_id);
        $studentNumber = false;
        if ($userDetail->issuance_certificate_information != null)
            if (array_key_exists('student_number', $userDetail->issuance_certificate_information))
                if ($userDetail->issuance_certificate_information['student_number'] != null && $userDetail->issuance_certificate_information['student_number'] != '')
                    $studentNumber = true;
        if ($studentNumber) {
            if ($gpa !== null) {
                ?>
                <h6 class="besmeh" id="bsm">بسمه تعالی</h6>

                <div class="top-info">
                        <span>
                            شماره مجوز: <?= $license ?>
                        </span>
                    <span>
                            تاریخ: <?= jdate('Y/m/d', time()) ?>
                        </span>
                    <span style="font-family: Sans-serif;">
                            Date: <?= date('Y/m/d', time()) ?>
                        </span>
                </div>

                <h5>گواهی پایان دوره آموزشی کاربردی و حرفه ای</h5>

                <div class="dates d-none">
                    <span></span>
                    <div class="">
                            <span>
                                شماره مجوز: <?= $license ?>
                            </span>
                        <!-- <span>تاریخ: ۱۳۹۸/۰۲/۰۴</span> -->
                    </div>
                    <span class="cert-date">تاریخ صدور: <?= jdate('Y/m/d', time()) ?></span>
                </div>

                <div class="text">
                    <span>گواهی می شود</span>
                    <p style="text-align: justify;">
                        به استناد شیوه نامه اجرایی احراز بسندگی زبان خارجی دانشجویان دکتری تخصصی دانشگاه تهران مورخ ۱۴۰۲/۰۴/۲۰ گواهی می گردد <span><?= Html::encode($gender1) . ' ' . Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?></span> به شماره دانشجویی <?= Html::encode($userDetail->issuance_certificate_information['student_number']) ?> در دوره های توانمند سازی زبان انگلیسی دانشجویان دکتری دانشگاه تهران شرکت نموده و این دوره را با میانگین <span><?= $gpa ?></span> از صد با موفقیت گذرانده است.
                    </p>
                </div>

                <div class="signatures">
                    <div>
                        <img src="<?= $front . '/college_logos/' . $collegeDetail->signature_file ?>" alt="college-signature-image">
                        <span><?= $collegeDetail->first_line_signature_fa ?></span>
                        <span><?= $collegeDetail->second_line_signature_fa ?></span>
                        <span class="third-line">
                                <?= $collegeDetail->first_line_signature_en ?>
                            </span>
                        <span><?= $collegeDetail->second_line_signature_en ?></span>
                    </div>
                    <div>
                        <span><?= $general->data['first_line_signature_fa'] ?></span>
                        <span><?= $general->data['second_line_signature_fa'] ?></span>
                        <span class="third-line">
                                <?= $general->data['first_line_signature_en'] ?>
                            </span>
                        <span><?= $general->data['second_line_signature_en'] ?></span>
                    </div>
                </div>

                <?php
            } else {
                ?>
                <p align="center">به علت نامشخص بودن نمرات دانشپذیر این مدرک قابل چاپ کردن نمی باشد</p>
                <?php
            }
        } else
            echo '<p align="center">به علت نامشخص بودن شماره دانشجویی دانشپذیر این مدرک قابل چاپ کردن نمی باشد</p>';
        ?>
    </div>

    <div class="d-none table-fa <?= $active5 ?>" id="printElement">
        <!--            <h6>بسمه تعالی</h6>-->
        <div class="table-header">
            <div class="course-info">
                <span class="d-block">ریز نمرات </span>
                <span class="d-block"><?= Html::encode($courseDetail->title['degree_fa']) ?></span>
                <span class="d-block mt-1">(شماره مجوز: <?= $license ?>)</span>
            </div>
            <div class="d-flex align-items-center user-info">
                <div class="">
                    <?php
                    $startDate = '';
                    $myFromDate = '';
                    $myToDate = '';
                    if ($courseDetail->type == '1') {
                        $myFromDate = $courseDetail->lessons[0]['date']['from'];
                        $myToDate = $courseDetail->lessons[0]['date']['to'];
                    } else {
                        $myFromDate = $courseDetail->date['from'];
                        $myToDate = $courseDetail->date['to'];
                    }
                    if ($myFromDate != '')
                        $myFromDate = explode('-', $myFromDate);
                    if ($myToDate != '')
                        $myToDate = explode('-', $myToDate);
                    ?>
                    <span>نام و نام خانوادگی: <?= Html::encode($userDetail->issuance_certificate_information['first_name_fa'] . ' ' . $userDetail->issuance_certificate_information['last_name_fa']) ?></span>
                    <span>تاریخ شروع: <?= $this->context->convert($myFromDate[0]) . '/' . $this->context->convert($myFromDate[1]) . '/' . $this->context->convert($myFromDate[2]) ?></span>
                    <span>مدت دوره: <?= Html::encode($this->context->convert($courseDetail->duration)) ?> ساعت</span>
                </div>
                <div class="">
                    <!--                        <span>نام پدر: --><?php //= $userDetail->issuance_certificate_information['father_name']
                    ?><!--</span>-->
                    <!--                        <span>شماره شناسنامه:-->
                    <!--                            --><?php
                    //                            if (array_key_exists('shsh', $userDetail->issuance_certificate_information)) {
                    //                                if ($userDetail->issuance_certificate_information['shsh'] != '')
                    //                                    echo $userDetail->issuance_certificate_information['shsh'];
                    //                                else
                    //                                    echo '-';
                    //                            } else
                    //                                echo '-';
                    //
                    ?>
                    <!--                        </span>-->
                    <span>کد ملی: <?= Html::encode($userDetail->issuance_certificate_information['id']) ?></span>
                    <span>تاریخ پایان: <?= $this->context->convert($myToDate[0]) . '/' . $this->context->convert($myToDate[1]) . '/' . $this->context->convert($myToDate[2]) ?></span>
                    <span>&nbsp;</span>
                </div>
            </div>
        </div>

        <div class="table-content" style="font-size: 9px;">
            <div class="d-flex align-items-center justify-content-between table-titles">
                <span>ردیف</span>
                <span>سرفصل</span>
                <div class="d-flex align-items-center gap-4">
                    <span>ساعت</span>
                    <span>نمره</span>
                </div>
            </div>
            <?php
            if ($courseDetail->lessons != null) {
                $row = 1;
                foreach ($courseDetail->lessons as $lesson) {
                    $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                    if ($lessonDetail != null) {
                        $lessonScore = $this->context->lesson_score((string) $courseDetail->_id, (string) $userDetail->_id, (string) $lessonDetail->_id);
                        ?>
                        <div class="gpa-row d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <span class="ps-1"><?= $row++ ?></span>
                                <span><?= Html::encode($lessonDetail->title) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-4">
                                    <span class="pe-4">
                                        <?php
                                        if (array_key_exists('duration', $lesson['date']))
                                            echo $this->context->convert($lesson['date']['duration']);
                                        else
                                            echo '-';
                                        ?>
                                    </span>
                                <span>
                                        <?php
                                        if ($lessonScore != null)
                                            echo $this->context->convert($lessonScore);
                                        else
                                            echo '-';
                                        ?>
                                    </span>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <div class="d-flex align-items-center justify-content-between gpa-score">
                    <span></span>
                    <span>معدل</span>
                    <span class="pe-4">
                            <?php
                            if ($gpa != null)
                                echo $this->context->convert(round($gpa, 2));
                            else
                                echo '-';
                            ?>
                        </span>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="signatures justify-content-end mt-3">

            <div>
                <img src="<?= $front . '/college_logos/' . $general->data['signature_file'] ?>" alt="admin-signature-image">
                <span><?= $general->data['first_line_signature_fa'] ?></span>
                <span><?= $general->data['second_line_signature_fa'] ?></span>
                <!--                    <span class="third-line">-->
                <!--                        --><?php //= $general->data['first_line_signature_en']
                ?>
                <!--                    </span>-->
                <!--                    <span>--><?php //= $general->data['second_line_signature_en']
                ?><!--</span>-->
            </div>
        </div>

        <div class="result-table-en" dir="ltr">

            <div class="table-header">
                <div class="course-info">
                    <span class="d-block">Student Transcript </span>
                    <span class="d-block"><?= Html::encode($courseDetail->title['degree_en']) ?></span>
                    <span class="d-block mt-1" style="font-family: Sans-serif;">(License Number: <?= $license ?>)</span>
                </div>
                <div class="d-flex align-items-left user-info">
                    <div class="">
                        <?php
                        $startDate = '';
                        $myFromDate = '';
                        $myToDate = '';
                        if ($courseDetail->type == '1') {
                            $myFromDate = $courseDetail->lessons[0]['date']['from'];
                            $myToDate = $courseDetail->lessons[0]['date']['to'];
                        } else {
                            $myFromDate = $courseDetail->date['from'];
                            $myToDate = $courseDetail->date['to'];
                        }
                        if ($myFromDate != '') {
                            $myFromDate = explode('-', $myFromDate);
                            $from = jalali_to_gregorian($myFromDate[0], $myFromDate[1], $myFromDate[2]);
                        }
                        if ($myToDate != '') {
                            $myToDate = explode('-', $myToDate);
                            $to = jalali_to_gregorian($myToDate[0], $myToDate[1], $myToDate[2]);
                        }
                        ?>
                        <span class="info1">Name and Surname: <?= Html::encode($userDetail->issuance_certificate_information['first_name_en'] . ' ' . $userDetail->issuance_certificate_information['last_name_en']) ?></span>
                        <span class="info1" style="font-family: Sans-serif;">Start Date: <?= $this->context->convert($from[0] . '/' . $from[1] . '/' . $from[2]) ?></span>
                        <span class="info1" style="font-family: Sans-serif;">Course Duration: <?= Html::encode($this->context->convert($courseDetail->duration)) ?> Hour</span>
                    </div>
                    <div class="">
                        <span class="info2" style="font-family: Sans-serif;">National ID: <?= Html::encode($userDetail->issuance_certificate_information['id']) ?></span>
                        <span class="info2" style="font-family: Sans-serif;">End Date: <?= $this->context->convert($to[0] . '/' . $to[1] . '/' . $to[2]) ?></span>
                        <span>&nbsp;</span>
                    </div>
                </div>
            </div>

            <div class="table-content" style="font-size: 9px;">
                <div class="d-flex align-items-center justify-content-between table-titles">
                    <span>Index</span>
                    <span>Title</span>
                    <div class="d-flex align-items-center gap-4">
                        <span>Hour</span>
                        <span>Score</span>
                    </div>
                </div>
                <?php
                if ($courseDetail->lessons != null) {
                    $row = 1;
                    foreach ($courseDetail->lessons as $lesson) {
                        $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                        if ($lessonDetail != null) {
                            $lessonScore = $this->context->lesson_score((string) $courseDetail->_id, (string) $userDetail->_id, (string) $lessonDetail->_id);
                            ?>
                            <div class="gpa-row d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="ps-1" style="font-family: Sans-serif;"><?= $this->context->convert($row++) ?></span>
                                    <span><?= Html::encode($lessonDetail->en_title) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-4">
                                        <span style="font-family: Sans-serif;">
                                            <?php
                                            if (array_key_exists('duration', $lesson['date']))
                                                echo $this->context->convert($lesson['date']['duration']);
                                            else
                                                echo '-';
                                            ?>
                                        </span>
                                    <span class="pe-4" style="font-family: Sans-serif;">
                                            <?php
                                            if ($lessonScore != null)
                                                echo $this->context->convert($lessonScore);
                                            else
                                                echo '-';
                                            ?>
                                        </span>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                    <div class="d-flex align-items-center justify-content-between gpa-score">
                        <span></span>
                        <span>GPA</span>
                        <span class="pe-4" style="font-family: Sans-serif;">
                                <?php
                                if ($gpa != null)
                                    echo $this->context->convert(round($gpa, 2));
                                else
                                    echo '-';
                                ?>
                            </span>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div class="signatures justify-content-start mt-3">

                <div>
                    <img src="<?= $front . '/college_logos/' . $general->data['signature_file'] ?>" alt="admin-signature-image">
                    <span class="third-line">
                            <?= $general->data['first_line_signature_en'] ?>
                        </span>
                    <span><?= $general->data['second_line_signature_en'] ?></span>
                </div>
            </div>
        </div>

    </div>

    <div class="d-none table-en <?= $active6 ?>" id="printElement" dir="ltr">
        <!--            <h6>بسمه تعالی</h6>-->
        <div class="table-header">
            <div class="course-info">
                <span class="d-block">Student Transcript </span>
                <span class="d-block"><?= $courseDetail->title['degree_en'] ?></span>
                <span class="d-block mt-1" style="font-family: Sans-serif;">(License Number: <?= $license ?>)</span>
            </div>
            <div class="d-flex align-items-left user-info">
                <div class="">
                    <?php
                    $startDate = '';
                    $myFromDate = '';
                    $myToDate = '';
                    if ($courseDetail->type == '1') {
                        $myFromDate = $courseDetail->lessons[0]['date']['from'];
                        $myToDate = $courseDetail->lessons[0]['date']['to'];
                    } else {
                        $myFromDate = $courseDetail->date['from'];
                        $myToDate = $courseDetail->date['to'];
                    }
                    if ($myFromDate != '') {
                        $myFromDate = explode('-', $myFromDate);
                        $from = jalali_to_gregorian($myFromDate[0], $myFromDate[1], $myFromDate[2]);
                    }
                    if ($myToDate != '') {
                        $myToDate = explode('-', $myToDate);
                        $to = jalali_to_gregorian($myToDate[0], $myToDate[1], $myToDate[2]);
                    }
                    ?>
                    <span class="info1">Name and Surname: <?= Html::encode($userDetail->issuance_certificate_information['first_name_en'] . ' ' . $userDetail->issuance_certificate_information['last_name_en']) ?></span>
                    <span class="info1" style="font-family: Sans-serif;">Start Date: <?= $this->context->convert($from[0] . '/' . $from[1] . '/' . $from[2]) ?></span>
                    <span class="info1" style="font-family: Sans-serif;">Course Duration: <?= $this->context->convert($courseDetail->duration) ?> Hour</span>
                </div>
                <div class="">
                    <span class="info2" style="font-family: Sans-serif;">National ID: <?= Html::encode($userDetail->issuance_certificate_information['id']) ?></span>
                    <span class="info2" style="font-family: Sans-serif;">End Date: <?= $this->context->convert($to[0] . '/' . $to[1] . '/' . $to[2]) ?></span>
                    <span>&nbsp;</span>
                </div>
            </div>
        </div>

        <div class="table-content" style="font-size: 9px;">
            <div class="d-flex align-items-center justify-content-between table-titles">
                <span>Index</span>
                <span>Title</span>
                <div class="d-flex align-items-center gap-4">
                    <span>Hour</span>
                    <span>Score</span>
                </div>
            </div>
            <?php
            if ($courseDetail->lessons != null) {
                $row = 1;
                foreach ($courseDetail->lessons as $lesson) {
                    $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                    if ($lessonDetail != null) {
                        $lessonScore = $this->context->lesson_score((string) $courseDetail->_id, (string) $userDetail->_id, (string) $lessonDetail->_id);
                        ?>
                        <div class="gpa-row d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <span class="ps-1" style="font-family: Sans-serif;"><?= $this->context->convert($row++) ?></span>
                                <span><?= Html::encode($lessonDetail->en_title) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-4">
                                    <span style="font-family: Sans-serif;">
                                        <?php
                                        if (array_key_exists('duration', $lesson['date']))
                                            echo $this->context->convert($lesson['date']['duration']);
                                        else
                                            echo '-';
                                        ?>
                                    </span>
                                <span class="pe-4" style="font-family: Sans-serif;">
                                        <?php
                                        if ($lessonScore != null)
                                            echo $this->context->convert($lessonScore);
                                        else
                                            echo '-';
                                        ?>
                                    </span>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
                <div class="d-flex align-items-center justify-content-between gpa-score">
                    <span></span>
                    <span>GPA</span>
                    <span class="pe-4" style="font-family: Sans-serif;">
                            <?php
                            if ($gpa != null)
                                echo $this->context->convert(round($gpa, 2));
                            else
                                echo '-';
                            ?>
                        </span>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="signatures justify-content-start mt-3">

            <div>
                <img src="<?= $front . '/college_logos/' . $general->data['signature_file'] ?>" alt="admin-signature-image">
                <span class="third-line">
                        <?= $general->data['first_line_signature_en'] ?>
                    </span>
                <span><?= $general->data['second_line_signature_en'] ?></span>
            </div>
        </div>

    </div>
</div>

<script>
    // JavaScript code for printing the element
    function printElement() {
        window.print()
    }
</script>
</body>