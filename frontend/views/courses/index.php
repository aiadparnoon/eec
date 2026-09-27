<?php
/**
 * دوره‌های کوتاه‌مدت — فهرست، آمار، فیلترها و ثبت دوره.
 *
 * @var $this yii\web\View
 * @var $searchModel app\models\ShortCoursesSearch
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $courses app\models\Courses[]
 * @var $teachers array [id => نام]
 * @var $brokerNames array [id => نام]
 * @var $registrants array
 * @var $stats array
 * @var $units array
 * @var $brokers array
 * @var $filterTeachers array
 * @var $capacityTypes array
 * @var $canCreate bool
 */
use app\components\CourseAccess;
use app\components\CourseStatus;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\components\classroom\ClassroomPlatforms;
use frontend\assets\InputGuardAsset;
use frontend\controllers\CoursesController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'دوره‌های کوتاه‌مدت';
InputGuardAsset::register($this);
$fa = function ($n) {
    return UsersImport::faDigits(number_format((int) $n));
};
$unitTitles = UsersDirectory::collegeTitles();
$pagination = $dataProvider->getPagination();
$offset = $pagination ? $pagination->getOffset() : 0;
$total = $dataProvider->getTotalCount();
$front = Yii::getAlias('@web');

$flash = Yii::$app->session->getFlash(CoursesController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$type'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 7000, escapeHtml: true});");
}

