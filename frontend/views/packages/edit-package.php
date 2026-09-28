<?php
$this->title = 'مدیریت دوره های جامع و یکساله';
use frontend\controllers\DashboardController;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\assets\Select2Asset;
use frontend\assets\SingleAsset;
use app\models\Courses;
use app\models\Discounts;
use app\models\CoursesFinancial;
use app\models\OrganizationPayments;
use frontend\controllers;
use yii\widgets\ListView;
use mihaildev\ckeditor\CKEditor;

$newLesson = new Courses();
SingleAsset::register($this);
Select2Asset::register($this);
$front = Yii::getAlias('@front');
$courseType = array(
    '3' => 'محتوا محور',
    '1' => 'غیر حضوری',
    '2' => 'نیمه حضوری',
    '4' => ' حضوری',
);
$capacityType = array(
    '1' => 'نامحدود',
    '2' => 'محدود',
    '3' => 'سازمانی'
);
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
    else if (Yii::$app->session->get('status') == '111')
        $script = <<< JS
    toastr.success("درخواست حذف دانشپذیر به دانشکده ارسال گردید", {
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
    else if (Yii::$app->session->get('status') == '21')
        $script = <<< JS
    toastr.error("خطایی در ارتباط با سیستم ادوبی رخ داده است، لطفا مجددا تلاش نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '22')
        $script = <<< JS
    toastr.error("تاریخ شروع باید کمتر یا برابر تاریخ اتمام کلاس باشد", {
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
    toastr.success("نمرات مورد نظر ثبت گردید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '25')
        $script = <<< JS
    toastr.success("لیست اساتید دوره بروز گردید", {
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
    else if (Yii::$app->session->get('status') == '31')
        $script = <<< JS
    toastr.success("درخواست افزایش اعتبار شما ثبت گردید هم اکنون می توانید آنرا پرداخت نمایید", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '32')
        $script = <<< JS
    toastr.error("مبلغ تعداد افراد اضافه شده بیشتر از کیف پول شما می باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    else if (Yii::$app->session->get('status') == '33')
        $script = <<< JS
    toastr.error("تاریخ پرداخت قسط نباید بیشتر از تاریخ اتمام دوره باشد", {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
    // تغییر ۶/۷ (2026-08-28): پیغام دقیقِ اعتبارسنجی اقساط (الزامی بودن، فقط
    // عدد انگلیسی، تاریخ، یا مجموع مبلغ نسبت به قیمت دوره) - از همون خطای
    // واقعی مدل Courses میاد، نه یک پیغام ثابت.
    else if (Yii::$app->session->get('status') == '35') {
        $installmentErrorMsg = Yii::$app->session->has('installmentError')
            ? Yii::$app->session->get('installmentError')
            : 'اطلاعات قسط معتبر نیست';
        $installmentErrorJs = json_encode($installmentErrorMsg, JSON_UNESCAPED_UNICODE);
        $script = <<< JS
    toastr.error({$installmentErrorJs}, {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
        Yii::$app->session->remove('installmentError');
    }
    // تغییر ۹/۱۰ (2026-08-28): پیغام دقیقِ اعتبارسنجی کد تخفیف (الزامی بودن،
    // فقط عدد انگلیسی، یا بیشتر از سهم کارگزار) - از همون خطای واقعی مدل
    // Discounts میاد.
    else if (Yii::$app->session->get('status') == '34') {
        $discountErrorMsg = Yii::$app->session->has('discountError')
            ? Yii::$app->session->get('discountError')
            : 'اطلاعات کد تخفیف معتبر نیست';
        $discountErrorJs = json_encode($discountErrorMsg, JSON_UNESCAPED_UNICODE);
        $script = <<< JS
    toastr.error({$discountErrorJs}, {
            positionClass: "toast-top-center",
            containerId: "toast-top-center",
            "closeButton": "true"
        });
JS;
        Yii::$app->session->remove('discountError');
    }
    $this->registerJs($script);
    Yii::$app->session->remove('status');
}



$tab = <<< JS
    const urlParams = new URLSearchParams(window.location.search);
    const tabValue = urlParams.get('tab');

    // فعال‌سازی تب اصلی
    if (tabValue) {
        $("#" + tabValue + " > button").click();
    } else {
        const isSearch = window.location.href?.includes('status') || window.location.href?.includes('page');
        $(isSearch ? ".course-tab:nth-child(4) > button" : ".course-tab:first > button").click();
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
// تاریخ اتمام حداقل یک روز بعد از شروع؛ همه‌ی فیلدهای انتخابی با جست‌وجو
\frontend\assets\DateRangeAsset::register($this);
\frontend\assets\SelectSearchAsset::register($this);
\frontend\assets\FormValidateAsset::register($this);
$this->registerJs("EecValidate.bind(document.getElementById('package-edit-form'), {ajax: true});", \yii\web\View::POS_END);
$this->registerJs("EecDateRange.bind(document.getElementById('start-date'), document.getElementById('end-date'));", \yii\web\View::POS_END);
$this->registerJs("$(window).on('load', function () { EecSelect.init(document.querySelector('.container-xxl')); });", \yii\web\View::POS_END);

$url = Yii::$app->urlManager->createAbsoluteUrl('packages/show_user_detail', 'https');
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


$type = array(
    '2' => 'دوره میان مدت (بین ۲۱ تا ۲۵۰ ساعت)',
    '3' => 'دوره بلند مدت (بین ۲۵۱ تا ۳۵۰ ساعت)',
);

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



$hiddenArchive = array(
    false => 'خیر' ,
    true => 'بله' ,
);

$input = <<< JS
    $(document).ready(function() {
    // تنظیم محدوده اولیه
    const initialMin = 21;
    const initialMax = 251;
    
    // تغییر محدوده بر اساس دراپ‌داون
    $('#course_type').change(function() {
        const selectedValue = $(this).val();
        
        // تنظیمات محدوده برای هر گزینه
        const ranges = {
            "2": { min: 21, max: 251 },
            "3": { min: 251, max: 350 }
        };

        // تنظیم محدوده جدید
        $('#duration').attr({
            'min': ranges[selectedValue].min,
            'max': ranges[selectedValue].max
        });

        // اعتبارسنجی مقدار فعلی
        const currentVal = parseInt($('#duration').val());
        if (currentVal < ranges[selectedValue].min) {
            $('#duration').val(ranges[selectedValue].min);
        } else if (currentVal > ranges[selectedValue].max) {
            $('#duration').val(ranges[selectedValue].max);
        }
    });
});
JS;
$this->registerJs($input);


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
        // console.log(res, 'res');
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
                    auth_key: "JqtOGraPXHLobZrMcln5l43cFty4tXm383fuy3cAYok"
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



$js = <<< JS
$(document).ready(function() {
    // نکته‌ی مهم (ریشه‌ی باگ «تاریخ اتمام دوره فعال نمی‌شه»): flatpickr (با
    // altInput:true) اینپوت اصلیِ تاریخ شروع/اتمام رو به type="hidden" تبدیل
    // می‌کنه و یک اینپوت نمایشیِ تازه (بدون id) درست بعدش اضافه می‌کنه. این
    // اتفاق روی این صفحه ممکنه *بعد* از اجرای همین $(document).ready بیفته.
    // نسخه‌ی قبلیِ این اسکریپت یک‌بار، همون لحظه‌ی اول، $('#from1 input')/
    // $('#to1 input') رو می‌خوند و در startDate/endDate ذخیره می‌کرد؛ وقتی
    // بعداً اینپوت نمایشیِ جدید ساخته می‌شد، اون متغیرهای قدیمی اصلاً ازش خبر
    // نداشتن (jQuery چنین رفرنسی رو خودکار به‌روز نمی‌کنه) و disabled/value
    // فقط روی اینپوت اصلیِ (حالا مخفیِ) قبلی اعمال می‌شد - نتیجه این بود که
    // از دید کاربر، فیلد «تاریخ اتمام دوره» هیچ‌وقت فعال به نظر نمی‌رسید، چون
    // خودِ اینپوت نمایشی که کاربر می‌بینه دست‌نخورده می‌موند.
    // راه‌حل: دیگه رفرنس ثابتی نگه نمی‌داریم؛ هر بار اینپوت‌های واقعیِ داخل
    // #from1/#to1 رو تازه می‌خونیم (startInputs/endInputs) و رویدادها هم -
    // دقیقاً مثل فیکس قبلیِ پیغام‌های خطا (oninvalid) در همین صفحه - روی
    // کانتینر ثابت #from1/#to1 به‌صورت delegated بسته می‌شن؛ این‌طوری فرقی
    // نمی‌کنه اینپوت اصلی مخفی شده باشه یا اینپوت نمایشیِ جدید جایگزینش شده
    // باشه. (2026-08-27)

    var deadlineDate = $('#deadline-date');

    function startInputs() { return $('#from1 input'); }
    function endInputs() { return $('#to1 input'); }

    // مقدار واقعی (dateFormat پیش‌فرض میلادی) همیشه روی اینپوت اصلی نگه
    // داشته می‌شه؛ چون flatpickr اینپوت نمایشیِ altInput رو همیشه *بعد* از
    // اینپوت اصلی اضافه می‌کنه، .first() همیشه دقیقاً همون اینپوت اصلی رو
    // برمی‌گردونه، چه هنوز hidden نشده باشه چه شده باشه.
    function currentStartVal() { return startInputs().first().val().trim(); }
    function currentEndVal() { return endInputs().first().val().trim(); }

    // توجه: برخلاف فرم «ثبت دوره»، اینجا (ویرایش) تاریخ شروع/اتمام از قبل
    // مقدار دارن، پس مثل قبل هیچ غیرفعال‌سازیِ اولیه‌ای روی بار اول صفحه
    // انجام نمی‌دیم (دقیقاً همون رفتار قبلی که این دو خط کامنت بودن حفظ شده).
    // endInputs().prop('disabled', true);
    // deadlineDate.prop('disabled', true);

    // تابع برای اعتبارسنجی تاریخ اتمام
    function validateEndDate() {
        var startVal = currentStartVal();
        var endVal = currentEndVal();

        // اگر تاریخ شروع وجود ندارد
        if (!startVal) {
            endInputs().prop('disabled', true);
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
            if (!endInputs().hasClass('error-shown')) {
                endInputs().addClass('error-shown');
            }
            endInputs().val('');
            deadlineDate.val('');
            deadlineDate.prop('disabled', true);
            return false;
        } else {
            // اگر تاریخ درست بود، کلاس خطا را حذف کن
            endInputs().removeClass('error-shown');

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

    // رویداد تغییر تاریخ شروع - delegated روی کانتینر ثابت #from1 (نه خودِ
    // اینپوت‌های داخلش)، تا چه اینپوت اصلیِ مخفی‌شده تغییر کنه چه اینپوت
    // نمایشیِ altInput، درست کار کنه.
    $('#from1').on('change input', 'input', function() {
        var startVal = currentStartVal();

        if (startVal) {
            endInputs().prop('disabled', false);
            endInputs().val('');
            deadlineDate.val('');
            deadlineDate.prop('disabled', true);
        } else {
            endInputs().prop('disabled', true);
            deadlineDate.prop('disabled', true);
            endInputs().val('');
            deadlineDate.val('');
        }

        // اگر تاریخ اتمام پر شده بود، دوباره اعتبارسنجی کن
        if (currentEndVal()) {
            validateEndDate();
        }
    });

    // چند رویداد برای تاریخ اتمام - این‌ها هم delegated روی #to1 هستن
    $('#to1').on('change', 'input', validateEndDate);

    // رویداد blur (وقتی از فیلد خارج می‌شود)
    $('#to1').on('blur', 'input', function() {
        if ($(this).val().trim()) {
            validateEndDate();
        }
    });

    // رویداد input (تایپ لحظه‌ای - اختیاری)
    $('#to1').on('input', 'input', function() {
        // فقط وقتی مقدار کامل به نظر می‌رسد اعتبارسنجی کن
        if (currentEndVal().length >= 8) { // حداقل طول یک تاریخ
            validateEndDate();
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

// ===== قیمت با تخفیف نباید بیشتر از قیمت اصلی باشد =====
// این فقط یک هشدار زودهنگام سمت مرورگره (تجربه‌ی کاربری بهتر)؛ منبع واقعی و
// تضمین‌شده همون قانون سمت سرور Courses::validateDiscountNotAbovePrice()
// هست که مساوی بودن (یعنی «بدون تخفیف») رو مجاز می‌دونه و فقط بیشتر بودن
// واقعی رو رد می‌کنه - این اسکریپت هم دقیقاً همون منطق رو پیاده می‌کنه (عیناً
// همون چیزی که در فرم «ثبت دوره» هم اضافه شده). (2026-08-27)
$discountCheck = <<<JS
$(document).ready(function() {
    var priceInput = document.getElementById('courses-price');
    var discountInput = document.getElementById('courses-discount_price');
    if (!priceInput || !discountInput) {
        return;
    }

    function checkDiscount() {
        var price = parseFloat(priceInput.value);
        var discount = parseFloat(discountInput.value);
        if (discountInput.value.trim() === '' || isNaN(discount) || isNaN(price)) {
            discountInput.setCustomValidity('');
            return;
        }
        if (discount > price) {
            discountInput.setCustomValidity('قیمت با تخفیف نمی‌تواند بیشتر از قیمت اصلی باشد');
        } else {
            discountInput.setCustomValidity('');
        }
    }

    priceInput.addEventListener('input', checkDiscount);
    priceInput.addEventListener('change', checkDiscount);
    discountInput.addEventListener('input', checkDiscount);
    discountInput.addEventListener('change', checkDiscount);
    discountInput.addEventListener('blur', function() {
        checkDiscount();
        if (!discountInput.validity.valid) {
            discountInput.reportValidity();
        }
    });
});
JS;
$this->registerJs($discountCheck);

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
                    <a href="javascript:void(0);">دوره های جامع و یکساله</a>
                </li>
                <li class="breadcrumb-item active">ویرایش دوره (<?= $model->title['main_fa'] ?>)</li>
            </ol>
            <span class="badge bg-label-dark h2">
                <?php
                if(Yii::$app->user->identity->role == 'user')
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl(['packages','CoursesSearch[college]' => $model->college ,'CoursesSearch[title][main_fa]' => '','CoursesSearch[license_code]' => '','CoursesSearch[status]' => '']).'">بازگشت</a>';
                else
                    echo '<a class="h6" href="'.Yii::$app->urlManager->createAbsoluteUrl('packages').'">بازگشت</a>';
                ?>
            </span>
        </div>
    </nav>
    <?php // نمودار مشترک مراحل دوره (همان طرح قبلی میان‌مدت؛ برای دوره‌های کوتاه‌مدت هم استفاده می‌شود) ?>
    <?= $this->render('@frontend/views/courses/_process', ['course' => $model]) ?>
    <div class="card text-center mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item course-tab" role="presentation" id="tab-id0">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id0" aria-controls="id0" aria-selected="false" tabindex="-1">
                        مشخصات دوره
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id3">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id3" aria-controls="id3" aria-selected="false" tabindex="-1">
                        اقساط
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id1">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id1" aria-controls="id1" aria-selected="true">
                        دروس دوره
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id2">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id2" aria-controls="id2" aria-selected="true">
                        اعضا
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id4">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id4" aria-controls="id4" aria-selected="true">
                        اساتید
                    </button>
                </li>
                <li class="nav-item course-tab" role="presentation" id="tab-id5">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#id5" aria-controls="id5" aria-selected="true">
                        کدهای تخفیف
                    </button>
                </li>
            </ul>
            <?php
            if (Yii::$app->user->identity->role == 'user' && $model->status != '7')
            {
            ?>
                <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                    <?php
                    if ($model->status != '1') {
                        $form = ActiveForm::begin(
                            [
                                'action' => ['dashboard/confirm_package'],
                                "method" => "post",
                            ]
                        );
                    ?>
                        <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                        <input type="hidden" name="page" value="packages">
                        <button type="submit" class="btn btn-success">تائید دوره</button>
                    <?php
                        ActiveForm::end();
                    } else
                        echo '<button type="button" disabled class="btn btn-label-dark">تائید دوره</button>';
                    ?>
                    <?php
                    if ($model->status != '4')
                        echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">نیاز به اصلاح</button>';
                    if ($model->status != '5')
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">رد کرد</button>';
                    ?>
                </div>
            <?php
            }
            else if ($model->status == '3' || $model->status == '4')
            {
                $form = ActiveForm::begin(
                    [
                        'action' => ['dashboard/send_course_to_admin'],
                        "method" => "post",
                    ]
                );
            ?>
                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                <input type="hidden" name="page" value="packages">
                <button type="submit" class="btn btn-success">ارسال برای مجوز</button>
            <?php
                ActiveForm::end();
            }
            ?>
            <?php
            if (Yii::$app->user->identity->role == 'emp' && ($model->status == '7' || $model->status == '8' || $model->status == '9') && Yii::$app->user->identity->college != '65afa2ea5136ec5b5b0c4064')
            {
                ?>
                <div class="btn-group demo-inline-spacing" role="group" aria-label="Basic example">
                    <?php
                    if ($model->status != '1') {
                        $form = ActiveForm::begin(
                            [
                                'action' => ['dashboard/confirm_package_from_college'],
                                "method" => "post",
                            ]
                        );
                        ?>
                        <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                        <input type="hidden" name="page" value="packages">
                        <button type="submit" class="btn btn-success">تائید دوره</button>
                        <?php
                        ActiveForm::end();
                    } else
                        echo '<button type="button" disabled class="btn btn-label-dark">تائید دوره</button>';
                    ?>
                    <?php
                    if ($model->status != '8')
                        echo ' <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#back">نیاز به اصلاح</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">نیاز به اصلاح</button>';
                    if ($model->status != '9')
                        echo '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reject">رد کرد</button>';
                    else
                        echo '<button type="button" disabled class="btn btn-label-dark">رد کرد</button>';
                    ?>
                </div>
                <?php
            }
//            else if ($model->status == '3' || $model->status == '4')
//            {
//                $form = ActiveForm::begin(
//                    [
//                        'action' => ['dashboard/send_course_to_admin'],
//                        "method" => "post",
//                    ]
//                );
//                ?>
<!--                --><?php //= $form->field($model, '_id')->hiddenInput()->label(false); ?>
<!--                <input type="hidden" name="page" value="packages">-->
<!--                <button type="submit" class="btn btn-success">ارسال برای مجوز</button>-->
<!--                --><?php
//                ActiveForm::end();
//            }
            ?>
        </div>

        <div class="tab-content shadow-none">
            <div class="tab-pane fade" id="id0" role="tabpanel">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-12 pe-0 mb-md-0 mb-2">
                            <input readonly class="form-control disabled" id="clipboard-example" type="text" value="<?= (string) $model->_id ?>">
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
                    <div class="modal-body">
                        <?php $form = ActiveForm::begin(
                            [
                                'id' => 'package-edit-form',
                                'action' => ['edit'],
                                "method" => "post",
                                'enableClientScript' => false,
                                'options' => ['enctype' => 'multipart/form-data'],
                            ]
                        ); ?>
                        <div class="row">
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان اصلی فارسی *</label>
                                <?php
                                if($allowEdit)
                                    echo $form->field($model, 'title[main_fa]')->textInput(
                                        [
                                            'class' => 'form-control text-start',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی فارسی را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$model->title['main_fa'].'">';
                                ?>
                                <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان اصلی انگلیسی </label>
                                <?php
                                if($allowEdit)
                                   echo $form->field($model, 'title[main_en]')->textInput(
                                        [
                                            'class' => 'form-control text-start',
//                                            'required' => true,
//                                            'readonly' => true,
//                                            'disabled' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان اصلی انگلیسی را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$model->title['main_en'].'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان فارسی (داخل مدرک) *</label>
                                <?php
                                if($allowEdit)
                                   echo $form->field($model, 'title[degree_fa]')->textInput(
                                        [
                                            'class' => 'form-control text-start',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان فارسی را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$model->title['degree_fa'].'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">عنوان انگلیسی (داخل مدرک) </label>
                                <?php
                                if($allowEdit)
                                   echo $form->field($model, 'title[degree_en]')->textInput(
                                        [
                                            'class' => 'form-control text-start',
//                                            'required' => true,
//                                            'readonly' => true,
//                                            'disabled' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا عنوان انگلیسی را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$model->title['degree_en'].'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">قیمت اصلی (تومان) *</label>
                                <?php
                                if($allowEdit)
                                    echo $form->field($model, 'price')->textInput(
                                        [
                                            'class' => 'form-control text-start only-english-digits',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت اصلی دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
//                                            'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.number_format($model->price).'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">قیمت با تخفیف (تومان) *</label>
                                <?php
                                // بند ۵.۲ صورتجلسه: تخفیف شهریه فقط توسط مدیر سیستم
                                if($allowEdit && \app\components\CourseAccess::canSetDiscount())
                                    echo $form->field($model, 'discount_price')->textInput(
                                        [
                                            'class' => 'form-control text-start only-english-digits',
                                            'required' => true,
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا قیمت با تخفیف دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
//                                            'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)"
                                        ]
                                    )->label(false);
                                else
                                {
                                    $discountAmount = null;
                                    if(is_numeric($model->discount_price))
                                        $discountAmount = $model->discount_price;
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.number_format($discountAmount).'">';
                                }
                                ?>
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
                                <?php
                                if($allowEdit)
                                    echo $form->field($model, 'content_type')->dropDownList(
                                    $courseType,
                                    [
                                        'class' => 'form-select',
                                        'id' => 'course-type',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        // «محتوا محور» دیگه برای انتخاب/تغییر جدید مجاز نیست؛ ولی چون
                                        // $courseType همینجا برای نمایش دوره‌های قدیمی هم استفاده می‌شه
                                        // (شاخه‌ی readonly زیر همین بلوک)، به‌جای حذف از لیست فقط همین
                                        // گزینه رو غیرفعال می‌کنیم - مگر اینکه مقدار فعلیِ همین دوره دقیقاً
                                        // همون باشه، تا بشه دوره رو بدون تغییر این فیلد ذخیره کرد. سمت
                                        // سرور هم توسط Courses::validateContentTypeNotDisabled() همین
                                        // قانون اجرا می‌شه. (2026-08-27)
                                        'options' => [
                                            Courses::CONTENT_TYPE_CONTENT_BASED => [
                                                'disabled' => $model->content_type !== Courses::CONTENT_TYPE_CONTENT_BASED,
                                            ],
                                        ],
                                    ]
                                )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$courseType[$model->content_type].'">';
                                ?>
                            </div>
                            <div class="col-2 col-md-2 col-sm-12 dol-lg-2 col-xl-2 mb-3">
                                <label for="nameWithTitle" class="form-label">نوع ظرفیت *</label>
                                <?php
                                // مقدار فعلیِ نوع ظرفیت همین دوره (اگه اصلاً ثبت شده باشه) - برای
                                // تشخیص اینکه آیا گزینه‌ی «نامحدود» باید غیرفعال بشه یا نه.
                                $currentCapacityType = ($model->student_capacity !== null && isset($model->student_capacity['type']))
                                    ? $model->student_capacity['type']
                                    : null;
                                echo $form->field($model, 'student_capacity[type]')->dropDownList(
                                    $capacityType,
                                    [
                                        'class' => 'form-select capacity_type',
                                        'required' => true,
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا نوع ظرفیت دوره را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        // گزینه‌ی «نامحدود» دیگه برای انتخاب/تغییر جدید مجاز نیست؛ فقط
                                        // اگه مقدار فعلیِ همین دوره دقیقاً همینه غیرفعال نمی‌شه، تا بشه
                                        // دوره رو بدون تغییر این فیلد ذخیره کرد. سمت سرور هم توسط
                                        // Courses::validateCapacityTypeNotDisabled() همین قانون اجرا
                                        // می‌شه. (2026-08-27)
                                        'options' => [
                                            Courses::CAPACITY_TYPE_UNLIMITED => [
                                                'disabled' => $currentCapacityType !== Courses::CAPACITY_TYPE_UNLIMITED,
                                            ],
                                        ],
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
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-1 col-md-1 col-sm-12 dol-lg-1 col-xl-1 mb-3" id="capacity1">
                                <?php
                                if ($model->student_capacity != null)
                                {
                                    if($model->student_capacity['type'] == '2')
                                    {
                                        echo '<label for="nameWithTitle" class="form-label">ظرفیت *</label>';
                                        // تغییر ۱ (2026-08-28): همون اصلاح پیغام انگلیسی مرورگر که
                                        // توی actionCapacity() (نسخه‌ی AJAX همین فیلد) هم اعمال شده.
                                        echo $form->field($model, 'student_capacity[number]')->textInput(
                                            [

                                                'class' => 'form-control numeral-mask text-start',
                                                'required' => true,
                                                'type' => 'number',
                                                'oninvalid' => 'this.setCustomValidity(\'لطفا ظرفیت را به صورت عددی وارد کنید\')',
                                                'oninput' => 'setCustomValidity(\'\')',
                                            ]
                                        )->label(false);
                                    }
                                }
                                ?>
                            </div>
                            <?php
                            $contractFileStyle = 'display:none;';
                            $contractFileLabel = 'فایل قرارداد *';
                            if($model->student_capacity['type'] == '3' && $model->contract_file != null)
                            {
                                $contractFileStyle = '';
                                $contractFileLabel = 'فایل قرارداد';
                            }
                            ?>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="contract_file_div" style="<?= $contractFileStyle ?>">
                                <label for="nameWithTitle" class="form-label"><?= $contractFileLabel ?></label>
                                <?= $form->field($model, 'contract_file')->fileInput(
                                    [
                                        'class' => 'form-control text-start',
                                        'id' => 'contract_file',
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا فایل قرارداد را وارد کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                    ]
                                )->label(false); ?>
                                <?php
                                if($model->student_capacity['type'] == '3' && $model->contract_file != null)
                                {
                                    ?>
                                    <div id="defaultFormControlHelp" class="form-text">
                                        <a download="true" href="<?= Yii::$app->urlManager->createUrl(['packages/contract_file','filename' => $model->contract_file])  ?>">دانلود فایل قرارداد</a>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
<!--                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">-->
<!--                                <label for="nameWithTitle" class="form-label">دسته بندی دوره *</label>-->
<!--                                --><?php
//                                if($allowEdit)
//                                    echo $form->field($model, 'type')->dropDownList(
//                                        $type,
//                                        [
//                                            'class' => 'form-select',
//                                            'required' => true,
//                                            'oninvalid' => 'this.setCustomValidity(\'لطفا دسته بندی دوره را مشخص کنید\')',
//                                            'oninput' => 'setCustomValidity(\'\')',
//                                        ]
//                                    )->label(false);
//                                else
//                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$type[$model->type].'">';
//                                ?>
<!--                            </div>-->
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                <label for="nameWithTitle" class="form-label">مدت زمان دوره (ساعت) *</label>
                                <?php
                                if($allowEdit)
                                    echo $form->field($model, 'duration')->textInput(
                                        [
                                            'class' => 'form-control text-start',
                                            'required' => true,
//                                            'id' => 'duration',
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان دوره را به درستی وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                            'type' => 'number'
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" class="form-control text-start" disabled readonly value="'.$model->duration.'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from1">
                                <label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>
                                <?php
                                // پس از ثبت، تاریخ‌ها را فقط مدیر سیستم تغییر می‌دهد (سمت سرور هم اعمال می‌شود)
                                if($allowEdit && \app\components\CourseAccess::isAdmin())
                                    echo $form->field($model, 'date[from]')->textInput(
                                        [
                                            'class' => 'form-control dob-picker text-start',
                                            'required' => true,
                                            'id' => 'start-date',
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" disabled readonly class="form-control text-start" value="'.\yii\helpers\Html::encode($model->date['from'] ?? '').'">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to1">
                                <label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>
                                <?php
                                if($allowEdit && \app\components\CourseAccess::isAdmin())
                                    echo $form->field($model, 'date[to]')->textInput(
                                        [
                                            'class' => 'form-control dob-picker text-start',
                                            'required' => true,
                                            'id' => 'end-date',
                                            'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                                            'oninput' => 'setCustomValidity(\'\')',
                                        ]
                                    )->label(false);
                                else
                                    echo '<input type="text" disabled readonly class="form-control text-start" value="'.\yii\helpers\Html::encode($model->date['to'] ?? '').'">';
                                ?>
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
                            <?php
                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->additional_access === true)
                            {
                                ?>
                                <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3">
                                    <div class="form-check mt-3">
                                        <?php
                                        if($allowEdit) {
                                            echo $form->field($model, 'allow_free_add_user')->checkbox([
                                                'class' => 'form-check-input',
                                                'label' => 'افزودن عضو بدون کیف پول',
                                                'labelOptions' => ['class' => 'form-check-label']
                                            ])->label(false);
                                        } else {
                                            $checked = $model->allow_free_add_user ? 'checked' : '';
                                            echo '<input type="checkbox" class="form-check-input" disabled ' . $checked . '>';
                                            echo '<label class="form-check-label">افزودن عضو بدون کیف پول</label>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                            <hr class="mt-2">
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
                                <label for="select2Basic" class="form-label">واحد *</label>
                                <?php
                                // انتخاب واحد فقط با مدیر سیستم؛ کارشناس واحد و کارگزار واحد خودشان را می‌بینند
                                if (!\app\components\CourseAccess::canChooseUnit() || !$allowEdit)
                                    echo '<input type="text" class="form-control" disabled value="' . \yii\helpers\Html::encode(\app\components\UsersDirectory::collegeTitles()[(string) $model->college] ?? '-') . '">'
                                        . \yii\helpers\Html::hiddenInput('Courses[college]', (string) $model->college);
                                else
                                echo $form->field($model, 'college')->dropDownList(
                                    $colleges,
                                    [
                                        'prompt' => 'لطفا دانشکده را مشخص کنید',
                                        'class' => 'select2 form-select form-select-lg',
                                        'required' => true,
                                        // اصلاح ۲۰۲۶-۰۸-۲۸: عیناً همون فیکسِ ایجادِ دوره (create-package.php)
                                        // برای پیغام انگلیسی پیش‌فرض مرورگر، اینجا (ویرایش) هم اعمال شد.
                                        'oninvalid' => 'this.setCustomValidity(\'لطفا دانشکده را مشخص کنید\')',
                                        'oninput' => 'setCustomValidity(\'\')',
                                        'id' => '',
                                        'data-allow-clear' => true,
                                        'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/packages/brokers1') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                        var main_data=JSON.parse(data);
                                                                                           $(\'#broker1\').html(main_data.brokers);
                                                                                           $(\'#teachers1\').html(main_data.teachers);
                                                                                           $(\'#lessons1\').html(main_data.lessons);
                                                                                        }
                                                                                    );'
                                    ]
                                )->label(false);
                                ?>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">کارگزار</label>
                                <div id="broker1">
                                    <?php
                                    $myBrokers = $this->context->my_brokers($model->college);
                                    $allCollegeBrokers = $this->context->all_college_brokers($model->college);
                                    if ($myBrokers != null)
                                    {
                                        $myBrokers = ArrayHelper::map($myBrokers, function ($model) {
                                            return (string) $model->_id;
                                        }, function ($model) {
                                            $type = 'حقیقی';
                                            if ($model->type == '1')
                                                $type = 'حقوقی - شرکت ' . $model->company_info['company_title'];
                                            return $model->connector_info['first_name'] . ' ' . $model->connector_info['last_name'] . '(' . $type . ')';
                                        });
                                        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                        {
                                            echo $form->field($model, 'broker[_id]')->dropDownList(
                                                $myBrokers,
                                                [
                                                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                                                    'class' => 'select2s form-select',
                                                    'id' => '',
                                                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/courses/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts1\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                                                ]
                                            )->label(false);
                                        }
                                        else
                                        {
                                            if($model->broker == null)
                                                echo '-';
                                            else
                                            {
                                                if($model->broker['_id'] != null && $model->broker['_id'] != '')
                                                    echo $allCollegeBrokers[$model->broker['_id']];
                                                else
                                                    echo '-';
                                            }
                                        }
                                    }

                                    ?>
                                </div>
                            </div>
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label for="nameWithTitle" class="form-label">نوع قرارداد کارگزار</label>
                                <div id="broker_contracts1">
                                    <?php
                                    if ($model->broker != null) {
                                        $myBrokerContracts = $this->context->my_broker_contract($model->broker['_id']);
                                        if ($myBrokerContracts != null)
                                        {
                                            $myBrokerContracts = ArrayHelper::map($myBrokerContracts, 'id', function ($model) {
                                                return $model['title'] . ' (' . $model['share'] . ' درصد)';
                                            });
                                            if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                                                echo $form->field($model, 'broker[contract]')->dropDownList(
                                                    $myBrokerContracts,
                                                    [
                                                        'prompt' => 'لطفا قرارداد کارگزار را انتخاب کنید',
                                                        'class' => 'select2s form-select',
                                                        'id' => '',
                                                        'required' => true,
                                                        // اصلاح ۲۰۲۶-۰۸-۲۸: بدون این، پیغام پیش‌فرض مرورگر انگلیسی بود.
                                                        'oninvalid' => 'this.setCustomValidity(\'لطفا قرارداد کارگزار را انتخاب کنید\')',
                                                        'oninput' => 'setCustomValidity(\'\')',
                                                    ]
                                                )->label(false);
                                            else if(array_key_exists('contract', $model->broker))
                                                echo $myBrokerContracts[$model->broker['contract']];
                                        }
                                    } else {
                                    ?>
                                        <span class="badge bg-label-warning">در انتظار انتخاب کارگزار</span>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <hr class="mb-2">
                            <div class="col-4 col-md-4 col-sm-12 dol-lg-4 col-xl-4 mb-3">
                                <label class="form-label" for="edit-server">سرور برگزاری کلاس *</label>
                                <?php
                                // دوره‌های قدیمی بدون فیلد: سرور پیش‌فرض اگر کلاس آنلاین دارد، وگرنه «هیچ‌کدام»
                                $currentServer = (string) $model->classroom_server;
                                if ($currentServer === '') {
                                    $effective = \app\components\classroom\ClassroomPlatforms::hasOnlineClass($model) ? \app\components\classroom\ClassroomPlatforms::serverFor($model) : null;
                                    $currentServer = $effective !== null ? (string) $effective->_id : \app\models\ClassroomServers::NONE;
                                }
                                $serverOptions = $servers;
                                if ($currentServer !== \app\models\ClassroomServers::NONE && !isset($serverOptions[$currentServer])) {
                                    $inactive = \app\models\ClassroomServers::findById($currentServer);
                                    if ($inactive !== null)
                                        $serverOptions[$currentServer] = $inactive->title . ' (غیرفعال)';
                                }
                                $serverOptions[\app\models\ClassroomServers::NONE] = 'هیچ‌کدام (برگزاری در سامانه‌ی دیگر / بدون کلاس آنلاین)';
                                if ($allowEdit)
                                    echo \yii\helpers\Html::dropDownList('Courses[classroom_server]', $currentServer, $serverOptions, ['id' => 'edit-server', 'class' => 'form-select', 'required' => true, 'prompt' => 'انتخاب سرور']);
                                else
                                    echo '<input type="text" class="form-control" disabled value="' . \yii\helpers\Html::encode($serverOptions[$currentServer] ?? '-') . '">';
                                ?>
                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="from">

                            </div>
                            <div class="col-3 col-md-3 col-sm-12 dol-lg-3 col-xl-3 mb-3" id="to">

                            </div>

                            <div class="card mb-4 relative">
                                <div class="card-body">
                                    <div class="dropzone needsclick dz-clickable drop-zone" id="dropzone-<?= rand() ?>">
                                        <img src="<?= $front . '/package_images/' . $model->preview_image ?>" class="upload-preview img-fluid">
                                        <div class="dz-message needsclick">
                                            <?= $form->field($model, 'preview_image')->fileInput(
                                                [
                                                    'class' => 'form-control text-start drop-file',
                                                ]
                                            )->label(false); ?>
                                            <span class="drop-title"></span>
                                            <span class="note needsclick">تصویر پیش نمایش دوره</span>
                                            <div class="alert alert-danger" role="alert">* نکته مهم: تصویر انتخاب شده نباید بیشتر از <?= Yii::getAlias('@uploadSize') / 1000 ?> کیلوبایت باشد</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <?php
                        if($model->status == '6' && Yii::$app->user->identity->role == 'emp')
                        {

                        }
                        else
                            echo '<button type="submit" form="package-edit-form" class="btn btn-primary">ویرایش مشخصات دوره</button>';
                        ?>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="id3" role="tabpanel">
                <?= $this->render('_installments', ['model' => $model, 'allowEdit' => $allowEdit]) ?>
            </div>
            <div class="tab-pane fade" id="id1" role="tabpanel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"></h5>
                    <?php
                    if ($model->status != '1' || $model->status == '3' || $model->status == '4' || Yii::$app->user->identity->role == 'user') {
                    ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCenter">
                            <span class="tf-icons fa-solid fa-plus fa-fw me-1"></span>افزودن درس جدید
                        </button>
                    <?php
                    }
                    ?>
                </div>
                <div class="row">
                    <?php
                    if ($model->lessons != null) {
                        $counter = 0;
                        foreach ($model->lessons as $lesson) {
                            $lessonDetail = $this->context->lesson_detail($lesson['_id']);
                    ?>
                            <div class="col-4 col-md-4 col-sm-4 dol-lg-4 col-xl-4 mb-3">
                                <?php
                                $form = ActiveForm::begin(
                                    [
                                        'action' => ['edit_course_in_package'],
                                        "method" => "post",
                                    ]
                                );
                                echo $form->field($model, '_id')->hiddenInput()->label(false)
                                ?>
                                <input type="hidden" name="row" value="<?= $counter ?>">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <?= $lessonDetail->title ?>
                                        <div class="avatar avatar-md me-2">
                                            <img src="<?= $front . '/lesson_images/' . $lessonDetail->imagePreview ?>" alt="آواتار" class="rounded-circle">
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label" for="basic-default-fullname">استاد درس *</label>
                                            <?= $form->field($model, 'lessons[' . $counter . '][teachers]')->dropDownList(
                                                ArrayHelper::map($teachers, function ($model) {
                                                    return (string) $model->_id;
                                                }, function ($model) {
                                                    return $model->first_name . ' ' . $model->last_name;
                                                }),
                                                [
                                                    'prompt' => 'لطفا استاد درس را انتخاب کنید',
                                                    'class' => 'select2 form-select',
                                                    'id' => ''
                                                    //                            'multiple' => true
                                                ]
                                            )->label(false) ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">تاریخ شروع *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][from]')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][_id]')->hiddenInput(
                                                [
                                                    'value' => (string) $lessonDetail->_id
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">تاریخ اتمام *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][to]')->textInput(
                                                [
                                                    'class' => 'form-control dob-picker text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">ساعت شروع *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][time]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">مدت زمان (ساعت) *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][date][duration]')->textInput(
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان درس را وارد کنید\')',
                                                    'oninput' => 'setCustomValidity(\'\')',
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">مخفی کردن آرشیو *</label>
                                            <?=
                                            $form->field($model, 'lessons[' . $counter . '][hide_archive]')->dropDownList(
                                                    $hiddenArchive,
                                                [
                                                    'class' => 'form-control text-start',
                                                    'required' => true,
                                                    'id' => '',
                                                ]
                                            )->label(false)
                                            ?>
                                        </div>
                                        <div class="pt-4">
                                            <button name="edit" type="submit" class="btn btn-warning me-sm-3 me-1">ویرایش درس</button>
                                            <?php
                                            if ($model->status != '1' || $model->status == '3' || $model->status == '4' || Yii::$app->user->identity->role == 'user')
                                                echo '<button name="delete" type="submit" class="btn btn-danger">حذف درس</button>';
                                            ?>
                                            <?php ActiveForm::end(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                            $counter++;
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="tab-pane fade text-start" id="id2" role="tabpanel">
                <?= $this->render('@frontend/views/course-members/_toolbar', ['model' => $searchModel, 'course' => $model, 'stats' => $memberStats]) ?>
                <?= $this->render('_member_search', ['packageDetail' => $model]) ?>
                <p class="mb-2"><a href="<?= \yii\helpers\Url::to(['packages/recording-grades', '_id' => (string) $model->_id]) ?>"><i class="bx bx-edit me-1"></i>ثبت کلی نمرات</a></p>
                <?= $this->render('@frontend/views/course-members/_table', ['course' => $model, 'dataProvider' => $dataProvider]) ?>
            </div>
            <div class="tab-pane fade" id="id4" role="tabpanel">
                <?php
                $form = ActiveForm::begin(
                    [
                        'action' => ['packages/other_teachers'],
                        "method" => "post",
                    ]
                );
                ?>
                <div class="row">
                    <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                    <div class="col-8 col-md-8 col-lg-8 col-sm-12 mb-3">
                        <label class="form-label" for="basic-default-fullname">سایر اساتید *</label>
                        <?= $form->field($model, 'other_teachers')->dropDownList(
                            ArrayHelper::map($teachers, function ($model) {
                                return (string) $model->_id;
                            }, function ($model) {
                                return $model->first_name . ' ' . $model->last_name;
                            }),
                            [
                                'prompt' => 'لطفا استاد درس را انتخاب کنید',
                                'class' => 'select2 form-select',
                                'id' => 'inputGroupSelect04',
                                'multiple' => true,
                                'aria-label' => 'Example select with button addon',
                            ]
                        )->label(false) ?>
                    </div>
                    <div class="col-4 col-md-4 col-lg-4 col-sm-12 mb-3">
                        <button class="btn btn-success mt-4" type="submit">بروزرسانی اساتید</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="tab-pane fade" id="id5" role="tabpanel">
                <?php
                if ($discounts != null)
                {
                    ?>
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">کدهای تخفیف ثبت شده</h4>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                            افزودن کد تخفیف
                        </button>
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
                                        <a class="nav-item nav-link active" href="javascript:void(0)">کد تخفیف: <?= $discount->code ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">تعداد: <?= $discount->count ?></a>
                                        <a class="nav-item nav-link active" href="javascript:void(0)">تعداد استفاده شده: <?= $used ?></a>
                                    </div>
                                    <?php
                                    if($discount->used == null)
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
                                                'action' => ['delete_discount'],
                                                "method" => "post",
                                            ]
                                        ); ?>
                                        <?php echo $form->field($discount, '_id')->hiddenInput()->label(false); ?>
                                        <div class="row">
                                            آیا از حذف کد تخفیف با مبلغ <?= number_format($discount->amount) ?> تومان و کد <?= $discount->code  ?> مطمئن هستید؟
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
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_discount_code">
                        افزودن کد تخفیف
                    </button>
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
                                        'action' => ['add_discount'],
                                        "method" => "post",
                                    ]
                                ); ?>
                                <?= $form->field($discountModel, 'course_id')->hiddenInput(
                                    [
                                        'value' => (string) $model->_id,
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
//                                                'step' => 'any',
//                                                'min' => '0',
//                                                'pattern' => '[0-9]*\.?[0-9]*',
//                                                'inputmode' => 'decimal',
//                                                'onkeydown' => 'return event.keyCode !== 69 && event.keyCode !== 189 && event.keyCode !== 187',
//                                                'oninput' => 'this.value = this.value.replace(/[^0-9.]/g, "").replace(/(\..*)\./g, "$1")'
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
        </div>
    </div>
</div>


<div class="modal fade" id="modalCenter" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title secondary-font" id="modalCenterTitle">ثبت درس جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin(
                    [
                        'action' => ['add_lesson_to_package'],
                        "method" => "post",
                    ]
                ); ?>
                <input type="hidden" name="_id" value="<?= (string) $model->_id ?>">
                <div class="row">
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">عنوان درس *</label>
                        <?php
                        echo $form->field($newLesson, 'lessons[_id]')->dropDownList(
                            $remainingLessons,
                            [
                                'prompt' => 'لطفا درس را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="select2Basic" class="form-label">استاد *</label>
                        <?php
                        echo $form->field($newLesson, 'lessons[teachers]')->dropDownList(
                            ArrayHelper::map($teachers, function ($model) {
                                return (string) $model->_id;
                            }, function ($model) {
                                return $model->first_name . ' ' . $model->last_name;
                            }),
                            [
                                'prompt' => 'لطفا مدرس درس را مشخص کنید',
                                'class' => 'select2 form-select form-select-lg',
                                'required' => true,
                                'id' => '',
                                'data-allow-clear' => true,
                            ]
                        )->label(false);
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">تاریخ شروع درس * </label>
                        <?=
                        $form->field($newLesson, 'lessons[date][from]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">تاریخ اتمام درس * </label>
                        <?=
                        $form->field($newLesson, 'lessons[date][to]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">ساعت شروع دوره * </label>
                        <?= $form->field($model, 'lessons[date][time]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">
                        <label for="nameWithTitle" class="form-label">مدت زمان (ساعت) * </label>
                        <?= $form->field($model, 'lessons[date][duration]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false); ?>
                    </div>
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">مخفی کردن آرشیو * </label>
                        <?= $form->field($model, 'lessons[hide_archive]')->dropDownList(
                                $hiddenArchive,
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                            ]
                        )->label(false); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    بستن
                </button>
                <button type="submit" class="btn btn-primary">ثبت درس</button>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>



<?php
if ((Yii::$app->user->identity->role == 'user' && $model->status != '7') || (Yii::$app->user->identity->role == 'emp' && ($model->status == '7' || $model->status == '8' || $model->status == '9')))
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
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($model, 'rejection_reason')->textarea(
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
                    <?= $form->field($model, '_id')->hiddenInput()->label(false); ?>
                    <input type="hidden" name="page" value="packages">
                    <div class="col-12 col-md-12 col-sm-12 dol-lg-12 col-xl-12 mb-3">
                        <label for="nameWithTitle" class="form-label">دلیل رد دوره *</label>
                        <?= $form->field($model, 'rejection_reason')->textarea(
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





