# گزارش بررسی امنیتی پروژه EEC — ۱۴۰۵/۰۶/۰۸ (۲۰۲۶-۰۸-۲۹)

> **دامنه‌ی بررسی:** نسخه‌ی موجود روی این دستگاه در `/Users/aiadparnoon/sites/eec`.
> این یک بازبینی کد (code review) است، نه تست نفوذ روی سرور زنده.
>
> **⚠ نکته‌ی مهم:** فایل‌های `index.php` و `main-local.php` معمولاً بین محلی و سرور فرق دارند.
> قبل از اقدام، حتماً **نسخه‌ی واقعی روی سرور** را با همین موارد تطبیق دهید. اگر سرور نسخه‌ی
> متفاوتی دارد، بندهای ۱ و ۲ ممکن است آنجا صدق نکند.

---

## خلاصه‌ی مدیریتی

| اولویت | تعداد | مهم‌ترین موارد |
|---|---|---|
| 🔴 بحرانی | ۶ | **نمایش رمز عبور اساتید در فهرست**، آپلود فایل بدون اعتبارسنجی، path traversal، حالت debug روشن، Gii فعال، Yii قدیمی |
| 🟠 بالا | ۹ | لایه‌ی sanitizer بی‌اثر، XSS ذخیره‌شده، دسترسی آزاد به فایل‌های محرمانه، PHPExcel متروک، **رمز متن‌ساده در لاگ** |
| 🟡 متوسط | ۹ | نبود محافظت brute-force، کوکی بدون Secure، هدرهای امنیتی، وابستگی‌های قدیمی |

**سه کاری که همین امروز باید انجام دهید:** بند ۱، بند ۲، بند ۴.

---

# 🔴 بحرانی

## ۰. فهرست اساتید، نام کاربری و رمز عبور هر استاد را کنار هم نمایش می‌دهد

> این شدیدترین یافته‌ی کل بررسی است و به هیچ مهارت فنی‌ای نیاز ندارد — فقط باز کردن یک صفحه.

**زنجیره:**

۱. رمز عبور حساب استاد، **کد ملی** اوست:
   `frontend/controllers/TeacherManageController.php:197`
   ```php
   $access->setPassword($model->id);      // $model->id = کد ملی استاد
   $access->username = $model->mobile;    // نام کاربری = شماره موبایل
   ```
   اینکه `id` همان کد ملی است، از متن خود پروژه تأیید می‌شود —
   `frontend/views/teacher-manage/index.php:378`:
   «آیا از بازنشانی رمز عبور ... به **کد ملی وی** (`<?= $teacher->id ?>`) اطمینان دارید؟»

۲. صفحه‌ی فهرست اساتید هر دو مقدار را **کنار هم در جدول چاپ می‌کند**:
   `frontend/views/teacher-manage/index.php:194-195`
   ```php
   <td><?= $teacher->mobile ?></td>   <!-- نام کاربری -->
   <td><?= $teacher->id ?></td>       <!-- کد ملی = رمز عبور -->
   ```

۳. دسترسی به این صفحه باز است — `TeacherManageController.php:42`:
   ```php
   role == 'user' || role == 'broker' || 'teacher-manage' در access list
   ```
   یعنی **نمایندگان (broker)** هم که افراد بیرون سازمان هستند، این فهرست را می‌بینند.

**نتیجه:** هر کاربر با نقش `broker` یا `user` با باز کردن یک صفحه، فهرست کامل نام کاربری و
رمز عبور همه‌ی اساتید را دارد. با آن حساب‌ها می‌تواند وارد شود و — طبق بند ۳ — فایل PHP
آپلود کند، یعنی **اجرای کد روی سرور**.

**همین الگو در جاهای دیگر هم هست:**

| فایل | خط | رمز عبور چه چیزی است |
|---|---|---|
| `TeacherManageController.php` | ۱۹۷، ۳۳۳ | کد ملی استاد |
| `ManageMembersController.php` | ۱۴۵، ۲۲۵ | کد ملی کاربر |
| `CreateBrokerController.php` | ۱۲۳ | `connector_info['id']` |
| `ManageBrokersController.php` | ۳۴۹، ۴۱۹، ۶۴۱ | `connector_info['id']` |
| `DashboardController.php` | ۸۴۱ | `connector_info['id']` |
| `PackagesController.php` | ۲۴۰۶ | کد ملی (ستون D فایل اکسل) |

