# فیلدهای ثبت همه‌ی بخش‌های پنل EEC

> فهرست فیلدهای «افزودن/ثبت» برای هر بخش، استخراج‌شده از مدل‌ها، فرم‌ها و کنترلرهای واقعی پروژه.
> برای درک ماشینی (ChatGPT) و انسانی تهیه شده است.
> **منبع:** `frontend/models/*`, `frontend/views/*/`, `frontend/controllers/*`.

**راهنمای نمادها:** ✅ اجباری | ❌ اختیاری | 🔒 مقدارِ خودکارِ سرور (کاربر وارد نمی‌کند)

**قرارداد مشترک جنسیت (`gender`):** `1` = مرد، `2` = زن.

---

## فهرست بخش‌ها
1. [افزودن استاد](#۱-افزودن-استاد)
2. [افزودن کارگزار (حقیقی و حقوقی)](#۲-افزودن-کارگزار)
3. [افزودن درس](#۳-افزودن-درس)
4. [افزودن واحد / دانشکده](#۴-افزودن-واحد--دانشکده)
5. [افزودن کاربر](#۵-افزودن-کاربر)
6. [افزودن کارمند](#۶-افزودن-کارمند)
7. [ثبت دوره بلندمدت](#۷-ثبت-دوره-بلندمدت)

---

## ۱. افزودن استاد
**مدل:** `Teachers` · **کنترلر:** `teacher-manage` (`actionNew`) · **فرم:** `views/teacher-manage/index.php` (مودال)

| کلید POST | برچسب | نوع | اجباری | توضیح |
|-----------|-------|-----|:------:|-------|
| `Teachers[first_name]` | نام | متن | ✅ | — |
| `Teachers[last_name]` | نام خانوادگی | متن | ✅ | — |
| `Teachers[id]` | کد ملی | متن/عدد | ✅ | همان مقداری که رمز عبور حساب استاد از آن ساخته می‌شود |
| `Teachers[mobile]` | شماره همراه | عدد | ✅ | نام کاربری حساب استاد |
| `Teachers[gender]` | جنسیت | انتخابی | ✅ | `1`=مرد، `2`=زن |
| `Teachers[colleges]` | دانشکده‌ها | آرایه شناسه | ✅ | یک استاد می‌تواند به چند دانشکده وصل باشد |
| `Teachers[comment]` | توضیحات | متن | ❌ | — |
| `Teachers[profile_image]` | تصویر پروفایل | فایل | ❌ | ذخیره در `web/teacher_profiles/` |
| `status` | وضعیت | 🔒 | — | فعال/غیرفعال |
| `registrant` | ثبت‌کننده | 🔒 | — | کاربر جاری |

---

## ۲. افزودن کارگزار
**مدل:** `Brokers` · **کنترلر:** `manage-brokers` / `create-broker`
**دو نوع:** حقیقی (`createNaturalBroker`) و حقوقی (`createLegalBroker`). ساختار فیلدها تقریباً یکسان است.

### ۲-۱. اطلاعات شرکت — `Brokers[company_info]`
| کلید | برچسب | حقیقی | حقوقی |
|------|-------|:-----:|:-----:|
| `company_info[company_title]` | نام شرکت/عنوان | ✅ | ✅ |
| `company_info[id]` | شناسه/شناسه ملی شرکت | ✅ | ✅ |
| `company_info[registration_number]` | شماره ثبت | ✅ | ✅ |
| `company_info[establishment_date]` | تاریخ تأسیس | ✅ | ✅ |
| `company_info[address]` | آدرس | ✅ | ✅ |
| `company_info[zip_code]` | کد پستی | ❌ | ✅ |
| `company_info[start_contract_date]` | تاریخ شروع قرارداد | ✅ | ✅ |
| `company_info[end_contract_date]` | تاریخ پایان قرارداد | ✅ | ✅ |

### ۲-۲. اطلاعات رابط — `Brokers[connector_info]`
| کلید | برچسب | حقیقی | حقوقی |
|------|-------|:-----:|:-----:|
| `connector_info[first_name]` | نام رابط | ✅ | ✅ |
| `connector_info[last_name]` | نام خانوادگی رابط | ✅ | ✅ |
| `connector_info[id]` | کد ملی رابط | ✅ | ✅ |
| `connector_info[mobile]` | شماره همراه رابط | ✅ | ✅ |
| `connector_info[birth_day]` | تاریخ تولد | ✅ | ✅ |
| `connector_info[address]` | آدرس رابط | ✅ | ❌ |
| `connector_info[zip_code]` | کد پستی رابط | ✅ | ❌ |

### ۲-۳. اطلاعات مالی — `Brokers[financial_info]`
| کلید | برچسب |
|------|-------|
| `financial_info[id]` | شناسه مالی |
| `financial_info[sub_service_id]` | شناسه زیرسرویس |

### ۲-۴. قرارداد — `Brokers[contracts]`
| کلید | برچسب |
|------|-------|
| `contracts[title]` | عنوان قرارداد |
| `contracts[share]` | سهم (درصد) |
| `contracts[expiration_date]` | تاریخ انقضا |

### ۲-۵. فایل‌ها و سایر
| کلید | برچسب | نوع |
|------|-------|-----|
| `Brokers[college]` | دانشکده | شناسه |
| `Brokers[statute_file]` | فایل اساسنامه | فایل |
| `Brokers[newspaper_file]` | فایل روزنامه رسمی | فایل |
| `Brokers[id_file]` | فایل کارت ملی | فایل |
| `Brokers[contract_file]` | فایل قرارداد | فایل |
| `type` | نوع (حقیقی/حقوقی) | 🔒 |
| `status` / `wallet_amount` / `registrant` | وضعیت / موجودی کیف‌پول / ثبت‌کننده | 🔒 |

---

## ۳. افزودن درس
**مدل:** `Lessons` · **کنترلر:** `lessons` (`actionNew`)

| کلید POST | برچسب | نوع | اجباری | توضیح |
|-----------|-------|-----|:------:|-------|
| `Lessons[title]` | عنوان فارسی | متن | ✅ | — |
| `Lessons[en_title]` | عنوان انگلیسی | متن | ❌ | — |
| `Lessons[college]` | دانشکده | شناسه | ✅ | درس متعلق به کدام دانشکده |
| `Lessons[comment]` | توضیحات | متن | ❌ | — |
| `Lessons[description]` | شرح | متن | ❌ | — |
| `status` | وضعیت | 🔒 | — | — |

---

## ۴. افزودن واحد / دانشکده
**مدل:** `Colleges` · **کنترلر:** `collages-manage` (`actionNew`)

| کلید POST | برچسب | نوع | اجباری | توضیح |
|-----------|-------|-----|:------:|-------|
| `Colleges[title]` | عنوان فارسی | متن | ✅ | — |
| `Colleges[title_en]` | عنوان انگلیسی | متن | ❌ | — |
| `Colleges[name]` | نام مسئول | متن | ✅ | — |
| `Colleges[last_name]` | نام خانوادگی مسئول | متن | ✅ | — |
| `Colleges[phone]` | تلفن | عدد | ❌ | — |
| `Colleges[prefix]` | پیشوند کد مدرک | متن | ✅ | در ساخت `license_code` استفاده می‌شود |
| `Colleges[logo]` | لوگو | فایل | ❌ | `web/college_logos/` |
| `Colleges[signature_file]` | فایل امضا | فایل | ❌ | — |
| `Colleges[first_line_signature_fa]` | خط اول امضا (فارسی) | متن | ❌ | روی مدرک |
| `Colleges[second_line_signature_fa]` | خط دوم امضا (فارسی) | متن | ❌ | روی مدرک |
| `Colleges[first_line_signature_en]` | خط اول امضا (انگلیسی) | متن | ❌ | — |
| `Colleges[second_line_signature_en]` | خط دوم امضا (انگلیسی) | متن | ❌ | — |
| `Colleges[allow_free_add_user]` | اجازه افزودن رایگان کاربر | flag | ❌ | — |
| `status` | وضعیت | 🔒 | — | — |

---

## ۵. افزودن کاربر
**مدل:** `Users` · **کنترلر:** `users-manage` (`actionNew_user`)

| کلید POST | برچسب | نوع | اجباری | توضیح |
|-----------|-------|-----|:------:|-------|
| `Users[first_name]` | نام | متن | ✅ | — |
| `Users[last_name]` | نام خانوادگی | متن | ✅ | — |
| `Users[username]` | نام کاربری | متن | ✅ | با `strtolower` ذخیره می‌شود؛ معمولاً موبایل یا ایمیل |
| `Users[password_hash]` | رمز عبور | متن | ✅ | با `setPassword` هش (bcrypt) می‌شود — مقدار خام ذخیره نمی‌شود |
| `Users[mobile]` | شماره همراه | عدد | ❌ | — |
| `Users[email]` | ایمیل | متن | ❌ | — |
| `role` | نقش | 🔒 | — | ثابت `'user'` در این اکشن |
| `status` | وضعیت | 🔒 | — | مقدار `10` |
| `auth_key` / `verification_token` / `registrant` | کلیدهای امنیتی / ثبت‌کننده | 🔒 | — | خودکار |

> **ثبت گروهی از اکسل** هم وجود دارد (`check_excel_file` → `add_user_from_exel`) با ستون‌های:
> نام، نام خانوادگی، نام کاربری، کد ملی (رمز)، نام لاتین، نام‌خانوادگی لاتین، جنسیت (۱/۲).

---

## ۶. افزودن کارمند
**مدل:** `Admin` · **کنترلر:** `manage-members` (`actionNew`)

| کلید POST | برچسب | نوع | اجباری | توضیح |
|-----------|-------|-----|:------:|-------|
| `Admin[first_name]` | نام | متن | ✅ | — |
| `Admin[last_name]` | نام خانوادگی | متن | ✅ | — |
| `Admin[username]` | نام کاربری | متن | ✅ | — |
| `Admin[national_code]` | کد ملی | متن/عدد | ✅ | رمز عبور از آن ساخته می‌شود |
| `Admin[gender]` | جنسیت | انتخابی | ✅ | `1`=مرد، `2`=زن |
| `Admin[college]` | دانشکده | شناسه | ✅ | — |
| `Admin[access]` | سطوح دسترسی | آرایه | ❌ | فهرست بخش‌هایی که کارمند به آن‌ها دسترسی دارد |
| `role` / `status` / `auth_key` / `registrant` | نقش / وضعیت / کلید / ثبت‌کننده | 🔒 | — | خودکار |

---

## ۷. ثبت دوره بلندمدت
**مدل:** `Courses` · **کنترلر:** `packages` (`actionCreatePackage`) · **فرم:** `views/packages/create-package.php`

> میان‌مدت و بلندمدت یک فرم دارند؛ تفاوت فقط در `duration` است (**بلندمدت = ۲۵۱ تا ۳۵۰ ساعت**).
> ⚠ فیلد `type` در فرم غیرفعال است و کنترلر آن را ثابت `'2'` می‌گذارد؛ بلندمدت بودن از `duration` تشخیص داده می‌شود.

### فیلدهای اصلی
| کلید POST | برچسب | نوع | اجباری | مقادیر / قالب |
|-----------|-------|-----|:------:|---------------|
| `Courses[title][main_fa]` | عنوان اصلی فارسی | متن | ✅ | — |
| `Courses[title][main_en]` | عنوان اصلی انگلیسی | متن | ❌ | — |
| `Courses[title][degree_fa]` | عنوان فارسی داخل مدرک | متن | ✅ | — |
| `Courses[title][degree_en]` | عنوان انگلیسی داخل مدرک | متن | ❌ | — |
| `Courses[price]` | قیمت اصلی | عدد | ✅ | فقط ارقام لاتین `^[0-9]+$` |
| `Courses[discount_price]` | قیمت با تخفیف | عدد | ❌ | `^[0-9]+$` — نباید از `price` بیشتر باشد |
| `Courses[college]` | دانشکده | شناسه | ✅ | — |
| `Courses[content_type]` | نوع دوره | انتخابی | ✅ | `1`=غیرحضوری، `2`=نیمه‌حضوری، `4`=حضوری |
| `Courses[duration]` | مدت (ساعت) | عدد | ✅ | بلندمدت = ۲۵۱–۳۵۰ |
| `Courses[place]` | محل برگزاری | متن | ✅ | — |
| `Courses[time]` | زمان برگزاری | متن | ✅ | — |
| `Courses[description]` | توضیح کوتاه | متن | ❌ | — |
| `Courses[full_description]` | توضیح کامل | HTML | ❌ | ادیتور |
| `Courses[preview_image]` | تصویر پیش‌نمایش | فایل | ❌ | نام سرور با `uniqid().'.jpg'` |
| `Courses[contract_file]` | فایل قرارداد | فایل | ❌ | — |
| `Courses[deadline_date]` | مهلت ثبت‌نام | تاریخ | ❌ | خالی → سرور خودکار حساب می‌کند |

### فیلدهای تودرتو
- **تاریخ** — `Courses[date][from]` (✅ `Y-m-d`), `Courses[date][to]` (✅ `Y-m-d`)
- **کارگزار (اختیاری)** — `Courses[broker][_id]`, `Courses[broker][contract]`
- **دروس (آرایه)** — هر ردیف: `[_id]`, `[teacher]`, `[date]` (from/to/time), `[meeting]`
- **اقساط (اختیاری)** — کلید مستقل `installments` + `Courses[prepayment_installments]`

### فیلدهای خودکار سرور
`type='2'` · `credit='0'` · `show_in_site=true` · `registrant` · `status` (بر اساس نقش) · `license_code` · `deadline_date`

---

## پیوست: نقش‌های کاربری سیستم
| نقش | توضیح |
|------|-------|
| `user` | ادمین اصلی (بیشترین دسترسی) |
| `cnt` | کارشناس/رابط (دسترسی محدود طبق لیست `access`) |
| `broker` | کارگزار |
| `emp` | کارمند |
| `teacher` | استاد |
