/* Demo Contacts API — client-side enhancements */
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit filter form on dropdown change
    var selects = document.querySelectorAll('.demo-filters select');
    selects.forEach(function (sel) {
        sel.addEventListener('change', function () {
            sel.closest('form').submit();
        });
    });
});
