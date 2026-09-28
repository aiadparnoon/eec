# EEC panel — University of Tehran

Yii2 advanced template, PHP 7.4, **MongoDB** (yii2-mongodb ActiveRecord). Only the `frontend` app is active.
UI theme: Sneat-style Bootstrap 5 RTL (Persian), files in `frontend/web/assets/{vendor,css,js,img}`. Keep this theme; make pages look more professional, don't switch UI kits.

## Working rules
- Redesign goes module by module. Specs live in `docs/specs/`. Read the relevant spec before coding.
- One branch + one PR per module. Never commit to `main` directly.
- UI text is Persian. Code comments may be Persian or English, match the surrounding file.
- Secrets live only in `*-local.php` (gitignored). Never hardcode credentials. Adobe Connect creds: aliases `@adobe_user`, `@adobe_password`; payment gateway key: `@payment_api_key` (read via `PaymentConfig::apiKey()`).
- Run `php init` (Development) to generate local config files in a fresh checkout; MongoDB DSN goes in `common/config/main-local.php`.

## Domain basics
- `users` collection = students (دانشپذیر) only.
- `admin` collection = staff. Roles: `user` and `cnt` = system admins (see everything), `emp` = college staff (کارشناس دانشکده), `broker` = کارگزار, `teacher`.
- Access check helper pattern: `allow($college)` in `ManageCourseContentsController`.
- Online classes currently use Adobe Connect via API at `@baseUrl` (`api-eec.ut.ac.ir`). BigBlueButton support is planned: don't hardwire new code to Adobe.

## Shell notes
- Local git is 2.23 (no `git init -b`, no `--cached` diff quirks). In the maintainer's zsh, `head` resolves to Perl HEAD: use `sed -n`.
