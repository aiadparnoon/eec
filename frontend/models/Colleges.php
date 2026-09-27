<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Authors".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title
 * @property string $logo
 * @property string $first_line_signature_fa
 * @property string $first_line_signature_en
 * @property string $second_line_signature_fa
 * @property string $second_line_signature_en
 * @property string $title_en
 * @property string $name
 * @property string $last_name
 * @property string $financial_info
 * @property string $prefix
 * @property string $signature_file
 * @property string $status
 */
class Colleges extends \yii\mongodb\ActiveRecord
{
    /** ساب سرویس آی دی درگاه پرداخت؛ برای همه‌ی واحدها ثابت است و از فرم پذیرفته نمی‌شود */
    const SUB_SERVICE_ID = '1000101';
    /** طول شناسه‌ی حساب (شناسه‌ی واریز) */
    const ACCOUNT_ID_LENGTH = 30;
    /** سناریوی فرم ثبت/ویرایش واحد (قوانین اعتبارسنجی فقط اینجا فعال‌اند) */
    const SCENARIO_MANAGE = 'manage';

    /** false: شناسه‌ی حساب از فرم نیامده (کاربر غیرمدیر) و فقط ساب سرویس آی دی یکسان می‌شود */
    public $validateAccount = true;

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'colleges'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title',
            'logo',
            'first_line_signature_fa',
            'second_line_signature_fa',
            'first_line_signature_en',
            'second_line_signature_en',
            'title_en',
            'name',
            'last_name',
            'financial_info',
            'prefix',
            'signature_file',
            'status',
            'pre_id',
            'phone',
            'allow_free_add_user',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
                'title',
                'logo',
                'first_line_signature_fa',
                'second_line_signature_fa',
                'first_line_signature_en',
                'second_line_signature_en',
                'title_en',
                'name',
                'last_name',
                'financial_info',
                'prefix',
                'signature_file',
                'status',
                'phone',
                'allow_free_add_user',
            ], 'safe'],

            // فرم مدیریت واحد
            [['title', 'prefix', 'first_line_signature_fa', 'second_line_signature_fa', 'title_en', 'name', 'last_name',
                'first_line_signature_en', 'second_line_signature_en', 'phone'], 'filter', 'filter' => function ($value) {
                return is_scalar($value) ? trim(self::normalizeDigits((string) $value)) : '';
            }, 'on' => self::SCENARIO_MANAGE],
            [['title', 'prefix'], 'required', 'on' => self::SCENARIO_MANAGE],
            [['title', 'title_en'], 'string', 'max' => 150, 'on' => self::SCENARIO_MANAGE],
            [['prefix'], 'string', 'max' => 50, 'on' => self::SCENARIO_MANAGE],
            [['first_line_signature_fa', 'second_line_signature_fa', 'first_line_signature_en', 'second_line_signature_en', 'name', 'last_name'], 'string', 'max' => 200, 'on' => self::SCENARIO_MANAGE],
            [['title_en', 'name', 'last_name', 'first_line_signature_en', 'second_line_signature_en'], 'match',
                'pattern' => "/^[A-Za-z0-9 .,'&()\\-]*$/", 'message' => '«{attribute}» فقط باید با حروف انگلیسی نوشته شود', 'on' => self::SCENARIO_MANAGE],
            [['phone'], 'match', 'pattern' => '/^[0-9+\\- ]{0,20}$/', 'message' => 'شماره تماس معتبر نیست', 'on' => self::SCENARIO_MANAGE],
            [['title'], 'validateUniqueTitle', 'on' => self::SCENARIO_MANAGE],
            [['prefix'], 'validateUniquePrefix', 'on' => self::SCENARIO_MANAGE],
            [['financial_info'], 'validateFinancialInfo', 'on' => self::SCENARIO_MANAGE],
        ];
    }

    /**
     * کد واحد (پیشوند کد مجوز دوره‌ها) را به‌صورت خودکار و یکتا تولید می‌کند (بند ۴.۱ صورتجلسه).
     * شمارنده‌ی اتمیک در generals (type = unit_code) است تا دو ثبت هم‌زمان کد یکسان نگیرند؛
     * اگر عددی قبلاً به‌صورت دستی استفاده شده باشد، از آن رد می‌شود.
     */
    public static function generateUnitCode()
    {
        $counters = Yii::$app->mongodb->getCollection(['eec', 'generals']);
        for ($i = 0; $i < 1000; $i++) {
            $doc = $counters->findAndModify(['type' => 'unit_code'], ['$inc' => ['data' => 1]], ['new' => true, 'upsert' => true]);
            $number = isset($doc['data']) ? (int) $doc['data'] : 0;
            if ($number < 101) { // کدهای سه‌رقمی از ۱۰۱
                $counters->update(['type' => 'unit_code'], ['$set' => ['data' => 100]]);
                continue;
            }
            $code = (string) $number;
            if (!self::find()->where(['prefix' => $code])->exists())
                return $code;
        }
        throw new \RuntimeException('Cannot generate a unique unit code');
    }

    /**
     * ارقام فارسی/عربی → لاتین.
     */
    public static function normalizeDigits($value)
    {
        return strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    /**
     * شناسه‌ی حساب: دقیقاً ۳۰ رقم انگلیسی. ساب سرویس آی دی همیشه مقدار ثابت است.
     */
    public function validateFinancialInfo($attribute)
    {
        $info = is_array($this->financial_info) ? $this->financial_info : [];
        if (!$this->validateAccount) {
            $info['sub_service_id'] = self::SUB_SERVICE_ID;
            $this->financial_info = $info;
            return;
        }
        $id = isset($info['id']) && is_scalar($info['id']) ? preg_replace('/[\s\-]/', '', self::normalizeDigits((string) $info['id'])) : '';
        if ($id === '') {
            $this->addError($attribute, 'شناسه‌ی حساب ۳۰ رقمی الزامی است');
        } else if (!preg_match('/^[0-9]+$/', $id)) {
            $this->addError($attribute, 'شناسه‌ی حساب فقط باید شامل ارقام انگلیسی (0 تا 9) باشد');
        } else if (strlen($id) !== self::ACCOUNT_ID_LENGTH) {
            $this->addError($attribute, 'شناسه‌ی حساب باید دقیقاً ۳۰ رقم باشد (' . strlen($id) . ' رقم وارد شده)');
        }
        // فقط کلیدهای مجاز ذخیره می‌شوند
        $this->financial_info = ['id' => $id, 'sub_service_id' => self::SUB_SERVICE_ID];
    }

    /**
     * عنوان واحد تکراری نباشد (بدون حساسیت به فاصله‌های اضافه).
     */
    public function validateUniquePrefix($attribute)
    {
        $query = self::find()->where(['prefix' => $this->prefix]);
        if (!$this->getIsNewRecord())
            $query->andWhere(['_id' => ['$ne' => $this->_id]]);
        if ($query->exists())
            $this->addError($attribute, 'کد واحد تکراری است');
    }

    public function validateUniqueTitle($attribute)
    {
        $query = self::find()->where(['title' => $this->title]);
        if (!$this->getIsNewRecord())
            $query->andWhere(['_id' => ['$ne' => $this->_id]]);
        if ($query->exists())
            $this->addError($attribute, 'عنوان واحد وارد شده تکراری است');
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'title' => 'عنوان واحد',
            'prefix' => 'کد مجوز',
            'first_line_signature_fa' => 'امضا خط اول',
            'second_line_signature_fa' => 'امضا خط دوم',
            'title_en' => 'Title',
            'name' => 'Name',
            'last_name' => 'Last Name',
            'first_line_signature_en' => 'First Signature',
            'second_line_signature_en' => 'Second Signature',
            'financial_info' => 'شناسه حساب',
            'phone' => 'شماره تماس',
            'logo' => 'لوگو',
            'signature_file' => 'فایل امضا',
        ];
    }
}
