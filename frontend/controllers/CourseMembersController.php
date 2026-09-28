<?php

namespace frontend\controllers;

use Yii;
use app\components\CourseAccess;
use app\components\CourseMembersImport;
use app\components\SafeRedirect;
use app\components\StudentProfile;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\components\XlsxWriter;
use app\components\classroom\ClassroomPlatforms;
use app\models\Brokers;
use app\models\CancelingRequests;
use app\models\CertificateRequests;
use app\models\CourseMembersSearch;
use app\models\Courses;
use app\models\Orders;
use app\models\Users;
use app\models\WalletTransactions;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\Response;

/**
 * اعضای دوره — مشترک دوره‌های کوتاه‌مدت (type 1) و میان‌مدت (type 2):
 * خروجی اکسل با فیلترها، افزودن از اکسل (بررسی کامل + کسر از کیف پول کارگزار)، اطلاعات مالی،
 * تغییر وضعیت، ثبت در کلاس آنلاین و حذف/درخواست انصراف.
 *
 * دسترسی: هر دوره با CourseAccess (مدیر سیستم همه، کارشناس واحد واحد خودش، کارگزار دوره‌های خودش).
 */
class CourseMembersController extends Controller
{
    const FLASH = 'course-members';
    const TYPES = ['1', '2'];

