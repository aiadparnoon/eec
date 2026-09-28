<?php

namespace app\components;

use Yii;
use app\components\classroom\ClassroomPlatforms;
use app\models\Brokers;
use app\models\Colleges;
use app\models\Courses;
use app\models\Orders;
use app\models\Users;
use app\models\UsersSearch;
use app\models\WalletTransactions;

/**
 * افزودن اعضای دوره (کوتاه‌مدت و میان‌مدت) از فایل اکسل.
 *
 * ستون‌ها: A نام، B نام خانوادگی، C نام کاربری (موبایل یا ایمیل)، D کد ملی (رمز اولیه‌ی حساب جدید)،
 * E نام انگلیسی، F نام خانوادگی انگلیسی، G جنسیت (1 مرد، 2 زن). ردیف اول عنوان ستون‌هاست.
 *
 * قاعده‌ی اصلی: اگر حتی یک ردیف یا یک شرط دوره مشکل داشته باشد، هیچ‌کس ثبت نمی‌شود و دکمه‌ی ثبت
 * نمایش داده نمی‌شود. در ثبت نهایی فایل دوباره کامل بررسی می‌شود (به پیش‌نمایش اعتماد نمی‌شود).
 *
 * هزینه: سهم واحد از شهریه برای هر نفر از کیف پول کارگزار دوره کسر می‌شود (مگر «افزودن عضو بدون کیف
 * پول» برای دوره یا واحد فعال باشد). کسر به‌صورت اتمیک (شرط روی مقدار قبلی) انجام می‌شود تا دو ثبت
 * هم‌زمان نتوانند بیش از موجودی برداشت کنند.
 */
class CourseMembersImport
{
    const MAX_FILE_SIZE = 5242880; // 5MB
    const MAX_ROWS = 1000;
    const SESSION_KEY = 'courseMembersImport';
    const COLUMNS = ['A' => 'نام', 'B' => 'نام خانوادگی', 'C' => 'نام کاربری', 'D' => 'کد ملی', 'E' => 'نام انگلیسی', 'F' => 'نام خانوادگی انگلیسی', 'G' => 'جنسیت'];