**اصلاح فوری (به ترتیب):**
1. ستون کد ملی را از فهرست اساتید حذف یا ماسک کنید (`•••••1234`).
2. برای همه‌ی حساب‌هایی که رمزشان از روی کد ملی/شناسه ساخته شده، رمز تصادفی تولید کنید و
   از طریق پیامک به خود فرد بفرستید.
3. فلگ `must_change_password` اضافه کنید و ورود اول را به صفحه‌ی تغییر رمز هدایت کنید.
4. دسترسی `broker` به `teacher-manage` را بازبینی کنید — احتمالاً از ابتدا اشتباه بوده.

---

## ۱. حالت Debug روی حالت توسعه قفل شده است

**فایل:** `frontend/web/index.php` خط ۲–۳

```php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
```

**چرا خطرناک است:** اگر همین فایل روی سرور باشد، با هر خطای مدیریت‌نشده Yii یک صفحه‌ی کامل
stack trace نمایش می‌دهد: مسیر مطلق فایل‌ها روی سرور، قطعه‌ی سورس کد اطراف خطا، مقادیر
متغیرها، و در بسیاری موارد پارامترهای اتصال به دیتابیس. مهاجم فقط کافی است یک پارامتر
نامعتبر بفرستد تا نقشه‌ی کامل سیستم را بگیرد.

**اصلاح:** روی سرور باید این‌طور باشد:

```php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
```

بهترین کار: مقدار را از متغیر محیطی وب‌سرور بخوانید تا دیگر خطای انسانی پیش نیاید.

---

## ۲. ماژول Gii روی سرور فعال است

**فایل:** `frontend/config/main-local.php` خط ۱۹–۲۲

```php
if (!YII_ENV_TEST) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = ['class' => 'yii\gii\Module'];
}
```

چون `YII_ENV` برابر `dev` است (بند ۱)، شرط `!YII_ENV_TEST` برقرار می‌شود و **Gii فعال است**.

**چرا خطرناک است:** Gii یک تولیدکننده‌ی کد است — یعنی می‌تواند **فایل PHP روی سرور بنویسد**.
دسترسی به آن = اجرای کد از راه دور.

به‌صورت پیش‌فرض Gii فقط از `127.0.0.1` باز است
(`vendor/yiisoft/yii2-gii/src/Module.php:56`)، ولی این محافظت در دو حالت رایج از بین می‌رود:

- سایت پشت **reverse proxy** (nginx جلوی php-fpm، لود بالانسر، Cloudflare) باشد و
  `REMOTE_ADDR` برابر `127.0.0.1` دیده شود → Gii برای **همه** باز می‌شود.
- کسی روی سرور `allowedIPs` را دستکاری کرده باشد.

**اصلاح:** روی سرور کل بلوک Gii را حذف کنید. Gii و `yii2-debug` هر دو ابزار توسعه‌اند و
اصلاً نباید روی production نصب باشند:

```bash
composer remove --dev yiisoft/yii2-gii yiisoft/yii2-debug
```

---

## ۳. آپلود فایل بدون هیچ اعتبارسنجی نوع، مستقیم داخل پوشه‌ی وب

**شواهد:**

- **صفر** ولیدیتور `extensions` در کل مدل‌های پروژه (`frontend/models/`, `common/models/`).
  همه‌ی قوانین `'safe'` هستند — مثلاً `frontend/models/News.php:46-60`.
- پسوند مستقیماً از **نام فایلِ کاربر** گرفته می‌شود و در نام مقصد استفاده می‌شود:

  `frontend/controllers/ManageNewsController.php:106-108`
  ```php
  $file1_ext  = $file1->extension;              // ← از نام فایل کاربر
  $file1_name = uniqid() . '.' . $file1_ext;
  $file1->saveAs('../../frontend/web/news_images/' . $file1_name);
  ```
- مقصد `frontend/web/...` است، یعنی **داخل document root** و مستقیماً از وب قابل فراخوانی.
- فایل **قبل از** `$model->save()` (و قبل از هر ولیدیشنی) روی دیسک نوشته می‌شود.

**سناریوی حمله:** کاربر واردشده‌ای که به «مدیریت اخبار» دسترسی دارد، فایلی به نام `shell.php`
آپلود می‌کند → روی سرور به‌صورت `68b2f1a4c3e91.php` در `frontend/web/news_images/` ذخیره
می‌شود → مهاجم `https://site/news_images/68b2f1a4c3e91.php` را باز می‌کند → **اجرای کد روی
سرور**.

