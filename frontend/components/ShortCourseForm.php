<?php

namespace app\components;

use Yii;
use app\models\Brokers;
use app\models\ClassroomServers;
use app\models\Courses;
use app\models\Lessons;
use app\models\Teachers;
use app\models\UsersSearch;
use yii\helpers\HtmlPurifier;

/**
 * خواندن و اعتبارسنجی فرم ثبت/ویرایش دوره‌ی کوتاه‌مدت (سمت سرور).
 *
 * فقط فیلدهای مجاز از ورودی خوانده می‌شوند (بدون mass assignment): وضعیت، کد مجوز، ثبت‌کننده
 * و ... هرگز از فرم پذیرفته نمی‌شوند. قواعدی که قبلاً فقط در مرورگر بود (مدت ۸ تا ۲۴ ساعت،
 * تاریخ پایان بعد از شروع، مهلت ثبت عضو) اینجا هم اعمال می‌شوند.
 */
class ShortCourseForm
{
    const MIN_HOURS = 8;
    const MAX_HOURS = 24;
    const CONTENT_TYPES = ['1' => 'غیرحضوری', '2' => 'نیمه‌حضوری', '4' => 'حضوری'];

    /** @var string|null */
    public static $error;
    /** @var string|null نام فیلد فرم مربوط به خطا (مثل Courses[price]) برای نمایش پیام زیر همان فیلد */
    public static $errorField;

    /**
     * @param Courses $model
     * @param array $input $_POST['Courses']
     * @param bool $isNew
     * @return bool
     */
    public static function apply(Courses $model, $input, $isNew)
    {
        self::$error = self::$errorField = null;
        $input = is_array($input) ? $input : [];
        if (!static::applyCommon($model, $input, $isNew))
            return false;
        $unit = (string) $model->college;
        if (!self::applyBroker($model, $input, $unit))
            return false;
        if (!self::applyLesson($model, $input, $unit, $isNew))
            return false;
        if (!self::applyServer($model, $input))
            return false;
        return true;
    }

