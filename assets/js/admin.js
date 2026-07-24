/**
 * Theme Options tab switcher.
 *
 * Lives in an enqueued file rather than an inline <script> so it survives a
 * Content-Security-Policy that disallows inline scripts — security plugins
 * commonly enforce one in wp-admin, which silently killed the previous inline
 * version. The tab anchors carry real ?tab= URLs, so even with this file
 * blocked or JS disabled the tabs still work via a server round-trip; this
 * script merely upgrades them to instant client-side switching.
 */
(function () {
    'use strict';

    var tabs = document.querySelectorAll('.rodolfo-tab-btn');
    var panels = document.querySelectorAll('.rodolfo-panel');
    var hiddens = document.querySelectorAll('input[name="rodolfo_active_tab"]');
    if (!tabs.length || !panels.length) {
        return;
    }

    function activate(slug) {
        tabs.forEach(function (t) {
            t.classList.toggle('is-active', t.dataset.tab === slug);
        });
        panels.forEach(function (p) {
            p.classList.toggle('is-active', p.dataset.panel === slug);
        });
        hiddens.forEach(function (h) { h.value = slug; });

        // The Documentation tab is read-only — Save/Reset do not apply.
        var footer = document.querySelector('.rodolfo-form-footer');
        if (footer) { footer.style.display = (slug === 'docs') ? 'none' : ''; }

        // Keep the address bar in step so reload/bookmark lands on this tab.
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', slug);
            history.replaceState(null, '', url.toString());
        } catch (e) { /* very old browsers: harmless, href fallback covers it */ }
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function (e) {
            e.preventDefault();
            activate(t.dataset.tab);
        });
    });
})();