    public function behaviors()
    {
        $staff = function () {
            $identity = Yii::$app->user->identity;
            if (in_array($identity->role, ['user', 'cnt'], true))
                return true;
            if (!in_array($identity->role, ['emp', 'broker'], true))
                return false;
            $access = is_array($identity->access) ? $identity->access : [];
            return in_array('courses', $access, true) || in_array('packages', $access, true);
        };
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['allow' => false, 'roles' => ['?']],
                    [
                        // خروجی اکسل برای استاد دوره هم (بدون ستون‌های مالی)
                        'actions' => ['report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () use ($staff) {
                            return $staff() || Yii::$app->user->identity->role == 'teacher';
                        },
                    ],
                    [
                        'actions' => ['template', 'check', 'import', 'finance', 'status', 'register-class', 'cancel'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => $staff,
                    ],
                ],
                'denyCallback' => function () {
                    Yii::$app->getResponse()->redirect(['access-denied']);
                },
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'check' => ['post'],
                    'import' => ['post'],
                    'finance' => ['post'],
                    'status' => ['post'],
                    'register-class' => ['post'],
                    'cancel' => ['post'],
                ],
            ],
        ];
    }

    // ================================================================== خروجی اکسل

    /**
     * اعضای دوره با همان فیلترهای تب «اعضا»: مشخصات، وضعیت، ثبت‌کننده، پرداخت و سهم‌ها، گواهی.
     */
    public function actionReport($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canView($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', null);
        $courseId = (string) $course->_id;
        $canSeeMoney = CourseAccess::canManage($course);
        $headers = ['ردیف', 'نام', 'نام خانوادگی', 'نام انگلیسی', 'نام خانوادگی انگلیسی', 'نام کاربری', 'کد ملی', 'جنسیت', 'شماره تماس',
            'وضعیت در دوره', 'درخواست انصراف', 'ثبت‌کننده', 'تاریخ عضویت در سامانه'];
        $widths = [7, 14, 18, 14, 18, 22, 13, 8, 14, 18, 12, 28, 14];
        if ($canSeeMoney) {
            $headers = array_merge($headers, ['نوع پرداخت', 'کانال پرداخت', 'تاریخ پرداخت', 'مبلغ پرداختی (تومان)', 'سهم واحد (تومان)', 'سهم کارگزار (تومان)']);
            $widths = array_merge($widths, [10, 16, 12, 16, 16, 16]);
        }
        $headers[] = 'وضعیت گواهی';
        $widths[] = 20;
        $writer = new XlsxWriter($headers, $widths);

        $query = (new CourseMembersSearch())->query(Yii::$app->request->queryParams, $courseId)
            ->select(['first_name', 'last_name', 'username', 'courses', 'issuance_certificate_information'])
            ->orderBy(['last_name' => SORT_ASC])->asArray();
        $certificates = [];
        foreach (CertificateRequests::find()->select(['username', 'status'])->where(['course_id' => $courseId])->asArray()->all() as $request)
            $certificates[(string) $request['username']] = (string) $request['status'];
        $str = function ($array, $key) {
            return isset($array[$key]) && is_scalar($array[$key]) ? (string) $array[$key] : '';
        };
        foreach ($query->batch(500) as $rows) {
            $registrantNames = [];
            $usernames = [];
            foreach ($rows as $row) {
                $usernames[] = $str($row, 'username');
                $item = self::entryOf(isset($row['courses']) ? $row['courses'] : [], $courseId);
                if (isset($item['registrant']))
                    $registrantNames[] = $item['registrant'];
            }
            $registrants = UsersDirectory::registrants($registrantNames);
            $orders = [];
            if ($canSeeMoney)
                foreach (Orders::find()->where(['username' => $usernames, 'orders._id' => $courseId])->orderBy(['_id' => SORT_ASC])->all() as $order)
                    if (StudentProfile::isSuccessfulOrder($order))
                        $orders[(string) $order->username] = $order;
            foreach ($rows as $row) {
                $item = self::entryOf(isset($row['courses']) ? $row['courses'] : [], $courseId);
                $username = $str($row, 'username');
                $registrant = UsersDirectory::describeUsername($str($item, 'registrant'), $username, $registrants);
                $info = isset($row['issuance_certificate_information']) && is_array($row['issuance_certificate_information']) ? $row['issuance_certificate_information'] : [];
                $gender = $str($info, 'gender');
                $line = [
                    $writer->rowCount() + 1,
                    $str($row, 'first_name'),
                    $str($row, 'last_name'),
                    $str($info, 'first_name_en'),
                    $str($info, 'last_name_en'),
                    $username,
                    $str($info, 'id'),
                    $gender === '1' ? 'مرد' : ($gender === '0' ? 'زن' : ''),
                    $str($info, 'phone'),
                    UsersDirectory::courseStatus(isset($item['status']) ? $item['status'] : '0')[0],
                    !empty($item['begin_deleted']) ? 'در انتظار' : '',
                    $registrant['name'] . ($registrant['roleLabel'] !== '' ? ' (' . $registrant['roleLabel'] . ')' : ''),
                    isset($row['_id']) ? UsersDirectory::jdate('Y/m/d', hexdec(substr((string) $row['_id'], 0, 8)), 'en') : '',
                ];
                if ($canSeeMoney) {
                    $order = isset($orders[$username]) ? $orders[$username] : null;
                    if ($order !== null) {
                        list($type, $channel) = StudentProfile::paymentType($order);
                        $shares = StudentProfile::shares($order);
                        $line = array_merge($line, [$type, $channel, is_array($order->payment_info) ? $str($order->payment_info, 'date') : '',
                            StudentProfile::orderPaidAmount($order), round($shares['college']), round($shares['broker'])]);
                    } else {
                        $line = array_merge($line, ['بدون پرداخت', '', '', '', '', '']);
                    }
                }
                // بند ۷.۶ صورتجلسه: مشخص باشد چه کسانی گواهی گرفته‌اند
                $line[] = isset($certificates[$username]) ? StudentProfile::certificateStatus($certificates[$username])[0] : 'درخواست نشده';
                $writer->addRow($line);
            }
        }
        $path = Yii::getAlias('@runtime') . '/CourseMembers-' . bin2hex(random_bytes(6)) . '.xlsx';
        $writer->save($path);
        $response = Yii::$app->response->sendFile($path, 'CourseMembers-' . UsersDirectory::jdate('Y-m-d-H-i', time(), 'en') . '.xlsx');
        $response->on(Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    // ================================================================== افزودن از اکسل

    public function actionTemplate()
    {
        $path = CourseMembersImport::template();
        $response = Yii::$app->response->sendFile($path, 'course-members-template.xlsx');
        $response->on(Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    /**
     * بررسی فایل (AJAX): پیش‌نمایش ردیف‌ها، خطاها، شرایط دوره و هزینه. تا وقتی خطایی هست دکمه‌ی ثبت نیست.
     */
    public function actionCheck($_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return ['ok' => false, 'html' => Html::tag('div', 'دوره یافت نشد یا به آن دسترسی ندارید', ['class' => 'alert alert-danger'])];
        $stored = CourseMembersImport::store(isset($_FILES['file']) ? $_FILES['file'] : null, (string) $course->_id);
        if ($stored['error'] !== null)
            return ['ok' => false, 'html' => Html::tag('div', Html::encode($stored['error']), ['class' => 'alert alert-danger'])];
        $analysis = CourseMembersImport::analyze(CourseMembersImport::pathFor($stored['token'], (string) $course->_id), $course);
        if (!$analysis['ok'])
            CourseMembersImport::discard(); // با خطا امکان ثبت نیست؛ فایل اصلاح‌شده دوباره بارگذاری می‌شود
        return [
            'ok' => $analysis['ok'],
            'html' => $this->renderPartial('_import-preview', [
                'analysis' => $analysis,
                'course' => $course,
                'token' => $analysis['ok'] ? $stored['token'] : null,
            ]),
        ];
    }

    public function actionImport($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', null);
        $path = CourseMembersImport::pathFor(Yii::$app->request->post('token'), (string) $course->_id);
        if ($path === null)
            return $this->back('error', 'فایل بررسی‌شده پیدا نشد یا منقضی شده است؛ دوباره بارگذاری کنید', $course);
        $mutex = Yii::$app->has('mutex') ? Yii::$app->mutex : null;
        $lock = 'course-members-import-' . (string) $course->_id;
        if ($mutex !== null && !$mutex->acquire($lock, 10))
            return $this->back('error', 'ثبت دیگری برای این دوره در حال انجام است؛ چند لحظه بعد دوباره تلاش کنید', $course);
        try {
            $result = CourseMembersImport::import($path, $course);
        } finally {
            CourseMembersImport::discard();
            if ($mutex !== null)
                $mutex->release($lock);
        }
        return $this->back($result['ok'] ? (empty($result['failed']) ? 'success' : 'warning') : 'error', $result['message'], $course);
    }

    // ================================================================== اطلاعات مالی

    /**
     * اطلاعات مالی کامل یک عضو در همین دوره (نوع پرداخت، کانال، سهم واحد و کارگزار، اقساط) — JSON.
     */
    public function actionFinance($_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return ['title' => 'اطلاعات مالی', 'html' => Html::tag('div', 'دوره یافت نشد یا به آن دسترسی ندارید', ['class' => 'text-danger'])];
        $member = $this->findMember($course, Yii::$app->request->post('member'));
        if ($member === null)
            return ['title' => 'اطلاعات مالی', 'html' => Html::tag('div', 'این دانشپذیر عضو دوره نیست', ['class' => 'text-danger'])];
        $profile = new StudentProfile($member);
        return [
            'title' => 'اطلاعات مالی ' . trim($member->first_name . ' ' . $member->last_name),
            'html' => $this->renderPartial('@frontend/views/users-manage/profile/_finance', [
                'profile' => $profile,
                'orders' => $profile->ordersForCourse((string) $course->_id),
                'installments' => $profile->installmentsForCourse((string) $course->_id),
                'showCourse' => false,
            ]),
        ];
    }

    // ================================================================== وضعیت و کلاس آنلاین

    /**
     * فعال ↔ غیرفعال. غیرفعال: دسترسی کلاس آنلاین برداشته می‌شود؛ فعال‌سازی: دوباره در کلاس ثبت می‌شود.
     */
    public function actionStatus($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', null);
        $member = $this->findMember($course, Yii::$app->request->post('member'));
        if ($member === null)
            return $this->back('error', 'این دانشپذیر عضو دوره نیست', $course);
        $courseId = (string) $course->_id;
        $online = ClassroomPlatforms::hasOnlineClass($course);
        $courses = array_values((array) $member->courses);
        $previous = null;
        foreach ($courses as $i => $item) {
            if (!is_array($item) || !isset($item['_id']) || (string) $item['_id'] !== $courseId)
                continue;
            if (!empty($item['begin_deleted']))
                return $this->back('warning', 'برای این دانشپذیر درخواست انصراف ثبت شده است', $course);
            $previous = (string) (isset($item['status']) ? $item['status'] : '0');
            // غیرفعال → فعال: اگر کلاس آنلاین دارد ابتدا «ثبت‌نشده در کلاس» و بلافاصله ثبت در کلاس
            $courses[$i]['status'] = $previous === '1' ? '2' : ($online ? '0' : '1');
        }
        if ($previous === null)
            return $this->back('error', 'این دانشپذیر عضو دوره نیست', $course);
        $member->courses = $courses;
        if (!$member->save(false, ['courses']))
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید', $course);
        if ($online) {
            $platform = ClassroomPlatforms::forCourse($course);
            if ($previous === '1')
                $platform->removeCourseUser($member, $courseId);
            else
                $platform->registerCourseUsers($courseId);
        }
        return $this->back('success', $previous === '1' ? 'دانشپذیر غیرفعال شد' : 'دانشپذیر فعال شد', $course);
    }

    /** ثبت اعضای «ثبت‌نشده در کلاس» در سامانه‌ی کلاس آنلاین */
    public function actionRegisterClass($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', null);
        if (!ClassroomPlatforms::hasOnlineClass($course))
            return $this->back('info', 'این دوره در هیچ سروری کلاس آنلاین ندارد', $course);
        $ok = ClassroomPlatforms::forCourse($course)->registerCourseUsers((string) $course->_id);
        return $this->back($ok ? 'success' : 'error', $ok ? 'درخواست ثبت در کلاس آنلاین ارسال شد' : 'ارتباط با سرور کلاس آنلاین برقرار نشد', $course);
    }

    // ================================================================== حذف / انصراف

    /**
     * مدیر سیستم: حذف مستقیم (سهم واحد به کیف پول کارگزار برمی‌گردد و درخواست انصراف «تأییدشده» ثبت می‌شود).
     * کارشناس واحد و کارگزار: درخواست انصراف «در انتظار» که مدیر سیستم در بخش «درخواست انصراف» بررسی می‌کند.
     */
    public function actionCancel($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', null);
        $member = $this->findMember($course, Yii::$app->request->post('member'));
        if ($member === null)
            return $this->back('error', 'این دانشپذیر عضو دوره نیست', $course);
        $identity = Yii::$app->user->identity;
        $courseId = (string) $course->_id;
        $isAdmin = CourseAccess::isAdmin();
        $entry = self::entryOf($member->courses, $courseId);
        if (!$isAdmin && !empty($entry['begin_deleted']))
            return $this->back('info', 'درخواست انصراف این دانشپذیر قبلاً ثبت شده و در انتظار تأیید است', $course);
        if (!$isAdmin && CancelingRequests::find()->where(['username' => (string) $member->username, 'course_id' => $courseId, 'status' => '0'])->exists())
            return $this->back('info', 'درخواست انصراف این دانشپذیر قبلاً ثبت شده و در انتظار تأیید است', $course);

        $brokerId = is_array($course->broker) && !empty($course->broker['_id']) ? (string) $course->broker['_id'] : null;
        $order = null;
        foreach (Orders::find()->where(['username' => (string) $member->username, 'orders._id' => $courseId])->orderBy(['_id' => SORT_DESC])->all() as $candidate)
            if (StudentProfile::isSuccessfulOrder($candidate)) {
                $order = $candidate;
                break;
            }
        $unitShare = $order !== null && is_array($order->shares) && isset($order->shares[0]['college_share']) && is_numeric($order->shares[0]['college_share'])
            ? (float) $order->shares[0]['college_share'] : 0.0;

        $request = new CancelingRequests();
        $request->username = (string) $member->username;
        $request->course_id = $courseId;
        $request->order_id = $order !== null ? (string) $order->_id : null;
        $request->college = $course->college;
        $request->registrant = $identity->role === 'broker' ? $brokerId : (string) $identity->username;
        $request->registrant_role = (string) $identity->role;
        $request->request_date = UsersDirectory::jdate('Y/m/d', time(), 'en');
        $request->status = $isAdmin ? 'approved' : '0';
        if ($isAdmin)
            $request->edited_by = (string) $identity->username;
        $request->save(false);

        $courses = array_values((array) $member->courses);
        foreach ($courses as $i => $item)
            if (is_array($item) && isset($item['_id']) && (string) $item['_id'] === $courseId) {
                if ($isAdmin)
                    unset($courses[$i]);
                else
                    $courses[$i]['begin_deleted'] = true;
            }
        $member->courses = array_values($courses);
        $member->save(false, ['courses']);

        if (!$isAdmin)
            return $this->back('info', 'درخواست انصراف ثبت شد و پس از تأیید مدیر سیستم (بخش «درخواست انصراف») اعمال می‌شود', $course);

        // مدیر سیستم: بازگشت سهم واحد به کیف پول کارگزار
        if ($order !== null) {
            $order->is_canceled = true;
            $order->save(false, ['is_canceled']);
        }
        $broker = $brokerId !== null && preg_match('/^[a-f0-9]{24}$/i', $brokerId) ? Brokers::findOne($brokerId) : null;
        if ($broker !== null && $unitShare > 0 && CourseMembersImport::adjustWallet($broker, $unitShare)) {
            $transaction = new WalletTransactions();
            $transaction->amount = (string) round($unitShare);
            $transaction->broker_id = (string) $broker->_id;
            $transaction->college = (string) $course->college;
            $transaction->status = '1';
            $transaction->date = UsersDirectory::jdate('Y/m/d', time(), 'en');
            $transaction->type = '6';
            $transaction->reference_id = (string) $request->_id;
            $transaction->description = 'انصراف ' . $member->username . ' از دوره ' . (isset($course->title['main_fa']) ? $course->title['main_fa'] : '');
            $transaction->save(false);
        }
        if (ClassroomPlatforms::hasOnlineClass($course))
            ClassroomPlatforms::forCourse($course)->removeCourseUser($member, $courseId);
        return $this->back('success', 'دانشپذیر از دوره حذف شد' . ($unitShare > 0 && $broker !== null ? '؛ ' . UsersImport::faDigits(number_format($unitShare)) . ' تومان به کیف پول کارگزار برگشت' : ''), $course);
    }

    // ================================================================== کمکی

    /**
     * @return Courses|null فقط دوره‌ی کوتاه‌مدت یا میان‌مدت با شناسه‌ی معتبر
     */
    private function findCourse($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id))
            return null;
        $course = Courses::findOne($id);
        return $course !== null && in_array((string) $course->type, self::TYPES, true) ? $course : null;
    }

    /**
     * @return Users|null عضوِ همین دوره
     */
    private function findMember(Courses $course, $id)
    {
        return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)
            ? Users::find()->where(['_id' => $id, 'courses._id' => (string) $course->_id])->one()
            : null;
    }

    public static function entryOf($courses, $courseId)
    {
        foreach ((array) $courses as $item)
            if (is_array($item) && isset($item['_id']) && (string) $item['_id'] === (string) $courseId)
                return $item;
        return [];
    }

    /** آدرس تب «اعضا»ی صفحه‌ی دوره */
    public static function membersUrl(Courses $course, array $params = [])
    {
        $route = (string) $course->type === '2' ? 'packages/edit-package' : 'courses/edit-course';
        return array_merge([$route, '_id' => (string) $course->_id, 'tab' => 'tab-id2'], $params);
    }

    private function back($type, $message, $course)
    {
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect($course !== null ? self::membersUrl($course) : SafeRedirect::referrer(['courses/index']));
    }
}
