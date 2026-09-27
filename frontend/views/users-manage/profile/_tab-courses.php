<?php
/**
 * تب «دوره‌ها»: برای هر دوره یک کارت بازشونده با وضعیت، کلاس آنلاین، حضور و غیاب،
 * گواهی، وضعیت مالی و آزمون/تمرین/نظرسنجی.
 *
 * @var $this yii\web\View
 * @var $student app\models\Users
 * @var $profile app\components\StudentProfile
 * @var $registrants array
 */
use app\components\StudentAccess;
use app\components\StudentProfile;
use app\components\UsersDirectory;
use app\components\UsersImport;
use app\components\classroom\ClassroomPlatforms;
use yii\helpers\Html;
use yii\helpers\Url;

$fa = function ($n) { return UsersImport::faDigits($n); };
$courses = $profile->courses();
$studentId = (string) $student->_id;
$collegeTitles = UsersDirectory::collegeTitles();
?>
<?php if (empty($courses)): ?>
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="bx bx-book d-block mb-2" style="font-size: 2.5rem"></i>این دانشپذیر در هیچ دوره‌ای ثبت‌نام نکرده است.
    </div></div>
    <?php return; ?>
<?php endif; ?>

<div class="accordion" id="student-courses">
<?php foreach ($courses as $index => $entry):
    $course = $entry['course'];
    $item = $entry['item'];
    $row = $entry['row'];
    $courseId = isset($item['_id']) ? (string) $item['_id'] : '';
    $uid = 'c' . $index;
    list($statusLabel, $statusColor) = UsersDirectory::courseStatus(isset($item['status']) ? $item['status'] : '0');
    $status = isset($item['status']) ? (string) $item['status'] : '0';
    $online = ClassroomPlatforms::hasOnlineClass($course);
    $platform = ClassroomPlatforms::forCourse($course);
    $canManage = StudentAccess::canManageCourse($course);
    $itemRegistrant = isset($item['registrant']) && is_scalar($item['registrant']) ? (string) $item['registrant'] : '';
    $registrantInfo = null;
    if ($itemRegistrant !== '')
        $registrantInfo = UsersDirectory::describeUsername($itemRegistrant, (string) $student->username, $registrants);
    ?>
    <div class="card accordion-item mb-3 <?= $index === 0 ? 'active' : '' ?>">
        <h2 class="accordion-header" id="head-<?= $uid ?>">
            <button type="button" class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>" data-bs-toggle="collapse" data-bs-target="#body-<?= $uid ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="body-<?= $uid ?>">
                <span class="d-flex align-items-center flex-wrap gap-2 w-100 me-3">
                    <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-book-open"></i></span></span>
                    <span class="d-flex flex-column me-auto">
                        <span class="fw-semibold"><?= Html::encode(StudentProfile::courseTitle($course)) ?></span>
                        <small class="text-muted">
                            <?php if ($course !== null): ?>
                                <?= Html::encode(isset($collegeTitles[(string) $course->college]) ? $collegeTitles[(string) $course->college] : '') ?>
                                · <?= Html::encode(UsersDirectory::courseType($course->type)) ?>
                                · <?= Html::encode(UsersDirectory::contentType($course->content_type)) ?>
                            <?php else: ?>
                                شناسه: <?= Html::encode($courseId) ?>
                            <?php endif; ?>
                        </small>
                    </span>
                    <span class="badge bg-label-<?= $statusColor ?>"><?= Html::encode($statusLabel) ?></span>
                </span>
            </button>
        </h2>
        <div id="body-<?= $uid ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="head-<?= $uid ?>">
            <div class="accordion-body">
            <?php if ($course === null): ?>
                <div class="alert alert-warning mb-0">اطلاعات این دوره در سامانه پیدا نشد (ممکن است حذف شده باشد).</div>
            <?php else: ?>
                <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                    <?php foreach (['status' => 'وضعیت و کلاس', 'attendance' => 'حضور و غیاب', 'certificate' => 'گواهی', 'finance' => 'مالی', 'exams' => 'آزمون و تمرین'] as $key => $label): ?>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link <?= $key === 'status' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#<?= $uid ?>-<?= $key ?>" role="tab"><?= Html::encode($label) ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content p-0 shadow-none">

                    <!-- وضعیت و کلاس آنلاین -->
                    <div class="tab-pane fade show active" id="<?= $uid ?>-status" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">وضعیت دانشپذیر در دوره</small>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge bg-label-<?= $statusColor ?> fs-6"><?= Html::encode($statusLabel) ?></span>
                                        <?php if ($canManage): ?>
                                            <?= Html::beginForm(['change_user_course_status'], 'post', ['class' => 'd-inline', 'onsubmit' => "return confirm('وضعیت دانشپذیر در این دوره تغییر کند؟');"]) ?>
                                                <?= Html::hiddenInput('Users[_id]', $studentId) ?>
                                                <?= Html::hiddenInput('courseId', $courseId) ?>
                                                <?= Html::hiddenInput('row', $row) ?>
                                                <?php if ($status === '1'): ?>
                                                    <button type="submit" class="btn btn-sm btn-label-danger"><i class="bx bx-pause-circle me-1"></i>غیرفعال کردن</button>
                                                <?php elseif ($status === '2' || !$online): ?>
                                                    <button type="submit" class="btn btn-sm btn-label-success"><i class="bx bx-play-circle me-1"></i>فعال کردن</button>
                                                <?php endif; ?>
                                            <?= Html::endForm() ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($registrantInfo !== null): ?>
                                        <small class="text-muted d-block mt-2">ثبت‌نام توسط: <?= Html::encode($registrantInfo['name']) ?><?= $registrantInfo['roleLabel'] !== '' ? ' (' . Html::encode($registrantInfo['roleLabel']) . ')' : '' ?></small>
                                    <?php endif; ?>
                                    <?php if (!$canManage): ?>
                                        <small class="text-muted d-block mt-2"><i class="bx bx-lock-alt"></i> این دوره متعلق به واحد دیگری است و فقط قابل مشاهده است.</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">کلاس آنلاین</small>
                                    <?php if (!$online): ?>
                                        <span class="text-muted">این دوره کلاس آنلاین ندارد.</span>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>
                                                <?= Html::encode($platform->title()) ?>:
                                                <?php if ($status === '1'): ?>
                                                    <span class="badge bg-label-success">ثبت‌شده و فعال</span>
                                                <?php elseif ($status === '2'): ?>
                                                    <span class="badge bg-label-secondary">دسترسی قطع شده</span>
                                                <?php else: ?>
                                                    <span class="badge bg-label-warning">ثبت‌نشده / ناموفق</span>
                                                <?php endif; ?>
                                            </span>
                                            <?php if ($status === '0' && $canManage): ?>
                                                <?= Html::beginForm(['reregister_course'], 'post', ['class' => 'd-inline']) ?>
                                                    <?= Html::hiddenInput('Users[_id]', $studentId) ?>
                                                    <?= Html::hiddenInput('courseId', $courseId) ?>
                                                    <?= Html::hiddenInput('row', $row) ?>
                                                    <button type="submit" class="btn btn-sm btn-warning text-nowrap"><i class="bx bx-refresh me-1"></i>ثبت مجدد</button>
                                                <?= Html::endForm() ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($status === '0'): ?>
                                            <small class="text-muted d-block mt-2">«ثبت مجدد» همه‌ی دانشپذیرانِ ثبت‌نشده‌ی این دوره را دوباره در <?= Html::encode($platform->title()) ?> ثبت می‌کند.</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- حضور و غیاب -->
                    <div class="tab-pane fade" id="<?= $uid ?>-attendance" role="tabpanel">
                        <?php
                        $sessions = $online ? $platform->attendance($student, $course) : [];
                        $present = 0;
                        $minutes = 0;
                        foreach ($sessions as $s) {
                            if ($s['present']) $present++;
                            $minutes += $s['minutes'];
                        }
                        ?>
                        <?php if (!$online): ?>
                            <div class="text-muted">این دوره کلاس آنلاین ندارد.</div>
                        <?php elseif (empty($sessions)): ?>
                            <div class="text-muted">هنوز جلسه‌ای برای این دوره برگزار نشده است.</div>
                        <?php else:
                            $percent = round($present * 100 / count($sessions)); ?>
                            <div class="d-flex flex-wrap align-items-center gap-4 mb-3">
                                <div><small class="text-muted d-block">حضور</small><span class="fw-semibold"><?= $fa($present) ?> از <?= $fa(count($sessions)) ?> جلسه</span></div>
                                <div><small class="text-muted d-block">مجموع زمان حضور</small><span class="fw-semibold"><?= $fa(floor($minutes / 60)) ?> ساعت و <?= $fa($minutes % 60) ?> دقیقه</span></div>
                                <div class="flex-grow-1" style="min-width: 160px">
                                    <small class="text-muted d-block">درصد حضور: <?= $fa($percent) ?>٪</small>
                                    <div class="progress" style="height: 8px"><div class="progress-bar bg-<?= $percent >= 75 ? 'success' : ($percent >= 50 ? 'warning' : 'danger') ?>" style="width: <?= $percent ?>%"></div></div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>#</th><th>درس</th><th>تاریخ جلسه</th><th>ساعت جلسه</th><th>وضعیت</th><th>ورود</th><th>خروج</th><th>مدت</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($sessions as $n => $s): ?>
                                        <tr>
                                            <td><?= $fa($n + 1) ?></td>
                                            <td><?= Html::encode($s['lesson']) ?></td>
                                            <td><?= UsersDirectory::jdate('Y/m/d', $s['from']) ?></td>
                                            <td><?= $s['from'] ? UsersDirectory::jdate('H:i', $s['from']) : '-' ?> تا <?= $s['to'] ? UsersDirectory::jdate('H:i', $s['to']) : '-' ?></td>
                                            <td><?= $s['present'] ? '<span class="badge bg-label-success">حاضر</span>' : '<span class="badge bg-label-danger">غایب</span>' ?></td>
                                            <td><?= $s['enter'] ? UsersDirectory::jdate('H:i', $s['enter']) : '-' ?></td>
                                            <td><?= $s['exit'] ? UsersDirectory::jdate('H:i', $s['exit']) : '-' ?></td>
                                            <td><?= $s['present'] ? $fa($s['minutes']) . ' دقیقه' : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- گواهی -->
                    <div class="tab-pane fade" id="<?= $uid ?>-certificate" role="tabpanel">
                        <?php $certificate = $profile->certificate($courseId); ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <?php if ($certificate === null): ?>
                                <div class="text-muted">درخواست صدور گواهی‌ای برای این دوره ثبت نشده است.</div>
                            <?php else:
                                list($certLabel, $certColor) = StudentProfile::certificateStatus($certificate->status); ?>
                                <div class="row g-3 flex-grow-1">
                                    <div class="col-sm-4"><small class="text-muted d-block">وضعیت درخواست</small><span class="badge bg-label-<?= $certColor ?>"><?= Html::encode($certLabel) ?></span></div>
                                    <div class="col-sm-4"><small class="text-muted d-block">تاریخ درخواست</small><span class="fw-semibold"><?= Html::encode(is_scalar($certificate->request) && $certificate->request !== '' ? $certificate->request : UsersDirectory::jdate('Y/m/d', hexdec(substr((string) $certificate->_id, 0, 8)))) ?></span></div>
                                    <div class="col-sm-4"><small class="text-muted d-block">شماره سریال گواهی</small><span class="fw-semibold" dir="ltr"><?= Html::encode($certificate->serial_number ?: '—') ?></span></div>
                                </div>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="وب‌سرویس صدور گواهی دیجیتال هنوز آماده نیست">
                                <i class="bx bx-certification me-1"></i>صدور گواهی دیجیتال <span class="badge bg-label-secondary ms-1">به‌زودی</span>
                            </button>
                        </div>
                    </div>

                    <!-- مالی -->
                    <div class="tab-pane fade" id="<?= $uid ?>-finance" role="tabpanel">
                        <?= $this->render('_finance', ['profile' => $profile, 'orders' => $profile->ordersForCourse($courseId), 'installments' => $profile->installmentsForCourse($courseId), 'showCourse' => false]) ?>
                    </div>

                    <!-- آزمون، تمرین، نظرسنجی -->
                    <div class="tab-pane fade" id="<?= $uid ?>-exams" role="tabpanel">
                        <?php
                        $tests = $profile->tests($courseId);
                        $assignments = $profile->assignments($courseId);
                        $surveys = $profile->surveys($courseId);
                        ?>
                        <h6 class="mb-2">آزمون‌ها</h6>
                        <?php if (empty($tests)): ?>
                            <p class="text-muted">آزمونی شرکت نکرده است.</p>
                        <?php else: ?>
                            <div class="table-responsive mb-3"><table class="table table-sm">
                                <thead><tr><th>آزمون</th><th>درس</th><th>دفعات شرکت</th><th>نمره نهایی</th><th>نتیجه</th></tr></thead>
                                <tbody>
                                <?php foreach ($tests as $t): ?>
                                    <tr>
                                        <td><?= Html::encode($t['title']) ?></td>
                                        <td><?= Html::encode($t['lesson']) ?></td>
                                        <td><?= $fa($t['tries']) ?></td>
                                        <td><?= $t['score'] === null ? '-' : $fa(round($t['score'], 1)) ?></td>
                                        <td><?= $t['pass'] === null ? '-' : ($t['pass'] ? '<span class="badge bg-label-success">قبول</span>' : '<span class="badge bg-label-danger">مردود</span>') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>

                        <h6 class="mb-2">تمرین‌ها</h6>
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted">تمرینی ارسال نکرده است.</p>
                        <?php else: ?>
                            <div class="table-responsive mb-3"><table class="table table-sm">
                                <thead><tr><th>درس</th><th>تاریخ ارسال</th><th>فایل</th></tr></thead>
                                <tbody>
                                <?php foreach ($assignments as $a): ?>
                                    <tr>
                                        <td><?= Html::encode($profile->lessonTitle($a->lesson_id)) ?></td>
                                        <td><?= UsersDirectory::jdate('Y/m/d H:i', hexdec(substr((string) $a->_id, 0, 8))) ?></td>
                                        <td>
                                            <?php if (is_scalar($a->content) && $a->content !== '' && $a->assignment_id): ?>
                                                <a href="<?= Url::to(['manage-course-contents/download_exercise', 'filename' => (string) $a->content, 'course_id' => $courseId, 'assignment_id' => (string) $a->assignment_id]) ?>" class="btn btn-sm btn-label-primary"><i class="bx bx-download me-1"></i>دانلود</a>
                                            <?php else: ?>-<?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>

                        <h6 class="mb-2">نظرسنجی‌ها</h6>
                        <?php if (empty($surveys)): ?>
                            <p class="text-muted mb-0">نظرسنجی‌ای تکمیل نکرده است.</p>
                        <?php else: ?>
                            <div class="table-responsive"><table class="table table-sm mb-0">
                                <thead><tr><th>نظرسنجی</th><th>درس</th><th>تاریخ تکمیل</th></tr></thead>
                                <tbody>
                                <?php foreach ($surveys as $s): ?>
                                    <tr><td><?= Html::encode($s['title']) ?></td><td><?= Html::encode($s['lesson']) ?></td><td><?= UsersDirectory::jdate('Y/m/d', $s['date']) ?></td></tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
