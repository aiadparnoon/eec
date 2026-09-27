<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Books */
/* @var $form yii\widgets\ActiveForm */
$select2 = <<< JS
    $('#broker').select2({
    placeholder: "فیلتر کارگزار"
});
    $('#college').select2({
    placeholder: "فیلتر بر اساس دانشکده"
});
$('#status').select2({
    placeholder: "فیلتر بر اساس وضعیت"
});
 $("#college").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
   $("#status").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
  $("#content_type").select2({
    // placeholder: "Put some text...",
    allowClear : true,
    debug: true
  })
JS;
$this->registerJs($select2);
$status = array(
    '0' => 'تائید شده غیر فعال',
    '1' => 'تائید شده',
    '2' => 'در انتظار بررسی',
    '3' => 'پیش نویس',
    '4' => 'نیاز به اصلاح',
    '5' => 'رد شده',
    '6' => 'اتمام یافته',
);
$type = array(
        '3' => 'محتوا محور',
        '1' => 'غیرحضوری',
        '2' => 'نیمه حضوری',
        '4' => 'حضوری',
);
?>

<?php
// بازطراحی UI فیلترها (۲۰۲۶-۰۸-۲۸): قبلاً همه‌ی فیلدها توی یک navbar با
// d-flex flex-wrap چیده شده بودن، ولی چون هیچ ستون‌بندی (col-*) روی خودِ
// فیلدها نبود، هر کدوم (به‌خاطر عرض ۱۰۰٪ پیش‌فرض form-control) کل عرض
// ردیف رو می‌گرفت و همه زیر هم می‌افتادن. الگوی جدید عیناً همون ساختاریه
// که خودِ پروژه توی frontend/views/offline-exams/participants_search.php
// برای فرم‌های با تعداد فیلد زیاد استفاده می‌کنه: یک کارت با card-header +
// یک ردیف Bootstrap grid (row g-3) که هر فیلد داخل یک col-md-3 مستقله - با
// ۷ یا ۸ آیتم فیلتر، دقیقاً توی دو ردیف چیده می‌شن. منطق/نام فیلدها و اکشن
// فرم‌ها کاملاً دست‌نخورده مونده، فقط چیدمانِ HTML/CSS عوض شده.
?>

