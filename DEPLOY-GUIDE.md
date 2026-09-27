# راهنمای انتقال تغییرات به سرور

> **تاریخ تهیه:** ۱۴۰۵/۰۶/۱۲ (۲۰۲۶-۰۹-۰۳)
> **سرور:** `eec1.ut.ac.ir` — مسیر `/var/www/admin`
> **روش مورد نظر شما:** جایگزینی کل محتویات `frontend/` به‌جز `frontend/web/`

---

# ۱. ⚠ چهار چیزی که هرگز نباید جایگزین شوند

اگر «همه‌ی `frontend/` به‌جز `web/`» را کپی کنید، این چهار مورد خراب می‌شوند. **این مهم‌ترین
بخش این سند است.**

## ۱-۱. `frontend/config/main-local.php` — 🔴 Gii را برمی‌گرداند

نسخه‌ی **محلی** این فایل هنوز بلوک Gii را دارد:

```php
if (!YII_ENV_TEST) {
    $config['bootstrap'][] = 'gii';      // ← هنوز اینجاست
    $config['modules']['gii'] = [ ... ];
}
```

چون اصلاح Gii را مستقیماً **روی سرور** با `sed` انجام دادید و به نسخه‌ی محلی نیاوردید.
اگر این فایل کپی شود، `/gii` دوباره برای همه‌ی اینترنت باز می‌شود.

**مشکل دوم همین فایل:** `cookieValidationKey` محلی با سروری فرق دارد. جایگزینی یعنی همه‌ی
کاربران یک‌باره logout می‌شوند.

## ۱-۲. `frontend/protected_folders/` — 🔴 حذف مدارک واقعی کاربران

این پوشه فایل‌های آپلودشده‌ی واقعی است (مدارک نمایندگان)، نه کد:

```
frontend/protected_folders/broker_files/657cca7da1e9c.jpg
frontend/protected_folders/broker_files/657cc53286d12.pdf
...
```

نسخه‌ی محلی **۲۳ فایل (۴.۱ مگابایت)** دارد. سرور تقریباً قطعاً خیلی بیشتر دارد. جایگزینی
یعنی **حذف دائمی مدارکی که در نسخه‌ی محلی شما نیستند**. با alias `@protectFolder` در
`common/config/main.php` هم به این مسیر ارجاع داده می‌شود.

## ۱-۳. `frontend/runtime/` — لاگ‌ها و کش سرور

۳۰۳ فایل محلی (لاگ‌های توسعه، کش، فایل‌های session). جایگزینی یعنی:
- لاگ‌های سرور پاک می‌شوند (همان‌هایی که برای بررسی حادثه لازم بودند)
- کش و session‌های فعال به‌هم می‌ریزند

## ۱-۴. فایل‌های `.bak` — ۵۲ فایل

در `frontend/controllers/`, `frontend/models/` و `frontend/views/` مجموعاً **۵۲ فایل
`.bak-*`** وجود دارد (بکاپ‌هایی که قبل از هر تغییر گرفته شده). این‌ها نباید روی سرور بروند —
حجم اضافه و نسخه‌های قدیمی سورس روی سرور تولید.

همچنین فایل‌های `.DS_Store` (زباله‌ی macOS).

---

# ۲. 🔴 قبل از هر کاری: تغییرات فقط-سروری را از دست ندهید

خودتان گفتید «یک سری تغییرات را مستقیم روی سرور اعمال کردم». اگر `frontend/` را
یکجا جایگزین کنید، **هر تغییری که فقط روی سرور است از بین می‌رود.**

قبل از انتقال، حتماً تفاوت‌ها را ببینید:

```bash
# روی مک خودتان
rsync -avn --delete \
  --exclude 'web/' --exclude 'runtime/' --exclude 'protected_folders/' \
  --exclude 'config/main-local.php' --exclude '*.bak*' --exclude '.DS_Store' \
  ~/sites/eec/frontend/  adminuser@srvhp110:/var/www/admin/frontend/
```

