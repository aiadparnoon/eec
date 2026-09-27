<?php
/**
 * پیش‌نمایش فایل اکسل: خطاها به تفکیک ردیف و ستون؛ تا رفع خطاها دکمه‌ی ثبت غیرفعال است.
 *
 * @var $analysis array خروجی UsersImport::analyze()
 * @var $token string|null
 * @var $collegeNames string[]
 */
use app\components\UsersImport;
use yii\helpers\Html;

$fa = function ($n) { return UsersImport::faDigits($n); };
$columns = UsersImport::COLUMNS;
$states = [
    'new' => ['جدید', 'success'],
    'existing' => ['موجود؛ به واحد اضافه می‌شود', 'info'],
    'existing_same' => ['موجود؛ بدون تغییر', 'secondary'],
    'invalid' => ['دارای خطا', 'danger'],
];
$errors = [];
foreach ($analysis['rows'] as $row)
    foreach ($row['errors'] as $col => $message)
        $errors[] = 'ردیف ' . $fa($row['line']) . '، ستون ' . $col . ': ' . $message;
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge bg-label-primary p-2">تعداد ردیف: <?= $fa(count($analysis['rows'])) ?></span>
    <span class="badge bg-label-success p-2">دانشپذیر جدید: <?= $fa($analysis['newCount']) ?></span>
    <span class="badge bg-label-info p-2">از قبل موجود: <?= $fa($analysis['existingCount']) ?></span>
    <span class="badge bg-label-<?= $analysis['errorCount'] ? 'danger' : 'secondary' ?> p-2">خطا: <?= $fa($analysis['errorCount']) ?></span>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6 class="alert-heading mb-2"><i class="bx bx-error-circle me-1"></i>فایل دارای خطا است؛ پس از اصلاح، دوباره بارگذاری کنید.</h6>
        <ul class="mb-0 ps-3" style="max-height: 160px; overflow-y: auto;">
            <?php foreach ($errors as $error): ?><li><?= Html::encode($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="table-responsive border rounded" style="max-height: 420px;">
    <table class="table table-sm mb-0">
        <thead class="table-light" style="position: sticky; top: 0;">
        <tr>
            <th>ردیف</th>
            <?php foreach ($columns as $col => $title): ?><th><?= $col ?> — <?= Html::encode($title) ?></th><?php endforeach; ?>
            <th>نتیجه</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($analysis['rows'] as $row): ?>
            <tr class="<?= $row['state'] === 'invalid' ? 'table-danger' : '' ?>">
                <td><?= $fa($row['line']) ?></td>
                <?php foreach ($columns as $col => $title):
                    $value = $row['values'][$col];
                    if ($col === 'D' && $value !== '')
                        $value = str_repeat('•', min(8, mb_strlen($value)));
                    if ($col === 'G' && in_array($value, ['1', '2'], true))
                        $value = $value === '1' ? 'مرد' : 'زن';
                    $hasError = isset($row['errors'][$col]);
                    ?>
                    <td class="<?= $hasError ? 'bg-danger text-white' : '' ?>" <?= $hasError ? 'title="' . Html::encode($row['errors'][$col]) . '"' : '' ?> <?= in_array($col, ['C', 'D', 'E', 'F'], true) ? 'dir="ltr"' : '' ?>>
                        <?= $value === '' ? ($hasError ? '<em>خالی</em>' : '<span class="text-muted">—</span>') : Html::encode($value) ?>
                        <?php if ($hasError): ?><div class="small"><?= Html::encode($row['errors'][$col]) ?></div><?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <td><span class="badge bg-label-<?= $states[$row['state']][1] ?>"><?= Html::encode($states[$row['state']][0]) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= Html::beginForm(['add_user_from_excel'], 'post', ['class' => 'd-flex flex-wrap justify-content-between align-items-center mt-3 gap-2']) ?>
    <small class="text-muted">
        <?= empty($collegeNames) ? 'دانشپذیران جدید بدون واحد ثبت می‌شوند.' : 'دانشپذیران به ' . Html::encode(implode('، ', $collegeNames)) . ' اضافه می‌شوند.' ?>
        فایل بلافاصله پس از ثبت حذف می‌شود.
    </small>
    <?= Html::hiddenInput('token', $token) ?>
    <button type="submit" class="btn btn-success" <?= $token === null ? 'disabled' : '' ?>>
        <i class="bx bx-check me-1"></i>ثبت نهایی (<?= $fa($analysis['newCount']) ?> جدید، <?= $fa($analysis['existingCount']) ?> موجود)
    </button>
<?= Html::endForm() ?>
