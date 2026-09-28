<?php

namespace app\models;

use Yii;

/**
 * This is the model class for collection "Courses".
 *
 * @property \MongoDB\BSON\ObjectID|string $_id
 */
class Courses extends \yii\mongodb\ActiveRecord
{
    /**
     * Scenario used by the "create-package" form (frontend/views/packages/create-package.php)
     * so the stricter required-field rules below only apply there and never affect
     * edit-package.php or any other place that uses this model in the default scenario.
     */
    const SCENARIO_CREATE_PACKAGE = 'create-package';

    /**
     * Scenario used by the "edit-package" form (frontend/views/packages/edit-package.php),
     * set in PackagesController::actionEdit(). Lets the rules below (price/discount
     * format, capacity-number requirement, and the legacy-safe content_type/capacity
     * guards) apply on edit exactly the same way they apply on create, without
     * touching any other flow that uses this model in the default scenario
     * (copy-package's initial save, admin financial adjustments, etc.).
     */
    const SCENARIO_EDIT_PACKAGE = 'edit-package';

    /**
     * Scenario used by the short-term "single course" create form (Modal inside
     * frontend/views/courses/index.php), set in CoursesController::actionNew().
     * Added 2026-08-28 while porting the same category of validation improvements
     * made for packages to short-term courses (type=1) - kept in its own scenario,
     * distinct from SCENARIO_CREATE_PACKAGE, because the short-term form has no
     * installments and its date lives at lessons[0][date] instead of the
     * top-level date[from]/date[to] (see validateRequiredLesson() below).
     */
    const SCENARIO_CREATE_COURSE = 'create-course';

    /**
     * Scenario used by the short-term "single course" edit form
     * (frontend/views/courses/edit-course.php), set in CoursesController::actionEdit().
     * See SCENARIO_CREATE_COURSE docblock above for why this is a separate scenario
     * from the package one.
     */
    const SCENARIO_EDIT_COURSE = 'edit-course';

    /**
     * "محتوا محور" - being phased out as a selectable content_type for NEW
     * assignments (2026-08-27). Existing courses that already have this value
     * keep working normally; see validateContentTypeNotDisabled().
     */
    const CONTENT_TYPE_CONTENT_BASED = '3';

    /**
     * "نامحدود" - being phased out as a selectable student_capacity[type] for
     * NEW assignments (2026-08-27). Existing courses that already have this
     * value keep working normally; see validateCapacityTypeNotDisabled().
     */
    const CAPACITY_TYPE_UNLIMITED = '1';