`-n` یعنی **dry-run**: چیزی منتقل نمی‌کند، فقط فهرست می‌کند چه چیزی عوض می‌شد. این فهرست را
بخوانید. اگر فایلی دیدید که انتظارش را نداشتید، اول روی سرور بررسی‌اش کنید.

برای مقایسه‌ی دقیق یک فایل مشکوک:
```bash
ssh adminuser@srvhp110 "cat /var/www/admin/frontend/controllers/X.php" | diff - ~/sites/eec/frontend/controllers/X.php
```

---

# ۳. مراحل انتقال

## گام ۱ — بکاپ کامل روی سرور

```bash
sudo tar czf /root/backup-frontend-$(date +%F-%H%M).tar.gz -C /var/www/admin frontend
ls -lh /root/backup-frontend-*.tar.gz
```

## گام ۲ — ساخت پوشه‌ی جدید `components`

فایل جدید `SecureFile.php` در پوشه‌ای است که **روی سرور وجود ندارد**:

```bash
sudo mkdir -p /var/www/admin/frontend/components
```

## گام ۳ — انتقال (همان دستور بالا، بدون `-n`)

```bash
rsync -av \
  --exclude 'web/' --exclude 'runtime/' --exclude 'protected_folders/' \
  --exclude 'config/main-local.php' --exclude '*.bak*' --exclude '.DS_Store' \
  ~/sites/eec/frontend/  adminuser@srvhp110:/var/www/admin/frontend/
```

**عمداً از `--delete` استفاده نکنید** — با آن، هر فایلی که روی سرور هست و در نسخه‌ی محلی
نیست حذف می‌شود.

## گام ۴ — تصحیح مالکیت فایل‌ها

```bash
ssh adminuser@srvhp110
sudo chown -R www-data:www-data /var/www/admin/frontend
sudo find /var/www/admin/frontend -type d -exec chmod 755 {} \;
sudo find /var/www/admin/frontend -type f -exec chmod 644 {} \;
```

(اگر کاربر وب‌سرورتان `www-data` نیست، با `ps aux | grep apache` ببینید چیست.)

---

# ۴. تست بعد از انتقال — به همین ترتیب

## ۴-۱. سایت اصلاً بالا می‌آید؟
```bash
curl -s -o /dev/null -w "%{http_code}\n" "https://eec1.ut.ac.ir/"
```
`200` یا `302` = خوب. `500` = مشکل، برو سراغ بخش rollback.

## ۴-۲. Gii هنوز بسته است؟ (تأیید اینکه `main-local.php` جایگزین نشده)
```bash
for p in gii gii1; do printf "/%-5s -> " "$p"; curl -s -o /dev/null -w "%{http_code}\n" "https://eec1.ut.ac.ir/$p"; done
```
هر دو باید `404` بدهند.

## ۴-۳. Path traversal بسته شد؟ (اصلاح اصلی این دور)
```bash
curl -s -o /dev/null -w "%{http_code}\n" \
  "https://eec1.ut.ac.ir/certificate-manage/file?filename=../../config/main-local.php"
```
نباید محتوای فایل را بدهد.

## ۴-۴. دانلودهای واقعی سالم‌اند؟
از داخل پنل، **دستی** این‌ها را تست کنید:
- دانلود یک مدرک تحصیلی از صفحه‌ی مدیریت مدارک
- دانلود یک فایل از مرکز آپلود (`upload_center`)
- دانلود یک فایل قرارداد

اگر هر کدام `500` داد، یعنی `SecureFile.php` منتقل نشده (گام ۲ و ۳ را چک کنید).

## ۴-۵. کد ملی اساتید ماسک شده؟
صفحه‌ی «مدیریت اساتید» را باز کنید. ستون کد ملی باید `••••••1234` باشد.

## ۴-۶. لاگ خطا تمیز است؟
```bash
sudo tail -50 /var/log/apache2/error.log
sudo tail -50 /var/www/admin/frontend/runtime/logs/app.log
```