**همین الگو در این کنترلرها تکرار شده است:**
`ManageNewsController`, `ManageBrokersController`, `CreateBrokerController`,
`CollagesManageController`, `CertificateManageController`, `CoursesController`,
`LessonsController`, `ManageArticlesController`, `DashboardController`,
`CreateSingleCourseController`.

**اصلاح (هر سه لایه لازم است):**

1. **ولیدیشن در مدل** — پسوند و MIME را سرور-ساید چک کنید:
   ```php
   [['image'], 'file', 'skipOnEmpty' => true,
    'extensions' => 'png, jpg, jpeg, webp',
    'mimeTypes'  => 'image/png, image/jpeg, image/webp',
    'maxSize'    => 5 * 1024 * 1024],
   ```
2. **نام مقصد را از ورودی کاربر نسازید** — پسوند را از لیست سفید خودتان انتخاب کنید،
   نه از `$file->extension`.
3. **پوشه‌ی آپلود را از `frontend/web/` بیرون ببرید** (مثلاً `@common/uploads/`) و فایل‌ها
   را فقط از طریق یک اکشن کنترل‌شده با `sendFile` سرو کنید.

اگر جابه‌جایی پوشه‌ها الان ممکن نیست، **حداقل** اجرای PHP را در آن پوشه‌ها ببندید:

nginx:
```nginx
location ~* ^/(news_images|news_files|broker_files|contract_files|certificate_files|college_logos|lesson_images|teacher_profiles|uploads|uploaded_excels)/ {
    location ~ \.(php|phtml|phar|php[0-9]?)$ { deny all; }
}
```

Apache — یک `.htaccess` داخل هر پوشه‌ی آپلود:
```apache
php_flag engine off
<FilesMatch "\.(php|phtml|phar|php[0-9]?)$">
    Require all denied
</FilesMatch>
```

---

## ۴. Path Traversal — خواندن هر فایلی از روی سرور

**فایل:** `frontend/controllers/CertificateManageController.php:151-155`

```php
public function actionFile($filename)
{
    $storagePath = 'certificate_files';
    if (file_exists("$storagePath/$filename"))
        return Yii::$app->response->sendFile("$storagePath/$filename", $filename);
```

`$filename` مستقیماً از درخواست می‌آید و **هیچ پاک‌سازی‌ای روی آن انجام نمی‌شود**.

**تأیید عملی (اجرا شده روی همین دستگاه):** مسیر جاری اجرای اپلیکیشن `frontend/web` است، پس:

```
ورودی مهاجم : ../../config/main-local.php
مسیر نهایی  : /Users/aiadparnoon/sites/eec/frontend/config/main-local.php
file_exists : بله   |   قابل خواندن: بله
محتوا       : شامل cookieValidationKey
```

**چرا این بدترین مورد است:** `cookieValidationKey` کلیدی است که Yii با آن کوکی‌ها و سشن را
امضا می‌کند. با داشتن این کلید، مهاجم می‌تواند **کوکی هویت جعل کند** و به‌عنوان هر کاربری
(از جمله ادمین) وارد شود. با همان روش می‌شود `common/config/main-local.php` (اطلاعات
دیتابیس) و هر سورس کد دیگری را هم خواند.

این نیاز به کاربر واردشده دارد، ولی هر کاربر عادی با نقش `user` یا `broker` طبق قانون دسترسی
خط ۵۰ به این اکشن دسترسی دارد — یعنی **ارتقای دسترسی از کاربر عادی به ادمین**.

**همین الگو در:** `ManageCourseContentsController.php:135, 141, 167`

**اصلاح:**

```php
public function actionFile($filename)
{
    $filename = basename($filename);                 // حذف هر مسیری
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $filename))
        throw new \yii\web\BadRequestHttpException('نام فایل نامعتبر است');

    $storagePath = Yii::getAlias('@common/uploads/certificate_files');
    $full = realpath($storagePath . '/' . $filename);

    if ($full === false || strpos($full, realpath($storagePath) . DIRECTORY_SEPARATOR) !== 0)
        throw new \yii\web\NotFoundHttpException('فایل پیدا نشد');

    return Yii::$app->response->sendFile($full, $filename);
}
```

