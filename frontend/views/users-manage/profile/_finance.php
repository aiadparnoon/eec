<?php
/**
 * جدول سفارش‌ها و اقساط — مشترک تب «مالی» هر دوره و تب «پرداختی‌ها».
 *
 * @var $profile app\components\StudentProfile
 * @var $orders app\models\Orders[]
 * @var $installments app\models\Installments[]
 * @var $showCourse bool ستون نام دوره
 */
use app\components\StudentProfile;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\models\Courses;
use yii\helpers\Html;

$fa = function ($n) { return UsersImport::faDigits($n); };
$money = function ($n) { return UsersImport::faDigits(number_format((float) $n)); };
$collegeTitles = UsersDirectory::collegeTitles();
$courseTitles = [];
$courseTitle = function ($id) use (&$courseTitles) {
    $id = (string) $id;
    if (!array_key_exists($id, $courseTitles))
        $courseTitles[$id] = preg_match('/^[a-f0-9]{24}$/i', $id) ? StudentProfile::courseTitle(Courses::findOne($id)) : '-';
    return $courseTitles[$id];
};
?>
<?php if (empty($orders) && empty($installments)): ?>
    <div class="text-muted">پرداختی ثبت نشده است.</div>
    <?php return; ?>
<?php endif; ?>

<?php if (!empty($orders)): ?>
    <div class="table-responsive mb-3">
        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <?php if ($showCourse): ?><th>دوره</th><?php endif; ?>
                <th>نوع پرداخت</th>
                <th>تاریخ</th>
                <th>سفارش / پیگیری</th>
                <th>مبلغ (تومان)</th>
                <th>سهم واحد</th>
                <th>سهم کارگزار</th>
            </tr>
            </thead>
            <tbody>
            <?php
            // اول پرداخت‌های موفق، بعد ناموفق/لغوشده (هر گروه از جدید به قدیم)
            $successful = [];
            $unsuccessful = [];
            foreach ($orders as $order) {
                if (StudentProfile::isSuccessfulOrder($order))
                    $successful[] = $order;
                else
                    $unsuccessful[] = $order;
            }
            $colspan = $showCourse ? 7 : 6;
            if (empty($successful)): ?>
                <tr><td colspan="<?= $colspan ?>" class="text-muted text-center">پرداخت موفقی ثبت نشده است.</td></tr>
            <?php endif;
            foreach (array_merge($successful, $unsuccessful) as $i => $order):
                list($type, $channel) = StudentProfile::paymentType($order);
                $shares = StudentProfile::shares($order);
                $pi = is_array($order->payment_info) ? $order->payment_info : [];
                $paid = StudentProfile::isSuccessfulOrder($order);
                if ($i === count($successful)): ?>
                    <tr class="table-light"><td colspan="<?= $colspan ?>" class="fw-semibold small text-muted"><i class="bx bx-error-circle me-1"></i>پرداخت‌های ناموفق / لغوشده (<?= $fa(count($unsuccessful)) ?>)</td></tr>
                <?php endif;
                $titles = [];
                if (is_array($order->orders))
                    foreach ($order->orders as $o)
                        if (isset($o['_id'])) $titles[] = $courseTitle($o['_id']);
                ?>
                <tr class="<?= $paid ? '' : 'text-muted' ?>">
                    <?php if ($showCourse): ?><td class="text-wrap" style="min-width: 160px"><?= Html::encode(implode('، ', $titles)) ?></td><?php endif; ?>
                    <td><span class="fw-semibold"><?= Html::encode($type) ?></span><small class="d-block text-muted"><?= Html::encode($channel) ?></small></td>
                    <td><?= Html::encode(isset($pi['date']) ? $pi['date'] : UsersDirectory::jdate('Y/m/d', hexdec(substr((string) $order->_id, 0, 8)))) ?></td>
                    <td>
                        <span class="d-block" dir="ltr" style="text-align:right"><?= Html::encode(isset($pi['order_id']) && $pi['order_id'] !== 'wallet' ? $pi['order_id'] : '-') ?></span>
                        <small class="text-muted d-block" dir="ltr" style="text-align:right"><?= Html::encode(isset($pi['tref']) ? $pi['tref'] : '') ?></small>
                    </td>
                    <td>
                        <span class="<?= $paid ? 'fw-semibold' : 'text-decoration-line-through' ?> d-block"><?= $money($paid ? StudentProfile::orderPaidAmount($order) : $order->amount) ?></span>
                        <?php if ($order->is_canceled): ?><span class="badge bg-label-secondary">لغو شده</span>
                        <?php elseif ($paid): ?><span class="badge bg-label-success">موفق</span>
                        <?php else: ?><span class="badge bg-label-danger">ناموفق</span><?php endif; ?>
                    </td>
                    <?php if (!$paid): ?>
                        <td>-</td><td>-</td>
                    <?php else: ?>
                    <td>
                        <?= $money($shares['college']) ?>
                        <?php if ($shares['collegeId'] !== '' && isset($collegeTitles[$shares['collegeId']])): ?><small class="d-block text-muted"><?= Html::encode($collegeTitles[$shares['collegeId']]) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?= $shares['percent'] > 0 ? $money($shares['broker']) : '-' ?>
                        <?php if ($shares['percent'] > 0): ?><small class="d-block text-muted"><?= Html::encode(trim($shares['brokerName'] . ' ' . $fa($shares['percent']) . '٪')) ?></small><?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php foreach ($installments as $installment):
    $maturities = is_array($installment->maturities) ? $installment->maturities : [];
    if (empty($maturities)) continue;
    $sum = ['paid' => 0, 'overdue' => 0, 'pending' => 0];
    foreach ($maturities as $m)
        $sum[StudentProfile::maturityState($m)] += isset($m['amount']) ? (float) $m['amount'] : 0;
    ?>
    <div class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <h6 class="mb-0">
                اقساط <?= $showCourse ? Html::encode($courseTitle($installment->course_id)) : '' ?>
                <?php if ($installment->is_cheque): ?><span class="badge bg-label-info ms-1">پرداخت با چک</span><?php endif; ?>
            </h6>
            <div class="d-flex gap-2 small">
                <span class="badge bg-label-success">پرداخت‌شده: <?= $money($sum['paid']) ?></span>
                <span class="badge bg-label-danger">سررسید گذشته: <?= $money($sum['overdue']) ?></span>
                <span class="badge bg-label-warning">سررسید نشده: <?= $money($sum['pending']) ?></span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>مبلغ (تومان)</th><th>تاریخ سررسید</th><?php if ($installment->is_cheque): ?><th>سریال چک</th><?php endif; ?><th>وضعیت</th><th>تاریخ پرداخت</th><th>شماره پیگیری</th></tr></thead>
                <tbody>
                <?php foreach ($maturities as $n => $m):
                    $state = StudentProfile::maturityState($m);
                    list($label, $color) = StudentProfile::maturityLabel($state);
                    $mpi = isset($m['payment_info']) && is_array($m['payment_info']) ? $m['payment_info'] : [];
                    ?>
                    <tr>
                        <td><?= $fa($n + 1) ?></td>
                        <td><?= $money(isset($m['amount']) ? $m['amount'] : 0) ?></td>
                        <td><?= Html::encode(isset($m['date']) ? $m['date'] : '-') ?></td>
                        <?php if ($installment->is_cheque): ?><td dir="ltr" class="text-end"><?= Html::encode(isset($m['serial']) ? $m['serial'] : '-') ?></td><?php endif; ?>
                        <td><span class="badge bg-label-<?= $color ?>"><?= $label ?></span></td>
                        <td><?= Html::encode(isset($mpi['date']) ? $mpi['date'] : '-') ?></td>
                        <td dir="ltr" class="text-end"><?= Html::encode(isset($mpi['tref']) ? $mpi['tref'] : '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
