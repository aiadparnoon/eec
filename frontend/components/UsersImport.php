<?php

namespace app\components;

use Yii;
use app\models\Users;
use app\models\UsersSearch;

/**
 * ورود دانشپذیران از فایل اکسل با قالب ثابت (docs/specs/users-manage.md بند ۴).
 *
 * ستون‌ها: A نام، B نام خانوادگی، C نام کاربری (موبایل یا ایمیل)، D رمز عبور،
 * E نام انگلیسی، F نام خانوادگی انگلیسی، G جنسیت (1 مرد، 2 زن). ردیف اول عنوان است.
 *
 * فایل خارج از web/ (در runtime) نگه داشته می‌شود و بلافاصله بعد از ثبت نهایی حذف می‌شود.
 */
class UsersImport
{
    const MAX_FILE_SIZE = 10485760; // 10MB
    const MAX_ROWS = 3000;
    /** سیاست طول رمز عبور (bcrypt فقط ۷۲ بایت اول را در نظر می‌گیرد) */
    const PASSWORD_MIN = 6;
    const PASSWORD_MAX = 72;
    const SESSION_KEY = 'usersImportFile';
    const COLUMNS = ['A' => 'نام', 'B' => 'نام خانوادگی', 'C' => 'نام کاربری', 'D' => 'رمز عبور', 'E' => 'نام انگلیسی', 'F' => 'نام خانوادگی انگلیسی', 'G' => 'جنسیت'];

    public static function storageDir()
    {
        $dir = Yii::getAlias('@runtime/user-imports');
        if (!is_dir($dir))
            @mkdir($dir, 0700, true);
        return $dir;
    }

    /**
     * فایل آپلودشده را بررسی و در پوشه‌ی خصوصی ذخیره می‌کند.
     *
     * @param array|null $file آرایه‌ی $_FILES['file']
     * @return array ['error' => string|null, 'token' => string|null]
     */
    public static function store($file)
    {
        if (!is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']))
            return ['error' => 'فایلی دریافت نشد، لطفاً دوباره تلاش کنید', 'token' => null];
        $ext = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION)); // پسوند بعد از آخرین نقطه
        if (!in_array($ext, ['xlsx', 'xls'], true))
            return ['error' => 'پسوند فایل باید xlsx یا xls باشد', 'token' => null];
        if ((int) $file['size'] > self::MAX_FILE_SIZE)
            return ['error' => 'حجم فایل باید کمتر از ۱۰ مگابایت باشد', 'token' => null];

