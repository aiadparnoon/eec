# Spec: مدیریت کاربران (دانشپذیران) — `users-manage`

Status: approved by maintainer on 2026-09-27, except broker access (§1.3), which needs a final decision.

## 1. Access
1. Roles `user` and `cnt` (admins) see and act on all students. (Finer-grained admin permissions come later with the staff module.)
2. College staff see only students whose `users.college` array contains one of their college ids.
3. **Broker (proposal, confirm with maintainer before coding):** a broker sees students enrolled in at least one course whose `courses.broker` includes that broker; in the profile they see only those courses. Map `admin` (role broker) → `brokers` record (existing code matches on mobile, e.g. `connector_info.mobile`). Verify the mapping.
4. The same rule is enforced in **every action** (edit, change password, status, course status, re-register), not only the list. Today any staff member can modify any user by posting another `_id` (IDOR). Fix this.

## 2. Data migration: `users.college` → array
- Console migration converting string values to `[value]`; null/empty → `[]`. Idempotent. Test locally first; maintainer runs it on the server.
- Update every writer: e.g. `PackagesController` enrollment currently sets `$model->college = $course->college`. It must append the college id to the array if missing.
- Update every reader that compares `college` as a string.

## 3. List page
- Columns: name, username, colleges, registrant (name + role), status, number of courses. Row click → profile page.
- Re-enable the status filter (commented out in `UsersSearch`).
- Performance: one shared modal filled per row via JS (not 4 modals × 50 rows); load registrants with one `$in` query per page instead of per row.
- Excel export keeps working.

## 4. Add user
### Single: existing form, plus college assignment (the current staff member's college).
### Excel import (fixed format given to all colleges)
| col | field | required | validation |
|---|---|---|---|
| A | first name | yes | not empty |
| B | last name | yes | not empty |
| C | username | yes | valid Iranian mobile (09xxxxxxxxx) **or** valid email; lowercase |
| D | password (usually national code) | yes | not empty |
| E | English first name | no | Latin letters if present |
| F | English last name | no | Latin letters if present |
| G | gender | yes | `1` = male, `2` = female |

- Preview step: table with invalid cells highlighted and a message per row/column ("ردیف ۱۲، ستون C: شماره موبایل معتبر نیست"). The confirm button stays disabled while errors exist. Also flag duplicates inside the file.
- **Existing username:** do not report it as an error. Add the importing college id to that user's `college` array. The final report lists "new users created" and "existing users added to your college" separately.
- Store E/F/G in `issuance_certificate_information` (`first_name_en`, `last_name_en`, `gender`). Gender mapping: input `1` → stored `'1'`, input `2` → stored `'0'` (certificate printing treats `'0'` as female).
- Delete the uploaded file right after processing. Don't store it under the public `web/` dir (currently `web/uploaded_excels`, holds plaintext passwords).
- Max size 10 MB (the `@maxFileSize` alias is 100 KB today while the message says 10 MB). Check the extension using the last dot.
- Fix the form action typo `add_user_from_excel` vs action `add_user_from_exel`.

## 5. Profile page (new) — layout like the maintainer's screenshot
Side card: avatar, full name, badge «دانشپذیر», stats: course count, total paid, cheques awaiting approval.
Tabs (lazy-loaded via AJAX):
1. **مشخصات:** username, first/last name (fa/en), national code (`issuance_certificate_information.id`), status, shsh, province, city, birth date, father name, gender, colleges, registrant. Actions: edit, change password, activate/deactivate.
2. **دوره‌ها:** one collapsible card per `users.courses[]` item (`_id` = course id, `status`: `'0'` = not yet registered on the class platform / failed, `'1'` = active, `'2'` = inactive, `registrant`):
   - Status in course + toggle (reuse the logic of `packages/change_status`, including the Adobe remove call).
   - Online-class platform status. For status `'0'`: button «ثبت مجدد» calling `@baseUrl/adobe-connect/add-users-courses/{courseId}` (course-wide for now; a per-user endpoint may come later). Build this behind a platform abstraction: BigBlueButton is coming, and its attendance works differently.
   - Attendance: `course_sessions` (per course+lesson) → `sessions[]` {from, to, participants[{_id: principal_id, name, enter, exit}]}. Match on the user's `principal_id` (see `Principals`). Show present (enter/exit) / absent per session. Reference view: `manage-course-contents/teacher-part.php`.
   - Certificate: `certificate_requests` by username + course_id. Status `1` در انتظار بررسی دانشکده, `2` تائید دانشکده در انتظار صدور, `3` رد دانشکده, `4` صادر شده, `5` رد صدور مدرک. Show `request`, `serial_number`. Button «صدور مدرک دیجیتال» disabled with «به‌زودی» (web service not ready). Reference: `certificate-manage/course-members.php`.
   - Finance: `orders` (orders[]._id = course, `payment_info` {order_id, tref (#…), date}, `payments`, `prepayment_settlement`, `settlement_payment`, `shares[0]` {college, broker, broker_contract_percent}, `is_pos`), wallet vs gateway, `installments.maturities[]` {amount, date, deadline, status, payment_info, serial}, `is_cheque`. Show payment type (cash/installment/wallet/gateway/cheque), date, order no., tracking no., college share vs broker share, installment table (paid / overdue / not due). Reference: `ReportingController::actionReport` and `actionInstallments_report`.
   - Exams / assignments / surveys: `user_tests` (username), `user_assignments` (user_id, downloadable via `manage-course-contents/download_exercise`), `user_exams` (surveys, `exams.type = '2'`). Reference: `manage-course-contents/manage-lesson.php`, `exam_report`, `poll_report`.
3. **پرداختی‌ها:** all payments across courses.
4. **چک‌ها:** installments paid by cheque.
5. **مدارک:** `issuance_certificate_information.id_file`, `degree_education_file`.

## 6. Other fixes
- `edit_user`: Adobe call must time out (e.g. 15s) and fail gracefully with a Persian flash message («ارتباط با سرور Adobe برقرار نشد، تغییرات ذخیره نشد»). Today a null response causes a PHP error. Build the JSON with `json_encode`, not string concatenation.
- `change_password` also updates the matching mentor `admin` record. Keep that behaviour.

## Implementation order
access → migration → list → excel → profile. Each step is testable on its own.