    /**
     * فیلدهای مشترک دوره‌ی کوتاه‌مدت و میان‌مدت: واحد، عنوان‌ها، شهریه و تخفیف، مدت، زمان و محل، نوع دوره،
     * ظرفیت، توضیحات و مجوز افزودن عضو بدون کیف پول.
     */
    protected static function applyCommon(Courses $model, array $input, $isNew)
    {
        // متن ساده: تگ HTML پذیرفته نمی‌شود (عنوان‌ها در سایت اصلی و گواهی هم نمایش داده می‌شوند)
        $str = function ($value, $max = 250) {
            return is_scalar($value) ? mb_substr(trim(strip_tags((string) $value)), 0, $max, 'UTF-8') : '';
        };
        $digits = function ($value) {
            return is_scalar($value) ? preg_replace('/\s|,/', '', UsersSearch::normalizeDigits((string) $value)) : '';
        };
        $isAdmin = CourseAccess::isAdmin();

        // واحد: فقط مدیر سیستم انتخاب می‌کند؛ کارشناس واحد/کارگزار با واحد خودش ثبت می‌کند
        // (اگر بیش از یک واحد داشته باشد، فقط یکی از واحدهای خودش). در ویرایش، غیرمدیر واحد را تغییر نمی‌دهد.
        $unit = isset($input['college']) && is_string($input['college']) ? $input['college'] : '';
        if ($isNew && !$isAdmin) {
            $own = CourseAccess::ownUnits();
            if (count($own) === 1)
                $unit = $own[0];
        }
        if ($isNew || $isAdmin) {
            if (!CourseAccess::canUseUnit($unit))
                return self::fail('واحد انتخاب‌شده معتبر نیست یا به آن دسترسی ندارید', 'Courses[college]');
            $model->college = $unit;
        }
        $unit = (string) $model->college;

        $title = isset($input['title']) && is_array($input['title']) ? $input['title'] : [];
        $model->title = [
            'main_fa' => $str(isset($title['main_fa']) ? $title['main_fa'] : ''),
            'main_en' => $str(isset($title['main_en']) ? $title['main_en'] : ''),
            'degree_fa' => $str(isset($title['degree_fa']) ? $title['degree_fa'] : ''),
            'degree_en' => $str(isset($title['degree_en']) ? $title['degree_en'] : ''),
        ];
        if ($model->title['main_fa'] === '')
            return self::fail('لطفاً عنوان اصلی فارسی را وارد کنید', 'Courses[title][main_fa]');
        if ($model->title['degree_fa'] === '')
            return self::fail('لطفاً عنوان فارسی داخل گواهی را وارد کنید', 'Courses[title][degree_fa]');
        foreach (['main_en', 'degree_en'] as $key)
            if ($model->title[$key] !== '' && !preg_match("/^[A-Za-z0-9 .,:;'&()\\-\\/]+$/", $model->title[$key]))
                return self::fail('عنوان انگلیسی فقط باید با حروف انگلیسی نوشته شود', 'Courses[title][' . $key . ']');

        $model->price = $digits(isset($input['price']) ? $input['price'] : '');
        if (!ctype_digit((string) $model->price) || (int) $model->price < 1)
            return self::fail('لطفاً قیمت اصلی دوره را به تومان وارد کنید', 'Courses[price]');
        // بند ۵.۲ صورتجلسه: کارگزار و کارشناس واحد تخفیف شهریه ثبت نمی‌کنند
        // در ویرایش، تخفیفی که مدیر سیستم قبلاً ثبت کرده با ذخیره‌ی غیرمدیر از بین نمی‌رود
        $previousDiscount = $isNew ? '' : (string) $model->getOldAttribute('discount_price');
        if (CourseAccess::canSetDiscount())
            $model->discount_price = $digits(isset($input['discount_price']) ? $input['discount_price'] : '');
        else
            $model->discount_price = ctype_digit($previousDiscount) && ctype_digit((string) $model->price) && (int) $previousDiscount <= (int) $model->price
                ? $previousDiscount
                : $model->price;
        if (ctype_digit((string) $model->discount_price) && ctype_digit((string) $model->price) && (int) $model->discount_price > (int) $model->price)
            return self::fail('قیمت با تخفیف نمی‌تواند از قیمت اصلی بیشتر باشد', 'Courses[discount_price]');
        if ($model->discount_price === '')
            $model->discount_price = $model->price;

        $duration = $digits(isset($input['duration']) ? $input['duration'] : '');
        if (($durationError = static::durationError($duration)) !== null)
            return self::fail($durationError, 'Courses[duration]');
        $model->duration = $duration;

        $model->time = $str(isset($input['time']) ? $input['time'] : '', 100);
        $model->place = $str(isset($input['place']) ? $input['place'] : '', 200);
        if ($model->time === '')
            return self::fail('لطفاً زمان برگزاری دوره را وارد کنید', 'Courses[time]');
        if ($model->place === '')
            return self::fail('لطفاً محل برگزاری دوره را وارد کنید', 'Courses[place]');
        $contentType = isset($input['content_type']) && is_scalar($input['content_type']) ? (string) $input['content_type'] : '';
        // «محتوامحور» فقط برای دوره‌های قدیمی که از قبل همین مقدار را دارند باقی می‌ماند (validator مدل)
        if (!isset(static::CONTENT_TYPES[$contentType]) && !($contentType === '3' && !$isNew))
            return self::fail($contentType === '' ? 'لطفاً نوع دوره را انتخاب کنید' : 'نوع دوره معتبر نیست', 'Courses[content_type]');
        $model->content_type = $contentType;

        $capacity = isset($input['student_capacity']) && is_array($input['student_capacity']) ? $input['student_capacity'] : [];
        $capacityType = isset($capacity['type']) && is_scalar($capacity['type']) ? (string) $capacity['type'] : '';
        if ($capacityType === '')
            return self::fail('لطفاً نوع ظرفیت را انتخاب کنید', 'Courses[student_capacity][type]');
        $model->student_capacity = ['type' => $capacityType];
        if ($capacityType === Courses::CAPACITY_TYPE_LIMITED) {
            $number = $digits(isset($capacity['number']) ? $capacity['number'] : '');
            if (!ctype_digit($number) || (int) $number < 1)
                return self::fail('برای ظرفیت محدود، تعداد ظرفیت را به‌صورت عدد وارد کنید', 'Courses[student_capacity][number]');
            $model->student_capacity = ['type' => $capacityType, 'number' => (string) (int) $number];
        }

        $description = isset($input['description']) && is_string($input['description']) ? $input['description'] : '';
        // متن ویرایشگر روی سایت اصلی به‌صورت HTML نمایش داده می‌شود: پاک‌سازی در برابر XSS
        $model->description = HtmlPurifier::process($description);

        // افزودن عضو بدون کیف پول: فقط مدیر سیستم یا کاربر دارای دسترسی ویژه
        $identity = CourseAccess::identity();
        if ($identity !== null && ($identity->role === 'user' || $identity->additional_access === true))
            $model->allow_free_add_user = isset($input['allow_free_add_user']) && (string) $input['allow_free_add_user'] === '1';
        return true;
    }

