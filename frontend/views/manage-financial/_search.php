<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$select2 = <<< JS
    $('#college').select2({
    placeholder: "فیلتر بر اساس دانشکده"
});
$('#status').select2({
    placeholder: "فیلتر بر اساس وضعیت"
});
$('#broker').select2({
    placeholder: "فیلتر بر کارگزار"
});
$("#college").select2({
    allowClear : true,
    debug: true
});
$("#status").select2({
    allowClear : true,
    debug: true
});
JS;
$this->registerJs($select2);

// گرفتن مقادیر از GET
$date1 = isset($_GET['date1']) ? $_GET['date1'] : '';
$date2 = isset($_GET['date2']) ? $_GET['date2'] : '';
$tref = isset($_GET['tref']) ? $_GET['tref'] : '';
?>

<style>
    /* استایل برای دکمه غیرفعال */
    .btn-disabled {
        opacity: 0.7;
        cursor: not-allowed !important;
    }

    /* استایل spinner */
    .spinner-border {
        vertical-align: middle;
        margin-left: 5px;
    }

    /* جلوگیری از انتخاب متن در دکمه */
    #excel-submit-btn {
        user-select: none;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-5">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <?php $form = ActiveForm::begin([
                'action'=>['index'],
                'method'=>'get',
                'options' => [
                    'class' => 'd-flex align-items-center',
                ],
                'fieldConfig' => [
                    'options' => [
                        'tag' => false,
                    ],
                ],
            ]); ?>

            <input value="<?= $date1 ?>" class="form-control dob-picker text-start me-2" placeholder="از تاریخ" name="date1">
            <input value="<?= $date2 ?>" class="form-control dob-picker text-start me-2" placeholder="تا تاریخ" name="date2">

            <!-- فیلد جستجوی tref -->
            <input value="<?= $tref ?>" class="form-control me-2" placeholder="کد پیگیری" name="tref" id="tref">

            <button class="btn btn-info me-2" type="submit">جستجو</button>

            <?php ActiveForm::end(); ?>
            <button class="btn btn-success me-2" type="button" data-bs-toggle="modal" data-bs-target="#from-excel">افزودن از فایل اکسل</button>
        </div>
    </div>
</nav>

<div class="modal fade" id="from-excel" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن از فایل اکسل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add-user-from-excel'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data',
                            'id' => 'excel-form'
                        ],
                    ]
                ); ?>
                <div class="card mb-4 relative">
                    <div class="card-body">
                        <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test">
                            <div class="dz-message needsclick">
                                <input class="form-control text-start drop-file" type="file" name="excel_file" required>
                                <span class="drop-title"></span>
                                <span class="note needsclick">فایل اکسل *</span>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="excel-submit-btn">بررسی و افزودن</button>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // مدیریت ارسال فرم اکسل
        $('#excel-submit-btn').on('click', function(e) {
            e.preventDefault();

            // غیرفعال کردن دکمه
            var $btn = $(this);
            $btn.prop('disabled', true);

            // تغییر متن دکمه
            var originalText = $btn.html();
            $btn.html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' +
                ' در حال پردازش فایل لطفا تا پردازش نهایی صبر کنید...'
            );

            // اضافه کردن کلاس برای حالت loading
            $btn.addClass('btn-disabled');

            // بررسی وجود فایل
            var fileInput = document.querySelector('#excel-form input[type="file"]');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                // اگر فایل انتخاب نشده
                e.preventDefault();
                toastr.error('لطفا فایل اکسل را انتخاب کنید');

                // فعال کردن مجدد دکمه
                $btn.prop('disabled', false);
                $btn.html(originalText);
                $btn.removeClass('btn-disabled');
                return false;
            }

            // نمایش پیام برای کاربر
            toastr.info('در حال پردازش فایل اکسل، لطفا صبر کنید...', '', {
                timeOut: 10000,
                positionClass: "toast-top-center"
            });

            // ارسال فرم
            $('#excel-form').submit();

            // اگر فرم بیش از 30 ثانیه طول بکشد، دکمه را فعال کن
            setTimeout(function() {
                if ($btn.prop('disabled')) {
                    $btn.prop('disabled', false);
                    $btn.html('بررسی و افزودن');
                    $btn.removeClass('btn-disabled');

                    toastr.warning('پردازش زمان‌بر است. اگر خطایی رخ داده، مجددا تلاش کنید.', '', {
                        timeOut: 5000,
                        positionClass: "toast-top-center"
                    });
                }
            }, 30000);
        });

        // جلوگیری از ارسال مجدد فرم اگر در حال پردازش است
        $('#excel-form').on('submit', function(e) {
            var $btn = $('#excel-submit-btn');
            if ($btn.prop('disabled')) {
                return true; // اجازه ارسال بده چون از قبل ارسال شده
            }
        });

        // وقتی modal بسته می‌شود، دکمه را ریست کنیم
        $('#from-excel').on('hidden.bs.modal', function () {
            var $btn = $('#excel-submit-btn');
            $btn.prop('disabled', false);
            $btn.html('بررسی و افزودن');
            $btn.removeClass('btn-disabled');
        });

        // اگر کاربر صفحه را رفرش کند یا برود، state را ریست کنیم
        window.addEventListener('beforeunload', function() {
            var $btn = $('#excel-submit-btn');
            $btn.prop('disabled', false);
        });

        // همچنین event listener اضافه برای دکمه (روش جایگزین)
        var excelSubmitBtn = document.getElementById('excel-submit-btn');
        var isSubmitting = false;

        if (excelSubmitBtn) {
            // روش native برای افزودن event listener
            excelSubmitBtn.addEventListener('click', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }

                isSubmitting = true;

                // غیرفعال کردن دکمه با روش native
                this.disabled = true;
                this.innerHTML =
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>' +
                    ' در حال پردازش فایل لطفا تا پردازش نهایی صبر کنید...';
                this.classList.add('btn-disabled');

                // ریست بعد از 30 ثانیه
                var btnElement = this;
                setTimeout(function() {
                    if (btnElement.disabled) {
                        btnElement.disabled = false;
                        btnElement.innerHTML = 'بررسی و افزودن';
                        btnElement.classList.remove('btn-disabled');
                        isSubmitting = false;
                    }
                }, 30000);
            });
        }
    });
</script>