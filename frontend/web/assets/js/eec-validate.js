/**
 * اعتبارسنجی فرم‌ها با پیام زیر هر فیلد (به‌جای حباب پیش‌فرض مرورگر که برای select2 و تاریخ
 * شمسی اصلاً نمایش داده نمی‌شود).
 *
 *  EecValidate.bind(form, {ajax: true})
 *   - فیلدهای required / pattern / setCustomValidity بررسی می‌شوند؛ پیام زیر فیلد با خط قرمز.
 *   - با اصلاح مقدار، پیام همان لحظه حذف می‌شود.
 *   - ajax: فرم در پس‌زمینه ارسال می‌شود؛ پاسخ JSON سرور {ok, redirect, field, message}
 *     و خطای سرور زیر همان فیلد (field = نام فیلد) نمایش داده می‌شود؛ اطلاعات فرم از دست نمی‌رود.
 *  EecValidate.showError(form, field, message)
 *
 * پیام دلخواه: data-error-required / data-error-pattern ؛ نام فیلد در پیام: data-label یا <label>.
 */
(function () {
    'use strict';

    var style = document.createElement('style');
    style.textContent =
        '.select2-container.is-invalid .select2-selection{border-color:#ff3e1d!important}' +
        '.eec-error{display:block;width:100%;margin-top:.25rem;font-size:.8125rem;color:#ff3e1d}' +
        '.eec-error-box{border-color:#ff3e1d!important}';
    document.head.appendChild(style);

    function isSelect2(el) {
        return el.tagName === 'SELECT' && el.classList.contains('select2-hidden-accessible');
    }

    /** عنصر قابل مشاهده‌ی فیلد (select2 / فیلد نمایشی flatpickr / خود فیلد) */
    function visual(el) {
        if (isSelect2(el) && el.nextElementSibling && el.nextElementSibling.classList.contains('select2'))
            return el.nextElementSibling;
        if (el._flatpickr && el._flatpickr.altInput)
            return el._flatpickr.altInput;
        return el;
    }

    /** پیام بعد از این عنصر قرار می‌گیرد */
    function anchor(el) {
        var v = visual(el);
        var group = v.closest ? v.closest('.input-group') : null;
        return group || v;
    }

    function isVisible(el) {
        var v = visual(el);
        return !!(v.offsetWidth || v.offsetHeight || v.getClientRects().length);
    }

    function labelOf(el) {
        if (el.dataset.label)
            return el.dataset.label;
        var label = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
        if (!label) {
            var col = el.closest('[class*="col"], .mb-2, .mb-3');
            label = col ? col.querySelector('label') : null;
        }
        return label ? label.textContent.replace(/[*؟?:]/g, '').trim() : '';
    }

    function isEmpty(el) {
        if (el.type === 'file')
            return !el.files || el.files.length === 0;
        if (el.tagName === 'SELECT' && el.multiple)
            return el.selectedOptions.length === 0;
        return String(el.value || '').trim() === '';
    }

    function messageFor(el) {
        if (el.disabled)
            return '';
        var name = labelOf(el), empty = isEmpty(el);
        if (el.required && empty) {
            if (el.dataset.errorRequired)
                return el.dataset.errorRequired;
            var verb = el.tagName === 'SELECT' ? 'انتخاب' : (el.type === 'file' ? 'بارگذاری' : 'وارد');
            return name ? 'لطفاً «' + name + '» را ' + verb + ' کنید' : 'این فیلد الزامی است';
        }
        if (empty)
            return '';
        var pattern = el.getAttribute('pattern');
        if (pattern && !(new RegExp('^(?:' + pattern + ')$')).test(String(el.value).trim()))
            return el.dataset.errorPattern || (name ? 'مقدار «' + name + '» درست نیست' : 'مقدار وارد شده درست نیست');
        if (el.validity && el.validity.customError)
            return el.validationMessage;
        return '';
    }

    function clear(el) {
        var v = visual(el);
        v.classList.remove('is-invalid');
        el.classList.remove('is-invalid');
        if (el._eecError) {
            el._eecError.remove();
            el._eecError = null;
        }
    }

    function show(el, message) {
        clear(el);
        var v = visual(el);
        v.classList.add('is-invalid');
        var div = document.createElement('div');
        div.className = 'eec-error';
        div.textContent = message;
        anchor(el).insertAdjacentElement('afterend', div);
        el._eecError = div;
    }

    function fields(form) {
        return Array.prototype.filter.call(form.querySelectorAll('input, select, textarea'), function (el) {
            if (el.disabled || el.type === 'submit' || el.type === 'button')
                return false;
            // فیلد اصلی flatpickr (type=hidden) بررسی می‌شود؛ بقیه‌ی hiddenها نه
            if (el.type === 'hidden' && !el._flatpickr)
                return false;
            if (el.previousElementSibling && el.previousElementSibling._flatpickr && el.previousElementSibling._flatpickr.altInput === el)
                return false; // فیلد نمایشی flatpickr
            if (el.closest('.select2-container'))
                return false;
            return isVisible(el);
        });
    }

    function validateField(el) {
        var message = messageFor(el);
        if (message)
            show(el, message);
        else
            clear(el);
        return !message;
    }

    function formAlert(form, message) {
        var box = form.querySelector('.eec-form-alert');
        if (!box) {
            box = document.createElement('div');
            box.className = 'alert alert-danger eec-form-alert';
            form.insertBefore(box, form.firstChild);
        }
        box.textContent = message;
        box.style.display = message ? '' : 'none';
        if (message)
            box.scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    function validate(form) {
        var first = null;
        formAlert(form, '');
        // خطای فیلدهایی که پنهان/غیرفعال شده‌اند هم پاک شود
        Array.prototype.forEach.call(form.querySelectorAll('input, select, textarea'), function (el) {
            if (el._eecError) clear(el);
        });
        Array.prototype.forEach.call(form.querySelectorAll('.eec-error-box'), function (el) {
            el.classList.remove('eec-error-box');
            var next = el.nextElementSibling;
            if (next && next.classList.contains('eec-error')) next.remove();
        });
        fields(form).forEach(function (el) {
            if (!validateField(el) && !first)
                first = el;
        });
        if (first)
            focus(first);
        return !first;
    }

    function focus(el) {
        var v = visual(el);
        v.scrollIntoView({behavior: 'smooth', block: 'center'});
        if (typeof v.focus === 'function' && v.tagName !== 'SPAN')
            setTimeout(function () { try { v.focus({preventScroll: true}); } catch (e) {} }, 300);
    }

    /** نام فیلد سرور → عنصر فرم (نام کامل، یا بدون پیشوند Courses، یا data-error-for) */
    function findField(form, field) {
        if (!field)
            return null;
        var esc = function (s) { return String(s).replace(/(["\\])/g, '\\$1'); };
        var candidates = [field, String(field).replace(/^([^\[]+)/, 'Courses[$1]')];
        for (var i = 0; i < candidates.length; i++) {
            var el = form.querySelector('[name="' + esc(candidates[i]) + '"]');
            if (el)
                return el;
        }
        return form.querySelector('[data-error-for="' + esc(field) + '"]');
    }

    function showError(form, field, message) {
        var el = findField(form, field);
        if (!el || (!isVisible(el) && !el.hasAttribute('data-error-for'))) {
            formAlert(form, message);
            return;
        }
        if (el.hasAttribute('data-error-for') && !/^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName)) {
            el.classList.add('eec-error-box');
            var next = el.nextElementSibling;
            if (next && next.classList.contains('eec-error')) next.remove();
            var div = document.createElement('div');
            div.className = 'eec-error';
            div.textContent = message;
            el.insertAdjacentElement('afterend', div);
            el.scrollIntoView({behavior: 'smooth', block: 'center'});
            return;
        }
        show(el, message);
        focus(el);
    }

    function toast(type, message) {
        if (window.toastr)
            window.toastr[type](message, '', {positionClass: 'toast-top-center', closeButton: true, timeOut: 8000, escapeHtml: true});
    }

    function submitAjax(form, submitter) {
        var data = new FormData(form);
        if (submitter && submitter.name)
            data.append(submitter.name, submitter.value);
        var buttons = form.querySelectorAll('button[type=submit], input[type=submit]');
        Array.prototype.forEach.call(buttons, function (b) { b.disabled = true; });
        fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}})
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.ok) {
                    window.location = res.redirect || window.location.href;
                    return;
                }
                Array.prototype.forEach.call(buttons, function (b) { b.disabled = false; });
                var message = res && res.message ? res.message : 'اطلاعات وارد شده معتبر نیست';
                showError(form, res ? res.field : null, message);
                toast('error', message);
            })
            .catch(function () {
                Array.prototype.forEach.call(buttons, function (b) { b.disabled = false; });
                toast('error', 'ارتباط با سرور برقرار نشد؛ لطفاً دوباره تلاش کنید');
            });
    }

    function bind(form, options) {
        if (!form || form._eecValidate)
            return;
        options = options || {};
        form._eecValidate = true;
        form.noValidate = true;
        var revalidate = function (e) {
            var el = e.target;
            if (!el || !el.name && !el._flatpickr && !el.required)
                return;
            // فیلد نمایشی flatpickr → فیلد اصلی
            if (el.previousElementSibling && el.previousElementSibling._flatpickr && el.previousElementSibling._flatpickr.altInput === el)
                el = el.previousElementSibling;
            if (el.classList.contains('is-invalid') || visual(el).classList.contains('is-invalid') || e.type === 'change')
                validateField(el);
        };
        form.addEventListener('input', revalidate);
        form.addEventListener('change', revalidate);
        // select2 رویداد change را با jQuery می‌فرستد
        var $ = window.jQuery;
        if ($)
            $(form).on('change', 'select', function () { if (visual(this).classList.contains('is-invalid')) validateField(this); });
        form.addEventListener('submit', function (e) {
            if (!validate(form)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toast('error', 'لطفاً موارد مشخص‌شده با رنگ قرمز را تکمیل یا اصلاح کنید');
                return;
            }
            if (options.ajax) {
                e.preventDefault();
                submitAjax(form, e.submitter || null);
            }
        }, true);
    }

    window.EecValidate = {bind: bind, validate: validate, validateField: validateField, showError: showError};
})();
