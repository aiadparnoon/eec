<?php

namespace app\components;

use Yii;

/**
 * لایه‌ی سراسری ورودی (قبل از هر درخواست، در frontend/config/main.php ثبت شده است).
 *
 *  ۱. NoSQL Injection: هر کلیدی که با $ شروع شود یا نقطه داشته باشد (مثل Users[username][$ne]=x
 *     یا ['college.$where' => ...]) از GET و POST حذف می‌شود؛ هیچ فرمی در پروژه چنین کلیدی ندارد
 *     و این تنها راه تزریق عملگر مونگو از طریق پارامترهای درخواست است.
 *  ۲. اعداد: ارقام فارسی/عربی در فیلدهای عددی (قیمت، مبلغ، موبایل، نام کاربری، کد ملی، تاریخ، …)
 *     پیش از رسیدن به کنترلر به ارقام انگلیسی تبدیل می‌شوند تا هیچ عدد فارسی در دیتابیس ذخیره نشود.
 *     رمز عبور و متن‌های آزاد (عنوان، توضیحات) دست نمی‌خورند.
 */
class RequestGuard
{
    /** نام فیلدهایی که همیشه عددی/کدی هستند (آخرین کلید مسیر؛ بدون حساسیت به حروف) */
    const NUMERIC_KEYS = '/(price|amount|prepayment|installment|duration|number|mobile|phone|tel|username|national|^id$|capacity|serial|sub_service|share|percent|deadline|^from$|^to$|^time$|date|zip|postal|shsh|count|code|license|sheba|iban|card|account|credit|score|grade|hours|^page$|per-page|year|month|day|economic|registration_id|student_id)/i';

    /** هرگز تبدیل نمی‌شوند (ممکن است کاربر عمداً رقم فارسی در رمز گذاشته باشد) */
    const SKIP_KEYS = '/(password|pass|token|_csrf|description|title|text|content|message|body|address)/i';

    public static function handle()
    {
        $request = Yii::$app->request;
        if (!$request instanceof \yii\web\Request)
            return;

        $get = self::clean($request->getQueryParams());
        $request->setQueryParams($get);
        $_GET = $get;

        if ($request->isPost || $request->isPut || $request->isPatch) {
            $body = $request->getBodyParams();
            if (is_array($body)) {
                $body = self::clean($body);
                $request->setBodyParams($body);
                $_POST = $body;
            }
        }
    }

    /**
     * @param mixed $data
     * @param string|null $key نام کلید والد برای تشخیص فیلد عددی
     * @return mixed
     */
    public static function clean($data, $key = null)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $k => $value) {
                if (is_string($k) && ($k === '' || $k[0] === '$' || strpos($k, '.') !== false))
                    continue; // عملگر/مسیر مونگو
                // کلید عددی (ردیف آرایه) نام فیلد والد را به ارث می‌برد
                $result[$k] = self::clean($value, is_int($k) ? $key : (string) $k);
            }
            return $result;
        }
        if (!is_string($data) || $key === null)
            return $data;
        if (!mb_check_encoding($data, 'UTF-8'))
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        if (preg_match(self::NUMERIC_KEYS, $key) && !preg_match(self::SKIP_KEYS, $key))
            $data = self::latinDigits($data);
        return $data;
    }

    public static function latinDigits($value)
    {
        return strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
