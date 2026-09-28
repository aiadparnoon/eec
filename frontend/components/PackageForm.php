<?php

namespace app\components;

use app\models\Courses;
use app\models\Installments;
use app\models\Lessons;
use app\models\Teachers;
use app\models\UsersSearch;

/**
 * خواندن و اعتبارسنجی فرم ثبت/ویرایش دوره‌ی میان‌مدت (سمت سرور) — همان قواعد دوره‌ی کوتاه‌مدت
 * (ShortCourseForm) به‌علاوه‌ی:
 *
 *  - تاریخ شروع و اتمام دوره: اتمام حداقل یک روز بعد از شروع؛ غیرمدیر پس از ثبت تاریخ‌ها را تغییر نمی‌دهد.
 *  - دروس (در ثبت): هر درس از دروس همان واحد، بدون تکرار، با مدرسِ همان واحد و تاریخ‌هایی داخل بازه‌ی دوره.
 *  - شرایط اقساطی: مجموع پیش‌پرداخت و اقساط دقیقاً برابر شهریه‌ی قابل پرداخت؛ تاریخ هر قسط بین تاریخ شروع
 *    و اتمام دوره و به ترتیب.
 */
class PackageForm extends ShortCourseForm
{
    /** دوره‌ی تا ۲۴ ساعت کوتاه‌مدت است */
    const MIN_HOURS = 25;
    const MAX_HOURS = 3000;
    const MAX_LESSONS = 60;
    const MAX_INSTALLMENTS = 24;

    public static function apply(Courses $model, $input, $isNew)
    {
        self::$error = null;
        $input = is_array($input) ? $input : [];
        if (!static::applyCommon($model, $input, $isNew))
            return false;
        $unit = (string) $model->college;
        if (!self::applyBroker($model, $input, $unit))
            return false;
        if (!self::applyDates($model, $input, $isNew))
            return false;
        if ($isNew && !self::applyLessons($model, $input, $unit))
            return false;
        if (!self::applyServer($model, $input))
            return false;
        if ($isNew)
            return self::applyInstallments($model, isset($input['prepayment_installments']) ? $input['prepayment_installments'] : '', isset($input['installments']) ? $input['installments'] : []);
        // ویرایش: اگر شهریه یا تاریخ‌ها عوض شده، شرایط اقساطی موجود باید با مقادیر جدید هم سازگار بماند
        $changed = (string) $model->getOldAttribute('price') !== (string) $model->price
            || (string) $model->getOldAttribute('discount_price') !== (string) $model->discount_price
            || json_encode($model->getOldAttribute('date')) !== json_encode($model->date);
        if ($changed && !self::checkPlan($model))
            return self::fail(self::$error . ' — ابتدا شرایط اقساطی را در تب «اقساط» اصلاح کنید.');
        return true;
    }

    protected static function durationError($duration)
    {
        if (!ctype_digit($duration) || (int) $duration < self::MIN_HOURS)
            return 'دوره‌ی میان‌مدت باید حداقل ' . UsersImport::faDigits(self::MIN_HOURS) . ' ساعت باشد؛ دوره‌ی تا ' . UsersImport::faDigits(ShortCourseForm::MAX_HOURS) . ' ساعت را از بخش دوره‌های کوتاه‌مدت ثبت کنید';
        if ((int) $duration > self::MAX_HOURS)
            return 'مدت دوره معتبر نیست';
        return null;
    }

    /**
     * تاریخ‌های دوره (شمسی Y-m-d در date.from/date.to) و مهلت ثبت عضو.
     */
    protected static function applyDates(Courses $model, array $input, $isNew)
    {
        $old = is_array($model->date) ? $model->date : [];
        if ($isNew || CourseAccess::isAdmin()) {
            $date = isset($input['date']) && is_array($input['date']) ? $input['date'] : [];
            $from = self::jalaliDate(isset($date['from']) ? $date['from'] : '');
            $to = self::jalaliDate(isset($date['to']) ? $date['to'] : '');
            if ($from === null || $to === null)
                return self::fail('تاریخ شروع و اتمام دوره را درست وارد کنید');
            if (strcmp($to, $from) <= 0)
                return self::fail('تاریخ اتمام دوره باید حداقل یک روز بعد از تاریخ شروع باشد');
        } else {
            $from = isset($old['from']) ? (string) $old['from'] : '';
            $to = isset($old['to']) ? (string) $old['to'] : '';
        }
        $model->date = ['from' => $from, 'to' => $to];
        $model->deadline_date = self::registrationDeadline($from, $to);
        return true;
    }

