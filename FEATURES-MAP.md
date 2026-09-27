# نقشه‌ی فیچرهای پنل EEC

> استخراج‌شده از `frontend/controllers/` — ۳۵ کنترلر، ۳۱۹ اکشن.
> عناوین فارسی از منوی سایدبار (`frontend/views/layouts/main.php`) گرفته شده.
> این سند «چه امکاناتی وجود دارد» را نشان می‌دهد؛ برای «هر امکان چطور کار می‌کند» به کد همان اکشن مراجعه کنید.

---

## فهرست بخش‌های اصلی (منوی سایدبار)

| # | بخش | کنترلر | تعداد اکشن |
|---|------|--------|:---:|
| ۱ | میزکار | `dashboard` | ۱۸ |
| ۲ | مدیریت کاربران | `users-manage` | ۹ |
| ۳ | مدیریت واحدها (دانشکده‌ها) | `collages-manage` | ۴ |
| ۴ | دروس | `lessons` | ۶ |
| ۵ | دوره‌های کوتاه‌مدت | `courses` | ۱۶ |
| ۶ | دوره‌های میان‌مدت | `packages` | ۵۰ |
| ۷ | مدیریت اساتید | `teacher-manage` | ۷ |
| ۸ | مدیریت صدور مدرک | `certificate-manage` | ۱۹ |
| ۹ | کیف پول | `wallet` | ۴ |
| ۱۰ | گزارش دوره‌ها | `reporting` | ۸ |
| ۱۱ | مدیریت کارگزاران | `manage-brokers` | ۲۱ |
| ۱۲ | مدیریت کارکنان | `manage-members` | ۶ |
| ۱۳ | مدیریت اخبار | `manage-news` | ۹ |
| ۱۴ | مالی | `manage-financial` | ۶ |
| ۱۵ | درخواست انصراف | `canceling-requests` | ۳ |
| ۱۶ | آزمون آنلاین‌ساز | `test-maker` | ۱۴ |
| ۱۷ | نظرسنجی | `survey-maker` | ۱۵ |
| ۱۸ | آزمون حضوری | `offline-exams` | ۱۵ |
| ۱۹ | بانک سوالات | `questions-bank` | ۱۲ |
| ۲۰ | گروه‌بندی سوالات | `tests-groups` | ۶ |
| ۲۱ | آپلود سنتر | `upload-center` | ۷ |

**کنترلرهای پشتیبان (بدون آیتم مستقیم در منو):**
`manage-course-contents` (۲۶) · `create-broker` (۳) · `create-single-course` (۲) · `single-lesson` (۳) · `manage-articles` (۹) · `poll` (۶) · `payment` (۳) · `setting` (۲) · `ins` (۴) · `login` · `site` · `access-denied`

---

## ۷. مدیریت اساتید — `teacher-manage`

| اکشن | کار |
|------|-----|
| `index` | فهرست اساتید |
| `new` | افزودن استاد |
| `new_with_id` | افزودن استاد با شناسه (کد ملی) |
| `edit` | ویرایش استاد |
| `reset_password` | بازنشانی رمز عبور استاد (به کد ملی) |
| `change_status` | فعال/غیرفعال کردن استاد |
| `report` | گزارش اساتید |

---

## ۱۱. مدیریت کارگزاران — `manage-brokers`

**ساخت و ویرایش:**
| اکشن | کار |
|------|-----|
| `index` | فهرست کارگزاران |
| `create` / `createLegalBroker` / `createNaturalBroker` / `create_natural_2` | ساخت کارگزار (حقوقی / حقیقی) |
| `edit` / `editLegalBroker` / `editNaturalBroker` / `edit_natural_broker` | ویرایش کارگزار |
| `reset_password` | بازنشانی رمز |
| `change_status` | تغییر وضعیت |
| `change_access` | تغییر سطح دسترسی |
| `file` | دریافت فایل‌های کارگزار |

**فرایند تأیید و قرارداد:**
| اکشن | کار |
|------|-----|
| `confirm` / `confirm_broker` | تأیید کارگزار |
| `un_confirm` / `back_broker` | لغو تأیید / برگشت |
| `reject_broker` | رد کارگزار |
| `new_contract` | افزودن قرارداد |
| `change_contract_status` | تغییر وضعیت قرارداد |
| `report` | گزارش کارگزاران |

> کنترلر جداگانه‌ی `create-broker` هم اکشن‌های `index`, `create`, `edit` را دارد (مسیر ساده‌تر ساخت کارگزار).

---

## ۴. دروس — `lessons`

| اکشن | کار |
|------|-----|
| `index` | فهرست دروس |
| `new` | افزودن درس |
| `edit` | ویرایش درس |
| `get_lesson_detail` | دریافت جزئیات درس (AJAX) |
| `delete_lesson` | حذف درس |
| `report` | گزارش دروس |

> `single-lesson` کنترلر مکملی است با `index`, `new`, `edit`.

---

## ۵. دوره‌های کوتاه‌مدت — `courses`

