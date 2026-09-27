<?php
/**
 * مودال‌های مشترک فهرست و پروفایل. هر مودال یک بار ساخته و با data-* دکمه پر می‌شود
 * (به جای ساختن چند مودال برای هر ردیف).
 *
 * @var $this yii\web\View
 * @var $showAdd bool مودال‌های افزودن (فقط در فهرست)
 */
use app\components\StudentAccess;
use app\components\UsersDirectory;
use yii\helpers\Html;
use yii\helpers\Url;
use frontend\assets\InputGuardAsset;

InputGuardAsset::register($this);
$checkUrl = Url::to(['check_excel_file']);
$this->registerJs(<<<JS
$(document).on('click', '.js-edit-user', function () {
    var d = $(this).data();
    $('#edit-user-id').val(d.id);
    $('#edit-user-first').val(d.first);
    $('#edit-user-last').val(d.last);
    $('#edit-user-title').text(d.first + ' ' + d.last);
});
$(document).on('click', '.js-change-password', function () {
    var d = $(this).data();
    $('#password-user-id').val(d.id);
    $('#password-user-title').text(d.name);
    $('#password-user-input').val('');
});
$(document).on('click', '#excel-check', function (e) {
    e.preventDefault();
    var file = $('#excel-file')[0].files[0];
    if (!file) { $('#excel-result').html('<div class="alert alert-warning mb-0">ابتدا فایل را انتخاب کنید</div>'); return; }
    var fd = new FormData();
    fd.append('file', file);
    fd.append(yii.getCsrfParam(), yii.getCsrfToken());
    var btn = $(this).prop('disabled', true);
    $('#excel-result').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">در حال بررسی فایل…</div></div>');
    $.ajax({url: '$checkUrl', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json'})
        .done(function (res) { $('#excel-result').html(res.html); })
        .fail(function () { $('#excel-result').html('<div class="alert alert-danger mb-0">خطا در ارتباط با سرور، لطفاً دوباره تلاش کنید</div>'); })
        .always(function () { btn.prop('disabled', false); });
});
$(document).on('change', '#excel-file', function () { $('#excel-result').empty(); });
JS
);
?>
<!-- ویرایش مشخصات -->
<div class="modal fade" id="edit-user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['edit_user'], 'post') ?>
            <div class="modal-header">
                <h5 class="modal-title">ویرایش مشخصات <span id="edit-user-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="Users[_id]" id="edit-user-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="edit-user-first" class="form-label">نام *</label>
                        <input type="text" class="form-control" name="Users[first_name]" id="edit-user-first" required maxlength="100" data-input="fa">
                    </div>
                    <div class="col-md-6">
                        <label for="edit-user-last" class="form-label">نام خانوادگی *</label>
                        <input type="text" class="form-control" name="Users[last_name]" id="edit-user-last" required maxlength="100" data-input="fa">
                    </div>
                </div>
                <small class="text-muted d-block mt-3">اگر دانشپذیر حساب کلاس آنلاین داشته باشد، نام در آنجا هم به‌روز می‌شود.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<!-- تغییر رمز عبور -->
<div class="modal fade" id="change-password" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <?= Html::beginForm(['change_password'], 'post') ?>
            <div class="modal-header">
                <h5 class="modal-title">تغییر رمز عبور <span id="password-user-title"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="Users[_id]" id="password-user-id">
                <label for="password-user-input" class="form-label">رمز عبور جدید *</label>
                <input type="text" class="form-control" name="Users[password_hash]" id="password-user-input" required minlength="6" maxlength="72" autocomplete="new-password" dir="ltr">
                <small class="text-muted">حداقل ۶ کاراکتر</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                <button type="submit" class="btn btn-primary">تغییر رمز عبور</button>
            </div>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>

<?php if (!empty($showAdd)):
    $newColleges = UsersDirectory::collegeNames(StudentAccess::collegesForNewStudent());
    $collegeNote = empty($newColleges)
        ? 'دانشپذیر بدون واحد ثبت می‌شود.'
        : 'دانشپذیر به صورت خودکار به ' . implode('، ', $newColleges) . ' اضافه می‌شود.';
    ?>
    <!-- افزودن با مشخصات -->
    <div class="modal fade" id="new-user" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <?= Html::beginForm(['new_user'], 'post') ?>
                <div class="modal-header">
                    <h5 class="modal-title">افزودن دانشپذیر جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="new-first">نام *</label>
                            <input type="text" class="form-control" id="new-first" name="Users[first_name]" required maxlength="100" data-input="fa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-last">نام خانوادگی *</label>
                            <input type="text" class="form-control" id="new-last" name="Users[last_name]" required maxlength="100" data-input="fa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-username">نام کاربری * (موبایل یا ایمیل)</label>
                            <input type="text" class="form-control" id="new-username" name="Users[username]" required dir="ltr" placeholder="09xxxxxxxxx" autocomplete="off" maxlength="100" data-input="mobile-email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new-password">رمز عبور *</label>
                            <input type="text" class="form-control" id="new-password" name="Users[password_hash]" required minlength="6" maxlength="72" dir="ltr" autocomplete="new-password" placeholder="معمولاً کد ملی">
                        </div>
                    </div>
                    <div class="alert alert-primary d-flex align-items-center mt-3 mb-0 py-2" role="alert">
                        <i class="bx bx-info-circle me-2"></i><span><?= Html::encode($collegeNote) ?> اگر نام کاربری از قبل وجود داشته باشد، کاربر جدید ساخته نمی‌شود.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-primary">ثبت دانشپذیر</button>
                </div>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>

    <!-- افزودن از فایل اکسل -->
    <div class="modal fade" id="from-excel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن دانشپذیران از فایل اکسل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-5">
                            <h6>قالب فایل</h6>
                            <table class="table table-sm table-bordered mb-2">
                                <thead><tr><th>ستون</th><th>فیلد</th><th>الزامی</th></tr></thead>
                                <tbody>
                                <tr><td>A</td><td>نام</td><td>بله</td></tr>
                                <tr><td>B</td><td>نام خانوادگی</td><td>بله</td></tr>
                                <tr><td>C</td><td>نام کاربری: موبایل ۰۹xxxxxxxxx یا ایمیل</td><td>بله</td></tr>
                                <tr><td>D</td><td>رمز عبور (معمولاً کد ملی)</td><td>بله</td></tr>
                                <tr><td>E</td><td>نام انگلیسی</td><td>خیر</td></tr>
                                <tr><td>F</td><td>نام خانوادگی انگلیسی</td><td>خیر</td></tr>
                                <tr><td>G</td><td>جنسیت: ۱ مرد، ۲ زن</td><td>بله</td></tr>
                                </tbody>
                            </table>
                            <small class="text-muted d-block mb-3">ردیف اول عنوان ستون‌هاست. <?= Html::encode($collegeNote) ?> نام‌های کاربری موجود خطا نیستند و به واحد شما اضافه می‌شوند.</small>
                            <a href="<?= Url::to(['excel_template']) ?>" class="btn btn-sm btn-label-success"><i class="bx bx-download me-1"></i>دانلود فایل نمونه</a>
                        </div>
                        <div class="col-lg-7">
                            <label class="form-label" for="excel-file">فایل اکسل (حداکثر ۱۰ مگابایت)</label>
                            <div class="input-group">
                                <input type="file" class="form-control" id="excel-file" accept=".xlsx,.xls">
                                <button class="btn btn-primary" id="excel-check" type="button"><i class="bx bx-check-shield me-1"></i>بررسی فایل</button>
                            </div>
                            <small class="text-muted">پس از بررسی، پیش‌نمایش و خطاهای احتمالی نمایش داده می‌شود و تا رفع خطاها امکان ثبت وجود ندارد.</small>
                        </div>
                    </div>
                    <div id="excel-result" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
