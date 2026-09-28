<?php

namespace frontend\controllers;

use Yii;
use app\models\Brokers;
use app\models\ClassroomServers;
use app\models\Colleges;
use app\models\Courses;
use app\models\CoursesContents;
use app\models\CoursesMembers;
use app\models\Discounts;
use app\models\Generals;
use app\models\Lessons;
use app\models\Scores;
use app\models\ShortCoursesSearch;
use app\models\Teachers;
use app\models\Users;
use app\components\CourseAccess;
use app\components\CourseStatus;
use app\components\SafeRedirect;
use app\components\SecureUpload;
use app\components\ShortCourseForm;
use app\components\StudentAccess;
use app\components\StudentProfile;
use app\components\UsersDirectory;
use app\components\XlsxWriter;
use app\components\classroom\ClassroomPlatforms;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

/**
 * دوره‌های کوتاه‌مدت (courses.type = '1').
 *
 * دسترسی هر اکشن با CourseAccess کنترل می‌شود (نه فقط در ویو):
 *  - استاد فقط فهرست دوره‌های خودش را می‌بیند؛ ثبت/ویرایش/کپی/حذف ندارد.
 *  - کارشناس واحد و کارگزار فقط دوره‌های محدوده‌ی خود؛ پس از تأیید فقط مدیر ویرایش می‌کند.
 */
class CoursesController extends Controller
{
    const FLASH = 'courses';
    const CONTRACT_DIR = '@frontend/web/contract_files';
    /** واحدی که دوره‌هایش بدون بررسی تأیید می‌شوند (قاعده‌ی موجود پروژه) */
    const AUTO_APPROVE_UNIT = '663b1d28c9c6ce2e65073e22';
    /** واحدی که دوره‌هایش مثل کارگزار ابتدا در واحد بررسی می‌شوند (قاعده‌ی موجود پروژه) */
    const UNIT_REVIEW_UNIT = '65afa2ea5136ec5b5b0c4064';

