<?php
$this->title = 'مدیریت دوره های تک درس';

use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

require_once(Yii::$app->basePath . '/web/jdf.php');

SingleAsset::register($this);
Select2Asset::register($this);
$model = new Courses();
$front = Yii::getAlias('@front');
$courseType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'نیمه حضوری',
    '4' => 'حضوری',
);
$capacityType = array(
    '1' => 'نامحدود',
    '2' => 'محدود',
    '3' => 'سازمانی'
);


if (Yii::$app->user->identity->role != 'user' && Yii::$app->user->identity->role != 'cnt')
{
    $collegeId = $myCollege['0']->_id;
    $collegeScript = <<< JS
     $.get("/courses/brokers1", { id: "{$collegeId}" } )
        .done(function(data) {
        var main_data=JSON.parse(data);
            $('#broker1').html(main_data.brokers);
            $('#teachers1').html(main_data.teachers);
            $('#archive1').html(main_data.archive);
            $('#lessons1').html(main_data.lessons);
        });
JS;
    $this->registerJs($collegeScript);
}

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
    toastr.warning("خطایی رخ داده است، لطفا مجددا تلاش کنید", {
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
    toastr.success("وضعیت دوره مرود نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.error("به دلیل ناقص بودن اطلاعات مالی دانشکده امکان تائید دوره وجود ندارد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.error("به دلیل ناقص بودن اطلاعات مالی کارگزار امکان تائید دوره وجود ندارد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("دوره با موفقیت حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.error("دوره با موفقیت ثبت گردید اما در ادوبی ثبت نگردید لطفا مجددا برای ثبت در ادوبی دوره تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '12')
        $script = <<< JS
    toastr.success("کلاس مورد نظر در ادوبی ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '13')
        $script = <<< JS
    toastr.error("خطای ثبت دوره در ادوبی، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '14')
        $script = <<< JS
    toastr.success("وضعیت نمایش دوره مورد نظر در سایت تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '15')
        $script = <<< JS
    toastr.success("دوره مورد نظر برای تائید به ادمین ارسال گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '16')
        $script = <<< JS
    toastr.error("دوره مورد نظر به دلیل رو به اتمام بودن قرارداد کارگزار قابل تائید نمی باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '17')
        $script = <<< JS
    toastr.error("برای ارسال دوره به منظور دریافت مجوز باید حداقل یک درس را ثبت کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '18')
        $script = <<< JS
    toastr.error("دوره مورد نظر به دلیل اتمام قرارداد کارگزار قابل تائید نمی باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
?>
<?php
$url = Yii::$app->urlManager->createAbsoluteUrl('courses/show_course_users', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-course-detail',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("حذف دوره");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('.title').html(main_data.title);
            $('#body').html(main_data.body);
            $('#submit').html(main_data.submit);
        }
        })
}
)
JS;
$this->registerJs($show_off);


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
    
    
});
JS;

$this->registerJs($js);


$js1 = <<< JS
$(document).ready(function() {
    function validateDuration(value, showToast = true) {
        let num = parseInt(value, 10);
        let errorMsg = '';
        
        if (isNaN(num)) {
            errorMsg = 'لطفا مدت زمان دوره را وارد کنید';
        } else if (num < 8) {
            errorMsg = 'امکان ثبت دوره کمتر از ۸ ساعت نمی باشد';
        } else if (num > 24) {
            errorMsg = 'برای ثبت دوره بیشتر از ۲۴ ساعت از قسمت دوره های میان مدت اقدام فرمائید';
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

    // فقط اگر فیلد duration در صفحه وجود داشته باشد (یعنی در فرم ثبت دوره)
    if ($('#duration').length) {
        $('#duration').on('blur', function() {
            validateDuration($(this).val(), true);
        });

        // اعمال روی فرم ثبت دوره به جای همه فرم‌ها
        $('#course-form').on('submit', function(e) {
            var isValid = validateDuration($('#duration').val(), false);
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
JS;

$this->registerJs($js1);




?>

<?php
$jss = <<< JS
$(document).ready(function() {
        setTimeout(() => {
        $("#teachers1 select").select2({
        placeholder: "انتخاب",
            dropdownParent: $("#modalCenter .modal-body")
        });
        
        $("#lessons1 select").select2({
        placeholder: "انتخاب",
        dropdownParent: $("#modalCenter .modal-body")
        });
        }, 200)
        
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
    /* Style the CKEditor element to look like a textfield */
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
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مدیریت دوره</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">دوره های تک درس</a>
            </li>
        </ol>
    </nav>
    <?php echo $this->render('_search', [
        'model' => $searchModel,
        'colleges' => $colleges,
        'brokers' => $brokers,
    ]); ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"> دوره های ثبت شده</h5>
            <?php
            if (DashboardController::access('create-course')) {
            ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCenter" id="create-course">
                    <span class="tf-icons fa-solid fa-square-plus me-1"></span>ثبت دوره جدید
                </button>
            <?php
            }
            ?>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>تصویر</th>
                        <th>عنوان دوره</th>
                        <th>استاد</th>
                        <?php if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt') echo ' <th>دانشکده</th>'; ?>
                        <th>ثبت کننده</th>
                        <th>تاریخ درخواست</th>
                        <th>کد مجوز</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php
                    $i = 1;
                    foreach ($dataProvider->models as $course)
                    {
                        $copyCourse = 'copyCourse' . rand();
                        $viewRejectionReason = 'viewRejectionReason' . rand();
                        $registrantInAdobe = 'registrantInAdobe' . rand();
                        $edit = 'edit' . rand();
                        $changeStatus = 'changeStatus' . rand();
                        $hiddenCourse = 'HiddenCourse'.rand();
                        $collegeDetail = DashboardController::college_detail($course->college);
                        $teacherDetail = null;
                        if (array_key_exists('teachers', $course->lessons[0]))
                            $teacherDetail = DashboardController::teacher_detail($course->lessons[0]['teachers']);
                        $status = 'نامشخص';
                        $statusBg = '';
                        $licenseCode = '-';
                        if ($course->license_code != null)
                            $licenseCode = $course->license_code;
                        if ($course->status == '0') {
                            $status = 'تائید شده - غیر فعال';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '1') {
                            $status = 'تائید شده - فعال';
                            $statusBg = 'bg-label-success';
                        } else if ($course->status == '2') {
                            $status = 'در انتظار بررسی';
                            $statusBg = 'bg-label-primary';
                        } else if ($course->status == '3') {
                            $status = 'پیش نویس';
                            $statusBg = 'bg-label-info';
                        } else if ($course->status == '4') {
                            $status = 'نیاز به اصلاح';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '5') {
                            $status = 'رد شده';
                            $statusBg = 'bg-label-danger';
                        } else if ($course->status == '6') {
                            $status = 'تمام شده';
                            $statusBg = 'bg-label-dark';
                        } else if ($course->status == '7') {
                            $status = 'در انتظار بررسی دانشکده';
                            $statusBg = 'bg-label-primary';
                        } else if ($course->status == '8') {
                            $status = 'نیاز به اصلاح توسط دانشکده';
                            $statusBg = 'bg-label-warning';
                        } else if ($course->status == '9') {
                            $status = 'رد شده توسط دانشکده';
                            $statusBg = 'bg-label-danger';
                        }
                        $meetingLost = true;
                        if($course->lessons != null)
                            if(array_key_exists('meeting', $course->lessons[0]))
                                $meetingLost = false;
                        $registrant = 'نامشخص';
                        $role = '';
                        $registrantDetail = DashboardController::registrant_detail($course->registrant);
                        if ($registrantDetail->role == 'user')
                            $role = 'ادمین';
                        else if ($registrantDetail->role == 'cnt')
                            $role = 'کارمند مرکز';
                        else if ($registrantDetail->role == 'emp')
                            $role = 'کارشناس دانشکده';
                        else if ($registrantDetail->role == 'broker')
                            $role = 'کارگزار';
                        $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name;
                        $sis = '';
                        if($course->status == '1' || $course->status == '6')
                        {
                            if($course->show_in_site === false)
                                $sis = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.91858 6.60465C2.70062 6.09784 2.11327 5.86324 1.60603 6.08063C1.0984 6.29818 0.863613 6.8869 1.08117 7.39453L1.0816 7.39553L1.08267 7.39802L1.08566 7.4049L1.09505 7.42618C1.10282 7.44366 1.11363 7.46765 1.12752 7.49772C1.15529 7.55783 1.19539 7.64235 1.2481 7.74777C1.35345 7.95845 1.5096 8.25357 1.71879 8.605C2.12772 9.29201 2.74529 10.2043 3.59029 11.1241L2.79285 11.9215C2.40232 12.312 2.40232 12.9452 2.79285 13.3357C3.18337 13.7262 3.81654 13.7262 4.20706 13.3357L5.04746 12.4953C5.61245 12.9515 6.24405 13.3814 6.94417 13.7519L6.16177 14.9544C5.86056 15.4173 5.99165 16.0367 6.45457 16.338C6.91748 16.6392 7.53693 16.5081 7.83814 16.0452L8.82334 14.531C9.50014 14.7386 10.2253 14.8864 11 14.9556V16.4998C11 17.0521 11.4477 17.4998 12 17.4998V12.9998C9.25227 12.9998 7.18102 11.8012 5.69633 10.4109C5.68823 10.4031 5.68003 10.3954 5.67173 10.3878C5.47324 10.2009 5.28532 10.0105 5.10775 9.81932C4.35439 9.00801 3.80137 8.19355 3.43737 7.58204C3.25594 7.27722 3.12302 7.02546 3.03696 6.85334C2.99397 6.76735 2.96278 6.70147 2.94319 6.65905C2.93339 6.63785 2.92651 6.62253 2.9225 6.61352L2.91858 6.60465ZM1.08117 7.39453L1.99995 6.99977C1.08081 7.39369 1.08117 7.39453 1.08117 7.39453Z" fill="#1C274C"/>
                                        <path opacity="0.5" d="M15.2209 12.3984C14.2784 12.7694 13.209 13.0002 12 13.0002V17.5002C12.5523 17.5002 13 17.0525 13 16.5002V14.9559C13.772 14.8867 14.4974 14.7392 15.1764 14.5311L16.1618 16.0456C16.463 16.5085 17.0825 16.6396 17.5454 16.3384C18.0083 16.0372 18.1394 15.4177 17.8382 14.9548L17.0558 13.7524C17.757 13.3816 18.3885 12.9517 18.9527 12.496L19.7929 13.3361C20.1834 13.7267 20.8166 13.7267 21.2071 13.3361C21.5976 12.9456 21.5976 12.3124 21.2071 11.9219L20.4097 11.1245C21.1521 10.3164 21.7181 9.51502 22.1207 8.86887C22.384 8.44627 22.5799 8.08609 22.7116 7.82793C22.7775 7.69874 22.8274 7.59476 22.8619 7.5209C22.8791 7.48397 22.8924 7.45453 22.902 7.4332L22.9134 7.40736L22.917 7.39913L22.9191 7.39411C23.1367 6.88648 22.9015 6.2986 22.3939 6.08105C21.8864 5.86355 21.2985 6.09892 21.0809 6.60627L21.0759 6.61747C21.0706 6.62926 21.0617 6.6489 21.0492 6.6758C21.0241 6.72962 20.9844 6.81235 20.9299 6.91928C20.8207 7.13337 20.6526 7.4431 20.4233 7.81119C19.9628 8.55023 19.2652 9.50857 18.3156 10.3999C17.4746 11.1893 16.4469 11.9158 15.2209 12.3984Z" fill="#1C274C"/>
                                        </svg>
                                        ';
                            else
                                $sis = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.5" d="M2 12C2 13.6394 2.42496 14.1915 3.27489 15.2957C4.97196 17.5004 7.81811 20 12 20C16.1819 20 19.028 17.5004 20.7251 15.2957C21.575 14.1915 22 13.6394 22 12C22 10.3606 21.575 9.80853 20.7251 8.70433C19.028 6.49956 16.1819 4 12 4C7.81811 4 4.97196 6.49956 3.27489 8.70433C2.42496 9.80853 2 10.3606 2 12Z" fill="#1C274C"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25 12C8.25 9.92893 9.92893 8.25 12 8.25C14.0711 8.25 15.75 9.92893 15.75 12C15.75 14.0711 14.0711 15.75 12 15.75C9.92893 15.75 8.25 14.0711 8.25 12ZM9.75 12C9.75 10.7574 10.7574 9.75 12 9.75C13.2426 9.75 14.25 10.7574 14.25 12C14.25 13.2426 13.2426 14.25 12 14.25C10.7574 14.25 9.75 13.2426 9.75 12Z" fill="#1C274C"/>
                                        </svg>
                                        ';
                        }
                    ?>
                        <tr>
                            <th scope="row"><?= $dataProvider->pagination->page * 50 + $i++ ?></th>
                            <td>
                                <div class="avatar avatar-sm me-2">
                                    <img src="<?= $front . '/lesson_images/' . $course->preview_image ?>" alt="" class="rounded-circle">
                                </div>
                            </td>
                            <td class="text-wrap w-25"><?= Html::encode($course->title['main_fa']) ?></td>
                            <td class="text-wrap w-25">
                                <?php
                                if ($teacherDetail != null)
                                    echo Html::encode($teacherDetail->first_name . ' ' . $teacherDetail->last_name);
                                else
                                    echo 'وارد نشده';
                                ?>
                            </td>
                            <?php
                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                            {
                                if (strlen($collegeDetail->title) <= 30)
                                    echo '<td>' . Html::encode($collegeDetail->title) . '</td>';
                                else {
                            ?>
                                    <td>
                                        <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= Html::encode($collegeDetail->title) ?>">
                                            <?= Html::encode(substr($collegeDetail->title, 0, 27)) . '...' ?>
                                        </button>
                                    </td>
                            <?php
                                }
                            }
                            ?>
                            <td class="text-wrap w-25">
                                <button type="button" class="btn btn-label-primary" data-bs-toggle="tooltip" data-bs-offset="0,8" data-bs-placement="top" data-bs-custom-class="tooltip-primary" data-bs-original-title="<?= $role ?>">
                                    <?= Html::encode($registrant) ?>
                                </button>
                            </td>
                            <td><?= jdate('Y/m/d', hexdec(substr($course->_id, 0, 8))) ?></td>
                            <td><?= $licenseCode ?></td>
                            <td>
                                <span class="badge <?= $statusBg ?>"><?= $status ?></span>
                                <?php
                                if($course->modified === true)
                                    echo '<br><span class="badge bg-label-info">اطلاح شده در انتظار بررسی</span>';
                                ?>
                                <?php
                                if(($course->adobe_status == '0' || $meetingLost) && ($course->content_type == '1' || $course->content_type == '2'))
                                    echo '<br><span class="badge bg-label-danger">خطای ادوبی در ثبت کلاس</span>';
                                echo $sis;
                                ?>
                            </td>
                            <td>
                                <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions" style="">
                                    <?php
                                    if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker') {
                                    ?>
                                        <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['courses/edit-course', '_id' => (string) $course->_id]) ?>">
                                            <?php
                                            if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                                echo 'تاييد و بررسي دوره';
                                            else
                                                echo 'مشاهده و ویرایش دوره';
                                            ?>
                                        </a>
                                    <?php
                                    }
                                    ?>
                                    <a class="dropdown-item" href="<?= Yii::$app->urlManager->createAbsoluteUrl(['manage-course-contents/teacher-part', '_id' => (string) $course->_id]) ?>">مدیریت دوره </a>
                                    <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $copyCourse ?>">کپی کردن دوره</a>
                                    <?php
                                    if ((Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp' || Yii::$app->user->identity->role == 'broker') && ($course->status == '4' || $course->status == '5' || $course->status == '8' || $course->status == '9')) {
                                    ?>
                                        <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $viewRejectionReason ?>">مشاهده دلیل رد یا اصلاح </a>
                                    <?php
                                    }
                                    if (Yii::$app->user->identity->role == 'user') {
                                        ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete" id="<?php echo (string) $course->_id; ?>">حذف دوره</a>
                                        <?php
                                    }
                                    if(($course->adobe_status == '0' || $meetingLost) && ($course->content_type == '1' || $course->content_type == '2'))
                                    {
                                        ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $registrantInAdobe ?>">ثبت کلاس در ادوبی</a>
                                        <?php
                                    }
                                    if ($course->status == '1' || $course->status == '6')
                                    {
                                        $showInSite = 'مخفی کردن در سایت';
                                            if($course->show_in_site === false)
                                                $showInSite = 'نمایش در سایت';
                                        ?>
                                        <a class="dropdown-item show-course-detail" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#<?= $hiddenCourse ?>"><?= $showInSite ?></a>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="<?= $viewRejectionReason ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده دلیل رد یا اصلاح درس <?= Html::encode($course->title['main_fa']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>وضعیت: <?= $status ?></p>
                                        <p style="word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap; margin: 0; line-height: 1.6;">
                                            دلیل: <?= Html::encode($course->rejection_reason) ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $registrantInAdobe ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت دوره در ادوبی </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['packages/register_class_in_adobe'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($course, '_id')->hiddenInput()->label(false); ?>
                                        آیا از ثبت دوره در ادوبی کاکنت اطمینان دارید؟
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary submit-course-btn">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $hiddenCourse ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">مخفی / نمایش درس در سایت </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['packages/hidden_course'],
                                                "method" => "post",
                                                'options' => [
                                                    'class' => '',
                                                    'enctype' => 'multipart/form-data'
                                                ],
                                            ]
                                        ); ?>
                                        <?= $form->field($course, '_id')->hiddenInput()->label(false); ?>
                                        <p>
                                            <?php
                                            $currentShowInSite = true;
                                            if($course->status == '1')
                                                if($course->show_in_site !== true)
                                                    $currentShowInSite = false;
                                            if($currentShowInSite == true)
                                                echo 'دوره مورد نظر هم اکنون در سایت نمایان است.<br> آیا از مخفی کردن درس در سایت مطمئن هستید؟';
                                            else
                                                echo 'درس مورد نظر هم اکنون در سایت مخفی شده است<br> آیا از نمایان کردن آن مطمئن هستید؟';
                                            ?>
                                        </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary submit-course-btn">بله مطمئنم</button>
                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="<?= $copyCourse ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">کپی کردن دوره <?= Html::encode($course->title['main_fa']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>آیا از کپی کردن دوره <?= Html::encode($course->title['main_fa']) ?> مطمئن هستید؟ </p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['courses/copy-course', '_id' => (string) $course->_id]) ?>" class="btn btn-primary submit-course-btn">بله مطمئنم</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
            <div class="demo-inline-spacing">
                <nav aria-label="Page navigation">
                    <?=
                    ListView::widget([
                        'dataProvider' => $dataProvider,
                        'emptyText' => '<div class="card">
                    <div class="card-body">
                        <div class="alert alert-danger" role="alert">نتیجه ای یافت نشد</div>
                    </div>
                </div>',
                        'pager' => [
                            'prevPageLabel' => ' <i class="tf-icon bx bx-chevrons-left"></i>',
                            'nextPageLabel' => ' <i class="tf-icon bx bx-chevrons-right"></i>',
                            'maxButtonCount' => 10,

                            'options' => [
                                'tag' => 'ul',
                                'class' => 'pagination justify-content-center',
                                'id' => 'pager-container',
                            ],
                            'linkOptions' => ['class' => 'page-item page-link'],
                            'activePageCssClass' => 'page-item active',
                            'disabledPageCssClass' => 'disable',
                            'prevPageCssClass' => 'paginate_button page-item previous',
                            'nextPageCssClass' => 'paginate_button page-item next',
                        ],
                    ]);
                    ?>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="exampleModalLabel3">ثبت دوره تک درس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        "method" => "post",
                        'options' => [
                            'class' => '',
                            'enctype' => 'multipart/form-data',
                            'id' => 'course-form'
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
                        <label for="nameWithTitle" class="form-label">مدت زمان دوره (ساعت) *</label>
                        <?= $form->field($model, 'duration')->textInput(
                            [
                                'type' => 'number',
                                'class' => 'form-control',
                                'required' => true,
                                'id' => 'duration',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
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
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="time">
                        <label for="nameWithTitle" class="form-label">ساعت شروع دوره *</label>
                        <?php
                        echo  $form->field($model, 'lessons[0][date][time]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from1">
                        <label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>
                        <?= $form->field($model, 'lessons[0][date][from]')->textInput(
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
                        <?= $form->field($model, 'lessons[0][date][to]')->textInput(
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
                                    'id' => 'college',
                                    'data-allow-clear' => true,
                                    'onchange' => '
                    $.get("' . Url::toRoute('/courses/brokers1') . '", { id: $(this).val() })
                    .done(function(data) {
                        var main_data = JSON.parse(data);
                        $("#broker1").html(main_data.brokers);
                        $("#teachers1").html(main_data.teachers);
                        $("#lessons1").html(main_data.lessons);
                        $("#archive1").html(main_data.archive);
                        
                        // مقداردهی مجدد Select2 برای المان‌های جدید
                        setTimeout(function() {
                            $("#broker1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#teachers1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#lessons1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#archive1 select").select2({
                                placeholder: "انتخاب"
                            });
                        }, 100);
                    });'
                                ]
                            )->label(false);
                        } else {
                            echo $form->field($model, 'college')->dropDownList(
                                $colleges,
                                [
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'id' => 'college',
                                    'data-allow-clear' => true,
                                    'onchange' => '
                    $.get("' . Url::toRoute('/courses/brokers1') . '", { id: $(this).val() })
                    .done(function(data) {
                        var main_data = JSON.parse(data);
                        $("#broker1").html(main_data.brokers);
                        $("#teachers1").html(main_data.teachers);
                        $("#lessons1").html(main_data.lessons);
                        $("#archive1").html(main_data.archive);
                        
                        // مقداردهی مجدد Select2 برای المان‌های جدید
                        setTimeout(function() {
                            $("#broker1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#teachers1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#lessons1 select").select2({
                                placeholder: "انتخاب"
                            });
                            $("#archive1 select").select2({
                                placeholder: "انتخاب"
                            });
                        }, 100);
                    });'
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
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">درس دوره *</label>
                        <div id="lessons1">
                            <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                        </div>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">مخفی کردن آرشیو *</label>
                        <div id="archive1">
                            <span class="badge bg-label-warning">در انتظار انتخاب درس</span>
                        </div>
                    </div>
                    <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                        <label for="nameWithTitle" class="form-label">مدرس دوره *</label>
                        <div id="teachers1">
                            <span class="badge bg-label-warning">در انتظار انتخاب دانشکده</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary submit-course-btn" disabled>ثبت دوره</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="delete" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <div id="submit"></div>
            </div>
        </div>
    </div>
</div>