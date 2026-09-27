<?php
$this->title = 'ویرایش دوره‌ی کوتاه‌مدت';

use frontend\controllers\DashboardController;
use app\components\CourseAccess;
use app\components\CourseStatus;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use app\models\Discounts;
use app\models\CoursesFinancial;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

$newLesson = new Courses();
SingleAsset::register($this);
Select2Asset::register($this);
$front = Yii::getAlias('@front');
$courseDetailType = array(
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

$flash = Yii::$app->session->getFlash(\frontend\controllers\CoursesController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $flashType = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$flashType'](" . \yii\helpers\Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 7000, escapeHtml: true});");
}

if (Yii::$app->session->has('status')) {
    if (Yii::$app->session->get('status') == '1')
        $script = <<< JS
    toastr.success("دوره مورد نظر ویرایش گردید", {
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
    toastr.success("درس مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '4')
        $script = <<< JS
    toastr.success("درس مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '5')
        $script = <<< JS
    toastr.success("درس مورد نظر اضافه گردید", {
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
    else if (Yii::$app->session->get('status') == '7')
        $script = <<< JS
    toastr.success("عضو مورد نظر به دوره اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '8')
        $script = <<< JS
    toastr.warning("عضو مورد نظر هم اکنون در لیست این دوره موجود می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '9')
        $script = <<< JS
    toastr.success("اعضای مورد نظر به دوره اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '10')
        $script = <<< JS
    toastr.success("وضعیت دانشپذیر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '11')
        $script = <<< JS
    toastr.success("دانشپذیر مورد نظر از دوره حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '12')
        $script = <<< JS
    toastr.success("مبلغ پیش پرداخت ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '13')
        $script = <<< JS
    toastr.success("قسط مورد نظر ویرایش گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '14')
        $script = <<< JS
    toastr.success("قسط مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '15')
        $script = <<< JS
    toastr.warning("کاربر مورد نظر در سیستم ادوبی ثبت نشده است، لطفا ابتدا کاربر را در ادوبی ثبت نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '16')
        $script = <<< JS
    toastr.warning("کاربر مورد نظر دارای نقش دیگری در سامانه است و نمی تواند به عنوان دستیار استاد انتخاب شود", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '17')
        $script = <<< JS
    toastr.success("نقش کاربر مورد نظر تغییر یافت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '18')
        $script = <<< JS
    toastr.warning("حجم عکس انتخاب شده بیشتر اندازه تعیین شده است", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '19')
        $script = <<< JS
    toastr.success("شرایط اقساط مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '20')
        $script = <<< JS
    toastr.success("قسط مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '23')
        $script = <<< JS
    toastr.success("ثبت در ادوبی انجام گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '24')
        $script = <<< JS
    toastr.success("نمره مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '26')
        $script = <<< JS
    toastr.success("کد تخفیف مورد نظر اضافه گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '27')
        $script = <<< JS
    toastr.success("کد تخفیف مورد نظر حذف گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '28')
        $script = <<< JS
    toastr.error("خطایی در دریافت توکن بانک رخ داده است، لطفا مجددا تلاش کنید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '29')
        $script = <<< JS
    toastr.success("پرداخت شما با موفقیت انجام پذیرفت", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '30')
        $script = <<< JS
    toastr.error("خطایی در پرداخت شما رخ داده است. اگر مبلغی از حساب شما کسر گردیده حداکثر تا ۷۲ ساعت به حساب شما عودت داده خواهد شد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}
$tab = <<< JS
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get('tab');

    if (tabValue) {
        $("#" + tabValue + " > button").click();
    } else {
        const isSearch = window.location.href?.includes('status') || window.location.href?.includes('page');
        $(isSearch ? ".course-tab:last > button" : ".course-tab:first > button").click();
    }

    function updateQueryStringParameter(uri, key, value) {
            let re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            let separator = uri.indexOf('?') !== -1 ? "&" : "?";
            if (uri.match(re)) {
                return uri.replace(re, '$1' + key + "=" + value + '$2');
            }
            return uri + separator + key + "=" + value;   
    }

    $(".course-tab").click(function() {
        const currentUrl = window.location.href;
        const newUrl = updateQueryStringParameter(currentUrl, 'tab', $(this).attr("id"));
        history.pushState(null, '', newUrl);
    });
JS;
$this->registerJs($tab);
$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_user_detail','https');
$_csrf = Yii::$app->request->getCsrfToken();
$list = <<<JS
$(document).on('click','#add_from-list',function(e) {
    e.preventDefault();
    var username = $('#username').val();
    var id = $('#packageId').val();
    $.ajax({
        url:'$url',
        type:'POST',
        data:{username:username,id:id, _csrf: yii.getCsrfToken()},
        success:function(data) {
            var main_data = JSON.parse(data);
            $('#selected-user').html(main_data.userDetail);  
            $('#final-ok').html(main_data.footer);  
        }
    });
});
JS;
$this->registerJs($list);

$excelUrl = Yii::$app->urlManager->createAbsoluteUrl('packages/check_excel_file','https');
$excel = <<<JS
$(document).on('click','#excel',function(e) {
    e.preventDefault();
    var \$btn = $(this);
    var originalText = \$btn.html();
    
    \$btn.prop('disabled', true)
        .removeClass('btn-primary')
        .addClass('btn-secondary')
        .html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>در حال بررسی...');
    var id = $('#packageId').val();
    var fd = new FormData();
    fd.append('file',$('#excel-file')[0].files[0]);
    fd.append('packageId ',$('#packageId').val());
    fd.append('_csrf', yii.getCsrfToken());

    console.log(fd)
    $.ajax({
        url:'$excelUrl',
        type:'POST',
        processData: false,  // Don't process the data
        contentType: false,  // Don't set content type
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-CSRF-Token', yii.getCsrfToken());
        },
        data:fd,
        success:function(data) {
            var main_data = JSON.parse(data);
            $('#message').html(main_data.message);  
            $('#final-ok').html(main_data.footer);  
        },
        error: function(xhr, status, error) {
            $('#message').html('<div class="alert alert-danger">خطا در پردازش فایل</div>');
        },
        complete: function(){
            // فعال کردن مجدد دکمه و بازگرداندن متن اصلی
            \$btn.prop('disabled', false).html(originalText);
            $('#ajax-loader').css("visibility", "hidden");
        }
    });
});
JS;
$this->registerJs($excel);

$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_course_lessons', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_off = <<<JS
$(document).on('click','.show-course-scores',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("در حال دریافت...");
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

$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_finance_info', 'https');
$_csrf = Yii::$app->request->getCsrfToken();
$show_finance = <<<JS
$(document).on('click','.show-finance-info',function(e) {
    e.preventDefault();
    var id = (this).id;
     $('.title').html("در حال دریافت...");
    $.ajax({
        url:'$url',
        type : 'POST',
        data : {id:id, _csrf: yii.getCsrfToken() },
        success:function(data) {
            console.log(JSON.parse(data));
            var main_data=JSON.parse(data);
            $('.finance_title').html(main_data.title);
            $('#finance_body').html(main_data.body);
        }
        })
}
)
JS;
$this->registerJs($show_finance);

?>

<?php
$pcPos = <<< JS
    $('#add-new-member').on("submit", async function(e) {
        e.preventDefault();

        const params = new URLSearchParams(window.location.search)
        const data = {
            username: $("#student-username").val(),
            first_name: $("#student-first-name").val(),
            last_name: $("#student-last-name").val(),
            auth_key: "JqtOGraPXHLobZrMcln5l43cFty4tXm383fuy3cAYok",
            items: [{ _id: params.get("_id"), payment_method: "1" }]
        };

        $('.pos-submit').attr("disabled", 'true');
        const res = await axios.post("https://api-eec.ut.ac.ir/external-api/pc-pos/checkout", data).then(res => res).catch(err => err);
        const orderData = res.data?.data;

        if(orderData?.additional_data) {
            toastr.success("درحال ارسال تراکنش به دستگاه پز...", {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });
            
            const posData = {
                RequestId: '',
                PcId: '1',
                TotalAmount: orderData.amount,
                MerchantAdditionalData: orderData.additional_data,
                Requests: orderData.requests,
            };

            const posRes = await axios.post("http://localhost:8000/multi-payment", posData);

            if(posRes.data?.status == 'success') {
                const callbackData = {
                    order_id: orderData.order_id,
                    status: posRes.data?.status,
                    tref: posRes.data?.traceNumber,
                    auth_key: "JqtOGraPXHLobZrMcln5l43cFty4tXm383fuy3cAYok",
                }
                const callbackRes = await axios.post("https://api-eec.ut.ac.ir/external-api/pc-pos/callback", callbackData);
                this.submit();
            } else {
                $('.pos-submit').attr("disabled", false);
                toastr.error("تراکنش ناموفق: " + posRes.data?.returnCode, {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });
            }
        } else {
            $('.pos-submit').attr("disabled", false);
            if(res.response?.data?.error == 'no-pos') {
                this.submit();
                return;
            }

            toastr.error("خطا در دریافت اطلاعات پرداخت...", {
                positionClass: "toast-top-center",
                containerId: "toast-top-center",
                "closeButton": "true"
            });
        }
    });
JS;

$this->registerJs($pcPos);
?>

<?php
$usernameCheck = <<< JS
// رویداد کلیک روی آیکون جستجو
$('#check-username-btn').on('click', function() {
    checkUsername();
});

// رویداد فشار دکمه Enter در فیلد نام کاربری
$('#student-username').on('keypress', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        checkUsername();
    }
});

function checkUsername() {
    var username = $('#student-username').val().trim();
    var resultDiv = $('#username-check-result');
    
    if (!username) {
        resultDiv.html('<div class="alert alert-warning">لطفا نام کاربری را وارد کنید</div>');
        return;
    }
    
    // نمایش لودینگ
    resultDiv.html('<div class="text-info"><i class="fa-solid fa-spinner fa-spin"></i> در حال بررسی...</div>');
    
    $.ajax({
        url: '/courses/check-username', // آدرس کنترلر
        type: 'POST',
        data: {
            username: username,
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                if (response.exists) {
                    var message = '<div class="alert alert-danger">';
                    message += '<i class="fa-solid fa-triangle-exclamation"></i> ';
                    message += 'این نام کاربری قبلا ثبت شده است';
                    
                    if (response.userInfo) {
                        message += '<br><small>نام: ' + response.userInfo.first_name + ' ' + response.userInfo.last_name + '</small>';
                    }
                    
                    message += '</div>';
                    resultDiv.html(message);
                } else {
                    resultDiv.html('<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> نام کاربری قابل استفاده است</div>');
                }
            } else {
                resultDiv.html('<div class="alert alert-danger">خطا در بررسی نام کاربری</div>');
            }
        },
        error: function() {
            resultDiv.html('<div class="alert alert-danger">خطا در ارتباط با سرور</div>');
        }
    });
}

// پاک کردن نتیجه وقتی کاربر تایپ می‌کند
$('#student-username').on('input', function() {
    $('#username-check-result').empty();
});
JS;

$this->registerJs($usernameCheck);



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
    // endDate.prop('disabled', true);
    // deadlineDate.prop('disabled', true);
    
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <ol class="lh-1-85 breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="javascript:void(0);">مدیریت دوره</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= Url::to(['index']) ?>">دوره‌های کوتاه‌مدت</a>
                </li>
                <li class="breadcrumb-item active">ویرایش دوره (<?= Html::encode($courseDetail->title['main_fa']) ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2">
                <a class="h6" href="<?= Url::to(['index']) ?>">بازگشت</a>
            </span>
        </div>
    </nav>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="mb-0"><i class="bx bx-git-commit me-1"></i>فرآیند بررسی و تأیید دوره</h6>
                <?php list($statusText, $statusColor) = CourseStatus::label($courseDetail); ?>
                <span class="badge bg-label-<?= Html::encode($statusColor) ?>">وضعیت فعلی: <?= Html::encode($statusText) ?></span>
            </div>
            <?= $this->render('_stepper', ['course' => $courseDetail]) ?>
        </div>
    </div>
    <div class="card text-center mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item course-tab" role="presentation" id="tab-id0">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id0" aria-controls="id0" aria-selected="false" tabindex="-1">
                        مشخصات دوره
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id1">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id1" aria-controls="id1" aria-selected="true">
                        کدهای تخفیف
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id2">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id2" aria-controls="id2" aria-selected="true">
                        اعضا
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id3">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id3" aria-controls="id3" aria-selected="true">
                        مالی
                    </button>
                </li>
            </ul>
            <?php
            if (Yii::$app->user->identity->role == 'user')
            {
            ?>
                <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                    <?php
                    if ($courseDetail->status != '1') {
                        $form = ActiveForm::begin(
                            [
                                'action' => ['dashboard/confirm_package'],
                                "method" => "post",
                            ]
                        );
                    ?>
                        <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                        <input type="hidden" name="page" value="courses">
                        <button type="submit" class="btn btn-success">تائید دوره</button>
                    <?php
                        ActiveForm::end();
                    } else
                        echo '<button type="button" disabled class="btn btn-label-dark">تائید دوره</button>';
                    ?>
                    <?php
                    if ($courseDetail->status != '4')
                        echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">نیاز به اصلاح</button>';
                    if ($courseDetail->status != '5')
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">رد کرد</button>';
                    ?>
                </div>
            <?php
            }
            if (Yii::$app->user->identity->role == 'emp' && ($courseDetail->status == '7' || $courseDetail->status == '8' || $courseDetail->status == '9'))
            {
                ?>
                <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                    <?php
                    if ($courseDetail->status != '1') {
                        $form = ActiveForm::begin(
                            [
                                'action' => ['dashboard/confirm_package_from_college'],
                                "method" => "post",
                            ]
                        );
                        ?>
                        <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                        <input type="hidden" name="page" value="courses">
                        <button type="submit" class="btn btn-success">تائید دوره</button>
                        <?php
                        ActiveForm::end();
                    } else
                        echo '<button type="button" disabled class="btn btn-label-dark">تائید دوره</button>';
                    ?>
                    <?php
                    if ($courseDetail->status != '8')
                        echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">نیاز به اصلاح</button>';
                    if ($courseDetail->status != '9')
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">رد کرد</button>';
                    ?>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="tab-content shadow-none">
            <div class="tab-pane fade" id="id0" role="tabpanel">
                <div class="row">
                    <div class="col-md-4 col-sm-12 pe-0 mb-md-0 mb-2">
                        <input readonly class="form-control" id="clipboard-example" type="text" value="<?= (string) $courseDetail->_id ?>">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <button type="button" class="btn btn-primary me-2"
                                onclick="document.getElementById('clipboard-example').select(); document.execCommand('copy'); toastr.success('کپی شد!', '', {positionClass: 'toast-top-center', closeButton: true});">
                            کپی آی دی دوره
                        </button>
                    </div>
                </div>
                <div class="divider">
                    <div class="divider-text">مشخصات دوره</div>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['edit'],
                            "method" => "post",
                            'options' => [
                                // 'enctype' => 'multipart/form-data'
                            ],
                        ]
                    ); ?>
                    <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                    <div class="row">
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان اصلی فارسی *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'title[main_fa]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی فارسی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->title['main_fa']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان اصلی انگلیسی </label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'title[main_en]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
//                                        'required' => true,
//                                        'readonly' => true,
//                                        'disabled' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی انگلیسی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->title['main_en']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان فارسی (داخل گواهی) *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'title[degree_fa]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فارسی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->title['degree_fa']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">عنوان انگلیسی (داخل گواهی) </label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'title[degree_en]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
//                                        'required' => true,
//                                        'readonly' => true,
//                                        'disabled' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان انگلیسی را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->title['degree_en']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">قیمت اصلی (تومان) *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'price')->textInput(
                                    [
                                        'class' => 'form-control text-start only-english-digits',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
//                                        'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.number_format($courseDetail->price).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">قیمت با تخفیف (تومان) *</label>
                            <?php
                            if($allowEdit && CourseAccess::canSetDiscount())
                                echo $form->field($courseDetail, 'discount_price')->textInput(
                                    [
                                        'class' => 'form-control text-start only-english-digits',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.number_format($courseDetail->discount_price).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">مدت زمان دوره (ساعت) *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'duration')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'type' => 'number',
                                        'id' => 'course-duration',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->duration).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">نوع دوره *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'content_type')->dropDownList(
                                    $courseDetailType,
                                    [
                                        'class' => 'form-select',
                                        'id' => 'course-type',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        // اصلاح ۲۰۲۶-۰۸-۲۸ (طبق درخواست کاربر «دقیق با همون شکل و قوانین دوره‌های
                                        // میان‌مدت»): عیناً همون الگوی packages/edit-package.php - گزینه‌ی
                                        // «محتوا محور» فقط وقتی این دوره از قبل همین مقدار رو نداشته غیرفعال
                                        // می‌شه، تا هم دوره‌های قدیمی محتوا محور درست نمایش داده بشن، هم امکان
                                        // انتخاب‌مجددِ این گزینه برای بقیه از UI هم مسدود بشه (سمت سرور هم توسط
                                        // Courses::validateContentTypeNotDisabled() تضمین می‌شه).
                                        'options' => [
                                            Courses::CONTENT_TYPE_CONTENT_BASED => ['disabled' => $courseDetail->content_type !== Courses::CONTENT_TYPE_CONTENT_BASED],
                                        ],
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetailType[$courseDetail->content_type] ?? '').'">';
                            ?>
                        </div>
                        <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                            <label for="nameWithTitle" class="form-label">نوع ظرفیت *</label>
                            <?php
                            if($allowEdit)
                                echo $form->field($courseDetail, 'student_capacity[type]')->dropDownList(
                                    $capacityType,
                                    [
                                        'class' => 'form-select capacity_type',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع ظرفیت دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        // اصلاح ۲۰۲۶-۰۸-۲۸: عیناً همون الگوی packages/edit-package.php - گزینه‌ی
                                        // «نامحدود» فقط وقتی این دوره از قبل همین مقدار رو نداشته غیرفعال می‌شه
                                        // (سمت سرور هم توسط Courses::validateCapacityTypeNotDisabled() تضمین می‌شه).
                                        'options' => [
                                            Courses::CAPACITY_TYPE_UNLIMITED => ['disabled' => (isset($courseDetail->student_capacity['type']) ? $courseDetail->student_capacity['type'] : null) !== Courses::CAPACITY_TYPE_UNLIMITED],
                                        ],
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/capacity') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#capacity\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false);
                            else
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($capacityType[$courseDetail->student_capacity['type']] ?? '').'">';
                            ?>
                        </div>
                        <?php
                        // اصلاح ۲۰۲۶-۰۸-۲۸ (طبق بازخورد کاربر: «توی صفحه‌ی ویرایش هم درستش کن»):
                        // بررسی دقیق نشون داد که در این صفحه (بر خلاف صفحه‌ی ثبت دوره) فیلد شماره‌ی
                        // ظرفیت از طریق PHP سمت سرور و بر اساس مقدار واقعیِ ذخیره‌شده‌ی دوره رندر
                        // می‌شه، نه از طریق AJAX روی رویداد onchange - پس برای رکوردهای موجود با
                        // نوع ظرفیت «محدود» (۲) از همون ابتدای لود صفحه به‌درستی نمایش داده می‌شه
                        // (تست زنده روی رکورد واقعی این مورد رو تائید کرد). با این‌حال، برای
                        // هم‌خوانی کامل با نسخه‌ی «فقط خواندنی» بالاتر (خط بالاتر که با isset از
                        // notice جلوگیری می‌کنه) و برای محکم‌کاری در برابر حالت فرضیِ نامعتبر
                        // (نبودِ کلید type)، همین بررسیِ ایمن با isset این‌جا هم اضافه شد - بدون
                        // هیچ تغییری در نتیجه‌ی نمایش برای داده‌های معتبر فعلی. توجه: عمداً از روش
                        // «trigger کردن onchange در لحظه‌ی لود» که در صفحه‌ی ثبت دوره استفاده شد
                        // این‌جا استفاده نشد، چون اون روش با فراخوانی AJAX به /courses/capacity
                        // یک مدلِ کاملاً خالی و تازه می‌سازه و مقدار ظرفیتِ واقعیِ همین دوره (که
                        // همین الان به‌درستی نمایش داده شده) رو با یک فیلد خالی جایگزین می‌کنه -
                        // یعنی همون کاری که این‌جا لازمه دقیقاً برعکسشه: نگه‌داشتنِ مقدار موجود.
                        if ((isset($courseDetail->student_capacity['type']) ? $courseDetail->student_capacity['type'] : null) == 2) {
                        ?>
                            <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity">
                                <label for="nameWithTitle" class="form-label">ظرفیت *</label>
                                <?php
                                if($allowEdit)
                                    echo $form->field($courseDetail, 'student_capacity[number]')->textInput(
                                        [

                                            'class' => 'form-control numeral-mask text-start',
                                            'required' => true,
                                            'type' => 'number',
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا ظرفیت دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else if(isset($courseDetail->student_capacity['number']))
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->student_capacity['number']).'">';
                                ?>
                            </div>
                        <?php
                        } else {
                        ?>
                            <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity"></div>
                        <?php
                        }
                        ?>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">زمان برگزاری * </label>
                            <?= $form->field($courseDetail, 'time')->textInput(
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
                            <?= $form->field($courseDetail, 'place')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'required' => true,
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا محل برگزاری دوره را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="time">
                            <?php
                            if ($courseDetail->lessons != null) {
                                echo '<label for="nameWithTitle" class="form-label">ساعت شروع دوره *</label>';
                                echo  $form->field($courseDetail, 'lessons[0][date][time]')->textInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            }
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from1">
                            <label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>
                            <?php
                            if($allowEdit && CourseAccess::isAdmin())
                               echo  $form->field($courseDetail, 'lessons[0][date][from]')->textInput(
                                    [
                                        'class' => 'form-control dob-picker text-start',
                                        'required' => true,
                                        'id' => 'start-date',
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else if(array_key_exists('from', $courseDetail->lessons[0]['date']))
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->lessons[0]['date']['from']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to1">
                            <label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>
                            <?php
                            if($allowEdit && CourseAccess::isAdmin())
                                 echo $form->field($courseDetail, 'lessons[0][date][to]')->textInput(
                                    [
                                        'class' => 'form-control dob-picker text-start',
                                        'required' => true,
                                        'id' => 'end-date',
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false);
                            else if(array_key_exists('to', $courseDetail->lessons[0]['date']))
                                echo '<input type="text" class="form-control text-start" disabled readonly value="'.Html::encode($courseDetail->lessons[0]['date']['to']).'">';
                            ?>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">آخرین مهلت ثبت عضو  </label>
                            <?= $form->field($courseDetail, 'deadline_date')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'readonly' => true,
                                    'id' => 'deadline-date',
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا آخرین مهلت ثبت عضو را وارد کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                ]
                            )->label(false); ?>
                        </div>
                        <?php
                        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->additional_access === true)
                        {
                            ?>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <div class="form-check mt-3">
                                    <?php
                                    if($allowEdit) {
                                        echo $form->field($courseDetail, 'allow_free_add_user')->checkbox([
                                            'class' => 'form-check-input',
                                            'label' => 'افزودن عضو بدون کیف پول',
                                            'labelOptions' => ['class' => 'form-check-label']
                                        ])->label(false);
                                    } else {
                                        $checked = $courseDetail->allow_free_add_user ? 'checked' : '';
                                        echo '<input type="checkbox" class="form-check-input" disabled ' . $checked . '>';
                                        echo '<label class="form-check-label">افزودن عضو بدون کیف پول</label>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                        <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                            <label for="nameWithTitle" class="form-label">توضیحات دوره</label>
                            <?php
                            echo $form->field($courseDetail, 'description')->widget(CKEditor::className(),[
                                'editorOptions' => [
                                    'preset' => 'full',
                                    'inline' => false,
                                ],
                            ])->label(false); ?>
                        </div>
                        <hr class="mt-2">
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="select2Basic" class="form-label">واحد *</label>
                            <?php
                            echo $form->field($courseDetail, 'college')->dropDownList(
                                $colleges,
                                [
                                    'prompt' => 'لطفا واحد را مشخص کنید',
                                    'class' => 'select2 form-select form-select-lg',
                                    'required' => true,
                                    'data-allow-clear' => true,
                                    "data" => "colleges",
                                    'oninvalid' => 'this.setCustomValidity(\'لطفا واحد را مشخص کنید\')',
                                    'oninput' => 'setCustomValidity(\'\')',
                                    'onchange' => '
                                                            $.get( "' . Url::toRoute('/courses/brokers') . '", { id: $(this).val() } )
                                                            .done(function( data ) {
                                                            var main_data=JSON.parse(data);
                                                                $(\'#broker\').html(main_data.brokers);
                                                                $(\'#teachers\').html(main_data.teachers);
                                                                $(\'#lessons\').html(main_data.lessons);
                                                            }
                                                        );'
                                ]
                            )->label(false);
                            ?>
                        </div>

                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">کارگزار</label>
                            <div id="broker">
                                <?php
                                if ($courseDetail->broker != null)
                                {
                                    $myBrokers = $this->context->my_brokers($courseDetail->college);
                                    if($myBrokers != null)
                                    {
                                        $myBrokers = ArrayHelper::map($myBrokers, function ($model) {
                                            return (string) $model->_id;
                                        }, function ($model) {
                                            $type = 'حقیقی';
                                            if($model->type == '1')
                                                $type = 'حقیقی - شرکت '.$model->company_info['company_title'];
                                            return $model->connector_info['first_name'].' '.$model->connector_info['last_name'].'('.$type.')';
                                        });
                                        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                            echo $form->field($courseDetail, 'broker[_id]')->dropDownList(
                                                $myBrokers,
                                                [
                                                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                                                    'class' => 'select2s form-select',
                                                    'id' => '',
                                                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                                ]
                                            )->label(false);
                                        else
                                            echo Html::encode($myBrokers[$courseDetail->broker['_id']]);
                                    }
                                ?>
                                <?php
                                } else {
                                ?>
                                    <span class="badge bg-label-warning">در انتظار انتخاب واحد</span>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                            <label for="nameWithTitle" class="form-label">نوع قرارداد کارگزار</label>
                            <div id="broker_contracts">
                                <?php
                                if ($courseDetail->broker != null)
                                {
                                    $myBrokerContracts = $this->context->my_broker_contract($courseDetail->broker['_id']);
                                    if($myBrokerContracts != null)
                                    {
                                        $myBrokerContracts = ArrayHelper::map($myBrokerContracts, 'id', function ($model) {
                                            return $model['title'] . ' (' . $model['share'] . ' درصد)';
                                        });
                                        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                            echo $form->field($courseDetail, 'broker[contract]')->dropDownList(
                                                $myBrokerContracts,
                                                [
                                                    'prompt' => 'لطفا قرارداد کارگزار را انتخاب کنید',
                                                    'class' => 'select2s form-select',
                                                    'id' => '',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا قرارداد کارگزار را انتخاب کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                ]
                                            )->label(false);
                                        else
                                            echo Html::encode($myBrokerContracts[$courseDetail->broker['contract']]);
                                    }
                                ?>
                                <?php
                                } else {
                                ?>
                                    <span class="badge bg-label-warning">در انتظار انتخاب کارگزار</span>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                        <hr class="mb-2">
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">درس دوره *</label>
                            <div id="lessons">
                                <?php
                                if ($courseDetail->lessons != null) {
                                    $myCourses = $this->context->my_courses($courseDetail->college);
                                    echo $form->field($courseDetail, 'lessons[0][_id]')->dropDownList(
                                        ArrayHelper::map($myCourses, function ($model) {
                                            return (string) $model->_id;
                                        }, function ($model) {
                                            return $model->title;
                                        }),
                                        [
                                            'prompt' => 'لطفا درس را انتخاب کنید',
                                            'class' => 'select2 form-select',
                                            'id' => '',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا درس را انتخاب کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                    ?>
                                    <?php
                                } else {
                                    ?>
                                    <span class="badge bg-label-warning">در انتظار انتخاب واحد</span>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">مخفی کردن آرشیو *</label>
                            <div id="lessons">
                                <?php
                                if ($courseDetail->lessons != null)
                                {
                                    $hiddenArchive = array(
                                        false => 'خیر' ,
                                        true => 'بله' ,
                                    );
                                    $myCourses = $this->context->my_courses($courseDetail->college);
                                    echo $form->field($courseDetail, 'lessons[0][hide_archive]')->dropDownList(
                                        $hiddenArchive,
                                        [
                                            'id' => '',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا وضعیت مخفی کردن آرشیو را مشخص کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                    ?>
                                    <?php
                                } else {
                                    ?>
                                    <span class="badge bg-label-warning">در انتظار انتخاب درس</span>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                            <label for="nameWithTitle" class="form-label">مدرس دوره *</label>
                            <div id="teachers">
                                <?php
                                if ($courseDetail->lessons != null) {
                                    $myTeachers = $this->context->my_teachers($courseDetail->college);
                                    echo $form->field($courseDetail, 'lessons[0][teachers]')->dropDownList(
                                        ArrayHelper::map($myTeachers, function ($model) {
                                            return (string) $model->_id;
                                        }, function ($model) {
                                            return $model->first_name . ' ' . $model->last_name;
                                        }),
                                        [
                                            'prompt' => 'لطفا مدرس را انتخاب کنید',
                                            'class' => 'select2 form-select',
                                            'id' => '',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا مدرس را انتخاب کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                } else {
                                ?>
                                    <span class="badge bg-label-warning">در انتظار انتخاب واحد</span>
                                <?php
                                }
                                ?>
                            </div>
                        </div>

                    </div>
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
            <div class="tab-pane fade" id="id1" role="tabpanel">
                <?php
                if ($discounts != null)
                {
                    ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">کدهای تخفیف ثبت شده</h4>
                        <?php if (CourseAccess::canSetDiscount()): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                            افزودن کد تخفیف
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php
                    $discountRow = 0;
                    foreach ($discounts as $discount)
                    {
                        $deleteDiscount = 'deleteDiscount' . rand();
                        $used = 0;
                        if($discount->used != null)
                            if(is_array($discount->used))
                                $used = count($discount->used);
                        ?>
                        <nav class="navbar navbar-expand-lg bg-label-secondary mb-2">
                            <div class="container-fluid">
                                <div class="collapse navbar-collapse" id="navbar-ex-8">
                                    <div class="navbar-nav me-auto">
                                        <a class="nav-item nav-link active" href="javascript:void(0)">مبلغ تخفیف: <?= number_format($discount->amount) . ' تومان' ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">کد تخفیف: <?= Html::encode($discount->code) ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">تعداد: <?= Html::encode($discount->count) ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">تعداد استفاده شده: <?= $used ?></a>
                                    </div>
                                    <?php
                                    if($discount->used == null && CourseAccess::canSetDiscount())
                                    {
                                        ?>
                                        <ul class="navbar-nav ms-lg-auto">
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="modal" data-bs-target="#<?= $deleteDiscount ?>" href="javascript:void(0);">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M3 6.52381C3 6.12932 3.32671 5.80952 3.72973 5.80952H8.51787C8.52437 4.9683 8.61554 3.81504 9.45037 3.01668C10.1074 2.38839 11.0081 2 12 2C12.9919 2 13.8926 2.38839 14.5496 3.01668C15.3844 3.81504 15.4756 4.9683 15.4821 5.80952H20.2703C20.6733 5.80952 21 6.12932 21 6.52381C21 6.9183 20.6733 7.2381 20.2703 7.2381H3.72973C3.32671 7.2381 3 6.9183 3 6.52381Z" fill="#1C274C" />
                                                        <path opacity="0.5" d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12405C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001Z" fill="#1C274C" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M9.42543 11.4815C9.83759 11.4381 10.2051 11.7547 10.2463 12.1885L10.7463 17.4517C10.7875 17.8855 10.4868 18.2724 10.0747 18.3158C9.66253 18.3592 9.29499 18.0426 9.25378 17.6088L8.75378 12.3456C8.71256 11.9118 9.01327 11.5249 9.42543 11.4815Z" fill="#1C274C" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M14.5747 11.4815C14.9868 11.5249 15.2875 11.9118 15.2463 12.3456L14.7463 17.6088C14.7051 18.0426 14.3376 18.3592 13.9254 18.3158C13.5133 18.2724 13.2126 17.8855 13.2538 17.4517L13.7538 12.1885C13.795 11.7547 14.1625 11.4381 14.5747 11.4815Z" fill="#1C274C" />
                                                    </svg>
                                                    حذف
                                                </a>
                                            </li>
                                        </ul>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </nav>
                        <div class="modal fade" id="<?= $deleteDiscount ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف کد تخفیف</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php $form = ActiveForm::begin(
                                            [
                                                'action' => ['packages/delete_discount'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <?php echo $form->field($discount, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            آیا از حذف کد تخفیف با مبلغ <?= number_format($discount->amount) ?> تومان و کد <?= Html::encode($discount->code) ?> مطمئن هستید؟
                                        </div>
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
                        <?php
                        $discountRow++;
                    }
                }
                else
                {
                    ?>
                    <div class="alert alert-warning text-dark" role="alert">برای این دوره تا کنون کد تخفیفی ثبت نشده است</div>
                    <?php if (CourseAccess::canSetDiscount()): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                        افزودن کد تخفیف
                    </button>
                    <?php endif; ?>
                    <?php
                }
                ?>
                <div class="modal fade" id="add_discount_code" tabindex="-1" style="display: none;" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن کد تخفیف</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                $discountModel = new Discounts();
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['packages/add_discount'],
                                        "method" => "post",
                                    ]
                                ); ?>
                                <?= $form->field($discountModel, 'course_id')->hiddenInput(
                                    [
                                        'value' => (string) $courseDetail->_id,
                                    ]
                                )->label(false); ?>
                                <div class="row">
                                    <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">مبلغ کد تخفیف (تومان) * </label>
                                        <?= $form->field($discountModel, 'amount')->textInput(
                                            [
                                                'class' => 'form-control text-start only-english-digits',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا مبلغ را صحیح وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
//                                                'onkeypress' => 'return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)'
                                            ]
                                        )->label(false); ?>
                                    </div>
                                    <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">تعداد *</label>
                                        <?= $form->field($discountModel, 'count')->textInput(
                                            [
                                                'class' => 'form-control text-start only-english-digits',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا تعداد کد تخفیف وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
//                                                'onkeypress' => 'return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)'
                                            ]
                                        )->label(false); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    بستن
                                </button>
                                <button type="submit" class="btn btn-primary">افزودن کد تخفیف</button>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="id2" role="tabpanel">
                <?php echo $this->render('_member_search', [
                    'model' => $searchModel,
                    'packageDetail' => $courseDetail,
                ]); ?>
                <p><a href="<?= Yii::$app->urlManager->createAbsoluteUrl(['packages/recording-grades', '_id' => (string) $courseDetail->_id]) ?>">برای ثبت کلی نمرات اینجا کلیک کنید</a></p>
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr class="text-nowrap">
                                <th>#</th>
                                <th>نام</th>
                                <th>نام خانوادگی</th>
                                <th>نام کاربری</th>
                                <th>ثبت کننده</th>
                                <th>وضعیت</th>
                                <th>نقش</th>
                                <th>ثبت نمره</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php
                            $i = 1;
                            foreach ($dataProvider->models as $member)
                            {
                                $scoreStatus = $this->context->score_status((string) $courseDetail->_id, (string) $member->_id);
                                $edit = 'edit' . rand();
                                $changeStatus = 'changeStatus' . rand();
                                $addToClass = 'addToClass' . rand();
                                $deleteFromClass = 'deleteFromClass' . rand();
                                $changeRole = 'changeRole' . rand();
                                $userRole = 'دانشپذیر';
                                //                                if ($member->role == 'mentor')
                                //                                    $userRole = 'دستیار استاد';
                                $memberStatus = 'خطا در ادوبی';
                                $statusClass = 'bg-label-danger';
                                $memberCourse = null;
                                if ($member->courses != null)
                                    foreach ($member->courses as $course)
                                        if (is_array($course))
                                            if (array_key_exists('_id', $course))
                                                if ($course['_id'] == (string) $courseDetail->_id)
                                                    $memberCourse = $course;
                                $registrant = 'کاربر';
                                if ($memberCourse != null) {
                                    if ($memberCourse['status'] == '1') {
                                        $memberStatus = 'فعال';
                                        $statusClass = 'bg-label-success';
                                    } else if ($memberCourse['status'] == '2') {
                                        $memberStatus = 'غیرفعال';
                                        $statusClass = 'bg-label-warning';
                                    }
                                    if (array_key_exists('role', $memberCourse))
                                        if ($memberCourse['role'] == 'mentor')
                                            $userRole = 'دستیار استاد';
                                    if (array_key_exists('registrant', $memberCourse))
                                    {
                                        if ($memberCourse['registrant'] == $member->username)
                                            $registrant = 'دانشپذیر';
                                        else {
                                            $registrantDetail = DashboardController::registrant_detail($memberCourse['registrant']);
                                            if ($registrantDetail != null)
                                            {
                                                $role = '';
                                                if ($registrantDetail->role == 'user')
                                                    $role = 'ادمین';
                                                else if ($registrantDetail->role == 'emp')
                                                    $role = 'کارشناس واحد';
                                                else if ($registrantDetail->role == 'broker')
                                                    $role = 'کارگزار';
                                                $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name . '(' . $role . ')';
                                            }
                                        }
                                    }
                                }
                            ?>
                                <tr>
                                    <th scope="row"><?= $dataProvider->pagination->page * 50 + $i++ ?></th>
                                    <td><?= Html::encode($member->first_name) ?></td>
                                    <td><?= Html::encode($member->last_name) ?></td>
                                    <td><?= Html::encode($member->username) ?>
                                        <?php
                                        if($member->issuance_certificate_information != null)
                                        {
                                            echo '<hr>';
                                            if(array_key_exists('id', $member->issuance_certificate_information))
                                                if($member->issuance_certificate_information['id'] != '')
                                                    echo 'کد ملی: '.Html::encode($member->issuance_certificate_information['id']);
                                            if(array_key_exists('phone', $member->issuance_certificate_information))
                                                if($member->issuance_certificate_information['phone'] != '')
                                                    echo '<br>شماره تماس: '.Html::encode($member->issuance_certificate_information['phone']);
                                        }
                                        ?>
                                    </td>
                                    <td><?= Html::encode($registrant) ?></td>
                                    <td><span class="badge <?= $statusClass ?>"><?= $memberStatus ?></span></td>
                                    <td><?= $userRole ?></td>
                                    <td><?= $scoreStatus ?></td>
                                    <td>
                                        <button class="btn p-0" type="button" id="analyticsOptions" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="analyticsOptions">
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $deleteFromClass ?>">حذف از دوره</a>
                                            <?php
                                            if ($memberCourse['status'] == '0')
                                            {
                                                $form = ActiveForm::begin(
                                                    [
                                                        'action' => ['packages/add_user_to_adobe'],
                                                        "method" => "post",
                                                    ]
                                                );
                                                ?>
                                                <input type="hidden" name="courseId" value="<?= (string) $courseDetail->_id ?>">
                                                <button class="dropdown-item">ثبت در ادوبی</button>
                                                <?php
                                                ActiveForm::end();
                                            }
                                            else
                                            {
                                                ?>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $changeStatus ?>">تغییر وضعیت</a>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#<?= $changeRole ?>">تغییر نقش</a>
                                                <?php
                                            }
                                            ?>
                                            <a class="dropdown-item show-finance-info" href="#" data-bs-toggle="modal" data-bs-target="#show-finance-info" id="<?php echo (string) $courseDetail->_id . '-' . (string) $member->username; ?>">اطلاعات مالی</a>
                                        </div>
                                    </td>
                                    <div class="modal fade" id="<?= $changeStatus ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر وضعیت <?= Html::encode($member->first_name . ' ' . $member->last_name) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php $form = ActiveForm::begin(
                                                        [
                                                            'action' => ['packages/change_status'],
                                                            "method" => "post",
                                                            'options' => [
                                                                'class' => '',
                                                                'enctype' => 'multipart/form-data'
                                                            ],
                                                        ]
                                                    ); ?>
                                                    <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                                    <input type="hidden" name="courseId" value="<?= (string) $courseDetail->_id ?>">
                                                    <p>وضعیت <?= Html::encode($member->first_name . ' ' . $member->last_name) ?> در حال حاضر (<?= $memberStatus ?>) می باشد</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                        بستن
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">تغییر وضعیت</button>
                                                    <?php ActiveForm::end(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal fade" id="<?= $deleteFromClass ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">حذف از دوره <?= Html::encode($member->first_name . ' ' . $member->last_name) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php $form = ActiveForm::begin(
                                                        [
                                                            'action' => ['packages/delete_user_from_course'],
                                                            "method" => "post",
                                                            'options' => [
                                                                'class' => '',
                                                                'enctype' => 'multipart/form-data'
                                                            ],
                                                        ]
                                                    ); ?>
                                                    <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                                    <input type="hidden" name="courseId" value="<?= (string) $courseDetail->_id ?>">
                                                    <p>آیا از حذف <?= Html::encode($member->first_name . ' ' . $member->last_name) ?> از این دوره مطمئن هستید؟ </p>
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
                                    <div class="modal fade" id="<?= $changeRole ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title secondary-font" id="modalCenterTitle">تغییر نقش <?= Html::encode($member->first_name . ' ' . $member->last_name) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php $form = ActiveForm::begin(
                                                        [
                                                            'action' => ['packages/change_role'],
                                                            "method" => "post",
                                                            'options' => [
                                                                'class' => '',
                                                                'enctype' => 'multipart/form-data'
                                                            ],
                                                        ]
                                                    ); ?>
                                                    <?= $form->field($member, '_id')->hiddenInput()->label(false); ?>
                                                    <input type="hidden" name="courseId" value="<?= (string) $courseDetail->_id ?>">
                                                    <p>نقش <?= Html::encode($member->first_name . ' ' . $member->last_name) ?> در حال حاضر (<?= $userRole ?>) می باشد</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                        بستن
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">تغییر نقش</button>
                                                    <?php ActiveForm::end(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </tr>
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
                        <div class="alert alert-danger" role="alert">کاربری یافت نشد</div>
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
            <div class="tab-pane fade" id="id3" role="tabpanel">
                <?php
                if ($courseFinancial != null)
                {
                    ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">صورت های مالی ثبت شده</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_financial">
                            افزودن صورت مالی
                        </button>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>ردیف</th>
                                <th>مبلغ</th>
                                <th>فایل</th>
                                <th>جزئیات</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                            <?php
                            $i = 1;
                            foreach ($courseFinancial as $value)
                            {
                                $viewCourseFinancial = 'viewCourseFinancial'.rand();
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= number_format($value->amount) ?></td>
                                    <td><a class="badge bg-label-secondary" href="<?= Yii::$app->urlManager->createUrl(['packages/file','filename' => $value->file])  ?>">دانلود</a></td>
                                    <td><a class="badge bg-label-info" href="#" data-bs-toggle="modal" data-bs-target="#<?= $viewCourseFinancial ?>">مشاهده جزئیات</a></td>
                                </tr>
                                <div class="modal fade" id="<?= $viewCourseFinancial ?>" tabindex="-1" style="display: none;" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title secondary-font" id="modalCenterTitle">مشاهده اطلاعات پرداخت</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-success alert-dismissible d-flex align-items-center" role="alert">
                                                    <svg class="me-2" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g opacity="0.5">
                                                            <path d="M21.9995 12.8175L21.591 12.409C20.7123 11.5303 19.2877 11.5303 18.409 12.409L17.6076 13.2104C17.2878 12.3573 16.4648 11.75 15.5 11.75C14.2574 11.75 13.25 12.7574 13.25 14V15.7638C12.7601 15.8183 12.2847 16.0334 11.909 16.409C11.0303 17.2877 11.0303 18.7123 11.909 19.591L12.318 20H10C6.22876 20 4.34315 20 3.17157 18.8284C2 17.6569 2 15.7712 2 12C2 11.5581 2.00188 10.392 2.00377 10H22C22.0019 10.392 22 11.5581 22 12C22 12.283 22 12.5553 21.9995 12.8175Z" fill="#1C274C"/>
                                                        </g>
                                                        <path d="M5.25 16C5.25 15.5858 5.58579 15.25 6 15.25H10C10.4142 15.25 10.75 15.5858 10.75 16C10.75 16.4142 10.4142 16.75 10 16.75H6C5.58579 16.75 5.25 16.4142 5.25 16Z" fill="#1C274C"/>
                                                        <path d="M9.99484 4H14.0052C17.7861 4 19.6766 4 20.8512 5.11578C21.6969 5.91916 21.9337 7.07507 22 9V10H2V9C2.0663 7.07507 2.3031 5.91916 3.14881 5.11578C4.3234 4 6.21388 4 9.99484 4Z" fill="#1C274C"/>
                                                        <path d="M19.4697 13.4697C19.7626 13.1768 20.2374 13.1768 20.5303 13.4697L22.5303 15.4697C22.8232 15.7626 22.8232 16.2374 22.5303 16.5303C22.2374 16.8232 21.7626 16.8232 21.4697 16.5303L20.75 15.8107V20C20.75 20.4142 20.4142 20.75 20 20.75C19.5858 20.75 19.25 20.4142 19.25 20V15.8107L18.5303 16.5303C18.2374 16.8232 17.7626 16.8232 17.4697 16.5303C17.1768 16.2374 17.1768 15.7626 17.4697 15.4697L19.4697 13.4697Z" fill="#1C274C"/>
                                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M15.5 13.25C15.9142 13.25 16.25 13.5858 16.25 14V18.1893L16.9697 17.4697C17.2626 17.1768 17.7374 17.1768 18.0303 17.4697C18.3232 17.7626 18.3232 18.2374 18.0303 18.5303L16.0303 20.5303C15.7374 20.8232 15.2626 20.8232 14.9697 20.5303L12.9697 18.5303C12.6768 18.2374 12.6768 17.7626 12.9697 17.4697C13.2626 17.1768 13.7374 17.1768 14.0303 17.4697L14.75 18.1893V14C14.75 13.5858 15.0858 13.25 15.5 13.25Z" fill="#1C274C"/>
                                                    </svg>
                                                    مبلغ پرداخت شده: <?= number_format($value->amount) ?> تومان
                                                </div>
                                                <div class="alert alert-success alert-dismissible d-flex align-items-center" role="alert">
                                                    <svg class="me-2" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6.94028 2C7.35614 2 7.69326 2.32421 7.69326 2.72414V4.18487C8.36117 4.17241 9.10983 4.17241 9.95219 4.17241H13.9681C14.8104 4.17241 15.5591 4.17241 16.227 4.18487V2.72414C16.227 2.32421 16.5641 2 16.98 2C17.3958 2 17.733 2.32421 17.733 2.72414V4.24894C19.178 4.36022 20.1267 4.63333 20.8236 5.30359C21.5206 5.97385 21.8046 6.88616 21.9203 8.27586L22 9H2.92456H2V8.27586C2.11571 6.88616 2.3997 5.97385 3.09665 5.30359C3.79361 4.63333 4.74226 4.36022 6.1873 4.24894V2.72414C6.1873 2.32421 6.52442 2 6.94028 2Z" fill="#1C274C"/>
                                                        <path opacity="0.5" d="M21.9995 14.0001V12.0001C21.9995 11.161 21.9963 9.66527 21.9834 9H2.00917C1.99626 9.66527 1.99953 11.161 1.99953 12.0001V14.0001C1.99953 17.7713 1.99953 19.6569 3.1711 20.8285C4.34267 22.0001 6.22829 22.0001 9.99953 22.0001H13.9995C17.7708 22.0001 19.6564 22.0001 20.828 20.8285C21.9995 19.6569 21.9995 17.7713 21.9995 14.0001Z" fill="#1C274C"/>
                                                        <path d="M18 17C18 17.5523 17.5523 18 17 18C16.4477 18 16 17.5523 16 17C16 16.4477 16.4477 16 17 16C17.5523 16 18 16.4477 18 17Z" fill="#1C274C"/>
                                                        <path d="M18 13C18 13.5523 17.5523 14 17 14C16.4477 14 16 13.5523 16 13C16 12.4477 16.4477 12 17 12C17.5523 12 18 12.4477 18 13Z" fill="#1C274C"/>
                                                        <path d="M13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17Z" fill="#1C274C"/>
                                                        <path d="M13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13Z" fill="#1C274C"/>
                                                        <path d="M8 17C8 17.5523 7.55228 18 7 18C6.44772 18 6 17.5523 6 17C6 16.4477 6.44772 16 7 16C7.55228 16 8 16.4477 8 17Z" fill="#1C274C"/>
                                                        <path d="M8 13C8 13.5523 7.55228 14 7 14C6.44772 14 6 13.5523 6 13C6 12.4477 6.44772 12 7 12C7.55228 12 8 12.4477 8 13Z" fill="#1C274C"/>
                                                    </svg>
                                                    تاریخ پرداخت: <?= Html::encode($value->payment_info['date'] ?? '') ?>
                                                </div>
                                                <div class="alert alert-success alert-dismissible d-flex align-items-center" role="alert">
                                                    <svg class="me-2" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path opacity="0.5" d="M3.46447 3.46447C2 4.92893 2 7.28595 2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12C22 7.28595 22 4.92893 20.5355 3.46447C19.0711 2 16.714 2 12 2C7.28595 2 4.92893 2 3.46447 3.46447Z" fill="#1C274C"/>
                                                        <path d="M10.004 5.75194C10.4182 5.74975 10.7522 5.41219 10.75 4.99798C10.7478 4.58377 10.4102 4.24977 9.99602 4.25196C8.91427 4.2577 8.01583 4.2824 7.28261 4.41067C6.53075 4.5422 5.88786 4.79268 5.36144 5.29403C4.90634 5.72746 4.60191 6.16641 4.43626 6.797C4.28612 7.36858 4.25937 8.07179 4.25231 8.9945C4.24914 9.40871 4.58234 9.74705 4.99654 9.75022C5.41074 9.75339 5.74909 9.42019 5.75226 9.00599C5.75952 8.05721 5.79204 7.53976 5.88704 7.1781C5.96654 6.87546 6.09314 6.6686 6.39592 6.38024C6.63788 6.1498 6.96814 5.98846 7.54109 5.88823C8.13268 5.78473 8.91071 5.75774 10.004 5.75194Z" fill="#1C274C"/>
                                                        <path d="M14.0041 4.25196C13.5899 4.24977 13.2523 4.58377 13.2501 4.99798C13.2479 5.41218 13.5819 5.74975 13.9961 5.75194C15.0894 5.75774 15.8674 5.78474 16.4589 5.88823C17.0319 5.98846 17.3621 6.1498 17.6041 6.38024C17.9069 6.6686 18.0335 6.87546 18.113 7.1781C18.208 7.53976 18.2405 8.05721 18.2477 9.00599C18.2509 9.42019 18.5893 9.75339 19.0035 9.75022C19.4177 9.74705 19.7509 9.40871 19.7477 8.9945C19.7406 8.07179 19.7139 7.36858 19.5637 6.797C19.3981 6.16641 19.0937 5.72746 18.6386 5.29403C18.1121 4.79269 17.4693 4.5422 16.7174 4.41067C15.9842 4.2824 15.0858 4.2577 14.0041 4.25196Z" fill="#1C274C"/>
                                                        <path d="M5 11.2503C4.58579 11.2503 4.25 11.5861 4.25 12.0003C4.25 12.4145 4.58579 12.7503 5 12.7503H19C19.4142 12.7503 19.75 12.4145 19.75 12.0003C19.75 11.5861 19.4142 11.2503 19 11.2503H5Z" fill="#1C274C"/>
                                                        <path d="M5.75226 14.9946C5.74909 14.5804 5.41074 14.2472 4.99654 14.2504C4.58234 14.2535 4.24914 14.5919 4.25231 15.0061C4.25937 15.9288 4.28612 16.632 4.43626 17.2036C4.60191 17.8342 4.90634 18.2731 5.36144 18.7066C5.88785 19.2079 6.53073 19.4584 7.28258 19.5899C8.01578 19.7182 8.91421 19.7429 9.99593 19.7486C10.4101 19.7508 10.7477 19.4168 10.7499 19.0026C10.7521 18.5884 10.4181 18.2508 10.0039 18.2487C8.91065 18.2429 8.13264 18.2159 7.54107 18.1124C6.96814 18.0121 6.63788 17.8508 6.39592 17.6204C6.09314 17.332 5.96654 17.1251 5.88704 16.8225C5.79204 16.4608 5.75952 15.9434 5.75226 14.9946Z" fill="#1C274C"/>
                                                        <path d="M19.7477 15.0061C19.7509 14.5919 19.4177 14.2535 19.0035 14.2504C18.5893 14.2472 18.2509 14.5804 18.2477 14.9946C18.2405 15.9434 18.208 16.4608 18.113 16.8225C18.0335 17.1251 17.9069 17.332 17.6041 17.6204C17.3621 17.8508 17.0319 18.0121 16.4589 18.1124C15.8674 18.2159 15.0894 18.2429 13.9961 18.2487C13.5819 18.2508 13.2479 18.5884 13.2501 19.0026C13.2523 19.4168 13.5899 19.7508 14.0041 19.7486C15.0858 19.7429 15.9842 19.7182 16.7174 19.5899C17.4693 19.4584 18.1121 19.2079 18.6386 18.7066C19.0937 18.2731 19.3981 17.8342 19.5637 17.2036C19.7139 16.632 19.7406 15.9288 19.7477 15.0061Z" fill="#1C274C"/>
                                                    </svg>
                                                    شماره سفارش: <?= $value->payment_info['order_id'] ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                                    بستن
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                }
                else
                {
                    ?>
                    <div class="alert alert-warning text-dark" role="alert">برای این دوره تا کنون هیچ صورت مالی ثبت نشده است</div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_financial">
                        افزودن صورت مالی
                    </button>
                    <?php
                }
                ?>
                <div class="modal fade" id="add_financial" tabindex="-1" style="display: none;" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title secondary-font" id="modalCenterTitle">افزودن صورت مالی</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                $financialModel = new CoursesFinancial();
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['packages/courses_financial'],
                                        "method" => "post",
                                    ]
                                ); ?>
                                <?= $form->field($financialModel, 'course_id')->hiddenInput(
                                    [
                                        'value' => (string) $courseDetail->_id,
                                    ]
                                )->label(false); ?>
                                <?= $form->field($financialModel, 'type')->hiddenInput(
                                    [
                                        'value' => '2',
                                    ]
                                )->label(false); ?>
                                <div class="row">
                                    <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">مبلغ (تومان) * </label>
                                        <?= $form->field($financialModel, 'amount')->textInput(
                                            [
                                                'class' => 'form-control text-start',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا مبلغ را صحیح وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                                'onkeypress' => 'return (event.charCode >= 48 && event.charCode <= 57) || (event.charCode == 46)'
                                            ]
                                        )->label(false); ?>
                                    </div>
                                    <div class="col-6 col-md-6 col-lg-6 col-sm-12 mb-3">
                                        <label for="nameWithTitle" class="form-label">فایل توضیحات * </label>
                                        <?= $form->field($financialModel, 'file')->fileInput(
                                            [
                                                'class' => 'form-control text-start',
                                                'required' => true,
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا فایل توضیحات را انتخاب کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                            ]
                                        )->label(false); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    بستن
                                </button>
                                <button type="submit" class="btn btn-primary">پرداخت مبلغ</button>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<?php
if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'emp')
{
?>
    <div class="modal fade" id="back" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">نیاز به اصلاح دوره</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['dashboard/back_package'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="courses">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($courseDetail, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل رد دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت اصلاح دوره</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="reject" tabindex="-1" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title secondary-font" id="modalCenterTitle">رد کردن دوره</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin(
                        [
                            'action' => ['dashboard/reject_package'],
                            "method" => "post",
                        ]
                    ); ?>
                    <?= $form->field($courseDetail, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="courses">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($courseDetail, 'rejection_reason')->textarea(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا دلیل رد دوره را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        بستن
                    </button>
                    <button type="submit" class="btn btn-primary">ثبت رد دوره</button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>


<div class="modal fade" id="show-course-scores" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="body"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="show-finance-info" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font finance_title" id="modalCenterTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="finance_body" class="modal-body"></div>
        </div>
    </div>
</div>