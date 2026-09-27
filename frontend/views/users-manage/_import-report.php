<?php
/**
 * گزارش نتیجه‌ی ورود از اکسل.
 * @var $report array ['created' => [], 'added' => [], 'unchanged' => [], 'failed' => []]
 */
use app\components\UsersImport;
use yii\helpers\Html;

$sections = [
    'created' => ['دانشپذیران جدید ایجاد شده', 'success', 'bx-user-plus'],
    'added' => ['دانشپذیران موجود که به دانشکده‌ی شما اضافه شدند', 'info', 'bx-transfer'],
    'unchanged' => ['دانشپذیران موجود بدون تغییر (از قبل عضو بودند)', 'secondary', 'bx-user-check'],
    'failed' => ['ناموفق', 'danger', 'bx-error'],
];
?>
<div class="card mb-4 border border-success">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="bx bx-spreadsheet me-1"></i>نتیجه‌ی ورود از فایل اکسل</h5>
        <button type="button" class="btn-close" onclick="this.closest('.card').remove()" aria-label="بستن"></button>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($sections as $key => $section):
                $items = isset($report[$key]) ? $report[$key] : [];
                if ($key === 'failed' && empty($items)) continue; ?>
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-label-<?= $section[1] ?> rounded p-2 me-2"><i class="bx <?= $section[2] ?>"></i></span>
                            <h6 class="mb-0 flex-grow-1"><?= Html::encode($section[0]) ?></h6>
                            <span class="fw-bold"><?= UsersImport::faDigits(count($items)) ?></span>
                        </div>
                        <?php if (!empty($items)): ?>
                            <ul class="list-unstyled small mb-0" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($items as $username => $name): ?>
                                    <li><?= Html::encode($name) ?> <span class="text-muted" dir="ltr">(<?= Html::encode($username) ?>)</span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