<div class="card mb-4">
    <h5 class="card-header heading-color">فیلتر دوره‌ها</h5>
    <?php $form = ActiveForm::begin([
        'action'=>['index'],
        'method'=>'get',
        'id' => 'course-filter-form',
        'options' => [
            'class' => 'card-body',
            'id' => 'course-filter-form',
        ],
        'fieldConfig' => [
            'options' => [
                'tag' => false,
            ],
        ],
    ]); ?>
        <div class="row g-3">
            <div class="col-md-3 col-sm-6">
                <?php
                // فیلتر «از تاریخ / تا تاریخ» بر اساس تاریخ ثبت دوره - تغییر ۳
                // (2026-08-28). دقیقاً همون کلاس dob-picker (flatpickr فارسی) که
                // خودِ فرم ثبت/ویرایش دوره ازش استفاده می‌کنه، اینجا هم استفاده
                // شده تا با بقیه‌ی پروژه یکدست بمونه.
                echo $form->field($model, 'reg_date_from')->textInput(
                    [
                        'placeholder' => 'از تاریخ',
                        'class' => 'form-control form-control-sm dob-picker text-start',
                        'id' => '',
                        'dir' => 'ltr',
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'reg_date_to')->textInput(
                    [
                        'placeholder' => 'تا تاریخ',
                        'class' => 'form-control form-control-sm dob-picker text-start',
                        'id' => '',
                        'dir' => 'ltr',
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'title[main_fa]')->textInput(
                    [
                        'placeholder' => 'فیلتر نام دوره',
                        'class' => 'form-control form-control-sm',
                        'id' => '',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'license_code')->textInput(
                    [
                        'placeholder' => 'فیلتر کد مجوز دوره',
                        'class' => 'form-control form-control-sm',
                        'id' => '',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'status')->dropDownList(
                    $status,
                    [
                        'prompt' => 'فیلتر وضعیت دوره',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'status',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'content_type')->dropDownList(
                    $type,
                    [
                        'prompt' => 'فیلتر نوع دوره',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'content_type',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'college')->dropDownList(
                    $colleges,
                    [
                        'prompt' => 'فیلتر دانشکده',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'college',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <?php if(Yii::$app->user->identity->role != 'broker'): ?>
            <div class="col-md-3 col-sm-6">
                <?php
                echo $form->field($model, 'broker[_id]')->dropDownList(
                    $brokers,
                    [
                        'prompt' => 'فیلتر کارگزار',
                        'class' => 'select2 form-select none-parent',
                        'id' => 'broker',
                        'data-allow-clear' => true
                    ]
                )->label(false);
                ?>
            </div>
            <?php endif; ?>
        </div>
    <?php ActiveForm::end(); ?>
    <?php
    if (isset(Yii::$app->request->queryParams['CoursesSearch']))
    {
        if (isset(Yii::$app->request->queryParams['CoursesSearch']['title']['main_fa']))
            $title = Yii::$app->request->queryParams['CoursesSearch']['title']['main_fa'];
        else
            $title = '';
        if (isset(Yii::$app->request->queryParams['CoursesSearch']['status']))
            $status = Yii::$app->request->queryParams['CoursesSearch']['status'];
        else
            $status = '';
        if (isset(Yii::$app->request->queryParams['CoursesSearch']['college']))
            $college = Yii::$app->request->queryParams['CoursesSearch']['college'];
        else
            $college = '';
        if (isset(Yii::$app->request->queryParams['CoursesSearch']['broker']['_id']))
            $broker = Yii::$app->request->queryParams['CoursesSearch']['broker']['_id'];
        else
            $broker = '';
    }
    else
    {
        $title = '';
        $status = '';
        $college = '';
        $broker = '';
    }
    ?>
    <?php
    // دکمه‌ی «جستجو» و دکمه‌ی «تهیه گزارش اکسل» توی یک ردیف، کنار هم (طبق
    // درخواست ۲۰۲۶-۰۸-۲۸). چون این دو دکمه به دو فرم/اکشن جدا (index و
    // report) تعلق دارن و HTML اجازه‌ی تو در توی هم قرار گرفتن دو <form> رو
    // نمی‌ده، دکمه‌ی «جستجو» با ویژگی استاندارد HTML5 «form="course-filter-form"»
    // بیرون از تگ فرم اصلی قرار گرفته و بازم دقیقاً همون فرم فیلتر بالا رو
    // سابمیت می‌کنه؛ فرم گزارش اکسل هم عیناً با همون منطق/فیلدهای قبلی، فقط
    // با چیدمانی که توی همین ردیف جا بشه.
    ?>
    <?php
    // انتقالِ ردیفِ دکمه‌ها به سمتِ چپِ صفحه (طبق درخواستِ ۲۰۲۶-۰۸-۲۸، دور ششم):
    // چون صفحه RTL هست، justify-content-end دقیقاً محتوا رو به سمتِ چپ (که در RTL همون
    // "end" محسوب می‌شه) هدایت می‌کنه؛ قبلاً هیچ justify-content‌ای تنظیم نشده بود و
    // پیش‌فرضِ flex-start باعث می‌شد دکمه‌ها سمتِ راست (کنارِ لبه‌ی شروعِ RTL) بمونن.
    ?>
    <div class="card-body pt-0 d-flex align-items-center justify-content-end gap-2">
        <button class="btn btn-info" type="submit" form="course-filter-form">جستجو</button>
        <?php $form = ActiveForm::begin([
            'action'=>['report'],
            'method'=>'get',
            'options' => [
                'class' => 'd-flex align-items-center m-0',
            ],
            'fieldConfig' => [
                'options' => [
                    'tag' => false,
                ],
            ],
        ]); ?>
        <?= $form->field($model, 'title[main_fa]')->hiddenInput(['value' => $title])->label(false); ?>
        <?= $form->field($model, 'status')->hiddenInput(['value' => $status])->label(false); ?>
        <?= $form->field($model, 'college')->hiddenInput(['value' => $college])->label(false); ?>
        <?= $form->field($model, 'broker[_id]')->hiddenInput(['value' => $broker])->label(false); ?>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-file-excel me-1"></i> تهیه گزارش اکسل</button>
        <?php ActiveForm::end(); ?>
    </div>
</div>
