<?php
/**
 * مدیریت واحدها: فهرست + آمار هر واحد + ثبت/ویرایش/گزارش سالانه.
 *
 * @var $this yii\web\View
 * @var $searchModel app\models\CollegesSearch
 * @var $dataProvider yii\data\ActiveDataProvider
 * @var $years int[]
 * @var $stats array خروجی UnitStats::all()
 * @var $isAdmin bool
 */
use app\components\UnitStats;
use app\components\UsersImport;
use app\models\Colleges;
use frontend\controllers\CollagesManageController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'مدیریت واحدها';
$fa = function ($n) {
    return UsersImport::faDigits(number_format((int) $n));
};
$logoUrl = function ($file) {
    return is_string($file) && $file !== '' ? Yii::getAlias('@web') . '/college_logos/' . rawurlencode(basename($file)) : null;
};
$units = $dataProvider->getModels();
$total = isset($stats['_total']) ? $stats['_total'] : UnitStats::empty();
$pagination = $dataProvider->getPagination();
$offset = $pagination ? $pagination->getOffset() : 0;

$flash = Yii::$app->session->getFlash(CollagesManageController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$type'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 7000});");
}

$accountLength = Colleges::ACCOUNT_ID_LENGTH;
$this->registerJs(<<<JS
// شناسه‌ی حساب: ارقام فارسی/عربی به انگلیسی، حذف هر کاراکتر غیرعددی، شمارنده‌ی ارقام
function normalizeAccount(input) {
    var map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
    var v = input.value.replace(/[۰-۹٠-٩]/g, function (d) { return map[d]; }).replace(/[^0-9]/g, '').slice(0, $accountLength);
    if (v !== input.value) input.value = v;
    var counter = $(input).closest('.account-field').find('.account-counter');
    counter.text(v.length + ' / $accountLength').toggleClass('text-success', v.length === $accountLength).toggleClass('text-danger', v.length !== $accountLength);
    input.setCustomValidity(v.length === $accountLength ? '' : 'شناسه‌ی حساب باید دقیقاً $accountLength رقم انگلیسی باشد');
}
$(document).on('input', '.account-input', function () { normalizeAccount(this); });
$('.account-input').each(function () { normalizeAccount(this); });

$(document).on('click', '.js-edit-unit', function () {
    var u = $(this).data('unit');
    var form = $('#edit-unit form');
    form.find('[name^="Colleges["]').not('[type=file]').each(function () {
        var key = this.name.replace('Colleges[', '').replace(']', '').replace('[', '.').replace(']', '');
        var value = key.split('.').reduce(function (o, k) { return o && o[k] !== undefined ? o[k] : ''; }, u);
        $(this).val(value);
    });
    form.find('[data-display]').each(function () {
        var value = $(this).data('display').split('.').reduce(function (o, k) { return o && o[k] !== undefined ? o[k] : ''; }, u);
        $(this).val(value);
    });
    form.find('[type=file]').val('');
    $('#edit-unit-title').text(u.title);
    $('#edit-unit-logo').attr('src', u.logoUrl || '').toggle(!!u.logoUrl);
    $('#edit-unit-signature').attr('src', u.signatureUrl || '').toggle(!!u.signatureUrl);
    form.find('.account-input').each(function () { normalizeAccount(this); });
});
$(document).on('click', '.js-report-unit', function () {
    $('#report-unit-id').val($(this).data('id'));
    $('#report-unit-title').text($(this).data('title'));
});
JS
);
$this->registerCss(<<<CSS
.units-table td, .units-table th { white-space: nowrap; vertical-align: middle; }
.units-table .stat { font-weight: 600; }
.unit-logo { width: 44px; height: 44px; object-fit: contain; background: #fff; border: 1px solid rgba(67, 89, 113, .1); }
.unit-preview { max-height: 64px; max-width: 120px; object-fit: contain; }
CSS
);

/**
 * فیلدهای مشترک فرم ثبت و ویرایش.
 */
$fields = function ($prefix, $isNew) use ($isAdmin, $accountLength) {
    $text = function ($name, $label, $options = []) use ($prefix) {
        $id = $prefix . '-' . str_replace(['[', ']'], ['-', ''], $name);
        $required = !empty($options['required']);
        unset($options['required']);
        return '<div class="' . (isset($options['col']) ? $options['col'] : 'col-md-6') . '">'
            . '<label class="form-label" for="' . $id . '">' . Html::encode($label) . ($required ? ' *' : '') . '</label>'
            . Html::textInput('Colleges[' . $name . ']', '', array_merge(['id' => $id, 'class' => 'form-control', 'maxlength' => 200, 'required' => $required, 'autocomplete' => 'off'], array_diff_key($options, ['col' => 1])))
            . '</div>';
    };
    ob_start(); ?>
    <h6 class="text-muted small mb-2">مشخصات واحد</h6>
    <div class="row g-3 mb-4">
        <?= $text('title', 'عنوان واحد', ['required' => true, 'maxlength' => 150]) ?>
        <?= $text('prefix', 'کد مجوز', ['required' => true, 'maxlength' => 50, 'dir' => 'ltr']) ?>
        <?= $text('title_en', 'Title (English)', ['dir' => 'ltr', 'maxlength' => 150]) ?>
        <?= $text('phone', 'شماره تماس', ['dir' => 'ltr', 'maxlength' => 20, 'inputmode' => 'tel']) ?>
    </div>
    <h6 class="text-muted small mb-2">امضای مدرک</h6>
    <div class="row g-3 mb-4">
        <?= $text('first_line_signature_fa', 'امضا خط اول') ?>
        <?= $text('second_line_signature_fa', 'امضا خط دوم') ?>
        <?= $text('name', 'Name', ['dir' => 'ltr']) ?>
        <?= $text('last_name', 'Last Name', ['dir' => 'ltr']) ?>
        <?= $text('first_line_signature_en', 'First Signature', ['dir' => 'ltr']) ?>
        <?= $text('second_line_signature_en', 'Second Signature', ['dir' => 'ltr']) ?>
    </div>
    <h6 class="text-muted small mb-2">اطلاعات مالی</h6>
    <div class="row g-3 mb-4">
        <div class="col-md-8 account-field">
            <label class="form-label d-flex justify-content-between" for="<?= $prefix ?>-account">
                <span>شناسه حساب <?= UsersImport::faDigits($accountLength) ?> رقمی *</span>
                <small class="account-counter text-muted" dir="ltr"></small>
            </label>
            <?php if ($isAdmin): ?>
                <?= Html::textInput('Colleges[financial_info][id]', '', ['id' => $prefix . '-account', 'class' => 'form-control account-input', 'dir' => 'ltr', 'inputmode' => 'numeric', 'maxlength' => $accountLength, 'pattern' => '[0-9]{' . $accountLength . '}', 'required' => true, 'autocomplete' => 'off', 'placeholder' => str_repeat('0', $accountLength)]) ?>
                <small class="text-muted">فقط ارقام انگلیسی؛ ارقام فارسی خودکار تبدیل می‌شوند.</small>
            <?php else: ?>
                <?= Html::textInput('', '', ['id' => $prefix . '-account', 'class' => 'form-control', 'dir' => 'ltr', 'disabled' => true, 'data-display' => 'financial_info.id']) ?>
                <small class="text-muted">تغییر شناسه‌ی حساب فقط توسط مدیر سیستم امکان‌پذیر است.</small>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="<?= $prefix ?>-sub-service">ساب سرویس آی دی</label>
            <input type="text" class="form-control" id="<?= $prefix ?>-sub-service" value="<?= Colleges::SUB_SERVICE_ID ?>" disabled dir="ltr">
            <small class="text-muted">مقدار ثابت برای همه‌ی واحدها</small>
        </div>
    </div>
    <h6 class="text-muted small mb-2">فایل‌ها</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="<?= $prefix ?>-logo">لوگوی واحد <?= $isNew ? '*' : '' ?></label>
            <?= Html::fileInput('Colleges[logo]', null, ['id' => $prefix . '-logo', 'class' => 'form-control', 'accept' => '.png,.jpg,.jpeg,.webp', 'required' => $isNew]) ?>
            <small class="text-muted">PNG، JPG یا WEBP، حداکثر ۲ مگابایت<?= $isNew ? '' : '؛ خالی = بدون تغییر' ?></small>
            <?php if (!$isNew): ?><div class="mt-2"><img id="edit-unit-logo" class="unit-preview rounded border p-1" alt="لوگوی فعلی"></div><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="<?= $prefix ?>-signature">تصویر امضا</label>
            <?= Html::fileInput('Colleges[signature_file]', null, ['id' => $prefix . '-signature', 'class' => 'form-control', 'accept' => '.png,.jpg,.jpeg,.webp']) ?>
            <small class="text-muted">PNG، JPG یا WEBP، حداکثر ۲ مگابایت<?= $isNew ? '' : '؛ خالی = بدون تغییر' ?></small>
            <?php if (!$isNew): ?><div class="mt-2"><img id="edit-unit-signature" class="unit-preview rounded border p-1" alt="امضای فعلی"></div><?php endif; ?>
        </div>
    </div>
    <?php return ob_get_clean();
};
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">مدیریت واحدها</h4>
            <p class="text-muted mb-0"><?= $isAdmin ? 'همه‌ی واحدهای سامانه' : 'واحد(های) شما' ?></p>
        </div>
        <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-unit">
                <i class="bx bx-plus me-1"></i>ثبت واحد جدید
            </button>
        <?php endif; ?>
    </div>

    <!-- آمار کل -->
    <div class="row g-4 mb-4">
        <?php
        $cards = [
            ['واحد', $dataProvider->getTotalCount(), 'bx-buildings', 'primary', null],
            ['دانشپذیر', $total['students'], 'bx-group', 'info', 'هر دانشپذیر یک بار شمرده شده'],
            ['کارمند واحد', $total['staff'], 'bx-id-card', 'secondary', null],
            ['استاد', $total['teachers'], 'bx-chalkboard', 'warning', null],
            ['دوره', $total['courses'], 'bx-book-open', 'success', $fa($total['activeCourses']) . ' دوره فعال'],
            ['مدرک صادرشده', $total['certificates'], 'bx-award', 'danger', $fa($total['pendingCertificates']) . ' درخواست در انتظار'],
            ['کارگزار', $total['brokers'], 'bx-briefcase', 'dark', $fa($total['activeBrokers']) . ' کارگزار فعال'],
        ];
        foreach ($cards as $card): ?>
            <div class="col-6 col-md-4 col-xl">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="avatar mb-3"><span class="avatar-initial rounded bg-label-<?= $card[3] ?>"><i class="bx <?= $card[2] ?>"></i></span></div>
                        <span class="d-block text-muted mb-1"><?= Html::encode($card[0]) ?></span>
                        <h4 class="card-title mb-0"><?= $fa($card[1]) ?></h4>
                        <?php if ($card[4] !== null): ?><small class="text-muted"><?= Html::encode($card[4]) ?></small><?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">واحدهای ثبت‌شده <span class="badge bg-label-primary ms-2"><?= $fa($dataProvider->getTotalCount()) ?></span></h5>
            <form action="<?= Url::to(['index']) ?>" method="get" class="d-flex gap-2">
                <?= Html::textInput('CollegesSearch[title]', is_scalar($searchModel->title) ? $searchModel->title : '', ['class' => 'form-control form-control-sm', 'placeholder' => 'جست‌وجوی عنوان واحد', 'maxlength' => 100]) ?>
                <button class="btn btn-sm btn-primary" type="submit"><i class="bx bx-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover units-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>واحد</th>
                    <th class="text-center">دانشپذیران</th>
                    <th class="text-center">کارمندان</th>
                    <th class="text-center">اساتید</th>
                    <th class="text-center">دوره‌ها<small class="d-block text-muted">فعال / کل</small></th>
                    <th class="text-center">مدارک صادرشده</th>
                    <th class="text-center">کارگزاران<small class="d-block text-muted">فعال / کل</small></th>
                    <th class="text-center">عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                <?php if (empty($units)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">واحدی یافت نشد</td></tr>
                <?php endif; ?>
                <?php foreach ($units as $i => $unit):
                    $id = (string) $unit->_id;
                    $s = isset($stats[$id]) ? $stats[$id] : UnitStats::empty();
                    $financial = is_array($unit->financial_info) ? $unit->financial_info : [];
                    $accountId = isset($financial['id']) && is_scalar($financial['id']) ? (string) $financial['id'] : '';
                    $accountOk = preg_match('/^[0-9]{' . Colleges::ACCOUNT_ID_LENGTH . '}$/', $accountId) === 1;
                    $data = [
                        '_id' => $id,
                        'financial_info' => ['id' => $accountId],
                        'logoUrl' => $logoUrl($unit->logo),
                        'signatureUrl' => $logoUrl($unit->signature_file),
                    ];
                    foreach (['title', 'prefix', 'first_line_signature_fa', 'second_line_signature_fa', 'title_en', 'name', 'last_name', 'first_line_signature_en', 'second_line_signature_en', 'phone'] as $field)
                        $data[$field] = is_scalar($unit->$field) ? (string) $unit->$field : '';
                    ?>
                    <tr>
                        <td class="text-muted"><?= $fa($offset + $i + 1) ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($logoUrl($unit->logo)): ?>
                                    <img src="<?= Html::encode($logoUrl($unit->logo)) ?>" alt="" class="unit-logo rounded-circle me-3" loading="lazy">
                                <?php else: ?>
                                    <span class="avatar me-3"><span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-buildings"></i></span></span>
                                <?php endif; ?>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold"><?= Html::encode($unit->title) ?></span>
                                    <small class="text-muted">
                                        کد مجوز: <span dir="ltr"><?= Html::encode($unit->prefix ?: '—') ?></span>
                                        <?php if (!$accountOk): ?><span class="badge bg-label-warning ms-1" title="شناسه‌ی حساب باید ۳۰ رقم باشد">شناسه حساب ناقص</span><?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center"><a class="stat" href="<?= Url::to(['users-manage/index', 'UsersSearch' => ['college_id' => $id]]) ?>" title="مشاهده‌ی دانشپذیران"><?= $fa($s['students']) ?></a></td>
                        <td class="text-center stat"><?= $fa($s['staff']) ?></td>
                        <td class="text-center stat"><?= $fa($s['teachers']) ?></td>
                        <td class="text-center"><span class="stat text-success"><?= $fa($s['activeCourses']) ?></span> / <span class="stat"><?= $fa($s['courses']) ?></span></td>
                        <td class="text-center">
                            <span class="stat"><?= $fa($s['certificates']) ?></span>
                            <?php if ($s['pendingCertificates'] > 0): ?><small class="d-block text-warning"><?= $fa($s['pendingCertificates']) ?> در انتظار</small><?php endif; ?>
                        </td>
                        <td class="text-center"><span class="stat text-success"><?= $fa($s['activeBrokers']) ?></span> / <span class="stat"><?= $fa($s['brokers']) ?></span></td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="عملیات"><i class="bx bx-dots-vertical-rounded"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item js-edit-unit" href="#" data-bs-toggle="modal" data-bs-target="#edit-unit" data-unit="<?= Html::encode(Json::encode($data)) ?>"><i class="bx bx-edit me-2"></i>ویرایش</a></li>
                                    <li><a class="dropdown-item js-report-unit" href="#" data-bs-toggle="modal" data-bs-target="#report-unit" data-id="<?= $id ?>" data-title="<?= Html::encode($unit->title) ?>"><i class="bx bx-spreadsheet me-2"></i>گزارش سالانه دوره‌ها</a></li>
                                    <li><a class="dropdown-item" href="<?= Url::to(['users-manage/index', 'UsersSearch' => ['college_id' => $id]]) ?>"><i class="bx bx-group me-2"></i>دانشپذیران واحد</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination && $pagination->getPageCount() > 1): ?>
            <div class="card-footer d-flex justify-content-center">
                <?= LinkPager::widget([
                    'pagination' => $pagination,
                    'options' => ['class' => 'pagination pagination-sm mb-0'],
                    'linkContainerOptions' => ['class' => 'page-item'],
                    'linkOptions' => ['class' => 'page-link'],
                    'disabledListItemSubTagOptions' => ['tag' => 'span', 'class' => 'page-link'],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="new-unit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['new'], 'post', ['enctype' => 'multipart/form-data']) ?>
            <div class="modal-header">
                <h5 class="modal-title">ثبت واحد جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body"><?= $fields('new-unit', true) ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">ثبت واحد</button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="edit-unit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['edit'], 'post', ['enctype' => 'multipart/form-data']) ?>
            <div class="modal-header">
                <h5 class="modal-title">ویرایش واحد <span id="edit-unit-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="Colleges[_id]">
                <?= $fields('edit-unit', false) ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<div class="modal fade" id="report-unit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['report'], 'post') ?>
            <div class="modal-header">
                <h5 class="modal-title">گزارش سالانه دوره‌های <span id="report-unit-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="college" id="report-unit-id">
                <label class="form-label" for="report-year">سال *</label>
                <select class="form-select" name="year" id="report-year" required>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= (int) $year ?>"><?= UsersImport::faDigits($year) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted d-block mt-2">فهرست دوره‌های ایجادشده در سال انتخابی به همراه تعداد شرکت‌کنندگان هر دوره (فایل اکسل).</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-download me-1"></i>دریافت گزارش</button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