**بعد از اصلاح حتماً `cookieValidationKey` را عوض کنید** — فرض کنید لو رفته است.

---

## ۵. Yii نسخه‌ی ۲.۰.۱۸ (آوریل ۲۰۱۹) — RCE شناخته‌شده

**تأیید شده:** `vendor/yiisoft/yii2/BaseYii.php:96` → `return '2.0.18';`

**CVE-2020-15148** — اجرای کد از راه دور از طریق unserialize ناامن در Yii2. در نسخه‌ی
**۲.۰.۳۸** رفع شده است. نسخه‌ی شما ۲۰ نسخه عقب‌تر از آن است. بعد از آن هم چند gadget chain
دیگر (از جمله CVE-2024-4990) رفع شده که نسخه‌ی شما همه را دارد.

**اصلاح:**
```bash
composer require "yiisoft/yii2:^2.0.53" --update-with-dependencies
```
حتماً اول روی یک نسخه‌ی staging تست کنید. ارتقا در شاخه‌ی 2.0.x معمولاً سازگار است.

---

# 🟠 بالا

## ۶. لایه‌ی InputSanitizer اصلاً اجرا نمی‌شود (امنیت کاذب)

پروژه یک middleware پاک‌سازی ورودی دارد که **هرگز اجرا نمی‌شود** — به دو دلیل مستقل:

**دلیل اول — جای ثبت اشتباه است.** هر دو ثبت، *داخل* آرایه‌ی `components` قرار گرفته‌اند:

- `frontend/config/main.php:21-24` → `'as globalSanitizer' => [...]` داخل `components`
- `common/config/main.php:68-70` → `'as beforeRequest' => [...]` داخل `components`

پیشوند `as ` فقط وقتی معنی «behavior» می‌دهد که در **ریشه‌ی آرایه‌ی config** باشد. الان Yii
این‌ها را دو کامپوننت تنبل با نام‌های عجیب «as globalSanitizer» و «as beforeRequest» می‌بیند
که هیچ‌وقت ساخته نمی‌شوند.

**دلیل دوم — باگ پاس دادن با ارجاع.** حتی اگر جای ثبت درست شود، این کد کار نمی‌کند:

`frontend/middlewares/InputSanitizerMiddleware.php:16, 21`
```php
$this->sanitize($request->get());     // ← خروجی تابع، نه متغیر
```
`sanitize` پارامترش را با ارجاع می‌گیرد (`&$data`)، ولی اینجا **خروجی یک تابع** پاس داده شده.
PHP نتیجه را دور می‌ریزد و `$_GET`/`$_POST` دست‌نخورده می‌ماند.

**دلیل سوم که باید بدانید:** حتی اگر هر دو باگ رفع شوند، این رویکرد **اشتباه** است:
- حذف `$ [ ] { } " '` از **همه‌ی** ورودی‌ها، داده‌ی سالم را خراب می‌کند (رمز عبور حاوی
  `'`، JSON، محتوای متنی).
- `htmlspecialchars` باید هنگام **خروجی** اعمال شود نه ورودی؛ اعمال روی ورودی باعث ذخیره‌ی
  داده‌ی encode‌شده در دیتابیس و double-encoding می‌شود.

**اصلاح پیشنهادی:** این middleware را حذف کنید و به‌جای آن:
- برای XSS → `Html::encode()` هنگام خروجی (بند ۷)
- برای NoSQL Injection → تبدیل نوع صریح: `(string) $input` قبل از قرار دادن در شرط کوئری.
  در MongoDB خطر اصلی وقتی است که ورودی **آرایه** باشد (مثل `username[$ne]=1`)؛ راه‌حل درست
  cast کردن به string است، نه حذف کاراکتر.

---

## ۷. XSS ذخیره‌شده — خروجی خام فیلدهای کاربرمحور

پروژه در ۳۶۸ جا از `Html::encode` استفاده می‌کند (خوب است) ولی حدود **۱۶۷۸** خروجی `<?= ... ?>`
بدون escape دارد. بخشی از آن‌ها عدد و شناسه‌اند و مشکلی ندارند، ولی **۴۶ مورد** مستقیماً
فیلدهای متنی کاربرمحور را خام چاپ می‌کنند. نمونه‌های قطعی:

