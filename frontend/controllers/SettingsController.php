<?php

namespace frontend\controllers;

use Yii;
use app\components\SafeRedirect;
use app\components\SettingsCrypto;
use app\components\classroom\BigBlueButtonPlatform;
use app\models\ClassroomServers;
use app\models\Courses;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * تنظیمات سایت (فقط مدیر سیستم، نقش user).
 *
 * بخش «سرورها»: سرورهای برگزاری کلاس آنلاین (ادوبی کانکت / بیگ‌بلوباتن). اطلاعات اتصال به‌جای کد و
 * فایل پیکربندی در دیتابیس ذخیره می‌شود؛ رمز و کلید محرمانه رمزنگاری‌شده (SettingsCrypto) و هرگز به
 * مرورگر برگردانده نمی‌شود.
 */
class SettingsController extends Controller
{
    const FLASH = 'settings';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['allow' => false, 'roles' => ['?']],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->identity->role == 'user';
                        }
                    ],
                ],
                'denyCallback' => function ($rule, $action) {
                    Yii::$app->getResponse()->redirect(['access-denied']);
                }
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'server-save' => ['post'],
                    'server-toggle' => ['post'],
                    'server-default' => ['post'],
                    'server-delete' => ['post'],
                    'server-test' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['servers']);
    }

    public function actionServers()
    {
        ClassroomServers::ensureSeeded();
        $servers = ClassroomServers::find()->orderBy(['is_default' => SORT_DESC, '_id' => SORT_ASC])->all();
        $usage = [];
        foreach ($servers as $server)
            $usage[(string) $server->_id] = (int) Courses::find()->where(['classroom_server' => (string) $server->_id])->count();
        return $this->render('servers', ['servers' => $servers, 'usage' => $usage]);
    }

    public function actionServerSave()
    {
        $input = Yii::$app->request->post('Server');
        if (!is_array($input))
            return $this->back('error', 'اطلاعات ارسال‌شده معتبر نیست');

        $id = isset($input['_id']) && is_string($input['_id']) ? $input['_id'] : '';
        $server = $id !== '' ? ClassroomServers::findById($id) : new ClassroomServers();
        if ($server === null)
            return $this->back('error', 'سرور مورد نظر یافت نشد');
        $isNew = $server->isNewRecord;

        $type = isset($input['type']) && is_string($input['type']) ? $input['type'] : '';
        if (!isset(ClassroomServers::FIELDS[$type]))
            return $this->back('error', 'نوع سرور معتبر نیست');
        // تغییر نوع سرورِ در حال استفاده، کلاس‌های ساخته‌شده‌ی دوره‌ها را بی‌اعتبار می‌کند
        if (!$isNew && $server->type !== $type && Courses::find()->where(['classroom_server' => (string) $server->_id])->exists())
            return $this->back('error', 'این سرور در دوره‌ها استفاده شده و نوع آن قابل تغییر نیست؛ یک سرور جدید اضافه کنید');

        $title = isset($input['title']) && is_string($input['title']) ? trim(strip_tags($input['title'])) : '';
        $server->title = mb_substr($title, 0, 100, 'UTF-8');
        $old = $server->type === $type && is_array($server->settings) ? $server->settings : [];
        $server->type = $type;

        $values = isset($input[$type]) && is_array($input[$type]) ? $input[$type] : [];
        $settings = [];
        foreach (ClassroomServers::FIELDS[$type] as $key => $field) {
            $value = isset($values[$key]) && is_string($values[$key]) ? trim($values[$key]) : '';
            if (!empty($field['secret'])) {
                // خالی در ویرایش = بدون تغییر
                if ($value === '') {
                    if (!empty($old[$key])) {
                        $settings[$key] = $old[$key];
                        continue;
                    }
                    if ($field['required'])
                        return $this->back('error', $field['label'] . ' الزامی است');
                    $settings[$key] = '';
                    continue;
                }
                if (mb_strlen($value, 'UTF-8') > 500)
                    return $this->back('error', $field['label'] . ' بیش از حد طولانی است');
                $settings[$key] = SettingsCrypto::encrypt($value);
                continue;
            }
            if ($value === '' && $field['required'])
                return $this->back('error', $field['label'] . ' الزامی است');
            switch ($field['type']) {
                case 'url':
                    if (!preg_match('~^https?://[^\s/$.?#][^\s<>"\']*$~i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false)
                        return $this->back('error', $field['label'] . ' باید یک آدرس معتبر با http یا https باشد');
                    $value = rtrim($value, '/');
                    break;
                case 'select':
                    if (!isset($field['options'][$value]))
                        return $this->back('error', $field['label'] . ' معتبر نیست');
                    break;
                case 'number':
                    $value = preg_replace('/\D/', '', $value);
                    if (strlen($value) > 6)
                        return $this->back('error', $field['label'] . ' معتبر نیست');
                    break;
                default:
                    $value = mb_substr(strip_tags($value), 0, 200, 'UTF-8');
            }
            $settings[$key] = $value;
        }
        $server->settings = $settings;
        if ($isNew) {
            $server->active = true;
            $server->is_default = !ClassroomServers::find()->where(['is_default' => true])->exists();
            $server->registrant = (string) Yii::$app->user->identity->username;
            $server->created_at = time();
        }
        $server->updated_at = time();
        if (!$server->validate())
            return $this->back('error', current($server->getFirstErrors()));
        $server->save(false);
        return $this->back('success', $isNew ? 'سرور «' . $server->title . '» اضافه شد' : 'تنظیمات سرور «' . $server->title . '» ذخیره شد');
    }

    public function actionServerToggle()
    {
        $server = ClassroomServers::findById(Yii::$app->request->post('id'));
        if ($server === null)
            return $this->back('error', 'سرور مورد نظر یافت نشد');
        if ($server->active && $server->is_default)
            return $this->back('error', 'سرور پیش‌فرض را نمی‌توان غیرفعال کرد؛ ابتدا سرور دیگری را پیش‌فرض کنید');
        $server->active = !$server->active;
        $server->updated_at = time();
        $server->save(false, ['active', 'updated_at']);
        return $this->back('success', $server->active
            ? 'سرور «' . $server->title . '» فعال شد'
            : 'سرور «' . $server->title . '» غیرفعال شد؛ دوره‌های فعلی آن تغییری نمی‌کنند ولی برای دوره‌ی جدید قابل انتخاب نیست');
    }

    public function actionServerDefault()
    {
        $server = ClassroomServers::findById(Yii::$app->request->post('id'));
        if ($server === null || !$server->active)
            return $this->back('error', 'فقط سرور فعال را می‌توان پیش‌فرض کرد');
        ClassroomServers::updateAll(['is_default' => false], ['is_default' => true]);
        $server->is_default = true;
        $server->save(false, ['is_default']);
        return $this->back('success', 'سرور «' . $server->title . '» پیش‌فرض شد (برای دوره‌های قدیمی که سرور مشخصی ندارند)');
    }

    public function actionServerDelete()
    {
        $server = ClassroomServers::findById(Yii::$app->request->post('id'));
        if ($server === null)
            return $this->back('error', 'سرور مورد نظر یافت نشد');
        if ($server->is_default)
            return $this->back('error', 'سرور پیش‌فرض قابل حذف نیست');
        if (Courses::find()->where(['classroom_server' => (string) $server->_id])->exists())
            return $this->back('error', 'این سرور در دوره‌ها استفاده شده و قابل حذف نیست؛ می‌توانید آن را غیرفعال کنید');
        $title = $server->title;
        $server->delete();
        return $this->back('success', 'سرور «' . $title . '» حذف شد');
    }

    /**
     * تست اتصال (JSON). رمزها سمت سرور خوانده می‌شوند و به مرورگر برنمی‌گردند.
     */
    public function actionServerTest()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $server = ClassroomServers::findById(Yii::$app->request->post('id'));
        if ($server === null)
            return ['ok' => false, 'message' => 'سرور مورد نظر یافت نشد'];
        if ($server->type === ClassroomServers::TYPE_BBB) {
            $ok = (new BigBlueButtonPlatform($server))->ping();
            return ['ok' => $ok, 'message' => $ok ? 'اتصال به سرور BigBlueButton و کلید محرمانه تأیید شد' : 'اتصال برقرار نشد یا کلید محرمانه نادرست است'];
        }
        $url = rtrim($server->setting('url'), '/') . '/api/xml';
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['action' => 'login', 'login' => $server->setting('username'), 'password' => $server->setting('password')]),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $raw = curl_exec($curl);
        curl_close($curl);
        if ($raw === false)
            return ['ok' => false, 'message' => 'اتصال به سرور ادوبی کانکت برقرار نشد'];
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $raw, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($previous);
        $ok = $xml !== false && isset($xml->status['code']) && (string) $xml->status['code'] === 'ok';
        return ['ok' => $ok, 'message' => $ok ? 'ورود به ادوبی کانکت با نام کاربری و رمز ذخیره‌شده موفق بود' : 'سرور پاسخ داد ولی ورود ناموفق بود؛ نام کاربری و رمز را بررسی کنید'];
    }

    private function back($type, $message)
    {
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect(SafeRedirect::referrer(['servers']));
    }
}