$checkDeleteUrl = Json::htmlEncode(Url::to(['show_course_users']));
$this->registerJs(<<<JS
// مودال تأیید مشترک برای کپی، حذف، نمایش در سایت و ساخت کلاس آنلاین
function openConfirm(opts) {
    var m = $('#course-confirm');
    m.find('.modal-title').text(opts.title);
    m.find('.confirm-message').text(opts.message);
    m.find('form').attr('action', opts.action).toggle(!!opts.action);
    m.find('input[name=_id]').val(opts.id || '');
    m.find('button[type=submit]').text(opts.button || 'تأیید').attr('class', 'btn btn-' + (opts.color || 'primary'));
    bootstrap.Modal.getOrCreateInstance(m[0]).show();
}
$(document).on('click', '.js-confirm', function (e) {
    e.preventDefault();
    openConfirm($(this).data());
});
$(document).on('click', '.js-delete', function (e) {
    e.preventDefault();
    var id = $(this).data('id'), action = $(this).data('action');
    $.post($checkDeleteUrl, {id: id, _csrf: yii.getCsrfToken()}, null, 'json').done(function (res) {
        openConfirm({title: 'حذف دوره', message: res.message, action: res.ok ? action : '', id: id, button: 'بله، حذف شود', color: 'danger'});
    });
});
$(document).on('click', '.js-reason', function (e) {
    e.preventDefault();
    openConfirm({title: 'دلیل رد / اصلاح', message: $(this).data('reason') || '—', action: ''});
});
$(document).on('click', '.courses-table tbody tr[data-href]', function (e) {
    if ($(e.target).closest('a, button, .dropdown-menu, form').length) return;
    window.location = $(this).data('href');
});
JS
);
$this->registerCss(<<<CSS
.courses-table td { vertical-align: middle; }
.courses-table th, .courses-table td { padding-left: .6rem; padding-right: .6rem; }
.courses-table tbody tr[data-href] { cursor: pointer; }
.courses-table .course-title { min-width: 220px; max-width: 340px; white-space: normal; }
.courses-table .unit-name { min-width: 130px; white-space: normal; }
.courses-table .nowrap { white-space: nowrap; }
CSS
);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">دوره‌های کوتاه‌مدت</h4>
            <p class="text-muted mb-0">دوره‌های تک‌درسی بین ۸ تا ۲۴ ساعت</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (CourseAccess::role() !== 'teacher'): ?>
                <a class="btn btn-label-success" href="<?= Url::to(array_merge(['report'], Yii::$app->request->queryParams)) ?>"><i class="bx bx-export me-1"></i>خروجی اکسل (<?= $fa($total) ?>)</a>
            <?php endif; ?>
            <?php if ($canCreate): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-course"><i class="bx bx-plus me-1"></i>ثبت دوره‌ی کوتاه‌مدت</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <?php
        $cards = [
            ['کل دوره‌ها', $stats['total'], 'bx-book-open', 'primary', null],
            ['در انتظار بررسی', $stats['awaiting'], 'bx-time-five', 'warning', 'awaiting'],
            ['اصلاح‌شده، در انتظار بررسی', $stats['corrected'], 'bx-revision', 'info', 'corrected'],
            ['نیاز به اصلاح', $stats['correction'], 'bx-error', 'danger', '4'],
            ['فعال', $stats['active'], 'bx-check-circle', 'success', '1'],
            ['پایان یافته', $stats['finished'], 'bx-flag', 'dark', '6'],
        ];
        if (isset($stats['unit']))
            array_splice($cards, 1, 0, [['در انتظار بررسی واحد', $stats['unit'], 'bx-buildings', 'primary', '7']]);
        foreach ($cards as $card):
            $url = $card[4] === null ? Url::to(['index']) : Url::to(['index', 'CS' => ['status' => $card[4]]]); ?>
            <div class="col-6 col-md-4 col-xl">
                <a class="card h-100 text-body" href="<?= Html::encode($url) ?>">
                    <div class="card-body">
                        <div class="avatar mb-3"><span class="avatar-initial rounded bg-label-<?= $card[3] ?>"><i class="bx <?= $card[2] ?>"></i></span></div>
                        <span class="d-block text-muted mb-1 small"><?= Html::encode($card[0]) ?></span>
                        <h4 class="card-title mb-0"><?= $fa($card[1]) ?></h4>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?= $this->render('_filters', ['model' => $searchModel, 'units' => $units, 'brokers' => $brokers, 'teachers' => $filterTeachers]) ?>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">فهرست دوره‌ها <span class="badge bg-label-primary ms-2"><?= $fa($total) ?></span></h5>
            <?php if ($searchModel->hasFilters()): ?>
                <a href="<?= Url::to(['index']) ?>" class="btn btn-sm btn-label-secondary"><i class="bx bx-x me-1"></i>حذف فیلترها</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover courses-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>دوره</th>
                    <th>واحد</th>
                    <th>کارگزار / ثبت‌کننده</th>
                    <th class="nowrap">برگزاری</th>
                    <th class="nowrap">وضعیت</th>
                    <th class="text-center">عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php if (empty($courses)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-5"><i class="bx bx-search-alt d-block mb-2" style="font-size:2rem"></i>دوره‌ای با این مشخصات یافت نشد</td></tr>
                <?php endif; ?>
                <?php foreach ($courses as $i => $course):
                    $id = (string) $course->_id;
                    $title = isset($course->title['main_fa']) && is_scalar($course->title['main_fa']) ? (string) $course->title['main_fa'] : '—';
                    $lesson = isset($course->lessons[0]) && is_array($course->lessons[0]) ? $course->lessons[0] : [];
                    $date = isset($lesson['date']) && is_array($lesson['date']) ? $lesson['date'] : [];
                    $teacherId = isset($lesson['teachers']) ? (string) $lesson['teachers'] : '';
                    $brokerId = is_array($course->broker) && isset($course->broker['_id']) ? (string) $course->broker['_id'] : '';
                    list($statusLabel, $statusColor) = CourseStatus::label($course);
                    $online = ClassroomPlatforms::hasOnlineClass($course);
                    $meetingMissing = $online && ($course->adobe_status === '0' || !isset($lesson['meeting']));
                    $status = (string) $course->status;
                    $editUrl = Url::to(['edit-course', '_id' => $id]);
                    $registrant = UsersDirectory::describeUsername(is_scalar($course->registrant) ? (string) $course->registrant : '', '', $registrants);
                    $image = is_scalar($course->preview_image) && $course->preview_image !== '' ? $front . '/lesson_images/' . rawurlencode(basename((string) $course->preview_image)) : null;
                    ?>
                    <tr data-href="<?= Html::encode($editUrl) ?>">
                        <td class="text-muted"><?= $fa($offset + $i + 1) ?></td>
                        <td class="course-title">
                            <div class="d-flex align-items-center">
                                <?php if ($image): ?>
                                    <img src="<?= Html::encode($image) ?>" alt="" class="rounded me-3" width="44" height="44" style="object-fit:cover" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('span'), {className: 'avatar me-3', innerHTML: '<span class=\'avatar-initial rounded bg-label-primary\'><i class=\'bx bx-book\'></i></span>'}))">
                                <?php else: ?>
                                    <span class="avatar me-3"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-book"></i></span></span>
                                <?php endif; ?>
                                <div class="d-flex flex-column">
                                    <a href="<?= Html::encode($editUrl) ?>" class="fw-semibold text-body"><?= Html::encode($title) ?></a>
                                    <small class="text-muted">مدرس: <?= Html::encode(isset($teachers[$teacherId]) ? $teachers[$teacherId] : '—') ?></small>
                                    <small class="text-muted">
                                        <?= Html::encode(UsersDirectory::contentType($course->content_type)) ?>
                                        · کد مجوز: <span dir="ltr"><?= Html::encode($course->license_code ?: '—') ?></span>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td class="unit-name"><?= Html::encode(isset($unitTitles[(string) $course->college]) ? $unitTitles[(string) $course->college] : '—') ?></td>
                        <td>
                            <?php if ($brokerId !== '' && isset($brokerNames[$brokerId])): ?>
                                <span class="d-block"><?= Html::encode($brokerNames[$brokerId]) ?></span><small class="text-muted">کارگزار</small>
                            <?php elseif (is_scalar($course->registrant) && (string) $course->registrant !== ''): ?>
                                <span class="d-block"><?= Html::encode($registrant['name']) ?></span><small class="text-muted"><?= Html::encode($registrant['roleLabel']) ?></small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="nowrap">
                            <span class="d-block">از <span dir="ltr"><?= Html::encode(isset($date['from']) ? str_replace('-', '/', (string) $date['from']) : '—') ?></span> تا <span dir="ltr"><?= Html::encode(isset($date['to']) ? str_replace('-', '/', (string) $date['to']) : '—') ?></span></span>
                            <small class="text-muted">ثبت: <?= UsersDirectory::jdate('Y/m/d', hexdec(substr($id, 0, 8))) ?></small>
                        </td>
                        <td class="nowrap">
                            <span class="badge bg-label-<?= $statusColor ?>"><?= Html::encode($statusLabel) ?></span>
                            <?php if ($meetingMissing && in_array($status, ['0', '1'], true)): ?><small class="d-block text-danger mt-1"><i class="bx bx-error-circle"></i> کلاس آنلاین ساخته نشده</small><?php endif; ?>
                            <?php if ($status === '1' && $course->show_in_site === false): ?><small class="d-block text-muted mt-1"><i class="bx bx-hide"></i> مخفی در سایت</small><?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="عملیات"><i class="bx bx-dots-vertical-rounded"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= Html::encode($editUrl) ?>"><i class="bx bx-show me-2"></i><?= CourseAccess::canEdit($course) ? 'مشاهده و ویرایش' : 'مشاهده' ?></a></li>
                                    <li><a class="dropdown-item" href="<?= Url::to(['manage-course-contents/teacher-part', '_id' => $id]) ?>"><i class="bx bx-video me-2"></i>جلسات و محتوای دوره</a></li>
                                    <?php if (!empty($course->rejection_reason) && in_array($status, ['4', '5', '8', '9'], true)): ?>
                                        <li><a class="dropdown-item js-reason" href="#" data-reason="<?= Html::encode($course->rejection_reason) ?>"><i class="bx bx-message-error me-2"></i>دلیل رد / اصلاح</a></li>
                                    <?php endif; ?>
                                    <?php if (CourseAccess::canManage($course)): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if ($meetingMissing): ?>
                                            <li><a class="dropdown-item js-confirm" href="#" data-title="ساخت کلاس آنلاین" data-message="<?= Html::encode('کلاس آنلاین دوره‌ی «' . $title . '» دوباره ساخته شود؟') ?>" data-action="<?= Url::to(['register-online']) ?>" data-id="<?= $id ?>" data-button="ساخت کلاس"><i class="bx bx-refresh me-2"></i>ساخت مجدد کلاس آنلاین</a></li>
                                        <?php endif; ?>
                                        <?php if ($status === '1'): ?>
                                            <li><a class="dropdown-item js-confirm" href="#" data-title="نمایش در سایت" data-message="<?= Html::encode($course->show_in_site === false ? 'دوره در سایت نمایش داده شود؟' : 'دوره از سایت مخفی شود؟') ?>" data-action="<?= Url::to(['toggle-site']) ?>" data-id="<?= $id ?>"><i class="bx <?= $course->show_in_site === false ? 'bx-show' : 'bx-hide' ?> me-2"></i><?= $course->show_in_site === false ? 'نمایش در سایت' : 'مخفی کردن در سایت' ?></a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item js-confirm" href="#" data-title="کپی دوره" data-message="<?= Html::encode('از دوره‌ی «' . $title . '» همراه با درس آن یک نسخه‌ی جدید ساخته شود؟') ?>" data-action="<?= Url::to(['copy-course', '_id' => $id]) ?>" data-id="<?= $id ?>" data-button="ساخت کپی"><i class="bx bx-copy me-2"></i>کپی دوره</a></li>
                                        <li><a class="dropdown-item text-danger js-delete" href="#" data-id="<?= $id ?>" data-action="<?= Url::to(['delete_course']) ?>"><i class="bx bx-trash me-2"></i>حذف دوره</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination && $pagination->getPageCount() > 1): ?>
            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">نمایش <?= $fa($offset + 1) ?> تا <?= $fa($offset + count($courses)) ?> از <?= $fa($total) ?></small>
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'maxButtonCount' => 7,
                    'options' => ['class' => 'pagination pagination-sm mb-0'],
                    'linkContainerOptions' => ['class' => 'page-item'],
                    'linkOptions' => ['class' => 'page-link'],
                    'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'page-link'],
                    'prevPageLabel' => '<i class="bx bx-chevron-right"></i>',
                    'nextPageLabel' => '<i class="bx bx-chevron-left"></i>',
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="course-confirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body"><p class="confirm-message mb-0" style="white-space: pre-line"></p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
                <?= Html::beginForm('', 'post', ['class' => 'd-inline']) ?>
                    <input type="hidden" name="_id">
                    <button type="submit" class="btn btn-primary">تأیید</button>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canCreate) echo $this->render('_create-modal', ['units' => $units, 'capacityTypes' => $capacityTypes]); ?>
