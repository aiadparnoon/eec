<?php
/**
 * تب «دروس دوره» در ویرایش دوره‌ی میان‌مدت — کارت هر درس مثل فرم ثبت؛ ذخیره/حذف هر درس جداگانه (AJAX با پیام خطا زیر فیلد).
 * قواعد سمت سرور: PackageForm::lessonRow (تاریخ داخل بازه‌ی دوره، اتمام حداقل یک روز بعد، مدرس همان واحد).
 *
 * @var $this yii\web\View
 * @var $model app\models\Courses
 * @var $teachers app\models\Teachers[] مدرسان واحد دوره
 * @var $remainingLessons array [id => عنوان] دروس واحد
 * @var $allowEdit bool
 */
use app\components\CourseOptions;
use app\components\UsersImport;
use yii\helpers\Html;
use yii\helpers\Json;

$teacherOptions = [];
foreach ($teachers as $teacher)
    $teacherOptions[(string) $teacher->_id] = trim($teacher->first_name . ' ' . $teacher->last_name);
$lessons = is_array($model->lessons) ? array_values($model->lessons) : [];
$used = [];
foreach ($lessons as $lesson)
    $used[(string) $lesson['_id']] = true;
$addable = [];
foreach ($remainingLessons as $id => $title) {
    $title = CourseOptions::cleanTitle($title);
    if ($title !== '' && !isset($used[(string) $id]))
        $addable[(string) $id] = $title;
}
$titles = CourseOptions::namesById(\app\models\Lessons::class, array_keys($used), 'title');
$range = Json::htmlEncode(['from' => is_array($model->date) ? ($model->date['from'] ?? '') : '', 'to' => is_array($model->date) ? ($model->date['to'] ?? '') : '']);
$dis = $allowEdit ? [] : ['disabled' => true];

$this->registerJs(<<<JS
(function () {
    var range = $range;
    // تقویم هر درس: فقط داخل بازه‌ی دوره؛ اتمام حداقل یک روز بعد از شروع
    $('.js-lesson-form').each(function () {
        var form = this, from = $(form).find('[data-f="from"]')[0], to = $(form).find('[data-f="to"]')[0];
        if ($.fn.flatpickr && from && to && !from.disabled) {
            var opts = {locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true, minDate: range.from || null, maxDate: range.to || null};
            $(from).flatpickr(opts); $(to).flatpickr(opts);
            EecDateRange.bind(from, to, 'تاریخ اتمام درس باید حداقل یک روز بعد از تاریخ شروع آن باشد');
        }
        EecSelect.init(form);
        EecValidate.bind(form, {ajax: true});
    });
    // دکمه‌ی حذف: پرسش تأیید
    $(document).on('click', '.js-lesson-delete', function (e) {
        if (!confirm('این درس از دوره حذف شود؟ کلاس آنلاین آن هم حذف می‌شود.')) e.preventDefault();
    });
})();
JS
, \yii\web\View::POS_END);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">تاریخ هر درس باید داخل بازه‌ی دوره (<?= Html::encode(UsersImport::faDigits(str_replace('-', '/', ($model->date['from'] ?? '') . ' تا ' . ($model->date['to'] ?? '')))) ?>) باشد و اتمام آن حداقل یک روز بعد از شروع.</div>
    <?php if ($allowEdit): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-lesson-modal"><i class="bx bx-plus me-1"></i>افزودن درس</button>
    <?php endif; ?>
</div>
<?php if (empty($lessons)): ?>
    <div class="alert alert-warning">هنوز درسی برای این دوره ثبت نشده است.</div>
