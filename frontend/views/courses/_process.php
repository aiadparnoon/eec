<?php
/**
 * نمودار مراحل دوره از ثبت تا پایان — مشترک دوره‌های کوتاه‌مدت و میان‌مدت (طرح نمودار دوره‌های میان‌مدت).
 * فقط نمایشی است. مسیر «کارگزار» (ثبت کارگزار ← بررسی واحد ← بررسی مدیر سیستم) در برابر مسیر «واحد»
 * با وجود کارگزار روی دوره تشخیص داده می‌شود؛ هر دو مسیر در وضعیت ۲ به هم می‌رسند.
 *
 * @var $this yii\web\View
 * @var $course app\models\Courses
 */
use app\components\CourseStatus;
use yii\helpers\Html;

$hasBroker = is_array($course->broker) ? !empty($course->broker['_id']) : (is_object($course->broker) && !empty($course->broker->_id));
$status = (string) $course->status;
$corrected = $course->modified === true && in_array($status, ['2', '7'], true);

$steps = $hasBroker
    ? [
        ['label' => 'ثبت دوره', 'subtitle' => 'توسط کارگزار', 'icon' => 'bx-briefcase-alt-2'],
        ['label' => 'بررسی واحد', 'subtitle' => 'تأیید یا اصلاح', 'icon' => 'bx-buildings'],
        ['label' => 'بررسی مدیر سیستم', 'subtitle' => 'تأیید یا اصلاح', 'icon' => 'bx-user-check'],
        ['label' => 'فعال‌سازی دوره', 'subtitle' => 'ثبت‌نام و برگزاری', 'icon' => 'bx-play-circle'],
        ['label' => 'پایان دوره', 'subtitle' => '', 'icon' => 'bx-flag-checkered'],
    ]
    : [
        ['label' => 'ثبت دوره', 'subtitle' => 'توسط واحد', 'icon' => 'bx-edit-alt'],
        ['label' => 'بررسی مدیر سیستم', 'subtitle' => 'تأیید یا اصلاح', 'icon' => 'bx-user-check'],
        ['label' => 'فعال‌سازی دوره', 'subtitle' => 'ثبت‌نام و برگزاری', 'icon' => 'bx-play-circle'],
        ['label' => 'پایان دوره', 'subtitle' => '', 'icon' => 'bx-flag-checkered'],
    ];
$states = array_fill(0, count($steps), 'upcoming');
$notes = array_fill(0, count($steps), null); // توضیح زیر مرحله (به‌جای subtitle)
$banner = null;
$returnedBy = (string) $course->returned_by;
// شماره‌ی مرحله‌ها در هر مسیر
$unit = $hasBroker ? 1 : null;
$admin = $hasBroker ? 2 : 1;
$active = $admin + 1;
$fill = function ($upTo) use (&$states) {
    for ($i = 0; $i < $upTo; $i++)
        $states[$i] = 'done';
};
switch ($status) {
    case '3': // پیش‌نویس
        $states[0] = 'current';
        $notes[0] = 'پیش‌نویس؛ هنوز ارسال نشده';
        break;
    case '7': // در انتظار بررسی واحد
        if ($unit !== null) {
            $fill($unit);
            $states[$unit] = 'current';
            $notes[$unit] = $corrected ? 'اصلاح شد؛ در انتظار تأیید واحد' : 'در انتظار تأیید واحد';
            if ($corrected && $returnedBy === 'admin')
                $states[$admin] = 'danger'; // دوره قبلاً توسط مدیر سیستم برگشت خورده بود
        } else {
            $states[0] = 'current';
        }
        break;
    case '8': // واحد برای اصلاح به کارگزار برگرداند
        $states[0] = 'current';
        $notes[0] = 'در انتظار اصلاح توسط کارگزار';
        if ($unit !== null) {
            $states[$unit] = 'danger';
            $notes[$unit] = 'برگشت داده شد';
        }
        break;
    case '9': // واحد رد کرد
        $fill($unit !== null ? $unit : 0);
        if ($unit !== null) {
            $states[$unit] = 'danger';
            $notes[$unit] = 'رد شد';
        }
        $banner = ['danger', 'این دوره توسط واحد رد شده است'];
        break;
    case '2': // در انتظار مدیر سیستم
        $fill($admin);
        $states[$admin] = 'current';
        $notes[$admin] = $corrected ? 'اصلاح شد؛ در انتظار بررسی مجدد' : 'در انتظار بررسی';
        break;
    case '4': // مدیر سیستم برای اصلاح برگرداند
        $states[$admin] = 'danger';
        $notes[$admin] = 'برگشت داده شد';
        if ($unit !== null) {
            // دوره‌ی کارگزار: به مرحله‌ی واحد برمی‌گردد؛ کارگزار اصلاح می‌کند و واحد دوباره تأیید می‌کند
            $states[0] = 'done';
            $states[$unit] = 'current';
            $notes[$unit] = 'در انتظار اصلاح توسط کارگزار';
        } else {
            $states[0] = 'current';
            $notes[0] = 'در انتظار اصلاح توسط واحد';
        }
        break;
    case '5': // مدیر سیستم رد کرد
        $fill($admin);
        $states[$admin] = 'danger';
        $notes[$admin] = 'رد شد';
        $banner = ['danger', 'این دوره توسط مدیر سیستم رد شده است'];
        break;
    case '1': // تأیید و فعال
        $fill($active);
        $states[$active] = 'current';
        break;
    case '0': // تأیید شده ولی غیرفعال
        $fill($active);
        $states[$active] = 'current-inactive';
        $notes[$active] = 'غیرفعال';
        break;
    case '6': // پایان یافته
        $states = array_fill(0, count($steps), 'done');
        break;
    default:
        $states[0] = 'current';
}
if (!empty($course->rejection_reason) && in_array($status, ['4', '5', '8', '9'], true))
    $banner = [in_array($status, ['5', '9'], true) ? 'danger' : 'warning', 'دلیل: ' . (is_scalar($course->rejection_reason) ? (string) $course->rejection_reason : '')];

