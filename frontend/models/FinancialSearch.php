<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;

/**
 * InstallmentsReportSearch represents the model behind the search form of `app\models\Orders`.
 */
class FinancialSearch extends Orders
{
    public $tref;
    public $financial_check_location; // برای نمایش محل فیلد financial_check

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'first_name', 'last_name', 'tref', 'financial_check_location'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Orders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        require_once(Yii::$app->basePath . '/web/jdf.php');
        date_default_timezone_set('Asia/Tehran');

        $date1 = null;
        $date2 = null;
        $tref = isset($params['tref']) ? trim($params['tref']) : null;

        // تبدیل تاریخ‌ها
        if(isset($params['date1']) && $params['date1'] != '')
        {
            $d1 = $params['date1'];
            $d2 = jalali_to_gregorian(explode('-',$d1)[0],explode('-',$d1)[1],explode('-',$d1)[2]);
            $date1 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d2[0].'-'.$d2[1].'-'.$d2['2'])).'0000000000000000');
        }

        if(isset($params['date2']) && $params['date2'] != '')
        {
            $d1 = $params['date2'];
            $d2 = jalali_to_gregorian(explode('-',$d1)[0],explode('-',$d1)[1],explode('-',$d1)[2]);
            $date2 = new \MongoDB\BSON\ObjectId(dechex(strtotime($d2[0].'-'.$d2[1].'-'.$d2['2'])).'0000000000000000');
        }

        if (!$this->validate()) {
            return $dataProvider;
        }

        // *** شرط اصلی: فقط دیتاهایی که financial_check دارند ***
        $query->andWhere([
            '$or' => [
                ['financial_check' => true],
                ['payment_info.financial_check' => true],
                ['payments.financial_check' => true],
                ['settlement_payment.financial_check' => true],
                ['prepayment_settlement.financial_check' => true]
            ]
        ]);

        // شرط‌های فیلتر
        $query->andFilterWhere(['>=', '_id', $date1])
            ->andFilterWhere(['<=', '_id', $date2])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'first_name', $this->first_name])
            ->andFilterWhere(['like', 'last_name', $this->last_name])
            ->andWhere(['status' => '1'])
            ->andWhere(['orders.type' => ['1','2']]);

        // جستجوی tref - روش ساده‌تر
        if (!empty($tref)) {
            $query->andWhere([
                '$or' => [
                    ['payment_info.tref' => $tref],
                    ['payments.tref' => $tref],
                    ['settlement_payment.tref' => $tref],
                    ['prepayment_settlement.tref' => $tref]
                ]
            ]);
        }

        $query->orderBy(['_id' => SORT_DESC]);

        return $dataProvider;
    }

    /**
     * متد کمکی برای پیدا کردن محل financial_check در یک سند
     */
    public function getFinancialCheckLocation($order)
    {
        $locations = [];

        if (isset($order['financial_check']) && $order['financial_check'] === true) {
            $locations[] = 'root';
        }

        if (isset($order['payment_info']['financial_check']) && $order['payment_info']['financial_check'] === true) {
            $locations[] = 'payment_info';
        }

        if (isset($order['payments']) && is_array($order['payments'])) {
            foreach ($order['payments'] as $index => $payment) {
                if (isset($payment['financial_check']) && $payment['financial_check'] === true) {
                    $locations[] = "payments[$index]";
                }
            }
        }

        if (isset($order['settlement_payment']['financial_check']) && $order['settlement_payment']['financial_check'] === true) {
            $locations[] = 'settlement_payment';
        }

        if (isset($order['prepayment_settlement']['financial_check']) && $order['prepayment_settlement']['financial_check'] === true) {
            $locations[] = 'prepayment_settlement';
        }

        return empty($locations) ? null : implode(', ', $locations);
    }

    /**
     * متد کمکی برای گرفتن تاریخ financial_check
     */
    public function getFinancialCheckDate($order)
    {
        $dates = [];

        // بررسی در root
        if (isset($order['financial_check_date'])) {
            $dates[] = [
                'location' => 'root',
                'date' => $order['financial_check_date']
            ];
        }

        // بررسی در payment_info
        if (isset($order['payment_info']['financial_check_date'])) {
            $dates[] = [
                'location' => 'payment_info',
                'date' => $order['payment_info']['financial_check_date']
            ];
        }

        // بررسی در payments
        if (isset($order['payments']) && is_array($order['payments'])) {
            foreach ($order['payments'] as $index => $payment) {
                if (isset($payment['financial_check_date'])) {
                    $dates[] = [
                        'location' => "payments[$index]",
                        'date' => $payment['financial_check_date']
                    ];
                }
            }
        }

        // بررسی در settlement_payment
        if (isset($order['settlement_payment']['financial_check_date'])) {
            $dates[] = [
                'location' => 'settlement_payment',
                'date' => $order['settlement_payment']['financial_check_date']
            ];
        }

        // بررسی در prepayment_settlement
        if (isset($order['prepayment_settlement']['financial_check_date'])) {
            $dates[] = [
                'location' => 'prepayment_settlement',
                'date' => $order['prepayment_settlement']['financial_check_date']
            ];
        }

        return $dates;
    }
}