| فایل | خط | مقدار خام |
|---|---|---|
| `frontend/views/teacher-manage/index.php` | ۱۹۲، ۱۹۳ | `$teacher->first_name` / `$teacher->last_name` |
| `frontend/views/tests-groups/index.php` | ۱۳۸ | `$group->title` |
| `frontend/views/manage-news/index.php` | ۱۶۸ | `$case->title` |
| `frontend/views/collages-manage/index.php` | ۱۱۷ | `$college->logo` داخل `src=""` |

همچنین `frontend/controllers/PackagesController.php` در متد `actionCheck_excel_file`
مقادیر خام سلول‌های اکسل (`$data['A']` تا `$data['G']`) را بدون escape داخل HTML چاپ می‌کند —
یعنی یک فایل اکسل با محتوای `<script>...</script>` در سلول، روی مرورگر اپراتور اجرا می‌شود.

**اصلاح:** همه را به `Html::encode()` تبدیل کنید:
```php
<td><?= Html::encode($teacher->first_name) ?></td>
```
برای `src`/`href` از `Html::a()` و `Html::img()` استفاده کنید که خودشان encode می‌کنند.

---

## ۸. فایل‌های محرمانه مستقیماً از وب قابل دانلودند

همه‌ی آپلودها داخل `frontend/web/` می‌روند و هیچ کنترل دسترسی‌ای ندارند:

| پوشه | محتوا |
|---|---|
| `contract_files/` | قراردادهای دوره |
| `broker_files/` | مدارک نمایندگان |
| `certificate_files/` | فایل‌های مدرک |
| `uploaded_excels/` | **فایل اکسل کاربران شامل نام، موبایل، ایمیل و کد ملی** |

هرکسی که آدرس فایل را داشته باشد (یا حدس بزند) بدون لاگین آن را دانلود می‌کند. برای
`uploaded_excels` این یعنی **نشت داده‌ی هویتی** — کد ملی طبق قانون حفاظت از داده‌های شخصی
داده‌ی حساس محسوب می‌شود.

نام فایل اکسل هم قابل حدس است:
`PackagesController.php` → `$newName = username . '-' . time() . '.xlsx'`
یعنی با دانستن نام کاربری اپراتور و بازه‌ی زمانی، فضای جست‌وجو خیلی کوچک می‌شود.

**اصلاح:** پوشه‌ها را به بیرون از docroot ببرید و از طریق اکشن کنترل‌شده سرو کنید. برای
`uploaded_excels` علاوه بر آن، فایل‌ها را بعد از پردازش **حذف** کنید (الان برای همیشه
می‌مانند).

---

## ۹. PHPExcel نسخه‌ی متروک روی فایل آپلودی کاربر

**تأیید شده:** `phpoffice/phpexcel 1.8.2` (انتشار: نوامبر ۲۰۱۸).

این کتابخانه از سال ۲۰۱۷ رسماً **متروک (abandoned)** اعلام شده و دیگر هیچ وصله‌ی امنیتی
نمی‌گیرد. جانشین رسمی‌اش `phpoffice/phpspreadsheet` است.

خطر مشخص: فایل XLSX در واقع یک ZIP حاوی XML است. نسخه‌های قدیمی PHPExcel در برابر
**XXE (XML External Entity)** آسیب‌پذیرند — یعنی یک فایل اکسل دستکاری‌شده می‌تواند سرور را
وادار به خواندن فایل‌های داخلی یا زدن درخواست به شبکه‌ی داخلی کند. و پروژه‌ی شما دقیقاً
فایل اکسل آپلودی کاربر را با همین کتابخانه پارس می‌کند.

**اصلاح:** مهاجرت به PhpSpreadsheet. مسیر مهاجرت رسمی وجود دارد و API نزدیک است.

---

## ۱۰. jQuery 3.3.1 با CVE‌های XSS

**تأیید شده:** `bower-asset/jquery 3.3.1`.

- **CVE-2020-11022** و **CVE-2020-11023** — XSS در `.html()`, `.append()` و مشابه‌ها.
  در jQuery **3.5.0** رفع شده‌اند.
- CVE-2019-11358 — prototype pollution، در ۳.۴.۰ رفع شده.

این مورد در پروژه‌ی شما تئوریک نیست: الگوی `$('#body').html(main_data.body)` در ده‌ها ویو
تکرار شده (مثلاً `certificate-manage/course-members.php:112`) — یعنی HTML سمت سرور
مستقیماً به `.html()` داده می‌شود.

