<?php

namespace frontend\controllers;

use Composer\Package\Package;
use DateInterval;
use DateTime;
use stdClass;
use Yii;
use app\models\Courses;
use app\models\Orders;
use app\models\Installments;
use app\models\Generals;
use app\models\Users;
use app\models\CoursesSearch;
use app\models\Colleges;
use app\models\Principals;
use app\models\Brokers;
use app\models\Teachers;
use app\models\Lessons;
use app\models\Scores;
use app\models\CoursesMembers;
use app\models\Discounts;
use common\models\Admin;
use app\models\CoursesFinancial;
use app\models\OrganizationPayments;
use app\models\WalletTransactions;
use app\models\CancelingRequests;
use app\components\StudentAccess;
use app\components\CourseAccess;
use app\components\CourseOptions;
use app\components\CourseStatus;
use app\components\SafeRedirect;
use app\components\UsersDirectory;
use app\components\XlsxWriter;
use app\components\classroom\ClassroomPlatforms;
use app\models\PackagesSearch;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\User;
use yii\widgets\ActiveForm;
use yii2tech\spreadsheet\Spreadsheet;
use PHPExcel_IOFactory;
require_once(Yii::$app->basePath . '/web/jdf.php');
/**
 * CoursesController implements the CRUD actions for Courses model.
 */
class PackagesController extends Controller
{
    /**
     * {@inheritdoc}
     */
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
                        'actions' => ['index', 'report','file','members_report', 'edit', 'brokers', 'brokers1', 'broker_contracts', 'capacity', 'course_date', 'my_brokers', 'show_courses', 'edit-package', 'edit_course_in_package', 'add_lesson_to_package','edit_prepayment_installments','edit_installments','delete_installments','show_course_users','delete_course','add_installment','add_single_installment','show_course_lessons','show_finance_info','register_course_scores','other_teachers','register-online','toggle-site','add_discount','delete_discount','recording-grades','register_grades','copy-package','courses_financial','courses_financial_callback','test','contract_file','add_credit_request','add_credit_callback','pay_add_credit'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['create-package', 'new', 'unit-options', 'installments-plan', 'new_user' ,'show_user_detail','add_user_from_list','check_excel_file','add_user_from_exel','change_status','change_role','delete_user_from_course','add_user_to_adobe','contract_file'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if ((array_search(Yii::$app->controller->id, Yii::$app->user->identity->access) !== false || Yii::$app->user->identity->role == 'user') && Yii::$app->user->identity->role != 'teacher')
                                return true;
                            else
                                return false;
                        }
                    ],
                    [
                        'actions' => ['confirm_package', 'back_package'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            if (Yii::$app->user->identity->role == 'user')
                                return true;
                            else
                                return false;
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
                    'logout' => ['post'],
                    'new' => ['post'],
                    'edit' => ['post'],
                    'installments-plan' => ['post'],
                    'copy-package' => ['post'],
                    'show_course_users' => ['post'],
                    'delete_course' => ['post'],
                    'toggle-site' => ['post'],
                    'register-online' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Courses models.
     * Lists all Courses models.
     * @return mixed
     */
    /**
     * امنیتی: بسیاری از اکشن‌های این کنترلر (اعضا، تخفیف، کلاس آنلاین، نمره، ...) که صفحه‌ی ویرایش
     * دوره‌های کوتاه‌مدت و میان‌مدت از آن‌ها استفاده می‌کند، بررسی نمی‌کردند که دوره متعلق به محدوده‌ی
     * کاربر هست یا نه (IDOR). تا بازطراحی این بخش، اینجا دوره‌ی هدفِ هر درخواست پیدا و با
     * CourseAccess بررسی می‌شود. تخفیف شهریه فقط توسط مدیر (بند ۵.۲ صورتجلسه).
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action))
            return false;
        if (Yii::$app->user->isGuest)
            return true;
        // اقساط فقط از فرم کامل «شرایط اقساطی» (installments-plan) با کنترل مجموع و تاریخ‌ها
        if (in_array($action->id, ['add_installment', 'add_single_installment', 'edit_installments', 'delete_installments', 'edit_prepayment_installments'], true)) {
            Yii::$app->session->setFlash(\frontend\controllers\CourseMembersController::FLASH, ['type' => 'error', 'message' => 'شرایط اقساطی را از فرم تب «اقساط» ویرایش کنید']);
            $this->redirect(\app\components\SafeRedirect::referrer(['index']))->send();
            return false;
        }
        // افزودن عضو از اکسل برای دوره‌های کوتاه‌مدت فقط از مسیر جدید (courses/members-check) با همه‌ی کنترل‌ها
        if (in_array($action->id, ['check_excel_file', 'add_user_from_exel'], true)) {
            $id = Yii::$app->request->post('courseId', Yii::$app->request->post('packageId_', Yii::$app->request->post('packageId')));
            $course = is_string($id) && preg_match('/^[a-f0-9]{24}$/i', trim($id)) ? Courses::findOne(trim($id)) : null;
            if ($course === null || in_array((string) $course->type, ['1', '2'], true)) {
                if (Yii::$app->request->isAjax)
                    throw new \yii\web\ForbiddenHttpException('از «افزودن از فایل اکسل» در تب اعضای صفحه‌ی دوره استفاده کنید');
                Yii::$app->session->setFlash('status', '2');
                $this->redirect(\app\components\SafeRedirect::referrer(['index']))->send();
                return false;
            }
        }
        // کد تخفیف: هر کسی که دوره را مدیریت می‌کند (بررسی canManage پایین‌تر با Discounts[course_id]/Discounts[_id]).
        // سقف مبلغ (سهم کارگزار منهای ۵۰ هزار تومان) در Discounts::rules() اعمال می‌شود.
        if (in_array($action->id, ['add_discount', 'delete_discount'], true)) {
            $post = Yii::$app->request->post('Discounts');
            if (!is_array($post) || (empty($post['course_id']) && empty($post['_id']))) {
                Yii::$app->session->setFlash('status', '2');
                $this->redirect(\app\components\SafeRedirect::referrer(['index']))->send();
                return false;
            }
        }
        $request = Yii::$app->request;
        $post = $request->post();
        $candidates = [
            $request->post('courseId'), $request->post('packageId'), $request->post('course_id'),
            isset($post['Courses']['_id']) ? $post['Courses']['_id'] : null,
            isset($post['Discounts']['course_id']) ? $post['Discounts']['course_id'] : null,
            isset($post['CoursesFinancial']['course_id']) ? $post['CoursesFinancial']['course_id'] : null,
            isset($post['Users']['courses']) ? $post['Users']['courses'] : null,
        ];
        if (isset($post['Discounts']['_id']) && is_string($post['Discounts']['_id']) && preg_match('/^[a-f0-9]{24}$/i', $post['Discounts']['_id'])) {
            $discount = \app\models\Discounts::findOne($post['Discounts']['_id']);
            if ($discount !== null)
                $candidates[] = $discount->course_id;
        }
        if (in_array($action->id, ['members_report', 'recording-grades', 'edit-package', 'copy-package'], true))
            $candidates[] = $request->get('_id');
        foreach ($candidates as $id) {
            if ($id === null || $id === '')
                continue;
            $course = is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id) ? Courses::findOne($id) : null;
            // ثبت نمره توسط استادِ همان دوره هم مجاز است
            $viewOnly = $request->isGet || in_array($action->id, ['register_grades', 'register_course_scores'], true);
            $allowed = $course !== null && ($viewOnly ? \app\components\CourseAccess::canView($course) : \app\components\CourseAccess::canManage($course));
            if (!$allowed) {
                if ($request->isAjax)
                    throw new \yii\web\ForbiddenHttpException('دسترسی به این دوره مجاز نیست');
                Yii::$app->session->setFlash('status', '2');
                $this->redirect(\app\components\SafeRedirect::referrer(['index']))->send();
                return false;
            }
        }
        return true;
    }

    public function actionIndex()
    {
        $searchModel = new PackagesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $courses = $dataProvider->getModels();

        // داده‌های کمکی هر ردیف با کوئری‌های دسته‌ای
        $ids = $brokerIds = $registrants = $teacherIds = [];
        foreach ($courses as $course) {
            $ids[] = (string) $course->_id;
            if (isset($course->broker['_id']) && is_string($course->broker['_id']))
                $brokerIds[] = $course->broker['_id'];
            foreach ((array) $course->lessons as $lesson)
                if (isset($lesson['teachers']) && is_string($lesson['teachers']))
                    $teacherIds[] = $lesson['teachers'];
            $registrants[] = $course->registrant;
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'courses' => $courses,
            'teachers' => CourseOptions::namesById(Teachers::class, $teacherIds),
            'brokerNames' => CourseOptions::brokerNames($brokerIds),
            'registrants' => UsersDirectory::registrants($registrants),
            'memberCounts' => CourseOptions::memberCounts($ids),
            'stats' => PackagesSearch::stats(),
            'units' => CourseOptions::selectableUnits(),
            'brokers' => CourseOptions::selectableBrokers(),
            'filterTeachers' => CourseOptions::selectableTeachers(),
            'canCreate' => in_array(CourseAccess::role(), ['user', 'cnt', 'emp', 'broker'], true),
        ]);
    }

    public function actionTest()
    {
       echo '<pre>';
       print_r($_POST);
    }

    public function actionFile($filename)
    {
        // امنیتی: نام فایل مستقیم از درخواست می‌آید. بدون این بررسی، ورودی
        // «../../config/main-local.php» کلید cookieValidationKey را برمی‌گرداند.
        $path = \app\components\SecureFile::resolve('upload_center', $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    public function actionContract_file($filename)
    {
        // امنیتی: نام فایل مستقیم از درخواست می‌آید. بدون این بررسی، ورودی
        // «../../config/main-local.php» کلید cookieValidationKey را برمی‌گرداند.
        $path = \app\components\SecureFile::resolve('contract_files', $filename);
        if($path === null)
            throw new \yii\web\NotFoundHttpException('فایل مورد نظر پیدا نشد.');
        return Yii::$app->response->sendFile($path, basename($path));
    }

    /**
     * فرم ثبت دوره‌ی میان‌مدت (صفحه‌ی جدید، همه‌ی قواعد در PackageForm).
     */
    public function actionCreatePackage()
    {
        if (!in_array(\app\components\CourseAccess::role(), ['user', 'cnt', 'emp', 'broker'], true))
            return $this->redirect(['index']);
        return $this->render('create-package', [
            'units' => \app\components\CourseOptions::creatableUnits(),
            'servers' => \app\models\ClassroomServers::activeOptions(),
            'capacityTypes' => \app\components\CourseOptions::capacityTypes(),
        ]);
    }

    /** کارگزاران (با قرارداد)، مدرسان و دروس یک واحد برای فرم ثبت (JSON) */
    public function actionUnitOptions($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return \app\components\CourseOptions::unitOptions($id);
    }

    public function actionEditPackage($_id)
    {
        if (isset($_GET['_id']))
        {
            $id = is_string($_GET['_id']) && preg_match('/^[a-f0-9]{24}$/i', $_GET['_id']) ? $_GET['_id'] : null;
            $model = $id === null ? null : Courses::find()->where(['_id' => $id])->andWhere(['<>','type','1'])->one();
            // دسترسی بر اساس نقش (مدیر سیستم، واحد خود، کارگزارِ همان دوره، استاد دوره)
            if ($model != null && \app\components\CourseAccess::canView($model))
                return $this->renderEditPackageView($model);
        }
        return $this->redirect(['../packages']);
    }

    /**
     * Builds every piece of data the "edit-package" view needs and renders it
     * for the given $model. Extracted out of actionEditPackage() (2026-08-27)
     * so actionEdit()'s failure path (see below) can re-render the exact same
     * form - with whatever the admin just typed still in place and the specific
     * validation error shown under the right field - instead of redirecting
     * back to a page that silently re-fetches the OLD, unedited record. The
     * normal GET flow (actionEditPackage) is unchanged: it still does its own
     * access check (`$this->allow(...)`) before calling this.
     */
    private function renderEditPackageView(Courses $model)
    {
        if (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
        {
            $colleges = Colleges::find()->all();
            if ($model->lessons != null)
                if (count($model->lessons) > 0)
                    $colleges = Colleges::find()->where(['_id' => $model->college])->all();
        }
        else
            $colleges = Colleges::find()->where(['_id' => Yii::$app->user->identity->college])->all();
        $teachers = Teachers::find()->where(['like', 'colleges', $model->college])->all();
        $collegeLessons = Lessons::find()->where(['college' => $model->college])->all();
//                if($model->lessons != null)
//                    $remainingLessons = Lessons::find()->where(['college' => $model->college])->andWhere(['NOT IN', (string)'_id', $model->my_lessons['_id']])->all();
//                else
        $remainingLessons = Lessons::find()->where(['college' => $model->college])->all();
        $discounts = Discounts::find()->where(['course_id' => (string) $model->_id])->all();
        $searchModel = new \app\models\CourseMembersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (string) $model->_id);
        $courseFinancial = CoursesFinancial::find()->where(['course_id' => (string) $model->_id])->andWhere(['payment_info.status' => '2'])->orderBy(['_id'=>SORT_DESC])->all();
        $addRequests = OrganizationPayments::find()->where(['product_id' => (string) $model->_id])->all();
        $allowEdit = \app\components\CourseAccess::canEdit($model);
        return $this->render('edit-package', [
            'colleges' => ArrayHelper::map($colleges, function ($m) {
                return (string) $m->_id;
            }, 'title'),
            'model' => $model,
            'teachers' => $teachers,
            'collegeLessons' => $collegeLessons,
            'remainingLessons' => ArrayHelper::map($remainingLessons, function ($m) {
                return (string) $m->_id;
            }, 'title'),
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'discounts' => $discounts,
            'allowEdit' => $allowEdit,
            'courseFinancial' => $courseFinancial,
            'addRequests' => $addRequests,
            'memberStats' => \app\models\CourseMembersSearch::stats((string) $model->_id),
            'servers' => \app\models\ClassroomServers::activeOptions(),
        ]);
    }

    /**
     * کپی دوره‌ی میان‌مدت با دروس و شرایط اقساطی (بدون کلاس آنلاین، کد مجوز و سوابق بررسی).
     */
    public function actionCopyPackage($_id)
    {
        $course = $this->findPackage($_id);
        if ($course === null || !CourseAccess::canManage($course))
            return $this->listBack('error', 'دوره یافت نشد یا به آن دسترسی ندارید');

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
        $model->returned_by = null;
        $model->license_code = null;
        $model->rejection_reason = null;
        $model->mentors = null;
        $model->other_teachers = null;
        $model->adobe_status = null;
        $model->classroom_meetings = null;
        $model->registrant = (string) Yii::$app->user->identity->username;
        if (!$model->save(false))
            return $this->listBack('error', 'کپی دوره ناموفق بود');
        return $this->packageBack('success', 'نسخه‌ی کپی ساخته شد؛ تاریخ‌ها و شرایط اقساطی را بررسی و در صورت نیاز ویرایش کنید', ['edit-package', '_id' => (string) $model->_id]);
    }

    public function actionRecordingGrades($_id)
    {
        if (isset($_GET['_id']))
        {
            $model = Courses::findOne($_GET['_id']);
            if($model != null)
            {
                if($this->allow((string) $model->college))
                {
                    $users = Users::find()->where(['courses._id' => (string) $model->_id])->all();
                    if($users != null)
                        return $this->render('recording-grades', [
                            'model' => $model,
                            'users' => $users
                        ]);
                }
                else
                    return $this->redirect(['../packages']);
            }
        }
        return $this->redirect(['../packages']);
    }

    public function actionRegister_grades()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post('course_id'));
            if($course != null)
            {
                $totalScore = Scores::find()->where(['course_id' => (string) $course->_id])->all();
                if($totalScore != null)
                    foreach ($totalScore as $oneScore)
                        $oneScore->delete();
                $i = 0;
                foreach ($_POST as $name => $val)
                {
                    if($name != '_csrf' && $name != 'course_id')
                    {
                        $data = explode('-',$name);
                        $user = Users::findOne($data[0]);
                        if($i == 0)
                            $index = null;
                        else
                            $index = $data[0];
                        if($user != null)
                        {
                            $score = Scores::find()->where(['course_id' => (string) $course->_id])->andWhere(['user_id' => (string) $user->_id])->one();
                            if($score != null)
                            {
                                if($index != $data[0])
                                {
                                    $preScores = array();
                                    $index = $data[0];
                                }
                                else
                                    $preScores = $score->scores;
                                $newScore = new stdClass();
                                $newScore->score = $val;
                                $newScore->lesson = $data[1];
                                $newScore->registrant = Yii::$app->user->identity->username;
                                array_push($preScores, $newScore);
                                $score->scores = $preScores;
                                $score->save();
                            }
                            else
                            {
                                $model = new Scores();
                                $scores = array();
                                $newScore = new stdClass();
                                $newScore->score = $val;
                                $newScore->lesson = $data[1];
                                $newScore->registrant = Yii::$app->user->identity->username;
                                array_push($scores, $newScore);
                                $model->scores = $scores;
                                $model->course_id = (string) $course->_id;
                                $model->user_id = (string) $user->_id;
                                $model->save();
                            }
                        }
                    }
                    $i++;
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * خروجی اکسل دوره‌های میان‌مدت با همان فیلترهای فهرست (نوشتن جریانی، کوئری‌های دسته‌ای).
     */
    public function actionReport()
    {
        @set_time_limit(0);
        $searchModel = new PackagesSearch();
        $query = $searchModel->search(Yii::$app->request->queryParams)->query;
        $writer = new XlsxWriter(
            ['ردیف', 'عنوان اصلی فارسی', 'عنوان اصلی انگلیسی', 'عنوان فارسی داخل گواهی', 'عنوان انگلیسی داخل گواهی', 'وضعیت دوره', 'کد مجوز',
                'شهریه (تومان)', 'شهریه با تخفیف (تومان)', 'مدت زمان (ساعت)', 'ظرفیت', 'نوع دوره', 'تاریخ شروع', 'تاریخ پایان', 'مهلت ثبت‌نام',
                'نوع پرداخت', 'پیش‌پرداخت (تومان)', 'تعداد اقساط', 'جمع اقساط و پیش‌پرداخت (تومان)',
                'واحد', 'کارگزار', 'ثبت‌کننده', 'تعداد دروس', 'دروس', 'مدرسان', 'تاریخ ثبت', 'تعداد دانشپذیر'],
            [7, 40, 30, 40, 30, 22, 16, 16, 16, 12, 14, 12, 12, 12, 12, 14, 16, 10, 20, 24, 24, 24, 10, 50, 40, 12, 14]
        );
        $units = UsersDirectory::collegeTitles();
        foreach ($query->batch(300) as $courses) {
            $ids = $teacherIds = $lessonIds = $brokerIds = $registrantNames = [];
            foreach ($courses as $course) {
                $ids[] = (string) $course->_id;
                foreach ((array) $course->lessons as $lesson) {
                    if (isset($lesson['teachers']) && is_string($lesson['teachers']))
                        $teacherIds[] = $lesson['teachers'];
                    if (isset($lesson['_id']) && is_string($lesson['_id']))
                        $lessonIds[] = $lesson['_id'];
                }
                if (isset($course->broker['_id']) && is_string($course->broker['_id']))
                    $brokerIds[] = $course->broker['_id'];
                $registrantNames[] = $course->registrant;
            }
            $teachers = CourseOptions::namesById(Teachers::class, $teacherIds);
            $lessonTitles = CourseOptions::namesById(Lessons::class, $lessonIds, 'title');
            $brokers = CourseOptions::brokerNames($brokerIds);
            $members = CourseOptions::memberCounts($ids);
            $registrants = UsersDirectory::registrants($registrantNames);
            foreach ($courses as $course) {
                $id = (string) $course->_id;
                $t = is_array($course->title) ? $course->title : [];
                $d = is_array($course->date) ? $course->date : [];
                $cap = is_array($course->student_capacity) ? $course->student_capacity : [];
                $capacity = isset($cap['type']) ? ((string) $cap['type'] === '2' ? (isset($cap['number']) ? $cap['number'] . ' نفر' : 'محدود') : ((string) $cap['type'] === '3' ? 'سازمانی' : 'نامحدود')) : '';
                $names = $teacherNames = [];
                foreach ((array) $course->lessons as $lesson) {
                    $lid = isset($lesson['_id']) ? (string) $lesson['_id'] : '';
                    $tid = isset($lesson['teachers']) ? (string) $lesson['teachers'] : '';
                    if (isset($lessonTitles[$lid]))
                        $names[] = $lessonTitles[$lid];
                    if (isset($teachers[$tid]))
                        $teacherNames[$tid] = $teachers[$tid];
                }
                $plan = is_array($course->installments) ? array_values($course->installments) : [];
                $planSum = ctype_digit((string) $course->prepayment_installments) ? (int) $course->prepayment_installments : 0;
                foreach ($plan as $row)
                    if (isset($row['amount']) && ctype_digit((string) $row['amount']))
                        $planSum += (int) $row['amount'];
                $brokerId = isset($course->broker['_id']) ? (string) $course->broker['_id'] : '';
                $registrant = UsersDirectory::describeUsername(is_scalar($course->registrant) ? (string) $course->registrant : '', '', $registrants);
                $writer->addRow([
                    $writer->rowCount() + 1,
                    isset($t['main_fa']) ? (string) $t['main_fa'] : '', isset($t['main_en']) ? (string) $t['main_en'] : '',
                    isset($t['degree_fa']) ? (string) $t['degree_fa'] : '', isset($t['degree_en']) ? (string) $t['degree_en'] : '',
                    CourseStatus::label($course)[0],
                    (string) $course->license_code,
                    is_numeric($course->price) ? (float) $course->price : '', is_numeric($course->discount_price) && (float) $course->discount_price > 0 ? (float) $course->discount_price : '',
                    is_numeric($course->duration) ? (int) $course->duration : '',
                    $capacity,
                    UsersDirectory::contentType($course->content_type),
                    isset($d['from']) ? str_replace('-', '/', (string) $d['from']) : '', isset($d['to']) ? str_replace('-', '/', (string) $d['to']) : '',
                    is_scalar($course->deadline_date) ? str_replace('-', '/', (string) $course->deadline_date) : '',
                    empty($plan) ? 'نقدی' : 'نقدی یا اقساطی',
                    empty($plan) ? '' : (int) $course->prepayment_installments,
                    count($plan),
                    empty($plan) ? '' : $planSum,
                    isset($units[(string) $course->college]) ? $units[(string) $course->college] : '',
                    isset($brokers[$brokerId]) ? $brokers[$brokerId] : '',
                    is_scalar($course->registrant) && (string) $course->registrant !== '' ? $registrant['name'] . ' (' . $registrant['roleLabel'] . ')' : '',
                    count((array) $course->lessons),
                    implode('، ', $names),
                    implode('، ', $teacherNames),
                    UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))),
                    isset($members[$id]) ? $members[$id] : 0,
                ]);
            }
        }
        return CourseOptions::sendXlsx($writer, 'MidTermCourses');
    }

