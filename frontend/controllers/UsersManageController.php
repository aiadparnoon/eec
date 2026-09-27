<?php

namespace frontend\controllers;

use Yii;
use app\models\Users;
use app\models\UsersSearch;
use app\models\Courses;
use app\components\StudentAccess;
use app\components\StudentProfile;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\components\SecureFile;
use app\components\classroom\ClassroomPlatforms;
use common\models\Admin;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii2tech\spreadsheet\Spreadsheet;

/**
 * مدیریت دانشپذیران (مجموعه‌ی users) — docs/specs/users-manage.md
 *
 * همه‌ی اکشن‌هایی که روی یک دانشپذیر کار می‌کنند، او را از مسیر StudentAccess
 * بارگذاری می‌کنند تا کارشناس فقط به دانشپذیران دانشکده‌ی خودش دسترسی داشته باشد.
 */
class UsersManageController extends Controller
{
    const FLASH = 'users-manage';

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
                        'actions' => ['index', 'report', 'profile', 'profile_tab', 'document', 'new_user', 'excel_template',
                            'check_excel_file', 'add_user_from_excel', 'change_status', 'change_user_course_status',
                            'reregister_course', 'edit_user', 'change_password'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $identity = Yii::$app->user->identity;
                            return (is_array($identity->access) && array_search(Yii::$app->controller->id, $identity->access) !== false)
                                || $identity->role == 'user' || $identity->role == 'broker';
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
                    'new_user' => ['post'],
                    'check_excel_file' => ['post'],
                    'add_user_from_excel' => ['post'],
                    'change_status' => ['post'],
                    'change_user_course_status' => ['post'],
                    'reregister_course' => ['post'],
                    'edit_user' => ['post'],
                    'change_password' => ['post'],
                ],
            ],
        ];
    }

    // ================================================================== فهرست

    public function actionIndex()
    {
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $students = $dataProvider->getModels();

        $registrantUsernames = [];
        foreach ($students as $student)
            $registrantUsernames[] = (string) $student->registrant;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'students' => $students,
            'registrants' => UsersDirectory::registrants($registrantUsernames),
            'stats' => UsersSearch::stats(),
            'colleges' => UsersDirectory::selectableColleges(),
            'courses' => $this->selectableCourses(),
        ]);
    }

    /**
     * خروجی اکسل با همان فیلترهای صفحه (همه‌ی ردیف‌ها، نه فقط صفحه‌ی جاری).
     */
    public function actionReport()
    {
        date_default_timezone_set('Asia/Tehran');
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, false);
        $students = $dataProvider->getModels();
        $usernames = [];
        foreach ($students as $student)
            $usernames[] = (string) $student->registrant;
        $registrants = UsersDirectory::registrants($usernames);

        $exporter = new Spreadsheet([
            'dataProvider' => new \yii\data\ArrayDataProvider(['allModels' => $students, 'pagination' => false]),
            'columns' => [
                ['header' => 'نام', 'attribute' => 'first_name'],
                ['header' => 'نام خانوادگی', 'attribute' => 'last_name'],
                ['header' => 'نام کاربری', 'value' => function ($model) {
                    return ' ' . $model->username . ' ';
                }],
                ['header' => 'کد ملی', 'value' => function ($model) {
                    $info = $model->issuance_certificate_information;
                    return is_array($info) && !empty($info['id']) ? ' ' . $info['id'] . ' ' : '-';
                }],
                ['header' => 'دانشکده‌ها', 'value' => function ($model) {
                    $names = UsersDirectory::collegeNames($model->college);
                    return empty($names) ? '-' : implode('، ', $names);
                }],
                ['header' => 'ثبت کننده', 'value' => function ($model) use ($registrants) {
                    $r = UsersDirectory::describeRegistrant($model, $registrants);
                    return $r['name'] . ($r['roleLabel'] !== '' ? ' (' . $r['roleLabel'] . ')' : '');
                }],
                ['header' => 'تعداد دوره', 'value' => function ($model) {
                    return is_array($model->courses) ? count($model->courses) : 0;
                }],
                ['header' => 'وضعیت', 'value' => function ($model) {
                    return $model->status == Users::STATUS_INACTIVE ? 'غیر فعال' : 'فعال';
                }],
                ['header' => 'تاریخ ثبت', 'value' => function ($model) {
                    return UsersDirectory::jdate('Y/m/d', hexdec(substr((string) $model->_id, 0, 8)));
                }],
            ],
        ]);
        $path = Yii::getAlias('@runtime') . '/users-export-' . bin2hex(random_bytes(6)) . '.xlsx';
        $exporter->save($path);
        require_once Yii::getAlias('@frontend') . '/web/jdf.php';
        $response = Yii::$app->response->sendFile($path, 'Members-' . jdate('Y-m-d-H-i', '', '', 'Asia/Tehran', 'en') . '.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    /**
     * دوره‌هایی که در فیلتر «دوره» نمایش داده می‌شوند.
     *
     * @return array [courseId => title]
     */
    private function selectableCourses()
    {
        $query = Courses::find()->select(['_id', 'title', 'college'])->orderBy(['_id' => SORT_DESC]);
        if (!StudentAccess::isAdmin()) {
            $colleges = StudentAccess::staffColleges();
            if (empty($colleges))
                return [];
            $query->where(['college' => $colleges]);
        }
        $result = [];
        foreach ($query->all() as $course)
            $result[(string) $course->_id] = StudentProfile::courseTitle($course);
        return $result;
    }

    // ================================================================== افزودن

    public function actionNew_user()
    {
        $input = Yii::$app->request->post('Users');
        $input = is_array($input) ? $input : [];
        $username = UsersImport::normalizeUsername(isset($input['username']) ? $input['username'] : '');
        $password = isset($input['password_hash']) ? (string) $input['password_hash'] : '';
        $firstName = trim(isset($input['first_name']) ? (string) $input['first_name'] : '');
        $lastName = trim(isset($input['last_name']) ? (string) $input['last_name'] : '');

        if ($firstName === '' || $lastName === '' || $password === '' || !UsersImport::isValidUsername($username))
            return $this->back('error', 'نام، نام خانوادگی، رمز عبور و نام کاربری معتبر (موبایل یا ایمیل) الزامی است');

        $colleges = StudentAccess::collegesForNewStudent();
        $existing = Users::find()->where(['username' => $username])->one();
        if ($existing !== null) {
            // مانند ورود از اکسل: کاربر موجود خطا نیست؛ دانشکده‌ی کارشناس به او اضافه می‌شود
            if (!empty($colleges) && StudentAccess::addColleges($existing, $colleges)) {
                if ($existing->save(false, ['college', 'updated_at']))
                    return $this->back('info', 'این نام کاربری از قبل وجود داشت؛ دانشپذیر به دانشکده‌ی شما اضافه شد', ['profile', 'id' => (string) $existing->_id]);
                return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
            }
            return $this->back('warning', 'دانشپذیری با این نام کاربری از قبل وجود دارد');
        }

        $model = new Users();
        $model->first_name = $firstName;
        $model->last_name = $lastName;
        $model->username = $username;
        $model->setPassword($password);
        $model->auth_key = Yii::$app->security->generateRandomString();
        $model->verification_token = Yii::$app->security->generateRandomString();
        $model->role = 'user';
        $model->status = Users::STATUS_ACTIVE;
        $model->registrant = (string) Yii::$app->user->identity->username;
        // ثبت توسط کارشناس: دانشکده‌ی او به آرایه‌ی college اضافه می‌شود تا دسترسی داشته باشد؛
        // ثبت توسط مدیر: آرایه‌ی خالی (بدون دانشکده). فرم، دانشکده نمی‌پرسد.
        $model->college = [];
        StudentAccess::addColleges($model, $colleges);
        if ($model->save())
            return $this->back('success', 'دانشپذیر جدید با موفقیت ثبت شد');
        return $this->back('error', 'خطا در ثبت دانشپذیر، لطفاً دوباره تلاش کنید');
    }

    public function actionExcel_template()
    {
        $path = UsersImport::template();
        $response = Yii::$app->response->sendFile($path, 'users-import-template.xlsx');
        $response->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($path) {
            @unlink($path);
        });
        return $response;
    }

    /**
     * مرحله‌ی پیش‌نمایش: فایل ذخیره، بررسی و جدول خطاها برگردانده می‌شود.
     */
    public function actionCheck_excel_file()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $stored = UsersImport::store(isset($_FILES['file']) ? $_FILES['file'] : null);
        if ($stored['error'] !== null)
            return ['ok' => false, 'html' => $this->renderPartial('_alert', ['type' => 'danger', 'message' => $stored['error']])];

        $analysis = UsersImport::analyze(UsersImport::pathFor($stored['token']));
        if ($analysis['fatal'] !== null) {
            UsersImport::discard();
            return ['ok' => false, 'html' => $this->renderPartial('_alert', ['type' => 'danger', 'message' => $analysis['fatal']])];
        }
        if ($analysis['errorCount'] > 0)
            UsersImport::discard(); // با خطا امکان ثبت نیست؛ کاربر فایل اصلاح‌شده را دوباره می‌فرستد
        return [
            'ok' => $analysis['errorCount'] === 0,
            'html' => $this->renderPartial('_import-preview', [
                'analysis' => $analysis,
                'token' => $analysis['errorCount'] === 0 ? $stored['token'] : null,
                'collegeNames' => UsersDirectory::collegeNames(StudentAccess::collegesForNewStudent()),
            ]),
        ];
    }

    /**
     * ثبت نهایی. فایل دوباره بررسی می‌شود (به پیش‌نمایش اعتماد نمی‌کنیم) و بلافاصله حذف می‌شود.
     */
    public function actionAdd_user_from_excel()
    {
        $path = UsersImport::pathFor(Yii::$app->request->post('token'));
        if ($path === null)
            return $this->back('error', 'فایل پیدا نشد یا منقضی شده است؛ لطفاً دوباره بارگذاری کنید');
        try {
            $analysis = UsersImport::analyze($path);
            if ($analysis['fatal'] !== null || $analysis['errorCount'] > 0)
                return $this->back('error', 'فایل دارای خطا است؛ لطفاً اصلاح و دوباره بارگذاری کنید');
            $report = UsersImport::import($analysis);
        } finally {
            UsersImport::discard();
        }
        Yii::$app->session->setFlash('users-import-report', $report);
        return $this->back('success', 'ورود اطلاعات از فایل اکسل انجام شد');
    }

    // ================================================================== پروفایل

    public function actionProfile($id)
    {
        $student = StudentAccess::findStudent($id);
        if ($student === null)
            return $this->back('error', 'دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید', ['index']);
        $profile = new StudentProfile($student);
        return $this->render('profile', [
            'student' => $student,
            'profile' => $profile,
            'registrant' => UsersDirectory::describeRegistrant($student, UsersDirectory::registrants([(string) $student->registrant])),
        ]);
    }

    /**
     * محتوای تب‌های پروفایل (بارگذاری تنبل با AJAX).
     */
    public function actionProfile_tab($id, $tab)
    {
        $student = $this->findStudentOr404($id);
        $tabs = ['info', 'courses', 'payments', 'cheques', 'documents'];
        if (!in_array($tab, $tabs, true))
            throw new NotFoundHttpException();
        $profile = new StudentProfile($student);
        return $this->renderPartial('profile/_tab-' . $tab, [
            'student' => $student,
            'profile' => $profile,
            'registrants' => UsersDirectory::registrants(array_merge(
                [(string) $student->registrant],
                array_map(function ($c) {
                    return isset($c['item']['registrant']) ? (string) $c['item']['registrant'] : '';
                }, $profile->courses())
            )),
        ]);
    }

    /**
     * دانلود/نمایش مدارک بارگذاری‌شده‌ی دانشپذیر (کارت ملی، آخرین مدرک تحصیلی).
     */
    public function actionDocument($id, $type, $inline = 0)
    {
        $student = $this->findStudentOr404($id);
        if (!in_array($type, ['id_file', 'degree_education_file'], true))
            throw new NotFoundHttpException();
        $info = $student->issuance_certificate_information;
        $filename = is_array($info) && !empty($info[$type]) ? (string) $info[$type] : '';
        $path = $filename === '' ? null : SecureFile::resolve(Yii::getAlias('@frontend/web/certificate_files'), $filename);
        if ($path === null)
            throw new NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path), ['inline' => (bool) $inline]);
    }

    // ================================================================== تغییرات

    public function actionChange_status()
    {
        $user = $this->findManagedStudent();
        if ($user === null)
            return $this->back('error', 'دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید');
        $user->status = $user->status == Users::STATUS_INACTIVE ? Users::STATUS_ACTIVE : Users::STATUS_INACTIVE;
        if ($user->save(false, ['status', 'updated_at']))
            return $this->back('success', $user->status == Users::STATUS_ACTIVE ? 'دانشپذیر فعال شد' : 'دانشپذیر غیرفعال شد');
        return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
    }

    /**
     * فعال/غیرفعال کردن دانشپذیر در یک دوره (منطق packages/change_status):
     *  فعال → غیرفعال: حذف از کلاس آنلاین.
     *  غیرفعال → برای دوره‌ی دارای کلاس آنلاین '0' و ثبت مجدد در سامانه؛ در غیر این صورت فعال.
     */
    public function actionChange_user_course_status()
    {
        list($user, $row, $course) = $this->findManagedCourse();
        if ($user === null)
            return $this->back('error', 'دوره یا دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید');

        $courses = $user->courses;
        $current = isset($courses[$row]['status']) ? (string) $courses[$row]['status'] : '0';
        $online = ClassroomPlatforms::hasOnlineClass($course);
        $platform = ClassroomPlatforms::forCourse($course);
        $warning = null;

        if ($current === '1') {
            $courses[$row]['status'] = '2';
            $user->courses = $courses;
            if (!$user->save(false, ['courses', 'updated_at']))
                return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
            if ($online && !$platform->removeCourseUser($user, (string) $course->_id))
                $warning = 'وضعیت تغییر کرد اما حذف از کلاس ' . $platform->title() . ' ناموفق بود';
            return $warning ? $this->back('warning', $warning) : $this->back('success', 'دانشپذیر در این دوره غیرفعال شد');
        }

        $courses[$row]['status'] = $online ? '0' : '1';
        $user->courses = $courses;
        if (!$user->save(false, ['courses', 'updated_at']))
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
        if ($online && !$platform->registerCourseUsers((string) $course->_id))
            return $this->back('warning', 'ثبت در کلاس ' . $platform->title() . ' ناموفق بود؛ بعداً از دکمه‌ی «ثبت مجدد» استفاده کنید');
        return $this->back('success', 'دانشپذیر در این دوره فعال شد');
    }

    /**
     * «ثبت مجدد» در سامانه‌ی کلاس آنلاین برای دوره‌ای که وضعیت کاربر در آن '0' است.
     * فعلاً سرویس در سطح دوره است (همه‌ی کاربرانِ در انتظار آن دوره).
     */
    public function actionReregister_course()
    {
        list($user, $row, $course) = $this->findManagedCourse();
        if ($user === null)
            return $this->back('error', 'دوره یا دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید');
        if (!ClassroomPlatforms::hasOnlineClass($course))
            return $this->back('warning', 'این دوره کلاس آنلاین ندارد');
        $platform = ClassroomPlatforms::forCourse($course);
        if ($platform->registerCourseUsers((string) $course->_id))
            return $this->back('success', 'درخواست ثبت مجدد در ' . $platform->title() . ' ارسال شد؛ وضعیت پس از پردازش به‌روز می‌شود');
        return $this->back('error', 'ارتباط با سرور ' . $platform->title() . ' برقرار نشد، لطفاً دوباره تلاش کنید');
    }

    public function actionEdit_user()
    {
        $user = $this->findManagedStudent();
        if ($user === null)
            return $this->back('error', 'دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید');
        // فقط فیلدهای فرم ویرایش؛ نه college/registrant/courses/principal_id
        $input = Yii::$app->request->post('Users');
        $input = is_array($input) ? $input : [];
        $firstName = trim(isset($input['first_name']) ? (string) $input['first_name'] : '');
        $lastName = trim(isset($input['last_name']) ? (string) $input['last_name'] : '');
        if ($firstName === '' || $lastName === '')
            return $this->back('error', 'نام و نام خانوادگی الزامی است');
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        if ($user->principal_id != null && !ClassroomPlatforms::forCourse()->updateUser($user))
            return $this->back('error', 'ارتباط با سرور Adobe برقرار نشد، تغییرات ذخیره نشد');
        if ($user->save(false, ['first_name', 'last_name', 'updated_at']))
            return $this->back('success', 'مشخصات دانشپذیر تغییر یافت');
        return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
    }

    public function actionChange_password()
    {
        $user = $this->findManagedStudent();
        if ($user === null)
            return $this->back('error', 'دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید');
        $input = Yii::$app->request->post('Users');
        $password = is_array($input) && isset($input['password_hash']) ? (string) $input['password_hash'] : '';
        if ($password === '')
            return $this->back('error', 'رمز عبور جدید وارد نشده است');
        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        if (!$user->save(false, ['password_hash', 'updated_at']))
            return $this->back('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید');
        // حساب مدرس (mentor) با همین نام کاربری هم هم‌زمان تغییر می‌کند
        $teacher = Admin::find()->where(['username' => $user->username])->andWhere(['mentor' => true])->one();
        if ($teacher != null) {
            $teacher->password_hash = $user->password_hash;
            $teacher->save(false, ['password_hash']);
        }
        return $this->back('success', 'رمز عبور تغییر یافت');
    }

    // ================================================================== کمکی

    /**
     * پیام را ثبت و به صفحه‌ی قبل (یا مسیر داده‌شده) برمی‌گردد.
     */
    private function back($type, $message, $url = null)
    {
        Yii::$app->session->setFlash(self::FLASH, ['type' => $type, 'message' => $message]);
        if ($url !== null)
            return $this->redirect($url);
        $referrer = Yii::$app->request->referrer;
        return $this->redirect($referrer ?: ['index']);
    }

    private function findStudentOr404($id)
    {
        $student = StudentAccess::findStudent($id);
        if ($student === null)
            throw new NotFoundHttpException('دانشپذیر مورد نظر یافت نشد یا شما به آن دسترسی ندارید.');
        return $student;
    }

    /**
     * دانشپذیرِ ارسال‌شده در Users[_id] را فقط اگر کاربر جاری به او دسترسی داشته باشد
     * برمی‌گرداند (جلوگیری از IDOR).
     *
     * @return Users|null
     */
    private function findManagedStudent()
    {
        $input = Yii::$app->request->post('Users');
        $id = is_array($input) && isset($input['_id']) ? $input['_id'] : null;
        return StudentAccess::findStudent($id);
    }

    /**
     * دانشپذیر + ردیف دوره در users.courses + خود دوره، با بررسی دسترسی به هر دو.
     *
     * @return array [Users|null, int|null, Courses|null]
     */
    private function findManagedCourse()
    {
        $user = $this->findManagedStudent();
        $courseId = (string) Yii::$app->request->post('courseId');
        $row = Yii::$app->request->post('row');
        if ($user === null || !is_array($user->courses) || !is_scalar($row) || !isset($user->courses[$row]['_id'])
            || (string) $user->courses[$row]['_id'] !== $courseId || !preg_match('/^[a-f0-9]{24}$/i', $courseId))
            return [null, null, null];
        $course = Courses::findOne($courseId);
        if ($course === null || !StudentAccess::canManageCourse($course))
            return [null, null, null];
        return [$user, $row, $course];
    }
}
