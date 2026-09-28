# Server snapshot 2026-09-28: how to merge it

Branch `server-sync` = `main` (old local baseline) + one commit that copies the **production server's**
`frontend/controllers` and `frontend/views` over it (from `newArchive.zip`; all other files are unchanged on the server).
Only one change: the hardcoded Adobe password in `ManageCourseContentsController::actionGoToArchive` was replaced by the
`@adobe_user` / `@adobe_password` aliases, the same way as on `main`.

## ⚠️ Do NOT merge this branch as-is
The differences go **both ways**. The local baseline has security and bug fixes (Aug 27 to Sep 3, see `AI-CHANGES-LOG.md`,
`SECURITY-AUDIT-2026-08-29.md`) that the server never got. The server has newer features that local doesn't have.
A plain merge would revert the local fixes. Port the **server-only changes** hunk by hunk into the redesign branches
(`claude/short-courses` and the branches it builds on), keeping every local security fix.

Use `git diff main server-sync -- <file>` and judge each hunk.

## Triage (server file date vs local)
**Likely genuine server changes: port these.**
| file | server date | notes |
|---|---|---|
| controllers/CertificateManageController.php | 2026-09-27 | digital-certificate status handling (flash `8`) etc. |
| views/certificate-manage/course-members.php | 2026-09-26 | flash «وضعیت صدور گواهی دیجیتال تغییر یافت». Keep LOCAL relative `createUrl` for the AJAX URL |
| views/certificate-manage/print-all-certificate.php | 2026-09-27 | prints only users with complete certificate info; `$users` are now user docs (no `user_detail()` lookup) |
| views/certificate-manage/new-print-all-certificate.php | 2026-09-27 | same `$users` shape change |
| views/certificate-manage/_member_search.php | 2026-09-28 | new search fields |
| views/reporting/__search.php | 2026-09-14 | one-line `InstallmentsSearch[college]` condition |
| controllers/CoursesController.php | 2026-09-23 | check each hunk |
| controllers/DashboardController.php | 2026-08-30 | check each hunk |
| controllers/PackagesController.php | 2026-08-30 | large diff, mostly local fixes. Find server-only hunks carefully |
| views/courses/index.php | 2026-08-16 | check each hunk |

**Server file is older than the local edits: keep local, just verify nothing server-only is lost.**
UploadCenterController (2024-02), ManageBrokersController (2026-01), ManageCourseContentsController (2026-06),
views: packages/* (2025-09 to 2026-06), lessons/index (2026-01), collages-manage/* (2023/2026-01), teacher-manage/index (2024-08),
dashboard/index (2026-01), courses/_search (2026-06), courses/edit-course (2026-06), layouts/main (2026-05).

## Known secret already in git
The payment gateway `api_key` is hardcoded in `PackagesController` (2×) and `WalletController` on `main` and on every branch.
Move it to a `@payment_api_key` alias in `common/config/main-local.php`. The maintainer should rotate the key.
