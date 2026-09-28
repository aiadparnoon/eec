<?php
$this->title = 'ثبت دوره جامع و یکساله';

use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use app\models\Brokers;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

SingleAsset::register($this);
Select2Asset::register($this);
$model = new Courses();
$front = Yii::getAlias('@front');
$courseType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'نیمه حضوری',
    '4' => ' حضوری',
);
$type = array(
    '2' => 'دوره میان مدت (بین ۲۱ تا ۲۵۰ ساعت)',
    '3' => 'دوره بلند مدت (بین ۲۵۱ تا ۳۵۰ ساعت)',
);

if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("دوره مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '2')
        $script = <<< JS
    toastr.error("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '3')
        $script = <<< JS
    toastr.error("شماره همراه وارد شده تکراری می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("دوره مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("رمز عبور دوره مورد نظر بازنشانی گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '6')
        $script = <<< JS
    toastr.success("وضعیت دوره مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}

$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_courses', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$ajax = <<<JS

$(".message-actions").click(function() {
    $(".add-dob").flatpickr({
        monthSelectorType: "static",
        locale: "fa",
        altInput: true,
        altFormat: "Y/m/d",
        disableMobile: true,
        allowInput:true,
    });
});

if($('#course-type').val() == '3') {
    $(".hide-on-content").hide();
}

$(document).on('change','#lessons',function(e) {
    e.preventDefault();
    var id = $(this).val();


    $.ajax({
        url:'$url',
        type:'POST',
        data:{id:id, _csrf: yii.getCsrfToken()},
        success:function(data) {
            var main_data = JSON.parse(data);
            var container = $("#show_courses");

            var newIds = new Set();
            var existingIds = new Set();
            

            // Create a set to keep track of unique identifiers in the existing container

            // Iterate over existing items in the container and add their IDs to the set
            container.children().each(function () {
                var itemId = $(this).attr('id'); // Adjust 'data-id' to your actual identifier attribute
                existingIds.add(itemId);
            });

            // Append new data to the container
            container.append(main_data.list);

            // Create a set to keep track of unique identifiers in the new data

            // Iterate over the new data
            container.children().each(function () {
                var itemId = $(this).attr('id'); // Adjust 'data-id' to your actual identifier attribute
                if (newIds.has(itemId)) {
                    // If the item is a duplicate, remove it
                    $(this).remove();
                } else {
                    // If the item is not a duplicate, add its ID to the set
                    newIds.add(itemId);
                }
            });

            // Remove items from the container that are not present in both new data and existing container
            container.children().each(function () {
                var itemId = $(this).attr('id'); // Adjust 'data-id' to your actual identifier attribute
                 if(!main_data.list.join(' ').includes(itemId)) {
                        $("#" + itemId).remove();
                }
            });


            $(".select2").each(function () {
                var targetInput = $(this);
                var select2Input = targetInput.select2({
                    placeholder: "لطفا انتخاب کنید",
                    dropdownParent: targetInput.hasClass("none-parent") ? null : targetInput.parent(),
                    value: "borna",
                });
             });

             $(".dob-picker").each(function () {
                $(this).flatpickr({
                    monthSelectorType: "static",
                    locale: "fa",
                    altInput: true,
                    altFormat: "Y/m/d",
                    disableMobile: true,
                    allowInput:true,
                });
            });

            console.log(main_data?.list?.length, 'lengthy')

            $('.submit-course-btn').attr("disabled", main_data?.list?.length == 0)
        }
    });

})
JS;
$this->registerJs($ajax);

$contract_file = <<<JS
// ===== اعتبارسنجی تاریخ اقساط با در نظر گرفتن تاریخ شمسی =====
function initInstallmentPickers() {
    // حذف نمونه‌های قبلی flatpickr برای جلوگیری از تداخل
    $('.add-dob').each(function() {
        if ($(this).data('flatpickr')) {
            $(this).data('flatpickr').destroy();
        }
    });

    // مقداردهی مجدد با اعتبارسنجی
    $('.add-dob').flatpickr({
        monthSelectorType: "static",
        locale: "fa",
        altInput: true,
        altFormat: "Y/m/d",
        disableMobile: true,
        allowInput: true,
        onClose: function(selectedDates, dateStr, instance) {
            var endDateInput = document.getElementById('end-date');
            if (!endDateInput || !endDateInput.value) {
                return; // اگر تاریخ اتمام خالی است، کاری نکن
            }

            // گرفتن نمونه flatpickr نصب شده روی تاریخ اتمام
            var endPicker = endDateInput._flatpickr;
            if (!endPicker) {
                return; // در صورت نبود نمونه، از انجام اعتبارسنجی صرف‌نظر کن
            }

            // تبدیل تاریخ اتمام (که شمسی است) به Date میلادی
            var endDate = endPicker.parseDate(endDateInput.value);
            if (!endDate) {
                return;
            }

            var selectedDate = selectedDates[0]; // تاریخ انتخاب شده برای قسط (میلادی)

            // مقایسه
            if (selectedDate && selectedDate > endDate) {
                toastr.error("تاریخ پرداخت قسط نباید از تاریخ اتمام دوره بزرگ‌تر باشد", {
                    positionClass: "toast-top-center",
                    containerId: "toast-top-center",
                    closeButton: true
                });
                // پاک کردن فیلد قسط
                instance.clear();
                instance.input.value = '';
            }
        }
    });
}

// ===== مدیریت دکمه افزودن قسط بر اساس وجود تاریخ اتمام =====
$(document).ready(function() {
    // اجرای اولیه برای فیلدهای موجود
    initInstallmentPickers();

    var addBtn = $('[data-repeater-create]');

    // تابع به‌روزرسانی وضعیت دکمه
    function toggleAddButton() {
        var endDate = $('#end-date').val().trim();
        addBtn.prop('disabled', !endDate);
    }

    // اجرای اولیه (غیرفعال کردن)
    toggleAddButton();

    // رویدادهای تغییر تاریخ اتمام
    $('#end-date').on('change input', function() {
        toggleAddButton();
    });

    // پس از افزودن قسط جدید توسط دکمه، دوباره flatpickr را روی فیلدهای جدید مقداردهی کن
    $(document).on('click', '[data-repeater-create]', function() {
        // تاخیر برای اطمینان از ایجاد المان‌های جدید
        setTimeout(function() {
            initInstallmentPickers();
        }, 200);
    });
});
JS;
$this->registerJs($contract_file);

if (Yii::$app->user->identity->role != 'user' || Yii::$app->user->identity->role != 'cnt') {
    $collegeId = $myCollege['0']->_id;
    $collegeScript = <<< JS
    let capacity_type = $("#courses-student_capacity-type").val()
$.get("/packages/brokers1", { id: "{$collegeId}", "capacity_type": capacity_type } )
   .done(function(data) {
   var main_data=JSON.parse(data);
       $('#broker1').html(main_data.brokers);
       $('#teachers1').html(main_data.teachers);
       $('#lessons1').html(main_data.lessons);
       $(".select2").each(function () {
            var targetInput = $(this);
            var select2Input = targetInput.select2({
                placeholder: "لطفا انتخاب کنید",
                dropdownParent: targetInput.hasClass("none-parent") ? null : targetInput.parent(),
                value: "borna",
            });
        });
   });
JS;
    $this->registerJs($collegeScript);
}

$input = <<< JS
$(document).ready(function() {
    // تابع اعتبارسنجی (بدون نمایش توستر در submit)
    function validateDuration(value, showToast = true) {
        let num = parseInt(value, 10);
        let errorMsg = '';
        
        if (isNaN(num)) {
            errorMsg = 'لطفا مدت زمان دوره را وارد کنید';
        } else if (num > 350) {
            errorMsg = 'امکان ثبت دوره بالای ۳۵۰ ساعت نمی باشد';
        } else if (num < 25) {
            errorMsg = 'برای ثبت دوره کمتر از ۲۵ ساعت از قسمت دوره های کوتاه مدت اقدام فرمائید';
        }
        
        if (errorMsg) {
            $('#duration')[0].setCustomValidity(errorMsg);
            if (showToast) {
                toastr.error(errorMsg, {
                    positionClass: "toast-top-center",
                    containerId: "toast-top-center",
                    closeButton: true
                });
            }
            return false;
        } else {
            $('#duration')[0].setCustomValidity('');
            return true;
        }
    }

    // فقط یک رویداد: خروج از فیلد (با نمایش توستر)
    $('#duration').on('blur', function() {
        validateDuration($(this).val(), true);
    });

    // هنگام ارسال فرم: فقط بررسی کن، توستر نمایش نده
    $('form').on('submit', function(e) {
        var isValid = validateDuration($('#duration').val(), false);
        if (!isValid) {
            e.preventDefault();
            // اگر می‌خواهید پیغام خطا به صورت alert هم نشان دهید (اختیاری)
            // alert($('#duration')[0].validationMessage);
        }
    });
});
JS;
$this->registerJs($input);

$calDate = <<< JS
    
JS;
$this->registerJs($calDate);
?>

<?php

$js = <<< JS
$(document).ready(function() {
    // استفاده مستقیم از IDهایی که در HTML گذاشتید
    var startDate = $('#from1 input');
    var endDate = $('#to1 input');
    var deadlineDate = $('#deadline-date');
    // پیدا کردن deadline از طریق DOM
    // var deadlineDiv = $('#from1').next().next();
    // var deadlineDate = deadlineDiv.find('input');
    
    // غیرفعال کردن اولیه
    endDate.prop('disabled', true);
    deadlineDate.prop('disabled', true);
    
    // تابع برای اعتبارسنجی تاریخ اتمام
    function validateEndDate() {
        var startVal = startDate.val().trim();
        var endVal = endDate.val().trim();
        
        // اگر تاریخ شروع وجود ندارد
        if (!startVal) {
            endDate.prop('disabled', true);
            return false;
        }
        
        // اگر تاریخ اتمام خالی است
        if (!endVal) {
            deadlineDate.val('');
            deadlineDate.prop('disabled', true);
            return false;
        }
        
        var start = new Date(startVal);
        var end = new Date(endVal);
        
        // بررسی اعتبار
        if (end <= start) {
            // فقط یک بار پیام نشان بده
            if (!endDate.hasClass('error-shown')) {
                endDate.addClass('error-shown');
            }
            endDate.val('');
            deadlineDate.val('');
            deadlineDate.prop('disabled', true);
            return false;
        } else {
            // اگر تاریخ درست بود، کلاس خطا را حذف کن
            endDate.removeClass('error-shown');
            
            // محاسبه deadline
            calculateDeadline(start, end);
            return true;
        }
    }
    
    // تابع محاسبه deadline
    function calculateDeadline(start, end) {
        var diff = end.getTime() - start.getTime();
        var quarter = diff / 4;
        var deadline = new Date(start.getTime() + quarter);
        
        // فرمت تاریخ
        var deadlineStr = deadline.getFullYear() + '/' + 
                         String(deadline.getMonth() + 1).padStart(2, '0') + '/' + 
                         String(deadline.getDate()).padStart(2, '0');
        
        deadlineDate.val(deadlineStr);
        deadlineDate.prop('disabled', false);
    }
    
    // رویداد تغییر تاریخ شروع
    startDate.on('change', function() {
        var startVal = $(this).val().trim();
        
        if (startVal) {
            endDate.prop('disabled', false);
            endDate.val('');
            deadlineDate.val('');
            deadlineDate.prop('disabled', true);
        } else {
            endDate.prop('disabled', true);
            deadlineDate.prop('disabled', true);
            endDate.val('');
            deadlineDate.val('');
        }
        
        // اگر تاریخ اتمام پر شده بود، دوباره اعتبارسنجی کن
        if (endDate.val().trim()) {
            validateEndDate();
        }
    });
    
    // چند رویداد برای تاریخ اتمام
    endDate.on('change', validateEndDate);
    
    // رویداد blur (وقتی از فیلد خارج می‌شود)
    endDate.on('blur', function() {
        if ($(this).val().trim()) {
            validateEndDate();
        }
    });
    
    // رویداد input (تایپ لحظه‌ای - اختیاری)
    endDate.on('input', function() {
        // فقط وقتی مقدار کامل به نظر می‌رسد اعتبارسنجی کن
        var val = $(this).val().trim();
        if (val.length >= 8) { // حداقل طول یک تاریخ
            validateEndDate();
        }
    });
    
    // وقتی فرم submit می‌شود
    $('form').on('submit', function(e) {
        var startVal = startDate.val().trim();
        var endVal = endDate.val().trim();
        var deadlineVal = deadlineDate.val().trim();
        
        if (!startVal) {
            e.preventDefault();
            alert('لطفا تاریخ شروع را وارد کنید');
            startDate.focus();
            return false;
        }
        
        if (!endVal) {
            e.preventDefault();
            alert('لطفا تاریخ اتمام را وارد کنید');
            endDate.focus();
            return false;
        }
        
        if (!deadlineVal) {
            e.preventDefault();
            alert('لطفا منتظر بمانید تا مهلت ثبت عضو محاسبه شود');
            return false;
        }
        
        // اعتبارسنجی نهایی
        if (!validateEndDate()) {
            e.preventDefault();
            return false;
        }
    });
});
JS;

$this->registerJs($js);

?>

<?php
$jss = <<< JS
$(document).ready(function() {
    // تعریف متغیرها
    var courseTypeSelect = $('#course-type');
    var capacitySelect = $('.capacity_type');
    var inPersonValue = "4"; // مقدار دوره حضوری
    var unlimitedValue = "1"; // مقدار ظرفیت نامحدود
    
    // ایجاد یک آی‌دی یکتا اگر وجود نداشته باشد
    if (!capacitySelect.attr('id')) {
        capacitySelect.attr('id', 'capacity_type_' + Math.random().toString(36).substr(2, 9));
    }
    
    var capacityTypeId = capacitySelect.attr('id');
    
    // ذخیره گزینه‌های اصلی ظرفیت
    var originalCapacityOptions = capacitySelect.html();
    
    // تابع برای آپدیت گزینه‌های ظرفیت
    function updateCapacityOptions() {
        var courseType = courseTypeSelect.val();
        var selectedCapacity = capacitySelect.val();
        
        // بازیابی گزینه‌های اصلی
        capacitySelect.html(originalCapacityOptions);
        
        // اگر دوره حضوری است
        if (courseType === inPersonValue) {
            // حذف گزینه نامحدود
            capacitySelect.find('option[value="' + unlimitedValue + '"]').remove();
            
            // اگر قبلاً نامحدود انتخاب شده بود
            if (selectedCapacity === unlimitedValue) {
                capacitySelect.val('');
                // فعال کردن رویداد onchange برای پاکسازی موارد وابسته
                triggerOnChange(capacitySelect);
            }
        }
        
        // بازسازی رویداد onchange اصلی
        restoreOnChangeEvent();
        
        // بازسازی Select2 اگر وجود دارد
        reinitializeSelect2();
    }
    
    // فعال‌سازی رویداد onchange
    function triggerOnChange(element) {
        var onchangeCode = element.attr('onchange');
        if (onchangeCode) {
            // ایجاد یک تابع از کد onchange
            try {
                var changeFunc = new Function('return (function() {' + onchangeCode + '})')();
                changeFunc.call(element[0]);
            } catch (e) {
                console.error('Error executing onchange:', e);
            }
        }
    }
    
    // بازسازی رویداد onchange
    function restoreOnChangeEvent() {
        var onchangeCode = capacitySelect.attr('onchange');
        capacitySelect.off('change.capacity').removeAttr('onchange');
        
        if (onchangeCode) {
            capacitySelect.on('change.capacity', function() {
                try {
                    eval(onchangeCode);
                } catch (e) {
                    console.error('Error in onchange event:', e);
                }
            });
        }
    }
    
    // بازسازی Select2
    function reinitializeSelect2() {
        if ($.fn.select2 && capacitySelect.hasClass('js-example-basic-single')) {
            capacitySelect.select2('destroy');
            capacitySelect.select2({
                placeholder: "انتخاب"
            });
        }
    }
    
    // اجرای اولیه
    updateCapacityOptions();
    
    // گوش دادن به تغییرات نوع دوره
    courseTypeSelect.on('change', function() {
        updateCapacityOptions();
        
        // اگر دوره حضوری است و قبلاً نامحدود انتخاب شده بود
        if ($(this).val() === inPersonValue && capacitySelect.val() === unlimitedValue) {
            capacitySelect.val('');
            triggerOnChange(capacitySelect);
        }
    });
    
    // گوش دادن به تغییرات ظرفیت
    $(document).on('change', '.capacity_type', function() {
        var courseType = courseTypeSelect.val();
        var capacityValue = $(this).val();
        
        // بررسی انتخاب نامحدود برای دوره حضوری
        if (courseType === inPersonValue && capacityValue === unlimitedValue) {
            alert("برای دوره حضوری نمی‌توان گزینه نامحدود را انتخاب کرد.");
            $(this).val('');
            triggerOnChange($(this));
            return false;
        }
    });
});

JS;

$this->registerJs($jss);



$digit = <<< JS

(function() {
    'use strict';

    // تابع بررسی: آیا کاراکتر یک عدد انگلیسی است؟
    function isEnglishDigit(char) {
        return /^[0-9]$/.test(char);
    }

    // تابع بررسی: آیا رشته حاوی اعداد غیرانگلیسی است؟
    function containsNonEnglishDigit(str) {
        // اعداد فارسی (۰-۹) و عربی (٠-٩) را چک می‌کند
        return /[۰-۹]|[٠-٩]/.test(str);
    }

    // تابع نمایش هشدار (می‌توانید متن دلخواه خود را جایگزین کنید)
    function showAlert() {
        toastr.error("لطفا اعداد را با کیبورد انگلیسی وارد کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
    }

    // تابع اصلی که روی هر فیلد اعمال می‌شود
    function setupEnglishDigitsInput(input) {
        // ذخیره آخرین مقدار مجاز
        let lastValidValue = input.value;

        // ۱. رویداد keydown: جلوگیری از تایپ کاراکترهای غیرمجاز
        input.addEventListener('keydown', function(e) {
            const key = e.key;

            // اجازه کلیدهای کنترلی (Backspace, Tab, Enter, Escape, arrows, Home, End, و ...)
            const controlKeys = [
                'Backspace', 'Tab', 'Enter', 'Escape', 'Delete',
                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                'Home', 'End', 'PageUp', 'PageDown'
            ];
            if (controlKeys.includes(key)) {
                return; // اجازه عبور
            }

            // اجازه کلیدهای ترکیبی با Ctrl/Cmd (مثل Ctrl+A, Ctrl+C, Ctrl+V)
            if (e.ctrlKey || e.metaKey) {
                return; // اجازه عبور (اما پیست بعداً کنترل می‌شود)
            }

            // اگر کلید یک عدد انگلیسی است، اجازه بده
            if (isEnglishDigit(key)) {
                return;
            }

            // اگر کلید عدد فارسی یا عربی است، بلاک کن و هشدار بده
            if (/^[۰-۹]$/.test(key) || /^[٠-٩]$/.test(key)) {
                e.preventDefault();
                showAlert();
                return;
            }

            // هر کلید دیگر (حروف، علائم و ...) بلاک شود
            e.preventDefault();
        });

        // ۲. رویداد paste: بررسی متن چسبانده شده
        input.addEventListener('paste', function(e) {
            e.preventDefault(); // همیشه پیش‌فرض را لغو می‌کنیم تا خودمان مدیریت کنیم
            const pastedText = (e.clipboardData || window.clipboardData).getData('text/plain');

            // اگر متن چسبانده شده شامل اعداد غیرانگلیسی باشد، هشدار بده و هیچ کاری نکن
            if (containsNonEnglishDigit(pastedText)) {
                showAlert();
                return;
            }

            // اگر فقط شامل اعداد انگلیسی و کاراکترهای مجاز دیگر باشد، آن را در جای درست وارد کن
            // (اختیاری: می‌توانید فقط اعداد را نگه دارید، اما ما کل متن را با شرط بالا پذیرفته‌ایم)
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const currentValue = input.value;
            const newValue = currentValue.substring(0, start) + pastedText + currentValue.substring(end);
            input.value = newValue;
            lastValidValue = newValue; // به‌روزرسانی مقدار مجاز
            input.setSelectionRange(start + pastedText.length, start + pastedText.length);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // ۳. رویداد input: برای مواردی مثل autofill یا drag-drop که از keydown رد نمی‌شوند
        input.addEventListener('input', function(e) {
            const currentValue = input.value;
            // اگر مقدار جدید شامل اعداد غیرانگلیسی است
            if (containsNonEnglishDigit(currentValue)) {
                // بازگرداندن به آخرین مقدار مجاز
                input.value = lastValidValue;
                showAlert();
                // اگر نیاز است validatorهای Yii را آگاه کنید
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                // در غیر این صورت مقدار جدید را به عنوان مجاز ذخیره کن
                lastValidValue = currentValue;
            }
        });

        // ۴. رویداد drop: جلوگیری از درگ کردن متن غیرمجاز
        input.addEventListener('drop', function(e) {
            e.preventDefault();
            const text = e.dataTransfer.getData('text/plain');
            if (containsNonEnglishDigit(text)) {
                showAlert();
                return;
            }
            // درج متن در موقعیت رها شده
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const currentValue = input.value;
            const newValue = currentValue.substring(0, start) + text + currentValue.substring(end);
            input.value = newValue;
            lastValidValue = newValue;
            input.setSelectionRange(start + text.length, start + text.length);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    // اعمال روی تمام فیلدهای موجود
    document.querySelectorAll('.only-english-digits').forEach(setupEnglishDigitsInput);

    // نظارت بر اضافه شدن فیلدهای جدید (مثلاً با Ajax)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // المان
                    if (node.matches && node.matches('.only-english-digits')) {
                        setupEnglishDigitsInput(node);
                    }
                    if (node.querySelectorAll) {
                        node.querySelectorAll('.only-english-digits').forEach(setupEnglishDigitsInput);
                    }
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();

JS;

$this->registerJs($digit);

$ins_date = <<< JS

JS;

$this->registerJs($ins_date);
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

    div[data-key] {
        display: none;
    }

    .summary {
        display: none;
    }
    .cke_textarea_inline
    {
        padding: 10px;
        height: 200px;
        overflow: auto;
        font-family:IRANYekanWeb;
        border: 1px solid gray;
        -webkit-appearance: textfield;
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="modal fade" id="cropper-modal" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font title" id="modalCenterTitle">ویرایش تصویر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="body">
                    <div class="" style="width: 500px; height: 500px;">
                        <img src="" class="cropper-image" alt="img-cropper" style="width: 500px; height: 500px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button class="btn btn-label-primary submit-crop">تایید</button>
                </div>
            </div>
        </div>
    </div>

    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت دوره</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">دوره های جامع و یکساله</a>
            </li>
            <li class="breadcrumb-item active">ثبت دوره جدید</li>
        </ol>
    </nav>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">فرم ثبت دوره جدید</h5>
        </div>
        <div class="card-body">
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data'
                        ],
                        'fieldConfig' => [
                            'options' => [
                                'tag' => false,
                            ],
                        ],
                    ]
                ); ?>
                <div class="row">
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان اصلی فارسی *</label>
                        <?= $form->field($model, 'title[main_fa]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی فارسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان اصلی انگلیسی </label>
                        <?= $form->field($model, 'title[main_en]')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true,
//                                'readonly' => true,
//                                'disabled' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی انگلیسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان فارسی (داخل مدرک) *</label>
                        <?= $form->field($model, 'title[degree_fa]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فارسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان انگلیسی (داخل مدرک) </label>
                        <?= $form->field($model, 'title[degree_en]')->textInput(
                            [
                                'class' => 'form-control text-start',
//                                'required' => true,
//                                'readonly' => true,
//                                'disabled' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان انگلیسی را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">قیمت اصلی (تومان) *</label>
                        <?= $form->field($model, 'price')->textInput(
                            [
                                'class' => 'form-control text-start only-english-digits',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
//                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">قیمت با تخفیف (تومان) </label>
                        <?= $form->field($model, 'discount_price')->textInput(
                            [
                                'class' => 'form-control text-start only-english-digits',
//                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">زمان برگزاری * </label>
                        <?= $form->field($model, 'time')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا زمان برگزاری دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">محل برگزاری * </label>
                        <?= $form->field($model, 'place')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا محل برگزاری دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع دوره *</label>
                        <?= $form->field($model, 'content_type')->dropDownList(
                            $courseType,
                            [
                                'class' => 'form-select',
                                'id' => 'course-type',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نوع دوره را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع ظرفیت *</label>
                        <?= $form->field($model, 'student_capacity[type]')->dropDownList(
                            $capacityType,
                            [
                                'class' => 'form-select capacity_type',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا نوع ظرفیت دوره را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'onchange' => '
            $.get( "' . Url::toRoute('/packages/capacity') . '", { id: $(this).val() } )
            .done(function( data ) {
                $("#capacity1").html(data);
                $(".js-example-basic-single").select2({
                    placeholder: "انتخاب"
                });
            });
          
            if (this.value == "3") {
                $("#contract_file_div").show();
                $("#contract_file").prop("required", true);
            } else {
                $("#contract_file_div").hide();
                $("#contract_file").prop("required", false);
                $("#fileInput").val(""); 
            }
        '
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity1">

                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="contract_file_div" style="display:none;">
                        <label for="nameWithTitle" class="form-label">فایل قرارداد *</label>
                        <?= $form->field($model, 'contract_file')->fileInput(
                            [
                                'class' => 'form-control text-start',
                                'id' => 'contract_file',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا فایل قرارداد را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
<!--                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">-->
<!--                        <label for="nameWithTitle" class="form-label">دسته بندی دوره *</label>-->
<!--                        --><?php //= $form->field($model, 'type')->dropDownList(
//                            $type,
//                            [
//                                'prompt' => 'لطفا انتخاب کنید',
//                                'class' => 'form-select',
//                                'required' => true,
//                                'id' => 'course_type',
//                                'oninvalid' => 'this.setCustomValidity(\'لطفا دسته بندی دوره را مشخص کنید\')',
//                                'oninput' => 'setCustomValidity(\'\')',
//                            ]
//                        )->label(false); ?>
<!--                    </div>-->
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">مدت زمان دوره *</label>
                        <?= $form->field($model, 'duration')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'id' => 'duration',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را به درستی وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'type' => 'number',
                                'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from1">
                        <label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>
                        <?= $form->field($model, 'date[from]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'id' => 'start-date',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to1">
                        <label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>
                        <?= $form->field($model, 'date[to]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'id' => 'end-date',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">آخرین مهلت ثبت عضو  </label>
                        <?= $form->field($model, 'deadline_date')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'readonly' => true,
                                'id' => 'deadline-date',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا آخرین مهلت ثبت عضو را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">توضیحات دوره</label>
                        <?php
                        echo $form->field($model, 'description')->widget(CKEditor::className(),[
                            'editorOptions' => [
                                'preset' => 'full',
                                'inline' => false,
                            ],
                        ])->label(false); ?>
                    </div>
                    <div class="card mb-4 relative">
                        <div class="card-body">
                            <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-test" cropper="cropper-modal">
                                <div class="dz-message needsclick">
                                    <?= $form->field($model, 'preview_image')->fileInput(
                                        [
                                            'class' => 'form-control text-start drop-file',
                                            'required' => true
                                        ]
                                    )->label(false); ?>
                                    <input type="hidden" name="image" class="base64_img" />
                                    <span class="drop-title"></span>
                                    <span class="note needsclick">تصویر پیش نمایش دوره</span>
                                    <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize') / 1000 ?> کیلوبایت باشد</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divider">
                        <div class="divider-text">شرایط اقساطی</div>
                    </div>
                    <div class="form-repeater">
                        <div data-repeater-list="installments">
                            <div class="form-send-message d-flex justify-content-between align-items-center">
                                <?= $form->field($model, 'prepayment_installments')->textInput(
                                    [
                                        'class' => 'form-control message-input me-3 only-english-digits',
                                        'type' => 'number',
                                        'placeholder' => 'مبلغ پیش پرداخت'
                                    ]
                                )->label(false); ?>
                                <div class="message-actions d-flex align-items-center">
                                    <button class="btn btn-primary" data-repeater-create="" type="button" style="width: 150px;">
                                        <i class="bx bx-plus me-1"></i>
                                        <span class="align-middle grow">افزودن قسط</span>
                                    </button>
                                </div>
                            </div>
                            <div data-repeater-item="">
                                <div class="row">
                                    <div class="mb-3 col-lg-6 col-xl-3 col-12 mb-0">
                                        <label class="form-label" for="form-repeater-1-1">تاریخ پرداخت *</label>
                                        <input type="text" required name="deadline" class="form-control text-start add-dob" dir="ltr">
                                    </div>
                                    <div class="mb-3 col-lg-6 col-xl-3 col-12 mb-0">
                                        <label class="form-label" for="form-repeater-1-2">مبلغ (تومان) *</label>
                                        <input type="number" required name="amount" id="form-repeater-1-2" class="form-control text-start" dir="ltr">
                                    </div>
                                    <div class="mb-3 col-lg-12 col-xl-2 col-12 d-flex align-items-center mb-0">
                                        <button class="btn btn-label-danger mt-4" data-repeater-delete="">
                                            <i class="bx bx-x me-1"></i>
                                            <span class="align-middle">حذف</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0 add-item">

                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="select2Basic" class="form-label">دانشکده *</label>
                        <?php
                        if (Yii::$app->user->identity->role == 'user') {
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا دانشکده را مشخص کنید',
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'id' => '',
                                    'data-allow-clear' => true,
                                    'onchange' => '
                                    let capacity_type = $("#courses-student_capacity-type").val()
                                    $.get( "' . Url::toRoute('/packages/brokers1') . '", { id: $(this).val(), capacity_type: capacity_type } )
                                    .done(function( data ) {
                                    var main_data=JSON.parse(data);
                                        $(\'#broker1\').html(main_data.brokers);
                                        $(\'#teachers1\').html(main_data.teachers);
                                        $(\'#lessons1\').html(main_data.lessons);
                                        $(".select2").each(function () {
                                        var targetInput = $(this);
                                        var select2Input = targetInput.select2({
                                            placeholder: "لطفا انتخاب کنید",
                                            dropdownParent: targetInput.hasClass("none-parent") ? null : targetInput.parent(),
                                            value: "borna",
                                        });
                                    });
                                    }
                                );'
                                ]
                            )->label(false);
                        } else {
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'id' => '',
                                    'data-allow-clear' => true,
                                    'onchange' => '
                                    let capacity_type = $("#courses-student_capacity-type").val()
                                    $.get( "' . Url::toRoute('/packages/brokers1') . '", { id: $(this).val(), capacity_type: capacity_type } )
                                    .done(function( data ) {
                                    var main_data=JSON.parse(data);
                                        $(\'#broker1\').html(main_data.brokers);
                                        $(\'#teachers1\').html(main_data.teachers);
                                        $(\'#lessons1\').html(main_data.lessons);
                                        $(".select2").each(function () {
                                        var targetInput = $(this);
                                        var select2Input = targetInput.select2({
                                            placeholder: "لطفا انتخاب کنید",
                                            dropdownParent: targetInput.hasClass("none-parent") ? null : targetInput.parent(),
                                            value: "borna",
                                        });
                                    });
                                    }
                                );'
                                ]
                            )->label(false);
                        }
                        ?>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">کارگزار</label>
                        <div id="broker1">
                            <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                        </div>
                    </div>
                    <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                        <label for="nameWithTitle" class="form-label">نوع قرارداد کارگزار</label>
                        <div id="broker_contracts1">
                            <span class="badge bg-label-warning">در انتظار انتخاب کارگزار</span>
                        </div>
                    </div>
                    <hr class="mb-2">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">انتخاب دروس دوره *</label>
                        <div id="lessons1">
                            <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <div class="row" id="show_courses">

                        </div>
                    </div>


                </div>
            </div>
            <div class="modal-footer demo-inline-spacing">
                <?php
                if (Yii::$app->user->identity->role == 'user') {
                    echo '<button type="submit" name="send_to_admin" class="btn btn-primary submit-course-btn" disabled>ثبت دوره </button>';
                } else {
                ?>
                    <button type="submit" name="send_to_admin" class="btn btn-primary submit-course-btn" disabled>ثبت دوره و ارسال برای تائید</button>
                    <button type="submit" name="draft" class="btn btn-warning submit-course-btn" disabled>ثبت دوره به عنوان پیش نویس</button>
                <?php
                }
                ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>