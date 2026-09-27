<?php

namespace app\components;

use app\models\Brokers;
use app\models\CertificateRequests;
use app\models\Courses;
use app\models\Exams;
use app\models\Installments;
use app\models\Lessons;
use app\models\Orders;
use app\models\Tests;
use app\models\UserAssignments;
use app\models\UserExams;
use app\models\Users;
use app\models\UserTests;

/**
 * داده‌های صفحه‌ی پروفایل دانشپذیر (docs/specs/users-manage.md بند ۵).
 * هر بخش یک بار و با کوئری‌های دسته‌ای بارگذاری و در همین شیء نگه داشته می‌شود.
 */
class StudentProfile
{
    /** @var Users */
    public $user;

    private $courses;
    private $orders;
    private $installments;
    private $certificates;

    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    /**
     * دوره‌های کاربر با جزئیات دوره.
     *
     * @return array[] هر مورد: ['row' => اندیس در users.courses, 'item' => آرایه‌ی ذخیره‌شده, 'course' => Courses|null]
     */
    public function courses()
    {
        if ($this->courses !== null)
            return $this->courses;
        $this->courses = [];
        $items = is_array($this->user->courses) ? $this->user->courses : [];
        $ids = [];
        foreach ($items as $item)
            if (isset($item['_id']) && preg_match('/^[a-f0-9]{24}$/i', (string) $item['_id']))
                $ids[] = (string) $item['_id'];
        $courses = [];
        if (!empty($ids))
            foreach (Courses::find()->where(['_id' => array_values(array_unique($ids))])->all() as $course)
                $courses[(string) $course->_id] = $course;
        foreach ($items as $row => $item) {
            $id = isset($item['_id']) ? (string) $item['_id'] : '';
            $this->courses[] = [
                'row' => $row,
                'item' => $item,
                'course' => isset($courses[$id]) ? $courses[$id] : null,
            ];
        }
        return $this->courses;
    }

    public static function courseTitle($course)
    {
        if ($course === null)
            return 'دوره حذف‌شده';
        return is_array($course->title) && isset($course->title['main_fa']) ? $course->title['main_fa'] : (string) $course->_id;
    }

    // ------------------------------------------------------------------ مالی

    /** @return Orders[] */
    public function orders()
    {
        if ($this->orders === null)
            $this->orders = Orders::find()->where(['username' => (string) $this->user->username])->orderBy(['_id' => SORT_DESC])->all();
        return $this->orders;
    }

    /** @return Orders[] سفارش‌هایی که شامل این دوره‌اند */
    public function ordersForCourse($courseId)
    {
        $result = [];
        foreach ($this->orders() as $order)
            if (is_array($order->orders))
                foreach ($order->orders as $item)
                    if (isset($item['_id']) && (string) $item['_id'] === (string) $courseId) {
                        $result[] = $order;
                        break;
                    }
        return $result;
    }

    /** @return Installments[] */
    public function installments()
    {
        if ($this->installments === null)
            $this->installments = Installments::find()->where(['username' => (string) $this->user->username])->all();
        return $this->installments;
    }

    /** @return Installments[] */
    public function installmentsForCourse($courseId)
    {
        $result = [];
        foreach ($this->installments() as $installment)
            if ((string) $installment->course_id === (string) $courseId)
                $result[] = $installment;
        return $result;
    }

    /**
     * مبلغ پرداخت‌شده‌ی یک سفارش (همان منطق ReportingController::actionReport).
     */
    public static function orderPaidAmount($order)
    {
        $amount = (float) $order->amount;
        if (!empty($order->payments) && is_array($order->payments)) {
            $i = 0;
            foreach ($order->payments as $payment) {
                if ($i++ != 0 && isset($payment['amount']))
                    $amount += (float) $payment['amount'];
            }
        } else {
            foreach (['prepayment_settlement', 'settlement_payment'] as $field) {
                $value = $order->$field;
                if (is_array($value) && array_key_exists('status', $value) && isset($value['amount']))
                    $amount += (float) $value['amount'];
            }
        }
        return $amount;
    }

    /** پرداخت موفق: status = '1' و لغو نشده */
    public static function isSuccessfulOrder($order)
    {
        return (string) $order->status === '1' && !$order->is_canceled;
    }