    /**
     * @return string|null پیام خطا اگر مدت دوره در بازه‌ی این نوع دوره نباشد
     */
    protected static function durationError($duration)
    {
        if (!ctype_digit($duration) || (int) $duration < self::MIN_HOURS)
            return 'امکان ثبت دوره‌ی کوتاه‌مدت کمتر از ' . self::MIN_HOURS . ' ساعت نیست';
        if ((int) $duration > self::MAX_HOURS)
            return 'دوره‌ی بیشتر از ' . self::MAX_HOURS . ' ساعت را از بخش دوره‌های میان‌مدت ثبت کنید';
        return null;
    }

    /**
     * سرور برگزاری کلاس: یکی از سرورهای فعال (تنظیمات سایت › سرورها) یا «هیچ‌کدام».
     * سروری که قبلاً روی دوره بوده، حتی اگر بعداً غیرفعال شده باشد، حفظ می‌شود.
     */
    protected static function applyServer(Courses $model, $input)
    {
        $value = isset($input['classroom_server']) && is_string($input['classroom_server']) ? $input['classroom_server'] : '';
        if ($value === ClassroomServers::NONE) {
            $model->classroom_server = ClassroomServers::NONE;
            return true;
        }
        if ($value !== '' && $value === (string) $model->getOldAttribute('classroom_server'))
            return true;
        $server = ClassroomServers::findById($value);
        if ($server === null || !$server->active)
            return self::fail('سرور برگزاری کلاس را انتخاب کنید (یا «هیچ‌کدام» اگر کلاس در سامانه‌ی دیگری برگزار می‌شود)', 'Courses[classroom_server]');
        $model->classroom_server = (string) $server->_id;
        return true;
    }

