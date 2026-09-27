/**
 * کنترل نوع ورودی فیلدها (صورتجلسه‌ی ۱۴۰۳/۴/۳۱، بند ۱.۴) — برای همه‌ی صفحه‌ها.
 *
 * روی input یک data-input بگذارید:
 *   en            فقط حروف انگلیسی، عدد، فاصله و . , ' - & ( )
 *   fa            فقط حروف فارسی، فاصله و نیم‌فاصله
 *   digits        فقط عدد (ارقام فارسی/عربی خودکار تبدیل می‌شوند)؛ طول با maxlength
 *   phone         عدد، + و -
 *   mobile-email  موبایل یا ایمیل (ارقام تبدیل، حروف کوچک، بدون فاصله، بدون حروف فارسی)
 * اگر کاربر با صفحه‌کلید اشتباه تایپ کند، کاراکتر حذف و راهنما زیر فیلد نمایش داده می‌شود.
 * این فقط تجربه‌ی کاربری است؛ اعتبارسنجی اصلی همیشه سمت سرور انجام می‌شود.
 */
(function () {
    'use strict';
    var FA_DIGITS = /[۰-۹٠-٩]/g;
    var DIGIT_MAP = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'};

    var RULES = {
        'en': {allowed: /[A-Za-z0-9 .,'&()\-]/, hint: 'این فیلد فقط حروف انگلیسی می‌پذیرد؛ صفحه‌کلید را انگلیسی کنید'},
        'fa': {allowed: /[\u0600-\u06FF\uFB50-\uFDFF\uFE70-\uFEFC \u200C]/, hint: 'این فیلد فقط حروف فارسی می‌پذیرد؛ صفحه‌کلید را فارسی کنید'},
        'digits': {allowed: /[0-9]/, hint: 'فقط عدد مجاز است'},
        'phone': {allowed: /[0-9+\-]/, hint: 'فقط عدد، + و - مجاز است'},
        'mobile-email': {allowed: /[A-Za-z0-9@._+\-]/, hint: 'موبایل یا ایمیل را با صفحه‌کلید انگلیسی وارد کنید'}
    };

    function toLatinDigits(value) {
        return value.replace(FA_DIGITS, function (d) { return DIGIT_MAP[d]; });
    }

    function showHint(input, text) {
        var holder = input.closest('.input-group') || input;
        var hint = holder.parentNode.querySelector('.eec-input-hint');
        if (!hint) {
            hint = document.createElement('small');
            hint.className = 'eec-input-hint text-danger d-block mt-1';
            hint.setAttribute('role', 'alert');
            holder.parentNode.insertBefore(hint, holder.nextSibling);
        }
        hint.textContent = text;
        clearTimeout(hint._timer);
        hint._timer = setTimeout(function () { hint.textContent = ''; }, 3000);
    }

    function clean(input) {
        var type = input.getAttribute('data-input');
        var rule = RULES[type];
        if (!rule) return;
        var original = input.value;
        var value = type === 'fa' ? original : toLatinDigits(original);
        if (type === 'mobile-email') value = value.replace(/\s+/g, '').toLowerCase();
        var removed = false;
        var result = '';
        for (var i = 0; i < value.length; i++) {
            var ch = value.charAt(i);
            if (rule.allowed.test(ch)) result += ch;
            else removed = true;
        }
        var max = parseInt(input.getAttribute('maxlength'), 10);
        if (max > 0 && result.length > max) result = result.slice(0, max);
        if (result !== original) {
            var pos = input.selectionStart;
            input.value = result;
            if (typeof pos === 'number' && document.activeElement === input) {
                var p = Math.max(0, pos - (original.length - result.length));
                try { input.setSelectionRange(p, p); } catch (e) {}
            }
        }
        if (removed) showHint(input, rule.hint);
    }

    document.addEventListener('input', function (e) {
        if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-input')) clean(e.target);
    }, true);
    // مقدارهایی که با JS پر می‌شوند (مودال ویرایش) هم یکسان شوند
    document.addEventListener('change', function (e) {
        if (e.target && e.target.hasAttribute && e.target.hasAttribute('data-input')) clean(e.target);
    }, true);
})();