    public static function isInstallmentOrder($order)
    {
        return is_array($order->orders) && isset($order->orders[0]['payment_method']) && (string) $order->orders[0]['payment_method'] !== '1';
    }

    public static function isWalletOrder($order)
    {
        return is_array($order->payment_info) && isset($order->payment_info['order_id']) && $order->payment_info['order_id'] === 'wallet';
    }

    /**
     * نوع پرداخت: نقدی/اقساطی + کانال (درگاه، کیف پول کارگزار، کارتخوان).
     *
     * @return string[] [نوع, کانال]
     */
    public static function paymentType($order)
    {
        $type = self::isInstallmentOrder($order) ? 'اقساطی' : 'نقدی';
        if (self::isWalletOrder($order))
            $channel = 'کیف پول کارگزار';
        else if ($order->is_pos)
            $channel = 'کارتخوان';
        else
            $channel = 'درگاه پرداخت';
        return [$type, $channel];
    }

    /**
     * سهم دانشکده و کارگزار از یک سفارش (بر اساس shares[0]).
     *
     * @return array ['college' => مبلغ, 'broker' => مبلغ, 'percent' => درصد کارگزار, 'brokerName' => ...]
     */
    public static function shares($order)
    {
        $paid = self::orderPaidAmount($order);
        $share = is_array($order->shares) && isset($order->shares[0]) ? $order->shares[0] : [];
        $percent = isset($share['broker_contract_percent']) ? (float) $share['broker_contract_percent'] : 0;
        $brokerName = '';
        if (!empty($share['broker'])) {
            $broker = Brokers::findOne($share['broker']);
            if ($broker !== null && is_array($broker->connector_info))
                $brokerName = trim((isset($broker->connector_info['first_name']) ? $broker->connector_info['first_name'] : '') . ' ' . (isset($broker->connector_info['last_name']) ? $broker->connector_info['last_name'] : ''));
        }
        return [
            'college' => $paid * (100 - $percent) / 100,
            'broker' => $paid * $percent / 100,
            'percent' => $percent,
            'brokerName' => $brokerName,
            'collegeId' => isset($share['college']) ? (string) $share['college'] : '',
        ];
    }

    /**
     * وضعیت یک قسط: paid / overdue / pending.
     */
    public static function maturityState($maturity)
    {
        if (isset($maturity['status']) && (string) $maturity['status'] === '1')
            return 'paid';
        $deadline = isset($maturity['deadline']) ? preg_replace('/\D/', '', (string) $maturity['deadline']) : '';
        if ($deadline === '' && isset($maturity['date']))
            $deadline = preg_replace('/\D/', '', (string) $maturity['date']);
        if (strlen($deadline) === 8 && $deadline < UsersDirectory::todayJalaliKey())
            return 'overdue';
        return 'pending';
    }

    public static function maturityLabel($state)
    {
        $labels = ['paid' => ['پرداخت شده', 'success'], 'overdue' => ['سررسید گذشته', 'danger'], 'pending' => ['در انتظار سررسید', 'warning']];
        return $labels[$state];
    }

    /**
     * جمع پرداختی‌ها: سفارش‌های موفق + اقساط پرداخت‌شده.
     */
    public function totalPaid()
    {
        $total = 0;
        foreach ($this->orders() as $order)
            if (self::isSuccessfulOrder($order))
                $total += self::orderPaidAmount($order);
        foreach ($this->installments() as $installment)
            if (is_array($installment->maturities))
                foreach ($installment->maturities as $maturity)
                    if (self::maturityState($maturity) === 'paid' && isset($maturity['amount']))
                        $total += (float) $maturity['amount'];
        return $total;
    }

    /**
     * چک‌ها: اقساطِ پرونده‌های is_cheque.
     *
     * @return array[] ['installment' => Installments, 'maturity' => array, 'state' => ...]
     */
    public function cheques()
    {
        $result = [];
        foreach ($this->installments() as $installment) {
            if (!$installment->is_cheque || !is_array($installment->maturities))
                continue;
            foreach ($installment->maturities as $maturity)
                $result[] = ['installment' => $installment, 'maturity' => $maturity, 'state' => self::maturityState($maturity)];
        }
        return $result;
    }

