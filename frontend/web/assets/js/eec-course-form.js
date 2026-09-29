/**
 * رفتار مشترک فرم ثبت و ویرایش دوره‌ی کوتاه‌مدت و میان‌مدت (یک کد برای هر دو صفحه تا دقیقاً یکسان باشند).
 *
 * فیلدها با data-role داخل فرم پیدا می‌شوند:
 *   unit, broker, contract, lesson, teacher, server, server-wrap, archive-wrap,
 *   capacity, capacity-number, capacity-number-wrap, contract-file, contract-file-wrap,
 *   duration, duration-help, start, end, deadline
 * مقدار فعلی (در ویرایش) روی خود select با data-value گذاشته می‌شود.
 *
 * رویداد: بعد از بارگذاری گزینه‌های واحد، روی فرم 'eec:unit-options' با داده‌ی {brokers, teachers, lessons} فرستاده می‌شود.
 *
 * استفاده: EecCourseForm.init(formElement, {optionsUrl, minHours, maxHours, kind: 'short'|'package'})
 */
(function (window) {
    'use strict';

    function init(formEl, cfg) {
        var $ = window.jQuery;
        if (!formEl || !$) return;
        var form = $(formEl), unitData = {brokers: [], teachers: [], lessons: []};
        var fa = function (n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); };
        var el = function (role) { return form.find('[data-role="' + role + '"]'); };

        function fill(select, items, prompt, keep) {
            var current = keep !== undefined ? keep : select.data('value');
            select.empty().append($('<option>').val('').text(prompt));
            $.each(items, function (i, item) { select.append($('<option>').val(item.id).text(item.name)); });
            // مقدار قبلی دوره (مثلاً قراردادی که بعداً غیرفعال شده) از فهرست حذف نمی‌شود
            if (current && !select.find('option').filter(function () { return this.value === String(current); }).length && select.data('label'))
                select.append($('<option>').val(current).text(select.data('label')));
            if (current) select.val(String(current));
            select.prop('disabled', items.length === 0 && !current).trigger('change.select2');
        }

        function loadUnit(first) {
            var id = el('unit').val();
            // با تغییر واحد، کارگزار/قرارداد/درس/مدرس قبلی دیگر معتبر نیست
            if (!first) el('broker').add(el('contract')).add(el('lesson')).add(el('teacher')).data('value', '').removeData('label');
            fill(el('broker'), [], 'ابتدا واحد را انتخاب کنید');
            fill(el('contract'), [], 'ابتدا کارگزار را انتخاب کنید');
            fill(el('lesson'), [], 'ابتدا واحد را انتخاب کنید');
            fill(el('teacher'), [], 'ابتدا واحد را انتخاب کنید');
            if (!id) { form.trigger('eec:unit-options', [unitData = {brokers: [], teachers: [], lessons: []}]); return; }
            $.getJSON(cfg.optionsUrl, {id: id}).done(function (data) {
                unitData = data;
                fill(el('broker'), data.brokers, data.brokers.length ? 'لطفاً کارگزار را انتخاب کنید' : 'کارگزار دارای قرارداد فعال برای این واحد ثبت نشده');
                if (data.brokers.length === 1 && el('broker').data('force')) el('broker').val(data.brokers[0].id);
                el('broker').trigger('change');
                fill(el('lesson'), data.lessons, data.lessons.length ? 'لطفاً درس را انتخاب کنید' : 'درسی برای این واحد ثبت نشده');
                el('lesson').trigger('change');
                fill(el('teacher'), data.teachers, data.teachers.length ? 'لطفاً مدرس را انتخاب کنید' : 'مدرسی برای این واحد ثبت نشده');
                form.trigger('eec:unit-options', [data]);
            });
        }

        form.on('change', '[data-role="unit"]', function () { loadUnit(false); });
        form.on('change', '[data-role="broker"]', function () {
            var id = $(this).val(), broker = null;
            $.each(unitData.brokers, function (i, b) { if (b.id === id) broker = b; });
            var contracts = broker ? $.map(broker.contracts, function (c) { return {id: c.id, name: c.title}; }) : [];
            fill(el('contract'), contracts, id ? 'لطفاً نوع قرارداد را انتخاب کنید' : 'ابتدا کارگزار را انتخاب کنید',
                String(el('broker').data('value') || '') === String(id) ? el('contract').data('value') : '');
            el('contract').prop('required', true);
        });

        // سرور کلاس بعد از انتخاب درس (کوتاه‌مدت)؛ «مخفی کردن آرشیو» فقط وقتی کلاس در سامانه است
        form.on('change', '[data-role="lesson"]', function () {
            if (!el('server-wrap').length || !el('lesson').length) return;
            var has = !!$(this).val();
            el('server-wrap').toggle(has);
            el('server').prop('required', has);
        });
        form.on('change', '[data-role="server"]', function () {
            var v = $(this).val();
            el('archive-wrap').toggle(!!v && v !== 'none');
        });

        form.on('change', '[data-role="capacity"]', function () {
            var type = $(this).val();
            el('capacity-number-wrap').toggle(type === '2');
            el('capacity-number').prop('required', type === '2');
            el('contract-file-wrap').toggle(type === '3');
            // فایل قرارداد فقط در ثبت یا وقتی قبلاً فایلی نبوده الزامی است
            el('contract-file').prop('required', type === '3' && !el('contract-file').data('has-file'));
        });

        form.on('input change', '[data-role="duration"]', function () {
            var v = parseInt(this.value, 10), msg = '';
            if (!isNaN(v)) {
                if (v < cfg.minHours) msg = cfg.kind === 'package'
                    ? 'دوره‌ی میان‌مدت حداقل ' + fa(cfg.minHours) + ' ساعت است؛ دوره‌ی کوتاه‌تر را از بخش کوتاه‌مدت ثبت کنید'
                    : 'امکان ثبت دوره‌ی کوتاه‌مدت کمتر از ' + fa(cfg.minHours) + ' ساعت نیست';
                else if (cfg.maxHours && v > cfg.maxHours) msg = cfg.kind === 'package'
                    ? 'مدت دوره معتبر نیست'
                    : 'دوره‌ی بیشتر از ' + fa(cfg.maxHours) + ' ساعت را از بخش دوره‌های میان‌مدت ثبت کنید';
            }
            this.setCustomValidity(msg);
            el('duration-help').text(msg || el('duration-help').data('text')).toggleClass('text-danger', !!msg);
        });

        // تاریخ‌ها: مقدار ارسالی شمسی Y-m-d؛ اتمام حداقل یک روز بعد از شروع؛ مهلت ثبت عضو = شروع + یک‌چهارم دوره
        if ($.fn.flatpickr && el('start').length && el('end').length) {
            var opts = {locale: 'fa', dateFormat: 'Y-m-d', altInput: true, altFormat: 'Y/m/d', disableMobile: true, onChange: deadline};
            el('start').add(el('end')).each(function () { if (!this._flatpickr && !this.disabled) $(this).flatpickr(opts); });
            if (window.EecDateRange) window.EecDateRange.bind(el('start')[0], el('end')[0]);
            deadline();
        }
        function deadline() {
            var s = el('start')[0] && el('start')[0]._flatpickr, e = el('end')[0] && el('end')[0]._flatpickr;
            form.trigger('eec:range', [{from: el('start').val(), to: el('end').val()}]);
            if (!s || !e || !s.selectedDates[0] || !e.selectedDates[0]) { if (s && e) el('deadline').val(''); return; }
            var days = Math.floor((e.selectedDates[0].getTime() - s.selectedDates[0].getTime()) / 86400000);
            if (days < 1) { el('deadline').val(''); return; }
            var d = new Date(s.selectedDates[0].getTime() + Math.floor(days / 4) * 86400000);
            el('deadline').val(fa(e.formatDate(typeof window.JDate === 'function' ? new window.JDate(d) : d, 'Y/m/d')));
        }

        if (window.EecSelect) window.EecSelect.init(formEl);
        el('capacity').trigger('change');
        el('server').trigger('change');
        el('duration').trigger('change');
        var unit = el('unit');
        if (unit.is('select') && !unit.val() && unit.find('option').length === 2) unit.val(unit.find('option:last').val());
        if (unit.val()) loadUnit(true);
        return {reload: function () { loadUnit(false); }, data: function () { return unitData; }};
    }

    window.EecCourseForm = {init: init};
})(window);