**مدیریت دوره:**
| اکشن | کار |
|------|-----|
| `index` | فهرست دوره‌ها |
| `new` / `edit` | افزودن / ویرایش دوره |
| `editCourse` / `copyCourse` / `manageCourse` | ویرایش / کپی / مدیریت دوره |
| `delete_course` | حذف دوره |
| `course_date` | تنظیم تاریخ دوره |
| `capacity` | مدیریت ظرفیت |

**کارگزار و اعضا:**
| اکشن | کار |
|------|-----|
| `brokers` / `brokers1` / `broker_contracts` | انتخاب کارگزار و قرارداد دوره |
| `checkUsername` | بررسی نام کاربری (AJAX) |
| `show_course_users` | نمایش کاربران دوره |
| `members_report` / `report` | گزارش اعضا / گزارش دوره |

> `create-single-course` کنترلر مکملی برای ساخت دوره‌ی تکی است (`index`, `create`).

---

## ۶. دوره‌های میان‌مدت — `packages` (بزرگ‌ترین بخش، ۵۰ اکشن)

**مدیریت دوره:**
| اکشن | کار |
|------|-----|
| `index` / `new` / `edit` | فهرست / افزودن / ویرایش |
| `createPackage` / `editPackage` / `copyPackage` | ساخت / ویرایش / کپی دوره میان‌مدت |
| `delete_course` / `hidden_course` | حذف / مخفی‌سازی |
| `capacity` / `course_date` | ظرفیت / تاریخ |
| `show_courses` / `show_course_users` / `show_course_lessons` | نمایش دوره‌ها / کاربران / دروس |
| `add_lesson_to_package` / `edit_course_in_package` | مدیریت دروس داخل دوره |
| `other_teachers` | اساتید دیگر |

**کاربران و ثبت‌نام:**
| اکشن | کار |
|------|-----|
| `new_user` / `show_user_detail` | افزودن / نمایش کاربر |
| `add_user_from_list` | افزودن کاربر از لیست |
| `check_excel_file` / `add_user_from_exel` | بررسی فایل اکسل / افزودن گروهی از اکسل ← *(همان بخشی که ولیدیشن اکسل رویش کار کردیم)* |
| `change_status` / `change_role` | تغییر وضعیت / نقش کاربر |
| `delete_user_from_course` | حذف کاربر از دوره |
| `members_report` | گزارش اعضا |

**نمرات و مدارک:**
| اکشن | کار |
|------|-----|
| `recordingGrades` / `register_grades` / `register_course_scores` | ثبت نمرات |

**مالی و اقساط:**
| اکشن | کار |
|------|-----|
| `add_installment` / `add_single_installment` | افزودن قسط |
| `edit_installments` / `edit_prepayment_installments` / `delete_installments` | ویرایش / حذف اقساط |
| `add_discount` / `delete_discount` | تخفیف |
| `add_credit_request` / `pay_add_credit` / `add_credit_callback` | درخواست و پرداخت اعتبار |
| `courses_financial` / `courses_financial_callback` | مالی دوره‌ها + بازگشت درگاه |
| `show_finance_info` | اطلاعات مالی |

**ادوبی کانکت (کلاس آنلاین):**
| اکشن | کار |
|------|-----|
| `add_user_to_adobe` | افزودن کاربر به کلاس |
| `register_class_in_adobe` | ثبت کلاس در ادوبی کانکت |

**فایل:**
| اکشن | کار |
|------|-----|
| `file` / `contract_file` | دریافت فایل دوره / قرارداد |

---

## ۸. مدیریت صدور مدرک — `certificate-manage`

| اکشن | کار |
|------|-----|
| `index` | فهرست |
| `course-members` (`courseMembers`) | اعضای دوره برای صدور مدرک |
| `complete_profile` / `show_profile_form` | تکمیل / نمایش فرم پروفایل کاربر |
| `add_single_request` | ثبت درخواست تکی مدرک |
| `view-requests` (`viewRequests`) | مشاهده درخواست‌ها |
| `confirm_request` | تأیید درخواست |
| `issued-certificates` (`issuedCertificates`) | مدارک صادرشده |
| `printCertificate` / `newPrintCertificate` | چاپ مدرک (نسخه قدیم/جدید) |
| `printAllCertificate` / `newPrintAllCertificate` | چاپ گروهی مدارک |
| `add_serial` / `checkSerialNumber` | مدیریت شماره سریال مدرک |
| `edit` | ویرایش |
| `cities` | فهرست شهرها (AJAX) |
| `change_preview` | تغییر پیش‌نمایش |
| `file` | دریافت فایل مدرک |
| `report` | گزارش |

---

## ۲. مدیریت کاربران — `users-manage`

| اکشن | کار |
|------|-----|
| `index` | فهرست کاربران |
| `new_user` | افزودن کاربر |
| `edit_user` | ویرایش کاربر |
| `change_password` | تغییر رمز عبور |
| `change_status` | تغییر وضعیت |
| `change_user_course_status` | تغییر وضعیت دوره‌ی کاربر |
| `check_excel_file` / `add_user_from_exel` | افزودن گروهی از اکسل |
| `report` | گزارش |