    public static function storageDir()
    {
        $dir = Yii::getAlias('@runtime/course-member-imports');
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);
        return $dir;
    }

    // ------------------------------------------------------------------ فایل

    /**
     * @return array ['error' => string|null, 'token' => string|null]
     */
    public static function store($file, $courseId)
    {
        if (!is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']))
            return ['error' => 'فایلی دریافت نشد، لطفاً دوباره تلاش کنید', 'token' => null];
        $ext = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true))
            return ['error' => 'پسوند فایل باید xlsx یا xls باشد', 'token' => null];
        if ((int) $file['size'] > self::MAX_FILE_SIZE)
            return ['error' => 'حجم فایل باید کمتر از ۵ مگابایت باشد', 'token' => null];
        // فایل اکسل واقعی: xlsx یک zip است و xls یک فایل OLE
        $head = (string) @file_get_contents($file['tmp_name'], false, null, 0, 8);
        if (($ext === 'xlsx' && strpos($head, "PK\x03\x04") !== 0) || ($ext === 'xls' && strpos($head, "\xD0\xCF\x11\xE0") !== 0))
            return ['error' => 'محتوای فایل با اکسل مطابقت ندارد', 'token' => null];

        self::discard();
        $token = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::storageDir() . DIRECTORY_SEPARATOR . $token))
            return ['error' => 'ذخیره‌ی فایل با خطا مواجه شد', 'token' => null];
        Yii::$app->session->set(self::SESSION_KEY, ['token' => $token, 'course' => (string) $courseId]);
        return ['error' => null, 'token' => $token];
    }

    /** مسیر فایلِ همین نشست و همین دوره */
    public static function pathFor($token, $courseId)
    {
        $saved = Yii::$app->session->get(self::SESSION_KEY);
        if (!is_array($saved) || !is_string($token) || !isset($saved['token'], $saved['course'])
            || !hash_equals((string) $saved['token'], $token) || $saved['course'] !== (string) $courseId
            || !preg_match('/^[a-f0-9]{32}\.(xlsx|xls)$/', $token))
            return null;
        $path = self::storageDir() . DIRECTORY_SEPARATOR . $token;
        return is_file($path) ? $path : null;
    }

    public static function discard()
    {
        $saved = Yii::$app->session->get(self::SESSION_KEY);
        if (is_array($saved) && isset($saved['token']) && preg_match('/^[a-f0-9]{32}\.(xlsx|xls)$/', (string) $saved['token'])) {
            $path = self::storageDir() . DIRECTORY_SEPARATOR . $saved['token'];
            if (is_file($path))
                @unlink($path);
        }
        Yii::$app->session->remove(self::SESSION_KEY);
    }

    // ------------------------------------------------------------------ قواعد

    /** کد ملی ایران: ۱۰ رقم با رقم کنترل؛ صفرهای اولِ حذف‌شده توسط اکسل برگردانده می‌شوند */
    public static function normalizeNationalCode($value)
    {
        $value = preg_replace('/[\s\-]/', '', UsersSearch::normalizeDigits((string) $value));
        if (preg_match('/^\d{8,9}$/', $value))
            $value = str_pad($value, 10, '0', STR_PAD_LEFT);
        return $value;
    }

    public static function isValidNationalCode($code)
    {
        if (!preg_match('/^\d{10}$/', $code) || preg_match('/^(\d)\1{9}$/', $code))
            return false;
        $sum = 0;
        for ($i = 0; $i < 9; $i++)
            $sum += (int) $code[$i] * (10 - $i);
        $remainder = $sum % 11;
        $check = (int) $code[9];
        return $remainder < 2 ? $check === $remainder : $check === 11 - $remainder;
    }

    /** ی و ک عربی به فارسی، فاصله‌های اضافه حذف */
    public static function normalizePersian($value)
    {
        $value = strtr(trim((string) $value), ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', "\xC2\xA0" => ' ']);
        return preg_replace('/\s+/u', ' ', $value);
    }

    public static function isPersianName($value)
    {
        // حروف فارسی/عربی، فاصله و نیم‌فاصله؛ بدون عدد و حرف لاتین
        return mb_strlen($value, 'UTF-8') >= 2 && mb_strlen($value, 'UTF-8') <= 60
            && preg_match('/^[\x{0621}-\x{063A}\x{0641}-\x{064A}\x{067E}\x{0686}\x{0698}\x{06A9}\x{06AF}\x{06CC}\x{0622}\x{0623}\x{0624}\x{0626}\x{0629}\x{06C0}\x{064B}-\x{0652}\x{200C} ]+$/u', $value) === 1;
    }

    // ------------------------------------------------------------------ بررسی

    /**
     * هزینه‌ی هر نفر و پرداخت‌کننده.
     *
     * @return array ['free' => bool, 'broker' => Brokers|null, 'contract' => array|null, 'perPerson' => int, 'error' => string|null]
     */
    public static function pricing(Courses $course)
    {
        $free = (string) $course->allow_free_add_user === '1' || $course->allow_free_add_user === true;
        $college = preg_match('/^[a-f0-9]{24}$/i', (string) $course->college) ? Colleges::findOne((string) $course->college) : null;
        if ($college !== null && $college->allow_free_add_user == true)
            $free = true;
        $result = ['free' => $free, 'broker' => null, 'contract' => null, 'perPerson' => 0, 'error' => null];

        $brokerId = is_array($course->broker) && isset($course->broker['_id']) ? (string) $course->broker['_id'] : '';
        $contractId = is_array($course->broker) && isset($course->broker['contract']) ? (string) $course->broker['contract'] : '';
        $broker = preg_match('/^[a-f0-9]{24}$/i', $brokerId) ? Brokers::findOne($brokerId) : null;
        if ($broker !== null && $contractId !== '')
            foreach ((array) $broker->contracts as $contract)
                if (is_array($contract) && isset($contract['id'], $contract['share']) && (string) $contract['id'] === $contractId && is_numeric($contract['share']))
                    $result['contract'] = $contract;
        $result['broker'] = $broker;
        if ($free)
            return $result;
        if ($broker === null || $result['contract'] === null)
            return array_merge($result, ['error' => 'این دوره کارگزار یا قرارداد معتبر ندارد؛ افزودن عضو از اکسل فقط با کسر سهم واحد از کیف پول کارگزار (یا با مجوز «افزودن عضو بدون کیف پول») ممکن است']);
        if (!is_numeric($course->price) || (float) $course->price <= 0)
            return array_merge($result, ['error' => 'شهریه‌ی دوره مشخص نیست']);
        $result['perPerson'] = (int) round((float) $course->price * (100 - (float) $result['contract']['share']) / 100);
        return $result;
    }

    public static function walletOf($broker)
    {
        return $broker !== null && is_numeric($broker->wallet_amount) ? (float) $broker->wallet_amount : 0.0;
    }

    /**
     * خواندن و اعتبارسنجی کامل فایل و شرایط دوره.
     *
     * @return array [
     *   'rows' => [['line', 'values', 'errors' => [col => msg], 'state' => new|existing|invalid, 'user' => Users|null]],
     *   'blockers' => string[], 'warnings' => string[], 'errorCount', 'newCount', 'existingCount',
     *   'pricing' => array, 'total' => int, 'wallet' => float, 'ok' => bool
     * ]
     */
    public static function analyze($path, Courses $course)
    {
        $result = ['rows' => [], 'blockers' => [], 'warnings' => [], 'errorCount' => 0, 'newCount' => 0, 'existingCount' => 0,
            'pricing' => null, 'total' => 0, 'wallet' => 0.0, 'ok' => false];
        try {
            $sheet = \PHPExcel_IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, true);
        } catch (\Exception $e) {
            $result['blockers'][] = 'فایل اکسل قابل خواندن نیست';
            return $result;
        }

        $rows = [];
        $line = 0;
        foreach ($sheet as $data) {
            $line++;
            if ($line === 1)
                continue;
            $values = [];
            foreach (array_keys(self::COLUMNS) as $col)
                $values[$col] = isset($data[$col]) && is_scalar($data[$col]) ? trim((string) $data[$col]) : '';
            if (implode('', $values) === '')
                continue;
            $rows[] = ['line' => $line, 'values' => $values, 'errors' => [], 'state' => 'new', 'user' => null];
        }
        if (empty($rows)) {
            $result['blockers'][] = 'هیچ ردیفی در فایل پیدا نشد (ردیف اول باید عنوان ستون‌ها باشد)';
            return $result;
        }
        if (count($rows) > self::MAX_ROWS) {
            $result['blockers'][] = 'حداکثر ' . UsersImport::faDigits(self::MAX_ROWS) . ' ردیف در هر فایل مجاز است';
            return $result;
        }

        // --- قواعد هر ردیف
        $usernames = [];
        $codes = [];
        foreach ($rows as &$row) {
            $v = &$row['values'];
            $v['A'] = self::normalizePersian($v['A']);
            $v['B'] = self::normalizePersian($v['B']);
            $v['C'] = UsersImport::normalizeUsername($v['C']);
            $v['D'] = self::normalizeNationalCode($v['D']);
            $v['E'] = preg_replace('/\s+/', ' ', $v['E']);
            $v['F'] = preg_replace('/\s+/', ' ', $v['F']);
            $v['G'] = UsersSearch::normalizeDigits($v['G']);
            $e = &$row['errors'];

            if ($v['A'] === '')
                $e['A'] = 'نام وارد نشده است';
            else if (!self::isPersianName($v['A']))
                $e['A'] = 'نام باید با حروف فارسی و بدون عدد یا حرف لاتین باشد';
            if ($v['B'] === '')
                $e['B'] = 'نام خانوادگی وارد نشده است';
            else if (!self::isPersianName($v['B']))
                $e['B'] = 'نام خانوادگی باید با حروف فارسی و بدون عدد یا حرف لاتین باشد';
            if ($v['C'] === '')
                $e['C'] = 'نام کاربری وارد نشده است';
            else if (!UsersImport::isValidUsername($v['C']))
                $e['C'] = preg_match('/^[\d+]+$/', $v['C']) ? 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود' : 'ایمیل معتبر نیست';
            else if (isset($usernames[$v['C']]))
                $e['C'] = 'تکراری با ردیف ' . UsersImport::faDigits($usernames[$v['C']]);
            else
                $usernames[$v['C']] = $row['line'];
            if ($v['D'] === '')
                $e['D'] = 'کد ملی وارد نشده است';
            else if (!self::isValidNationalCode($v['D']))
                $e['D'] = 'کد ملی معتبر نیست (۱۰ رقم با رقم کنترل درست)';
            else if (isset($codes[$v['D']]))
                $e['D'] = 'کد ملی تکراری با ردیف ' . UsersImport::faDigits($codes[$v['D']]);
            else
                $codes[$v['D']] = $row['line'];
            foreach (['E', 'F'] as $col)
                if ($v[$col] !== '' && !preg_match("/^[A-Za-z][A-Za-z .'\\-]{1,59}$/", $v[$col]))
                    $e[$col] = 'فقط حروف انگلیسی (حداقل ۲ حرف)';
            if ($v['G'] === '')
                $e['G'] = 'جنسیت وارد نشده است';
            else if (!in_array($v['G'], ['1', '2'], true))
                $e['G'] = 'جنسیت فقط ۱ (مرد) یا ۲ (زن)';
            unset($v, $e);
        }
        unset($row);

        // --- مقایسه با دیتابیس (دو کوئری)
        $existing = [];
        if (!empty($usernames))
            foreach (Users::find()->where(['username' => array_keys($usernames)])->all() as $user)
                $existing[(string) $user->username] = $user;
        $codeOwners = [];
        if (!empty($codes))
            foreach (Users::find()->select(['username', 'issuance_certificate_information'])->where(['issuance_certificate_information.id' => array_map('strval', array_keys($codes))])->asArray()->all() as $user)
                $codeOwners[(string) $user['issuance_certificate_information']['id']][] = (string) $user['username'];

        $courseId = (string) $course->_id;
        $missingEnglish = 0;
        foreach ($rows as &$row) {
            $v = $row['values'];
            if (!isset($row['errors']['C']) && isset($existing[$v['C']])) {
                $user = $existing[$v['C']];
                $row['user'] = $user;
                foreach ((array) $user->courses as $item)
                    if (is_array($item) && isset($item['_id']) && (string) $item['_id'] === $courseId)
                        $row['errors']['C'] = 'این کاربر از قبل عضو همین دوره است';
                $recorded = is_array($user->issuance_certificate_information) && isset($user->issuance_certificate_information['id'])
                    ? self::normalizeNationalCode($user->issuance_certificate_information['id']) : '';
                if (!isset($row['errors']['D']) && $recorded !== '' && $recorded !== $v['D'])
                    $row['errors']['D'] = 'با کد ملی ثبت‌شده برای این نام کاربری مطابقت ندارد';
            }
            if (!isset($row['errors']['D']) && isset($codeOwners[$v['D']]))
                foreach ($codeOwners[$v['D']] as $owner)
                    if ($owner !== $v['C'])
                        $row['errors']['D'] = 'این کد ملی برای حساب کاربری دیگری ثبت شده است';
            if (!empty($row['errors'])) {
                $row['state'] = 'invalid';
                $result['errorCount'] += count($row['errors']);
            } else if ($row['user'] !== null) {
                $row['state'] = 'existing';
                $result['existingCount']++;
            } else {
                $result['newCount']++;
            }
            if ($v['E'] === '' || $v['F'] === '')
                $missingEnglish++;
        }
        unset($row);
        $result['rows'] = $rows;
        if ($missingEnglish > 0)
            $result['warnings'][] = UsersImport::faDigits($missingEnglish) . ' نفر نام انگلیسی کامل ندارند؛ برای صدور گواهی، نام انگلیسی لازم است.';

        // --- شرایط دوره
        foreach (self::courseBlockers($course, count($rows)) as $blocker)
            $result['blockers'][] = $blocker;
        $pricing = self::pricing($course);
        $result['pricing'] = $pricing;
        if ($pricing['error'] !== null)
            $result['blockers'][] = $pricing['error'];
        if (!$pricing['free'] && $pricing['error'] === null) {
            $result['total'] = $pricing['perPerson'] * count($rows);
            $result['wallet'] = self::walletOf($pricing['broker']);
            if ($result['wallet'] < $result['total'])
                $result['blockers'][] = 'موجودی کیف پول کارگزار (' . UsersImport::faDigits(number_format($result['wallet'])) . ' تومان) برای ثبت '
                    . UsersImport::faDigits(count($rows)) . ' نفر کافی نیست؛ مبلغ لازم ' . UsersImport::faDigits(number_format($result['total']))
                    . ' تومان (سهم واحد هر نفر ' . UsersImport::faDigits(number_format($pricing['perPerson'])) . ' تومان) و کسری '
                    . UsersImport::faDigits(number_format($result['total'] - $result['wallet'])) . ' تومان است';
        }
        $result['ok'] = $result['errorCount'] === 0 && empty($result['blockers']);
        return $result;
    }

    /**
     * شرایط دوره برای افزودن عضو.
     *
     * @return string[]
     */
    public static function courseBlockers(Courses $course, $count)
    {
        $blockers = [];
        if (!in_array((string) $course->type, ['1', '2'], true))
            $blockers[] = 'این بخش فقط برای دوره‌های کوتاه‌مدت و میان‌مدت است';
        if (!CourseAccess::canManage($course))
            $blockers[] = 'به این دوره دسترسی ندارید';
        if (!CourseAccess::isAdmin()) {
            if ((string) $course->status !== CourseStatus::ACTIVE)
                $blockers[] = 'فقط به دوره‌ی تأییدشده و فعال می‌توان عضو اضافه کرد';
            $deadline = self::deadline($course);
            if ($deadline !== null && $deadline < UsersDirectory::jdate('Y-m-d', time(), 'en'))
                $blockers[] = 'مهلت ثبت عضو این دوره (' . UsersImport::faDigits(str_replace('-', '/', $deadline)) . ') به پایان رسیده است';
        }
        $capacity = is_array($course->student_capacity) ? $course->student_capacity : [];
        if (isset($capacity['type']) && (string) $capacity['type'] === '2' && isset($capacity['number']) && ctype_digit((string) $capacity['number'])) {
            $members = (int) Users::find()->where(['courses._id' => (string) $course->_id])->count();
            if ($members + $count > (int) $capacity['number'])
                $blockers[] = 'ظرفیت دوره ' . UsersImport::faDigits($capacity['number']) . ' نفر است و ' . UsersImport::faDigits($members)
                    . ' نفر عضو دارد؛ حداکثر ' . UsersImport::faDigits(max(0, (int) $capacity['number'] - $members)) . ' نفر دیگر قابل ثبت است';
        }
        return $blockers;
    }

    /**
     * مهلت ثبت عضو (شمسی Y-m-d)؛ اگر ذخیره نشده باشد از تاریخ‌های دوره محاسبه می‌شود
     * (کوتاه‌مدت: تاریخ درس؛ میان‌مدت: تاریخ دوره).
     */
    public static function deadline(Courses $course)
    {
        $deadline = ShortCourseForm::jalaliDate((string) $course->deadline_date);
        if ($deadline !== null)
            return $deadline;
        $date = (string) $course->type === '2'
            ? (is_array($course->date) ? $course->date : [])
            : (isset($course->lessons[0]['date']) && is_array($course->lessons[0]['date']) ? $course->lessons[0]['date'] : []);
        return isset($date['from'], $date['to']) ? ShortCourseForm::registrationDeadline((string) $date['from'], (string) $date['to']) : null;
    }

    // ------------------------------------------------------------------ ثبت

    /**
     * ثبت نهایی: فایل دوباره بررسی می‌شود؛ هر خطایی = هیچ ثبتی.
     *
     * @return array ['ok' => bool, 'message' => string, 'created' => int, 'added' => int, 'failed' => string[], 'charged' => int]
     */
    public static function import($path, Courses $course)
    {
        $analysis = self::analyze($path, $course);
        if (!$analysis['ok'])
            return ['ok' => false, 'message' => 'فایل یا شرایط دوره تغییر کرده و دیگر معتبر نیست؛ دوباره بررسی کنید', 'created' => 0, 'added' => 0, 'failed' => [], 'charged' => 0];

        $identity = Yii::$app->user->identity;
        $pricing = $analysis['pricing'];
        $count = count($analysis['rows']);
        $batch = 'excel-' . bin2hex(random_bytes(6));

        // ۱) کسر اتمیک از کیف پول کارگزار (پیش از ثبت اعضا)
        $charged = 0;
        if (!$pricing['free']) {
            $charged = $pricing['perPerson'] * $count;
            if (!self::adjustWallet($pricing['broker'], -$charged))
                return ['ok' => false, 'message' => 'موجودی کیف پول کارگزار کافی نیست یا هم‌زمان تغییر کرد؛ هیچ عضوی ثبت نشد', 'created' => 0, 'added' => 0, 'failed' => [], 'charged' => 0];
        }

        // ۲) ثبت اعضا
        $courseId = (string) $course->_id;
        $units = StudentAccess::normalizeColleges($course->college);
        $online = ClassroomPlatforms::hasOnlineClass($course);
        $entry = ['_id' => $courseId, 'status' => $online ? '0' : '1', 'registrant' => (string) $identity->username];
        $report = ['created' => 0, 'added' => 0, 'failed' => []];
        $done = [];
        foreach ($analysis['rows'] as $row) {
            $v = $row['values'];
            $info = ['first_name_fa' => $v['A'], 'last_name_fa' => $v['B'], 'id' => $v['D'], 'gender' => $v['G'] === '2' ? '0' : '1'];
            if ($v['E'] !== '')
                $info['first_name_en'] = $v['E'];
            if ($v['F'] !== '')
                $info['last_name_en'] = $v['F'];

            $user = Users::find()->where(['username' => $v['C']])->one();
            if ($user !== null) {
                $courses = is_array($user->courses) ? array_values($user->courses) : [];
                foreach ($courses as $item)
                    if (is_array($item) && isset($item['_id']) && (string) $item['_id'] === $courseId)
                        continue 2; // هم‌زمان اضافه شده؛ دوباره ثبت و هزینه نمی‌شود (پایین برگشت داده می‌شود)
                $courses[] = $entry;
                $user->courses = $courses;
                StudentAccess::addColleges($user, $units);
                // اطلاعات گواهی: فقط فیلدهای خالی تکمیل می‌شوند
                $current = is_array($user->issuance_certificate_information) ? $user->issuance_certificate_information : [];
                foreach ($info as $key => $value)
                    if (!isset($current[$key]) || $current[$key] === '' || $current[$key] === null)
                        $current[$key] = $value;
                $user->issuance_certificate_information = $current;
                if ((int) $user->status !== Users::STATUS_ACTIVE)
                    $user->status = Users::STATUS_ACTIVE;
                if ($user->save(false)) {
                    $report['added']++;
                    $done[] = $v;
                } else {
                    $report['failed'][] = $v['C'];
                }
                continue;
            }

            $model = new Users();
            $model->first_name = $v['A'];
            $model->last_name = $v['B'];
            $model->username = $v['C'];
            $model->setPassword($v['D']);
            $model->auth_key = Yii::$app->security->generateRandomString();
            $model->verification_token = Yii::$app->security->generateRandomString();
            $model->role = 'user';
            $model->status = Users::STATUS_ACTIVE;
            $model->registrant = (string) $identity->username;
            $model->must_change_password = true; // رمز اولیه = کد ملی
            $model->college = $units;
            $model->courses = [$entry];
            $model->issuance_certificate_information = $info;
            if ($model->save(false)) {
                $report['created']++;
                $done[] = $v;
            } else {
                $report['failed'][] = $v['C'];
            }
        }

        // ۳) بازگرداندن هزینه‌ی ردیف‌هایی که ثبت نشدند
        $registered = count($done);
        if (!$pricing['free'] && $registered < $count) {
            $refund = $pricing['perPerson'] * ($count - $registered);
            self::adjustWallet($pricing['broker'], $refund);
            $charged -= $refund;
        }

        // ۴) سفارش‌ها و تراکنش کیف پول (همان قالب قبلی سیستم برای گزارش‌های مالی)
        if (!$pricing['free'] && $registered > 0) {
            $broker = $pricing['broker'];
            $share = (float) $pricing['contract']['share'];
            $price = (float) $course->price;
            foreach ($done as $v) {
                $order = new Orders();
                $order->username = $v['C'];
                $order->first_name = $v['A'];
                $order->last_name = $v['B'];
                $order->orders = [['_id' => $courseId, 'type' => '1', 'payment_method' => '1', 'price' => $course->discount_price]];
                $order->amount = $course->price;
                $order->payment_info = ['order_id' => 'wallet', 'date' => UsersDirectory::jdate('Y/m/d', time(), 'en'), 'clean_date' => UsersDirectory::jdate('Ymd', time(), 'en'), 'reference_id' => $batch];
                $order->shares = [[
                    'id' => $courseId,
                    'college' => (string) $course->college,
                    'item' => isset($course->title['main_fa']) ? $course->title['main_fa'] : '',
                    'price' => $course->price,
                    'broker' => (string) $broker->_id,
                    'broker_contract_percent' => $pricing['contract']['share'],
                    'college_share' => $pricing['perPerson'],
                    'broker_share' => $price - $pricing['perPerson'],
                ]];
                $order->status = '1';
                $order->save(false);
            }
            $transaction = new WalletTransactions();
            $transaction->amount = (string) $charged;
            $transaction->broker_id = (string) $broker->_id;
            $transaction->college = (string) $course->college;
            $transaction->status = '1';
            $transaction->date = UsersDirectory::jdate('Y/m/d', time(), 'en');
            $transaction->reference_id = $batch;
            $transaction->type = '3';
            $transaction->description = 'افزودن ' . $registered . ' نفر از اکسل به دوره ' . (isset($course->title['main_fa']) ? $course->title['main_fa'] : '')
                . ' توسط ' . $identity->username;
            $transaction->save(false);
        }

        // ۵) ثبت در کلاس آنلاین
        if ($registered > 0 && $online)
            ClassroomPlatforms::forCourse($course)->registerCourseUsers($courseId);

        $message = 'ثبت انجام شد: ' . UsersImport::faDigits($report['created']) . ' حساب جدید، ' . UsersImport::faDigits($report['added']) . ' کاربر موجود';
        if (!$pricing['free'])
            $message .= '؛ ' . UsersImport::faDigits(number_format($charged)) . ' تومان از کیف پول کارگزار کسر شد';
        if (!empty($report['failed']))
            $message .= '؛ ثبت ' . UsersImport::faDigits(count($report['failed'])) . ' نفر ناموفق بود و هزینه‌ی آن‌ها برگشت داده شد';
        return ['ok' => true, 'message' => $message, 'created' => $report['created'], 'added' => $report['added'], 'failed' => $report['failed'], 'charged' => $charged];
    }

    /**
     * تغییر اتمیک موجودی کیف پول: به‌روزرسانی فقط وقتی انجام می‌شود که موجودی از زمان خواندن تغییر نکرده
     * باشد (compare-and-set)؛ در برداشت، موجودی منفی نمی‌شود.
     */
    public static function adjustWallet(Brokers $broker, $delta)
    {
        if ((float) $delta == 0.0)
            return true;
        $collection = Brokers::getCollection();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $doc = $collection->findOne(['_id' => $broker->_id], ['wallet_amount' => 1]);
            if ($doc === null)
                return false;
            $raw = array_key_exists('wallet_amount', $doc) ? $doc['wallet_amount'] : null;
            $current = is_numeric($raw) ? (float) $raw : 0.0;
            $next = $current + $delta;
            if ($next < 0)
                return false;
            $updated = $collection->update(['_id' => $broker->_id, 'wallet_amount' => $raw], ['$set' => ['wallet_amount' => (string) round($next)]]);
            if ($updated === 1 || $updated === true) {
                $broker->wallet_amount = (string) round($next);
                return true;
            }
            usleep(50000);
        }
        return false;
    }

    /**
     * فایل نمونه (ستون‌ها متنی تا صفر اول موبایل و کد ملی حذف نشود).
     */
    public static function template()
    {
        $excel = new \PHPExcel();
        $sheet = $excel->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('members');
        $headers = ['نام', 'نام خانوادگی', 'نام کاربری (موبایل یا ایمیل)', 'کد ملی', 'نام انگلیسی', 'نام خانوادگی انگلیسی', 'جنسیت (1 مرد، 2 زن)'];
        foreach (array_keys(self::COLUMNS) as $i => $col) {
            $sheet->getStyle($col . '1:' . $col . '2000')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $sheet->setCellValueExplicit($col . '1', $headers[$i], \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getColumnDimension($col)->setWidth(in_array($col, ['C', 'F', 'G'], true) ? 26 : 18);
        }
        $sample = ['محمد', 'احمدی', '09121234567', '0012345679', 'Mohammad', 'Ahmadi', '1'];
        foreach (array_keys(self::COLUMNS) as $i => $col)
            $sheet->setCellValueExplicit($col . '2', $sample[$i], \PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $path = self::storageDir() . DIRECTORY_SEPARATOR . 'template-' . bin2hex(random_bytes(6)) . '.xlsx';
        \PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($path);
        return $path;
    }
}
