document.addEventListener('DOMContentLoaded', function () {
    var selects = document.querySelectorAll('[data-autosubmit]');
    selects.forEach(function (sel) {
        sel.addEventListener('change', function () {
            sel.closest('form').submit();
        });
    });
});
