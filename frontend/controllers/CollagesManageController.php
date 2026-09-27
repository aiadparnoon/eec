<?php

namespace frontend\controllers;

use Yii;
use app\models\Colleges;
use app\models\CollegesSearch;
use app\models\Courses;
use app\models\Users;
use app\models\UsersSearch;
use app\components\SecureUpload;
use app\components\StudentAccess;
use app\components\StudentProfile;
use app\components\UnitStats;
use app\components\UsersDirectory;
use app\components\XlsxWriter;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\UploadedFile;

/**
 * مدیریت واحدها (مجموعه‌ی colleges).
 *
 *  - مدیر سیستم (user/cnt): همه‌ی واحدها، ثبت واحد جدید، ویرایش اطلاعات مالی.
 *  - سایر کاربرانِ دارای دسترسی این صفحه: فقط واحد(های) خودشان؛ بدون ثبت واحد و بدون
 *    تغییر شناسه‌ی حساب (تغییر حساب مقصد پرداخت فقط توسط مدیر).
 */
class CollagesManageController extends Controller
{
    const FLASH = 'units-manage';
    const LOGO_DIR = '@frontend/web/college_logos';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => false,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['index', 'new', 'edit', 'report'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $identity = Yii::$app->user->identity;
                            return $identity->role == 'user'
                                || (is_array($identity->access) && in_array(Yii::$app->controller->id, $identity->access, true));
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
                    'new' => ['post'],
                    'edit' => ['post'],
                    'report' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new CollegesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination->pageSize = 30;
        $visible = $this->visibleUnitIds();
        if ($visible !== null)
            $dataProvider->query->andWhere(['_id' => array_map(function ($id) {
                return new \MongoDB\BSON\ObjectId($id);
            }, array_filter($visible, function ($id) {
                return preg_match('/^[a-f0-9]{24}$/i', $id) === 1;
            }))]);

        $currentYear = (int) UsersDirectory::jdate('Y', time(), 'en');
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'years' => range($currentYear, 1401),
            'stats' => UnitStats::all($visible),
            'isAdmin' => StudentAccess::isAdmin(),
        ]);
    }

    public function actionNew()
    {
        if (!StudentAccess::isAdmin())
            return $this->back('error', 'ثبت واحد جدید فقط توسط مدیر سیستم امکان‌پذیر است');

        $model = new Colleges(['scenario' => Colleges::SCENARIO_MANAGE]);
        $this->assign($model, true);
        $model->status = '1';
        if (!$model->validate())
            return $this->back('error', $this->firstError($model));

        $logo = UploadedFile::getInstance($model, 'logo');
        if ($logo === null)
            return $this->back('error', 'لوگوی واحد الزامی است');
        $logoName = SecureUpload::save($logo, 'image', self::LOGO_DIR);
        if ($logoName === null)
            return $this->back('error', 'لوگو: ' . SecureUpload::$lastError);
        $model->logo = $logoName;

        $signature = UploadedFile::getInstance($model, 'signature_file');
        if ($signature !== null) {
            $signatureName = SecureUpload::save($signature, 'image', self::LOGO_DIR);
            if ($signatureName === null) {
                SecureUpload::delete(self::LOGO_DIR, $logoName);
                return $this->back('error', 'فایل امضا: ' . SecureUpload::$lastError);
            }
            $model->signature_file = $signatureName;
        }

        if ($model->save(false))
            return $this->back('success', 'واحد «' . $model->title . '» ثبت شد');
        SecureUpload::delete(self::LOGO_DIR, $model->logo);
        SecureUpload::delete(self::LOGO_DIR, $model->signature_file);
        return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
    }

    public function actionEdit()
    {
        $input = Yii::$app->request->post('Colleges');
        $id = is_array($input) && isset($input['_id']) && is_string($input['_id']) ? $input['_id'] : '';
        $model = $this->findUnit($id);
        if ($model === null)
            return $this->back('error', 'واحد مورد نظر یافت نشد یا شما به آن دسترسی ندارید');

        $model->scenario = Colleges::SCENARIO_MANAGE;
        $isAdmin = StudentAccess::isAdmin();
        $this->assign($model, $isAdmin);
        if (!$model->validate())
            return $this->back('error', $this->firstError($model));

        $oldLogo = $model->getOldAttribute('logo');
        $oldSignature = $model->getOldAttribute('signature_file');
        $newFiles = [];
        foreach (['logo' => 'لوگو', 'signature_file' => 'فایل امضا'] as $attribute => $label) {
            $file = UploadedFile::getInstance($model, $attribute);
            if ($file === null)
                continue;
            $name = SecureUpload::save($file, 'image', self::LOGO_DIR);
            if ($name === null) {
                foreach ($newFiles as $saved)
                    SecureUpload::delete(self::LOGO_DIR, $saved);
                return $this->back('error', $label . ': ' . SecureUpload::$lastError);
            }
            $model->$attribute = $name;
            $newFiles[$attribute] = $name;
        }

        if (!$model->save(false)) {
            foreach ($newFiles as $saved)
                SecureUpload::delete(self::LOGO_DIR, $saved);
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
        }
        // فایل قبلی فقط بعد از ذخیره‌ی موفق حذف می‌شود
        if (isset($newFiles['logo']))
            SecureUpload::delete(self::LOGO_DIR, $oldLogo);
        if (isset($newFiles['signature_file']))
            SecureUpload::delete(self::LOGO_DIR, $oldSignature);
        return $this->back('success', 'واحد «' . $model->title . '» ویرایش شد');
    }

    /**
     * گزارش سالانه‌ی دوره‌های یک واحد (سال شمسی) با تعداد شرکت‌کنندگان هر دوره.
     */
    public function actionReport()
    {
        $unit = $this->findUnit((string) Yii::$app->request->post('college'));
        $year = (int) UsersSearch::normalizeDigits((string) Yii::$app->request->post('year'));
        if ($unit === null)
            return $this->back('error', 'واحد مورد نظر یافت نشد یا شما به آن دسترسی ندارید');
        if ($year < 1390 || $year > 1500)
            return $this->back('error', 'سال انتخاب‌شده معتبر نیست');

        $from = UsersSearch::jalaliToTimestamp($year . '/1/1', false);
        $to = UsersSearch::jalaliToTimestamp(($year + 1) . '/1/1', false) - 1;
        $courses = Courses::find()
            ->select(['_id', 'title', 'license_code', 'status', 'content_type', 'type', 'price'])
            ->where(['college' => (string) $unit->_id])
            ->andWhere(['status' => ['0', '1', '6']])
            ->andWhere(['from_pec' => ['$ne' => true]])
            ->andWhere(['_id' => ['$gte' => UsersSearch::objectIdFromTime($from), '$lte' => UsersSearch::objectIdFromTime($to, true)]])
            ->orderBy(['_id' => SORT_DESC])
            ->asArray()
            ->all();

        // تعداد شرکت‌کنندگان همه‌ی دوره‌ها با یک aggregation
        $participants = [];
        $ids = array_map(function ($c) {
            return (string) $c['_id'];
        }, $courses);
        if (!empty($ids)) {
            $rows = Users::getCollection()->aggregate([
                ['$match' => ['courses._id' => ['$in' => $ids]]],
                ['$project' => ['courses._id' => 1]],
                ['$unwind' => '$courses'],
                ['$match' => ['courses._id' => ['$in' => $ids]]],
                ['$group' => ['_id' => '$courses._id', 'count' => ['$sum' => 1]]],
            ]);
            foreach ($rows as $row)
                $participants[(string) $row['_id']] = (int) $row['count'];
        }

        $writer = new XlsxWriter(['ردیف', 'نام دوره', 'کد مجوز', 'نوع دوره', 'نوع برگزاری', 'تاریخ ایجاد', 'تعداد شرکت‌کنندگان'], [7, 45, 16, 16, 16, 14, 18]);
        foreach ($courses as $i => $course) {
            $id = (string) $course['_id'];
            $title = isset($course['title']['main_fa']) && is_scalar($course['title']['main_fa']) ? (string) $course['title']['main_fa'] : '-';
            $writer->addRow([
                $i + 1,
                $title,
                isset($course['license_code']) && is_scalar($course['license_code']) ? (string) $course['license_code'] : '',
                UsersDirectory::courseType(isset($course['type']) ? $course['type'] : ''),
                UsersDirectory::contentType(isset($course['content_type']) ? $course['content_type'] : ''),
                UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))),
                isset($participants[$id]) ? $participants[$id] : 0,
            ]);
        }
        $path = Yii::getAlias('@runtime') . '/unit-report-' . bin2hex(random_bytes(6)) . '.xlsx';
        $writer->save($path);
        $response = Yii::$app->response->sendFile($path, 'UnitReport-' . $year . '.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    // ------------------------------------------------------------------ کمکی

    /**
     * فقط فیلدهای مجاز فرم؛ بدون mass-assignment (status، allow_free_add_user، ... از فرم پذیرفته نمی‌شوند).
     */
    private function assign(Colleges $model, $withFinancial)
    {
        $input = Yii::$app->request->post('Colleges');
        $input = is_array($input) ? $input : [];
        foreach (['title', 'prefix', 'first_line_signature_fa', 'second_line_signature_fa', 'title_en', 'name', 'last_name',
                     'first_line_signature_en', 'second_line_signature_en', 'phone'] as $field)
            $model->$field = isset($input[$field]) && is_scalar($input[$field]) ? (string) $input[$field] : '';
        if ($withFinancial) {
            $financial = isset($input['financial_info']) && is_array($input['financial_info']) ? $input['financial_info'] : [];
            $model->financial_info = ['id' => isset($financial['id']) && is_scalar($financial['id']) ? (string) $financial['id'] : ''];
        } else {
            // غیرمدیر: اطلاعات مالی دست‌نخورده می‌ماند (validator فقط ساب سرویس آی دی را یکسان‌سازی می‌کند)
            $model->financial_info = is_array($model->financial_info) ? $model->financial_info : [];
            $model->validateAccount = false;
        }
    }

    /**
     * @return string[]|null شناسه‌ی واحدهای قابل مشاهده؛ null یعنی همه (مدیر)
     */
    private function visibleUnitIds()
    {
        return StudentAccess::isAdmin() ? null : StudentAccess::staffColleges();
    }

    /**
     * @return Colleges|null
     */
    private function findUnit($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id))
            return null;
        $visible = $this->visibleUnitIds();
        if ($visible !== null && !in_array($id, $visible, true))
            return null;
        return Colleges::findOne($id);
    }

    private function firstError(Colleges $model)
    {
        foreach ($model->getFirstErrors() as $error)
            return $error;
        return 'اطلاعات وارد شده معتبر نیست';
    }

    private function back($type, $message)
    {
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }
}
