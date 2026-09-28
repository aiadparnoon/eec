<?php
/**
 * جدول اعضای دوره — مشترک کوتاه‌مدت و میان‌مدت. عملیات هر عضو با یک مودال تأیید مشترک (بدون مودال جدا برای
 * هر ردیف) انجام می‌شود و همه‌ی متن‌ها encode می‌شوند.
 *
 * @var $this yii\web\View
 * @var $course app\models\Courses
 * @var $dataProvider yii\data\ActiveDataProvider
 */
use app\components\CourseAccess;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\components\classroom\ClassroomPlatforms;
use frontend\controllers\CourseMembersController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$fa = function ($n) { return UsersImport::faDigits($n); };
$courseId = (string) $course->_id;
$members = $dataProvider->getModels();
$canManage = CourseAccess::canManage($course);
$isAdmin = CourseAccess::isAdmin();
$online = ClassroomPlatforms::hasOnlineClass($course);
$pagination = $dataProvider->getPagination();
$offset = $pagination ? $pagination->getOffset() : 0;

// ثبت‌کننده‌ها با یک کوئری
$registrantNames = [];
foreach ($members as $member) {
    $entry = CourseMembersController::entryOf($member->courses, $courseId);
    if (isset($entry['registrant']))
        $registrantNames[] = $entry['registrant'];
}
$registrants = UsersDirectory::registrants($registrantNames);
$statuses = [
    '1' => ['فعال', 'success'],
    '2' => ['غیرفعال', 'secondary'],
    '0' => [$online ? 'ثبت‌نشده در کلاس' : 'در انتظار', 'warning'],
];

$financeUrl = Json::htmlEncode(Url::to(['course-members/finance', '_id' => $courseId]));
$this->registerJs(<<<JS
$(document).on('click', '.js-member-action', function (e) {
    e.preventDefault();
    var btn = $(this), modal = $('#member-action-modal');
    modal.find('form').attr('action', btn.data('url'));
    modal.find('.js-member-id').val(btn.data('member'));
    modal.find('.modal-title').text(btn.data('title'));
    modal.find('.js-message').text(btn.data('message'));
    modal.find('button[type=submit]').attr('class', 'btn ' + (btn.data('danger') ? 'btn-danger' : 'btn-primary'));
    bootstrap.Modal.getOrCreateInstance(modal[0]).show();
});
$(document).on('click', '.js-member-finance', function (e) {
    e.preventDefault();
    var modal = $('#member-finance-modal');
    modal.find('.modal-title').text('در حال دریافت...');
    modal.find('.modal-body').html('<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>');
    bootstrap.Modal.getOrCreateInstance(modal[0]).show();
    $.post($financeUrl, {member: $(this).data('member'), _csrf: yii.getCsrfToken()}).done(function (res) {
        modal.find('.modal-title').text(res.title || 'اطلاعات مالی');
        modal.find('.modal-body').html(res.html || '');
    }).fail(function () {
        modal.find('.modal-title').text('اطلاعات مالی');
        modal.find('.modal-body').text('دریافت اطلاعات ممکن نشد');
    });
});
JS
, \yii\web\View::POS_END);
?>
<?php if ($canManage && $online): ?>
    <div class="d-flex justify-content-end mb-2">
        <?= Html::beginForm(['course-members/register-class', '_id' => $courseId], 'post', ['class' => 'd-inline']) ?>
            <button class="btn btn-sm btn-label-warning"><i class="bx bx-refresh me-1"></i>ثبت اعضای «ثبت‌نشده در کلاس» در کلاس آنلاین</button>
        <?= Html::endForm() ?>
    </div>