---

## ۱۲. مدیریت کارکنان — `manage-members`

| اکشن | کار |
|------|-----|
| `index` / `new` / `edit` | فهرست / افزودن / ویرایش کارمند |
| `reset_password` | بازنشانی رمز (به کد ملی) |
| `change_status` | تغییر وضعیت |
| `report` | گزارش |

---

## ۳. مدیریت واحدها (دانشکده‌ها) — `collages-manage`

| اکشن | کار |
|------|-----|
| `index` / `new` / `edit` | فهرست / افزودن / ویرایش واحد |
| `report` | گزارش |

---

## ۹. کیف پول — `wallet`

| اکشن | کار |
|------|-----|
| `index` | نمایش کیف پول |
| `increase_wallet` / `increase_wallet_callback` | افزایش موجودی + بازگشت از درگاه |
| `report` | گزارش تراکنش‌ها |

---

## ۱۰ و ۱۴. گزارش دوره‌ها و مالی — `reporting` / `manage-financial`

| اکشن | کار |
|------|-----|
| `index` | صفحه اصلی گزارش |
| `installments` / `installments_report` | گزارش اقساط |
| `coursesFinancial` | مالی دوره‌ها |
| `financial_excel` / `create_excel` | خروجی اکسل |
| `addUserFromExcel` (در مالی) | افزودن کاربر از اکسل |
| `report` | گزارش کلی |

---

## ۱۶–۲۰. سیستم آزمون و سوالات

**آزمون آنلاین‌ساز — `test-maker`:**
`index`, `new`, `edit`, `manageQuestions`, `create_question`, `edit_question`, `delete_question`, `random_questions`, `show_in_site` …

**نظرسنجی — `survey-maker`:**
`index`, `managementSurvey`, `manageSurvey`, `manageManagementSurvey`, `new`, `new_management_survey`, `edit`, `create_question`, `edit_question` …

**آزمون حضوری — `offline-exams`:**
`index`, `new`, `edit`, `change_status`, `participants` (شرکت‌کنندگان), `print`, `financialPrint`, `report`, `financial_report`, `edit_user` …

**بانک سوالات — `questions-bank`:**
`index`, `new`, `create`, `edit`, `manageQuestions`, `create_question`, `edit_question`, `delete_question` …

**گروه‌بندی سوالات — `tests-groups`:**
`index`, `new`, `edit`, `create_question`, `edit_question`, `readXmlFile` (بارگذاری از XML)

---

## ۱۵. درخواست انصراف — `canceling-requests`

| اکشن | کار |
|------|-----|
| `index` | فهرست درخواست‌های انصراف |
| `accept` | پذیرش انصراف |
| `report` | گزارش |

---

## ۲۱ و پشتیبان. آپلود سنتر و محتوای دوره

**آپلود سنتر — `upload-center`:**
`index`, `new`, `edit`, `file`, `show_file_uses` (کاربردهای فایل), `delete_file`, `report`

**مدیریت محتوای دوره — `manage-course-contents` (۲۶ اکشن):**
`index`, `new_title`, `new_video_from_upload_center`, `new_direct_video`, `new_exam`, `new_survey`, `new_workout_from_upload_center`, `new_file_from_upload_center`, `download_file`, `download_exercise`, `file` … — مدیریت ویدیو، تمرین، آزمون و فایل داخل هر دوره.

---

## ۱۳. مدیریت اخبار و مقالات

**اخبار — `manage-news`:**
`index`, `new`, `new_news`, `editNews`, `edit`, `new_file`, `add_new_file`, `edit_type_2`, `delete`

**مقالات — `manage-articles`:**
`index`, `add`, `editArticle`, `completionArticle`, `editCompletionArticle`, `upload`, `update`, `update_base`, `delete`

---

## ۱. میزکار — `dashboard` (۱۸ اکشن)

علاوه بر صفحه‌ی اصلی، فرایند **تأیید دوره** را مدیریت می‌کند:
`confirm_package` / `confirm_package_from_college` (تأیید توسط ادمین / دانشکده) · `back_package` / `reject_package` (و نسخه‌های `_from_college`) · `send_course_to_admin` · `exportCoursesYear` · `create_natural` (ساخت کارگزار حقیقی از میزکار).

> نکته: `dashboard` یک تابع `access($page)` هم دارد که سطح دسترسی نقش‌ها به هر بخش را تعیین می‌کند — منطق مجوزدهی مرکزی سیستم اینجاست.

---

## نقش‌های کاربری سیستم

از بررسی کد، این نقش‌ها استفاده می‌شوند:

| نقش | توضیح |
|------|-------|
| `user` | ادمین اصلی (بیشترین دسترسی) |
| `cnt` | کارشناس / رابط (دسترسی محدود بر اساس لیست `access`) |
| `broker` | کارگزار |
| `emp` | کارمند |
| `teacher` | استاد |

دسترسی هر نقش به هر بخش در `DashboardController::access()` و در `behaviors()` هر کنترلر (بخش `access` با `matchCallback`) تعیین می‌شود.
