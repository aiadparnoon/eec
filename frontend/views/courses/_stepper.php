<?php
/**
 * نمودار مرحله‌ای فرآیند ثبت، بررسی و تأیید دوره (صورتجلسه‌ی ۱۴۰۳/۴/۳۱، بند ۶).
 *
 * @var $this yii\web\View
 * @var $course app\models\Courses
 * @var $compact bool نسخه‌ی کوچک (فقط نقطه‌ها، برای مودال فهرست)
 */
use app\components\CourseStatus;
use app\components\UsersImport;
use yii\helpers\Html;

$steps = CourseStatus::steps($course);
$compact = !empty($compact);
$icons = ['done' => 'bx-check', 'current' => 'bx-loader-circle', 'todo' => '', 'skipped' => 'bx-minus', 'failed' => 'bx-x'];
$colors = ['done' => 'success', 'current' => 'primary', 'todo' => 'secondary', 'skipped' => 'secondary', 'failed' => 'danger'];
$stateText = ['done' => 'انجام شده', 'current' => 'مرحله‌ی فعلی', 'todo' => 'در ادامه', 'skipped' => 'لازم نشد', 'failed' => 'رد شده'];

$this->registerCss(<<<CSS
.course-stepper { display: flex; flex-wrap: wrap; gap: .5rem 0; counter-reset: step; }
.course-stepper .cs-step { flex: 1 1 0; min-width: 88px; text-align: center; position: relative; padding: 0 .25rem; }
.course-stepper .cs-step:not(:last-child)::after { content: ''; position: absolute; top: 16px; left: -50%; width: 100%; height: 2px; background: rgba(67, 89, 113, .15); z-index: 0; }
[dir=rtl] .course-stepper .cs-step:not(:last-child)::after { left: auto; right: 50%; }
.course-stepper .cs-dot { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; position: relative; z-index: 1; font-size: .85rem; font-weight: 600; }
.course-stepper .cs-title { display: block; font-size: .75rem; margin-top: .35rem; line-height: 1.35; }
.course-stepper .cs-skipped { opacity: .45; }
.course-stepper .cs-current .cs-title { font-weight: 700; }
.course-stepper .cs-loop { border-top: 1px dashed rgba(255, 171, 0, .6); padding-top: .25rem; }
CSS
);
?>
<div class="course-stepper <?= $compact ? 'course-stepper-compact' : '' ?>" role="list" aria-label="فرآیند بررسی دوره">
    <?php foreach ($steps as $step):
        $state = $step['state'];
        $inLoop = $step['number'] >= 4 && $step['number'] <= 8; ?>
        <div class="cs-step cs-<?= $state ?> <?= $inLoop ? 'cs-loop' : '' ?>" role="listitem" title="<?= Html::encode($step['title'] . ' — ' . $stateText[$state]) ?>">
            <span class="cs-dot bg-<?= $state === 'todo' || $state === 'skipped' ? 'label-' : '' ?><?= $colors[$state] ?> <?= $state === 'done' || $state === 'current' || $state === 'failed' ? 'text-white' : '' ?>">
                <?php if ($icons[$state] !== '' && $state !== 'current'): ?><i class="bx <?= $icons[$state] ?>"></i><?php else: ?><?= UsersImport::faDigits($step['number']) ?><?php endif; ?>
            </span>
            <?php if (!$compact): ?><span class="cs-title"><?= Html::encode($step['title']) ?></span><?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php if (!$compact): ?>
    <small class="text-muted d-block mt-2"><i class="bx bx-info-circle"></i> مراحل با خط‌چین نارنجی (۴ تا ۸) فقط وقتی طی می‌شوند که دوره برای اصلاح بازگردانده شود.</small>
<?php endif; ?>