<?php endif; ?>
<div class="table-responsive text-nowrap">
    <table class="table table-hover align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>دانشپذیر</th>
            <th>نام کاربری / کد ملی</th>
            <th>ثبت‌کننده</th>
            <th>وضعیت</th>
            <th>نقش</th>
            <th>نمره</th>
            <th class="text-end">عملیات</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($members)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">عضوی یافت نشد.</td></tr>
        <?php endif; ?>
        <?php foreach ($members as $index => $member):
            $memberId = (string) $member->_id;
            $entry = CourseMembersController::entryOf($member->courses, $courseId);
            $status = (string) (isset($entry['status']) ? $entry['status'] : '0');
            $pendingCancel = !empty($entry['begin_deleted']);
            $info = is_array($member->issuance_certificate_information) ? $member->issuance_certificate_information : [];
            $registrant = UsersDirectory::describeUsername(isset($entry['registrant']) && is_scalar($entry['registrant']) ? (string) $entry['registrant'] : '', (string) $member->username, $registrants);
            $fullName = trim($member->first_name . ' ' . $member->last_name);
            ?>
            <tr>
                <td><?= $fa($offset + $index + 1) ?></td>
                <td>
                    <a href="<?= Url::to(['users-manage/profile', 'id' => $memberId]) ?>" target="_blank" rel="noopener" class="fw-semibold"><?= Html::encode($fullName) ?></a>
                    <?php if (!empty($info['first_name_en']) || !empty($info['last_name_en'])): ?>
                        <div class="small text-muted" dir="ltr"><?= Html::encode(trim((isset($info['first_name_en']) ? $info['first_name_en'] : '') . ' ' . (isset($info['last_name_en']) ? $info['last_name_en'] : ''))) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span dir="ltr"><?= Html::encode($member->username) ?></span>
                    <?php if (!empty($info['id']) && is_scalar($info['id'])): ?><div class="small text-muted">کد ملی: <?= Html::encode($info['id']) ?></div><?php endif; ?>
                    <?php if (!empty($info['phone']) && is_scalar($info['phone'])): ?><div class="small text-muted">تماس: <?= Html::encode($info['phone']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?= Html::encode($registrant['name']) ?>
                    <?php if ($registrant['roleLabel'] !== ''): ?><div class="small text-muted"><?= Html::encode($registrant['roleLabel']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?php if ($pendingCancel): ?>
                        <span class="badge bg-label-danger">درخواست انصراف</span>
                    <?php else: list($label, $color) = isset($statuses[$status]) ? $statuses[$status] : ['نامشخص', 'secondary']; ?>
                        <span class="badge bg-label-<?= $color ?>"><?= $label ?></span>
                    <?php endif; ?>
                </td>
                <td><?= isset($entry['role']) && $entry['role'] === 'mentor' ? 'دستیار استاد' : 'دانشپذیر' ?></td>
                <td><?= $this->context->score_status($courseId, $memberId) ?></td>
                <td class="text-end">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="عملیات"><i class="bx bx-dots-vertical-rounded"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="<?= Url::to(['users-manage/profile', 'id' => $memberId]) ?>" target="_blank" rel="noopener"><i class="bx bx-user me-1"></i>پروفایل دانشپذیر</a>
                            <?php if ($canManage): ?>
                                <a class="dropdown-item js-member-finance" href="#" data-member="<?= $memberId ?>"><i class="bx bx-wallet me-1"></i>اطلاعات مالی</a>
                                <?php if (!$pendingCancel): ?>
                                    <?php if ($status === '0' && $online): ?>
                                        <?= Html::beginForm(['course-members/register-class', '_id' => $courseId], 'post') ?>
                                            <button class="dropdown-item"><i class="bx bx-video me-1"></i>ثبت در کلاس آنلاین</button>
                                        <?= Html::endForm() ?>
                                    <?php else: ?>
                                        <a class="dropdown-item js-member-action" href="#" data-member="<?= $memberId ?>"
                                           data-url="<?= Url::to(['course-members/status', '_id' => $courseId]) ?>"
                                           data-title="تغییر وضعیت"
                                           data-message="<?= Html::encode($fullName . ($status === '1' ? ' غیرفعال شود؟ دسترسی کلاس آنلاین برداشته می‌شود.' : ' فعال شود؟')) ?>">
                                            <i class="bx bx-toggle-left me-1"></i><?= $status === '1' ? 'غیرفعال کردن' : 'فعال کردن' ?>
                                        </a>
                                    <?php endif; ?>
                                    <a class="dropdown-item js-member-action" href="#" data-member="<?= $memberId ?>"
                                       data-url="<?= Url::to(['packages/change_role']) ?>"
                                       data-title="تغییر نقش"
                                       data-message="<?= Html::encode('نقش ' . $fullName . (isset($entry['role']) && $entry['role'] === 'mentor' ? ' به «دانشپذیر»' : ' به «دستیار استاد»') . ' تغییر کند؟') ?>">
                                        <i class="bx bx-transfer me-1"></i>تغییر نقش
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <?php if ($isAdmin): ?>
                                        <a class="dropdown-item text-danger js-member-action" href="#" data-member="<?= $memberId ?>" data-danger="1"
                                           data-url="<?= Url::to(['course-members/cancel', '_id' => $courseId]) ?>"
                                           data-title="حذف از دوره"
                                           data-message="<?= Html::encode($fullName . ' از دوره حذف شود؟ سهم واحد به کیف پول کارگزار برمی‌گردد و در «درخواست انصراف» ثبت می‌شود.') ?>">
                                            <i class="bx bx-trash me-1"></i>حذف از دوره
                                        </a>
                                    <?php else: ?>
                                        <a class="dropdown-item text-danger js-member-action" href="#" data-member="<?= $memberId ?>" data-danger="1"
                                           data-url="<?= Url::to(['course-members/cancel', '_id' => $courseId]) ?>"
                                           data-title="درخواست انصراف"
                                           data-message="<?= Html::encode('درخواست انصراف ' . $fullName . ' ثبت شود؟ پس از تأیید مدیر سیستم از دوره حذف می‌شود.') ?>">
                                            <i class="bx bx-log-out me-1"></i>ثبت درخواست انصراف
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dropdown-item disabled"><i class="bx bx-time me-1"></i>انصراف در انتظار تأیید</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($pagination): ?>
    <div class="d-flex justify-content-center mt-3">
        <?= LinkPager::widget(['pagination' => $pagination, 'options' => ['class' => 'pagination pagination-sm'], 'linkContainerOptions' => ['class' => 'page-item'], 'linkOptions' => ['class' => 'page-link'], 'disabledListItemSubTagOptions' => ['class' => 'page-link']]) ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="member-action-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <?= Html::beginForm('', 'post', ['class' => 'modal-content']) ?>
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body text-start">
                <p class="js-message mb-0"></p>
                <input type="hidden" name="member" class="js-member-id">
                <input type="hidden" name="Users[_id]" class="js-member-id">
                <input type="hidden" name="courseId" value="<?= Html::encode($courseId) ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">تأیید</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>

<div class="modal fade" id="member-finance-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">اطلاعات مالی</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body text-start"></div>
        </div>
    </div>
</div>
