/**
 * همه‌ی فیلدهای انتخابی (select) یک بخش را به select2 با جست‌وجو تبدیل می‌کند.
 *
 *  - داخل مودال، فهرست کشویی به خود مودال وصل می‌شود تا جست‌وجو کار کند.
 *  - گزینه‌هایی که بعداً با JS عوض می‌شوند (مثل دروس یک واحد) خودکار دیده می‌شوند؛
 *    بعد از تغییر گزینه‌ها ‎.trigger('change')‎ کافی است.
 *  - فیلدهای select2 قبلی دوباره ساخته نمی‌شوند.
 *
 * استفاده: EecSelect.init(document.getElementById('...'))  یا  EecSelect.init('#form-id')
 */
(function (window) {
    'use strict';
    // صفحه دو نسخه jQuery دارد (Yii و قالب)؛ select2 روی نسخه‌ی قالب است، پس هنگام اجرا از window.jQuery استفاده می‌شود
    var language = {
        noResults: function () { return 'نتیجه‌ای یافت نشد'; },
        searching: function () { return 'در حال جست‌وجو…'; },
        inputTooShort: function () { return 'چند حرف وارد کنید'; }
    };

    function init(root) {
        var $ = window.jQuery;
        if (!$ || !$.fn.select2) return;
        $(root || document).find('select').each(function () {
            var select = $(this);
            if (select.hasClass('select2-hidden-accessible') || select.is('[multiple][data-no-select2], [data-no-select2]')) return;
            var modal = select.closest('.modal');
            var empty = select.find('option[value=""]').first();
            select.select2({
                dir: 'rtl',
                width: '100%',
                language: language,
                dropdownParent: modal.length ? modal : $(document.body),
                placeholder: select.data('placeholder') || (empty.length ? empty.text() : ''),
                allowClear: empty.length > 0 && !select.prop('required'),
                minimumResultsForSearch: 0
            });
        });
    }

    window.EecSelect = {init: init};
})(window);
