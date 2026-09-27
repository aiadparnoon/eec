<?php
/**
 * پروفایل دانشپذیر — docs/specs/users-manage.md بند ۵
 * کارت کناری + تب‌هایی که با AJAX و فقط در اولین نمایش بارگذاری می‌شوند.
 *
 * @var $this yii\web\View
 * @var $student app\models\Users
 * @var $profile app\components\StudentProfile
 * @var $registrant array
 */
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\models\Users;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$fullName = trim($student->first_name . ' ' . $student->last_name) ?: $student->username;
$this->title = 'پروفایل ' . $fullName;
echo $this->render('_flash');

$id = (string) $student->_id;
$info = is_array($student->issuance_certificate_information) ? $student->issuance_certificate_information : [];
$inactive = $student->status == Users::STATUS_INACTIVE;
$collegeNames = UsersDirectory::collegeNames($student->college);
$tabs = [
    'info' => ['مشخصات', 'bx-user'],
    'courses' => ['دوره‌ها', 'bx-book-open'],
    'payments' => ['پرداختی‌ها', 'bx-credit-card'],
    'cheques' => ['چک‌ها', 'bx-receipt'],
    'documents' => ['مدارک', 'bx-folder'],
];
$tabUrl = Json::htmlEncode(Url::to(['profile_tab', 'id' => $id]));
$storageKey = Json::htmlEncode('users-profile-tab-' . $id);

