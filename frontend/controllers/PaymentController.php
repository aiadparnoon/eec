<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\mongodb\Collection;

class PaymentController extends Controller
{
    /**
     * محاسبه پرداخت‌های موفق با فیلتر تاریخ و بررسی تکراری بودن tref
     */
    public function actionGetSuccessfulPayments()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            // دریافت پارامترهای تاریخ از درخواست
            $request = Yii::$app->request;
            $startDate = $request->get('start_date', '1404/09/01');
            $endDate = $request->get('end_date', '1404/09/30');

            $collection = Yii::$app->mongodb->getCollection('orders');

            // خط تغییر یافته: فیلتر payment_info.order_id مخالف wallet
            $documents = $collection->find([
                'status' => '1',
            ]);

            $totalAmount = 0;
            $successfulPayments = [];
            $paymentDetails = [];
            $trefCollection = []; // برای جمع‌آوری تمام trefها

            foreach ($documents as $document) {
                $paymentData = $this->extractPaymentsFromDocument($document);

                if (!empty($paymentData['amounts'])) {
                    foreach ($paymentData['amounts'] as $payment) {
                        if ($this->isDateInRange($payment['date'] ?? '', $startDate, $endDate)) {
                            $amount = $payment['amount'];
                            $totalAmount += $amount;

                            $tref = $payment['tref'] ?? null;
                            $isTrefDuplicate = false;

                            // بررسی تکراری بودن tref
                            if ($tref) {
                                if (in_array($tref, $trefCollection)) {
                                    $isTrefDuplicate = true;
                                } else {
                                    $trefCollection[] = $tref;
                                }
                            }

                            $paymentDetails[] = [
                                'document_id' => (string)$document['_id'],
                                'username' => $document['username'] ?? '',
                                'first_name' => $document['first_name'] ?? '',
                                'last_name' => $document['last_name'] ?? '',
                                'payment_type' => $payment['type'],
                                'amount' => $amount,
                                'amount_formatted' => number_format($amount) . ' ریال',
                                'order_id' => $payment['order_id'] ?? '',
                                'date' => $payment['date'] ?? '',
                                'reference_id' => $payment['reference_id'] ?? '',
                                'tref' => $tref,
                                'is_tref_duplicate' => $isTrefDuplicate,
                                'tref_status' => $isTrefDuplicate ? 'تکراری' : 'منحصر به فرد'
                            ];
                        }
                    }

                    $successfulPayments[] = [
                        '_id' => (string)$document['_id'],
                        'username' => $document['username'] ?? '',
                        'total_payment' => $paymentData['total'],
                        'payment_count' => count($paymentData['amounts']),
                        'details' => $paymentData['details']
                    ];
                }
            }

            // تحلیل trefهای تکراری
            $trefAnalysis = $this->analyzeTrefDuplicates($paymentDetails);