    /**
     * کارگزار و قرارداد باید متعلق به همین واحد و فعال باشند؛ کارگزار فقط خودش.
     */
    protected static function applyBroker(Courses $model, $input, $unit)
    {
        // در ویرایش، اگر فرم فیلد کارگزار نداشت (مثلاً برای کارشناس واحد فقط نمایشی است)، کارگزار فعلی حفظ می‌شود
        if (!$model->isNewRecord && !array_key_exists('broker', $input) && CourseAccess::role() !== 'broker')
            return true;
        $broker = isset($input['broker']) && is_array($input['broker']) ? $input['broker'] : [];
        $brokerId = isset($broker['_id']) && is_string($broker['_id']) ? $broker['_id'] : '';
        if (CourseAccess::role() === 'broker') {
            $own = CourseAccess::broker();
            if ($own === null)
                return self::fail('اطلاعات کارگزاری شما یافت نشد');
            $brokerId = (string) $own->_id;
        }
        // کارگزار و نوع قرارداد در ثبت دوره الزامی است؛ دوره‌های قدیمیِ بدون کارگزار در ویرایش همان‌طور می‌مانند
        if ($brokerId === '') {
            if ($model->isNewRecord || CourseStatus::hasBroker($model) || (string) $model->college !== (string) $model->getOldAttribute('college'))
                return self::fail('کارگزار دوره را انتخاب کنید', 'Courses[broker][_id]');
            $model->broker = null;
            return true;
        }
        if (!preg_match('/^[a-f0-9]{24}$/i', $brokerId))
            return self::fail('کارگزار انتخاب‌شده معتبر نیست', 'Courses[broker][_id]');
        $record = Brokers::findOne($brokerId);
        if ($record === null || (string) $record->status !== '1' || !in_array($unit, StudentAccess::normalizeColleges($record->college), true))
            return self::fail('کارگزار انتخاب‌شده متعلق به این واحد نیست یا فعال نیست', 'Courses[broker][_id]');
        $contract = isset($broker['contract']) && is_scalar($broker['contract']) ? (string) $broker['contract'] : '';
        // قرارداد غیرفعال‌شده توسط مدیر قابل انتخاب نیست؛ مگر همان قراردادی که از قبل روی دوره بوده
        $old = $model->getOldAttribute('broker');
        $keep = is_array($old) && isset($old['_id'], $old['contract']) && (string) $old['_id'] === $brokerId ? (string) $old['contract'] : null;
        $contractIds = [];
        foreach ((array) $record->contracts as $item)
            if (is_array($item) && isset($item['id']) && (CourseOptions::contractActive($item) || (string) $item['id'] === $keep))
                $contractIds[] = (string) $item['id'];
        if ($contract === '' || !in_array($contract, $contractIds, true))
            return self::fail('نوع قرارداد کارگزار را انتخاب کنید (قراردادهای غیرفعال قابل انتخاب نیستند)', 'Courses[broker][contract]');
        $model->broker = ['_id' => $brokerId, 'contract' => $contract];
        return true;
    }