    /**
     * ثبت دوره‌ی میان‌مدت. فقط فیلدهای مجاز خوانده می‌شوند (PackageForm)؛ وضعیت، کد مجوز و ثبت‌کننده از فرم
     * پذیرفته نمی‌شوند. تصویر و قرارداد با SecureUpload (نوع واقعی فایل، نام تصادفی) ذخیره می‌شوند.
     */
    public function actionNew()
    {
        $model = new Courses();
        $model->scenario = Courses::SCENARIO_CREATE_PACKAGE;
        if (!\app\components\PackageForm::apply($model, Yii::$app->request->post('Courses'), true))
            return $this->packageBack('error', \app\components\PackageForm::$error, ['create-package']);

        $role = \app\components\CourseAccess::role();
        $unit = (string) $model->college;
        $model->type = '2';
        $model->credit = '0';
        $model->show_in_site = true;
        $model->modified = false;
        $model->registrant = (string) Yii::$app->user->identity->username;
        if (Yii::$app->request->post('draft') !== null && $role !== 'user')
            $model->status = \app\components\CourseStatus::DRAFT;
        else if ($role === 'user' || $unit === CoursesController::AUTO_APPROVE_UNIT)
            $model->status = \app\components\CourseStatus::ACTIVE;
        else if ($role === 'broker' || $unit === CoursesController::UNIT_REVIEW_UNIT)
            $model->status = \app\components\CourseStatus::AWAITING_UNIT;
        else
            $model->status = \app\components\CourseStatus::AWAITING;

        $image = UploadedFile::getInstance($model, 'preview_image');
        if ($image !== null) {
            $name = \app\components\SecureUpload::save($image, 'image', '@frontend/web/package_images');
            if ($name === null)
                return $this->packageBack('error', 'تصویر دوره: ' . \app\components\SecureUpload::$lastError, ['create-package']);
            $model->preview_image = $name;
        } else {
            $model->preview_image = 'default_course.png';
        }
        $contract = UploadedFile::getInstance($model, 'contract_file');
        if ($contract !== null) {
            $name = \app\components\SecureUpload::save($contract, 'document', '@frontend/web/contract_files');
            if ($name === null)
                return $this->packageBack('error', 'فایل قرارداد: ' . \app\components\SecureUpload::$lastError, ['create-package']);
            $model->contract_file = $name;
        }
        if (!$model->validate()) {
            \app\components\SecureUpload::delete('@frontend/web/contract_files', $model->contract_file);
            return $this->packageBack('error', current($model->getFirstErrors()) ?: 'اطلاعات وارد شده معتبر نیست', ['create-package']);
        }
        if ($model->status === \app\components\CourseStatus::ACTIVE && !\app\components\CourseOptions::assignLicenseCode($model))
            return $this->packageBack('error', 'تولید کد مجوز ممکن نشد (کد واحد یا شمارنده‌ی کد مجوز تعریف نشده است)', ['create-package']);
        if (!$model->save(false))
            return $this->packageBack('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید', ['create-package']);

        $message = 'دوره «' . $model->title['main_fa'] . '» ثبت شد';
        if ($model->status === \app\components\CourseStatus::ACTIVE && \app\components\classroom\ClassroomPlatforms::hasOnlineClass($model)) {
            $result = \app\components\classroom\ClassroomPlatforms::forCourse($model)->createCourseMeetings((string) $model->_id);
            $model->adobe_status = $result === true ? '1' : '0';
            $model->save(false, ['adobe_status']);
            if ($result !== true)
                return $this->packageBack('warning', $message . '؛ اما ساخت کلاس آنلاین ناموفق بود', ['edit-package', '_id' => (string) $model->_id]);
        }
        return $this->packageBack('success', $message, ['edit-package', '_id' => (string) $model->_id]);
    }

    /**
     * ویرایش مشخصات دوره‌ی میان‌مدت (دروس و اقساط در تب‌های خودشان).
     */
    public function actionEdit()
    {
        $input = Yii::$app->request->post('Courses');
        $id = is_array($input) && isset($input['_id']) && is_string($input['_id']) && preg_match('/^[a-f0-9]{24}$/i', $input['_id']) ? $input['_id'] : null;
        $model = $id === null ? null : Courses::find()->where(['_id' => $id, 'type' => '2'])->one();
        if ($model === null || !\app\components\CourseAccess::canEdit($model))
            return $this->packageBack('error', 'دوره یافت نشد یا امکان ویرایش آن را ندارید (دوره‌ی تأییدشده را فقط مدیر سیستم ویرایش می‌کند)', ['index']);
        $back = ['edit-package', '_id' => (string) $model->_id];
        $oldServer = (string) $model->classroom_server;
        $model->scenario = Courses::SCENARIO_EDIT_PACKAGE;
        if (!\app\components\PackageForm::apply($model, $input, false))
            return $this->packageBack('error', \app\components\PackageForm::$error, $back);

        $image = UploadedFile::getInstance($model, 'preview_image');
        if ($image !== null) {
            $name = \app\components\SecureUpload::save($image, 'image', '@frontend/web/package_images');
            if ($name === null)
                return $this->packageBack('error', 'تصویر دوره: ' . \app\components\SecureUpload::$lastError, $back);
            $old = (string) $model->getOldAttribute('preview_image');
            if ($old !== '' && $old !== 'default_course.png')
                \app\components\SecureUpload::delete('@frontend/web/package_images', $old);
            $model->preview_image = $name;
        }
        $contract = UploadedFile::getInstance($model, 'contract_file');
        if ($contract !== null) {
            $name = \app\components\SecureUpload::save($contract, 'document', '@frontend/web/contract_files');
            if ($name === null)
                return $this->packageBack('error', 'فایل قرارداد: ' . \app\components\SecureUpload::$lastError, $back);
            $model->contract_file = $name;
        }
        if (!$model->validate())
            return $this->packageBack('error', current($model->getFirstErrors()) ?: 'اطلاعات وارد شده معتبر نیست', $back);
        // ویرایش دوره‌ی «نیاز به اصلاح» = ارسال مجدد (دوره‌ی کارگزار پس از برگشت مدیر، دوباره از واحد می‌گذرد)
        if (in_array((string) $model->status, [\app\components\CourseStatus::NEEDS_CORRECTION, \app\components\CourseStatus::UNIT_CORRECTION], true))
            \app\components\CourseStatus::submit($model, \app\components\CourseAccess::role() === 'broker');
        if (!$model->save(false))
            return $this->packageBack('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید', $back);

        $message = 'دوره «' . $model->title['main_fa'] . '» ویرایش شد';
        if ($oldServer !== (string) $model->classroom_server && \app\components\classroom\ClassroomPlatforms::hasOnlineClass($model)
            && in_array((string) $model->status, [\app\components\CourseStatus::ACTIVE, \app\components\CourseStatus::APPROVED_INACTIVE], true)) {
            $result = \app\components\classroom\ClassroomPlatforms::forCourse($model)->createCourseMeetings((string) $model->_id);
            if ($result !== true) {
                $model->adobe_status = '0';
                $model->save(false, ['adobe_status']);
                return $this->packageBack('warning', $message . '؛ اما ساخت کلاس آنلاین روی سرور جدید ناموفق بود', $back);
            }
        }
        return $this->packageBack('success', $message, $back);
    }

    /**
     * ذخیره‌ی کامل شرایط اقساطی (پیش‌پرداخت + اقساط) با قواعد PackageForm::checkPlan.
     */
    public function actionInstallmentsPlan($_id)
    {
        $model = is_string($_id) && preg_match('/^[a-f0-9]{24}$/i', $_id) ? Courses::find()->where(['_id' => $_id, 'type' => '2'])->one() : null;
        if ($model === null || !\app\components\CourseAccess::canEdit($model))
            return $this->packageBack('error', 'دوره یافت نشد یا امکان ویرایش آن را ندارید', ['index']);
        $back = ['edit-package', '_id' => (string) $model->_id, 'tab' => 'tab-id3'];
        if (\app\components\PackageForm::planInUse($model) && !\app\components\CourseAccess::isAdmin())
            return $this->packageBack('error', 'دانشپذیرانی با این شرایط اقساطی ثبت‌نام کرده‌اند؛ تغییر آن فقط توسط مدیر سیستم ممکن است', $back);
        if (!\app\components\PackageForm::applyInstallments($model, Yii::$app->request->post('prepayment_installments'), Yii::$app->request->post('installments')))
            return $this->packageBack('error', \app\components\PackageForm::$error, $back);
        if (!$model->save(false, ['installments', 'prepayment_installments']))
            return $this->packageBack('error', 'خطا در ذخیره‌سازی، لطفاً دوباره تلاش کنید', $back);
        return $this->packageBack('success', empty($model->installments) ? 'شرایط اقساطی حذف شد؛ دوره فقط نقدی است' : 'شرایط اقساطی ذخیره شد', $back);
    }

    private function packageBack($type, $message, $url)
    {
        Yii::$app->session->setFlash(\frontend\controllers\CourseMembersController::FLASH, ['type' => $type, 'message' => $message]);
        return $this->redirect($url);
    }

    /** بازگشت به صفحه‌ی قبل (فهرست) با پیام */
    private function listBack($type, $message)
    {
        return $this->packageBack($type, $message, SafeRedirect::referrer(['index']));
    }

    /** دوره‌ی میان‌مدت با شناسه‌ی معتبر، وگرنه null */
    private function findPackage($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{24}$/i', $id))
            return null;
        $course = Courses::findOne($id);
        return $course !== null && in_array((string) $course->type, PackagesSearch::TYPES, true) ? $course : null;
    }

