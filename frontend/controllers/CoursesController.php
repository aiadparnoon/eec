<?php

namespace frontend\controllers;

use Yii;
use app\models\Brokers;
use app\models\ClassroomServers;
use app\models\Colleges;
use app\models\Courses;
use app\models\CoursesContents;
use app\models\CourseMembersSearch;
use app\models\Discounts;
use app\models\Generals;
use app\models\Lessons;
use app\models\Scores;
use app\models\ShortCoursesSearch;
use app\models\Teachers;
use app\models\Users;
use app\components\CourseAccess;
use app\components\CourseOptions;
use app\components\CourseStatus;
use app\components\SafeRedirect;
use app\components\SecureUpload;
use app\components\ShortCourseForm;
use app\components\StudentAccess;
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
                            'show_course_users', 'delete_course', 'toggle-site', 'register-online'],
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
        return CourseOptions::unitOptions($id);
    }

    // ================================================================== ثبت و ویرایش

    public function actionNew()
    {
        $model = new Courses();
        $model->scenario = Courses::SCENARIO_CREATE_COURSE;
        if (!ShortCourseForm::apply($model, Yii::$app->request->post('Courses'), true))
            return $this->back('error', ShortCourseForm::$error, null, ShortCourseForm::$errorField);

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
                return $this->back('error', 'فایل قرارداد: ' . SecureUpload::$lastError, null, 'Courses[contract_file]');
            $model->contract_file = $name;
        }

        $lesson = Lessons::findOne($model->lessons[0]['_id']);
        if ($lesson !== null)
            $model->preview_image = $lesson->imagePreview;

        if (!$model->validate()) {
            SecureUpload::delete(self::CONTRACT_DIR, $model->contract_file);
            return $this->back('error', $this->firstError($model), null, $this->errorField($model));
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
            return $this->back('error', ShortCourseForm::$error, null, ShortCourseForm::$errorField);
        if (!$model->validate())
            return $this->back('error', $this->firstError($model), null, $this->errorField($model));

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

        $searchModel = new CourseMembersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $courseDetail->_id);
        return $this->render('edit-course', [
            'memberStats' => CourseMembersSearch::stats((string) $courseDetail->_id),
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

    /** سازگاری با پیوندهای قدیمی: خروجی اعضا به CourseMembersController منتقل شده است */
    public function actionMembers_report()
    {
        return $this->redirect(array_merge(['course-members/report'], Yii::$app->request->queryParams));
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

    // کمکی‌های مشترک با دوره‌های میان‌مدت در CourseOptions

    private function assignLicenseCode(Courses $model)
    {
        return CourseOptions::assignLicenseCode($model);
    }

    private function selectableUnits()
    {
        return CourseOptions::selectableUnits();
    }

    private function creatableUnits()
    {
        return CourseOptions::creatableUnits();
    }

    private function selectableBrokers()
    {
        return CourseOptions::selectableBrokers();
    }

    private function selectableTeachers()
    {
        return CourseOptions::selectableTeachers();
    }

    private function capacityTypes()
    {
        return CourseOptions::capacityTypes();
    }

    private function namesById($class, array $ids, $field = null)
    {
        return CourseOptions::namesById($class, $ids, $field);
    }

    private function brokerNames(array $ids)
    {
        return CourseOptions::brokerNames($ids);
    }

    private function memberCounts(array $courseIds)
    {
        return CourseOptions::memberCounts($courseIds);
    }

    private function sendXlsx(XlsxWriter $writer, $name)
    {
        return CourseOptions::sendXlsx($writer, $name);
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

    /**
     * پیام و بازگشت. درخواست پس‌زمینه‌ی فرم (AJAX) → JSON با نام فیلد خطا تا پیام زیر همان فیلد نمایش داده شود
     * و اطلاعات واردشده از دست نرود.
     */
    private function back($type, $message, $url = null, $field = null)
    {
        $url = $url !== null ? $url : SafeRedirect::referrer(['index']);
        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($type === 'error')
                return ['ok' => false, 'message' => $message, 'field' => $field];
            Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
            return ['ok' => true, 'redirect' => Url::to($url)];
        }
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect($url);
    }

    /** نام فیلد فرم برای اولین خطای مدل (title[main_fa] → Courses[title][main_fa]) */
    private function errorField(Courses $model)
    {
        foreach (array_keys($model->getFirstErrors()) as $attribute) {
            $map = ['student_capacity' => 'student_capacity[type]', 'title' => 'title[main_fa]'];
            $attribute = isset($map[$attribute]) ? $map[$attribute] : $attribute;
            return 'Courses[' . preg_replace('/^([^\[]+)/', '$1]', $attribute, 1);
        }
        return null;
    }
}