            return [
                'success' => true,
                'filters' => [
                    'date_range' => "از {$startDate} تا {$endDate}",
                    'status' => '1 (پرداخت موفق)',
                    'payment_info_filter' => 'order_id != wallet'
                ],
                'summary' => [
                    'total_amount' => (int)$totalAmount,
                    'total_amount_formatted' => number_format($totalAmount) . ' ریال',
                    'total_payments_count' => count($paymentDetails),
                    'total_documents_count' => count($successfulPayments)
                ],
                'tref_analysis' => $trefAnalysis,
                'payment_details' => $paymentDetails,
                'documents' => $successfulPayments
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در پردازش اطلاعات: ' . $e->getMessage()
            ];
        }
    }

    /**
     * استخراج پرداخت‌ها از یک سند با رعایت دقیق قوانین
     */
    private function extractPaymentsFromDocument($document)
    {
        $amounts = [];
        $details = [];
        $total = 0;

        // قانون ۴: اولویت با آرایه payments
        if (isset($document['payments']) &&
            is_array($document['payments']) &&
            !empty(array_filter($document['payments'], function($p) {
                return isset($p['status']) && $p['status'] === '1';
            }))) {

            foreach ($document['payments'] as $payment) {
                if (isset($payment['status']) && $payment['status'] === '1' && isset($payment['amount'])) {
                    $amount = (int)$payment['amount'];
                    $total += $amount;

                    $amounts[] = [
                        'type' => 'payments_array',
                        'amount' => $amount,
                        'order_id' => $payment['order_id'] ?? '',
                        'date' => $payment['date'] ?? '',
                        'clean_date' => $payment['clean_date'] ?? '',
                        'reference_id' => $payment['reference_id'] ?? '',
                        'tref' => $payment['tref'] ?? ''
                    ];

                    $details[] = [
                        'source' => 'payments array',
                        'amount' => $amount,
                        'order_id' => $payment['order_id'] ?? '',
                        'date' => $payment['date'] ?? '',
                        'tref' => $payment['tref'] ?? ''
                    ];
                }
            }

            return [
                'amounts' => $amounts,
                'details' => $details,
                'total' => $total
            ];
        }

        // قانون ۵: بررسی prepayment_settlement و settlement_payment
        $hasPrepayment = isset($document['prepayment_settlement']) &&
            is_array($document['prepayment_settlement']) &&
            !empty($document['prepayment_settlement']);

        $hasSettlement = isset($document['settlement_payment']) &&
            is_array($document['settlement_payment']) &&
            !empty($document['settlement_payment']);

        if ($hasPrepayment || $hasSettlement) {
            // بررسی prepayment_settlement
            if ($hasPrepayment &&
                isset($document['prepayment_settlement']['status']) &&
                $document['prepayment_settlement']['status'] === '1' &&
                isset($document['prepayment_settlement']['amount'])) {

                $amount = (int)$document['prepayment_settlement']['amount'];
                $total += $amount;

                $amounts[] = [
                    'type' => 'prepayment_settlement',
                    'amount' => $amount,
                    'order_id' => $document['prepayment_settlement']['order_id'] ?? '',
                    'date' => $document['prepayment_settlement']['date'] ?? '',
                    'clean_date' => $document['prepayment_settlement']['clean_date'] ?? '',
                    'reference_id' => $document['prepayment_settlement']['reference_id'] ?? '',
                    'tref' => $document['prepayment_settlement']['tref'] ?? ''
                ];

                $details[] = [
                    'source' => 'prepayment_settlement',
                    'amount' => $amount,
                    'order_id' => $document['prepayment_settlement']['order_id'] ?? '',
                    'date' => $document['prepayment_settlement']['date'] ?? '',
                    'tref' => $document['prepayment_settlement']['tref'] ?? ''
                ];
            }

            // بررسی settlement_payment
            if ($hasSettlement &&
                isset($document['settlement_payment']['status']) &&
                $document['settlement_payment']['status'] === '1' &&
                isset($document['settlement_payment']['amount'])) {

                $amount = (int)$document['settlement_payment']['amount'];
                $total += $amount;

                $amounts[] = [
                    'type' => 'settlement_payment',
                    'amount' => $amount,
                    'order_id' => $document['settlement_payment']['order_id'] ?? '',
                    'date' => $document['settlement_payment']['date'] ?? '',
                    'clean_date' => $document['settlement_payment']['clean_date'] ?? '',
                    'reference_id' => $document['settlement_payment']['reference_id'] ?? '',
                    'tref' => $document['settlement_payment']['tref'] ?? ''
                ];

                $details[] = [
                    'source' => 'settlement_payment',
                    'amount' => $amount,
                    'order_id' => $document['settlement_payment']['order_id'] ?? '',
                    'date' => $document['settlement_payment']['date'] ?? '',
                    'tref' => $document['settlement_payment']['tref'] ?? ''
                ];
            }

            return [
                'amounts' => $amounts,
                'details' => $details,
                'total' => $total
            ];
        }

        // قانون اصلاح شده: استفاده از amount در روت داکیومنت
        // نه از payment_info

        // بررسی اینکه آیا payments خالی است یا وجود ندارد
        $hasPayments = isset($document['payments']) && is_array($document['payments']) && !empty($document['payments']);

        // اگر payments وجود ندارد یا خالی است، از amount در روت استفاده کن
        if (!$hasPayments && isset($document['amount'])) {
            $amount = (int)$document['amount'];
            $total += $amount;

            // استفاده از payment_info برای اطلاعات تکمیلی (اگر وجود دارد)
            $orderId = '';
            $date = '';
            $referenceId = '';
            $trefValue = '';

            if (isset($document['payment_info']) &&
                isset($document['payment_info']['status']) &&
                $document['payment_info']['status'] === '1') {
                $orderId = $document['payment_info']['order_id'] ?? '';
                $date = $document['payment_info']['date'] ?? '';
                $referenceId = $document['payment_info']['reference_id'] ?? '';
                $trefValue = $document['payment_info']['tref'] ?? '';
            }

            $amounts[] = [
                'type' => 'root_amount',
                'amount' => $amount,
                'order_id' => $orderId,
                'date' => $date,
                'clean_date' => isset($document['payment_info']['clean_date']) ? $document['payment_info']['clean_date'] : '',
                'reference_id' => $referenceId,
                'tref' => $trefValue
            ];

            $details[] = [
                'source' => 'root amount field',
                'amount' => $amount,
                'order_id' => $orderId,
                'date' => $date,
                'tref' => $trefValue
            ];
        }

        return [
            'amounts' => $amounts,
            'details' => $details,
            'total' => $total
        ];
    }

    /**
     * بررسی اینکه تاریخ در بازه مورد نظر باشد
     */
    private function isDateInRange($date, $startDate, $endDate)
    {
        if (empty($date)) {
            return false;
        }

        $dateFormatted = str_replace('/', '', $date);
        $startFormatted = str_replace('/', '', $startDate);
        $endFormatted = str_replace('/', '', $endDate);

        return ($dateFormatted >= $startFormatted && $dateFormatted <= $endFormatted);
    }

    /**
     * تحلیل trefهای تکراری
     */
    private function analyzeTrefDuplicates($paymentDetails)
    {
        // جمع‌آوری همه trefها
        $trefs = [];
        foreach ($paymentDetails as $payment) {
            if (!empty($payment['tref'])) {
                $trefs[] = $payment['tref'];
            }
        }

        // شمارش فراوانی هر tref
        $trefCounts = array_count_values($trefs);

        // جدا کردن trefهای تکراری
        $uniqueTrefs = [];
        $duplicateTrefs = [];

        foreach ($trefCounts as $tref => $count) {
            if ($count > 1) {
                $duplicateTrefs[$tref] = $count;
            } else {
                $uniqueTrefs[] = $tref;
            }
        }

        // پیدا کردن پرداخت‌های مربوط به هر tref تکراری
        $duplicatePaymentsDetails = [];
        foreach ($duplicateTrefs as $tref => $count) {
            $paymentsForThisTref = [];
            $totalAmountForTref = 0;

            foreach ($paymentDetails as $payment) {
                if ($payment['tref'] === $tref) {
                    $paymentsForThisTref[] = [
                        'document_id' => $payment['document_id'],
                        'username' => $payment['username'],
                        'name' => $payment['first_name'] . ' ' . $payment['last_name'],
                        'amount' => $payment['amount'],
                        'payment_type' => $payment['payment_type'],
                        'date' => $payment['date']
                    ];
                    $totalAmountForTref += $payment['amount'];
                }
            }

            $duplicatePaymentsDetails[] = [
                'tref' => $tref,
                'duplicate_count' => $count,
                'total_amount' => $totalAmountForTref,
                'total_amount_formatted' => number_format($totalAmountForTref) . ' ریال',
                'payments' => $paymentsForThisTref
            ];
        }

        return [
            'total_trefs' => count($trefs),
            'unique_trefs_count' => count($uniqueTrefs),
            'duplicate_trefs_count' => count($duplicateTrefs),
            'duplicate_trefs_list' => array_keys($duplicateTrefs),
            'duplicate_trefs_details' => $duplicatePaymentsDetails,
            'summary' => [
                'unique_percentage' => count($trefs) > 0 ?
                    round((count($uniqueTrefs) / count($trefs)) * 100, 2) : 0,
                'duplicate_percentage' => count($trefs) > 0 ?
                    round((count($duplicateTrefs) / count($trefs)) * 100, 2) : 0
            ]
        ];
    }

    // باقی متدها همانند قبل هستند...

    /**
     * اکشن ویژه برای بررسی trefهای تکراری
     */
    public function actionCheckDuplicateTrefs()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $request = Yii::$app->request;
            $startDate = $request->get('start_date', '1404/01/01');
            $endDate = $request->get('end_date', '1404/09/30');

            $collection = Yii::$app->mongodb->getCollection('orders');

            // اعمال فیلتر مشابه
            $documents = $collection->find([
                'status' => '1',
                '$or' => [
                    ['payment_info' => ['$exists' => false]],
                    ['payment_info.order_id' => ['$ne' => 'wallet']],
                    ['payment_info.order_id' => ['$exists' => false]]
                ]
            ]);

            $allTrefs = [];
            $trefSources = []; // برای ذخیره اینکه هر tref از کجا آمده

            foreach ($documents as $document) {
                $paymentData = $this->extractPaymentsFromDocument($document);

                foreach ($paymentData['amounts'] as $payment) {
                    if ($this->isDateInRange($payment['date'] ?? '', $startDate, $endDate)) {
                        $tref = $payment['tref'] ?? null;

                        if ($tref) {
                            $allTrefs[] = $tref;

                            // ذخیره اطلاعات منبع tref
                            if (!isset($trefSources[$tref])) {
                                $trefSources[$tref] = [];
                            }

                            $trefSources[$tref][] = [
                                'document_id' => (string)$document['_id'],
                                'username' => $document['username'] ?? '',
                                'name' => ($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? ''),
                                'payment_type' => $payment['type'],
                                'amount' => $payment['amount'],
                                'date' => $payment['date'],
                                'order_id' => $payment['order_id'] ?? ''
                            ];
                        }
                    }
                }
            }

            // شمارش فراوانی trefها
            $trefCounts = array_count_values($allTrefs);

            // جدا کردن trefها
            $uniqueTrefs = [];
            $duplicateTrefs = [];

            foreach ($trefCounts as $tref => $count) {
                if ($count > 1) {
                    $duplicateTrefs[$tref] = [
                        'count' => $count,
                        'sources' => $trefSources[$tref] ?? []
                    ];
                } else {
                    $uniqueTrefs[] = $tref;
                }
            }

            // محاسبه آمار
            $totalTrefs = count($allTrefs);
            $totalUnique = count($uniqueTrefs);
            $totalDuplicate = count($duplicateTrefs);

            // یافتن trefهایی که بیشترین تکرار را دارند
            $mostFrequentTrefs = [];
            foreach ($duplicateTrefs as $tref => $data) {
                $mostFrequentTrefs[] = [
                    'tref' => $tref,
                    'duplicate_count' => $data['count'],
                    'sources_count' => count($data['sources'])
                ];
            }

            // مرتب کردن بر اساس تعداد تکرار
            usort($mostFrequentTrefs, function($a, $b) {
                return $b['duplicate_count'] - $a['duplicate_count'];
            });

            // فقط ۱۰ مورد اول
            $mostFrequentTrefs = array_slice($mostFrequentTrefs, 0, 10);

            return [
                'success' => true,
                'filters' => [
                    'date_range' => "از {$startDate} تا {$endDate}",
                    'status' => '1',
                    'payment_info_filter' => 'order_id != wallet'
                ],
                'statistics' => [
                    'total_trefs' => $totalTrefs,
                    'unique_trefs' => $totalUnique,
                    'duplicate_trefs' => $totalDuplicate,
                    'unique_percentage' => $totalTrefs > 0 ? round(($totalUnique / $totalTrefs) * 100, 2) : 0,
                    'duplicate_percentage' => $totalTrefs > 0 ? round(($totalDuplicate / $totalTrefs) * 100, 2) : 0
                ],
                'duplicate_trefs_details' => $duplicateTrefs,
                'most_frequent_duplicates' => $mostFrequentTrefs,
                'analysis' => $this->analyzeTrefPatterns($duplicateTrefs)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در بررسی trefهای تکراری: ' . $e->getMessage()
            ];
        }
    }

    /**
     * تحلیل الگوهای tref تکراری
     */
    private function analyzeTrefPatterns($duplicateTrefs)
    {
        $analysis = [
            'by_payment_type' => [],
            'by_amount_range' => [],
            'by_month' => []
        ];

        foreach ($duplicateTrefs as $tref => $data) {
            foreach ($data['sources'] as $source) {
                // تحلیل بر اساس نوع پرداخت
                $paymentType = $source['payment_type'];
                if (!isset($analysis['by_payment_type'][$paymentType])) {
                    $analysis['by_payment_type'][$paymentType] = 0;
                }
                $analysis['by_payment_type'][$paymentType]++;

                // تحلیل بر اساس محدوده مبلغ
                $amount = $source['amount'];
                $range = $this->getAmountRange($amount);
                if (!isset($analysis['by_amount_range'][$range])) {
                    $analysis['by_amount_range'][$range] = 0;
                }
                $analysis['by_amount_range'][$range]++;

                // تحلیل بر اساس ماه
                if (!empty($source['date'])) {
                    $month = substr(str_replace('/', '', $source['date']), 4, 2);
                    if (!isset($analysis['by_month'][$month])) {
                        $analysis['by_month'][$month] = 0;
                    }
                    $analysis['by_month'][$month]++;
                }
            }
        }

        // مرتب کردن
        arsort($analysis['by_payment_type']);
        arsort($analysis['by_amount_range']);
        arsort($analysis['by_month']);

        return $analysis;
    }

    /**
     * تعیین محدوده مبلغ
     */
    private function getAmountRange($amount)
    {
        if ($amount < 1000000) {
            return 'زیر ۱ میلیون';
        } elseif ($amount < 5000000) {
            return '۱ تا ۵ میلیون';
        } elseif ($amount < 10000000) {
            return '۵ تا ۱۰ میلیون';
        } elseif ($amount < 50000000) {
            return '۱۰ تا ۵۰ میلیون';
        } else {
            return 'بالای ۵۰ میلیون';
        }
    }

    /**
     * پیدا کردن اسناد بر اساس tref
     */
    public function actionFindByTref($tref)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $collection = Yii::$app->mongodb->getCollection('orders');

            // جستجو در تمام فیلدهای ممکن برای tref
            $documents = $collection->find([
                'status' => '1',
                '$or' => [
                    ['payment_info.tref' => $tref],
                    ['payments.tref' => $tref],
                    ['prepayment_settlement.tref' => $tref],
                    ['settlement_payment.tref' => $tref]
                ]
            ]);

            $results = [];

            foreach ($documents as $document) {
                $paymentData = $this->extractPaymentsFromDocument($document);

                // فقط پرداخت‌هایی که tref مطابقت دارد
                $matchingPayments = [];
                foreach ($paymentData['amounts'] as $payment) {
                    if (($payment['tref'] ?? '') === $tref) {
                        $matchingPayments[] = $payment;
                    }
                }

                if (!empty($matchingPayments)) {
                    $results[] = [
                        '_id' => (string)$document['_id'],
                        'username' => $document['username'] ?? '',
                        'name' => ($document['first_name'] ?? '') . ' ' . ($document['last_name'] ?? ''),
                        'tref' => $tref,
                        'matching_payments' => $matchingPayments,
                        'total_amount' => array_sum(array_column($matchingPayments, 'amount')),
                        'payment_count' => count($matchingPayments)
                    ];
                }
            }

            return [
                'success' => true,
                'tref' => $tref,
                'found_count' => count($results),
                'results' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در جستجوی tref: ' . $e->getMessage()
            ];
        }
    }
}