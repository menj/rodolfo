/**
 * Google "Preferred Sources" button theme sync (inc/preferred-sources.php).
 *
 * Keeps each button's data-theme in step with the site's light/dark mode.
 * The theme toggles the "dark" class on <html>, so a MutationObserver on
 * that class is enough.
 */
(function () {
    'use strict';

    var root = document.documentElement;

    function sync() {
        var theme = root.classList.contains('dark') ? 'dark' : 'light';
        document.querySelectorAll('[google-add-preferred-source-btn]').forEach(function (el) {
            el.setAttribute('data-theme', theme);
        });
    }

    sync();
    new MutationObserver(sync).observe(root, { attributes: true, attributeFilter: ['class'] });
})();
