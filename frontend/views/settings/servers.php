<?php
/**
 * تنظیمات سایت › سرورهای برگزاری کلاس آنلاین.
 *
 * @var $this yii\web\View
 * @var $servers app\models\ClassroomServers[]
 * @var $usage array [serverId => تعداد دوره]
 */
use app\components\UsersImport;
use app\models\ClassroomServers;
use frontend\controllers\SettingsController;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'تنظیمات سایت — سرورها';
$fa = function ($n) { return UsersImport::faDigits($n); };

$flash = Yii::$app->session->getFlash(SettingsController::FLASH);
if (is_array($flash) && isset($flash['message'])) {
    $type = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
    $this->registerJs("toastr['$type'](" . Json::htmlEncode($flash['message']) . ", '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 7000, escapeHtml: true});");
}

// داده‌ی فرم ویرایش هر سرور — بدون فیلدهای محرمانه
$editData = [];
foreach ($servers as $server) {
    $values = [];
    foreach (ClassroomServers::FIELDS[$server->type] as $key => $field)
        $values[$key] = empty($field['secret']) ? $server->setting($key) : ($server->hasSetting($key) ? '1' : '');
    $editData[(string) $server->_id] = ['title' => $server->title, 'type' => $server->type, 'values' => $values];
}
$editJson = Json::htmlEncode($editData);
$testUrl = Json::htmlEncode(Url::to(['server-test']));
$this->registerJs(<<<JS
(function () {
    var data = $editJson, modal = $('#server-modal'), form = $('#server-form');
    function showType(type) {
        form.find('.server-fields').each(function () {
            var active = $(this).data('type') === type;
            $(this).toggle(active).find('input, select').prop('disabled', !active);
        });
    }
    form.on('change', '#server-type', function () { showType($(this).val()); });
    $('.js-server-new').on('click', function () {
        form[0].reset();
        form.find('[name="Server[_id]"]').val('');
        form.find('.secret-hint').hide();
        form.find('input[data-secret]').attr('placeholder', '').prop('required', true);
        $('#server-type').prop('disabled', false);
        modal.find('.modal-title').text('افزودن سرور');
        showType($('#server-type').val());
    });
    $('.js-server-edit').on('click', function () {
        var id = $(this).data('id'), item = data[id];
        if (!item) return;
        form[0].reset();
        form.find('[name="Server[_id]"]').val(id);
        form.find('[name="Server[title]"]').val(item.title);
        $('#server-type').val(item.type);
        $.each(item.values, function (key, value) {
            var input = form.find('[name="Server[' + item.type + '][' + key + ']"]');
            if (input.is('[data-secret]')) {
                input.val('').prop('required', !value).attr('placeholder', value ? 'بدون تغییر' : '');
                input.closest('.mb-3').find('.secret-hint').toggle(!!value);
            } else input.val(value);
        });
        modal.find('.modal-title').text('ویرایش سرور');
        showType(item.type);
    });
    $('.js-server-test').on('click', function () {
        var btn = $(this).prop('disabled', true);
        $.post($testUrl, {id: btn.data('id'), _csrf: yii.getCsrfToken()}).done(function (res) {
            toastr[res.ok ? 'success' : 'error'](res.message, '', {positionClass: 'toast-top-center', escapeHtml: true});
        }).fail(function () {
            toastr.error('خطا در تست اتصال', '', {positionClass: 'toast-top-center'});
        }).always(function () { btn.prop('disabled', false); });
    });
    $('.js-confirm').on('click', function (e) {
        if (!confirm($(this).data('confirm'))) e.preventDefault();
    });
})();
JS
);
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">تنظیمات سایت</li>
            <li class="breadcrumb-item active">سرورها</li>
        </ol>
    </nav>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="card-title mb-1">سرورهای برگزاری کلاس آنلاین</h5>
                <small class="text-muted">هنگام ثبت دوره، ثبت‌کننده یکی از سرورهای فعال یا «هیچ‌کدام» را انتخاب می‌کند.</small>
            </div>
            <button type="button" class="btn btn-primary js-server-new" data-bs-toggle="modal" data-bs-target="#server-modal">
                <i class="bx bx-plus me-1"></i>افزودن سرور
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>نام سرور</th>
                    <th>نوع</th>
                    <th>آدرس</th>
                    <th>دوره‌ها</th>
                    <th>وضعیت</th>
                    <th class="text-end">عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($servers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">سروری ثبت نشده است.</td></tr>
                <?php endif; ?>
                <?php foreach ($servers as $server):
                    $id = (string) $server->_id;
                    $missingSecret = false;
                    foreach (ClassroomServers::FIELDS[$server->type] as $key => $field)
                        if (!empty($field['secret']) && $field['required'] && ($server->setting($key) === ''))
                            $missingSecret = true;
                    ?>
                    <tr>
                        <td>
                            <span class="fw-semibold"><?= Html::encode($server->title) ?></span>
                            <?php if ($server->is_default): ?><span class="badge bg-label-primary ms-1">پیش‌فرض</span><?php endif; ?>
                            <?php if ($missingSecret): ?><div><small class="text-danger"><i class="bx bx-error-circle"></i> رمز/کلید ذخیره نشده یا قابل خواندن نیست</small></div><?php endif; ?>
                        </td>
                        <td><?= Html::encode($server->typeLabel()) ?></td>
                        <td dir="ltr" class="text-start"><small><?= Html::encode($server->setting('url')) ?></small></td>
                        <td><?= $fa($usage[$id]) ?></td>
                        <td>
                            <?php if ($server->active): ?>
                                <span class="badge bg-label-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-label-secondary">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                <button type="button" class="btn btn-sm btn-label-info js-server-test" data-id="<?= $id ?>"><i class="bx bx-plug"></i> تست اتصال</button>
                                <button type="button" class="btn btn-sm btn-label-primary js-server-edit" data-id="<?= $id ?>" data-bs-toggle="modal" data-bs-target="#server-modal"><i class="bx bx-edit"></i> ویرایش</button>
                                <?php if ($server->active && !$server->is_default): ?>
                                    <?= Html::beginForm(['server-default'], 'post', ['class' => 'd-inline']) . Html::hiddenInput('id', $id) ?>
                                    <button class="btn btn-sm btn-label-secondary">پیش‌فرض</button>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                                <?php if (!$server->is_default): ?>
                                    <?= Html::beginForm(['server-toggle'], 'post', ['class' => 'd-inline']) . Html::hiddenInput('id', $id) ?>
                                    <button class="btn btn-sm <?= $server->active ? 'btn-label-warning' : 'btn-label-success' ?>"><?= $server->active ? 'غیرفعال' : 'فعال' ?></button>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                                <?php if (!$server->is_default && $usage[$id] === 0): ?>
                                    <?= Html::beginForm(['server-delete'], 'post', ['class' => 'd-inline']) . Html::hiddenInput('id', $id) ?>
                                    <button class="btn btn-sm btn-label-danger js-confirm" data-confirm="سرور «<?= Html::encode($server->title) ?>» حذف شود؟"><i class="bx bx-trash"></i></button>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bx bx-info-circle me-1"></i>
        رمز عبور و کلیدهای محرمانه رمزنگاری‌شده در دیتابیس ذخیره می‌شوند و در این صفحه نمایش داده نمی‌شوند. برای تغییر، مقدار جدید را وارد کنید؛ خالی گذاشتن یعنی بدون تغییر.
    </div>
</div>

<div class="modal fade" id="server-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?= Html::beginForm(['server-save'], 'post', ['id' => 'server-form', 'class' => 'modal-content', 'autocomplete' => 'off']) ?>
        <div class="modal-header">
            <h5 class="modal-title">افزودن سرور</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="Server[_id]" value="">
            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label class="form-label" for="server-title">نام سرور *</label>
                    <input type="text" id="server-title" name="Server[title]" class="form-control" required maxlength="100" placeholder="مثلاً کلاس مجازی دانشکدگان مدیریت">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="server-type">نوع سرور *</label>
                    <?= Html::dropDownList('Server[type]', ClassroomServers::TYPE_ADOBE, ClassroomServers::TYPES, ['id' => 'server-type', 'class' => 'form-select']) ?>
                </div>
            </div>
            <?php foreach (ClassroomServers::FIELDS as $type => $fields): ?>
                <div class="server-fields" data-type="<?= $type ?>" <?= $type === ClassroomServers::TYPE_ADOBE ? '' : 'style="display:none"' ?>>
                    <hr>
                    <h6 class="text-muted small"><?= Html::encode(ClassroomServers::TYPES[$type]) ?></h6>
                    <div class="row">
                        <?php foreach ($fields as $key => $field):
                            $name = 'Server[' . $type . '][' . $key . ']';
                            $inputId = 'server-' . $type . '-' . $key;
                            $options = ['id' => $inputId, 'class' => 'form-control', 'disabled' => $type !== ClassroomServers::TYPE_ADOBE];
                            if (!empty($field['placeholder']))
                                $options['placeholder'] = $field['placeholder'];
                            if ($field['required'] && empty($field['secret']))
                                $options['required'] = true;
                            ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="<?= $inputId ?>"><?= Html::encode($field['label']) ?><?= $field['required'] ? ' *' : '' ?></label>
                                <?php if ($field['type'] === 'select'): ?>
                                    <?= Html::dropDownList($name, $field['default'], $field['options'], array_merge($options, ['class' => 'form-select'])) ?>
                                <?php elseif ($field['type'] === 'password'): ?>
                                    <?= Html::passwordInput($name, '', array_merge($options, ['data-secret' => '1', 'dir' => 'ltr', 'autocomplete' => 'new-password', 'maxlength' => 500])) ?>
                                    <small class="text-muted secret-hint" style="display:none">مقدار فعلی ذخیره شده است؛ برای تغییر مقدار جدید وارد کنید.</small>
                                <?php elseif ($field['type'] === 'number'): ?>
                                    <?= Html::textInput($name, '', array_merge($options, ['dir' => 'ltr', 'inputmode' => 'numeric', 'data-input' => 'digits', 'maxlength' => 6])) ?>
                                <?php else: ?>
                                    <?= Html::textInput($name, '', array_merge($options, ['dir' => 'ltr', 'maxlength' => 300])) ?>
                                <?php endif; ?>
                                <?php if (!empty($field['hint'])): ?><small class="text-muted d-block"><?= Html::encode($field['hint']) ?></small><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>