<?php endif; ?>
<div class="row g-3">
    <?php foreach ($lessons as $i => $lesson):
        $date = isset($lesson['date']) && is_array($lesson['date']) ? $lesson['date'] : [];
        $teacherId = isset($lesson['teachers']) ? (string) $lesson['teachers'] : '';
        $options = $teacherOptions;
        if ($teacherId !== '' && !isset($options[$teacherId]))
            $options[$teacherId] = 'مدرس فعلی (خارج از مدرسان واحد)';
        ?>
        <div class="col-md-6 col-xl-4">
            <?= Html::beginForm(['edit_course_in_package'], 'post', ['class' => 'border rounded p-3 h-100 js-lesson-form text-start']) ?>
                <?= Html::hiddenInput('Courses[_id]', (string) $model->_id) ?>
                <?= Html::hiddenInput('row', $i) ?>
                <h6 class="mb-3"><i class="bx bx-book-open me-1"></i><?= Html::encode($titles[(string) $lesson['_id']] ?? 'درس حذف‌شده') ?></h6>
                <div class="mb-2">
                    <label class="form-label small">مدرس *</label>
                    <?= Html::dropDownList("Courses[lessons][$i][teachers]", $teacherId, $options, ['class' => 'form-select', 'prompt' => 'جست‌وجو و انتخاب مدرس', 'required' => true] + $dis) ?>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">تاریخ شروع *</label><input type="text" name="Courses[lessons][<?= $i ?>][date][from]" data-f="from" value="<?= Html::encode($date['from'] ?? '') ?>" class="form-control" required autocomplete="off" <?= $allowEdit ? '' : 'disabled' ?>></div>
                    <div class="col-6"><label class="form-label small">تاریخ اتمام *</label><input type="text" name="Courses[lessons][<?= $i ?>][date][to]" data-f="to" value="<?= Html::encode($date['to'] ?? '') ?>" class="form-control" required autocomplete="off" <?= $allowEdit ? '' : 'disabled' ?>></div>
                    <div class="col-6"><label class="form-label small">ساعت شروع *</label><input type="text" name="Courses[lessons][<?= $i ?>][date][time]" value="<?= Html::encode($date['time'] ?? '') ?>" class="form-control" required placeholder="18:30" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" data-error-pattern="ساعت را به شکل ۱۸:۳۰ وارد کنید" dir="ltr" maxlength="5" <?= $allowEdit ? '' : 'disabled' ?>></div>
                    <div class="col-6"><label class="form-label small">مدت (ساعت) *</label><input type="text" name="Courses[lessons][<?= $i ?>][date][duration]" value="<?= Html::encode($date['duration'] ?? '') ?>" class="form-control" required inputmode="numeric" data-input="digits" dir="ltr" maxlength="3" <?= $allowEdit ? '' : 'disabled' ?>></div>
                    <div class="col-12"><label class="form-label small">مخفی کردن آرشیو</label><?= Html::dropDownList("Courses[lessons][$i][hide_archive]", !empty($lesson['hide_archive']) ? '1' : '0', ['0' => 'خیر', '1' => 'بله'], ['class' => 'form-select', 'data-no-select2' => 1] + $dis) ?></div>
                </div>
                <?php if ($allowEdit): ?>
                    <div class="d-flex gap-2 justify-content-end mt-3">
                        <?php if (count($lessons) > 1): ?>
                            <button type="submit" name="delete" value="1" class="btn btn-sm btn-label-danger js-lesson-delete" formnovalidate>حذف درس</button>
                        <?php endif; ?>
                        <button type="submit" name="edit" value="1" class="btn btn-sm btn-primary">ذخیره‌ی درس</button>
                    </div>
                <?php endif; ?>
            <?= Html::endForm() ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($allowEdit): ?>
<div class="modal fade" id="add-lesson-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <?= Html::beginForm(['add_lesson_to_package'], 'post', ['class' => 'modal-content js-lesson-form text-start']) ?>
            <div class="modal-header">
                <h5 class="modal-title">افزودن درس به دوره</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <?= Html::hiddenInput('_id', (string) $model->_id) ?>
                <div class="mb-3">
                    <label class="form-label">درس *</label>
                    <?= Html::dropDownList('Courses[lessons][_id]', null, $addable, ['class' => 'form-select', 'prompt' => empty($addable) ? 'درس دیگری در این واحد نیست' : 'جست‌وجو و انتخاب درس', 'required' => true]) ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">مدرس *</label>
                    <?= Html::dropDownList('Courses[lessons][teachers]', null, $teacherOptions, ['class' => 'form-select', 'prompt' => 'جست‌وجو و انتخاب مدرس', 'required' => true]) ?>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">تاریخ شروع *</label><input type="text" name="Courses[lessons][date][from]" data-f="from" class="form-control" required autocomplete="off"></div>
                    <div class="col-6"><label class="form-label">تاریخ اتمام *</label><input type="text" name="Courses[lessons][date][to]" data-f="to" class="form-control" required autocomplete="off"></div>
                    <div class="col-6"><label class="form-label">ساعت شروع *</label><input type="text" name="Courses[lessons][date][time]" class="form-control" required placeholder="18:30" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]" data-error-pattern="ساعت را به شکل ۱۸:۳۰ وارد کنید" dir="ltr" maxlength="5"></div>
                    <div class="col-6"><label class="form-label">مدت (ساعت) *</label><input type="text" name="Courses[lessons][date][duration]" class="form-control" required inputmode="numeric" data-input="digits" dir="ltr" maxlength="3"></div>
                    <div class="col-12"><label class="form-label">مخفی کردن آرشیو</label><?= Html::dropDownList('Courses[lessons][hide_archive]', '0', ['0' => 'خیر', '1' => 'بله'], ['class' => 'form-select', 'data-no-select2' => 1]) ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary" <?= empty($addable) ? 'disabled' : '' ?>>افزودن درس</button>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
<?php endif; ?>