    /** "محدود" - when selected, student_capacity[number] becomes required. */
    const CAPACITY_TYPE_LIMITED = '2';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'normalizePrices']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'normalizePrices']);
    }

    /**
     * {@inheritdoc}
     */
    public static function collectionName()
    {
        return ['eec', 'courses'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributes()
    {
        return [
            '_id',
            'title', // main_fa, main_en, degree_fa, degree_en
            'price',
            'discount_price',
            'college',
            'broker', // _id, contract (_id)
            'licence_code',
            'student_capacity', // type, number
            'type', // 1 = Single Course, 2 = Comprehensive Course , 3 = Year Period Course
            'content_type', // 1 = Online , 2 = Offline , 3 = Content Base
            'status', // 0 = InActive, 1 = Active , 2 = Draft , 3 = Need To Edit, 4 = Refused
            'rejection_reason',
            'preview_image',
            'date',
            'registration_deadline',
            'description',
            'full_description',
            'lessons', // _id, teacher, date(from, to, time)
            'registrant',
            'duration',
            'my_lessons',
            'license_code',
            'installments',
            'prepayment_installments',
            'mentors',
            'from_excel',
            'pre_id',
            'other_teachers',
            'print_preview',
            'from_pec',
            'adobe_status',
            'show_in_site',
            'serving', //if true = Zemn Khedmat
            'modified',
            'place',
            'time',
            'contract_file',
            'credit',
            'allow_free_add_user',
            'deadline_date',
            'classroom_server', // شناسه‌ی سرور کلاس (ClassroomServers) یا 'none'؛ خالی = دوره‌ی قدیمی، سرور پیش‌فرض
            'classroom_meetings', // جلسه‌های BBB هر درس: [lessonId => meeting_id, attendee_pw, moderator_pw, server]
            'digital_cert', // true = صدور گواهی دیجیتال برای این دوره فعال است
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['contract_file', 'required',
                'when' => function($model) {
                    if (!$model->isNewRecord) {
                        return false;
                    }
                    if (is_object($model->student_capacity)) {
                        return isset($model->student_capacity->type) && $model->student_capacity->type === '3';
                    } elseif (is_array($model->student_capacity)) {
                        return isset($model->student_capacity['type']) && $model->student_capacity['type'] === '3';
                    }
                    return false;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#courses-student_capacity-type').val() === '3';
                }",
                'message' => 'وارد کردن فایل قرارداد الزامی می باشد'
            ],
            // --- Fields required on the "create-package" (ثبت دوره جدید) form only ---
            // Kept in their own scenario so edit-package.php and every other flow that
            // uses this model keep behaving exactly as before.
            ['price', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا قیمت اصلی دوره را وارد کنید'],
            ['time', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا زمان برگزاری دوره را وارد کنید'],
            ['place', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا محل برگزاری دوره را وارد کنید'],
            ['content_type', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا نوع دوره را مشخص کنید'],
            ['duration', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا مدت زمان دوره را به درستی وارد کنید'],
            ['college', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا دانشکده را مشخص کنید'],
            ['student_capacity', 'required', 'on' => self::SCENARIO_CREATE_PACKAGE, 'message' => 'لطفا نوع ظرفیت دوره را مشخص کنید'],
            [['title'], 'validateRequiredTitles', 'on' => self::SCENARIO_CREATE_PACKAGE],
            [['date'], 'validateRequiredDates', 'on' => self::SCENARIO_CREATE_PACKAGE],

            // --- Fields validated on BOTH "create-package" and "edit-package" ---
            // (PackagesController::actionEdit() sets SCENARIO_EDIT_PACKAGE the same
            // way actionNew() already sets SCENARIO_CREATE_PACKAGE.) Every other flow
            // that uses this model (copy-package's initial save, admin financial
            // pages, discounts, etc.) stays in the default scenario and is completely
            // unaffected by these rules.
            [['price', 'discount_price'], 'match', 'pattern' => '/^[0-9]+$/',
                'message' => 'فقط اعداد انگلیسی مجاز است (بدون حروف، ممیز، یا اعداد فارسی/عربی)',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE]],
            ['discount_price', 'validateDiscountNotAbovePrice',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE]],
            ['student_capacity', 'validateCapacityNumberWhenLimited',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE]],
            // اصلاح ۲۰۲۶-۰۸-۲۸ (طبق درخواست صریح کاربر: «فیلد نوع دوره و ظرفیت دوره
            // دقیق با همون شکل و قوانینی که توی دوره‌های میان‌مدت انجام دادی بزن»):
            // این دو قانون قبلاً عمداً فقط برای create-package/edit-package بودن
            // (نگاه کنید توضیح پایین‌تر، نزدیک SCENARIO_CREATE_COURSE/EDIT_COURSE)
            // چون تصمیم بود «محتوا محور»/«نامحدود» برای دوره‌های کوتاه‌مدت هنوز کار
            // کنه؛ حالا که کاربر صراحتاً خواسته قوانین یکسان باشه، همون منع
            // انتخاب‌مجدد این دو مقدار منسوخ - بدون خراب کردن رکوردهای موجود - برای
            // دوره‌های کوتاه‌مدت هم اعمال می‌شه.
            ['content_type', 'validateContentTypeNotDisabled',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE, self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            ['student_capacity', 'validateCapacityTypeNotDisabled',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE, self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            ['installments', 'validateInstallmentsAgainstCourse',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE]],

            // --- Fields required on BOTH the short-term "create-course" (Modal
            // inside courses/index.php) and "edit-course" (courses/edit-course.php)
            // forms. Unlike packages (whose SCENARIO_EDIT_PACKAGE currently does
            // NOT re-require these), the short-term spec (2026-08-28) explicitly
            // asks for Yii2 validation on BOTH Create and Edit, so both scenarios
            // are listed here from the start. Every field below is already marked
            // `required` with a matching oninvalid/oninput Persian message in both
            // courses/index.php and edit-course.php, so a real submission through
            // either page already satisfies these - this is a server-side safety
            // net (section 23 of the spec: "UI restriction ≠ Security"), not a
            // behavior change for legitimate use.
            ['price', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا قیمت اصلی دوره را وارد کنید'],
            ['time', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا زمان برگزاری دوره را وارد کنید'],
            ['place', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا محل برگزاری دوره را وارد کنید'],
            ['content_type', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا نوع دوره را مشخص کنید'],
            ['duration', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا مدت زمان دوره را به درستی وارد کنید'],
            ['college', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا دانشکده را مشخص کنید'],
            ['student_capacity', 'required', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE], 'message' => 'لطفا نوع ظرفیت دوره را مشخص کنید'],
            // Reused verbatim from the package scenario - title[main_fa]/[degree_fa]
            // are required identically in both forms; see validateRequiredTitles().
            [['title'], 'validateRequiredTitles', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            // Short-term-specific: exactly one lesson, with its own required
            // sub-fields (_id, teachers, hide_archive, date[from], date[to]) -
            // NOT the same as validateRequiredDates() above, because short-term
            // dates live at lessons[0][date], never at the top-level date[from]/to
            // (see project spec section 2/16). Also independently enforces the
            // "exactly one lesson" invariant (spec section 25) against a
            // manipulated POST, regardless of what the UI ever sends.
            [['lessons'], 'validateRequiredLesson', 'on' => [self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            // Reused verbatim from the package scenario - same digit-only /
            // discount-not-above-price / capacity-number rules apply identically
            // to short-term courses; none of these three touch installments
            // (short-term has no installments - out of scope by spec).
            // اصلاح ۲۰۲۶-۰۸-۲۸: قبلاً اینجا نوشته شده بود که
            // validateContentTypeNotDisabled/validateCapacityTypeNotDisabled عمداً
            // به این دو سناریو اضافه نشدن (چون قرار بود «محتوا محور»/«نامحدود»
            // برای دوره‌های کوتاه‌مدت هنوز قابل‌انتخاب بمونه). طبق درخواست صریح
            // بعدی کاربر («دقیق با همون قوانین دوره‌های میان‌مدت»)، این تصمیم
            // برعکس شد - این دو قانون حالا بالاتر (نزدیک SCENARIO_CREATE_PACKAGE)
            // برای هر ۴ سناریو مشترکاً تعریف شدن.
            [['price', 'discount_price'], 'match', 'pattern' => '/^[0-9]+$/',
                'message' => 'فقط اعداد انگلیسی مجاز است (بدون حروف، ممیز، یا اعداد فارسی/عربی)',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE, self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            ['discount_price', 'validateDiscountNotAbovePrice',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE, self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],
            ['student_capacity', 'validateCapacityNumberWhenLimited',
                'on' => [self::SCENARIO_CREATE_PACKAGE, self::SCENARIO_EDIT_PACKAGE, self::SCENARIO_CREATE_COURSE, self::SCENARIO_EDIT_COURSE]],

            [[
                'title',
                'price',
                'discount_price',
                'college',
                'broker',
                'licence_code',
                'student_capacity',
                'type',
                'content_type',
                'status',
                'rejection_reason',
                'date',
                'registration_deadline',
                'description',
                'full_description',
                'lessons',
                'registrant',
                'duration',
                'my_lessons',
                'license_code',
                'installments',
                'prepayment_installments',
                'mentors',
                'other_teachers',
                'print_preview',
                'adobe_status',
                'show_in_site',
                'serving',
                'modified',
                'place',
                'time',
                'contract_file',
                'credit',
                'allow_free_add_user',
                'deadline_date',
            ], 'safe'],
            ['allow_free_add_user', 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            '_id' => 'ID',
            'price' => 'قیمت اصلی',
            'discount_price' => 'قیمت با تخفیف',
            'time' => 'زمان برگزاری',
            'place' => 'محل برگزاری',
            'content_type' => 'نوع دوره',
            'duration' => 'مدت زمان دوره',
            'college' => 'دانشکده',
            'date' => 'تاریخ برگزاری',
            'student_capacity' => 'نوع ظرفیت',
            'contract_file' => 'فایل قرارداد',
        ];
    }

    /**
     * Inline validator (used only in SCENARIO_CREATE_PACKAGE) that checks the two
     * required Persian sub-fields of the nested "title" attribute:
     * title[main_fa] and title[degree_fa]. title[main_en] / title[degree_en] stay
     * optional, exactly as before.
     *
     * This runs server-side only. Yii2 can't generate real per-sub-field
     * client-side (JS) validation for a bracket-notation attribute like
     * "title[main_fa]" - it always resolves the field back to the base
     * attribute "title" before asking the validator for JS, so a single rule
     * on "title" cannot tell main_fa/degree_fa (required) apart from
     * main_en/degree_en (optional) in the browser. The create-package.php
     * view keeps a plain HTML "required" on just those two Persian inputs as
     * the in-browser safety net; this method is the real (tested) source of
     * truth on submit.
     */
    public function validateRequiredTitles($attribute, $params)
    {
        $required = [
            'main_fa' => 'لطفا عنوان اصلی فارسی را وارد کنید',
            'degree_fa' => 'لطفا عنوان فارسی (داخل مدرک) را وارد کنید',
        ];
        $title = $this->$attribute;
        foreach ($required as $key => $message) {
            $value = null;
            if (is_array($title) && isset($title[$key])) {
                $value = $title[$key];
            } elseif (is_object($title) && isset($title->$key)) {
                $value = $title->$key;
            }
            if ($value === null || trim((string) $value) === '') {
                $this->addError("{$attribute}[{$key}]", $message);
            }
        }
    }

    /**
     * Inline validator (used only in SCENARIO_CREATE_PACKAGE) that checks the two
     * required sub-fields of the nested "date" attribute: date[from] and date[to].
     * A plain ['date', 'required'] rule can't catch this server-side because
     * $model->date is still a non-empty array (e.g. ['from' => '', 'to' => ''])
     * even when both sub-fields are blank. Same client-side limitation as
     * validateRequiredTitles() above - see that method's docblock.
     */
    public function validateRequiredDates($attribute, $params)
    {
        $required = [
            'from' => 'لطفا تاریخ شروع دوره را وارد کنید',
            'to' => 'لطفا تاریخ اتمام دوره را وارد کنید',
        ];
        $date = $this->$attribute;
        foreach ($required as $key => $message) {
            $value = null;
            if (is_array($date) && isset($date[$key])) {
                $value = $date[$key];
            } elseif (is_object($date) && isset($date->$key)) {
                $value = $date->$key;
            }
            if ($value === null || trim((string) $value) === '') {
                $this->addError("{$attribute}[{$key}]", $message);
            }
        }
    }

    /**
     * Inline validator (SCENARIO_CREATE_COURSE + SCENARIO_EDIT_COURSE) for
     * short-term (type=1) courses. A short-term course always has EXACTLY ONE
     * lesson (project spec section 2/25) and its date lives at
     * lessons[0][date][from]/[to] - never at the top-level date[from]/[to]
     * (see validateRequiredDates() above, which is package-only and must not be
     * reused here). This checks, server-side, independent of whatever the UI
     * did or didn't disable:
     *   - lessons is a non-empty array with exactly one entry (rejects both an
     *     empty submission and a manipulated POST trying to add a second lesson);
     *   - lessons[0][_id] (the selected Lesson), [teachers] (the selected
     *     Teacher) and [hide_archive] are all present;
     *   - lessons[0][date][from]/[to] are both present.
     * Every one of these sub-fields is already marked `required` with a Persian
     * oninvalid message on both courses/index.php and edit-course.php, so this
     * is a safety net for a real submission, not a new restriction on the form.
     */
    public function validateRequiredLesson($attribute, $params)
    {
        $lessons = $this->$attribute;
        if (!is_array($lessons) || count($lessons) < 1) {
            $this->addError($attribute, 'لطفا درس دوره را انتخاب کنید');
            return;
        }
        if (count($lessons) > 1) {
            $this->addError($attribute, 'دوره کوتاه‌مدت فقط می‌تواند دقیقاً یک درس داشته باشد');
            return;
        }
        $lesson = $lessons[0];
        $get = function ($key) use ($lesson) {
            if (is_array($lesson)) {
                return $lesson[$key] ?? null;
            }
            if (is_object($lesson)) {
                return $lesson->$key ?? null;
            }
            return null;
        };

        $lessonId = $get('_id');
        if ($lessonId === null || trim((string) $lessonId) === '') {
            $this->addError("{$attribute}[0][_id]", 'لطفا درس را انتخاب کنید');
        }

        $teacher = $get('teachers');
        if ($teacher === null || trim((string) $teacher) === '') {
            $this->addError("{$attribute}[0][teachers]", 'لطفا مدرس را انتخاب کنید');
        }

        $hideArchive = $get('hide_archive');
        if ($hideArchive === null || $hideArchive === '') {
            $this->addError("{$attribute}[0][hide_archive]", 'لطفا وضعیت مخفی کردن آرشیو را مشخص کنید');
        }

        $date = $get('date');
        $dateFrom = null;
        $dateTo = null;
        if (is_array($date)) {
            $dateFrom = $date['from'] ?? null;
            $dateTo = $date['to'] ?? null;
        } elseif (is_object($date)) {
            $dateFrom = $date->from ?? null;
            $dateTo = $date->to ?? null;
        }
        if ($dateFrom === null || trim((string) $dateFrom) === '') {
            $this->addError("{$attribute}[0][date][from]", 'لطفا تاریخ شروع دوره را وارد کنید');
        }
        if ($dateTo === null || trim((string) $dateTo) === '') {
            $this->addError("{$attribute}[0][date][to]", 'لطفا تاریخ اتمام دوره را وارد کنید');
        }
    }

    /**
     * Inline validator (SCENARIO_CREATE_PACKAGE + SCENARIO_EDIT_PACKAGE) enforcing
     * that discount_price never exceeds price. Equal values are allowed on purpose:
     * checking the real data (2026-08-27, 4000 existing courses) showed ~90% of
     * courses store "no discount" as discount_price == price rather than leaving
     * discount_price empty - that is the established convention on this project,
     * not a bug, so this rule must not reject it. Only genuinely-broken data
     * (discount_price strictly greater than price - about 15 old courses at the
     * time this was written) gets rejected. Skipped entirely when discount_price
     * is empty, since it's an optional field.
     */
    public function validateDiscountNotAbovePrice($attribute, $params)
    {
        $discount = $this->$attribute;
        if ($discount === null || trim((string) $discount) === '') {
            return;
        }
        if (!is_numeric($discount) || !is_numeric($this->price)) {
            // Already reported by the 'match' rule above; nothing more to add here.
            return;
        }
        if ((float) $discount > (float) $this->price) {
            $this->addError($attribute, 'قیمت با تخفیف نمی‌تواند بیشتر از قیمت اصلی باشد');
        }
    }

    /**
     * Inline validator (SCENARIO_CREATE_PACKAGE + SCENARIO_EDIT_PACKAGE) requiring
     * a precise, positive, English-digit-only student_capacity[number] whenever
     * student_capacity[type] is "محدود" (CAPACITY_TYPE_LIMITED). Safe against the
     * older document shapes still present in the database (student_capacity as a
     * bare string, or missing entirely) - those simply never have type === '2', so
     * this rule naturally doesn't apply to them.
     */
    public function validateCapacityNumberWhenLimited($attribute, $params)
    {
        $capacity = $this->$attribute;
        $type = null;
        $number = null;
        if (is_array($capacity)) {
            $type = $capacity['type'] ?? null;
            $number = $capacity['number'] ?? null;
        } elseif (is_object($capacity)) {
            $type = $capacity->type ?? null;
            $number = $capacity->number ?? null;
        }
        if ($type !== self::CAPACITY_TYPE_LIMITED) {
            return;
        }
        if ($number === null || trim((string) $number) === '') {
            $this->addError("{$attribute}[number]", 'لطفا ظرفیت را به صورت عددی وارد کنید');
            return;
        }
        if (!preg_match('/^[0-9]+$/', (string) $number) || (int) $number <= 0) {
            $this->addError("{$attribute}[number]", 'ظرفیت باید یک عدد صحیح و مثبت (اعداد انگلیسی) باشد');
        }
    }

    /**
     * Inline validator (SCENARIO_CREATE_PACKAGE + SCENARIO_EDIT_PACKAGE + - از
     * ۲۰۲۶-۰۸-۲۸ - SCENARIO_CREATE_COURSE + SCENARIO_EDIT_COURSE) که مانع
     * انتخاب/تغییرِ جدید content_type === CONTENT_TYPE_CONTENT_BASED ("محتوا
     * محور") می‌شه، در حالی که رکوردهایی که از قبل این مقدار رو داشتن دست
     * نمی‌خورن - ویرایش هر فیلد دیگه‌ی همچین دوره‌ای، یا حتی دست‌نخورده گذاشتن
     * content_type، هرگز این قانون رو فعال نمی‌کنه. getOldAttribute() مقدار
     * قبل از این درخواست رو برمی‌گردونه (یا null برای رکورد کاملاً جدید)، پس
     * فقط روی تلاش واقعی برای ست‌کردن/تغییر به مقدار غیرفعال‌شده فایر می‌شه.
     */
    public function validateContentTypeNotDisabled($attribute, $params)
    {
        if ($this->$attribute !== self::CONTENT_TYPE_CONTENT_BASED) {
            return;
        }
        if ($this->getOldAttribute($attribute) === self::CONTENT_TYPE_CONTENT_BASED) {
            return;
        }
        $this->addError($attribute, 'گزینه «محتوا محور» دیگر برای دوره‌های جدید قابل انتخاب نیست');
    }

    /**
     * Inline validator (SCENARIO_CREATE_PACKAGE + SCENARIO_EDIT_PACKAGE + - از
     * ۲۰۲۶-۰۸-۲۸ - SCENARIO_CREATE_COURSE + SCENARIO_EDIT_COURSE)، همون الگوی
     * legacy-safe validateContentTypeNotDisabled() بالا، ولی برای
     * student_capacity[type] === CAPACITY_TYPE_UNLIMITED ("نامحدود"). فقط
     * مقدار زیرِ "type" رو با مقدار قبلی‌اش مقایسه می‌کنه (نه کل آرایه/آبجکت
     * student_capacity)، پس ویرایش student_capacity[number] یا هر فیلد
     * نامرتبط دیگه هرگز این قانون رو فعال نمی‌کنه.
     */
    public function validateCapacityTypeNotDisabled($attribute, $params)
    {
        $capacity = $this->$attribute;
        $newType = null;
        if (is_array($capacity)) {
            $newType = $capacity['type'] ?? null;
        } elseif (is_object($capacity)) {
            $newType = $capacity->type ?? null;
        }
        if ($newType !== self::CAPACITY_TYPE_UNLIMITED) {
            return;
        }
        $oldCapacity = $this->getOldAttribute($attribute);
        $oldType = null;
        if (is_array($oldCapacity)) {
            $oldType = $oldCapacity['type'] ?? null;
        } elseif (is_object($oldCapacity)) {
            $oldType = $oldCapacity->type ?? null;
        }
        if ($oldType === self::CAPACITY_TYPE_UNLIMITED) {
            return;
        }
        $this->addError("{$attribute}[type]", 'گزینه «نامحدود» دیگر برای دوره‌های جدید قابل انتخاب نیست');
    }

    /**
     * Inline validator (SCENARIO_CREATE_PACKAGE + SCENARIO_EDIT_PACKAGE) for the
     * nested "installments" array (رویداد‌های تغییر ۵، ۶ و ۷ - 2026-08-28):
     *   - هر ردیفی که واقعاً پر شده باشه، هم "deadline" و هم "amount" لازم داره؛
     *   - "amount" فقط باید عدد انگلیسی باشه (دقیقاً همون قاعده‌ی price/discount_price بالا)؛
     *   - "deadline" نباید از تاریخ اتمام دوره (date[to]) بزرگتر باشه - مساوی مجازه،
     *     دقیقاً همون منطقی که از قبل به‌صورت دستی توی actionAdd_single_installment()
     *     با مقایسه‌ی رشته‌ای پیاده شده بود، اینجا دوباره استفاده شده؛
     *   - مجموع مبلغ همه‌ی ردیف‌ها نباید از «قیمت قابل پرداخت» دوره بیشتر بشه:
     *     یعنی discount_price وقتی با price فرق داره، وگرنه خود price - عیناً
     *     همون قراردادی که validateDiscountNotAbovePrice() بالا استفاده می‌کنه.
     *
     * Legacy-safe (خیلی مهم): این ولیدیتور فقط وقتی واقعاً فعال می‌شه که مقدار
     * "installments" نسبت به مقدار قبلی‌اش در دیتابیس تغییر کرده باشه (یعنی
     * دقیقاً همون لحظه‌ای که قسطی اضافه/ویرایش/حذف می‌شه). بررسی داده‌ی واقعی
     * (2026-08-28، ۴۰۰۰ دوره) نشون داد ۳۹ از ۳۴۱ دوره‌ی دارای قسط از قبل قسطی
     * بعد از تاریخ اتمام دوره دارن و ۲۰ تا هم مجموع اقساطشون از قیمت دوره
     * بیشتره؛ اگه این قانون بدون این چک روی «صرفاً ذخیره‌ی دوره» (که هیچ ربطی
     * به تغییر اقساط نداره - مثل actionEdit()) هم اجرا می‌شد، ذخیره‌ی این
     * دوره‌های قدیمی برای هر تغییر ساده‌ی دیگه‌ای هم قفل می‌شد. با این چک،
     * داده‌ی قدیمیِ دست‌نخورده هیچ‌وقت رد نمی‌شه؛ فقط قسط تازه/تغییریافته
     * اعتبارسنجی می‌شه - دقیقاً هدف تغییرات ۵، ۶ و ۷.
     */
    public function validateInstallmentsAgainstCourse($attribute, $params)
    {
        $installments = $this->$attribute;
        if (!is_array($installments) || empty($installments)) {
            return;
        }

        $oldInstallments = $this->getOldAttribute($attribute);
        if ($this->normalizeInstallmentsForCompare($installments) === $this->normalizeInstallmentsForCompare($oldInstallments)) {
            return;
        }

        $endDate = null;
        if (is_array($this->date) && isset($this->date['to']) && trim((string) $this->date['to']) !== '') {
            $endDate = str_replace('-', '', (string) $this->date['to']);
        }

        $sum = 0.0;
        foreach ($installments as $index => $row) {
            $deadline = is_array($row) ? ($row['deadline'] ?? null) : (is_object($row) ? ($row->deadline ?? null) : null);
            $amount = is_array($row) ? ($row['amount'] ?? null) : (is_object($row) ? ($row->amount ?? null) : null);

            $deadlineEmpty = ($deadline === null || trim((string) $deadline) === '');
            $amountEmpty = ($amount === null || trim((string) $amount) === '');

            // ردیف کاملاً خالی (نه تاریخ نه مبلغ) - مثلاً ردیف اولیه‌ی repeater
            // که کاربر هنوز پرش نکرده - نادیده گرفته می‌شه.
            if ($deadlineEmpty && $amountEmpty) {
                continue;
            }

            if ($deadlineEmpty) {
                $this->addError("{$attribute}[{$index}][deadline]", 'لطفا تاریخ پرداخت قسط را وارد کنید');
            }
            if ($amountEmpty) {
                $this->addError("{$attribute}[{$index}][amount]", 'لطفا مبلغ قسط را وارد کنید');
            } elseif (!preg_match('/^[0-9]+$/', (string) $amount)) {
                $this->addError("{$attribute}[{$index}][amount]", 'مبلغ قسط فقط باید عدد انگلیسی باشد (بدون حروف، ممیز، یا اعداد فارسی/عربی)');
            } else {
                $sum += (float) $amount;
            }

            if (!$deadlineEmpty && $endDate !== null) {
                $normalizedDeadline = str_replace('-', '', (string) $deadline);
                if ($normalizedDeadline > $endDate) {
                    $this->addError("{$attribute}[{$index}][deadline]", 'تاریخ پرداخت قسط نباید از تاریخ اتمام دوره بیشتر باشد');
                }
            }
        }

        if ($sum > 0) {
            $payablePrice = null;
            if (is_numeric($this->price)) {
                $payablePrice = (float) $this->price;
                if ($this->discount_price !== null && trim((string) $this->discount_price) !== '' && is_numeric($this->discount_price)) {
                    if ((float) $this->discount_price != (float) $this->price) {
                        $payablePrice = (float) $this->discount_price;
                    }
                }
            }
            if ($payablePrice !== null && $sum > $payablePrice) {
                $this->addError($attribute, 'مجموع مبلغ اقساط نمی‌تواند از قیمت قابل پرداخت دوره (' . number_format($payablePrice) . ' تومان) بیشتر باشد');
            }
        }
    }

    /**
     * Turns an "installments" value (array/object rows, possibly null) into a
     * comparable string, so validateInstallmentsAgainstCourse() can tell
     * whether it actually changed from what's already in the database.
     */
    private function normalizeInstallmentsForCompare($installments)
    {
        if (!is_array($installments)) {
            return '';
        }
        $rows = [];
        foreach ($installments as $row) {
            if (is_array($row)) {
                $rows[] = [
                    'deadline' => isset($row['deadline']) ? (string) $row['deadline'] : '',
                    'amount' => isset($row['amount']) ? (string) $row['amount'] : '',
                ];
            } elseif (is_object($row)) {
                $rows[] = [
                    'deadline' => isset($row->deadline) ? (string) $row->deadline : '',
                    'amount' => isset($row->amount) ? (string) $row->amount : '',
                ];
            }
        }
        return json_encode($rows);
    }

    /**
     * Normalize price and discount_price fields
     * Convert Persian numbers to English and remove commas
     */
    public function normalizePrices($event)
    {
        $attributesToNormalize = ['price', 'discount_price'];
        $changed = false;

        foreach ($attributesToNormalize as $attribute) {
            if (!empty($this->$attribute)) {
                $normalizedValue = $this->normalizeNumber($this->$attribute);
                if ($normalizedValue !== $this->$attribute) {
                    $this->$attribute = $normalizedValue;
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->off(self::EVENT_AFTER_UPDATE);
            $this->off(self::EVENT_AFTER_INSERT);

            $this->save(false);

            $this->on(self::EVENT_AFTER_INSERT, [$this, 'normalizePrices']);
            $this->on(self::EVENT_AFTER_UPDATE, [$this, 'normalizePrices']);
        }
    }

    /**
     * Convert Persian numbers to English and remove commas
     * @param mixed $value
     * @return mixed
     */
    private function normalizeNumber($value)
    {
        if (is_string($value)) {
            $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٬'];
            $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ''];

            $value = str_replace($persianNumbers, $englishNumbers, $value);

            $value = str_replace([',', '،', ' '], '', $value);
            
            if ($value === '') {
                return null;
            }
        }

        return $value;
    }
}