    /**
     * دروس دوره (فقط در ثبت؛ در ویرایش از تب «دروس دوره» تغییر می‌کنند).
     */
    protected static function applyLessons(Courses $model, array $input, $unit)
    {
        $rows = isset($input['lessons']) && is_array($input['lessons']) ? array_values($input['lessons']) : [];
        if (empty($rows))
            return self::fail('حداقل یک درس برای دوره انتخاب کنید');
        if (count($rows) > self::MAX_LESSONS)
            return self::fail('تعداد دروس بیش از حد مجاز است');
        $ids = [];
        foreach ($rows as $row)
            if (is_array($row) && isset($row['_id']) && is_string($row['_id']) && preg_match('/^[a-f0-9]{24}$/i', $row['_id']))
                $ids[] = $row['_id'];
        if (count($ids) !== count($rows) || count(array_unique($ids)) !== count($ids))
            return self::fail('دروس انتخاب‌شده معتبر نیستند (هر درس فقط یک بار)');
        $titles = [];
        foreach (Lessons::find()->select(['title'])->where(['_id' => $ids, 'college' => $unit])->all() as $lesson)
            $titles[(string) $lesson->_id] = (string) $lesson->title;
        if (count($titles) !== count($ids))
            return self::fail('همه‌ی دروس باید از دروس همین واحد باشند');
        $teacherIds = [];
        foreach ($rows as $row)
            if (isset($row['teachers']) && is_string($row['teachers']) && preg_match('/^[a-f0-9]{24}$/i', $row['teachers']))
                $teacherIds[] = $row['teachers'];
        $validTeachers = [];
        if (!empty($teacherIds))
            foreach (Teachers::find()->select(['_id'])->where(['_id' => array_values(array_unique($teacherIds)), 'colleges' => $unit])->all() as $teacher)
                $validTeachers[(string) $teacher->_id] = true;

        $from = (string) $model->date['from'];
        $to = (string) $model->date['to'];
        $lessons = [];
        foreach ($rows as $row) {
            $id = $row['_id'];
            $name = '«' . $titles[$id] . '»';
            $teacher = isset($row['teachers']) && is_string($row['teachers']) ? $row['teachers'] : '';
            if (!isset($validTeachers[$teacher]))
                return self::fail('مدرس درس ' . $name . ' را از مدرسان همین واحد انتخاب کنید');
            $date = isset($row['date']) && is_array($row['date']) ? $row['date'] : [];
            $lessonFrom = self::jalaliDate(isset($date['from']) ? $date['from'] : '');
            $lessonTo = self::jalaliDate(isset($date['to']) ? $date['to'] : '');
            if ($lessonFrom === null || $lessonTo === null)
                return self::fail('تاریخ شروع و اتمام درس ' . $name . ' را درست وارد کنید');
            if (strcmp($lessonTo, $lessonFrom) < 0)
                return self::fail('تاریخ اتمام درس ' . $name . ' نباید قبل از تاریخ شروع آن باشد');
            if (strcmp($lessonFrom, $from) < 0 || strcmp($lessonTo, $to) > 0)
                return self::fail('تاریخ‌های درس ' . $name . ' باید داخل بازه‌ی برگزاری دوره باشد');
            $time = isset($date['time']) && is_scalar($date['time']) ? UsersSearch::normalizeDigits(trim((string) $date['time'])) : '';
            if (!preg_match('/^([01]?\d|2[0-3]):[0-5]\d$/', $time))
                return self::fail('ساعت شروع درس ' . $name . ' را به شکل ۱۸:۳۰ وارد کنید');
            $hours = isset($date['duration']) && is_scalar($date['duration']) ? UsersSearch::normalizeDigits(trim((string) $date['duration'])) : '';
            if (!ctype_digit($hours) || (int) $hours < 1 || (int) $hours > 500)
                return self::fail('مدت زمان درس ' . $name . ' را به ساعت وارد کنید');
            $lessons[] = [
                '_id' => $id,
                'teachers' => $teacher,
                'hide_archive' => isset($row['hide_archive']) && ($row['hide_archive'] === '1' || $row['hide_archive'] === 'true'),
                'date' => ['from' => $lessonFrom, 'to' => $lessonTo, 'time' => $time, 'duration' => $hours],
            ];
        }
        $model->lessons = $lessons;
        return true;
    }

    // ------------------------------------------------------------------ شرایط اقساطی

    /** شهریه‌ی قابل پرداخت (با تخفیف) */
    public static function payable(Courses $model)
    {
        if (ctype_digit((string) $model->discount_price) && (int) $model->discount_price > 0)
            return (int) $model->discount_price;
        return ctype_digit((string) $model->price) ? (int) $model->price : 0;
    }

