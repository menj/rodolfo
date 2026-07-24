/**
 * Reading position memory.
 *
 * Long-form posts and multi-part series deserve to be resumable — you don't
 * want to hunt for your spot in a 4,000-word guide. This stores scroll
 * position per post URL in localStorage under a keyed prefix, and on return
 * offers a small "resume where you left off" pill in the corner. Never
 * auto-scrolls: unsolicited page jumps are hostile.
 *
 * All in localStorage: no server, no accounts, no data leaves the browser.
 * Same posture as the dark-mode toggle.
 *
 * Guards:
 *   - Only tracks singular posts, and only when rodolfo_resume_enabled is on.
 *   - Ignores positions under 15% (barely started — nothing to resume) and
 *     over 92% (essentially finished — no point offering).
 *   - Debounced writes so scrolling doesn't hammer localStorage.
 *   - Never restores automatically; the pill has to be clicked.
 *   - Prunes older than 90 days on load to keep storage bounded.
 */
(function () {
    'use strict';

    var root = document.querySelector('article.rodolfo-single, .rodolfo-single-article');
    if (!root) { return; }

    var STORE_KEY = 'rodolfo:resume:v1';
    var PATH      = location.pathname;
    var MIN_PCT   = 0.15;
    var MAX_PCT   = 0.92;
    var MAX_AGE   = 90 * 24 * 60 * 60 * 1000;

    function load() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; }
        catch (e) { return {}; }
    }

    function save(store) {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(store)); }
        catch (e) { /* quota exceeded; nothing we can do */ }
    }

    // Prune old entries opportunistically.
    var store = load();
    var now = Date.now();
    Object.keys(store).forEach(function (k) {
        if (!store[k] || (now - (store[k].t || 0)) > MAX_AGE) { delete store[k]; }
    });

    function scrollPct() {
        var doc = document.documentElement;
        var height = doc.scrollHeight - doc.clientHeight;
        return height > 0 ? Math.min(1, Math.max(0, window.scrollY / height)) : 0;
    }

    // Offer resume if we have a stored position for THIS post.
    var prior = store[PATH];
    if (prior && prior.pct > MIN_PCT && prior.pct < MAX_PCT) {
        var pill = document.createElement('button');
        pill.type = 'button';
        pill.className = 'rodolfo-resume';
        pill.textContent = 'Resume where you left off — ' + Math.round(prior.pct * 100) + '%';
        pill.setAttribute('aria-label', 'Resume where you left off, ' + Math.round(prior.pct * 100) + ' percent');
        document.body.appendChild(pill);

        var dismiss = function () {
            if (pill.parentNode) { pill.parentNode.removeChild(pill); }
        };
        pill.addEventListener('click', function () {
            var target = (document.documentElement.scrollHeight - document.documentElement.clientHeight) * prior.pct;
            window.scrollTo({ top: target, behavior: 'smooth' });
            dismiss();
        });
        // Auto-dismiss on any scroll — the user is reading, offer withdrawn.
        var onScrollDismiss = function () {
            dismiss();
            window.removeEventListener('scroll', onScrollDismiss);
        };
        setTimeout(function () { window.addEventListener('scroll', onScrollDismiss); }, 800);
        // Hard timeout so it doesn't linger indefinitely.
        setTimeout(dismiss, 15000);
    }

    // Save position, debounced. rAF gate + timeout gives one write per idle burst.
    var pending = false;
    var timer = null;
    function schedule() {
        if (pending) { return; }
        pending = true;
        clearTimeout(timer);
        timer = setTimeout(function () {
            requestAnimationFrame(function () {
                pending = false;
                var pct = scrollPct();
                if (pct < MIN_PCT || pct > MAX_PCT) {
                    // Below threshold: forget any prior mark. Finished: same.
                    if (store[PATH]) { delete store[PATH]; save(store); }
                    return;
                }
                store[PATH] = { pct: pct, t: Date.now() };
                save(store);
            });
        }, 400);
    }
    window.addEventListener('scroll', schedule, { passive: true });
    // Also save on unload for cases where scroll debounce hasn't fired yet.
    window.addEventListener('beforeunload', function () {
        var pct = scrollPct();
        if (pct >= MIN_PCT && pct <= MAX_PCT) {
            store[PATH] = { pct: pct, t: Date.now() };
            save(store);
        }
    });
})();
