<?php

namespace app\models;

use Yii;
use app\components\SettingsCrypto;

/**
 * سرورهای برگزاری کلاس آنلاین (تنظیمات سایت › سرورها).
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 * @property string $title    نام نمایشی سرور
 * @property string $type     adobe | bbb
 * @property array  $settings تنظیمات بر اساس نوع؛ فیلدهای محرمانه رمزنگاری‌شده ذخیره می‌شوند
 * @property bool   $active   فقط سرورهای فعال در فرم دوره قابل انتخاب‌اند
 * @property bool   $is_default سرور دوره‌های قدیمی که سرور مشخصی ندارند
 * @property string $registrant
 * @property int    $created_at
 * @property int    $updated_at
 */
class ClassroomServers extends \yii\mongodb\ActiveRecord
{
    const TYPE_ADOBE = 'adobe';
    const TYPE_BBB = 'bbb';

    /** مقدار انتخاب «هیچ‌کدام» در فرم دوره: دوره کلاس آنلاین در سامانه ندارد */
    const NONE = 'none';

    const TYPES = [
        self::TYPE_ADOBE => 'ادوبی کانکت (Adobe Connect)',
        self::TYPE_BBB => 'بیگ‌بلوباتن (BigBlueButton)',
    ];

    /**
     * فیلدهای هر نوع سرور. secret=true یعنی رمزنگاری در دیتابیس و عدم نمایش مقدار در فرم.
     */
    const FIELDS = [
        self::TYPE_ADOBE => [
            'url' => ['label' => 'آدرس سرور کلاس (Adobe Connect)', 'placeholder' => 'https://eecvclass1.ut.ac.ir', 'type' => 'url', 'required' => true],
            'api_url' => ['label' => 'آدرس وب‌سرویس واسط (api-eec)', 'placeholder' => 'https://api-eec.ut.ac.ir', 'type' => 'url', 'required' => true,
                'hint' => 'ساخت کلاس و ثبت کاربران از طریق این وب‌سرویس انجام می‌شود'],
            'username' => ['label' => 'نام کاربری مدیر ادوبی', 'type' => 'text', 'required' => true],
            'password' => ['label' => 'رمز عبور مدیر ادوبی', 'type' => 'password', 'required' => true, 'secret' => true],
        ],
        self::TYPE_BBB => [
            'url' => ['label' => 'آدرس API سرور (BigBlueButton)', 'placeholder' => 'https://bbb.example.ir/bigbluebutton/', 'type' => 'url', 'required' => true,
                'hint' => 'همان آدرسی که دستور bbb-conf --secret نشان می‌دهد'],
            'secret' => ['label' => 'کلید محرمانه (Shared Secret)', 'type' => 'password', 'required' => true, 'secret' => true],
            'checksum' => ['label' => 'الگوریتم checksum', 'type' => 'select', 'required' => true,
                'options' => ['sha1' => 'SHA-1 (پیش‌فرض)', 'sha256' => 'SHA-256', 'sha512' => 'SHA-512'], 'default' => 'sha1'],
            'record' => ['label' => 'امکان ضبط جلسات', 'type' => 'select', 'required' => true,
                'options' => ['1' => 'بله', '0' => 'خیر'], 'default' => '1'],
            'max_participants' => ['label' => 'حداکثر شرکت‌کننده در هر کلاس', 'type' => 'number', 'required' => false,
                'hint' => 'خالی یا ۰ = بدون محدودیت'],
        ],
    ];

    public static function collectionName()
    {
        return ['eec', 'classroom_servers'];
    }

    public function attributes()
    {
        return ['_id', 'title', 'type', 'settings', 'active', 'is_default', 'registrant', 'created_at', 'updated_at'];
    }

    public function rules()
    {
        return [
            [['title', 'type'], 'required', 'message' => '{attribute} الزامی است'],
            ['title', 'string', 'max' => 100],
            ['type', 'in', 'range' => array_keys(self::TYPES), 'message' => 'نوع سرور معتبر نیست'],
        ];
    }

    public function attributeLabels()
    {
        return ['title' => 'نام سرور', 'type' => 'نوع سرور'];
    }

    public function typeLabel()
    {
        return isset(self::TYPES[$this->type]) ? self::TYPES[$this->type] : '-';
    }

    /**
     * مقدار یک تنظیم (فیلدهای محرمانه رمزگشایی می‌شوند).
     */
    public function setting($key, $default = '')
    {
        $settings = is_array($this->settings) ? $this->settings : [];
        if (!isset($settings[$key]) || $settings[$key] === '')
            return isset(self::FIELDS[$this->type][$key]['default']) ? self::FIELDS[$this->type][$key]['default'] : $default;
        if (!empty(self::FIELDS[$this->type][$key]['secret'])) {
            $plain = SettingsCrypto::decrypt((string) $settings[$key]);
            return $plain === null ? $default : $plain;
        }
        return (string) $settings[$key];
    }

    public function hasSetting($key)
    {
        return is_array($this->settings) && isset($this->settings[$key]) && $this->settings[$key] !== '';
    }

    /**
     * سرورهای فعال برای انتخاب در فرم دوره: [id => title].
     */
    public static function activeOptions()
    {
        self::ensureSeeded();
        $options = [];
        foreach (self::find()->where(['active' => true])->orderBy(['is_default' => SORT_DESC, 'title' => SORT_ASC])->all() as $server)
            $options[(string) $server->_id] = $server->title . ' — ' . ($server->type === self::TYPE_BBB ? 'BigBlueButton' : 'Adobe Connect');
        return $options;
    }

    /**
     * سرور پیش‌فرض (برای دوره‌های قدیمی بدون فیلد classroom_server).
     *
     * @return static|null
     */
    public static function defaultServer()
    {
        self::ensureSeeded();
        $server = self::find()->where(['is_default' => true])->one();
        return $server !== null ? $server : self::find()->where(['type' => self::TYPE_ADOBE])->orderBy(['_id' => SORT_ASC])->one();
    }

    /**
     * @return static|null
     */
    public static function findById($id)
    {
        return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id) ? self::findOne($id) : null;
    }

    /**
     * سرور فعلی ادوبی کانکت که تا امروز در کد/پیکربندی بود، در اولین استفاده به دیتابیس منتقل می‌شود.
     */
    public static function ensureSeeded()
    {
        static $checked = false;
        if ($checked)
            return;
        $checked = true;
        if (self::find()->exists())
            return;
        $password = Yii::getAlias('@adobe_password', false);
        $server = new self();
        $server->title = 'ادوبی کانکت (سرور فعلی)';
        $server->type = self::TYPE_ADOBE;
        $server->settings = [
            'url' => 'https://eecvclass1.ut.ac.ir',
            'api_url' => (string) Yii::getAlias('@baseUrl', false),
            'username' => (string) Yii::getAlias('@adobe_user', false),
            'password' => is_string($password) && $password !== '' ? SettingsCrypto::encrypt($password) : '',
        ];
        $server->active = true;
        $server->is_default = true;
        $server->registrant = 'system';
        $server->created_at = time();
        $server->updated_at = time();
        $server->save(false);
    }
}