        self::discard(); // فایل قبلی همین کاربر
        $token = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::storageDir() . DIRECTORY_SEPARATOR . $token))
            return ['error' => 'ذخیره‌ی فایل با خطا مواجه شد', 'token' => null];
        Yii::$app->session->set(self::SESSION_KEY, $token);
        return ['error' => null, 'token' => $token];
    }

    /**
     * مسیر فایلِ همین نشست را، اگر توکن ارسالی درست باشد، برمی‌گرداند.
     */
    public static function pathFor($token)
    {
        $expected = Yii::$app->session->get(self::SESSION_KEY);
        if (!is_string($token) || !is_string($expected) || !hash_equals($expected, $token) || !preg_match('/^[a-f0-9]{32}\.(xlsx|xls)$/', $token))
            return null;
        $path = self::storageDir() . DIRECTORY_SEPARATOR . $token;
        return is_file($path) ? $path : null;
    }

    /** حذف فایل موقت همین نشست */
    public static function discard()
    {
        $token = Yii::$app->session->get(self::SESSION_KEY);
        if (is_string($token) && preg_match('/^[a-f0-9]{32}\.(xlsx|xls)$/', $token)) {
            $path = self::storageDir() . DIRECTORY_SEPARATOR . $token;
            if (is_file($path))
                @unlink($path);
        }
        Yii::$app->session->remove(self::SESSION_KEY);
    }

    /**
     * خواندن و اعتبارسنجی همه‌ی ردیف‌ها.
     *
     * @return array [
     *   'rows' => [['line' => شماره ردیف اکسل, 'values' => [...], 'errors' => [col => msg], 'state' => new|existing|existing_same|invalid]],
     *   'errorCount' => int, 'newCount' => int, 'existingCount' => int, 'fatal' => string|null
     * ]
     */
    public static function analyze($path)
    {
        try {
            $sheet = \PHPExcel_IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Exception $e) {
            return ['rows' => [], 'errorCount' => 0, 'newCount' => 0, 'existingCount' => 0, 'fatal' => 'فایل اکسل قابل خواندن نیست'];
        }

        $rows = [];
        $line = 0;
        foreach ($sheet as $data) {
            $line++;
            if ($line === 1)
                continue; // عنوان ستون‌ها
            $values = [];
            foreach (array_keys(self::COLUMNS) as $col)
                $values[$col] = isset($data[$col]) ? trim((string) $data[$col]) : '';
            if (implode('', $values) === '')
                continue; // ردیف خالی
            $rows[] = ['line' => $line, 'values' => $values, 'errors' => [], 'state' => 'new'];
        }
        if (empty($rows))
            return ['rows' => [], 'errorCount' => 0, 'newCount' => 0, 'existingCount' => 0, 'fatal' => 'هیچ ردیفی در فایل پیدا نشد (ردیف اول باید عنوان ستون‌ها باشد)'];
        if (count($rows) > self::MAX_ROWS)
            return ['rows' => [], 'errorCount' => 0, 'newCount' => 0, 'existingCount' => 0, 'fatal' => 'حداکثر ' . self::faDigits(self::MAX_ROWS) . ' ردیف در هر فایل مجاز است'];

        $seen = [];
        foreach ($rows as &$row) {
            $v = &$row['values'];
            $v['C'] = self::normalizeUsername($v['C']);
            $v['G'] = UsersSearch::normalizeDigits($v['G']);
            $v['D'] = UsersSearch::normalizeDigits($v['D']);

            if ($v['A'] === '')
                $row['errors']['A'] = 'نام وارد نشده است';
            if ($v['B'] === '')
                $row['errors']['B'] = 'نام خانوادگی وارد نشده است';
            if ($v['C'] === '')
                $row['errors']['C'] = 'نام کاربری وارد نشده است';
            else if (!self::isValidUsername($v['C']))
                $row['errors']['C'] = ctype_digit(ltrim($v['C'], '+')) ? 'شماره موبایل معتبر نیست' : 'ایمیل معتبر نیست';
            else if (isset($seen[$v['C']]))
                $row['errors']['C'] = 'تکراری با ردیف ' . self::faDigits($seen[$v['C']]);
            else
                $seen[$v['C']] = $row['line'];
            if ($v['D'] === '')
                $row['errors']['D'] = 'رمز عبور وارد نشده است';
            else if (($passwordError = self::passwordError($v['D'])) !== null)
                $row['errors']['D'] = $passwordError;
            foreach (['E', 'F'] as $col)
                if ($v[$col] !== '' && !preg_match("/^[A-Za-z][A-Za-z .'\\-]*$/", $v[$col]))
                    $row['errors'][$col] = 'فقط حروف انگلیسی مجاز است';
            if ($v['G'] === '')
                $row['errors']['G'] = 'جنسیت وارد نشده است';
            else if (!in_array($v['G'], ['1', '2'], true))
                $row['errors']['G'] = 'جنسیت باید ۱ (مرد) یا ۲ (زن) باشد';
            unset($v);
        }
        unset($row);

        // نام‌های کاربری موجود با یک کوئری
        $existing = [];
        if (!empty($seen))
            foreach (Users::find()->select(['username', 'college'])->where(['username' => array_keys($seen)])->all() as $user)
                $existing[(string) $user->username] = $user;
        $colleges = StudentAccess::collegesForNewStudent();

        $result = ['rows' => [], 'errorCount' => 0, 'newCount' => 0, 'existingCount' => 0, 'fatal' => null];
        foreach ($rows as $row) {
            if (!empty($row['errors'])) {
                $row['state'] = 'invalid';
                $result['errorCount'] += count($row['errors']);
            } else if (isset($existing[$row['values']['C']])) {
                $has = StudentAccess::normalizeColleges($existing[$row['values']['C']]->college);
                $row['state'] = (empty($colleges) || count(array_diff($colleges, $has)) === 0) ? 'existing_same' : 'existing';
                $result['existingCount']++;
            } else {
                $result['newCount']++;
            }
            $result['rows'][] = $row;
        }
        return $result;
    }

    /**
     * ثبت نهایی. فقط وقتی هیچ خطایی نباشد اجرا می‌شود (فراخواننده بررسی می‌کند).
     *
     * @return array ['created' => [...], 'added' => [...], 'unchanged' => [...], 'failed' => [...]] هر مورد: نام کاربری => نام کامل
     */
    public static function import(array $analysis)
    {
        $identity = Yii::$app->user->identity;
        $colleges = StudentAccess::collegesForNewStudent();
        $report = ['created' => [], 'added' => [], 'unchanged' => [], 'failed' => []];
        foreach ($analysis['rows'] as $row) {
            $v = $row['values'];
            $fullName = $v['A'] . ' ' . $v['B'];
            $user = Users::find()->where(['username' => $v['C']])->one();
            if ($user !== null) {
                if (!empty($colleges) && StudentAccess::addColleges($user, $colleges)) {
                    if ($user->save(false, ['college', 'updated_at']))
                        $report['added'][$v['C']] = trim($user->first_name . ' ' . $user->last_name);
                    else
                        $report['failed'][$v['C']] = $fullName;
                } else {
                    $report['unchanged'][$v['C']] = trim($user->first_name . ' ' . $user->last_name);
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
            $model->must_change_password = true; // رمز اولیه را کارکنان تعیین کرده‌اند (بند ۱.۳ صورتجلسه)
            $model->college = [];
            StudentAccess::addColleges($model, $colleges);
            $info = ['first_name_fa' => $v['A'], 'last_name_fa' => $v['B'], 'gender' => $v['G'] === '2' ? '0' : '1'];
            if ($v['E'] !== '')
                $info['first_name_en'] = $v['E'];
            if ($v['F'] !== '')
                $info['last_name_en'] = $v['F'];
            $model->issuance_certificate_information = $info;
            if ($model->save())
                $report['created'][$v['C']] = $fullName;
            else
                $report['failed'][$v['C']] = $fullName;
        }
        return $report;
    }

    /**
     * نام کاربری: ارقام لاتین، حروف کوچک؛ صفر اولِ حذف‌شده توسط اکسل و پیش‌شماره‌ی 98 اصلاح می‌شود.
     */
    public static function normalizeUsername($value)
    {
        // mb_strtolower: strtolower در PHP 7.4 به locale وابسته است و متن UTF-8 را خراب می‌کند
        $value = mb_strtolower(str_replace([' ', "\xE2\x80\x8C", "\xC2\xA0"], '', UsersSearch::normalizeDigits((string) $value)), 'UTF-8');
        if (preg_match('/^9\d{9}$/', $value))
            return '0' . $value;
        if (preg_match('/^(?:\+98|0098|98)(9\d{9})$/', $value, $m))
            return '0' . $m[1];
        return $value;
    }

    /**
     * @return string|null پیام خطا اگر رمز با سیاست طول هم‌خوان نباشد
     */
    public static function passwordError($password)
    {
        $length = mb_strlen((string) $password, 'UTF-8');
        if ($length < self::PASSWORD_MIN)
            return 'رمز عبور باید حداقل ' . self::faDigits(self::PASSWORD_MIN) . ' کاراکتر باشد';
        if (strlen((string) $password) > self::PASSWORD_MAX)
            return 'رمز عبور بیش از حد طولانی است';
        return null;
    }

    public static function isValidUsername($value)
    {
        return preg_match('/^09\d{9}$/', $value) === 1 || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function faDigits($value)
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /**
     * فایل نمونه‌ی قالب (ستون‌ها با قالب متنی تا صفرهای اول حذف نشوند).
     *
     * @return string مسیر فایل موقت
     */
    public static function template()
    {
        $excel = new \PHPExcel();
        $sheet = $excel->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('users');
        $headers = array_values(self::COLUMNS);
        $headers[2] = 'نام کاربری (موبایل یا ایمیل)';
        $headers[6] = 'جنسیت (1 مرد، 2 زن)';
        foreach (array_keys(self::COLUMNS) as $i => $col) {
            $sheet->getStyle($col . '1:' . $col . '1000')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
            $sheet->setCellValueExplicit($col . '1', $headers[$i], \PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getColumnDimension($col)->setWidth($i === 2 ? 28 : 18);
        }
        $sample = ['محمد', 'احمدی', '09121234567', '0012345678', 'Mohammad', 'Ahmadi', '1'];
        foreach (array_keys(self::COLUMNS) as $i => $col)
            $sheet->setCellValueExplicit($col . '2', $sample[$i], \PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $path = self::storageDir() . DIRECTORY_SEPARATOR . 'template-' . bin2hex(random_bytes(6)) . '.xlsx';
        \PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save($path);
        return $path;
    }
}
