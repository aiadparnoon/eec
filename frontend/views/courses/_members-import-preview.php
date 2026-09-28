<?php
/**
 * پیش‌نمایش فایل اکسل اعضا: شرایط دوره، هزینه، خطای هر ردیف/ستون. با هر خطا دکمه‌ی ثبت نمایش داده نمی‌شود.
 *
 * @var $this yii\web\View
 * @var $analysis array خروجی CourseMembersImport::analyze()
 * @var $course app\models\Courses
 * @var $token string|null
 */
use app\components\CourseMembersImport;
use app\components\UsersImport;
use yii\helpers\Html;

$fa = function ($n) { return UsersImport::faDigits($n); };
$money = function ($n) { return UsersImport::faDigits(number_format((float) $n)); };
$pricing = $analysis['pricing'];
$states = [
    'new' => ['حساب جدید', 'success'],
    'existing' => ['کاربر موجود', 'info'],
    'invalid' => ['دارای خطا', 'danger'],
];
$errors = [];
foreach ($analysis['rows'] as $row)
    foreach ($row['errors'] as $col => $message)
        $errors[] = 'ردیف ' . $fa($row['line']) . '، ستون ' . $col . ' (' . CourseMembersImport::COLUMNS[$col] . '): ' . $message;
?>
<?php if (!empty($analysis['blockers'])): ?>
    <div class="alert alert-danger">
        <h6 class="alert-heading mb-2"><i class="bx bx-block me-1"></i>امکان ثبت وجود ندارد</h6>
        <ul class="mb-0 ps-3"><?php foreach ($analysis['blockers'] as $blocker): ?><li><?= Html::encode($blocker) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if (!empty($analysis['rows'])): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-label-primary p-2">تعداد ردیف: <?= $fa(count($analysis['rows'])) ?></span>
        <span class="badge bg-label-success p-2">حساب جدید: <?= $fa($analysis['newCount']) ?></span>
        <span class="badge bg-label-info p-2">کاربر موجود: <?= $fa($analysis['existingCount']) ?></span>
        <span class="badge bg-label-<?= $analysis['errorCount'] ? 'danger' : 'secondary' ?> p-2">خطا: <?= $fa($analysis['errorCount']) ?></span>
    </div>
<?php endif; ?>

<?php if ($pricing !== null && $pricing['error'] === null && !empty($analysis['rows'])): ?>
    <div class="border rounded p-3 mb-3">
        <?php if ($pricing['free']): ?>
            <i class="bx bx-gift text-success me-1"></i> «افزودن عضو بدون کیف پول» برای این دوره/واحد فعال است؛ هزینه‌ای کسر نمی‌شود.
        <?php else: ?>
            <div class="row g-2 small">
                <div class="col-6 col-md-3"><span class="text-muted d-block">سهم واحد هر نفر</span><b><?= $money($pricing['perPerson']) ?></b> تومان</div>
                <div class="col-6 col-md-3"><span class="text-muted d-block">مبلغ کل قابل کسر</span><b><?= $money($analysis['total']) ?></b> تومان</div>
                <div class="col-6 col-md-3"><span class="text-muted d-block">موجودی کیف پول کارگزار</span><b class="<?= $analysis['wallet'] < $analysis['total'] ? 'text-danger' : '' ?>"><?= $money($analysis['wallet']) ?></b> تومان</div>
                <div class="col-6 col-md-3"><span class="text-muted d-block">موجودی پس از ثبت</span><b><?= $analysis['wallet'] >= $analysis['total'] ? $money($analysis['wallet'] - $analysis['total']) : '—' ?></b></div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php foreach ($analysis['warnings'] as $warning): ?>
    <div class="alert alert-warning py-2"><i class="bx bx-info-circle me-1"></i><?= Html::encode($warning) ?></div>
<?php endforeach; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6 class="alert-heading mb-2"><i class="bx bx-error-circle me-1"></i>فایل دارای خطا است؛ هیچ‌کس ثبت نمی‌شود. پس از اصلاح، دوباره بارگذاری کنید.</h6>
        <ul class="mb-0 ps-3" style="max-height: 160px; overflow-y: auto;">
            <?php foreach ($errors as $error): ?><li><?= Html::encode($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($analysis['rows'])): ?>
    <div class="table-responsive border rounded" style="max-height: 380px;">
        <table class="table table-sm mb-0 text-nowrap">
            <thead class="table-light" style="position: sticky; top: 0;">
            <tr>
                <th>ردیف</th>
                <?php foreach (CourseMembersImport::COLUMNS as $col => $title): ?><th><?= $col ?> — <?= Html::encode($title) ?></th><?php endforeach; ?>
                <th>نتیجه</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($analysis['rows'] as $row): ?>
                <tr class="<?= $row['state'] === 'invalid' ? 'table-danger' : '' ?>">
                    <td><?= $fa($row['line']) ?></td>
                    <?php foreach (CourseMembersImport::COLUMNS as $col => $title):
                        $value = $row['values'][$col];
                        if ($col === 'G' && in_array($value, ['1', '2'], true))
                            $value = $value === '1' ? 'مرد' : 'زن';
                        $hasError = isset($row['errors'][$col]);
                        ?>
                        <td class="<?= $hasError ? 'bg-danger text-white' : '' ?>" <?= in_array($col, ['C', 'D', 'E', 'F'], true) ? 'dir="ltr"' : '' ?>>
                            <?= $value === '' ? ($hasError ? '<em>خالی</em>' : '<span class="text-muted">—</span>') : Html::encode($value) ?>
                            <?php if ($hasError): ?><div class="small text-wrap" style="min-width: 140px"><?= Html::encode($row['errors'][$col]) ?></div><?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td><span class="badge bg-label-<?= $states[$row['state']][1] ?>"><?= Html::encode($states[$row['state']][0]) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($token !== null && $analysis['ok']): ?>
    <?= Html::beginForm(['members-import', '_id' => (string) $course->_id], 'post', ['class' => 'd-flex flex-wrap justify-content-between align-items-center mt-3 gap-2', 'id' => 'members-import-form']) ?>
        <small class="text-muted">
            همه‌ی ردیف‌ها بدون خطا هستند. دانشپذیران به واحد دوره اضافه می‌شوند؛ رمز اولیه‌ی حساب‌های جدید کد ملی است و در اولین ورود باید تغییر کند.
            فایل بلافاصله پس از ثبت حذف می‌شود و هنگام ثبت دوباره بررسی می‌شود.
        </small>
        <?= Html::hiddenInput('token', $token) ?>
        <button type="submit" class="btn btn-success" onclick="this.disabled = true; this.form.submit();">
            <i class="bx bx-check me-1"></i>ثبت نهایی <?= $fa(count($analysis['rows'])) ?> نفر
            <?php if (!$pricing['free']): ?>و کسر <?= $money($analysis['total']) ?> تومان<?php endif; ?>
        </button>
    <?= Html::endForm() ?>
<?php endif; ?>
