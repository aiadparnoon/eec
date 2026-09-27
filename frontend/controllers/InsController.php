<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\mongodb\Collection;

class InsController extends Controller
{
    /**
     * دریافت پرداخت‌های موفق بر اساس تاریخ و status = 1
     * تاریخ: از 1 آذر 1404 تا 29 آذر 1404
     * status: باید برابر 1 باشد
     */
    public function actionGet()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // اتصال به collection
            $collection = Yii::$app->mongodb->getCollection('installments'); // نام collection را تنظیم کنید

            // تاریخ شروع و پایان
            $startDate = '1404/09/01'; // 1 آذر 1404 (ماه 9)
            $endDate = '1404/09/29';   // 29 آذر 1404

            // کوئری برای پیدا کردن اسنادی که در بازه تاریخ مورد نظر هستند
            // و status = 1 دارند (در هر سطحی)
            $documents = $collection->find();

            $results = [];
            $totalAmount = 0;

            foreach ($documents as $document) {
                $payments = $this->extractPaymentsByStatusAndDate($document, $startDate, $endDate);

                if (!empty($payments)) {
                    $documentTotal = array_sum(array_column($payments, 'amount'));
                    $totalAmount += $documentTotal;

                    $results[] = [
                        '_id' => (string)$document['_id'],
                        'username' => $document['username'] ?? '',
                        'name' => ($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? ''),
                        'status' => $document['status'] ?? '',
                        'total_amount' => $documentTotal,
                        'total_amount_formatted' => number_format($documentTotal) . ' ریال',
                        'payment_count' => count($payments),
                        'payments' => $payments,
                        'document_details' => [
                            'course_id' => $document['course_id'] ?? '',
                            'order_id' => $document['order_id'] ?? '',
                            'college' => $document['college'] ?? '',
                            'is_cheque' => $document['is_cheque'] ?? false,
                            'createdAt' => isset($document['createdAt']['$date']) ?
                                date('Y-m-d H:i:s', strtotime($document['createdAt']['$date'])) : '',
                            'updatedAt' => isset($document['updatedAt']['$date']) ?
                                date('Y-m-d H:i:s', strtotime($document['updatedAt']['$date'])) : ''
                        ]
                    ];
                }
            }