$classes = [
    'done' => 'bg-success border-success text-white',
    'current' => 'bg-primary border-primary text-white',
    'current-inactive' => 'bg-label-secondary border-secondary text-secondary',
    'warning' => 'bg-warning border-warning text-white',
    'danger' => 'bg-danger border-danger text-white',
    'upcoming' => 'bg-label-secondary border-secondary-subtle text-muted',
];
$filledUpTo = 0;
foreach ($states as $i => $state)
    if (in_array($state, ['done', 'current', 'current-inactive'], true))
        $filledUpTo = $i;

$this->registerCss(<<<CSS
.course-process-track { display: flex; align-items: flex-start; width: 100%; padding: 4px 4px 0; }
.course-process-step { display: flex; flex-direction: column; align-items: center; text-align: center; flex: 0 0 auto; min-width: 84px; }
.course-process-line { flex: 1 1 auto; height: 3px; margin: 22px 6px 0; border-radius: 2px; }
.course-process-circle { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border-width: 2px; border-style: solid; }
.course-process-step.is-upcoming .course-process-circle { opacity: .6; }
.course-process-label { margin-top: 8px; }
.course-process-title { display: block; font-size: .8125rem; font-weight: 600; }
.course-process-subtitle { display: block; font-size: .7rem; opacity: .75; }
.course-process-note { display: block; font-size: .72rem; font-weight: 600; max-width: 130px; margin: 2px auto 0; }
.course-process-step.is-danger .course-process-title { color: var(--bs-danger); }
.course-process-step.is-current .course-process-title { color: var(--bs-primary); }
.course-process-banner { margin-top: 10px; padding: 8px 14px; border-radius: 6px; font-size: .8125rem; }
@media (max-width: 767px) {
    .course-process-subtitle { display: none; }
    .course-process-circle { width: 34px; height: 34px; font-size: 1rem; }
    .course-process-title { font-size: .68rem; }
    .course-process-step { min-width: 52px; }
    .course-process-line { margin-top: 16px; }
}
CSS
);
?>
<div class="card mb-3">
    <div class="card-body pb-2">
        <?php list($statusText, $statusColor) = CourseStatus::label($course); ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0"><i class="bx bx-git-commit me-1"></i>مراحل دوره از ثبت تا پایان</h6>
            <span class="badge bg-label-<?= Html::encode($statusColor) ?>">وضعیت فعلی: <?= Html::encode($statusText) ?></span>
        </div>
        <div class="course-process-track" role="list" aria-label="مراحل دوره">
            <?php foreach ($steps as $i => $step): ?>
                <?php if ($i > 0): ?>
                    <div class="course-process-line <?= $i <= $filledUpTo ? 'bg-success' : 'bg-label-secondary' ?>"></div>
                <?php endif; ?>
                <div class="course-process-step is-<?= $states[$i] ?>" role="listitem">
                    <span class="course-process-circle <?= $classes[$states[$i]] ?>"><i class="bx <?= Html::encode($step['icon']) ?>"></i></span>
                    <div class="course-process-label">
                        <span class="course-process-title"><?= Html::encode($step['label']) ?></span>
                        <?php if ($notes[$i] !== null): ?>
                            <span class="course-process-note text-<?= $states[$i] === 'danger' ? 'danger' : ($states[$i] === 'current' ? 'primary' : 'muted') ?>"><?= Html::encode($notes[$i]) ?></span>
                        <?php elseif ($step['subtitle'] !== ''): ?>
                            <span class="course-process-subtitle"><?= Html::encode($step['subtitle']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($banner !== null): ?>
            <div class="course-process-banner bg-label-<?= $banner[0] ?> text-<?= $banner[0] ?>"><?= Html::encode($banner[1]) ?></div>
        <?php endif; ?>
    </div>
</div>