    public function behaviors()
    {
        $staff = function () {
            $identity = Yii::$app->user->identity;
            return $identity->role == 'user'
                || (is_array($identity->access) && in_array(Yii::$app->controller->id, $identity->access, true));
        };
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['allow' => false, 'roles' => ['?']],
                    [
                        // استاد فقط فهرست
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () use ($staff) {
                            return $staff() || Yii::$app->user->identity->role == 'teacher';
                        },
                    ],
                    [
                        'actions' => ['new', 'edit', 'edit-course', 'copy-course', 'report', 'members_report', 'unit-options',
                            'brokers', 'brokers1', 'broker_contracts', 'capacity', 'course_date', 'check-username',
                            'show_course_users', 'delete_course', 'toggle-site', 'register-online', 'member-finance'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () use ($staff) {
                            return $staff() && Yii::$app->user->identity->role != 'teacher';
                        },
                    ],
                ],
                'denyCallback' => function () {
                    Yii::$app->getResponse()->redirect(['access-denied']);
                },
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'new' => ['post'],
                    'edit' => ['post'],
                    'copy-course' => ['post'],
                    'delete_course' => ['post'],
                    'show_course_users' => ['post'],
                    'check-username' => ['post'],
                    'toggle-site' => ['post'],
                    'register-online' => ['post'],
                    'member-finance' => ['post'],
                ],
            ],
        ];
    }

    // ================================================================== فهرست

    public function actionIndex()
    {
        $searchModel = new ShortCoursesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $courses = $dataProvider->getModels();

        // داده‌های کمکی هر ردیف با کوئری‌های دسته‌ای
        $teacherIds = [];
        $brokerIds = [];
        $registrants = [];
        foreach ($courses as $course) {
            if (isset($course->lessons[0]['teachers']) && is_string($course->lessons[0]['teachers']))
                $teacherIds[] = $course->lessons[0]['teachers'];
            if (is_array($course->broker) && isset($course->broker['_id']) && is_string($course->broker['_id']))
                $brokerIds[] = $course->broker['_id'];
            $registrants[] = $course->registrant;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'courses' => $courses,
            'teachers' => $this->namesById(Teachers::class, $teacherIds),
            'brokerNames' => $this->brokerNames($brokerIds),
            'registrants' => UsersDirectory::registrants($registrants),
            'stats' => ShortCoursesSearch::stats(),
            'units' => $this->selectableUnits(),
            'createUnits' => $this->creatableUnits(),
            'servers' => ClassroomServers::activeOptions(),
            'brokers' => $this->selectableBrokers(),
            'filterTeachers' => $this->selectableTeachers(),
            'capacityTypes' => $this->capacityTypes(),
            'canCreate' => in_array(CourseAccess::role(), ['user', 'cnt', 'emp', 'broker'], true),
        ]);
    }

    /**
     * کارگزاران، اساتید و دروس یک واحد برای فرم ثبت دوره (JSON؛ رشته‌ها در مرورگر encode می‌شوند).
     */
    public function actionUnitOptions($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (!CourseAccess::canUseUnit($id))
            return ['brokers' => [], 'teachers' => [], 'lessons' => []];
        $brokers = [];
        $brokerQuery = Brokers::find()->where(['college' => $id, 'status' => '1']);
        if (CourseAccess::role() === 'broker') {
            $own = CourseAccess::broker();
            $brokerQuery->andWhere(['_id' => $own === null ? null : $own->_id]);
        }
        foreach ($brokerQuery->all() as $broker) {
            $ci = is_array($broker->connector_info) ? $broker->connector_info : [];
            $name = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
            if ((string) $broker->type === '1' && isset($broker->company_info['company_title']))
                $name .= ' (شرکت ' . $broker->company_info['company_title'] . ')';
            $contracts = [];
            foreach ((array) $broker->contracts as $contract)
                if (is_array($contract) && isset($contract['id']))
                    $contracts[] = ['id' => (string) $contract['id'], 'title' => (isset($contract['title']) ? (string) $contract['title'] : '') . (isset($contract['share']) ? ' (' . $contract['share'] . ' درصد)' : '')];
            if (empty($contracts))
                continue; // بدون قرارداد، دوره قابل ثبت نیست (سرور هم قرارداد را الزامی می‌داند)
            $brokers[] = ['id' => (string) $broker->_id, 'name' => $name !== '' ? $name : 'کارگزار بدون نام', 'contracts' => $contracts];
        }
        $teachers = [];
        foreach (Teachers::find()->select(['first_name', 'last_name'])->where(['colleges' => $id])->all() as $teacher)
            $teachers[] = ['id' => (string) $teacher->_id, 'name' => trim($teacher->first_name . ' ' . $teacher->last_name)];
        $lessons = [];
        foreach (Lessons::find()->select(['title'])->where(['college' => $id])->all() as $lesson)
            $lessons[] = ['id' => (string) $lesson->_id, 'name' => (string) $lesson->title];
        return ['brokers' => $brokers, 'teachers' => $teachers, 'lessons' => $lessons];
    }

    // ================================================================== ثبت و ویرایش

    public function actionNew()
    {
        $model = new Courses();
        $model->scenario = Courses::SCENARIO_CREATE_COURSE;
        if (!ShortCourseForm::apply($model, Yii::$app->request->post('Courses'), true))
            return $this->back('error', ShortCourseForm::$error);

        $model->type = '1';
        $role = CourseAccess::role();
        $unit = (string) $model->college;
        if ($role === 'user' || $unit === self::AUTO_APPROVE_UNIT)
            $model->status = CourseStatus::ACTIVE;
        else if ($role === 'broker' || $unit === self::UNIT_REVIEW_UNIT)
            $model->status = CourseStatus::AWAITING_UNIT;
        else
            $model->status = CourseStatus::AWAITING;
        $model->modified = false;
        $model->show_in_site = true;
        $model->credit = '0';
        $model->registrant = (string) Yii::$app->user->identity->username;

        // قرارداد (برای ظرفیت سازمانی الزامی): فقط PDF یا تصویر، با نام تصادفی
        $contract = UploadedFile::getInstance($model, 'contract_file');
        if ($contract !== null) {
            $name = SecureUpload::save($contract, 'document', self::CONTRACT_DIR);
            if ($name === null)
                return $this->back('error', 'فایل قرارداد: ' . SecureUpload::$lastError);
            $model->contract_file = $name;
        }

        $lesson = Lessons::findOne($model->lessons[0]['_id']);
        if ($lesson !== null)
            $model->preview_image = $lesson->imagePreview;

        if (!$model->validate()) {
            SecureUpload::delete(self::CONTRACT_DIR, $model->contract_file);
            return $this->back('error', $this->firstError($model));
        }
        if ($model->status === CourseStatus::ACTIVE && !$this->assignLicenseCode($model)) {
            SecureUpload::delete(self::CONTRACT_DIR, $model->contract_file);
            return $this->back('error', 'تولید کد مجوز ممکن نشد (کد واحد یا شمارنده‌ی کد مجوز تعریف نشده است)');
        }
        if (!$model->save(false)) {
            SecureUpload::delete(self::CONTRACT_DIR, $model->contract_file);
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
        }

        $message = 'دوره «' . $model->title['main_fa'] . '» ثبت شد';
        if (ClassroomPlatforms::hasOnlineClass($model)) {
            $result = ClassroomPlatforms::forCourse($model)->createCourseMeetings((string) $model->_id);
            $model->adobe_status = $result === true ? '1' : '0';
            $model->save(false, ['adobe_status']);
            if ($result !== true)
                return $this->back('warning', $message . '؛ اما ساخت کلاس آنلاین ناموفق بود و از فهرست قابل ثبت مجدد است');
        }
        return $this->back('success', $message);
    }

    public function actionEdit()
    {
        $input = Yii::$app->request->post('Courses');
        $id = is_array($input) && isset($input['_id']) && is_string($input['_id']) ? $input['_id'] : '';
        $model = $this->findCourse($id);
        if ($model === null || !CourseAccess::canEdit($model))
            return $this->back('error', 'دوره یافت نشد یا امکان ویرایش آن را ندارید (دوره‌ی تأییدشده را فقط مدیر سیستم ویرایش می‌کند)');

        $oldLessonId = isset($model->lessons[0]['_id']) ? (string) $model->lessons[0]['_id'] : '';
        $oldServer = (string) $model->classroom_server;
        $model->scenario = Courses::SCENARIO_EDIT_COURSE;
        if (!ShortCourseForm::apply($model, $input, false))
            return $this->back('error', ShortCourseForm::$error);
        if (!$model->validate())
            return $this->back('error', $this->firstError($model));

        // ویرایش دوره‌ی «نیاز به اصلاح» = ارسال مجدد با برچسب «اصلاح‌شده، در انتظار بررسی» (بند ۵.۳)
        if (in_array((string) $model->status, [CourseStatus::NEEDS_CORRECTION, CourseStatus::UNIT_CORRECTION], true))
            CourseStatus::submit($model, CourseAccess::role() === 'broker');

        if (!$model->save(false))
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');

        $newLessonId = (string) $model->lessons[0]['_id'];
        $message = 'دوره «' . $model->title['main_fa'] . '» ویرایش شد';
        if (ClassroomPlatforms::hasOnlineClass($model)) {
            $result = true;
            if ($oldServer !== (string) $model->classroom_server)
                $result = ClassroomPlatforms::forCourse($model)->createCourseMeetings((string) $model->_id); // سرور عوض شد: کلاس روی سرور جدید
            else if ($newLessonId !== $oldLessonId)
                $result = ClassroomPlatforms::forCourse($model)->createCourseMeetings((string) $model->_id, $newLessonId);
            if ($result !== true) {
                $model->adobe_status = '0';
                $model->save(false, ['adobe_status']);
                return $this->back('warning', $message . '؛ اما ساخت کلاس آنلاین روی سرور انتخاب‌شده ناموفق بود و از فهرست قابل ثبت مجدد است');
            }
        }
        return $this->back('success', $message);
    }

    public function actionEditCourse($_id)
    {
        $courseDetail = $this->findCourse($_id);
        if ($courseDetail === null || !CourseAccess::canView($courseDetail))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید', ['index']);

        $searchModel = new CoursesMembers();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $courseDetail->_id);
        $dataProvider->pagination->pageSize = 50;
        return $this->render('edit-course', [
            'colleges' => $this->selectableUnits(),
            'servers' => ClassroomServers::activeOptions(),
            'courseDetail' => $courseDetail,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'discounts' => Discounts::find()->where(['course_id' => (string) $courseDetail->_id])->all(),
            'allowEdit' => CourseAccess::canEdit($courseDetail),
        ]);
    }

    /**
     * اطلاعات مالی کامل یک عضو در همین دوره (نوع پرداخت، کانال، سهم واحد و کارگزار، اقساط) — JSON.
     * همان جدول پروفایل دانشپذیر؛ فقط برای کسی که دوره را مدیریت می‌کند و فقط برای عضوِ همین دوره.
     */
    public function actionMemberFinance($_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $course = $this->findCourse((string) $_id);
        if ($course === null || !CourseAccess::canManage($course))
            return ['title' => 'اطلاعات مالی', 'html' => Html::tag('div', 'دوره یافت نشد یا به آن دسترسی ندارید', ['class' => 'text-danger'])];
        $memberId = (string) Yii::$app->request->post('member');
        $member = preg_match('/^[a-f0-9]{24}$/i', $memberId)
            ? Users::find()->where(['_id' => $memberId, 'courses._id' => (string) $course->_id])->one()
            : null;
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

    /**
     * کپی دوره با درس‌ها (بدون کلاس آنلاین، کد مجوز و سوابق بررسی).
     */
    public function actionCopyCourse($_id)
    {
        $course = $this->findCourse($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید');

        $model = new Courses();
        $attributes = $course->attributes;
        unset($attributes['_id']);
        $model->setAttributes($attributes, false);
        $lessons = [];
        foreach ((array) $course->lessons as $lesson) {
            if (!is_array($lesson))
                continue;
            unset($lesson['meeting']); // کلاس آنلاین برای دوره‌ی جدید دوباره ساخته می‌شود
            $lessons[] = $lesson;
        }
        $model->lessons = $lessons;
        $model->status = CourseAccess::role() === 'broker' ? CourseStatus::AWAITING_UNIT : CourseStatus::AWAITING;
        $model->modified = false;
        $model->license_code = null;
        $model->rejection_reason = null;
        $model->mentors = null;
        $model->other_teachers = null;
        $model->adobe_status = null;
        $model->classroom_meetings = null;
        $model->registrant = (string) Yii::$app->user->identity->username;
        if (!$model->save(false))
            return $this->back('error', 'کپی دوره ناموفق بود');
        Yii::$app->session->setFlash(self::FLASH, ['type' => 'success', 'message' => 'نسخه‌ی کپی ساخته شد؛ اطلاعات آن را بررسی و در صورت نیاز ویرایش کنید']);
        return $this->redirect(['edit-course', '_id' => (string) $model->_id]);
    }

    // ================================================================== حذف

    public function actionShow_course_users()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $course = $this->findCourse((string) Yii::$app->request->post('id'));
        if ($course === null || !CourseAccess::canManage($course))
            return ['ok' => false, 'message' => 'دوره یافت نشد یا به آن دسترسی ندارید'];
        $title = isset($course->title['main_fa']) ? (string) $course->title['main_fa'] : '';
        if (Users::find()->where(['courses._id' => (string) $course->_id])->exists())
            return ['ok' => false, 'message' => 'دوره‌ی «' . $title . '» دانشپذیر دارد و قابل حذف نیست'];
        if (!CourseAccess::canDelete($course))
            return ['ok' => false, 'message' => 'دوره‌ی تأییدشده را فقط مدیر سیستم می‌تواند حذف کند'];
        return ['ok' => true, 'message' => 'آیا از حذف دوره‌ی «' . $title . '» مطمئن هستید؟'];
    }

    public function actionDelete_course()
    {
        $course = $this->findCourse((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canDelete($course))
            return $this->back('error', 'این دوره قابل حذف نیست (دانشپذیر دارد یا دسترسی لازم را ندارید)');
        if ($course->delete())
            return $this->back('success', 'دوره حذف شد');
        return $this->back('error', 'خطا در حذف دوره');
    }

    /**
     * نمایش/مخفی کردن دوره‌ی فعال در سایت اصلی.
     */
    public function actionToggleSite()
    {
        $course = $this->findCourse((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید');
        if ((string) $course->status !== CourseStatus::ACTIVE)
            return $this->back('warning', 'فقط دوره‌ی فعال در سایت نمایش داده می‌شود');
        $course->show_in_site = $course->show_in_site === false;
        if ($course->save(false, ['show_in_site']))
            return $this->back('success', $course->show_in_site ? 'دوره در سایت نمایش داده می‌شود' : 'دوره از سایت مخفی شد');
        return $this->back('error', 'خطا در ذخیره‌سازی');
    }

    /**
     * ساخت مجدد کلاس آنلاین دوره (وقتی ساخت اولیه ناموفق بوده).
     */
    public function actionRegisterOnline()
    {
        $course = $this->findCourse((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canManage($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید');
        if (!ClassroomPlatforms::hasOnlineClass($course))
            return $this->back('warning', 'این دوره کلاس آنلاین ندارد');
        $platform = ClassroomPlatforms::forCourse($course);
        $result = $platform->createCourseMeetings((string) $course->_id);
        $course->adobe_status = $result === true ? '1' : '0';
        $course->save(false, ['adobe_status']);
        if ($result === true)
            return $this->back('success', 'کلاس آنلاین دوره در ' . $platform->title() . ' ساخته شد');
        return $this->back('error', $result === null ? 'ارتباط با سرور ' . $platform->title() . ' برقرار نشد' : 'ساخت کلاس آنلاین ناموفق بود');
    }

    // ================================================================== گزارش‌ها

    public function actionReport()
    {
        @set_time_limit(0);
        $searchModel = new ShortCoursesSearch();
        $query = $searchModel->search(Yii::$app->request->queryParams)->query;
        $writer = new XlsxWriter(
            ['ردیف', 'عنوان اصلی فارسی', 'عنوان اصلی انگلیسی', 'عنوان فارسی داخل گواهی', 'عنوان انگلیسی داخل گواهی', 'وضعیت دوره', 'کد مجوز',
                'قیمت اصلی (تومان)', 'قیمت با تخفیف (تومان)', 'مدت زمان (ساعت)', 'ظرفیت', 'نوع دوره', 'تاریخ شروع', 'تاریخ پایان', 'ساعت شروع',
                'واحد', 'کارگزار', 'درس', 'مدرس', 'تاریخ ثبت', 'تعداد دانشپذیر'],
            [7, 40, 30, 40, 30, 22, 16, 16, 16, 12, 14, 12, 12, 12, 10, 24, 24, 30, 22, 12, 14]
        );
        $units = UsersDirectory::collegeTitles();
        foreach ($query->batch(300) as $courses) {
            $ids = $teacherIds = $lessonIds = $brokerIds = [];
            foreach ($courses as $course) {
                $ids[] = (string) $course->_id;
                if (isset($course->lessons[0]['teachers']) && is_string($course->lessons[0]['teachers']))
                    $teacherIds[] = $course->lessons[0]['teachers'];
                if (isset($course->lessons[0]['_id']) && is_string($course->lessons[0]['_id']))
                    $lessonIds[] = $course->lessons[0]['_id'];
                if (isset($course->broker['_id']) && is_string($course->broker['_id']))
                    $brokerIds[] = $course->broker['_id'];
            }
            $teachers = $this->namesById(Teachers::class, $teacherIds);
            $lessons = $this->namesById(Lessons::class, $lessonIds, 'title');
            $brokers = $this->brokerNames($brokerIds);
            $members = $this->memberCounts($ids);
            foreach ($courses as $course) {
                $id = (string) $course->_id;
                $t = is_array($course->title) ? $course->title : [];
                $d = isset($course->lessons[0]['date']) && is_array($course->lessons[0]['date']) ? $course->lessons[0]['date'] : [];
                $cap = is_array($course->student_capacity) ? $course->student_capacity : [];
                $capacity = isset($cap['type']) ? ((string) $cap['type'] === '2' ? (isset($cap['number']) ? $cap['number'] . ' نفر' : 'محدود') : ((string) $cap['type'] === '3' ? 'سازمانی' : 'نامحدود')) : '';
                $teacherId = isset($course->lessons[0]['teachers']) ? (string) $course->lessons[0]['teachers'] : '';
                $lessonId = isset($course->lessons[0]['_id']) ? (string) $course->lessons[0]['_id'] : '';
                $brokerId = isset($course->broker['_id']) ? (string) $course->broker['_id'] : '';
                $writer->addRow([
                    $writer->rowCount() + 1,
                    isset($t['main_fa']) ? (string) $t['main_fa'] : '', isset($t['main_en']) ? (string) $t['main_en'] : '',
                    isset($t['degree_fa']) ? (string) $t['degree_fa'] : '', isset($t['degree_en']) ? (string) $t['degree_en'] : '',
                    CourseStatus::label($course)[0],
                    (string) $course->license_code,
                    is_numeric($course->price) ? (float) $course->price : '', is_numeric($course->discount_price) ? (float) $course->discount_price : '',
                    is_numeric($course->duration) ? (int) $course->duration : '',
                    $capacity,
                    UsersDirectory::contentType($course->content_type),
                    isset($d['from']) ? (string) $d['from'] : '', isset($d['to']) ? (string) $d['to'] : '', isset($d['time']) ? (string) $d['time'] : '',
                    isset($units[(string) $course->college]) ? $units[(string) $course->college] : '',
                    isset($brokers[$brokerId]) ? $brokers[$brokerId] : '',
                    isset($lessons[$lessonId]) ? $lessons[$lessonId] : '',
                    isset($teachers[$teacherId]) ? $teachers[$teacherId] : '',
                    UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))),
                    isset($members[$id]) ? $members[$id] : 0,
                ]);
            }
        }
        return $this->sendXlsx($writer, 'ShortCourses');
    }

    public function actionMembers_report()
    {
        $course = $this->findCourse((string) Yii::$app->request->get('_id'));
        if ($course === null || !CourseAccess::canView($course))
            return $this->back('error', 'دوره یافت نشد یا به آن دسترسی ندارید');
        $courseId = (string) $course->_id;
        $writer = new XlsxWriter(['ردیف', 'نام', 'نام خانوادگی', 'نام کاربری', 'کد ملی', 'ثبت کننده', 'وضعیت در دوره', 'وضعیت گواهی'], [7, 16, 20, 24, 14, 30, 22, 24]);
        $query = Users::find()->select(['first_name', 'last_name', 'username', 'courses', 'issuance_certificate_information'])
            ->where(['courses._id' => $courseId])->orderBy(['last_name' => SORT_ASC])->asArray();
        $certificates = [];
        foreach (\app\models\CertificateRequests::find()->select(['username', 'status'])->where(['course_id' => $courseId])->asArray()->all() as $request)
            $certificates[(string) $request['username']] = (string) $request['status'];
        foreach ($query->batch(500) as $rows) {
            $registrantNames = [];
            foreach ($rows as $row)
                foreach ((array) (isset($row['courses']) ? $row['courses'] : []) as $item)
                    if (is_array($item) && isset($item['_id'], $item['registrant']) && (string) $item['_id'] === $courseId)
                        $registrantNames[] = $item['registrant'];
            $registrants = UsersDirectory::registrants($registrantNames);
            foreach ($rows as $row) {
                $item = [];
                foreach ((array) (isset($row['courses']) ? $row['courses'] : []) as $c)
                    if (is_array($c) && isset($c['_id']) && (string) $c['_id'] === $courseId)
                        $item = $c;
                $username = isset($row['username']) && is_scalar($row['username']) ? (string) $row['username'] : '';
                $registrant = UsersDirectory::describeUsername(isset($item['registrant']) && is_scalar($item['registrant']) ? (string) $item['registrant'] : '', $username, $registrants);
                $info = isset($row['issuance_certificate_information']) && is_array($row['issuance_certificate_information']) ? $row['issuance_certificate_information'] : [];
                // بند ۷.۶ صورتجلسه: مشخص باشد چه کسانی گواهی گرفته‌اند
                $certificate = isset($certificates[$username]) ? StudentProfile::certificateStatus($certificates[$username])[0] : 'درخواست نشده';
                $writer->addRow([
                    $writer->rowCount() + 1,
                    isset($row['first_name']) && is_scalar($row['first_name']) ? (string) $row['first_name'] : '',
                    isset($row['last_name']) && is_scalar($row['last_name']) ? (string) $row['last_name'] : '',
                    $username,
                    isset($info['id']) && is_scalar($info['id']) ? (string) $info['id'] : '',
                    $registrant['name'] . ($registrant['roleLabel'] !== '' ? ' (' . $registrant['roleLabel'] . ')' : ''),
                    UsersDirectory::courseStatus(isset($item['status']) ? $item['status'] : '0')[0],
                    $certificate,
                ]);
            }
        }
        return $this->sendXlsx($writer, 'CourseMembers');
    }

    // ============================================== اکشن‌های کمکی فرم (سازگار با صفحه‌ی ویرایش)

    public function actionBrokers($id)
    {
        return $this->actionBrokers1($id);
    }

    /**
     * HTML فیلدهای کارگزار/مدرس/درس برای یک واحد (صفحه‌ی ویرایش دوره از این قالب استفاده می‌کند).
     */
    public function actionBrokers1($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $options = $this->actionUnitOptions($id);
        Yii::$app->response->format = Response::FORMAT_JSON;
        $select = function ($name, $items, $prompt, $required, $extra = '') {
            if (empty($items))
                return null;
            $html = '<select name="' . Html::encode($name) . '" class="select2 form-select"' . ($required ? ' required' : '') . $extra . '>';
            $html .= '<option value="">' . Html::encode($prompt) . '</option>';
            foreach ($items as $item)
                $html .= '<option value="' . Html::encode($item['id']) . '">' . Html::encode($item['name']) . '</option>';
            return $html . '</select>';
        };
        $contractsUrl = Html::encode(Url::to(['broker_contracts']));
        return [
            'brokers' => $select('Courses[broker][_id]', $options['brokers'], 'لطفا کارگزار را انتخاب کنید', false,
                ' onchange="$.get(\'' . $contractsUrl . '\', {id: this.value}).done(function (d) { $(\'#broker_contracts, #broker_contracts1\').html(d); });"'),
            'teachers' => $select('Courses[lessons][0][teachers]', $options['teachers'], 'لطفا مدرس را انتخاب کنید', true),
            'lessons' => $select('Courses[lessons][0][_id]', $options['lessons'], 'لطفا درس را انتخاب کنید', true),
            'archive' => '<select name="Courses[lessons][0][hide_archive]" class="form-select" required><option value="0">خیر</option><option value="1">بله</option></select>',
        ];
    }

    public function actionBroker_contracts($id)
    {
        $broker = is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id) ? Brokers::findOne($id) : null;
        if ($broker === null)
            return '';
        $allowed = CourseAccess::isAdmin() || count(array_intersect(StudentAccess::normalizeColleges($broker->college), CourseAccess::units())) > 0;
        if (CourseAccess::role() === 'broker') {
            $own = CourseAccess::broker();
            $allowed = $own !== null && (string) $own->_id === (string) $broker->_id;
        }
        if (!$allowed)
            return '';
        $html = '<select name="Courses[broker][contract]" class="select2 form-select" required><option value="">لطفا قرارداد کارگزار را انتخاب کنید</option>';
        foreach ((array) $broker->contracts as $contract)
            if (is_array($contract) && isset($contract['id']))
                $html .= '<option value="' . Html::encode($contract['id']) . '">' . Html::encode((isset($contract['title']) ? $contract['title'] : '') . (isset($contract['share']) ? ' (' . $contract['share'] . ' درصد)' : '')) . '</option>';
        return $html . '</select>';
    }

    public function actionCapacity($id)
    {
        if ((string) $id !== Courses::CAPACITY_TYPE_LIMITED)
            return '';
        return '<label class="form-label">ظرفیت *</label><input type="number" min="1" step="1" name="Courses[student_capacity][number]" class="form-control text-start" required data-input="digits">';
    }

    public function actionCourse_date($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if ((string) $id === '3')
            return ['from' => '<div></div>', 'to' => '<div></div>', 'time' => '<div></div>'];
        return [
            'from' => '<label class="form-label">تاریخ شروع دوره *</label><input type="text" name="Courses[lessons][0][date][from]" class="form-control dob-picker text-start" required>',
            'to' => '<label class="form-label">تاریخ اتمام دوره *</label><input type="text" name="Courses[lessons][0][date][to]" class="form-control dob-picker text-start" required>',
            'time' => '<label class="form-label">ساعت شروع دوره *</label><input type="text" name="Courses[lessons][0][date][time]" class="form-control text-start" required>',
        ];
    }

    /**
     * بررسی وجود نام کاربری هنگام افزودن عضو (فقط نام و نام خانوادگی برگردانده می‌شود).
     */
    public function actionCheckUsername()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $username = Yii::$app->request->post('username');
        if (!is_string($username) || trim($username) === '')
            return ['success' => false, 'message' => 'نام کاربری ارسال نشده است'];
        $user = Users::find()->select(['first_name', 'last_name'])->where(['username' => mb_strtolower(trim($username), 'UTF-8')])->asArray()->one();
        if ($user === null)
            return ['success' => true, 'exists' => false];
        return ['success' => true, 'exists' => true, 'userInfo' => [
            'first_name' => isset($user['first_name']) && is_scalar($user['first_name']) ? (string) $user['first_name'] : '',
            'last_name' => isset($user['last_name']) && is_scalar($user['last_name']) ? (string) $user['last_name'] : '',
        ]];
    }

    // ============================================== متدهای کمکی که ویوها صدا می‌زنند

    public function my_brokers($college)
    {
        if (!CourseAccess::isAdmin() && !in_array((string) $college, CourseAccess::units(), true))
            return null;
        $brokers = Brokers::find()->where(['college' => (string) $college])->all();
        return $brokers ?: null;
    }

    public function my_broker_contract($_id)
    {
        $broker = is_string($_id) && preg_match('/^[a-f0-9]{24}$/i', $_id) ? Brokers::findOne($_id) : null;
        return $broker !== null ? $broker->contracts : null;
    }

    public function my_courses($college)
    {
        return Lessons::find()->where(['college' => (string) $college])->all();
    }

    public function my_teachers($college)
    {
        return Teachers::find()->all();
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }

    public function lesson_contents($courseId, $lessonId)
    {
        return CoursesContents::find()->where(['course_id' => (string) $courseId, 'lesson_id' => (string) $lessonId])->all();
    }

    public function score_status($courseId, $userId)
    {
        $has = Scores::find()->where(['course_id' => (string) $courseId, 'user_id' => (string) $userId])->exists();
        return '<i class="bx ' . ($has ? 'bx-check-circle text-success' : 'bx-x-circle text-danger') . '" style="font-size:1.4rem"></i>';
    }

    public function allow($college)
    {
        return CourseAccess::isAdmin() || in_array((string) $college, CourseAccess::units(), true);
    }

    /**
     * آیا هنوز در مهلت ثبت عضو (یک‌چهارم اول دوره) هستیم. خروجی JSON برای سازگاری با ویو.
     */
    public function check_date($startDateJalali, $endDateJalali)
    {
        $start = ShortCourseForm::toTimestamp($startDateJalali);
        $end = ShortCourseForm::toTimestamp($endDateJalali);
        if ($start === null || $end === null)
            return json_encode(['allowDeadlineDate' => true, 'deadlineTimestamp' => null]);
        $deadline = $start + ($end - $start) / 4;
        return json_encode(['allowDeadlineDate' => time() <= $deadline + 86399, 'deadlineTimestamp' => $deadline]);
    }

    // ================================================================== کمکی

    /**
     * کد مجوز = کد واحد + شمارنده‌ی سراسری (اتمیک).
     */
    private function assignLicenseCode(Courses $model)
    {
        $unit = Colleges::findOne($model->college);
        if ($unit === null || !is_scalar($unit->prefix) || (string) $unit->prefix === '')
            return false;
        $doc = Yii::$app->mongodb->getCollection(['eec', 'generals'])
            ->findAndModify(['type' => 'license_code'], ['$inc' => ['data' => 1]], ['new' => true]);
        if (!isset($doc['data']))
            return false;
        $model->license_code = $unit->prefix . '-' . (int) $doc['data'];
        return true;
    }

    private function selectableUnits()
    {
        $titles = UsersDirectory::collegeTitles();
        if (CourseAccess::isAdmin())
            return $titles;
        $result = [];
        foreach (CourseAccess::units() as $id)
            if (isset($titles[$id]))
                $result[$id] = $titles[$id];
        return $result;
    }

    /**
     * واحدهای قابل انتخاب در فرم ثبت دوره: مدیر سیستم همه؛ بقیه فقط واحد خودشان.
     */
    private function creatableUnits()
    {
        $titles = UsersDirectory::collegeTitles();
        if (CourseAccess::canChooseUnit())
            return $titles;
        $result = [];
        foreach (CourseAccess::ownUnits() as $id)
            if (isset($titles[$id]))
                $result[$id] = $titles[$id];
        return $result;
    }

    private function selectableBrokers()
    {
        $query = Brokers::find()->select(['connector_info', 'company_info', 'type'])->orderBy(['_id' => SORT_DESC]);
        if (CourseAccess::role() === 'broker')
            return [];
        if (!CourseAccess::isAdmin()) {
            $units = CourseAccess::units();
            $query->where(empty($units) ? ['_id' => null] : ['college' => $units]);
        }
        $result = [];
        foreach ($query->all() as $broker) {
            $ci = is_array($broker->connector_info) ? $broker->connector_info : [];
            $result[(string) $broker->_id] = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
        }
        return $result;
    }

    private function selectableTeachers()
    {
        $query = Teachers::find()->select(['first_name', 'last_name'])->orderBy(['last_name' => SORT_ASC]);
        if (!CourseAccess::isAdmin()) {
            $units = CourseAccess::units();
            $query->where(empty($units) ? ['_id' => null] : ['colleges' => $units]);
        }
        $result = [];
        foreach ($query->all() as $teacher)
            $result[(string) $teacher->_id] = trim($teacher->first_name . ' ' . $teacher->last_name);
        return $result;
    }

    /**
     * گزینه‌های «نوع ظرفیت» (بند ۶ صورتجلسه: «نامحدود» حذف). ظرفیت محدود فقط وقتی شناسه‌ی حساب واحد ثبت شده باشد.
     */
    private function capacityTypes()
    {
        $types = ['2' => 'محدود', '3' => 'سازمانی'];
        if (CourseAccess::isAdmin())
            return $types;
        foreach (CourseAccess::units() as $id) {
            $unit = Colleges::findOne($id);
            if ($unit !== null && is_array($unit->financial_info) && !empty($unit->financial_info['id']))
                return $types;
        }
        return ['3' => 'سازمانی'];
    }

    private function namesById($class, array $ids, $field = null)
    {
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id);
        })));
        if (empty($ids))
            return [];
        $result = [];
        foreach ($class::find()->where(['_id' => $ids])->all() as $model)
            $result[(string) $model->_id] = $field !== null ? (string) $model->$field : trim($model->first_name . ' ' . $model->last_name);
        return $result;
    }

    private function brokerNames(array $ids)
    {
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id);
        })));
        if (empty($ids))
            return [];
        $result = [];
        foreach (Brokers::find()->select(['connector_info'])->where(['_id' => $ids])->asArray()->all() as $broker) {
            $ci = isset($broker['connector_info']) && is_array($broker['connector_info']) ? $broker['connector_info'] : [];
            $result[(string) $broker['_id']] = trim((isset($ci['first_name']) ? $ci['first_name'] : '') . ' ' . (isset($ci['last_name']) ? $ci['last_name'] : ''));
        }
        return $result;
    }

    private function memberCounts(array $courseIds)
    {
        if (empty($courseIds))
            return [];
        $result = [];
        foreach (Users::getCollection()->aggregate([
            ['$match' => ['courses._id' => ['$in' => $courseIds]]],
            ['$project' => ['courses._id' => 1]],
            ['$unwind' => '$courses'],
            ['$match' => ['courses._id' => ['$in' => $courseIds]]],
            ['$group' => ['_id' => '$courses._id', 'count' => ['$sum' => 1]]],
        ]) as $row)
            $result[(string) $row['_id']] = (int) $row['count'];
        return $result;
    }

    private function sendXlsx(XlsxWriter $writer, $name)
    {
        $path = Yii::getAlias('@runtime') . '/' . $name . '-' . bin2hex(random_bytes(6)) . '.xlsx';
        $writer->save($path);
        $response = Yii::$app->response->sendFile($path, $name . '-' . UsersDirectory::jdate('Y-m-d-H-i', time(), 'en') . '.xlsx');
        $response->on(Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    /**
     * @return Courses|null فقط دوره‌ی کوتاه‌مدت با شناسه‌ی معتبر
     */
    private function findCourse($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id))
            return null;
        $course = Courses::findOne($id);
        return $course !== null && (string) $course->type === ShortCoursesSearch::TYPE ? $course : null;
    }

    private function firstError($model)
    {
        foreach ($model->getFirstErrors() as $error)
            return $error;
        return 'اطلاعات وارد شده معتبر نیست';
    }

    private function back($type, $message, $url = null)
    {
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect($url !== null ? $url : SafeRedirect::referrer(['index']));
    }
}