    public function actionBrokers($id)
    {
        $collegeBrokers = Brokers::find()->where(['college' => $id])->all();
        $collegeTeachers = Teachers::find()->where(['like', 'colleges', $id])->all();
        $collegeLessons = Lessons::find()->where(['college' => $id])->all();
        $brokers = null;
        $teachers = null;
        $lessons = null;
        if ($collegeBrokers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'broker[_id]')->dropDownList(
                ArrayHelper::map($collegeBrokers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->company_info['company_title'];
                }),
                [
                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/packages/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                ]
            )->label(false);
            $brokers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeTeachers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[teacher]')->dropDownList(
                ArrayHelper::map($collegeTeachers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->first_name . ' ' . $model->last_name;
                }),
                [
                    'prompt' => 'لطفا مدرس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true,
                    // اصلاح ۲۰۲۶-۰۸-۲۸: بدون این، پیغام پیش‌فرض مرورگر انگلیسی بود
                    // (همون باگی که برای فیلد دانشکده در create-package.php اصلاح شد).
                    'oninvalid' => 'this.setCustomValidity(\'لطفا مدرس را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $teachers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeLessons != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[_id]')->dropDownList(
                ArrayHelper::map($collegeLessons, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->title;
                }),
                [
                    'prompt' => 'لطفا درس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true,
                    'multiple' => true
                ]
            )->label(false);
            $lessons = ob_get_contents();
            ob_end_clean();
        }
        $response = array(
            'brokers' => $brokers,
            'teachers' => $teachers,
            'lessons' => $lessons,
        );
        return json_encode($response);
    }

    public function actionBrokers1($id, $capacity_type)
    {
        if($capacity_type == 3)
            $collegeBrokers = Brokers::find()->where(['college' => $id])->andWhere(['status' => '1'])->all();
        else
            $collegeBrokers = Brokers::find()->where(['college' => $id])->andWhere(['status' => '1'])->andWhere(['<>','financial_info.sub_service_id',''])->all();
        if(Yii::$app->user->identity->role == 'broker')
            $collegeBrokers = Brokers::find()->where(['college' => $id])->andWhere(['status' => '1'])->andWhere(['connector_info.mobile' => Yii::$app->user->identity->username])->all();
        $collegeTeachers = Teachers::find()->where(['like', 'colleges', $id])->all();
        $collegeLessons = Lessons::find()->where(['college' => $id])->all();
        $brokers = null;
        $teachers = null;
        $lessons = null;
        if ($collegeBrokers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'broker[_id]')->dropDownList(
                ArrayHelper::map($collegeBrokers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    $type = 'حقیقی';
                    if($model->type == '1')
                        $type = 'حقیقی - شرکت '.$model->company_info['company_title'];
                    return $model->connector_info['first_name'].' '.$model->connector_info['last_name'].'('.$type.')';
                }),
                [
                    'prompt' => 'لطفا کارگزار را انتخاب کنید',
                    'class' => ' form-select',
                    'id' => '',
                    'onchange' => '
                                                                                        $.get( "' . Url::toRoute('/packages/broker_contracts') . '", { id: $(this).val() } )
                                                                                        .done(function( data ) {
                                                                                           $(\'#broker_contracts1\').html(data);
                                                                                           $(\'.js-example-basic-single\').select2({
                                                                                             placeholder: \'انتخاب\'
                                                                                            });
                                                                                        }
                                                                                    );'
                ]
            )->label(false);
            $brokers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeTeachers != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'lessons[teacher]')->dropDownList(
                ArrayHelper::map($collegeTeachers, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->first_name . ' ' . $model->last_name;
                }),
                [
                    'prompt' => 'لطفا مدرس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true,
                    // اصلاح ۲۰۲۶-۰۸-۲۸: بدون این، پیغام پیش‌فرض مرورگر انگلیسی بود
                    // (همون باگی که برای فیلد دانشکده در create-package.php اصلاح شد).
                    'oninvalid' => 'this.setCustomValidity(\'لطفا مدرس را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $teachers = ob_get_contents();
            ob_end_clean();
        }
        if ($collegeLessons != null) {
            ob_start();
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'name' => 'form2',
                    ],

                ]
            );
            echo $form->field($model, 'my_lessons[_id]')->dropDownList(
                ArrayHelper::map($collegeLessons, function ($model) {
                    return (string) $model->_id;
                }, function ($model) {
                    return $model->title;
                }),
                [
                    'prompt' => 'لطفا درس را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => 'lessons',
                    'required' => true,
                    'multiple' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا حداقل یک درس را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
            $lessons = ob_get_contents();
            ob_end_clean();
        }
        $response = array(
            'brokers' => $brokers,
            'teachers' => $teachers,
            'lessons' => $lessons,
        );
        return json_encode($response);
    }

    public function actionBroker_contracts($id)
    {
        $broker = Brokers::findOne($id);
        if ($broker != null)
        {
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],

                ]
            );
            echo $form->field($model, 'broker[contract]')->dropDownList(
                ArrayHelper::map($broker->contracts, 'id', function ($model) {
                    return $model['title'] . ' (' . $model['share'] . ' درصد)';
                }),
                [
                    'prompt' => 'لطفا قرارداد کارگزار را انتخاب کنید',
                    'class' => 'select2 form-select',
                    'id' => '',
                    'required' => true,
                    'oninvalid' => 'this.setCustomValidity(\'لطفا قرارداد کارگزار را انتخاب کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
        }
    }

    public function actionCapacity($id)
    {
        if ($id == 2) {
            $model = new Courses();
            $form = ActiveForm::begin(
                [
                    'action' => ['new'],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                    ],

                ]
            );
            echo '<label for="nameWithTitle" class="form-label">ظرفیت *</label>';
            // تغییر ۱ (2026-08-28): بدون oninvalid/oninput، مرورگر برای این
            // فیلد پیغام پیش‌فرض انگلیسی نشون می‌داد (بر خلاف همه‌ی فیلدهای
            // دیگه‌ی همین فرم که این پیغام رو دارن) - همون الگوی موجود پروژه
            // (نه یک روش تازه) روی این فیلد هم اعمال شده.
            echo $form->field($model, 'student_capacity[number]')->textInput(
                [

                    'class' => 'form-control numeral-mask text-start',
                    'required' => true,
                    'type' => 'number',
                    'oninvalid' => 'this.setCustomValidity(\'لطفا ظرفیت را به صورت عددی وارد کنید\')',
                    'oninput' => 'setCustomValidity(\'\')',
                ]
            )->label(false);
        }
    }

    public function actionCourse_date($id)
    {
        if ($id == 3) {
            $response = array(
                'from' => "",
                'to' => ""
            );
            return json_encode($response);
        }

        ob_start();
        $model = new Courses();
        $form = ActiveForm::begin(
            [
                'action' => ['new'],
                'options' => [
                    'enctype' => 'multipart/form-data',
                ],

            ]
        );
        echo '<label for="nameWithTitle" class="form-label">تاریخ شروع دوره *</label>';
        echo  $form->field($model, 'date[from]')->textInput(
            [
                'class' => 'form-control dob-picker text-start',
                'required' => true,
                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع دوره را وارد کنید\')',
                'oninput' => 'setCustomValidity(\'\')',
            ]
        )->label(false);
        $from = ob_get_contents();
        ob_end_clean();
        ob_start();
        $model = new Courses();
        $form = ActiveForm::begin(
            [
                'action' => ['new'],
                'options' => [
                    'enctype' => 'multipart/form-data',
                ],

            ]
        );
        echo '<label for="nameWithTitle" class="form-label">تاریخ اتمام دوره *</label>';
        echo  $form->field($model, 'date[to]')->textInput(
            [
                'class' => 'form-control dob-picker text-start',
                'required' => true,
                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام دوره را وارد کنید\')',
                'oninput' => 'setCustomValidity(\'\')',
            ]
        )->label(false);
        $to = ob_get_contents();
        ob_end_clean();
        $response = array(
            'from' => $from,
            'to' => $to
        );
        return json_encode($response);
    }

    public function my_brokers($college)
    {
        $brokers = Brokers::find()->where(['college' => (string) $college])->andWhere(['status' => '1'])->all();
        if ($brokers != null)
            return $brokers;
        else
            return  null;
    }

    public function all_college_brokers($college)
    {
        $brokers = Brokers::find()->where(['college' => (string) $college])->all();
        if ($brokers != null)
        {
            $brokers = ArrayHelper::map($brokers, function ($model) {
                return (string) $model->_id;
            }, function ($model) {
                $type = 'حقیقی';
                if ($model->type == '1')
                    $type = 'حقوقی - شرکت ' . $model->company_info['company_title'];
                return $model->connector_info['first_name'] . ' ' . $model->connector_info['last_name'] . '(' . $type . ')';
            });
            return $brokers;
        }
        else
            return  null;
    }

    public function my_broker_contract($_id)
    {
        $broker = Brokers::findOne($_id);
        if ($broker != null)
            return $broker->contracts;
        else
            return null;
    }

    public function my_courses($college)
    {
        return Lessons::find()->where(['college' => $college])->all();
    }

    public function my_teachers($college)
    {
        return Teachers::find()->where(['like', 'colleges', $college])->all();
    }

    public function actionShow_courses()
    {
        $list = '';
        $array = array();
        $hiddenArchive = array(
            false => 'خیر' ,
            true => 'بله' ,
        );
        if(isset($_POST['id']))
        {
            $myLesson = Lessons::findOne($_POST['id'][0]);
            if($myLesson != null)
                $teachers = Teachers::find()->where(['like','colleges', $myLesson->college])->all();
            else
                $teachers = null;
            $counter = 0;
            foreach ($_POST['id'] as $item) {
                $lesson = Lessons::findOne($item);
                $model = new Courses();
                $form = ActiveForm::begin(
                    [
                        'action' => ['new'],
                        'options' => [
                            'enctype' => 'multipart/form-data',
                        ],
                    ]
                );
                if ($lesson != null) {
                    $list = '
            <div class="col-4 col-md-4 col-sm-4 dol-lg-4 col-xl-4 mb-3 ' . (string) $lesson->_id . '" id="' . (string) $lesson->_id . '">
                  <div class="card mb-4" id="course-card-' . (string) $lesson->_id . '">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      ' . $lesson->title . '
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                          <label class="form-label" for="basic-default-fullname">استاد *</label>
                         ' . $form->field($model, 'lessons[' . $counter . '][teachers]')->dropDownList(
                            ArrayHelper::map($teachers, function ($model) {
                                return (string) $model->_id;
                            }, function ($model) {
                                return $model->first_name . ' ' . $model->last_name;
                            }),
                            [
                                'prompt' => 'لطفا استاد درس را انتخاب کنید',
                                'class' => 'select2 form-select',
                                'required' => true,
                                // اصلاح ۲۰۲۶-۰۸-۲۸: این دقیقاً فیلد «استاد» بود که بدون oninvalid
                                // پیغام پیش‌فرض انگلیسی مرورگر رو نشون می‌داد - برخلاف فیلدهای
                                // تاریخ/ساعت همین کارت که oninvalid دارن.
                                'oninvalid' => 'this.setCustomValidity(\'لطفا استاد درس را انتخاب کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                //                            'multiple' => true
                            ]
                        )->label(false) . '
                        </div>
                        <div class="mb-3">
                          <label class="form-label">تاریخ شروع *</label>
                          ' .
                        $form->field($model, 'lessons[' . $counter . '][date][from]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ شروع درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false)
                        . '
                           ' .
                        $form->field($model, 'lessons[' . $counter . '][_id]')->hiddenInput(
                            [
                                'value' => (string) $lesson->_id
                            ]
                        )->label(false)
                        . '
                        </div>
                        <div class="mb-3">
                          <label class="form-label">تاریخ اتمام *</label>
                          ' .
                        $form->field($model, 'lessons[' . $counter . '][date][to]')->textInput(
                            [
                                'class' => 'form-control dob-picker text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا تاریخ اتمام درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        . '
                        </div>
                        <div class="mb-3">
                          <label class="form-label">ساعت شروع *</label>
                          ' .
                        $form->field($model, 'lessons[' . $counter . '][date][time]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا ساعت شروع درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        . '
                        </div>
                        <div class="mb-3">
                          <label class="form-label">مدت زمان (ساعت) *</label>
                          ' .
                        $form->field($model, 'lessons[' . $counter . '][date][duration]')->textInput(
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'oninvalid' => 'this.setCustomValidity(\'لطفا مدت زمان درس را وارد کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                                'id' => '',
                            ]
                        )->label(false)
                        . '
                        </div>
                        <div class="mb-3">
                          <label class="form-label">مخفی کردن آرشیو *</label>
                          ' .
                        $form->field($model, 'lessons[' . $counter . '][hide_archive]')->dropDownList(
                            $hiddenArchive,
                            [
                                'class' => 'form-control text-start',
                                'required' => true,
                                'id' => '',
                                'oninvalid' => 'this.setCustomValidity(\'لطفا وضعیت مخفی کردن آرشیو را مشخص کنید\')',
                                'oninput' => 'setCustomValidity(\'\')',
                            ]
                        )->label(false)
                        . '
                        </div>
                    </div>
                  </div>
                </div>
            ';
                }
                $counter++;
                array_push($array, $list);
            }
        }
        $response = array(
            'list' => $array
        );
        return json_encode($response);
    }

    public function lesson_detail($_id)
    {
        return Lessons::findOne($_id);
    }

    public function actionEdit_course_in_package()
    {
        if (Yii::$app->request->isPost) {
            $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if ($course != null)
            {
                $lessons = $course->lessons;
                $lessons = array_values($course->lessons);
                $course->lessons = $lessons;
                $course->save();
                if (isset($_POST['edit']))
                {
                    $from = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['date']['from'];
                    $to = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['date']['to'];
                    $fromm = str_replace('-','',$from);
                    $too = str_replace('-','',$to);
                    if($fromm <= $too)
                    {
                        $lessonId = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['_id'];
                        $lessons = $course->lessons;
                       if($course->content_type == '1' || $course->content_type == '2')
                       {
                           $meeting = null;
                           if(array_key_exists('meeting', $lessons[Yii::$app->request->post('row')]))
                                $meeting = $lessons[Yii::$app->request->post('row')]['meeting'];
                           $lessons[Yii::$app->request->post('row')] = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')];
                           if($meeting != null)
                               $lessons[Yii::$app->request->post('row')]['meeting'] = $meeting;
                       }
                       else
                           $lessons[Yii::$app->request->post('row')] = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')];
                        $course->lessons = $lessons;
                        // Begin Call AdobeConnect For Remove Course
                        $adminRole = Admin::find()->where(['role' => 'user'])->one();
                        $response = null;
                        if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                        {
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/update-lesson',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS =>'{
                                "course_id": "'.(string) $course->_id.'",
                                "lesson_id": "'.(string) $lessonId.'",
                                "from": "'.$from.'",
                                "to": "'.$to.'"
                            }',
                                CURLOPT_HTTPHEADER => array(
                                    '_id: '.(string) $adminRole->_id,
                                    'Content-Type: application/json'
                                ),
                            ));
                            $response = curl_exec($curl);
                            $response = json_decode($response);
                            curl_close($curl);

                            $response1 = null;
                            $teacherDetail = Teachers::findOne($lessons[Yii::$app->request->post('row')]['teachers']);
                            if($teacherDetail != null)
                            {
                                $teacherAdmin = Admin::find()->where(['username' => $teacherDetail->mobile])->one();
                                if($teacherAdmin != null)
                                {
                                    $curl = curl_init();
//
                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/replace-teacher',
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_POSTFIELDS =>'{
                                            "course_id": "'.(string) $course->_id.'",
                                            "lesson_id": "'.(string) $lessonId.'",
                                            "teacher_id": "'.(string) $teacherAdmin->_id.'"
                                        }',
                                        CURLOPT_HTTPHEADER => array(
                                            '_id: '.(string) $adminRole->_id,
                                            'Content-Type: application/json'
                                        ),
                                    ));
                                    $response1 = curl_exec($curl);
                                    $response1 = json_decode($response1);
                                    curl_close($curl);
                                }
                            }
                        }
                        // End Call AdobeConnect For Remove Course
                       if($course->content_type == '1' || $course->content_type == '2')
                       {
                           if(property_exists($response,'status') && property_exists($response1,'status'))
                           {
                               if($response->status == 'ok' && $response1->status == 'ok')
                               {
                                   if ($course->save())
                                       Yii::$app->session->setFlash('status', '3');
                                   else
                                       Yii::$app->session->setFlash('status', '2');
                               }
                               else
                                   Yii::$app->session->setFlash('status','21');
                           }
                           else
                           {
                               $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
                               if($course != null)
                               {
                                   $lessons = $course->lessons;
                                   if(array_key_exists('duration',Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['date']))
                                   {
                                       $lessons[Yii::$app->request->post('row')]['date']['duration'] = Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['date']['duration'];
                                       $course->lessons = $lessons;
                                       $course->save();
                                   }
                               }
                               Yii::$app->session->setFlash('status','21');
                           }
                       }
                       else
                       {
                           if ($course->save())
                               Yii::$app->session->setFlash('status', '3');
                           else
                               Yii::$app->session->setFlash('status', '2');
                       }
                    }
                    else
                        Yii::$app->session->setFlash('status','22');
                }
                else if ((isset($_POST['delete'])) && (($course->status == '2') || ($course->status == '3') || ($course->status == '4') || ($course->status == '7') || ($course->status == '8') || ($course->status == '9') || (Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')))
                {
                    $index = null;
                    if ($course->my_lessons != null)
                    {
                        $i = 0;
                        foreach ($course->my_lessons['_id'] as $lesson)
                        {
                            if ($lesson == Yii::$app->request->post()['Courses']['lessons'][Yii::$app->request->post('row')]['_id'])
                                $index = $i;
                            $i++;
                        }
                    }
                    $newLessons = $course->my_lessons['_id'];
                    if ($index !== null) {
                        unset($newLessons[$index]);
                        $newLessons = array_values($newLessons);
                    }
                    $newLessons = array(
                        '_id' => $newLessons
                    );
                    $lessons = $course->lessons;
                    $lessonId = $lessons[Yii::$app->request->post('row')]['_id'];
                    unset($lessons[Yii::$app->request->post('row')]);
                    $lessons = array_values($lessons);
                    $course->lessons = $lessons;
                    $course->my_lessons = $newLessons;
                    // Begin Call AdobeConnect For Remove Course
                    $adminRole = Admin::find()->where(['role' => 'user'])->one();
                    $response = null;
                    if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                    {
                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/delete-lesson/'.(string) $course->_id.'/'. (string) $lessonId,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_HTTPHEADER => array(
                                '_id: '.(string) $adminRole->_id
                            ),
                        ));
                        $response = curl_exec($curl);
                        $response = json_decode($response);
                        curl_close($curl);
                    }
                    // End Call AdobeConnect For Remove Course
                    if($course->content_type == '1' || $course->content_type == '2')
                    {
                        if(property_exists($response,'status'))
                        {
                            if($response->status == 'ok')
                            {
                                if ($course->save())
                                    Yii::$app->session->setFlash('status', '4');
                                else
                                    Yii::$app->session->setFlash('status', '2');
                            }
                            else
                                Yii::$app->session->setFlash('status','21');
                        }
                        else
                            Yii::$app->session->setFlash('status','21');
                    }
                    else
                    {
                        if ($course->save())
                            Yii::$app->session->setFlash('status', '4');
                        else
                            Yii::$app->session->setFlash('status', '2');
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAdd_lesson_to_package()
    {
        if (Yii::$app->request->isPost)
        {
            $course = Courses::findOne(['_id' => Yii::$app->request->post('_id')]);
            if ($course != null)
            {
                if ($course->status == '2' || $course->status == '3' || $course->status == '4' || $course->status == '7' || $course->status == '8' || $course->status == '9' || Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
                {
                    $preLessons = array();
                    if($course->lessons != null)
                        $preLessons = $course->lessons;
                    array_push($preLessons, Yii::$app->request->post()['Courses']['lessons']);
                    $course->lessons = $preLessons;
                    $myLessons = array();
                    if($course->my_lessons != null)
                        $course->my_lessons['_id'];
                    array_push($myLessons, Yii::$app->request->post()['Courses']['lessons']['_id']);
                    $newLessons = array(
                        '_id' => $myLessons
                    );
                    $course->my_lessons = $newLessons;
                    if ($course->save())
                    {
                        // Call AdobeConnect For Create Meetings Course
                        $adminRole = Admin::find()->where(['role' => 'user'])->one();
                        if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                        {
                            $curl = curl_init();

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/create-meeting/'.(string) $course->_id.'?new-lesson='.Yii::$app->request->post()['Courses']['lessons']['_id'],
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_HTTPHEADER => array(
                                    '_id: '.(string) $adminRole->_id
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                        }
                        // Call AdobeConnect For Create Meetings Course
                        Yii::$app->session->setFlash('status', '5');
                    }
                    else
                        Yii::$app->session->setFlash('status', '2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionNew_user()
    {
        if(Yii::$app->request->isPost)
        {
            $find = Users::find()->where(['username' => strtolower(Yii::$app->request->post()['Users']['username'])])->one();
            $course = Courses::findOne(Yii::$app->request->post('packageId'));
            if($find == null)
            {
                $model = new Users();
                $model->load(Yii::$app->request->post());
                $model->username = strtolower(Yii::$app->request->post()['Users']['username']);
                $model->setPassword(Yii::$app->request->post()['Users']['password_hash']);
                $model->auth_key = Yii::$app->security->generateRandomString();
                $model->verification_token = Yii::$app->security->generateRandomString();
                $model->getAuthKey();
                $model->role = 'user';
                // users.college آرایه است (docs/specs/users-manage.md بند ۲)
                $model->college = StudentAccess::normalizeColleges($course->college);
                $model->status = 10;
                $model->courses = array(
                    '0' => array(
                        '_id' => Yii::$app->request->post('packageId'),
                        'status' => '0',
                        'registrant' => Yii::$app->user->identity->username
                    )
                );
                $model->registrant = Yii::$app->user->identity->username;
               if( $model->save())
               {
                   // Call AdobeConnect For Create Meetings Course
                   $adminRole = Admin::find()->where(['role' => 'user'])->one();
                   if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                   {
                       $curl = curl_init();

                       curl_setopt_array($curl, array(
                           CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) Yii::$app->request->post('packageId'),
                           CURLOPT_RETURNTRANSFER => true,
                           CURLOPT_ENCODING => '',
                           CURLOPT_MAXREDIRS => 10,
                           CURLOPT_TIMEOUT => 0,
                           CURLOPT_FOLLOWLOCATION => true,
                           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                           CURLOPT_CUSTOMREQUEST => 'POST',
                           CURLOPT_HTTPHEADER => array(
                               '_id: '.(string) $adminRole->_id
                           ),
                       ));
                       $response = curl_exec($curl);
                       curl_close($curl);
                   }
                   // Call AdobeConnect For Create Meetings Course
                   Yii::$app->session->setFlash('status','7');
               }
               else
                   Yii::$app->session->setFlash('status','2');
            }
            else
            {
                if($find->status != 10)
                {
                    $find->status = 10;
                    $find->save();
                }
                if($find->courses == null)
                {
                    $flag = true;
                    $find->courses = array(
                        '0' => array(
                            '_id' => Yii::$app->request->post('packageId'),
                            'status' => '0',
                            'registrant' => Yii::$app->user->identity->username
                        )
                    );
                }
                else
                {
                    $flag = true;
                    foreach ($find->courses as $item)
                        if($item['_id'] == Yii::$app->request->post('packageId'))
                            $flag = false;
                    if($flag)
                    {
                        $courses = $find->courses;
                        $newCourse = array(
                            '_id' => Yii::$app->request->post('packageId'),
                            'status' => '0'
                        );
                        array_push($courses, $newCourse);
                        $find->courses = $courses;
                    }
                }
                // دانشکده‌ی دوره به دانشکده‌های کاربر اضافه می‌شود (جایگزین نمی‌شود)
                StudentAccess::addColleges($find, StudentAccess::normalizeColleges($course->college));
                if($find->save())
                {
                    if($flag)
                    {
                        // Call AdobeConnect For Create Meetings Course
                        $adminRole = Admin::find()->where(['role' => 'user'])->one();
                        if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                        {
                            $curl = curl_init();

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) Yii::$app->request->post('packageId'),
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_HTTPHEADER => array(
                                    '_id: '.(string) $adminRole->_id
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                        }
                        // Call AdobeConnect For Create Meetings Course
                        Yii::$app->session->setFlash('status','7');
                    }
                    else
                        Yii::$app->session->setFlash('status','8');
                }
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionShow_user_detail()
    {
        if(Yii::$app->request->isPost)
        {
            $user = Users::find()->where(['username' => $_POST['username']])->one();
            if($user != null)
            {
                $course = Courses::findOne($_POST['id']);
                if($course != null)
                {
                    $flag = true;
                    if($user->courses != null)
                    {
                        $find = false;
                        foreach ($user->courses as $item)
                            if($item['_id'] == (string) $course->_id)
                                $find = true;
                        if($find)
                            $flag = false;
                    }
                    if($flag)
                    {
                        ob_start();
                        echo '<div class="alert alert-success">دانشپذیر مورد نظر '.$user->first_name.' '.$user->last_name.' می باشد. آیا از ثبت وی در دوره مطمئن هستید؟</div>';
                        $userDetail = ob_get_contents();
                        ob_end_clean();
                        ob_start();
                        $model = new Users();
                        $form = ActiveForm::begin(['action' => ['add_user_from_list']]);
                        echo  $form->field($model, 'username')->hiddenInput(
                            [
                                'value' => $user->username,
                            ]
                        )->label(false);
                        echo  $form->field($model, 'courses')->hiddenInput(
                            [
                                'value' => (string) $course->_id,
                            ]
                        )->label(false);
                        echo '<button class="btn bg-label-primary">بله مطمئنم</button>';
                        ActiveForm::end();
                        $footer = ob_get_contents();
                        ob_end_clean();
                        $response = array(
                            'userDetail' => $userDetail,
                            'footer' => $footer
                        );
                        return json_encode($response);
                    }
                    else
                    {
                        $response = array(
                            'userDetail' => '<div class="alert alert-danger" role="alert">دانشپذیر مور نظر '.$user->first_name.' '.$user->last_name.' هم اکنون در لیست دوره مورد نظر می باشد.</div>'
                        );
                        return json_encode($response);
                    }
                }
                else
                {
                    $response = array(
                        'userDetail' => '<div class="alert alert-danger" role="alert">دوره مورد نظر معتبر نمی باشد</div>'
                    );
                    return json_encode($response);
                }
            }
            else
            {
                $response = array(
                    'userDetail' => '<div class="alert alert-danger" role="alert">کاربری با نام کاربری وارد شده وجود ندارد</div>'
                );
                return json_encode($response);
            }
        }
        else
            return false;
    }

    public function actionAdd_user_from_list()
    {
        if(Yii::$app->request->isPost)
        {
           $user = Users::find()->where(['username' => Yii::$app->request->post()['Users']['username']])->one();
           if($user != null)
           {
               $course = Courses::findOne(Yii::$app->request->post()['Users']['courses']);
               if($course != null)
               {
                   $flag = true;
                   if($user->courses != null)
                   {
                       foreach ($user->courses as $item)
                           if($item['_id'] == (string) $course->_id)
                               $flag = false;
                   }
                   else
                   {
                       $courses = array(
                           '0' => array(
                               '_id' => (string) $course->_id,
                               'status' => '0',
                               'registrant' => Yii::$app->user->identity->username,
                           )
                       );
                   }
                   if($flag)
                   {
                       $courses = $user->courses;
                       $newCourse = array(
                           '_id' => (string) $course->_id,
                           'status' => '0',
                           'registrant' => Yii::$app->user->identity->username,
                       );
                       array_push($courses, $newCourse);
                   }
                   $user->courses = $courses;
                   StudentAccess::addColleges($user, StudentAccess::normalizeColleges($course->college));
                   if($user->save())
                   {
                       // Call AdobeConnect For Create Meetings Course
                       $adminRole = Admin::find()->where(['role' => 'user'])->one();
                       if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                       {
                           $curl = curl_init();

                           curl_setopt_array($curl, array(
                               CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) $course->_id,
                               CURLOPT_RETURNTRANSFER => true,
                               CURLOPT_ENCODING => '',
                               CURLOPT_MAXREDIRS => 10,
                               CURLOPT_TIMEOUT => 0,
                               CURLOPT_FOLLOWLOCATION => true,
                               CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                               CURLOPT_CUSTOMREQUEST => 'POST',
                               CURLOPT_HTTPHEADER => array(
                                   '_id: '.(string) $adminRole->_id
                               ),
                           ));
                           $response = curl_exec($curl);
                           curl_close($curl);
                       }
                       // Call AdobeConnect For Create Meetings Course
                       Yii::$app->session->setFlash('status','7');
                   }
                   else
                       Yii::$app->session->setFlash('status','2');
               }
           }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCheck_excel_file()
    {
        $errors = array();
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_type = $_FILES['file']['type'];
        $file_ext = explode('.', $file_name)[1];

        $extensions = array("xlsx", "xls");

        if (in_array($file_ext, $extensions) === false) {
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">پسوند فایل انتخاب شده اشتباه می باشد</div>'
            );
            return json_encode($response);
        }

        if ($file_size > Yii::getAlias('@maxFileSize')) {
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">حجم فایل وارد شده باید کمتر از ۱۰ مگابایت باشد</div>'
            );
            return json_encode($response);
        }

        if (empty($errors) == true)
        {
            $wallet = 0;
            $course = Courses::findOne($_POST["packageId_"]);
            $allowFree = false;
            if($course->allow_free_add_user != null)
                if($course->allow_free_add_user == '1')
                    $allowFree = true;
            if($course != null)
            {
                $broker = null;
                $brokerWallet = 0;
                $contractBrokerShare = null;
                $contractCollegeShare = null;
                $finalCourseCollegeShare = null;
                if($course->broker != null)
                {
                    if(array_key_exists('_id', $course->broker) && array_key_exists('contract', $course->broker))
                    {
                        $broker = Brokers::findOne($course->broker['_id']);
                        if($broker != null)
                        {
                            if($broker->wallet_amount != null)
                                $brokerWallet = $broker->wallet_amount;
                            if($broker->contracts != null)
                            {
                                foreach ($broker->contracts as $item)
                                {
                                    if($item['id'] == $course->broker['contract'])
                                    {
                                        $contractBrokerShare = $item['share'];
                                        $contractCollegeShare = 100 - $item['share'];
                                    }
                                }
                            }
                        }
                    }
                }
                if($broker != null && $contractBrokerShare != null && $contractCollegeShare != null)
                    $finalCourseCollegeShare = $course->price * ($contractCollegeShare / 100);
            }
            $newName = Yii::$app->user->identity->username . '-' . time() . '.' . $file_ext;
            move_uploaded_file($file_tmp, "../../frontend/web/uploaded_excels/" . $newName);
            $objPHPExcel = PHPExcel_IOFactory::load('../../frontend/web/uploaded_excels/' . $newName);
            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

            $i = 0;
            $j = 1;
            $validRows = 0; // شمارنده سطرهای معتبر
            $rowErrors = array(); // ایرادهای هر سطر (کلید = شماره سطر در فایل اکسل)
            $errorRowsCount = 0; // تعداد سطرهایی که حداقل یک ایراد دارند

            if ($sheetData != null) {
                ob_start();
                echo '<div class="card">';
                echo '<div class="mt-3">';
                echo '<div class="btn-group" role="group" aria-label="Basic example">';

                // شمارش سطرهای معتبر و بررسی صحت اطلاعات هر سطر
                foreach ($sheetData as $rowKey => $data) {
                    if ($j != 1) { // رد کردن هدر
                        if ($this->isValidRow($data))
                        {
                            if($data['A'] != '' && $data['B'] != '' && $data['C'] != '' && $data['D'] != '')
                                $validRows++;

                            $cellErrors = $this->getExcelRowErrors($data);
                            if (count($cellErrors) > 0)
                            {
                                $rowErrors[$rowKey] = $cellErrors;
                                $errorRowsCount++;
                            }
                        }
                    }
                    $j++;
                }

                echo '<button class="btn btn-secondary">تعداد کاربران موجود در فایل: ' . $validRows . ' نفر می باشد</button>';
                $form = ActiveForm::begin(['action' => ['add_user_from_exel']]);
                if($errorRowsCount > 0)
                    echo '<button type="button" class="btn btn-success" disabled>افزودن نهایی به دوره</button>';
                else if($validRows > 0)
                {
                    if(($brokerWallet >= ($finalCourseCollegeShare * $validRows)) || ($allowFree == true))
                        echo '<button type="submit" class="btn btn-success">افزودن نهایی به دوره</button>';
                    else if($brokerWallet < ($finalCourseCollegeShare * $validRows))
                        echo '<button type="button" class="btn btn-danger">اعتبار کیف پول شما برای '.$validRows.' نفر کافی نمی باشد</button>';
                }
                else
                    echo '<button type="button" class="btn btn-danger">فایل اکسل شما خالی می باشد</button>';
                echo '<input name="fileName" value="' . $newName . '" type="hidden">';
                echo '<input name="courseId" value="' . $_POST["packageId_"] . '" type="hidden">';
                ActiveForm::end();
                echo '</div>';
                echo '</div>';
                if($errorRowsCount > 0)
                    echo '<div class="alert alert-danger m-3" role="alert">سطرهایی که با رنگ قرمز مشخص شده است را تصحیح کنید تا اجازه ثبت نهایی فایل را داشته باشید. (' . $errorRowsCount . ' سطر ایراد دارد - برای دیدن علت ایراد، نشانگر ماوس را روی خانه های قرمز پررنگ نگه دارید)</div>';
                echo '<h5 class="card-header heading-color">تعداد کاربر: ' . $validRows . '</h5>';
                echo '<div class="table-responsive text-nowrap" id="tbl">';
                echo '<table class="table">';
                echo '<thead> <tr><th>#</th><th>نام</th><th>نام خانوادگی</th><th>نام کاربری</th><th>کد ملی (رمز عبور)</th><th>نام انگلیسی</th><th>نام خانوادگی انگلیسی</th><th>جنسیت</th></tr></thead>';
                echo '<tbody class="table-border-bottom-0">';

                $j = 1;
                $displayIndex = 1;

                foreach ($sheetData as $rowKey => $data) {
                    if ($j++ != 1) { // رد کردن هدر
                        // فقط سطرهای معتبر رو نمایش بده
                        if ($this->isValidRow($data)) {
                            $gender = '-';
                            $firstNameEn = '-';
                            $lastNameEn = '-';

                            if (isset($data['E']) && !empty(trim($data['E'])))
                                $firstNameEn = $data['E'];
                            if (isset($data['F']) && !empty(trim($data['F'])))
                                $lastNameEn = $data['F'];
                            if (isset($data['G']) && !empty(trim($data['G']))) {
                                if ($data['G'] == 2 || $data['G'] == '۲')
                                    $gender = 'زن';
                                else if ($data['G'] == 1 || $data['G'] == '۱')
                                    $gender = 'مرد';
                            }

                            // ایرادهای همین سطر (اگر خالی نباشد، پس زمینه سطر قرمز می شود)
                            $cellErrors = isset($rowErrors[$rowKey]) ? $rowErrors[$rowKey] : array();
                            $rowClass = count($cellErrors) > 0 ? ' class="table-danger"' : '';

                            // اگر جنسیت ایراد دارد، مقدار خام فایل نمایش داده شود تا کاربر متوجه اشتباه شود
                            if (isset($cellErrors['G']) && isset($data['G']) && trim((string) $data['G']) !== '')
                                $gender = $data['G'];

                            echo '<tr' . $rowClass . '>';
                            echo '<td' . $this->excelCellStyle($cellErrors, null) . '><span class="badge badge-center bg-label-secondary">' . $displayIndex . '</span></td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'A') . '>' . $data['A'] . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'B') . '>' . $data['B'] . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'C') . '>' . $data['C'] . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'D') . '>' . $data['D'] . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'E') . '>' . $firstNameEn . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'F') . '>' . $lastNameEn . '</td>';
                            echo '<td' . $this->excelCellStyle($cellErrors, 'G') . '>' . $gender . '</td>';
                            echo '</tr>';

                            $displayIndex++;
                        }
                    }
                    $i++;
                }

                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                $message = ob_get_contents();
                ob_end_clean();
            }

            $response = array(
                'message' => $message
            );
            return json_encode($response);
        } else {
            $response = array(
                'message' => '<div class="alert alert-danger" role="alert">خطایی در بارگزاری فایل رخ داده، لطفا مجددا تلاش کنید</div>'
            );
            return json_encode($response);
        }
    }
    public function actionAdd_user_from_exel()
    {
        if(Yii::$app->request->isPost)
        {
            if(file_exists('../../frontend/web/uploaded_excels/'.Yii::$app->request->post('fileName')))
            {
                $course = Courses::findOne(Yii::$app->request->post('courseId'));
                $allowFree = false;
                if($course->allow_free_add_user != null)
                    if($course->allow_free_add_user == '1')
                        $allowFree = true;
                $college = Colleges::findOne($course->college);
                if($college != null)
                    if($college->allow_free_add_user == true)
                        $allowFree = true;
                $broker = null;
                if($course != null)
                {
                    $objPHPExcel = PHPExcel_IOFactory::load('../../frontend/web/uploaded_excels/'.Yii::$app->request->post('fileName'));
                    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                    $i = 1;
                    $j = 1;
                    $allExcelUserCount = 0;
                    foreach ($sheetData as $data)
                    {
                        if($j++ != 1)
                        {
                            if($data['A'] != '' && $data['B'] != '' && $data['C'] != '' && $data['D'] != '')
                                $allExcelUserCount++;
                        }
                    }
                    $allAmount = null;
                    $brokerWalletAmount = 0;
                    if($course->broker != null)
                    {
                        if(array_key_exists('_id', $course->broker) && array_key_exists('contract', $course->broker))
                        {
                            $broker = Brokers::findOne($course->broker['_id']);
                            if($broker != null)
                            {
                                if($broker->wallet_amount != null)
                                    $brokerWalletAmount = $broker->wallet_amount;
                                $contract = null;
                                if($broker->contracts != null)
                                {
                                    foreach ($broker->contracts as $item)
                                        if($item['id'] == $course->broker['contract'])
                                            $contract = $item;
                                }
                                if($contract != null)
                                    $allAmount = ((100 - $contract['share']) / 100) * $course->price * $allExcelUserCount;
                            }
                        }
                    }
                    if($allAmount <= $brokerWalletAmount || ($allowFree == true))
                    {
                        foreach ($sheetData as $data)
                        {
                            if($i++ != 1)
                            {
                                if($data['A'] != '' && $data['B'] != '' && $data['C'] != '' && $data['D'] != '')
                                {
                                    $user = Users::find()->where(['username' => strtolower(trim($data['C']))])->one();
                                    if($user != null)
                                    {
                                        if($user->courses == null)
                                        {
                                            $newCourses = array(
                                                '0' => array(
                                                    '_id' => (string) $course->_id,
                                                    'status' => '0',
                                                    'registrant' => Yii::$app->user->identity->username
                                                )
                                            );
                                            $user->courses = $newCourses;
                                        }
                                        else
                                        {
                                            $search = 1;
                                            foreach ($user->courses as $item)
                                            {
                                                if(array_key_exists('_id', $item))
                                                {
                                                    if($item['_id'] == (string) $course->_id)
                                                        $search = 2;
                                                }
                                            }
                                            if($search == 1)
                                            {
                                                $newCourses = $user->courses;
                                                $tmp = array(
                                                    '_id' => (string) $course->_id,
                                                    'status' => '0',
                                                    'registrant' => Yii::$app->user->identity->username
                                                );
                                                array_push($newCourses, $tmp);
                                                $user->courses = $newCourses;
                                            }
                                        }
                                        $issuance_certificate_information = new stdClass();
                                        if(isset($data['A']))
                                            if($data['A'] != '')
                                                $issuance_certificate_information->first_name_fa = $data['A'];
                                        if(isset($data['B']))
                                            if($data['B'] != '')
                                                $issuance_certificate_information->last_name_fa = $data['B'];
                                        if(isset($data['D']))
                                            if($data['D'] != '')
                                                $issuance_certificate_information->id = (string) $data['D'];
                                        if(isset($data['E']))
                                            if($data['E'] != '')
                                                $issuance_certificate_information->first_name_en = $data['E'];
                                        if(isset($data['F']))
                                            if($data['F'] != '')
                                                $issuance_certificate_information->last_name_en = $data['F'];
                                        if(isset($data['G']) && $data['G'] != '')
                                        {
                                            $gender = '';
                                            if($data['G'] == 2 || $data['G'] == '2' || $data['G'] == '۲')
                                                $gender = '0';
                                            else if($data['G'] == 1 || $data['G'] == '1' || $data['G'] == '۱')
                                                $gender = '1';
                                            $issuance_certificate_information->gender = $gender;
                                        }
                                        $user->issuance_certificate_information = $issuance_certificate_information;
                                        StudentAccess::addColleges($user, StudentAccess::normalizeColleges($course->college));
                                        $user->save();
                                    }
                                    else
                                    {
                                        if($data['A'] != '' && $data['B'] != '' && $data['C'] != '' && $data['D'] != '')
                                        {
                                            $model = new Users();
                                            $model->first_name = $data['A'];
                                            $model->last_name = $data['B'];
                                            $model->username = strtolower(trim($data['C']));
                                            $model->setPassword(trim($data['D']));
                                            $model->auth_key = Yii::$app->security->generateRandomString();
                                            $model->verification_token = Yii::$app->security->generateRandomString();
                                            $model->getAuthKey();
                                            $model->role = 'user';
                                            // users.college آرایه است (docs/specs/users-manage.md بند ۲)
                                            $model->college = StudentAccess::normalizeColleges($course->college);
                                            $model->status = 10;
                                            $model->courses = array(
                                                '0' => array(
                                                    '_id' => (string) $course->_id,
                                                    'status' => '0',
                                                    'registrant' => Yii::$app->user->identity->username
                                                )
                                            );
                                            $model->registrant = Yii::$app->user->identity->username;
                                            $issuance_certificate_information = new stdClass();
                                            $issuance_certificate_information->first_name_fa = $data['A'];
                                            $issuance_certificate_information->last_name_fa = $data['B'];
                                            $issuance_certificate_information->id = (string) $data['D'];
                                            if(isset($data['E']))
                                                if($data['E'] != '')
                                                    $issuance_certificate_information->first_name_en = $data['E'];
                                            if(isset($data['F']))
                                                if($data['F'] != '')
                                                    $issuance_certificate_information->last_name_en = $data['F'];
                                            if(isset($data['G']))
                                            {
                                                if($data['G'] != '')
                                                {
                                                    if($data['G'] == '1'  || $data['G'] == '۱' || $data['G'] == '2' || $data['G'] == '۲')
                                                    {
                                                        if($data['G'] == '1'  || $data['G'] == '۱')
                                                            $gender = '1';
                                                        else
                                                            $gender = '0';
                                                        $issuance_certificate_information->gender = $gender;
                                                    }
                                                }
                                            }
                                            $model->issuance_certificate_information = $issuance_certificate_information;
                                            $model->save();
                                        }
                                    }
                                    $order = new Orders();
                                    $order->username = strtolower(trim($data['C']));
                                    $orders = array(
                                        '0' => array(
                                            '_id' => (string) $course->_id,
                                            'type' => '1',
                                            'payment_method' => '1',
                                            'price' => $course->discount_price
                                        )
                                    );
                                    $order->orders = $orders;
                                    $order->amount = $course->price;
                                    $order->first_name = trim($data['A']);
                                    $order->last_name = trim($data['B']);
                                    $paymentInfo = array(
                                        'order_id' => 'wallet',
                                        'date' => jdate('Y/m/d'),
                                        'clean_date' => jdate('Ymd'),
                                    );
                                    $order->payment_info = $paymentInfo;
                                    if($broker != null)
                                    {
                                        $collegeShare = ((100 - $contract['share']) / 100) * $course->price;
                                        $brokerShare = ($contract['share'] / 100) * $course->price;
                                        $shares = array(
                                            '0' => array(
                                                'id' => (string) $course->_id,
                                                'college' => (string) $broker->college,
                                                'item' => $course->title['main_fa'],
                                                'price' => $course->price,
                                                'broker' => (string) $broker->_id,
                                                'broker_contract_percent' => $contract['share'],
                                                'college_share' => $collegeShare,
                                                'broker_share' => $brokerShare,
                                            )
                                        );
                                    }
                                    else
                                    {
                                        $collegeShare = $course->price;
                                        $shares = array(
                                            '0' => array(
                                                'id' => (string) $course->_id,
                                                'college' => (string) $course->college,
                                                'item' => $course->title['main_fa'],
                                                'price' => $course->price,
                                                'broker' => null,
                                                'broker_contract_percent' => '0',
                                                'college_share' => $collegeShare,
                                                'broker_share' => '0',
                                            )
                                        );
                                    }
                                    $order->shares = $shares;
                                    $order->status = '1';
                                    if($allowFree === false)
                                        $order->save();
                                }

                            }
                        }
                        // Call AdobeConnect For Create Meetings Course
                        $adminRole = Admin::find()->where(['role' => 'user'])->one();
                        if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                        {
                            $curl = curl_init();

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) $course->_id,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_HTTPHEADER => array(
                                    '_id: '.(string) $adminRole->_id
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                        }
                        // Call AdobeConnect For Create Meetings Course


                        if($allowFree === false)
                        {
                            $newWalletAmount = $brokerWalletAmount - $allAmount;
                            $broker->wallet_amount = $newWalletAmount;
                            $broker->save();
                            $model = new WalletTransactions();
                            $model->amount = (string) $allAmount;
                            $model->broker_id = (string) $broker->_id;
                            $model->college = (string) $broker->college;
                            $model->status = '1';
                            $model->date = jdate('Y/m/d');
                            $model->reference_id = Yii::$app->request->post('fileName');
                            $model->type = '3';
                            $model->description = 'افزودن '.$allExcelUserCount.' نفر به دوره '.$course->title['main_fa'];
                            $model->save();
                        }


                        Yii::$app->session->setFlash('status','9');
                    }
                    else
                        Yii::$app->session->setFlash('status','32');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
    private function isValidRow($data)
    {
        // بررسی کن که حداقل یکی از فیلدهای اصلی پر باشه
        return (
            (isset($data['A']) && !empty(trim($data['A']))) || // نام
            (isset($data['B']) && !empty(trim($data['B']))) || // نام خانوادگی
            (isset($data['C']) && !empty(trim($data['C']))) || // نام کاربری
            (isset($data['D']) && !empty(trim($data['D'])))    // کد ملی
        );
    }

    /**
     * بررسی صحت اطلاعات یک سطر از فایل اکسل افزودن کاربر
     * خروجی: آرایه ای از ستون های ایرادار به همراه علت ایراد
     * (اگر آرایه خالی باشد یعنی سطر هیچ ایرادی ندارد)
     */
    private function getExcelRowErrors($data)
    {
        $errors = array();

        $firstName = isset($data['A']) ? trim((string) $data['A']) : '';
        $lastName  = isset($data['B']) ? trim((string) $data['B']) : '';
        $username  = isset($data['C']) ? trim((string) $data['C']) : '';
        $password  = isset($data['D']) ? trim((string) $data['D']) : '';
        $gender    = isset($data['G']) ? trim((string) $data['G']) : '';

        // ۱- فیلدهای اجباری
        if ($firstName === '')
            $errors['A'] = 'نام وارد نشده است';
        if ($lastName === '')
            $errors['B'] = 'نام خانوادگی وارد نشده است';
        if ($password === '')
            $errors['D'] = 'کد ملی (رمز عبور) وارد نشده است';

        // ۲- نام کاربری: یا فقط عدد لاتین (شماره همراه) باشد یا اگر حروف انگلیسی دارد، ایمیل معتبر باشد
        if ($username === '')
            $errors['C'] = 'نام کاربری وارد نشده است';
        else if (preg_match('/[A-Za-z]/', $username) === 1)
        {
            if (filter_var($username, FILTER_VALIDATE_EMAIL) === false)
                $errors['C'] = 'نام کاربری حروف انگلیسی دارد، پس باید فرمت ایمیل معتبر داشته باشد';
        }
        else if (preg_match('/^[0-9]+$/', $username) !== 1)
            $errors['C'] = 'نام کاربری باید فقط عدد لاتین (شماره همراه) یا یک ایمیل معتبر باشد';

        // ۳- جنسیت: اجباری و فقط عدد ۱ (مرد) یا ۲ (زن)
        if ($gender === '')
            $errors['G'] = 'جنسیت وارد نشده است';
        else if (in_array($gender, array('1', '2', '۱', '۲'), true) === false)
            $errors['G'] = 'جنسیت فقط می تواند عدد ۱ (مرد) یا ۲ (زن) باشد';

        return $errors;
    }

    /**
     * ساخت style و tooltip خانه های جدول بررسی فایل اکسل
     * خانه ای که خودش ایراد دارد قرمز پررنگ و همراه با علت ایراد نمایش داده می شود
     * و بقیه خانه های همان سطر قرمز کمرنگ می شوند
     */
    private function excelCellStyle($cellErrors, $column)
    {
        if (count($cellErrors) == 0)
            return '';

        if ($column !== null && isset($cellErrors[$column]))
            return ' style="background-color:#ffb1b1;" title="' . htmlspecialchars($cellErrors[$column], ENT_QUOTES, 'UTF-8') . '"';

        return ' style="background-color:#ffdede;"';
    }

    public function actionMembers_report()
    {
        date_default_timezone_set('Asia/Tehran');
        require_once(Yii::$app->basePath . '/web/jdf.php');
        Yii::$app->setTimeZone('Asia/Tehran');

        // افزایش محدودیت‌ها (اختیاری)
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $courseId = Yii::$app->request->get('_id');
        $searchModel = new CoursesMembers();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $courseId);
        $courseDetail = Courses::findOne($courseId);

        // ========== 1. پیش‌بارگذاری اطلاعات سفارش اولیه (Orders) ==========
        $allOrders = [];
        $ordersQuery = Orders::find()->where(['orders._id' => $courseId])->all();
        foreach ($ordersQuery as $order) {
            // بررسی می‌کنیم که آیا سفارش مربوط به همین دوره است
            if (!empty($order->orders)) {
                foreach ($order->orders as $item) {
                    if ((string)$item['_id'] === (string)$courseId) {
                        $allOrders[$order->username] = $order;
                        break;
                    }
                }
            }
        }

        // ========== 2. پیش‌بارگذاری اطلاعات اقساط (Installments) ==========
        $allInstallments = Installments::find()
            ->where(['course_id' => $courseId])
            ->indexBy('username')
            ->all();

        // ========== 3. ستون‌های ثابت ==========
        $columns = [
            ['attribute' => 'first_name', 'header' => 'نام'],
            ['attribute' => 'last_name', 'header' => 'نام خانوادگی'],
            ['attribute' => 'username', 'header' => 'نام کاربری'],
            [
                'attribute' => function($model) use ($courseId) {
                    $memberCourse = null;
                    if ($model->courses != null) {
                        foreach ($model->courses as $course) {
                            if ($course['_id'] == $courseId) {
                                $memberCourse = $course;
                                break;
                            }
                        }
                    }
                    $registrant = 'نامشخص';
                    if (is_array($memberCourse) && array_key_exists('registrant', $memberCourse)) {
                        if ($memberCourse['registrant'] == Yii::getAlias('@adminUsername')) {
                            $registrant = 'مدیریت';
                        } elseif ($memberCourse['registrant'] == $model->username) {
                            $registrant = 'کاربر';
                        } else {
                            $registrantDetail = DashboardController::registrant_detail($memberCourse['registrant']);
                            $role = '';
                            if ($registrantDetail->role == 'emp')
                                $role = 'کارشناس دانشکده';
                            else if ($registrantDetail->role == 'broker')
                                $role = 'کارگزار';
                            $registrant = $registrantDetail->first_name . ' ' . $registrantDetail->last_name . '(' . $role . ')';
                        }
                    }
                    return $registrant;
                },
                'header' => 'ثبت کننده'
            ],
            [
                'attribute' => function($model) use ($courseId) {
                    $memberCourse = null;
                    if ($model->courses != null) {
                        foreach ($model->courses as $course) {
                            if ($course['_id'] == $courseId) {
                                $memberCourse = $course;
                                break;
                            }
                        }
                    }
                    $status = 'نامشخص';
                    if ($memberCourse != null) {
                        if ($memberCourse['status'] == '0')
                            $status = 'خطا در ثبت در ادوبی';
                        else if ($memberCourse['status'] == '1')
                            $status = 'فعال';
                        else if ($memberCourse['status'] == '2')
                            $status = 'غیرفعال';
                    }
                    return $status;
                },
                'header' => 'وضعیت'
            ],
        ];

        // ========== 4. ستون‌های پرداخت اولیه (Orders) ==========
        $columns[] = [
            'attribute' => function($model) use ($allOrders) {
                $order = $allOrders[$model->username] ?? null;
                if ($order && $order->status == '1') {
                    return number_format($order->amount);
                }
                return 'پرداخت نشده';
            },
            'header' => 'مبلغ پرداخت اولیه'
        ];
        $columns[] = [
            'attribute' => function($model) use ($allOrders) {
                $order = $allOrders[$model->username] ?? null;
                if ($order && $order->status == '1' && isset($order->payment_info['date'])) {
                    return $order->payment_info['date'];
                }
                return '-';
            },
            'header' => 'تاریخ پرداخت اولیه'
        ];
        $columns[] = [
            'attribute' => function($model) use ($allOrders) {
                $order = $allOrders[$model->username] ?? null;
                if ($order && $order->status == '1' && isset($order->payment_info['tref'])) {
                    return ' '.$order->payment_info['tref'].' ';
                }
                return '-';
            },
            'header' => 'شماره رهگیری پرداخت اولیه'
        ];
        $columns[] = [
            'attribute' => function($model) use ($allOrders) {
                $order = $allOrders[$model->username] ?? null;
                if ($order && $order->discount_code != null) {
                    $discount = Discounts::find()->where(['code' => $order->discount_code])->one();
                    if($discount != null)
                        return number_format($discount->amount);
                    else
                        return '0';
                }
                return '0';
            },
            'header' => 'مبلغ تخفیف (تومان)'
        ];

        // ========== 5. ستون‌های اقساط (داینامیک) ==========
        $installmentsPlan = $courseDetail->installments ?? [];
        if (!empty($installmentsPlan) && is_array($installmentsPlan)) {
            // برای هر قسط سه ستون جداگانه (وضعیت، تاریخ، رهگیری)
            foreach ($installmentsPlan as $index => $installment) {
                $installmentNumber = $index + 1;
                $amount = $installment['amount'] ?? '-';
                $deadline = $installment['deadline'] ?? '-';
                $headerBase = "قسط {$installmentNumber} (مبلغ: {$amount} - مهلت: {$deadline})";

                // وضعیت قسط
                $columns[] = [
                    'attribute' => function($model) use ($allInstallments, $index) {
                        $inst = $allInstallments[$model->username] ?? null;
                        if ($inst && isset($inst->maturities[$index]['status']) && $inst->maturities[$index]['status'] == '1') {
                            return 'پرداخت شده';
                        }
                        return 'پرداخت نشده';
                    },
                    'header' => "{$headerBase} - وضعیت"
                ];
                // تاریخ پرداخت قسط
                $columns[] = [
                    'attribute' => function($model) use ($allInstallments, $index) {
                        $inst = $allInstallments[$model->username] ?? null;
                        if ($inst && isset($inst->maturities[$index]['payment_info']['date'])) {
                            return number_format($inst->maturities[$index]['amount']);
                        }
                        return '-';
                    },
                    'header' => "{$headerBase} - مبلغ پرداختی"
                ];
                // شماره رهگیری قسط
                $columns[] = [
                    'attribute' => function($model) use ($allInstallments, $index) {
                        $inst = $allInstallments[$model->username] ?? null;
                        if ($inst && isset($inst->maturities[$index]['payment_info']['tref'])) {
                            return ' '.$inst->maturities[$index]['payment_info']['tref'].' ';
                        }
                        return '-';
                    },
                    'header' => "{$headerBase} - رهگیری"
                ];
            }

            // ========== 6. ستون جمع مبلغ اقساط پرداخت شده ==========
            $columns[] = [
                'attribute' => function($model) use ($allInstallments, $installmentsPlan) {
                    $inst = $allInstallments[$model->username] ?? null;
                    if (!$inst || empty($inst->maturities)) {
                        return '0';
                    }
                    $totalPaid = 0;
                    foreach ($inst->maturities as $idx => $maturity) {
                        if (isset($maturity['status']) && $maturity['status'] == '1') {
                            // مبلغ قسط از طرح دوره گرفته شود (با فرض ترتیب یکسان)
                            $amount = $installmentsPlan[$idx]['amount'] ?? 0;
                            $totalPaid += (int)$amount;
                        }
                    }
                    return number_format($totalPaid) ;
                },
                'header' => 'جمع مبلغ اقساط پرداخت شده'
            ];
        }

        // ========== 7. ساخت و خروجی اکسل ==========
        $exporter = new Spreadsheet([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
        ]);

        $tempFile = Yii::getAlias('@runtime') . '/members_report.xlsx';
        $exporter->save($tempFile);
        $fileName = $courseDetail->title['main_fa'] . '-' . jdate('Y/m/d-H:i:s') . '.xlsx';

        return Yii::$app->response->sendFile($tempFile, $fileName)->on(
            \yii\web\Response::EVENT_AFTER_SEND,
            function() use ($tempFile) {
                if (file_exists($tempFile)) unlink($tempFile);
            }
        );
    }

    public function actionChange_status()
    {
        if(Yii::$app->request->isPost)
        {
            $user = Users::findOne(Yii::$app->request->post()['Users']['_id']);
            if($user != null)
            {
                $course = Courses::findOne(Yii::$app->request->post('courseId'));
                if($user->courses != null)
                {
                    $userCourses = $user->courses;
                    $flag = null;
                    $i = 0;
                    $preStatus = null;
                    foreach ($userCourses as $item)
                    {
                        if($item['_id'] == Yii::$app->request->post('courseId'))
                            $flag = $i;
                        $i++;
                    }
                    if($flag !== null)
                    {
                        $preStatus = $userCourses[$flag]['status'];
                        if($userCourses[$flag]['status'] == '1')
                            $userCourses[$flag]['status'] = '2';
                        else
                            $userCourses[$flag]['status'] = '1';
                    }
                    $user->courses = $userCourses;
                    if($user->save())
                    {
                        if($preStatus != null)
                        {
                            if($preStatus == '1')
                            {
                                // Begin Call AdobeConnect For Remove User From Course
                                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                                if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                                {
                                    $curl = curl_init();

                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/remove-course-user/'.(string) $user->_id.'/'.(string) Yii::$app->request->post('courseId'),
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_HTTPHEADER => array(
                                            '_id: '.(string) $adminRole->_id
                                        ),
                                    ));

                                    $response = curl_exec($curl);
                                    curl_close($curl);
                                }
                                // End Call AdobeConnect For Remove User From Course
                            }
                            else if($preStatus == '2')
                            {
                                $userCourses[$flag]['status'] = '0';
                                $user->courses = $userCourses;
                                $user->save();
                                // Call AdobeConnect For Create Meetings Course
                                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                                if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                                {
                                    $curl = curl_init();

                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) Yii::$app->request->post('courseId'),
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_HTTPHEADER => array(
                                            '_id: '.(string) $adminRole->_id
                                        ),
                                    ));
                                    $response = curl_exec($curl);
                                    curl_close($curl);
                                }
                                // Call AdobeConnect For Create Meetings Course
                            }
                        }
                        Yii::$app->session->setFlash('status','10');
                    }
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChange_role()
    {
        if(Yii::$app->request->isPost)
        {
            $user = Users::findOne(Yii::$app->request->post()['Users']['_id']);
            if($user != null)
            {
                $principal = Principals::find()->where(['username' => $user->username])->one();
                if($principal != null)
                {
                    $course = Courses::findOne(Yii::$app->request->post('courseId'));
                    if($course != null)
                    {
                        $userCourses = $user->courses;
                        $courseIndex = null;
                        $row = 0;
                        foreach ($userCourses as $item)
                        {
                            if($item['_id'] == Yii::$app->request->post('courseId'))
                                $courseIndex = $row;
                            $row++;
                        }
                       if($courseIndex !== null)
                       {
                           $userRole = 'user';
                           if(array_key_exists('role' ,$userCourses[$courseIndex]))
                               if($userCourses[$courseIndex]['role'] == 'mentor')
                                   $userRole = 'mentor';
                           if($userRole == 'user')
                           {
                               $findAdmin = Admin::find()->where(['username' => $user->username])->one();
                               if($findAdmin != null)
                               {
                                   if($findAdmin->role == 'teacher' && $findAdmin->mentor == true)
                                   {
                                       $colleges = $findAdmin->college;
                                       if(array_search($course->college, $findAdmin->college) === null)
                                       {
                                           array_push($colleges, $course->college);
                                           $findAdmin->college = $colleges;
                                           $findAdmin->save();
                                       }
                                       $courseMentors = $course->mentors;
                                       if($courseMentors == null)
                                           $courseMentors[0] = $user->username;
                                       else
                                       {
                                           if(!array_search($user->username, $courseMentors))
                                               array_push($courseMentors, $user->username);
                                       }
                                       $course->mentors = $courseMentors;
                                       if($course->save())
                                       {
                                           //Begin Call Api For add Mentor To Course
                                           $userAdmin = Admin::find()->where(['role' => 'user'])->one();
                                           if($userAdmin != null && ($course->content_type == '1' || $course->content_type == '2'))
                                           {
                                               $curl = curl_init();
                                               curl_setopt_array($curl, array(
                                                   CURLOPT_URL => 'https://api-eec.ut.ac.ir/adobe-connect/update-user-role',
                                                   CURLOPT_RETURNTRANSFER => true,
                                                   CURLOPT_ENCODING => '',
                                                   CURLOPT_MAXREDIRS => 10,
                                                   CURLOPT_TIMEOUT => 0,
                                                   CURLOPT_FOLLOWLOCATION => true,
                                                   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                   CURLOPT_CUSTOMREQUEST => 'POST',
                                                   CURLOPT_POSTFIELDS =>'{
                                        "course_id": "'.(string) $course->_id.'",
                                        "principal_id": "'. $principal->principal_id.'",
                                        "role": "host"
                                    }',
                                                   CURLOPT_HTTPHEADER => array(
                                                       '_id: '.(string) $userAdmin->_id,
                                                       'Content-Type: application/json'
                                                   ),
                                               ));
                                               $response = curl_exec($curl);
                                               curl_close($curl);
                                           }
                                           //End Call Api For add Mentor To Course

                                           //Begin Update User Role in Users Collection
                                           $userCourses[$courseIndex]['role'] = 'mentor';
                                           $user->courses = $userCourses;
                                           $user->save();
                                           //End Update User Role in Users Collection
                                           Yii::$app->session->setFlash('status','17');
                                       }
                                       else
                                           Yii::$app->session->setFlash('status','2');
                                   }
                                   else
                                       Yii::$app->session->setFlash('status','16');
                               }
                               else
                               {
                                   $college[0] = $course->college;
                                   $admin = new Admin();
                                   $admin->first_name = $user->first_name;
                                   $admin->last_name = $user->last_name;
                                   $admin->password_hash = $user->password_hash;
                                   $admin->username = $user->username;
                                   $admin->auth_key = Yii::$app->security->generateRandomString();
                                   $admin->verification_token = Yii::$app->security->generateRandomString();
                                   $admin->getAuthKey();
                                   $admin->role = 'teacher';
                                   $admin->status = 10;
                                   $admin->college = $college;
                                   $admin->principal_id = $user->principal_id;
                                   $admin->mentor = true;
                                   $admin->access = array("courses","packages","test-maker","upload-center");
                                   if($admin->save())
                                   {
                                       $courseMentors = $course->mentors;
                                       if($courseMentors == null)
                                           $courseMentors[0] = $user->username;
                                       else
                                       {
                                           if(!array_search($user->username, $courseMentors))
                                               array_push($courseMentors, $user->username);
                                       }
                                       $course->mentors = $courseMentors;
                                       if($course->save())
                                       {
                                           // Begin Call API For Change User To Mentor in AdobeConnect
                                           $userAdmin = Admin::find()->where(['role' => 'user'])->one();
                                           if($userAdmin != null  && ($course->content_type == '1' || $course->content_type == '2'))
                                           {
                                               $curl = curl_init();
                                               curl_setopt_array($curl, array(
                                                   CURLOPT_URL => 'https://api-eec.ut.ac.ir/adobe-connect/update-user-role',
                                                   CURLOPT_RETURNTRANSFER => true,
                                                   CURLOPT_ENCODING => '',
                                                   CURLOPT_MAXREDIRS => 10,
                                                   CURLOPT_TIMEOUT => 0,
                                                   CURLOPT_FOLLOWLOCATION => true,
                                                   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                   CURLOPT_CUSTOMREQUEST => 'POST',
                                                   CURLOPT_POSTFIELDS =>'{
                                            "course_id": "'.(string) $course->_id.'",
                                            "principal_id": "'. $principal->principal_id.'",
                                            "role": "host"
                                        }',
                                                   CURLOPT_HTTPHEADER => array(
                                                       '_id: '.(string) $userAdmin->_id,
                                                       'Content-Type: application/json'
                                                   ),
                                               ));
                                               $response = curl_exec($curl);
                                               curl_close($curl);
                                           }
                                           // End Call API For Change User To Mentor in AdobeConnect

                                           //Begin Update User Role in Users Collection
                                           if($courseIndex !== null)
                                           {
                                               $userCourses[$courseIndex]['role'] = 'mentor';
                                           }
                                           $user->courses = $userCourses;
                                           $user->save();
                                           //End Update User Role in Users Collection
                                           Yii::$app->session->setFlash('status','17');
                                       }
                                       else
                                           Yii::$app->session->setFlash('status','2');
                                   }
                                   else
                                       Yii::$app->session->setFlash('status','2');
                               }
                           }
                           else // Change User Role From Mentor To User(View)
                           {
                               $findAdmin = Admin::find()->where(['username' => $user->username])->one();
                               if($findAdmin != null)
                               {
                                   if($findAdmin->role == 'teacher' && $findAdmin->mentor == true)
                                   {
                                       $courseMentors = $course->mentors;
                                       if($courseMentors != null)
                                       {
                                           if(array_search($user->username, $courseMentors) !== null)
                                           {
                                               $i = 0;
                                               $index = null;
                                               foreach ($courseMentors as $item)
                                               {
                                                   if($item == $user->username)
                                                       $index = $i;
                                                   $i++;
                                               }
                                               if($index !== null)
                                               {
                                                   unset($courseMentors[$index]);
                                                   $courseMentors = array_values($courseMentors);
                                               }
                                           }
                                       }
                                       $course->mentors = $courseMentors;
                                       if($course->save())
                                       {
                                           //Begin Call Api For Delete Mentor To Course
                                           $userAdmin = Admin::find()->where(['role' => 'user'])->one();
                                           if($userAdmin != null && ($course->content_type == '1' || $course->content_type == '2'))
                                           {
                                               $curl = curl_init();
                                               curl_setopt_array($curl, array(
                                                   CURLOPT_URL => 'https://api-eec.ut.ac.ir/adobe-connect/update-user-role',
                                                   CURLOPT_RETURNTRANSFER => true,
                                                   CURLOPT_ENCODING => '',
                                                   CURLOPT_MAXREDIRS => 10,
                                                   CURLOPT_TIMEOUT => 0,
                                                   CURLOPT_FOLLOWLOCATION => true,
                                                   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                                   CURLOPT_CUSTOMREQUEST => 'POST',
                                                   CURLOPT_POSTFIELDS =>'{
                                            "course_id": "'.(string) $course->_id.'",
                                            "principal_id": "'. $principal->principal_id.'",
                                            "role": "view"
                                        }',
                                                   CURLOPT_HTTPHEADER => array(
                                                       '_id: '.(string) $userAdmin->_id,
                                                       'Content-Type: application/json'
                                                   ),
                                               ));
                                               $response = curl_exec($curl);
                                               curl_close($curl);
                                           }
                                           // End Call Api For Delete Mentor To Course

                                           //Begin Update User Role in Users Collection
                                           if($courseIndex !== null)
                                           {
                                               $userCourses[$courseIndex]['role'] = 'user';
                                           }
                                           $user->courses = $userCourses;
                                           $user->save();
                                           //End Update User Role in Users Collection
                                           Yii::$app->session->setFlash('status','17');
                                       }
                                       else
                                           Yii::$app->session->setFlash('status','2');
                                   }
                                   else
                                       Yii::$app->session->setFlash('status','16');
                               }
                           }
                       }
                    }
                }
            }
            else
                Yii::$app->session->setFlash('status','15');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDelete_user_from_course()
    {
        if(Yii::$app->request->isPost)
        {
            $userId = isset(Yii::$app->request->post()['Users']['_id']) ? Yii::$app->request->post()['Users']['_id'] : null;
            $user = is_string($userId) && preg_match('/^[a-f0-9]{24}$/i', $userId) ? Users::findOne($userId) : null;
            $course = Courses::findOne((string) Yii::$app->request->post('courseId'));
            if($user != null && $course != null)
            {
                // کارگزار (و در دوره‌های کوتاه‌مدت، کارشناس واحد هم) مستقیم حذف نمی‌کند: درخواست انصراف
                // ثبت می‌شود و پس از تأیید مدیر در «درخواست انصراف» اعمال می‌شود. مدیر سیستم مستقیم حذف می‌کند.
                $role = Yii::$app->user->identity->role;
                $requestOnly = $role == 'broker' || (!\app\components\CourseAccess::isAdmin() && (string) $course->type === '1');
                if ($requestOnly && CancelingRequests::find()->where(['username' => $user->username, 'course_id' => (string) $course->_id, 'status' => '0'])->exists())
                {
                    Yii::$app->session->setFlash('status', '111');
                    return $this->redirect(\app\components\SafeRedirect::referrer(['index']));
                }
                //Add Cancel Request To Collection
                $order = Orders::find()->where(['username' => $user->username])->andWhere(['orders._id' => (string) $course->_id])->one();
                if($order != null)
                {
                    if($order->shares != null)
                    {
                        if(array_key_exists('college_share', $order->shares[0]))
                        {
                            if($course->broker != null)
                                $broker_id = $course->broker['_id'];
                            else
                                $broker_id = null;
                            if($requestOnly)
                            {
                                $registrant = $role == 'broker' ? $broker_id : Yii::$app->user->identity->username;
                                $status = '0';
                                $registrantRole = $role;
                            }
                            else
                            {
                                $registrant = Yii::$app->user->identity->username;
                                $status = 'approved';
                                $registrantRole = Yii::$app->user->identity->role;
                            }
                            $cancelRequest = new CancelingRequests();
                            $cancelRequest->username = $user->username;
                            $cancelRequest->course_id = (string) $course->_id;
                            $cancelRequest->order_id = (string) $order->_id;
                            $cancelRequest->college = $course->college;
                            $cancelRequest->registrant = $registrant;
                            $cancelRequest->request_date = jdate('Y/m/d');
                            $cancelRequest->status = $status;
                            $cancelRequest->registrant_role = $registrantRole;
                            $cancelRequest->save();
                            $walletTransaction = new WalletTransactions();
                            $amount = $order->shares[0]['college_share'];
                            if(!$requestOnly)
                            {
                                $walletTransaction->amount = $amount;
                                $walletTransaction->broker_id = $broker_id;
                                $walletTransaction->college = $course->college;
                                $walletTransaction->status = '1';
                                $walletTransaction->date = jdate('Y/m/d');
                                $walletTransaction->type = '6';
                                $walletTransaction->save();
                                if($broker_id != null)
                                {
                                    $broker = Brokers::findOne($broker_id);
                                    if($broker != null)
                                    {
                                        $broker_wallet_amount = 0;
                                        if($broker->wallet_amount != null)
                                            $broker_wallet_amount = $broker->wallet_amount;
                                        $broker_wallet_amount += $amount;
                                        $broker->wallet_amount = $broker_wallet_amount;
                                        $broker->save();
                                    }
                                }
                            }
                        }
                    }
                }
                else if ($requestOnly)
                {
                    // عضو بدون سفارش (افزوده‌شده بدون پرداخت): درخواست بدون بازگشت وجه ثبت می‌شود
                    $cancelRequest = new CancelingRequests();
                    $cancelRequest->username = $user->username;
                    $cancelRequest->course_id = (string) $course->_id;
                    $cancelRequest->order_id = null;
                    $cancelRequest->college = $course->college;
                    $cancelRequest->registrant = $role == 'broker' && $course->broker != null ? $course->broker['_id'] : Yii::$app->user->identity->username;
                    $cancelRequest->request_date = jdate('Y/m/d');
                    $cancelRequest->status = '0';
                    $cancelRequest->registrant_role = $role;
                    $cancelRequest->save();
                }
                //Add Cancel Request To Collection

                $userCourses = $user->courses;
                if($userCourses != null)
                {
                    $index = null;
                    $row = 0;
                    foreach ($userCourses as $item)
                    {
                        if($item['_id'] == Yii::$app->request->post('courseId'))
                            $index = $row;
                        $row++;
                    }
                    if($index !== null)
                    {
                        if(!$requestOnly)
                        {
                            unset($userCourses[$index]);
                            $userCourses = array_values($userCourses);
                        }
                        else
                        {
                            $userCourses[$index]['begin_deleted'] = true;
                        }
                        $user->courses = $userCourses;
                        if($user->save())
                        {
                            if(!$requestOnly)
                            {
                                // Begin Call AdobeConnect For Remove User From Course
                                $adminRole = Admin::find()->where(['role' => 'user'])->one();
                                if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                                {
                                    $curl = curl_init();

                                    curl_setopt_array($curl, array(
                                        CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/remove-course-user/'.(string) $user->_id.'/'.(string) Yii::$app->request->post('courseId'),
                                        CURLOPT_RETURNTRANSFER => true,
                                        CURLOPT_ENCODING => '',
                                        CURLOPT_MAXREDIRS => 10,
                                        CURLOPT_TIMEOUT => 0,
                                        CURLOPT_FOLLOWLOCATION => true,
                                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                        CURLOPT_CUSTOMREQUEST => 'POST',
                                        CURLOPT_HTTPHEADER => array(
                                            '_id: '.(string) $adminRole->_id
                                        ),
                                    ));

                                    $response = curl_exec($curl);
                                    curl_close($curl);
                                }
                                // End Call AdobeConnect For Remove User From Course
                                Yii::$app->session->setFlash('status','11');
                            }
                            else
                                Yii::$app->session->setFlash('status','111');
                        }
                        else
                            Yii::$app->session->setFlash('status','2');
                    }
                }
            }
        }
        return $this->redirect(\app\components\SafeRedirect::referrer(['index']));
    }

    public function actionEdit_prepayment_installments()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($course != null)
            {
                $course->load(Yii::$app->request->post());
                if($course->save())
                    Yii::$app->session->setFlash('status','12');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionEdit_installments()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($course != null)
            {
               if($course->installments != null)
               {
                   $installments = $course->installments;
                   $installments[Yii::$app->request->post('row')] = Yii::$app->request->post()['Courses']['installments'][Yii::$app->request->post('row')];
                   $course->installments = $installments;
                   if($course->save())
                       Yii::$app->session->setFlash('status','13');
                   else
                       Yii::$app->session->setFlash('status','2');
               }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDelete_installments()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post()['Courses']['_id']);
            if($course != null)
            {
                if($course->installments != null)
                {
                    $installments = $course->installments;
                    unset($installments[Yii::$app->request->post('row')]);
                    $installments = array_values($installments);
                    $course->installments = $installments;
                    if($course->save())
                        Yii::$app->session->setFlash('status','14');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionShow_course_users()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $course = $this->findPackage((string) Yii::$app->request->post('id'));
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
        $course = $this->findPackage((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canDelete($course))
            return $this->listBack('error', 'این دوره قابل حذف نیست (دانشپذیر دارد یا دسترسی لازم را ندارید)');
        if ($course->delete())
            return $this->listBack('success', 'دوره حذف شد');
        return $this->listBack('error', 'خطا در حذف دوره');
    }

    /**
     * برمی‌گردونه اولین پیغام خطای واقعیِ یک مدل (برای فلش‌های اعتبارسنجی
     * جدید تغییر ۶/۷/۹/۱۰ - 2026-08-28)، تا کاربر دقیقاً بفهمه چرا رد شده،
     * نه یک پیغام کلی «خطایی رخ داده است».
     */
    private function firstModelErrorMessage($model)
    {
        $errors = $model->getFirstErrors();
        if (!empty($errors)) {
            return reset($errors);
        }
        return 'اطلاعات وارد شده معتبر نیست';
    }

    public function actionAdd_installment()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post('_id'));
            if($course != null)
            {
                $installments = array();
                $newInstallment = array(
                    'deadline' => Yii::$app->request->post('deadline'),
                    'amount' => Yii::$app->request->post('amount')
                );
                $installments['0'] = $newInstallment;
                // تغییر ۶ (2026-08-28): همون قانون Yii2 Validation که برای
                // create-package هست (Courses::validateInstallmentsAgainstCourse)
                // اینجا هم دوباره‌استفاده شده - نه یک قانون تازه. اسکوپ اعتبارسنجی
                // فقط روی «installments» نگه داشته شده (نه کل سناریوی
                // edit-package) تا فیلدهای بی‌ربط این درخواست (قیمت، ظرفیت، نوع
                // دوره) که اصلاً توی این فرم وجود ندارن هیچ‌وقت اینجا چک نشن.
                $course->scenario = Courses::SCENARIO_EDIT_PACKAGE;
                $course->prepayment_installments = Yii::$app->request->post('prepayment_installments');
                $course->installments = $installments;
                if($course->validate(['installments']) && $course->save(false))
                    Yii::$app->session->setFlash('status','19');
                else
                {
                    Yii::$app->session->setFlash('status','35');
                    Yii::$app->session->setFlash('installmentError', $this->firstModelErrorMessage($course));
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAdd_single_installment()
    {
        if(Yii::$app->request->isPost)
        {
            $course = Courses::findOne(Yii::$app->request->post('_id'));
            if($course != null)
            {
                if($course->prepayment_installments != null && $course->prepayment_installments != '')
                {
                    $installments = $course->installments;
                    $newInstallment = array(
                        'deadline' => Yii::$app->request->post('deadline'),
                        'amount' => Yii::$app->request->post('amount')
                    );
                    array_push($installments, $newInstallment);
                    // تغییر ۶/۷ (2026-08-28): قبلاً اینجا فقط تاریخ با مقایسه‌ی
                    // رشته‌ای دستی چک می‌شد (بدون اینکه required بودن یا مجموع
                    // مبلغ اقساط نسبت به قیمت دوره اصلاً بررسی بشه). حالا همون
                    // ولیدیتور واقعی مدل (Courses::validateInstallmentsAgainstCourse)
                    // که هر سه قانون (الزامی بودن، تاریخ، مجموع مبلغ) رو با هم
                    // چک می‌کنه، عیناً استفاده شده - دقیقاً همون قانون تاریخی که
                    // از قبل اینجا بود («مساوی مجاز، بیشتر غیرمجاز») هم داخلش
                    // حفظ شده.
                    $course->scenario = Courses::SCENARIO_EDIT_PACKAGE;
                    $course->installments = $installments;
                    if($course->validate(['installments']) && $course->save(false))
                        Yii::$app->session->setFlash('status','20');
                    else
                    {
                        Yii::$app->session->setFlash('status','35');
                        Yii::$app->session->setFlash('installmentError', $this->firstModelErrorMessage($course));
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAdd_user_to_adobe()
    {
        // Call AdobeConnect For Create Meetings Course
        $adminRole = Admin::find()->where(['role' => 'user'])->one();
        if($adminRole != null)
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-users-courses/'.(string) Yii::$app->request->post('courseId'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    '_id: '.(string) $adminRole->_id
                ),
            ));
            $response = curl_exec($curl);
            $response = json_decode($response);
            curl_close($curl);
            if(property_exists($response,'status'))
            {
                if($response->status == 'ok')
                    Yii::$app->session->setFlash('status','23');
                else
                    Yii::$app->session->setFlash('status','2');
            }
            else
                Yii::$app->session->setFlash('status','2');
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionShow_course_lessons()
    {
        if(isset($_POST['id']))
        {
            $id = explode('-', $_POST['id']);
            $course = Courses::findOne($id[0]);
            if($course != null)
            {
                $user = Users::findOne($id[1]);
                if($user != null)
                {
                    $score = Scores::find()->where(['course_id' => (string) $course->_id])->andWhere(['user_id' => (string) $user->_id])->one();
                    ob_start();
                    $form = ActiveForm::begin(
                        [
                            'action' => ['register_course_scores'],
                            "method" => "post",
                        ]
                    );
                    if($course->lessons != null)
                    {
                        echo '<div class="modal-body" id="body">';
                        echo ' <div class="row">';
                        $row = 0;
                        foreach ($course->lessons as $lesson)
                        {
                            $lessonDetail = Lessons::findOne($lesson['_id']);
                            if($score == null)
                                $model = new Scores();
                            else
                                $model = $score;
                            echo '<div class="col-6 col-md-6 col-sm-12 dol-lg-6 col-xl-6 mb-3">';
                            echo '<label for="nameWithTitle" class="form-label">'.Html::encode($lessonDetail->title).' </label>';
                            echo $form->field($model, 'scores['.$row.'][score]')->textInput(
                                [
                                    'class' => 'form-control text-start',
                                    'onkeypress' => "return (event.charCode >= 48 && event.charCode <= 57)  || (event.charCode == 46)"
//                                    'required' => true
                                ]
                            )->label(false);
                            echo $form->field($model, 'scores['.$row.'][lesson]')->hiddenInput(
                                [
                                    'value' => $lesson['_id']
                                ]
                            )->label(false);
                            echo $form->field($model, 'scores['.$row.'][registrant]')->hiddenInput(
                                [
                                    'value' => Yii::$app->user->identity->username
                                ]
                            )->label(false);
                            echo '</div>';
                            $row++;
                        }
                        echo '</div>';
                        echo '<input type="hidden" name="course_id" value="'.(string) $id[0].'">';
                        echo '<input type="hidden" name="user_id" value="'.(string) $id[1].'">';
                        echo '</div>';
                        echo '
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-label-success">ثبت نهایی نمرات</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                بستن
                            </button>
                        </div>
                        ';
                        ActiveForm::end();
                    }
                    $body = ob_get_contents();
                    ob_end_clean();

                    ob_start();
                    $submit = ob_get_contents();
                    ob_end_clean();
                    $response = array(
                        'title' => 'ثبت نمرات برای '.Html::encode($user->first_name.' '.$user->last_name).'('.Html::encode($user->username).')',
                        'body' => $body,
                        'submit' => $submit
                    );
                    return json_encode($response);
                }
                else
                    return  false;
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionShow_finance_info()
    {
        if(isset($_POST['id']))
        {
            $id = explode('-', (string) $_POST['id'], 2);
            $course = preg_match('/^[a-f0-9]{24}$/i', $id[0]) ? Courses::findOne($id[0]) : null;
            // امنیتی: قبلاً هر کاربرِ این صفحه اطلاعات مالی هر دوره‌ای را می‌دید
            if($course != null && \app\components\CourseAccess::canManage($course) && isset($id[1]))
            {
                $user = Users::find()->where(['username' => $id[1]])->one();
                if($user != null)
                {
                    $order = Orders::find()->where(['username' => $user->username])->andWhere(['orders._id' => (string) $course->_id])->andWhere(['status' => '1'])->one();
                    if($order != null)
                    {
                        ob_start();
                        echo ' <div class="alert alert-success" role="alert">';
                        if($order->orders[0]['payment_method'] == '2')
                            echo 'مبلغ پیش پرداخت: '.number_format($order->orders[0]['price']).' تومان';
                        else
                            echo 'مبلغ پرداخت شده: '.number_format($order->orders[0]['price']).' تومان';
                        echo '<br>';
                        echo 'تاریخ پرداخت: '.$order->payment_info['date'].'<br>';
                        echo 'شماره سفارش: '.$order->payment_info['order_id'].'<br>';
                        echo 'شماره مرجع پرداخت: '.$order->payment_info['reference_id'].'<br>';
                        echo '</div>';
                        if($order->orders[0]['payment_method'] == '2')
                        {
                            $installments = Installments::find()->where(['username' => $user->username])->andWhere(['course_id' => (string) $course->_id])->andWhere(['order_id' => (string) $order->_id])->one();
                            if($installments != null)
                            {
                                if($installments->maturities != null)
                                {
                                    $i = 1;
                                    foreach ($installments->maturities as $maturity)
                                    {
                                        $bg = 'alert-success';
                                        $status = 'پرداخت شده';
                                        if($maturity['status'] == '0')
                                        {
                                            if($maturity['deadline'] >= jdate('Ymd'))
                                            {
                                                $bg = 'alert-warning';
                                                $status = 'سررسید نشده';
                                            }
                                            else
                                            {
                                                $bg = 'alert-danger';
                                                $status = 'معوقه';
                                            }
                                        }
                                        echo ' <div class="alert '.$bg.'" role="alert">';
                                        echo 'قسط : '.$i;
                                        echo '<br>';
                                        echo 'وضعیت: '.$status.'<br>';
                                        echo 'تاریخ سررسید: '.$maturity['date'].'<br>';
                                        echo 'مبلغ: '.number_format($maturity['amount']).'<br>';
                                        if($maturity['status'] == '1')
                                        {
                                            echo 'تاریخ پرداخت: '.$maturity['payment_info']['date'].'<br>';
                                            echo 'شماره سفارش: '.$maturity['payment_info']['order_id'].'<br>';
                                            echo 'شماره مرجع پرداخت: '.$maturity['payment_info']['reference_id'].'<br>';
                                        }
                                        echo '</div>';
                                        $i++;
                                    }
                                }
                            }
                        }
                        $body = ob_get_contents();
                        ob_end_clean();
                        $response = array(
                            'title' => 'گزارش مالی '.Html::encode($user->first_name.' '.$user->last_name),
                            'body' => $body,
                        );
                        return json_encode($response);
                    }
                    else
                    {
                        ob_start();
                        echo 'اطلاعات مالی یافت نشد';
                        $body = ob_get_contents();
                        ob_end_clean();
                        $response = array(
                            'title' => 'گزارش مالی '.Html::encode($user->first_name.' '.$user->last_name),
                            'body' => $body,
                        );
                        return json_encode($response);
                    }
                }
                else
                    return  false;
            }
            else
                return false;
        }
        else
            return false;
    }

    public function actionRegister_course_scores()
    {
       if(Yii::$app->request->isPost)
       {
           $score = Scores::find()->where(['course_id' => Yii::$app->request->post('course_id')])->andWhere(['user_id' => Yii::$app->request->post('user_id')])->one();
           if($score != null)
           {
               $score->load(Yii::$app->request->post());
               if($score->save())
                   Yii::$app->session->setFlash('status','24');
               else
                   Yii::$app->session->setFlash('status','2');
           }
           else
           {
               $model = new Scores();
               $model->load(Yii::$app->request->post());
               $model->course_id = Yii::$app->request->post('course_id');
               $model->user_id = Yii::$app->request->post('user_id');
               if($model->save())
                   Yii::$app->session->setFlash('status','24');
               else
                   Yii::$app->session->setFlash('status','2');
           }
       }
       return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionOther_teachers()
    {
       if(Yii::$app->request->isPost)
       {
           $course = Courses::findOne(Yii::$app->request->post('_id'));
           if($course != null)
           {
               $preTeachers = $course->other_teachers;
               $teacherArray = array();
               if($preTeachers != null)
                   foreach ($preTeachers as $item)
                   {
                       $teacher = Teachers::findOne($item);
                       if($teacher != null)
                       {
                           $admin = Admin::find()->where(['username' => $teacher->mobile])->one();
                           if($admin != null)
                            array_push($teacherArray, (string) $admin->_id);
                       }
                   }
               $course->load(Yii::$app->request->post());
               if($course->save())
               {
                   //Begin Call AdobeConnect For Edit Teacher List
                   $adminRole = Admin::find()->where(['role' => 'user'])->one();
                   if($adminRole != null && ($course->content_type == '1' || $course->content_type == '2'))
                   {

                       $curl = curl_init();

                       curl_setopt_array($curl, array(
                           CURLOPT_URL => Yii::getAlias('@baseUrl').'/adobe-connect/add-other-teachers',
                           CURLOPT_RETURNTRANSFER => true,
                           CURLOPT_ENCODING => '',
                           CURLOPT_MAXREDIRS => 10,
                           CURLOPT_TIMEOUT => 0,
                           CURLOPT_FOLLOWLOCATION => true,
                           CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                           CURLOPT_CUSTOMREQUEST => 'POST',
                           CURLOPT_POSTFIELDS =>'{
    "course_id": "'.(string) $course->_id.'",
    "old_teachers": '.json_encode($teacherArray).'
}',
                           CURLOPT_HTTPHEADER => array(
                               '_id: '.(string) $adminRole->_id,
                               'Content-Type: application/json'
                           ),
                       ));

                       $response = curl_exec($curl);

                   }
                   //Begin Call AdobeConnect For Edit Teacher List
                   $response = json_decode($response);
                   curl_close($curl);
                   if(property_exists($response,'status'))
                   {
                       if($response->status == 'ok')
                           Yii::$app->session->setFlash('status','25');
                       else
                       {
                           $course->other_teachers = $preTeachers;
                           $course->save();
                           Yii::$app->session->setFlash('status','2');
                       }
                   }
                   else
                       Yii::$app->session->setFlash('status','2');
               }
               else
                   Yii::$app->session->setFlash('status','2');
           }
       }
       return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * ساخت مجدد کلاس آنلاین دوره (وقتی ساخت اولیه ناموفق بوده) از طریق سرور انتخاب‌شده (Adobe/BBB).
     */
    public function actionRegisterOnline()
    {
        $course = $this->findPackage((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canManage($course))
            return $this->listBack('error', 'دوره یافت نشد یا به آن دسترسی ندارید');
        if (!ClassroomPlatforms::hasOnlineClass($course))
            return $this->listBack('warning', 'این دوره کلاس آنلاین ندارد');
        $platform = ClassroomPlatforms::forCourse($course);
        $result = $platform->createCourseMeetings((string) $course->_id);
        $course->adobe_status = $result === true ? '1' : '0';
        $course->save(false, ['adobe_status']);
        if ($result === true)
            return $this->listBack('success', 'کلاس آنلاین دوره در ' . $platform->title() . ' ساخته شد');
        return $this->listBack('error', $result === null ? 'ارتباط با سرور ' . $platform->title() . ' برقرار نشد' : 'ساخت کلاس آنلاین ناموفق بود');
    }

    /**
     * نمایش/مخفی کردن دوره‌ی فعال در سایت اصلی.
     */
    public function actionToggleSite()
    {
        $course = $this->findPackage((string) Yii::$app->request->post('_id'));
        if ($course === null || !CourseAccess::canManage($course))
            return $this->listBack('error', 'دوره یافت نشد یا به آن دسترسی ندارید');
        if ((string) $course->status !== CourseStatus::ACTIVE)
            return $this->listBack('warning', 'فقط دوره‌ی فعال در سایت نمایش داده می‌شود');
        $course->show_in_site = $course->show_in_site === false;
        if ($course->save(false, ['show_in_site']))
            return $this->listBack('success', $course->show_in_site ? 'دوره در سایت نمایش داده می‌شود' : 'دوره از سایت مخفی شد');
        return $this->listBack('error', 'خطا در ذخیره‌سازی');
    }

    public function actionAdd_discount()
    {
        if(Yii::$app->request->isPost)
        {
            $courseDetail = Courses::findOne(Yii::$app->request->post()['Discounts']['course_id']);
            if($courseDetail != null)
            {
                $model = new Discounts();
                $model->load(Yii::$app->request->post());
                $model->code = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $model->used = null;
                $model->registrant = Yii::$app->user->identity->username;
                // تغییر ۹/۱۰ (2026-08-28): مبلغ و تعداد از طریق Discounts::rules()
                // الزامی و فقط-عدد-انگلیسی هستن، و مبلغ نباید از سهم کارگزار
                // (با همون فرمول موجود پروژه) بیشتر باشه. اگه رد بشه، پیغام
                // دقیق خطا (نه فقط «خطایی رخ داده») به کاربر نشون داده می‌شه.
                if($model->save())
                    Yii::$app->session->setFlash('status','26');
                else
                {
                    Yii::$app->session->setFlash('status','34');
                    Yii::$app->session->setFlash('discountError', $this->firstModelErrorMessage($model));
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDelete_discount()
    {
        if(Yii::$app->request->isPost)
        {
            $discountDetail = Discounts::findOne(Yii::$app->request->post()['Discounts']['_id']);
            if($discountDetail != null)
            {
                if($discountDetail->used == null)
                {
                    if($discountDetail->delete())
                        Yii::$app->session->setFlash('status','27');
                    else
                        Yii::$app->session->setFlash('status','2');
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCourses_financial()
    {
        if(Yii::$app->request->isPost)
        {
            $model = new CoursesFinancial();
            $model->load(Yii::$app->request->post());
            $model->registrant = Yii::$app->user->identity->username;
            $amount = Yii::$app->request->post()['CoursesFinancial']['amount'] * 10;
            $courseDetail = Courses::findOne(Yii::$app->request->post()['CoursesFinancial']['course_id']);
            if($courseDetail != null)
            {
                $collegeDetail = Colleges::findOne($courseDetail->college);
                if($collegeDetail != null)
                {
                    if($collegeDetail->financial_info != null)
                    {
                        if(array_key_exists('id', $collegeDetail->financial_info))
                        {
                            $curl = curl_init();

                            curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://api-eec.ut.ac.ir/external-api/gateway-token',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS =>'{
                "mobile": "",
                "amount": "'. $amount.'",
                "id": "'. $collegeDetail->financial_info['id'].'",
                "api_key": "'. \app\components\PaymentConfig::apiKey() .'",
                "callback": "https://eec1.ut.ac.ir/packages/courses_financial_callback"
}',
                                CURLOPT_HTTPHEADER => array(
                                    'Content-Type: application/json'
                                ),
                            ));

                            $response = curl_exec($curl);
                            $response = json_decode($response);
                            curl_close($curl);
                            if($response != null)
                            {
                                if (property_exists($response->data, 'gateway_token'))
                                {
                                    $paymentInfo = new stdClass();
                                    $paymentInfo->status = '1';
                                    $paymentInfo->date = jdate('Y/m/d');
                                    $paymentInfo->gateway_token = $response->data->gateway_token;
                                    $paymentInfo->order_id = $response->data->order_id;
                                    $model->payment_info = $paymentInfo;
                                    $model->college = $courseDetail->college;
                                    if ($_FILES['CoursesFinancial']['name']['file'] != '')
                                    {
                                        $file = UploadedFile::getInstance($model, 'file');
                                        $file_ext = $file->extension;
                                        $file_name = uniqid() . '.' . $file_ext;
                                        $file->saveAs('../../frontend/web/upload_center/' . $file_name);
                                        if (UploadedFile::getInstance($model, 'file') != null)
                                            $model->file = $file_name;
                                    }
                                    if($model->save())
                                    {
                                        $data = ['autoSubmit' => true, 'formAction' => 'test', 'formData' => ['RefId' => $response->data->gateway_token]];
                                        return $this->render('auto_submit_form', $data);
                                    }
                                    else
                                        Yii::$app->session->setFlash('status','2');
                                }
                                else
                                    Yii::$app->session->setFlash('status','28');
                            }
                        }
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCourses_financial_callback()
    {
       if(isset($_GET['order_id']))
       {
           $model = CoursesFinancial::find()->where(['payment_info.order_id' => Yii::$app->request->get('order_id')])->one();
           if($model != null)
           {
               $course = Courses::findOne($model->course_id);
               if($course != null)
               {
                   if($model->payment_info['status'] == '1')
                   {
                       $paymentInfo = $model->payment_info;
                       if (Yii::$app->request->get('status') == 'success') {
                           $paymentInfo['status'] = '2';
                           $paymentInfo['tref'] = Yii::$app->request->get('tref');
                           $model->payment_info = $paymentInfo;
                           $model->save();
                           Yii::$app->session->setFlash('status', '29');
                       } else {
                           $paymentInfo['status'] = '3';
                           $model->payment_info = $paymentInfo;
                           $model->save();
                           Yii::$app->session->setFlash('status', '30');
                       }
                       if($course->type == '1' || $course->type == '3')
                           return $this->redirect(['packages/edit-package', '_id' => (string)$course->_id, 'tab' => 'tab-id6', 'subtab' => 'navs-pills-left-messages']);
                       else
                           return $this->redirect(['courses/edit-course', '_id' => (string)$course->_id, 'tab' => 'tab-id6', 'subtab' => 'navs-pills-left-messages']);
                   }
               }
           }
       }
        return $this->redirect(['../dashboard']);
    }

    public function actionAdd_credit_request()
    {
        if(Yii::$app->request->isPost)
        {
            $courseDetail = Courses::findOne(Yii::$app->request->post()['OrganizationPayments']['product_id']);
            if($courseDetail != null)
            {
                $model = new OrganizationPayments();
                $model->load(Yii::$app->request->post());
                $model->registrant = Yii::$app->user->identity->username;
                $model->status = '0';
                $amount = Yii::$app->request->post()['OrganizationPayments']['number'] * $courseDetail->discount_price * 10;
                $model->amount = $amount;
                $model->number = Yii::$app->request->post()['OrganizationPayments']['number'];
                if($model->save())
                    Yii::$app->session->setFlash('status','31');
                else
                    Yii::$app->session->setFlash('status','2');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionPay_add_credit()
    {
        if(Yii::$app->request->isPost)
        {
            $requestDetail = OrganizationPayments::findOne(Yii::$app->request->post()['_id']);
            if($requestDetail != null)
            {
                $courseDetail = Courses::findOne($requestDetail->product_id);
                if($courseDetail != null)
                {
                    $collegeDetail = Colleges::findOne($courseDetail->college);
                    if($collegeDetail != null)
                    {
                        if($collegeDetail->financial_info != null)
                        {
                            if(array_key_exists('id', $collegeDetail->financial_info))
                            {
                                $curl = curl_init();

                                curl_setopt_array($curl, array(
                                    CURLOPT_URL => 'https://api-eec.ut.ac.ir/external-api/gateway-token',
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_POSTFIELDS =>'{
                "mobile": "",
                "amount": "'. $requestDetail->amount.'",
                "id": "'. $collegeDetail->financial_info['id'].'",
                "api_key": "'. \app\components\PaymentConfig::apiKey() .'",
                "callback": "https://eec1.ut.ac.ir/packages/add_credit_callback"
}',
                                    CURLOPT_HTTPHEADER => array(
                                        'Content-Type: application/json'
                                    ),
                                ));

                                $response = curl_exec($curl);
                                $response = json_decode($response);
                                curl_close($curl);
                                if($response != null)
                                {
                                    if (property_exists($response->data, 'gateway_token'))
                                    {
                                        $requestDetail->status = '0';
                                        $requestDetail->date = jdate('Y/m/d');
                                        $requestDetail->gateway_token = $response->data->gateway_token;
                                        $requestDetail->order_id = $response->data->order_id;
                                        if($requestDetail->save())
                                        {
                                            $data = ['autoSubmit' => true, 'formAction' => 'test', 'formData' => ['RefId' => $response->data->gateway_token]];
                                            return $this->render('auto_submit_form', $data);
                                        }
                                        else
                                            Yii::$app->session->setFlash('status','2');
                                    }
                                    else
                                        Yii::$app->session->setFlash('status','28');
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAdd_credit_callback()
    {
        if(isset($_GET['order_id']))
        {
            $model = OrganizationPayments::find()->where(['order_id' => Yii::$app->request->get('order_id')])->one();
            if($model != null)
            {
                $course = Courses::findOne($model->product_id);
                if($course != null)
                {
                    if($model->status == '0')
                    {
                        if (Yii::$app->request->get('status') == 'success')
                        {
                            $model->status = '1';
                            $model->tref = Yii::$app->request->get('tref');
                            $model->save();
                            $courseCredit = 0;
                            if($course->credit != null)
                                $courseCredit = $courseCredit + $model->number;
                            else
                                $courseCredit = $model->number;
                            $course->credit = (string) $courseCredit;
                            $course->save();
                            Yii::$app->session->setFlash('status', '29');
                        }
                        else
                        {
                            Yii::$app->session->setFlash('status', '30');
                        }
                        if($course->type != '1')
                            return $this->redirect(['packages/edit-package', '_id' => (string)$course->_id, 'tab' => 'tab-id6']);
                        else
                            return $this->redirect(['courses/edit-course', '_id' => (string)$course->_id, 'tab' => 'tab-id6']);
                    }
                }
            }
        }
        return $this->redirect(['../dashboard']);
    }

    public function member_detail($username)
    {
        return Users::find()->where(['username' => $username])->one();
    }

    public function score($course, $user)
    {
        return Scores::find()->where(['course_id' => $course])->andWhere(['user_id' => $user])->one();
    }

    public function score_status($courseId, $userId)
    {
        $score = Scores::find()->where(['course_id' => $courseId])->andWhere(['user_id' => $userId])->one();
        $flag = true;
        if($score != null)
            if($score->scores != null)
                foreach ($score->scores as $item)
                    if($item['score'] == null || $item['score'] == '' || !is_numeric($item['score']))
                        $flag = false;
        if($flag)
            return '
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path opacity="0.5" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" fill="green"/>
<path d="M16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="green"/>
</svg>

            ';
        else
            return '
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path opacity="0.5" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" fill="red"/>
<path d="M8.96967 8.96967C9.26256 8.67678 9.73744 8.67678 10.0303 8.96967L12 10.9394L13.9697 8.96969C14.2626 8.6768 14.7374 8.6768 15.0303 8.96969C15.3232 9.26258 15.3232 9.73746 15.0303 10.0304L13.0607 12L15.0303 13.9696C15.3232 14.2625 15.3232 14.7374 15.0303 15.0303C14.7374 15.3232 14.2625 15.3232 13.9696 15.0303L12 13.0607L10.0304 15.0303C9.73746 15.3232 9.26258 15.3232 8.96969 15.0303C8.6768 14.7374 8.6768 14.2626 8.96969 13.9697L10.9394 12L8.96967 10.0303C8.67678 9.73744 8.67678 9.26256 8.96967 8.96967Z" fill="red"/>
</svg>

            ';
    }

    public function allow($college)
    {
        $allow = false;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $allow = true;
        else
        {
            if(is_array(Yii::$app->user->identity->college))
            {
                if(array_search($college, Yii::$app->user->identity->college) !== false)
                    $allow = true;
            }
            else if($college == Yii::$app->user->identity->college)
                $allow = true;
        }
        return $allow;
    }

    public function allow_edit($college, $status)
    {
        $allow = false;
        if(Yii::$app->user->identity->role == 'user' || Yii::$app->user->identity->role == 'cnt')
            $allow = true;
        else
        {
            if(is_array(Yii::$app->user->identity->college))
            {
                if(array_search($college, Yii::$app->user->identity->college) !== false)
                {
                    if($status != '1')
                        $allow = true;
                    else
                        $allow = false;
                }
            }
            else if($college == Yii::$app->user->identity->college)
            {
                if($status != '1')
                    $allow = true;
                else
                    $allow = false;
            }
        }
        return $allow;
    }

    public function check_date($startDateJalali, $endDateJalali)
    {
        $allowDeadlineDate = true;
        $startDateGregorian = $this->jalaliToGregorian($startDateJalali);
        $endDateGregorian = $this->jalaliToGregorian($endDateJalali);
        // محاسبه فاصله بین دو تاریخ به ثانیه
        $startTimestamp = strtotime($startDateGregorian);
        $endTimestamp = strtotime($endDateGregorian);
        $intervalSeconds = $endTimestamp - $startTimestamp;

// محاسبه یک‌چهارم فاصله (به ثانیه)
        $quarterIntervalSeconds = $intervalSeconds / 4;

// تاریخ امروز به شمسی
        $todayJalali = jdate('Y/m/d'); // با استفاده از jdf
        $todayGregorian = $this->jalaliToGregorian($todayJalali);
        $todayTimestamp = strtotime($todayGregorian);
        // تاریخ مهلت: تاریخ شروع + یک‌چهارم فاصله
        $deadlineTimestamp = $startTimestamp + $quarterIntervalSeconds;

// بررسی: اگر امروز از تاریخ مهلت بزرگتر باشد
        $allowDeadlineDate = ($todayTimestamp <= $deadlineTimestamp);
        $ret = array(
            'allowDeadlineDate' => $allowDeadlineDate,
            'deadlineTimestamp' => $deadlineTimestamp
        );
        return json_encode($ret);
    }
    function jalaliToGregorian($jalaliDate) {
        // جدا کردن و بررسی اولیه
        $parts = explode('-', $jalaliDate);

        if (count($parts) !== 3) {
            // اگر با - جدا نشد، با / امتحان کن
            $parts = explode('/', $jalaliDate);
            if (count($parts) !== 3) {
                die('فرمت تاریخ اشتباه است. باید Y-m-d یا Y/m/d باشد');
            }
        }

        list($jY, $jM, $jD) = $parts;

        // تبدیل
        $gregorian = jalali_to_gregorian($jY, $jM, $jD);
        return implode('-', $gregorian) . ' 00:00:00';
    }


}