            return [
                'success' => true,
                'filters' => [
                    'date_range' => 'از 1 آذر 1404 تا 29 آذر 1404',
                    'status_condition' => 'status = 1',
                    'search_fields' => [
                        'maturities.status',
                        'document.status'
                    ]
                ],
                'summary' => [
                    'total_documents' => count($results),
                    'total_payments' => array_sum(array_column($results, 'payment_count')),
                    'total_amount' => $totalAmount,
                    'total_amount_formatted' => number_format($totalAmount) . ' ریال'
                ],
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در پردازش اطلاعات: ' . $e->getMessage()
            ];
        }
    }

    /**
     * استخراج پرداخت‌ها بر اساس status = 1 و تاریخ
     */
    private function extractPaymentsByStatusAndDate($document, $startDate, $endDate)
    {
        $payments = [];

        // 1. بررسی فیلد maturities (اگر وجود دارد)
        if (isset($document['maturities']) && is_array($document['maturities'])) {
            foreach ($document['maturities'] as $maturity) {
                // بررسی status = 1
                if (isset($maturity['status']) && $maturity['status'] === '1') {
                    // بررسی تاریخ در بازه مورد نظر
                    if ($this->isDateInRange($maturity['date'] ?? '', $startDate, $endDate)) {
                        $payments[] = [
                            'type' => 'maturity',
                            'amount' => (int)($maturity['amount'] ?? 0),
                            'date' => $maturity['date'] ?? '',
                            'deadline' => $maturity['deadline'] ?? '',
                            'serial' => $maturity['serial'] ?? '',
                            'status' => $maturity['status'] ?? '',
                            'source' => 'maturities array'
                        ];
                    }
                }
            }
        }

        // 2. بررسی سایر فیلدهای ممکن در ساختار
        // اگر ساختار متفاوتی دارید، اینجا اضافه کنید

        return $payments;
    }

    /**
     * بررسی اینکه تاریخ در بازه مورد نظر باشد
     */
    private function isDateInRange($date, $startDate, $endDate)
    {
        if (empty($date)) {
            return false;
        }

        // حذف اسلش‌ها برای مقایسه
        $dateFormatted = str_replace('/', '', $date);
        $startFormatted = str_replace('/', '', $startDate);
        $endFormatted = str_replace('/', '', $endDate);

        return ($dateFormatted >= $startFormatted && $dateFormatted <= $endFormatted);
    }

    /**
     * کوئری پیشرفته‌تر با استفاده از MongoDB aggregation
     */
    public function actionGetPaymentsAdvanced()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $collection = Yii::$app->mongodb->getCollection('payments');

            // تاریخ‌ها
            $startJalali = '14040901'; // 1 آذر 1404
            $endJalali = '14040929';   // 29 آذر 1404

            // Aggregation pipeline
            $pipeline = [
                // مرحله 1: فیلتر اسنادی که maturities دارند
                [
                    '$match' => [
                        'maturities' => ['$exists' => true],
                        '$or' => [
                            // یا status کلی سند برابر 1 است
                            ['status' => '1'],
                            // یا حداقل یکی از maturities.status برابر 1 است
                            ['maturities.status' => '1']
                        ]
                    ]
                ],
                // مرحله 2: unwind کردن maturities
                [
                    '$unwind' => '$maturities'
                ],
                // مرحله 3: فیلتر maturities با status = 1 و تاریخ در بازه مورد نظر
                [
                    '$match' => [
                        'maturities.status' => '1',
                        'maturities.deadline' => [
                            '$gte' => $startJalali,
                            '$lte' => $endJalali
                        ]
                    ]
                ],
                // مرحله 4: group کردن نتایج
                [
                    '$group' => [
                        '_id' => '$_id',
                        'document' => ['$first' => '$$ROOT'],
                        'total_amount' => ['$sum' => '$maturities.amount'],
                        'payments' => ['$push' => '$maturities'],
                        'payment_count' => ['$sum' => 1]
                    ]
                ],
                // مرحله 5: projection برای خروجی تمیز
                [
                    '$project' => [
                        '_id' => 1,
                        'username' => '$document.username',
                        'first_name' => '$document.first_name',
                        'last_name' => '$document.last_name',
                        'status' => '$document.status',
                        'course_id' => '$document.course_id',
                        'order_id' => '$document.order_id',
                        'college' => '$document.college',
                        'is_cheque' => '$document.is_cheque',
                        'total_amount' => 1,
                        'payment_count' => 1,
                        'payments' => 1,
                        'createdAt' => '$document.createdAt',
                        'updatedAt' => '$document.updatedAt'
                    ]
                ]
            ];

            $cursor = $collection->aggregate($pipeline);

            $results = [];
            $summary = [
                'total_documents' => 0,
                'total_payments' => 0,
                'total_amount' => 0
            ];

            foreach ($cursor as $doc) {
                $summary['total_documents']++;
                $summary['total_payments'] += $doc['payment_count'];
                $summary['total_amount'] += (int)$doc['total_amount'];

                // تبدیل تاریخ‌ها
                $paymentsFormatted = [];
                foreach ($doc['payments'] as $payment) {
                    $paymentsFormatted[] = [
                        'amount' => (int)$payment['amount'],
                        'amount_formatted' => number_format($payment['amount']) . ' ریال',
                        'date' => $payment['date'] ?? '',
                        'deadline' => $payment['deadline'] ?? '',
                        'serial' => $payment['serial'] ?? '',
                        'status' => $payment['status'] ?? ''
                    ];
                }

                $results[] = [
                    '_id' => (string)$doc['_id'],
                    'username' => $doc['username'] ?? '',
                    'name' => ($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? ''),
                    'status' => $doc['status'] ?? '',
                    'total_amount' => (int)$doc['total_amount'],
                    'total_amount_formatted' => number_format($doc['total_amount']) . ' ریال',
                    'payment_count' => $doc['payment_count'],
                    'payments' => $paymentsFormatted,
                    'metadata' => [
                        'course_id' => $doc['course_id'] ?? '',
                        'order_id' => $doc['order_id'] ?? '',
                        'college' => $doc['college'] ?? '',
                        'is_cheque' => $doc['is_cheque'] ?? false
                    ]
                ];
            }

            return [
                'success' => true,
                'filters' => [
                    'date_range' => 'از 1 آذر 1404 تا 29 آذر 1404',
                    'status' => '1',
                    'field' => 'maturities.status',
                    'date_field' => 'maturities.deadline'
                ],
                'summary' => [
                    'total_documents' => $summary['total_documents'],
                    'total_payments' => $summary['total_payments'],
                    'total_amount' => $summary['total_amount'],
                    'total_amount_formatted' => number_format($summary['total_amount']) . ' ریال'
                ],
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در اجرای کوئری: ' . $e->getMessage()
            ];
        }
    }

    /**
     * کوئری ساده‌تر برای دریافت پرداخت‌ها
     */
    public function actionGetSimplePayments()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $collection = Yii::$app->mongodb->getCollection('payments');

            // کوئری ساده: اسنادی که status = 1 دارند
            // و در maturities حداقل یک آیتم با status = 1 دارند
            $documents = $collection->find([
                '$or' => [
                    ['status' => '1'],
                    ['maturities.status' => '1']
                ]
            ]);

            $results = [];
            $stats = [
                'total_documents' => 0,
                'documents_with_status_1' => 0,
                'documents_with_maturity_status_1' => 0,
                'total_amount' => 0
            ];

            foreach ($documents as $document) {
                $stats['total_documents']++;

                $documentStatus = $document['status'] ?? '';
                $hasMaturityStatus1 = false;
                $payments = [];

                // بررسی maturities
                if (isset($document['maturities']) && is_array($document['maturities'])) {
                    foreach ($document['maturities'] as $maturity) {
                        if (isset($maturity['status']) && $maturity['status'] === '1') {
                            $hasMaturityStatus1 = true;

                            // بررسی تاریخ (آذر 1404)
                            $deadline = $maturity['deadline'] ?? '';
                            if ($this->isJalaliDateInMonth($deadline, 9, 1404)) { // ماه 9 = آذر
                                $payments[] = [
                                    'amount' => (int)($maturity['amount'] ?? 0),
                                    'date' => $maturity['date'] ?? '',
                                    'deadline' => $deadline,
                                    'serial' => $maturity['serial'] ?? ''
                                ];
                            }
                        }
                    }
                }

                if ($documentStatus === '1') {
                    $stats['documents_with_status_1']++;
                }

                if ($hasMaturityStatus1) {
                    $stats['documents_with_maturity_status_1']++;
                }

                if (!empty($payments)) {
                    $documentTotal = array_sum(array_column($payments, 'amount'));
                    $stats['total_amount'] += $documentTotal;

                    $results[] = [
                        '_id' => (string)$document['_id'],
                        'username' => $document['username'] ?? '',
                        'name' => ($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? ''),
                        'document_status' => $documentStatus,
                        'has_maturity_status_1' => $hasMaturityStatus1,
                        'total_amount' => $documentTotal,
                        'payment_count' => count($payments),
                        'payments' => $payments
                    ];
                }
            }

            return [
                'success' => true,
                'statistics' => $stats,
                'filter_description' => 'اسنادی که status = 1 دارند یا در maturities status = 1 دارند',
                'date_filter' => 'ماه آذر 1404',
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا: ' . $e->getMessage()
            ];
        }
    }

    /**
     * بررسی اینکه تاریخ شمسی در ماه و سال مشخص باشد
     */
    private function isJalaliDateInMonth($cleanDate, $month, $year)
    {
        if (empty($cleanDate) || strlen($cleanDate) !== 8) {
            return false;
        }

        $dateYear = (int)substr($cleanDate, 0, 4);
        $dateMonth = (int)substr($cleanDate, 4, 2);

        return ($dateYear === $year && $dateMonth === $month);
    }

    /**
     * دریافت پرداخت‌ها با امکان فیلتر پارامتری
     */
    public function actionGetFilteredPayments($startDate = '14040901', $endDate = '14040929', $status = '1')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $collection = Yii::$app->mongodb->getCollection('payments');

            // فیلترهای پارامتری
            $startJalali = $startDate;
            $endJalali = $endDate;

            $documents = $collection->find([
                'maturities' => [
                    '$elemMatch' => [
                        'status' => $status,
                        'deadline' => [
                            '$gte' => $startJalali,
                            '$lte' => $endJalali
                        ]
                    ]
                ]
            ]);

            $results = [];

            foreach ($documents as $document) {
                $filteredMaturities = [];

                foreach ($document['maturities'] as $maturity) {
                    if (isset($maturity['status']) &&
                        $maturity['status'] === $status &&
                        isset($maturity['deadline']) &&
                        $maturity['deadline'] >= $startJalali &&
                        $maturity['deadline'] <= $endJalali) {

                        $filteredMaturities[] = $maturity;
                    }
                }

                if (!empty($filteredMaturities)) {
                    $totalAmount = array_sum(array_column($filteredMaturities, 'amount'));

                    $results[] = [
                        '_id' => (string)$document['_id'],
                        'username' => $document['username'],
                        'name' => ($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? ''),
                        'filtered_maturities' => $filteredMaturities,
                        'total_amount' => $totalAmount,
                        'maturity_count' => count($filteredMaturities)
                    ];
                }
            }

            return [
                'success' => true,
                'parameters' => [
                    'start_date' => $startJalali,
                    'end_date' => $endJalali,
                    'status' => $status
                ],
                'results_count' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در فیلتر کردن: ' . $e->getMessage()
            ];
        }
    }
}