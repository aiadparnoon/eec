/**
 * بازه‌ی تاریخ شروع و اتمام دوره روی flatpickr شمسی (flatpickr-jdate).
 *
 * نکته: این نسخه‌ی flatpickr برای minDate فقط رشته‌ی شمسی (مثل 1405-09-11) را می‌پذیرد و Date بومی
 * را بی‌صدا نادیده می‌گیرد؛ به همین دلیل محدودیت قبلی عملاً اعمال نمی‌شد.
 *
 *  - روزهای قبل از «شروع + ۱ روز» در تقویم تاریخ اتمام غیرفعال‌اند.
 *  - اگر تاریخ اتمام (تایپی یا قدیمی) مساوی/قبل از شروع باشد، پاک و خطا نمایش داده می‌شود
 *    و فرم تا اصلاح ارسال نمی‌شود. سرور هم همین قاعده را دوباره بررسی می‌کند.
 */
(function (window, $) {
    'use strict';
    var MESSAGE = 'تاریخ اتمام دوره باید حداقل یک روز بعد از تاریخ شروع باشد';

    function valueOf(input) {
        var v = (input.value || '').trim()
            .replace(/[۰-۹]/g, function (c) { return String(c.charCodeAt(0) - 0x06F0); })
            .replace(/[٠-٩]/g, function (c) { return String(c.charCodeAt(0) - 0x0660); })
            .replace(/\//g, '-');
        var m = v.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        return m ? m[1] + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[3]).slice(-2) : '';
    }

    function nextDay(fp, value) {
        var d = fp.parseDate(value, 'Y-m-d');
        if (!d) return null;
        var n = typeof window.JDate === 'function' ? new window.JDate(new Date(d.getTime() + 86400000)) : new Date(d.getTime() + 86400000);
        return fp.formatDate(n, 'Y-m-d');
    }

    function feedback(endInput, message) {
        var fp = endInput._flatpickr, target = fp && fp.altInput ? fp.altInput : endInput;
        var box = target.parentNode.querySelector('.eec-date-range-error');
        if (!box) {
            box = document.createElement('div');
            box.className = 'invalid-feedback eec-date-range-error';
            box.textContent = message || MESSAGE;
            target.insertAdjacentElement('afterend', box);
        }
        return {target: target, box: box};
    }

    function setError(endInput, on, message) {
        var f = feedback(endInput, message);
        f.target.classList.toggle('is-invalid', on);
        f.box.style.display = on ? 'block' : 'none';
        f.target.setCustomValidity(on ? (message || MESSAGE) : '');
    }

    /** message: پیام دلخواه (مثلاً برای تاریخ درس)؛ پیش‌فرض پیام تاریخ دوره */
    function bind(start, end, message) {
        if (!start || !end) return;
        var tries = 0;
        (function wait() {
            // flatpickr ممکن است بعد از این اسکریپت روی فیلدها ساخته شود
            if (!start._flatpickr || !end._flatpickr) {
                if (tries++ < 40) setTimeout(wait, 100);
                return;
            }
            var sfp = start._flatpickr, efp = end._flatpickr;

            function check(fromEnd) {
                var s = valueOf(start), e = valueOf(end);
                var invalid = s && e && e <= s; // قبل از set(minDate) که خودش مقدار نامجاز را بی‌صدا پاک می‌کند
                efp.set('minDate', s ? nextDay(efp, s) : null);
                if (invalid) {
                    efp.clear();
                    setError(end, true, message);
                    return false;
                }
                if (fromEnd || e) setError(end, false, message);
                return true;
            }

            sfp.config.onChange.push(function () { check(false); });
            sfp.config.onClose.push(function () { check(false); });
            efp.config.onChange.push(function () { check(true); });
            efp.config.onClose.push(function () { check(true); });
            $(start).add(end).add(sfp.altInput || []).add(efp.altInput || []).on('change blur', function () { check(false); });
            $(start).closest('form').on('submit', function (ev) {
                var s = valueOf(start), e = valueOf(end);
                if (s && e && e <= s) {
                    ev.preventDefault();
                    ev.stopImmediatePropagation();
                    efp.clear();
                    setError(end, true, message);
                    if (window.toastr) window.toastr.error(message || MESSAGE, '', {positionClass: 'toast-top-center'});
                }
            });
            check(false);
        })();
    }

    window.EecDateRange = {bind: bind, message: MESSAGE};
})(window, jQuery);
