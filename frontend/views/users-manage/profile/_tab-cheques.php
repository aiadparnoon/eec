<?php
/**
 * تب «چک‌ها»: اقساطی که با چک پرداخت می‌شوند.
 *
 * @var $profile app\components\StudentProfile
 */
use app\components\StudentProfile;
use app\components\UsersImport;
use app\models\Courses;
use yii\helpers\Html;

$cheques = $profile->cheques();
$fa = function ($n) { return UsersImport::faDigits($n); };
$titles = [];
?>
<div class="card">
    <div class="card-body">
        <?php if (empty($cheques)): ?>
            <div class="text-center py-5 text-muted"><i class="bx bx-receipt d-block mb-2" style="font-size: 2.5rem"></i>چکی ثبت نشده است.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>#</th><th>دوره</th><th>سریال چک</th><th>مبلغ (تومان)</th><th>تاریخ سررسید</th><th>وضعیت</th><th>تاریخ وصول</th></tr></thead>
                    <tbody>
                    <?php foreach ($cheques as $n => $cheque):
                        $m = $cheque['maturity'];
                        $courseId = (string) $cheque['installment']->course_id;
                        if (!array_key_exists($courseId, $titles))
                            $titles[$courseId] = preg_match('/^[a-f0-9]{24}$/i', $courseId) ? StudentProfile::courseTitle(Courses::findOne($courseId)) : '-';
                        list($label, $color) = StudentProfile::maturityLabel($cheque['state']);
                        if ($cheque['state'] === 'paid')
                            $label = 'وصول شده';
                        $mpi = isset($m['payment_info']) && is_array($m['payment_info']) ? $m['payment_info'] : [];
                        ?>
                        <tr>
                            <td><?= $fa($n + 1) ?></td>
                            <td><?= Html::encode($titles[$courseId]) ?></td>
                            <td dir="ltr" class="text-end"><?= Html::encode(isset($m['serial']) ? $m['serial'] : '-') ?></td>
                            <td><?= $fa(number_format(isset($m['amount']) ? (float) $m['amount'] : 0)) ?></td>
                            <td><?= Html::encode(isset($m['date']) ? $m['date'] : '-') ?></td>
                            <td><span class="badge bg-label-<?= $color ?>"><?= Html::encode($label) ?></span></td>
                            <td><?= Html::encode(isset($mpi['date']) ? $mpi['date'] : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