    public function pendingChequesCount()
    {
        $count = 0;
        foreach ($this->cheques() as $cheque)
            if ($cheque['state'] !== 'paid')
                $count++;
        return $count;
    }

    // ------------------------------------------------------------------ مدرک

    /** @return CertificateRequests|null */
    public function certificate($courseId)
    {
        if ($this->certificates === null) {
            $this->certificates = [];
            foreach (CertificateRequests::find()->where(['username' => (string) $this->user->username])->all() as $request)
                $this->certificates[(string) $request->course_id] = $request;
        }
        return isset($this->certificates[(string) $courseId]) ? $this->certificates[(string) $courseId] : null;
    }

    public static function certificateStatus($status)
    {
        $labels = [
            '1' => ['در انتظار بررسی دانشکده', 'warning'],
            '2' => ['تائید دانشکده، در انتظار صدور', 'info'],
            '3' => ['رد دانشکده', 'danger'],
            '4' => ['صادر شده', 'success'],
            '5' => ['رد صدور مدرک', 'danger'],
        ];
        return isset($labels[(string) $status]) ? $labels[(string) $status] : ['نامشخص', 'secondary'];
    }

    // ------------------------------------------------------------ آزمون/تمرین/نظرسنجی

    /**
     * آزمون‌های کاربر در دوره با نمره‌ی نهایی (همان منطق exam_report).
     *
     * @return array[] ['title', 'lesson', 'score', 'pass', 'tries']
     */
    public function tests($courseId)
    {
        $items = UserTests::find()->where(['username' => (string) $this->user->username, 'course_id' => (string) $courseId])->all();
        $rows = [];
        foreach ($items as $item) {
            $test = $item->exam_id ? Tests::findOne($item->exam_id) : null;
            $tries = is_array($item->questions) ? $item->questions : [];
            $sum = 0;
            $max = 0;
            foreach ($tries as $try) {
                $score = isset($try['score']) ? (float) $try['score'] : 0;
                $sum += $score;
                $max = max($max, $score);
            }
            $score = null;
            if (count($tries) > 0)
                $score = ($test !== null && (string) $test->score_type === '1') ? $sum / count($tries) : $max;
            $rows[] = [
                'title' => $test !== null ? $test->title : 'آزمون',
                'lesson' => $this->lessonTitle($item->lesson_id),
                'score' => $score,
                'pass' => ($score !== null && $test !== null && $test->pass_score !== null) ? $score >= (float) $test->pass_score : null,
                'tries' => count($tries),
            ];
        }
        return $rows;
    }

    /** @return UserAssignments[] */
    public function assignments($courseId)
    {
        return UserAssignments::find()->where(['user_id' => (string) $this->user->_id, 'course_id' => (string) $courseId])->all();
    }

    /**
     * نظرسنجی‌های تکمیل‌شده (user_exams با exams.type = '2').
     *
     * @return array[] ['title', 'lesson', 'date']
     */
    public function surveys($courseId)
    {
        $rows = [];
        foreach (UserExams::find()->where(['user_id' => (string) $this->user->_id, 'course_id' => (string) $courseId])->all() as $item) {
            $exam = $item->exam_id ? Exams::findOne($item->exam_id) : null;
            if ($exam !== null && (string) $exam->type !== '2')
                continue;
            $rows[] = [
                'title' => $exam !== null ? $exam->title : 'نظرسنجی',
                'lesson' => $this->lessonTitle($item->lesson_id),
                'date' => hexdec(substr((string) $item->_id, 0, 8)),
            ];
        }
        return $rows;
    }

    private $lessonTitles = [];

    public function lessonTitle($lessonId)
    {
        $lessonId = (string) $lessonId;
        if ($lessonId === '' || !preg_match('/^[a-f0-9]{24}$/i', $lessonId))
            return '-';
        if (!array_key_exists($lessonId, $this->lessonTitles)) {
            $lesson = Lessons::findOne($lessonId);
            $this->lessonTitles[$lessonId] = $lesson !== null ? $lesson->title : '-';
        }
        return $this->lessonTitles[$lessonId];
    }
}