**اصلاح:** ارتقا به jQuery ≥ ۳.۵.۰ (ترجیحاً ۳.۷.x).

---

## ۱۱. CSRF در یک کنترلر غیرفعال شده

**فایل:** `frontend/controllers/ManageBrokersController.php:90`
```php
$this->enableCsrfValidation = false;
```

یعنی هر سایت خارجی می‌تواند از طرف کاربر واردشده به اکشن‌های این کنترلر درخواست POST بفرستد.

**اصلاح:** اگر برای یک وب‌هوک یا API لازم است، فقط برای همان یک اکشن غیرفعالش کنید نه کل
کنترلر، و به‌جای CSRF یک احراز هویت دیگر (توکن/امضا) بگذارید.

---

## ۱۲. کد ملی به‌عنوان رمز عبور

در فرایند افزودن کاربر از فایل اکسل، **کد ملی کاربر همان رمز عبور اوست**.

**چرا مشکل است:**
- کد ملی آنتروپی بسیار پایینی دارد و در ایران نیمه‌عمومی است.
- بین سیستم‌های مختلف مشترک است — نشت از یک جا، همه‌ی حساب‌ها را باز می‌کند.
- هیچ اجباری برای تغییر آن در اولین ورود وجود ندارد.

**نکته‌ی مثبت:** ذخیره‌سازی رمزها درست است — `Yii::$app->security->generatePasswordHash()`
یعنی bcrypt (`common/models/Admin.php:271`). مشکل فقط سیاست انتخاب رمز است.

**اصلاح:** یک فلگ `must_change_password` بگذارید و کاربر را در اولین ورود به صفحه‌ی تغییر
رمز هدایت کنید.

---

## ۱۲-الف. رمزهای عبور به‌صورت متن ساده در فایل لاگ ذخیره می‌شوند

**فایل:** `frontend/config/main.php:63` → `'logVars' => ['_GET', '_POST']`

هر بار که خطایی رخ دهد، Yii کل `$_POST` را داخل لاگ می‌نویسد — از جمله فرم ورود. نمونه‌ی
واقعی از `frontend/runtime/logs/app.log` (مقدار رمز عمداً حذف شده):

```
$_POST = [
    'AdminLoginForm' => [
        'username' => 'fmut'
        'password' => '<رمز واقعی به صورت متن ساده>'
    ]
]
```

در `app.log` فعلی **۶ مورد** رمز عبور متن‌ساده وجود دارد. این یعنی هر کسی که به فایل لاگ
دسترسی پیدا کند (بکاپ، دسترسی به سرور، یا از طریق بند ۴) رمز عبور واقعی کاربران را دارد —
در حالی که کل زحمت bcrypt دقیقاً برای جلوگیری از همین بود.

**اصلاح:**
```php
'logVars' => ['_GET'],   // یا حذف کامل logVars
```
اگر `_POST` را لازم دارید، Yii امکان ماسک کردن دارد:
```php
'maskVars' => ['_POST.AdminLoginForm.password', '_POST.LoginForm.password'],
```
و **لاگ‌های فعلی را پاک یا rotate کنید** — الان ۷ مگابایت با ۶ رمز داخلش است.

---

## ۱۲-ب. فیلتر لاگ، همه‌ی خطاهای برنامه را دور می‌ریزد

**فایل:** `frontend/config/main.php:64` → `'categories' => ['yii\mongodb\*']`

تنها target لاگ پروژه فقط دسته‌ی `yii\mongodb\*` را قبول می‌کند. یعنی خطاهای PHP،
exception‌های Yii، `ParseError`، `404` و هر چیز دیگری **هیچ‌جا نوشته نمی‌شود**.

**تأیید عملی از روی همین لاگ:**

| نوع خطا | آخرین باری که لاگ شده |
|---|---|
| خطاهای غیر-مونگو (`HttpException`, `ErrorException`, `ParseError`, `Error`) | **۲۰۲۵-۰۴-۱۵** |
| خطاهای مونگو | ۲۰۲۶-۰۸-۲۷ (به‌روز) |

یعنی از فروردین ۱۴۰۴ به بعد، حدود **۱۶ ماه** است که هیچ خطای برنامه‌ای ثبت نمی‌شود.