$this->registerJs(<<<JS
(function () {
    var loaded = {};
    function load(tab) {
        if (loaded[tab]) return;
        loaded[tab] = true;
        var pane = $('#tab-' + tab);
        $.get($tabUrl, {tab: tab})
            .done(function (html) { pane.html(html); })
            .fail(function () {
                loaded[tab] = false;
                pane.html('<div class="alert alert-danger mb-0">بارگذاری این بخش با خطا مواجه شد. <a href="#" class="js-retry" data-tab="' + tab + '">تلاش دوباره</a></div>');
            });
    }
    $(document).on('click', '.js-retry', function (e) { e.preventDefault(); load($(this).data('tab')); });
    $('#profile-tabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        var tab = $(this).data('tab');
        load(tab);
        try { sessionStorage.setItem($storageKey, tab); } catch (e) {}
    });
    var initial = (window.location.hash || '').replace('#', '');
    try { initial = initial || sessionStorage.getItem($storageKey) || ''; } catch (e) {}
    var btn = $('#profile-tabs button[data-tab="' + initial + '"]');
    if (btn.length && initial !== 'info') { bootstrap.Tab.getOrCreateInstance(btn[0]).show(); } else { load('info'); }
})();
JS
);
$this->registerCss(<<<CSS
.profile-avatar { width: 96px; height: 96px; font-size: 2rem; }
.profile-details li { display: flex; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px dashed rgba(67, 89, 113, .12); }
.profile-details li:last-child { border-bottom: 0; }
.profile-details .label { color: #a1acb8; white-space: nowrap; }
.profile-tab-loading { min-height: 220px; display: flex; align-items: center; justify-content: center; }
CSS
);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="<?= Url::to(['index']) ?>">مدیریت دانشپذیران</a></li>
            <li class="breadcrumb-item active"><?= Html::encode($fullName) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- کارت کناری -->
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center pt-2">
                        <div class="avatar profile-avatar mb-3">
                            <span class="avatar-initial rounded-circle bg-label-<?= $inactive ? 'secondary' : 'primary' ?>"><?= Html::encode(UsersDirectory::initials($student->first_name, $student->last_name)) ?></span>
                        </div>
                        <h4 class="mb-2"><?= Html::encode($fullName) ?></h4>
                        <div class="d-flex gap-2 mb-1">
                            <span class="badge bg-label-primary"><?= $student->role == 'mentor' ? 'دستیار استاد' : 'دانشپذیر' ?></span>
                            <span class="badge bg-label-<?= $inactive ? 'danger' : 'success' ?>"><?= $inactive ? 'غیرفعال' : 'فعال' ?></span>
                        </div>
                        <small class="text-muted" dir="ltr"><?= Html::encode($student->username) ?></small>
                    </div>

                    <div class="d-flex justify-content-around flex-wrap my-4 gap-3 text-center">
                        <div>
                            <span class="badge bg-label-primary p-2 rounded mb-1"><i class="bx bx-book-open bx-sm"></i></span>
                            <h5 class="mb-0"><?= UsersImport::faDigits(count($profile->courses())) ?></h5>
                            <small class="text-muted">دوره</small>
                        </div>
                        <div>
                            <span class="badge bg-label-success p-2 rounded mb-1"><i class="bx bx-wallet bx-sm"></i></span>
                            <h5 class="mb-0"><?= UsersImport::faDigits(number_format($profile->totalPaid())) ?></h5>
                            <small class="text-muted">جمع پرداختی (تومان)</small>
                        </div>
                        <div>
                            <span class="badge bg-label-warning p-2 rounded mb-1"><i class="bx bx-receipt bx-sm"></i></span>
                            <h5 class="mb-0"><?= UsersImport::faDigits($profile->pendingChequesCount()) ?></h5>
                            <small class="text-muted">چک در انتظار</small>
                        </div>
                    </div>

                    <h6 class="pb-2 border-bottom mb-2">اطلاعات کلی</h6>
                    <ul class="list-unstyled profile-details mb-4">
                        <li><span class="label">کد ملی</span><span><?= !empty($info['id']) ? Html::encode($info['id']) : '<span class="badge bg-label-warning">ثبت نشده</span>' ?></span></li>
                        <li><span class="label">دانشکده‌ها</span>
                            <span class="text-end">
                                <?php if (empty($collegeNames)): ?>—<?php endif; ?>
                                <?php foreach ($collegeNames as $name): ?><span class="badge bg-label-primary mb-1"><?= Html::encode($name) ?></span> <?php endforeach; ?>
                            </span>
                        </li>
                        <li><span class="label">ثبت کننده</span><span class="text-end"><?= Html::encode($registrant['name']) ?><?php if ($registrant['roleLabel'] !== ''): ?><small class="d-block text-muted"><?= Html::encode($registrant['roleLabel']) ?></small><?php endif; ?></span></li>
                        <li><span class="label">تاریخ ثبت</span><span><?= UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))) ?></span></li>
                        <li><span class="label">حساب کلاس آنلاین</span><span><?= $student->principal_id ? '<span class="badge bg-label-success">دارد</span>' : '<span class="badge bg-label-secondary">ندارد</span>' ?></span></li>
                    </ul>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary js-edit-user" data-bs-toggle="modal" data-bs-target="#edit-user" data-id="<?= $id ?>" data-first="<?= Html::encode($student->first_name) ?>" data-last="<?= Html::encode($student->last_name) ?>">
                            <i class="bx bx-edit me-1"></i>ویرایش مشخصات
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-label-secondary flex-fill js-change-password" data-bs-toggle="modal" data-bs-target="#change-password" data-id="<?= $id ?>" data-name="<?= Html::encode($fullName) ?>">
                                <i class="bx bx-lock-alt me-1"></i>تغییر رمز
                            </button>
                            <button type="button" class="btn btn-label-<?= $inactive ? 'success' : 'danger' ?> flex-fill" data-bs-toggle="modal" data-bs-target="#toggle-status">
                                <i class="bx <?= $inactive ? 'bx-user-check' : 'bx-user-x' ?> me-1"></i><?= $inactive ? 'فعال‌سازی' : 'غیرفعال‌سازی' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- تب‌ها -->
        <div class="col-xl-8 col-lg-7">
            <ul class="nav nav-pills flex-wrap mb-3 gap-1" id="profile-tabs" role="tablist">
                <?php $first = true;
                foreach ($tabs as $key => $tab): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $first ? 'active' : '' ?>" id="tab-btn-<?= $key ?>" data-bs-toggle="tab" data-bs-target="#tab-<?= $key ?>" data-tab="<?= $key ?>" type="button" role="tab" aria-controls="tab-<?= $key ?>" aria-selected="<?= $first ? 'true' : 'false' ?>">
                            <i class="bx <?= $tab[1] ?> me-1"></i><?= Html::encode($tab[0]) ?>
                        </button>
                    </li>
                    <?php $first = false;
                endforeach; ?>
            </ul>
            <div class="tab-content p-0 bg-transparent shadow-none">
                <?php $first = true;
                foreach ($tabs as $key => $tab): ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= $key ?>" role="tabpanel" aria-labelledby="tab-btn-<?= $key ?>">
                        <div class="card"><div class="card-body profile-tab-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">در حال بارگذاری…</span></div></div></div>
                    </div>
                    <?php $first = false;
                endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- تأیید تغییر وضعیت -->
<div class="modal fade" id="toggle-status" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['change_status'], 'post') ?>
            <div class="modal-body text-center pt-4">
                <i class="bx <?= $inactive ? 'bx-user-check text-success' : 'bx-user-x text-danger' ?>" style="font-size: 3rem"></i>
                <p class="mt-3 mb-0"><?= $inactive ? 'حساب ' . Html::encode($fullName) . ' فعال شود؟' : 'حساب ' . Html::encode($fullName) . ' غیرفعال شود؟ دانشپذیر دیگر نمی‌تواند وارد سامانه شود.' ?></p>
                <?= Html::hiddenInput('Users[_id]', $id) ?>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-<?= $inactive ? 'success' : 'danger' ?>"><?= $inactive ? 'فعال‌سازی' : 'غیرفعال‌سازی' ?></button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<?= $this->render('_modals', ['showAdd' => false]) ?>
