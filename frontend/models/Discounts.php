<?php

namespace app\models;

use Yii;
use app\models\Courses;
use app\models\Brokers;

/**
 * This is the model class for collection "Discounts".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $id
 * @property string $name
 * @property string $province_id
 */
class Discounts extends \yii\mongodb\ActiveRecord
{
    /**
     * حداقل فاصله‌ای که مبلغ کد تخفیف باید از سهم کارگزار کمتر باشه (تومان).
     * طبق درخواست ۲۰۲۶-۰۸-۲۸: صرفِ کمتر یا مساوی بودن با سهم کارگزار کافی
     * نیست، باید حداقل به این مقدار از سهم کارگزار فاصله داشته باشه.
     */
    const MIN_MARGIN_BELOW_BROKER_SHARE = 50000;

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'discounts'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'course_id',
            'amount',
            'count',
            'code',
            'used',
            'registrant'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // «افزودن کد تخفیف» توی edit-package.php - تغییر ۹ (2026-08-28):
            // مبلغ و تعداد هر دو الزامی‌ان و فقط عدد انگلیسی قبول می‌کنن (عدد
            // فارسی/عربی رد می‌شه) - همون قاعده‌ای که برای price/discount_price
            // توی Courses::rules() استفاده شده.
            [['amount', 'count'], 'required', 'message' => 'این فیلد الزامی است'],
            [['amount', 'count'], 'match', 'pattern' => '/^[0-9]+$/',
                'message' => 'فقط اعداد انگلیسی مجاز است (بدون حروف، ممیز، یا اعداد فارسی/عربی)'],
            // تغییر ۱۰: مبلغ کد تخفیف نباید از سهم کارگزار بیشتر باشه.
            ['amount', 'validateAmountNotAboveBrokerShare'],
            [[
                'course_id',
                'code',
                'used',
                'registrant'
            ], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'amount' => 'مبلغ کد تخفیف',
            'count' => 'تعداد',
        ];
    }

    /**
     * Inline validator (تغییر ۱۰ - 2026-08-28، به‌روزشده ۲۰۲۶-۰۸-۲۸): مبلغ کد
     * تخفیف باید حداقل self::MIN_MARGIN_BELOW_BROKER_SHARE (۵۰,۰۰۰ تومان)
     * کمتر از «سهم کارگزار» همون دوره باشه (نه صرفاً کمتر یا مساوی). فرمول
     * سهم کارگزار عیناً همون فرمول موجود پروژه‌ست
     * (PackagesController::actionCourses_financial_callback):
     *   سهم کارگزار = (contract.share / 100) × course.price
     * فرمول جدیدی ساخته نشده - طبق دستور صریح مستند. اگه دوره کارگزار نداشته
     * باشه یا قرارداد/سهم پیدا نشه، این قانون بی‌معنیه و چیزی رد نمی‌شه (تا
     * دوره‌های بدون کارگزار مثل قبل کار کنن).
     */
    public function validateAmountNotAboveBrokerShare($attribute, $params)
    {
        $amount = $this->$attribute;
        if ($amount === null || trim((string) $amount) === '' || !ctype_digit((string) $amount)) {
            return; // قبلاً توسط required/match گزارش شده
        }
        if ((int) $amount < 1) {
            $this->addError($attribute, 'مبلغ کد تخفیف باید بیشتر از صفر باشد');
            return;
        }
        if ($this->count !== null && ctype_digit((string) $this->count) && (int) $this->count < 1) {
            $this->addError('count', 'تعداد باید حداقل ۱ باشد');
            return;
        }

        $course = is_string($this->course_id) && preg_match('/^[a-f0-9]{24}$/i', $this->course_id) ? Courses::findOne($this->course_id) : null;
        if ($course === null) {
            $this->addError('course_id', 'دوره یافت نشد');
            return;
        }
        $brokerShare = self::brokerShare($course);
        if ($brokerShare === null) {
            // تخفیف از سهم کارگزار کم می‌شود؛ دوره‌ی کوتاه‌مدت بدون کارگزار/قرارداد کد تخفیف ندارد
            if ((string) $course->type === '1')
                $this->addError($attribute, 'این دوره کارگزار (یا قرارداد با سهم مشخص) ندارد؛ تخفیف از سهم کارگزار داده می‌شود و ثبت کد تخفیف ممکن نیست');
            return;
        }

        $maxAllowedAmount = $brokerShare - self::MIN_MARGIN_BELOW_BROKER_SHARE;
        if ((float) $amount > $maxAllowedAmount) {
            $this->addError($attribute, $maxAllowedAmount <= 0
                ? 'سهم کارگزار این دوره (' . number_format($brokerShare) . ' تومان) کمتر از حداقل لازم است و امکان ثبت کد تخفیف نیست'
                : 'مبلغ کد تخفیف باید حداقل ' . number_format(self::MIN_MARGIN_BELOW_BROKER_SHARE)
                    . ' تومان کمتر از سهم کارگزار (' . number_format($brokerShare) . ' تومان) باشد؛ حداکثر مبلغ مجاز '
                    . number_format($maxAllowedAmount) . ' تومان است');
        }
    }

    /**
     * سهم کارگزار از شهریه‌ی دوره با همان فرمول موجود پروژه
     * (PackagesController::actionCourses_financial_callback): (contract.share / 100) × course.price.
     *
     * @return float|null null اگر دوره کارگزار/قرارداد/سهم نداشته باشد
     */
    public static function brokerShare($course)
    {
        if ($course === null || $course->broker === null)
            return null;
        $brokerInfo = $course->broker;
        $brokerId = is_array($brokerInfo) ? ($brokerInfo['_id'] ?? null) : (is_object($brokerInfo) ? ($brokerInfo->_id ?? null) : null);
        $contractId = is_array($brokerInfo) ? ($brokerInfo['contract'] ?? null) : (is_object($brokerInfo) ? ($brokerInfo->contract ?? null) : null);
        if (!is_string($brokerId) || $brokerId === '' || $contractId === null || $contractId === '')
            return null;
        $broker = preg_match('/^[a-f0-9]{24}$/i', $brokerId) ? Brokers::findOne($brokerId) : null;
        if ($broker === null || !is_array($broker->contracts))
            return null;
        $share = null;
        foreach ($broker->contracts as $contract) {
            $currentContractId = is_array($contract) ? ($contract['id'] ?? null) : (is_object($contract) ? ($contract->id ?? null) : null);
            if ((string) $currentContractId === (string) $contractId) {
                $share = is_array($contract) ? ($contract['share'] ?? null) : (is_object($contract) ? ($contract->share ?? null) : null);
                break;
            }
        }
        if ($share === null || !is_numeric($share) || !is_numeric($course->price))
            return null;
        return ((float) $share / 100) * (float) $course->price;
    }

    /**
     * حداکثر مبلغ مجاز کد تخفیف برای دوره (برای نمایش در فرم).
     *
     * @return float|null
     */
    public static function maxAmount($course)
    {
        $share = self::brokerShare($course);
        return $share === null ? null : max(0, $share - self::MIN_MARGIN_BELOW_BROKER_SHARE);
    }
}