این توضیح می‌دهد که چرا حالت debug ضروری به نظر می‌رسد: وقتی لاگ خالی است، تنها راه دیدن
خطا همان صفحه‌ی stack trace در مرورگر است.

**اصلاح:** فیلتر دسته را بردارید تا همه‌ی خطاها لاگ شوند:
```php
'targets' => [
    [
        'class'   => 'yii\log\FileTarget',
        'levels'  => ['error', 'warning'],
        'logVars' => ['_GET'],              // ← بند ۱۲-الف
        'logFile' => '@runtime/logs/app.log',
        'maxFileSize' => 10240,
        'maxLogFiles' => 10,
    ],
],
```

---

# 🟡 متوسط

## ۱۳. هیچ محافظتی در برابر حمله‌ی brute-force نیست
در `common/models/LoginForm.php` و `AdminLoginForm.php` هیچ شمارنده‌ی تلاش ناموفق، تأخیر،
قفل موقت حساب یا کپچا وجود ندارد. با توجه به بند ۱۲ (رمزهای قابل حدس)، این ترکیب خطرناک است.
**اصلاح:** محدودیت نرخ بر اساس IP + نام کاربری، قفل موقت بعد از ۵ تلاش، کپچا بعد از ۳ تلاش.

## ۱۴. کوکی سشن بدون `Secure` و `SameSite`
`frontend/config/main.php:53-56` فقط `name` را تنظیم می‌کند.
```php
'session' => [
    'name' => 'userspart',
    'cookieParams' => ['httponly' => true, 'secure' => true, 'samesite' => 'Lax'],
],
'request' => [
    'baseUrl' => '',
    'csrfCookie' => ['httpOnly' => true, 'secure' => true, 'sameSite' => 'Lax'],
],
'user' => [
    'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true, 'secure' => true],
],
```

## ۱۵. `rememberMe` پیش‌فرض روشن با اعتبار ۳۰ روزه
`common/models/LoginForm.php:14` → `public $rememberMe = true;` و خط ۵۹ → `3600 * 24 * 30`.
یعنی هر ورودی به‌صورت پیش‌فرض یک کوکی ۳۰ روزه می‌گیرد، حتی اگر کاربر نخواسته باشد.
**اصلاح:** پیش‌فرض را `false` کنید و مدت را به ۷ روز کاهش دهید.

## ۱۶. MongoDB بدون احراز هویت
`common/config/main-local.php:7` → `'dsn' => 'mongodb://127.0.0.1:27017'` — بدون کاربر و رمز.
اگر روی سرور هم همین‌طور باشد، هر کسی که به سرور دسترسی محدود پیدا کند (مثلاً از طریق بند ۳)
مستقیماً به کل دیتابیس می‌رسد.
**اصلاح:** احراز هویت MongoDB را فعال کنید، کاربر با حداقل دسترسی بسازید، و مطمئن شوید پورت
۲۷۰۱۷ روی اینترنت باز نیست (`bindIp: 127.0.0.1`).

## ۱۷. فایل `index-test.php` در پوشه‌ی وب
`frontend/web/index-test.php` — یک entry script دوم با `YII_DEBUG = true`. با allowlist روی
`127.0.0.1` محافظت شده، ولی دقیقاً مثل بند ۲ پشت reverse proxy این محافظت می‌شکند.
**اصلاح:** روی سرور حذفش کنید.

## ۱۸. نام کاربری هاردکد در قانون دسترسی
`frontend/controllers/CertificateManageController.php:54`
```php
|| Yii::$app->user->identity->username == '09122388496'
```
یک استثنای دائمی برای یک حساب مشخص، بیرون از سیستم نقش‌ها. اگر آن حساب لو برود یا دست
کس دیگری بیفتد، دسترسی ویژه‌اش باقی می‌ماند و در هیچ گزارشی دیده نمی‌شود.
**اصلاح:** به سیستم نقش/دسترسی موجود منتقلش کنید.

## ۱۹. کلید Google Maps داخل سورس
`common/config/main.php:51` → `'key' => 'AIzaSyCfB6...'`
کلید سمت کلاینت است و ذاتاً عمومی می‌شود، ولی حتماً در Google Cloud Console آن را به دامنه‌ی
خودتان محدود کنید، وگرنه هزینه‌اش پای شما نوشته می‌شود.

## ۲۰. وابستگی‌های متروک و قدیمی

