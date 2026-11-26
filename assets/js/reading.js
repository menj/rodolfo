/**
 * Reading aids for singular views (inc/reading-progress.php).
 *
 * 1. Progress bar: fills #tk-progress-fill as the reader scrolls.
 * 2. Sticky title strip: shows #tk-sticky-title once the post <h1> has
 *    scrolled out of view. An IntersectionObserver drives it, so this part
 *    needs no scroll handler.
 *
 * Each part checks for its own element, so the file is safe to load when
 * only one of the two features is switched on.
 */
(function () {
    'use strict';

    var fill = document.getElementById('tk-progress-fill');
    if (fill) {
        var update = function () {
            var h = document.documentElement;
            var max = h.scrollHeight - h.clientHeight;
            var pct = max > 0 ? (h.scrollTop || document.body.scrollTop) / max * 100 : 0;
            fill.style.width = Math.min(100, Math.max(0, pct)) + '%';
        };
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });
        update();
    }

    var strip = document.getElementById('tk-sticky-title');
    var title = document.querySelector('article h1');
    if (strip && title && typeof IntersectionObserver !== 'undefined') {
        var header = document.querySelector('header.sticky');
        strip.style.top = (header ? header.getBoundingClientRect().height : 0) + 'px';
        new IntersectionObserver(function (entries) {
            strip.classList.toggle('is-visible', !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0);
        }).observe(title);
    }
})();