    /**
     * درس، مدرس و تاریخ‌ها. غیرمدیر پس از ثبت، تاریخ شروع/پایان را تغییر نمی‌دهد (رفتار قبلی).
     */
    private static function applyLesson(Courses $model, $input, $unit, $isNew)
    {
        $lessons = isset($input['lessons'][0]) && is_array($input['lessons'][0]) ? $input['lessons'][0] : [];
        $old = is_array($model->lessons) && isset($model->lessons[0]) && is_array($model->lessons[0]) ? $model->lessons[0] : [];
        $lessonId = isset($lessons['_id']) && is_string($lessons['_id']) ? $lessons['_id'] : '';
        $teacherId = isset($lessons['teachers']) && is_string($lessons['teachers']) ? $lessons['teachers'] : '';
        if (!preg_match('/^[a-f0-9]{24}$/i', $lessonId) || !Lessons::find()->where(['_id' => $lessonId, 'college' => $unit])->exists())
            return self::fail('درس دوره را از دروس همین واحد انتخاب کنید', 'Courses[lessons][0][_id]');
        if (!preg_match('/^[a-f0-9]{24}$/i', $teacherId) || !Teachers::find()->where(['_id' => $teacherId])->exists())
            return self::fail('مدرس دوره را انتخاب کنید', 'Courses[lessons][0][teachers]');

        $date = isset($lessons['date']) && is_array($lessons['date']) ? $lessons['date'] : [];
        $oldDate = isset($old['date']) && is_array($old['date']) ? $old['date'] : [];
        $canChangeDates = $isNew || CourseAccess::isAdmin();
        $from = $canChangeDates ? self::jalaliDate(isset($date['from']) ? $date['from'] : '') : (isset($oldDate['from']) ? (string) $oldDate['from'] : '');
        $to = $canChangeDates ? self::jalaliDate(isset($date['to']) ? $date['to'] : '') : (isset($oldDate['to']) ? (string) $oldDate['to'] : '');
        $time = isset($date['time']) && is_scalar($date['time']) ? trim((string) $date['time']) : '';
        if ($canChangeDates) {
            $fromTs = $from === null ? null : self::toTimestamp($from);
            $toTs = $to === null ? null : self::toTimestamp($to);
            if ($fromTs === null || $toTs === null)
                return self::fail('تاریخ شروع و پایان دوره را درست وارد کنید', 'Courses[lessons][0][date][' . ($fromTs === null ? 'from' : 'to') . ']');
            // تاریخ پایان حداقل یک روز بعد از تاریخ شروع
            if (strcmp($to, $from) <= 0 || $toTs - $fromTs < 86400 - 3600)
                return self::fail('تاریخ پایان دوره باید حداقل یک روز بعد از تاریخ شروع باشد', 'Courses[lessons][0][date][to]');
        }
        if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', UsersSearch::normalizeDigits($time)))
            return self::fail('ساعت شروع دوره را به شکل ۱۸:۳۰ وارد کنید', 'Courses[lessons][0][date][time]');

        $lesson = [
            '_id' => $lessonId,
            'teachers' => $teacherId,
            'hide_archive' => isset($lessons['hide_archive']) && ($lessons['hide_archive'] === '1' || $lessons['hide_archive'] === 'true' || $lessons['hide_archive'] === true),
            'date' => ['from' => (string) $from, 'to' => (string) $to, 'time' => UsersSearch::normalizeDigits($time)],
        ];
        if (isset($old['meeting']) && (string) (isset($old['_id']) ? $old['_id'] : '') === $lessonId)
            $lesson['meeting'] = $old['meeting']; // کلاس آنلاین ساخته‌شده حفظ می‌شود
        $model->lessons = [$lesson];
        $model->deadline_date = self::registrationDeadline((string) $from, (string) $to);
        return true;
    }

    /**
     * مهلت ثبت عضو: شروع + یک‌چهارم طول دوره (قاعده‌ی موجود پروژه)، به‌صورت شمسی.
     */
    public static function registrationDeadline($from, $to)
    {
        $start = self::toTimestamp($from);
        $end = self::toTimestamp($to);
        if ($start === null || $end === null || $end <= $start)
            return null;
        $days = (int) floor(($end - $start) / 86400);
        return UsersDirectory::jdate('Y-m-d', $start + intdiv($days, 4) * 86400, 'en');
    }

    /**
     * «1404/7/1» یا «1404-07-01» (ارقام فارسی مجاز) → «1404-07-01»؛ نامعتبر → null.
     */
    public static function jalaliDate($value)
    {
        $value = is_scalar($value) ? UsersSearch::normalizeDigits((string) $value) : '';
        if (!preg_match('/^(1[34]\d{2})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $value, $m))
            return null;
        if ((int) $m[2] < 1 || (int) $m[2] > 12 || (int) $m[3] < 1 || (int) $m[3] > 31)
            return null;
        require_once Yii::getAlias('@frontend') . '/web/jdf.php';
        if (!jcheckdate((int) $m[2], (int) $m[3], (int) $m[1]))
            return null; // مثلاً ۳۱ مهر یا ۳۰ اسفند سال غیرکبیسه
        return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    }

    public static function toTimestamp($jalali)
    {
        $jalali = self::jalaliDate($jalali);
        return $jalali === null ? null : UsersSearch::jalaliToTimestamp(str_replace('-', '/', $jalali), false);
    }

    protected static function fail($message, $field = null)
    {
        self::$error = $message;
        self::$errorField = $field;
        return false;
    }
}