| بسته | نسخه‌ی شما | وضعیت |
|---|---|---|
| `yiisoft/yii2` | 2.0.18 (۲۰۱۹) | ← بند ۵ |
| `phpoffice/phpexcel` | 1.8.2 (۲۰۱۸) | متروک از ۲۰۱۷ → بند ۹ |
| `swiftmailer/swiftmailer` | 6.2.1 (۲۰۱۹) | متروک از ۲۰۲۱ → `symfony/mailer` |
| `mongodb/mongodb` | 1.4.2 (۲۰۱۸) | خیلی قدیمی |
| `bower-asset/jquery` | 3.3.1 | ← بند ۱۰ |

`composer.lock` از دسامبر ۲۰۲۳ دست نخورده. ضمناً `composer.json` هنوز `"php": ">=5.4.0"`
اعلام می‌کند در حالی که روی PHP 7.4 اجرا می‌شود (خودِ PHP 7.4 هم از نوامبر ۲۰۲۲ EOL است).

**اصلاح — اول ببینید چه چیزی آسیب‌پذیر است:**
```bash
composer audit
```

## ۲۱. نبود هدرهای امنیتی HTTP
هیچ `.htaccess` یا تنظیم هدری در پروژه نیست. حداقل این‌ها را در وب‌سرور اضافه کنید:
```
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; img-src 'self' data:; ...
```
(CSP را با احتیاط و ابتدا در حالت `Report-Only` اضافه کنید، چون قالب از inline script زیاد
استفاده می‌کند.)

---

# ترتیب پیشنهادی اقدام

### همین امروز (بدون نیاز به تغییر کد)
1. `YII_DEBUG = false` و `YII_ENV = 'prod'` روی سرور — بند ۱
2. حذف Gii و `index-test.php` از سرور — بند ۲ و ۱۷
3. بستن اجرای PHP در پوشه‌های آپلود با تنظیم وب‌سرور — بند ۳
4. تعویض `cookieValidationKey` — بند ۴
5. بررسی اینکه پورت MongoDB روی اینترنت باز نیست — بند ۱۶

### این هفته
6. اصلاح `actionFile` (path traversal) — بند ۴
7. اضافه کردن ولیدیشن `extensions` به همه‌ی آپلودها — بند ۳
8. حذف middleware بی‌اثر و `Html::encode` روی ۴۶ خروجی خام — بند ۶ و ۷
9. برگرداندن CSRF در `ManageBrokersController` — بند ۱۱
10. تنظیم `secure` و `sameSite` روی کوکی‌ها — بند ۱۴

### این ماه
11. ارتقای Yii به ۲.۰.۵۳+ (اول روی staging) — بند ۵
12. ارتقای jQuery به ۳.۷ — بند ۱۰
13. مهاجرت PHPExcel به PhpSpreadsheet — بند ۹
14. بیرون بردن پوشه‌های آپلود از docroot + کنترل دسترسی روی دانلود — بند ۸
15. اجبار تغییر رمز در اولین ورود — بند ۱۲
16. محافظت brute-force روی لاگین — بند ۱۳

---

## چیزهایی که این بررسی پوشش نداده

برای شفافیت، این موارد بررسی نشده‌اند و ممکن است یافته‌های بیشتری داشته باشند:

- **پیکربندی واقعی سرور** (وب‌سرور، فایروال، دسترسی فایل‌ها، TLS) — دسترسی نداشتم.
- **تست نفوذ زنده** — این فقط بازبینی کد بود، هیچ اکسپلویتی روی سیستم زنده اجرا نشد.
- **بررسی خط‌به‌خط همه‌ی ۴۵ کنترلر** — روی الگوهای پرخطر تمرکز کردم، نه پوشش کامل.
- **منطق کسب‌وکار** (مثلاً آیا کاربر می‌تواند به دوره‌ای که پولش را نداده دسترسی پیدا کند).
- **NoSQL Injection** — الگوهای واضح پیدا نشد، ولی برای تأیید قطعی باید همه‌ی کوئری‌های
  MongoDB که ورودی کاربر می‌گیرند جداگانه بررسی شوند.
- **کنترل دسترسی افقی** (آیا کاربر A می‌تواند با تغییر `_id` در URL به داده‌ی کاربر B برسد) —
  این معمولاً پرتکرارترین ایراد در چنین پروژه‌هایی است و ارزش یک بررسی جداگانه را دارد.
