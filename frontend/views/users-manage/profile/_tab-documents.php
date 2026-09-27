<?php
/**
 * تب «مدارک»: تصویر کارت ملی و آخرین مدرک تحصیلی (issuance_certificate_information).
 *
 * @var $student app\models\Users
 */
use yii\helpers\Html;
use yii\helpers\Url;

$info = is_array($student->issuance_certificate_information) ? $student->issuance_certificate_information : [];
$documents = [
    'id_file' => ['تصویر کارت ملی', 'bx-id-card'],
    'degree_education_file' => ['تصویر آخرین مدرک تحصیلی', 'bx-award'],
];
?>
<div class="card">
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($documents as $type => $doc):
                $file = !empty($info[$type]) && is_scalar($info[$type]) ? (string) $info[$type] : '';
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                $url = Url::to(['document', 'id' => (string) $student->_id, 'type' => $type]);
                ?>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-label-primary p-2 rounded me-2"><i class="bx <?= $doc[1] ?>"></i></span>
                            <h6 class="mb-0"><?= Html::encode($doc[0]) ?></h6>
                        </div>
                        <?php if ($file === ''): ?>
                            <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted py-4 bg-lighter rounded">بارگذاری نشده</div>
                        <?php else: ?>
                            <?php if ($isImage): ?>
                                <a href="<?= Html::encode(Url::to(['document', 'id' => (string) $student->_id, 'type' => $type, 'inline' => 1])) ?>" target="_blank" class="d-block mb-3 text-center bg-lighter rounded p-2">
                                    <img src="<?= Html::encode(Url::to(['document', 'id' => (string) $student->_id, 'type' => $type, 'inline' => 1])) ?>" alt="<?= Html::encode($doc[0]) ?>" class="img-fluid rounded" style="max-height: 220px" onerror="this.replaceWith(Object.assign(document.createElement('span'), {className: 'text-muted', textContent: 'فایل روی سرور پیدا نشد'}))">
                                </a>
                            <?php else: ?>
                                <div class="flex-grow-1 d-flex align-items-center justify-content-center py-4 bg-lighter rounded mb-3"><i class="bx bx-file" style="font-size: 3rem"></i></div>
                            <?php endif; ?>
                            <a href="<?= Html::encode($url) ?>" class="btn btn-sm btn-label-primary mt-auto"><i class="bx bx-download me-1"></i>دانلود</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
