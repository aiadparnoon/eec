<?php
use yii\helpers\Html;

$this->title = 'مشاهده جزئیات نظرسنجی '.$examDetails->title;

// تابع helper برای رنگ‌های progress bar
function getProgressBarColor($index)
{
    $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'];
    return $colors[$index % count($colors)];
}
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <nav aria-label="breadcrumb">
        <ol class="lh-1-85 breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="<?= Yii::$app->urlManager->createAbsoluteUrl('survey-maker/management-survey') ?>">نظرسنجی مدیریتی</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">مشاهده جزئیات نظرسنجی</a>
            </li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-xl-12">
            <h6 class="text-muted">مشاهده نتایج نظرسنجی <?= $examDetails->title ?></h6>
            <div class="card text-center mb-3">
                <div class="card-header border-bottom primary-font">
                    <ul class="nav nav-pills" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-within-card-active" aria-controls="navs-pills-within-card-active" aria-selected="true">
                                نتایج کلی
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-within-card-link" aria-controls="navs-pills-within-card-link" aria-selected="false" tabindex="-1">
                                مشاهده جزئیات
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade active show" id="navs-pills-within-card-active" role="tabpanel">
                        <?php if (!empty($userPolls) && !empty($examDetails->questions)): ?>
                            <?php
                            // جمع‌آوری آمار از تمام پاسخ‌ها
                            $pollStats = [];
                            $totalRespondents = count($userPolls);

                            foreach ($examDetails->questions as $questionIndex => $question) {
                                if (!isset($question['question_text']) || !isset($question['options'])) {
                                    continue;
                                }

                                $pollStats[$questionIndex] = [];

                                // Initialize counters for each option
                                foreach ($question['options'] as $optionIndex => $option) {
                                    $pollStats[$questionIndex][$optionIndex] = 0;
                                }

                                // Count responses from all users
                                foreach ($userPolls as $userPoll) {
                                    $userQuestions = $userPoll->questions;

                                    // ابتدا مطمئن شویم که $userQuestions آرایه است
                                    if (!is_array($userQuestions)) {
                                        continue;
                                    }

                                    // ساختار قدیمی: [0]['questions'][$index]
                                    if (isset($userQuestions[0]) && is_array($userQuestions[0]) && isset($userQuestions[0]['questions']) && is_array($userQuestions[0]['questions'])) {
                                        $userQuestionData = $userQuestions[0]['questions'];

                                        if (isset($userQuestionData[$questionIndex]) && is_array($userQuestionData[$questionIndex])) {
                                            $userAnswer = $userQuestionData[$questionIndex];

                                            // برای سوالات radio (پاسخ تکی)
                                            if (isset($userAnswer['user_answer'])) {
                                                if (is_array($userAnswer['user_answer'])) {
                                                    // پاسخ چندگانه
                                                    foreach ($userAnswer['user_answer'] as $answerIndex) {
                                                        if (isset($pollStats[$questionIndex][$answerIndex])) {
                                                            $pollStats[$questionIndex][$answerIndex]++;
                                                        }
                                                    }
                                                } else {
                                                    // پاسخ تکی
                                                    $answerIndex = $userAnswer['user_answer'];
                                                    if (isset($pollStats[$questionIndex][$answerIndex])) {
                                                        $pollStats[$questionIndex][$answerIndex]++;
                                                    }
                                                }
                                            }

                                            // برای سوالات checkbox (پاسخ چندگانه) - ساختار جدیدتر
                                            if (isset($userAnswer['answers']) && is_array($userAnswer['answers'])) {
                                                foreach ($userAnswer['answers'] as $answerIndex) {
                                                    if (isset($pollStats[$questionIndex][$answerIndex])) {
                                                        $pollStats[$questionIndex][$answerIndex]++;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    // ساختار جدید: [0][$index]
                                    elseif (isset($userQuestions[0]) && is_array($userQuestions[0])) {
                                        $userQuestionData = $userQuestions[0];

                                        if (isset($userQuestionData[$questionIndex]) && is_array($userQuestionData[$questionIndex])) {
                                            $userAnswer = $userQuestionData[$questionIndex];

                                            // برای سوالات radio (پاسخ تکی)
                                            if (isset($userAnswer['answer'])) {
                                                $answerIndex = $userAnswer['answer'];
                                                if (isset($pollStats[$questionIndex][$answerIndex])) {
                                                    $pollStats[$questionIndex][$answerIndex]++;
                                                }
                                            }

                                            // برای سوالات checkbox (پاسخ چندگانه)
                                            if (isset($userAnswer['answers']) && is_array($userAnswer['answers'])) {
                                                foreach ($userAnswer['answers'] as $answerIndex) {
                                                    if (isset($pollStats[$questionIndex][$answerIndex])) {
                                                        $pollStats[$questionIndex][$answerIndex]++;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            ?>

                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="card mb-4">
                                        <div class="card-header heading-color">
                                            <h5 class="mb-0 text-white">آمار پاسخ‌ها</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php foreach ($examDetails->questions as $questionIndex => $question): ?>
                                                <?php if (!isset($question['question_text']) || !isset($question['options'])) continue; ?>

                                                <div class="question-block mb-4 p-3 border rounded">
                                                    <h6 class="text-primary mb-3">
                                                        <?= ($questionIndex + 1) . '. ' . Html::encode($question['question_text']) ?>
                                                    </h6>

                                                    <?php
                                                    $questionTotal = array_sum($pollStats[$questionIndex] ?? []);

                                                    foreach ($question['options'] as $optionIndex => $option) {
                                                        if (!isset($option['title'])) continue;

                                                        $count = $pollStats[$questionIndex][$optionIndex] ?? 0;
                                                        $percentage = $totalRespondents > 0 ? round(($count / $totalRespondents) * 100, 1) : 0;
                                                        ?>
                                                        <div class="mb-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="fw-medium">
                                                                    <?= Html::encode($option['title']) ?>
                                                                </span>
                                                                <span class="text-muted">
                                                                    <?= $count ?> رأی (<?= $percentage ?>%)
                                                                </span>
                                                            </div>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar"
                                                                     role="progressbar"
                                                                     style="width: <?= $percentage ?>%; background-color: <?= getProgressBarColor($optionIndex) ?>;"
                                                                     aria-valuenow="<?= $percentage ?>"
                                                                     aria-valuemin="0"
                                                                     aria-valuemax="100">
                                                                    <?= $percentage ?>%
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>

                                                    <div class="mt-3 p-2 bg-light rounded">
                                                        <small class="text-muted">
                                                            تعداد پاسخ‌دهندگان: <?= $totalRespondents ?> نفر |
                                                            نوع سوال: <?= ($question['type'] == 2) ? 'چند انتخابی' : 'تک انتخابی' ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php else: ?>
                            <div class="alert alert-warning">
                                <?php if (empty($userPolls)): ?>
                                    هنوز پاسخی برای این نظرسنجی ثبت نشده است.
                                <?php else: ?>
                                    سوالی برای این نظرسنجی تعریف نشده است.
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="navs-pills-within-card-link" role="tabpanel">
                        <h4 class="card-title primary-font mb-4">جزئیات پاسخ‌های کاربران</h4>
                        <?php if (!empty($userPolls)): ?>
                            <div class="row">
                            <?php foreach ($userPolls as $userIndex => $userPoll): ?>
                                <?php
                                $userAnswers = [];
                                $userQuestions = $userPoll->questions;

                                if (is_array($userQuestions)) {
                                    // بررسی هر دو ساختار
                                    if (isset($userQuestions[0]['questions']) && is_array($userQuestions[0]['questions'])) {
                                        $userAnswers = $userQuestions[0]['questions'];
                                    } elseif (isset($userQuestions[0]) && is_array($userQuestions[0])) {
                                        $userAnswers = $userQuestions[0];
                                    }
                                }
                                $userDetails = $this->context->user_detail($userPoll->user_id);
                                ?>

                                <div class="col-xl-6 col-lg-12 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">پاسخ‌های کاربر <?= $userIndex + 1 ?></h6>
                                            <small>نام کاربر:
                                                <?php
                                                if($userDetails != null)
                                                    echo $userDetails->first_name.' '.$userDetails->last_name;
                                                else
                                                    echo '-';
                                                ?></small>
                                        </div>
                                        <div class="card-body">
                                            <div class="user-responses">
                                                <?php foreach ($examDetails->questions as $qIndex => $question): ?>
                                                    <div class="response-item mb-3 pb-2 border-bottom">
                                                        <h6 class="text-primary mb-2">
                                                            <small>سوال <?= $qIndex + 1 ?>:</small>
                                                            <?= Html::encode($question['question_text']) ?>
                                                        </h6>

                                                        <div class="response-answer">
                                                            <?php if (isset($userAnswers[$qIndex]) && is_array($userAnswers[$qIndex])): ?>
                                                                <?php
                                                                $answer = $userAnswers[$qIndex];
                                                                $answerText = 'پاسخ داده نشده';

                                                                // ساختار قدیمی
                                                                if (isset($answer['user_answer'])) {
                                                                    if (is_array($answer['user_answer'])) {
                                                                        // چندگانه
                                                                        $selectedAnswers = [];
                                                                        foreach ($answer['user_answer'] as $ansIndex) {
                                                                            if (isset($question['options'][$ansIndex]['title'])) {
                                                                                $selectedAnswers[] = $question['options'][$ansIndex]['title'];
                                                                            }
                                                                        }
                                                                        $answerText = !empty($selectedAnswers) ? implode('، ', $selectedAnswers) : 'پاسخ نامعتبر';
                                                                    } else {
                                                                        // تکی
                                                                        if (isset($question['options'][$answer['user_answer']]['title'])) {
                                                                            $answerText = $question['options'][$answer['user_answer']]['title'];
                                                                        } else {
                                                                            $answerText = 'پاسخ نامعتبر';
                                                                        }
                                                                    }
                                                                }
                                                                // ساختار جدید
                                                                elseif (isset($answer['answer'])) {
                                                                    // تکی
                                                                    if (isset($question['options'][$answer['answer']]['title'])) {
                                                                        $answerText = $question['options'][$answer['answer']]['title'];
                                                                    } else {
                                                                        $answerText = 'پاسخ نامعتبر';
                                                                    }
                                                                }
                                                                elseif (isset($answer['answers']) && is_array($answer['answers'])) {
                                                                    // چندگانه
                                                                    $selectedAnswers = [];
                                                                    foreach ($answer['answers'] as $ansIndex) {
                                                                        if (isset($question['options'][$ansIndex]['title'])) {
                                                                            $selectedAnswers[] = $question['options'][$ansIndex]['title'];
                                                                        }
                                                                    }
                                                                    $answerText = !empty($selectedAnswers) ? implode('، ', $selectedAnswers) : 'پاسخ نامعتبر';
                                                                }
                                                                ?>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="fw-medium">پاسخ:</span>
                                                                    <span class="badge bg-success"><?= $answerText ?></span>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <span class="fw-medium">پاسخ:</span>
                                                                    <span class="badge bg-secondary">پاسخ داده نشده</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="response-meta mt-2">
                                                            <small class="text-muted">
                                                                نوع سوال: <?= ($question['type'] == 2) ? 'چند انتخابی' : 'تک انتخابی' ?>
                                                                <?php if (isset($question['required']) && $question['required'] == 1): ?>
                                                                    | <span class="text-danger">اجباری</span>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">
                                                زمان ثبت: <?= date('Y-m-d H:i', time()) ?> |
                                                وضعیت: <span class="text-success">تکمیل شده</span>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <?php // هر دو کاربر در یک ردیف نمایش داده شوند ?>
                                <?php if (($userIndex + 1) % 2 == 0): ?>
                                    </div><div class="row">
                                <?php endif; ?>

                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle"></i>
                                هیچ پاسخی برای این نظرسنجی ثبت نشده است.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .question-block {
        background: #f8f9fa;
        border-left: 4px solid #007bff !important;
    }
    .heading-color {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
    }
    .progress-bar {
        font-weight: bold;
        font-size: 12px;
    }
    .table th {
        text-align: center;
        vertical-align: middle;
    }
    .user-responses {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
    }
    .response-item {
        padding-bottom: 10px;
    }
    .response-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    .response-answer .badge {
        font-size: 0.85rem;
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
</style>