    /**
     * شرایط اقساطی را از ورودی می‌خواند و اعتبارسنجی می‌کند. پیش‌پرداخت و اقساط خالی = دوره‌ی نقدی (بدون اقساط).
     *
     * @param mixed $prepayment
     * @param mixed $rows [['deadline' => '1405-09-01', 'amount' => '2000000'], ...]
     */
    public static function applyInstallments(Courses $model, $prepayment, $rows)
    {
        $digits = function ($value) {
            return is_scalar($value) ? preg_replace('/\s|,/', '', UsersSearch::normalizeDigits((string) $value)) : '';
        };
        $prepayment = $digits($prepayment);
        $plan = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row))
                continue;
            $deadline = isset($row['deadline']) && is_scalar($row['deadline']) ? trim((string) $row['deadline']) : '';
            $amount = $digits(isset($row['amount']) ? $row['amount'] : '');
            if ($deadline === '' && $amount === '')
                continue; // ردیف خالیِ فرم
            $plan[] = ['deadline' => $deadline, 'amount' => $amount];
        }
        if ($prepayment === '' && empty($plan)) {
            $model->prepayment_installments = null;
            $model->installments = null;
            return true;
        }
        $model->prepayment_installments = $prepayment;
        $model->installments = $plan;
        return self::checkPlan($model);
    }

    /**
     * قواعد شرایط اقساطی ذخیره‌شده روی مدل (در ثبت، ویرایش شهریه/تاریخ و ویرایش خود اقساط).
     */
    public static function checkPlan(Courses $model)
    {
        $plan = is_array($model->installments) ? array_values($model->installments) : [];
        $prepayment = (string) $model->prepayment_installments;
        if (empty($plan) && ($prepayment === '' || $prepayment === null))
            return true;
        if (empty($plan))
            return self::fail('برای شرایط اقساطی حداقل یک قسط وارد کنید (یا پیش‌پرداخت را هم خالی بگذارید تا دوره نقدی باشد)');
        if (count($plan) > self::MAX_INSTALLMENTS)
            return self::fail('حداکثر ' . self::MAX_INSTALLMENTS . ' قسط مجاز است');
        if (!ctype_digit($prepayment) || (int) $prepayment < 1)
            return self::fail('مبلغ پیش‌پرداخت را به تومان وارد کنید');
        $from = is_array($model->date) && isset($model->date['from']) ? (string) $model->date['from'] : '';
        $to = is_array($model->date) && isset($model->date['to']) ? (string) $model->date['to'] : '';
        $sum = (int) $prepayment;
        $previous = '';
        foreach ($plan as $i => $row) {
            $number = 'قسط ' . UsersImport::faDigits($i + 1);
            $deadline = self::jalaliDate(isset($row['deadline']) ? $row['deadline'] : '');
            $amount = isset($row['amount']) ? (string) $row['amount'] : '';
            if ($deadline === null)
                return self::fail('تاریخ سررسید ' . $number . ' را درست وارد کنید');
            if (!ctype_digit($amount) || (int) $amount < 1)
                return self::fail('مبلغ ' . $number . ' را به تومان وارد کنید');
            if ($from !== '' && strcmp($deadline, $from) < 0)
                return self::fail('سررسید ' . $number . ' (' . UsersImport::faDigits(str_replace('-', '/', $deadline)) . ') نمی‌تواند قبل از شروع دوره (' . UsersImport::faDigits(str_replace('-', '/', $from)) . ') باشد');
            if ($to !== '' && strcmp($deadline, $to) > 0)
                return self::fail('سررسید ' . $number . ' (' . UsersImport::faDigits(str_replace('-', '/', $deadline)) . ') نمی‌تواند بعد از اتمام دوره (' . UsersImport::faDigits(str_replace('-', '/', $to)) . ') باشد');
            if ($previous !== '' && strcmp($deadline, $previous) <= 0)
                return self::fail('سررسید اقساط باید به ترتیب و در روزهای مختلف باشد (' . $number . ')');
            $previous = $deadline;
            $plan[$i] = ['deadline' => $deadline, 'amount' => (string) (int) $amount];
            $sum += (int) $amount;
        }
        $payable = self::payable($model);
        if ($sum !== $payable)
            return self::fail('مجموع پیش‌پرداخت و اقساط (' . UsersImport::faDigits(number_format($sum)) . ' تومان) باید دقیقاً برابر شهریه‌ی دوره ('
                . UsersImport::faDigits(number_format($payable)) . ' تومان) باشد؛ ' . ($sum < $payable ? 'کسری ' : 'مازاد ')
                . UsersImport::faDigits(number_format(abs($payable - $sum))) . ' تومان');
        $model->installments = $plan;
        $model->prepayment_installments = (string) (int) $prepayment;
        return true;
    }

    /** آیا دانشپذیری با این شرایط اقساطی ثبت‌نام کرده است (تغییر شرایط برای غیرمدیر بسته می‌شود) */
    public static function planInUse(Courses $model)
    {
        return Installments::find()->where(['course_id' => (string) $model->_id])->exists();
    }
}
