<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="py-3 breadcrumb-wrapper mb-4">
        <span class="text-muted fw-light"> </span> نظرسنجی
    </h4>
    <?php
    if($userPolls != null) {
        foreach ($userPolls as $pollIndex => $poll) {
            ?>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card mb-4">
                        <h5 class="card-header heading-color text-white mt-5"><?= $poll->title ?></h5>
                        <div class="card-body">
                            <?php
                            if(!empty($poll->questions)) {
                                $form = ActiveForm::begin([
                                    'action' => ['save_poll'],
                                    "method" => "post",
                                    'options' => [
                                        'class' => '',
                                        'enctype' => 'multipart/form-data',
                                        'id' => 'poll-form-' . $pollIndex
                                    ],
                                ]);

                                echo Html::hiddenInput('exam_id', (string)$poll->_id);
                                echo Html::hiddenInput('poll_title', $poll->title);

                                foreach ($poll->questions as $questionIndex => $question)
                                {
                                    if (!isset($question['question_text']) || !isset($question['options']))
                                    {
                                        continue;
                                    }

                                    $isRequired = isset($question['required']) && $question['required'] == 1;
                                    ?>
                                    <div class="question-block mb-4 p-3 border rounded">
                                        <h6 class="text-primary mb-3">
                                            <?= ($questionIndex + 1) . '. ' . Html::encode($question['question_text']) ?>
                                            <?php if ($isRequired): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </h6>

                                        <?php
                                        // تعیین نوع input بر اساس type
                                        $inputType = ($question['type'] == 2) ? 'checkbox' : 'radio';
                                        $namePattern = ($question['type'] == 2) ?
                                            "questions[{$questionIndex}][answers][]" :
                                            "questions[{$questionIndex}][answer]";

                                        // افزودن attribute required برای validation client-side
                                        $inputOptions = [
                                            'class' => 'form-check-input',
                                            'id' => "q{$questionIndex}_opt0" // ID اولیه
                                        ];

                                        if ($isRequired && $inputType == 'radio') {
                                            $inputOptions['required'] = 'required';
                                            $inputOptions['data-required'] = 'true';
                                            $inputOptions['data-question-index'] = $questionIndex;
                                        }

                                        foreach ($question['options'] as $optionIndex => $option) {
                                            if (!isset($option['title'])) {
                                                continue;
                                            }
                                            $value = $optionIndex;
                                            $inputId = "q{$questionIndex}_opt{$optionIndex}";
                                            $inputOptions['id'] = $inputId;
                                            ?>
                                            <div class="form-check form-check-success mb-2">
                                                <?php if ($inputType == 'radio'): ?>
                                                    <?= Html::radio(
                                                        $namePattern,
                                                        false,
                                                        array_merge($inputOptions, ['value' => $value])
                                                    ) ?>
                                                <?php else: ?>
                                                    <?= Html::checkbox(
                                                        $namePattern,
                                                        false,
                                                        array_merge($inputOptions, ['value' => $value])
                                                    ) ?>
                                                <?php endif; ?>

                                                <label class="form-check-label" for="<?= $inputId ?>">
                                                    <?= Html::encode($option['title']) ?>
                                                </label>
                                            </div>
                                            <?php
                                        }
                                        ?>

                                        <!-- فیلدهای مخفی برای اطلاعات سوال -->
                                        <?= Html::hiddenInput("questions[{$questionIndex}][question_text]", $question['question_text']) ?>
                                        <?= Html::hiddenInput("questions[{$questionIndex}][type]", $question['type']) ?>
                                        <?= Html::hiddenInput("questions[{$questionIndex}][required]", $question['required']) ?>

                                        <!-- نمایش خطا برای سوالات اجباری -->
                                        <div class="invalid-feedback" id="error-<?= $questionIndex ?>" style="display: none;">
                                            لطفاً این سوال را پاسخ دهید
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">ثبت نظرسنجی</button>
                                </div>
                                <?php
                                ActiveForm::end();
                            } else {
                                echo '<div class="alert alert-warning">هیچ سوالی برای این نظرسنجی وجود ندارد.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript برای validation -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('poll-form-<?= $pollIndex ?>');

                    form.addEventListener('submit', function(e) {
                        let isValid = true;
                        const requiredQuestions = form.querySelectorAll('[data-required="true"]');

                        // بررسی سوالات اجباری
                        requiredQuestions.forEach(function(radio) {
                            const questionIndex = radio.getAttribute('data-question-index');
                            const questionRadios = form.querySelectorAll('[name="questions[' + questionIndex + '][answer]"]');
                            let isAnswered = false;

                            questionRadios.forEach(function(r) {
                                if (r.checked) {
                                    isAnswered = true;
                                }
                            });

                            if (!isAnswered) {
                                isValid = false;
                                document.getElementById('error-' + questionIndex).style.display = 'block';
                                // هایلایت کردن سوال پاسخ داده نشده
                                const questionBlock = radio.closest('.question-block');
                                questionBlock.style.borderColor = '#dc3545';
                            } else {
                                document.getElementById('error-' + questionIndex).style.display = 'none';
                            }
                        });

                        if (!isValid) {
                            e.preventDefault();
                            // اسکرول به اولین سوال پاسخ داده نشده
                            const firstError = form.querySelector('.question-block[style*="border-color: #dc3545"]');
                            if (firstError) {
                                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    });

                    // حذف خطا هنگام انتخاب گزینه
                    form.querySelectorAll('.form-check-input').forEach(function(input) {
                        input.addEventListener('change', function() {
                            const questionIndex = this.getAttribute('data-question-index');
                            if (questionIndex) {
                                document.getElementById('error-' + questionIndex).style.display = 'none';
                                const questionBlock = this.closest('.question-block');
                                questionBlock.style.borderColor = '';
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    } else {
        echo '<div class="alert alert-info">نظرسنجی فعالی وجود ندارد.</div>';
    }
    ?>
</div>

<style>
    .question-block {
        background: #f8f9fa;
        border-left: 4px solid #007bff !important;
        transition: border-color 0.3s ease;
    }
    .heading-color {
        color: #2c3e50;
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
    }
    .form-check {
        padding-left: 2rem;
    }
    .form-check-input {
        margin-left: -2rem;
    }
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.5rem;
    }
    .text-danger {
        color: #dc3545 !important;
        font-weight: bold;
    }
</style>