---

# ۵. چه چیزی در این دور تغییر کرد و چرا

| فایل | تغییر | دلیل |
|---|---|---|
| `components/SecureFile.php` | **فایل جدید** | helper مشترک برای امن‌سازی مسیر فایل |
| `controllers/CertificateManageController.php` | `actionFile` | Path traversal |
| `controllers/ManageBrokersController.php` | `actionFile` | Path traversal |
| `controllers/ManageCourseContentsController.php` | ۳ اکشن | Path traversal |
| `controllers/PackagesController.php` | ۲ اکشن + ولیدیشن اکسل | Path traversal + کنترل فایل اکسل |
| `controllers/UploadCenterController.php` | `actionFile` | Path traversal |
| `views/teacher-manage/index.php` | ماسک کد ملی | رمز عبور اساتید در فهرست دیده می‌شد |
| `views/certificate-manage/course-members.php` | آدرس AJAX | مودال روی محیط محلی باز نمی‌شد |

**خارج از `frontend/` (جداگانه منتقل شود):**

| فایل | تغییر | وضعیت |
|---|---|---|
| `common/config/main.php` | حذف ماژول `gii1` | ✅ قبلاً منتقل شده (۰۳ سپتامبر ۰۸:۳۶) |

شرح کامل هر تغییر با دلیل و نتیجه‌ی تست‌ها در `AI-CHANGES-LOG.md` است.

---

# ۶. کارهایی که هنوز انجام نشده

این‌ها در `SECURITY-AUDIT-2026-08-29.md` مستند شده‌اند و **در این انتقال نیستند**:

| مورد | اولویت |
|---|---|
| رمزهای متن‌ساده در `app.log` (`logVars => ['_GET','_POST']`) | 🔴 بالا |
| فیلتر لاگ که ۱۶ ماه است خطاها را دور می‌ریزد | 🔴 بالا |
| ولیدیشن پسوند فایل در آپلودها (هیچ ولیدیتوری وجود ندارد) | 🔴 بالا |
| رمز عبور = کد ملی برای همه‌ی حساب‌ها | 🔴 بالا |
| نبود محافظت brute-force روی لاگین | 🟠 متوسط |
| `mod_remoteip` (همه‌ی لاگ‌ها `127.0.0.1` ثبت می‌شوند) | 🟠 متوسط |
| ارتقای Yii از 2.0.18 | 🟠 متوسط |
| تعویض `cookieValidationKey` | بعد از رفع path traversal |

---

# ۷. برگرداندن (Rollback)

اگر بعد از انتقال چیزی خراب شد:

```bash
ssh adminuser@srvhp110
sudo mv /var/www/admin/frontend /var/www/admin/frontend.broken-$(date +%F-%H%M)
sudo tar xzf /root/backup-frontend-<تاریخ>.tar.gz -C /var/www/admin
sudo chown -R www-data:www-data /var/www/admin/frontend
curl -s -o /dev/null -w "%{http_code}\n" "https://eec1.ut.ac.ir/"
```

پوشه‌ی `frontend.broken-*` را نگه دارید تا بشود فهمید چه چیزی مشکل داشت.

---

# ۸. پیشنهاد برای دفعات بعد

الان هیچ سیستم کنترل نسخه‌ای ندارید (`git` نیست). به همین دلیل است که:
- تغییرات سروری و محلی از هم جدا افتاده‌اند
- ۵۲ فایل `.bak` به‌عنوان جایگزین دستی نسخه‌بندی ساخته شده
- نمی‌شود مطمئن گفت سرور و لوکال دقیقاً چقدر فرق دارند

با `git init` (حتی بدون سرور راه دور) این مشکلات حل می‌شود و `.gitignore` هم دقیقاً همان
چیزهایی را کنار می‌گذارد که در بخش ۱ فهرست شد. اگر خواستید، راه‌اندازی‌اش سریع است.
