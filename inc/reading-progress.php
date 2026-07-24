<?php
/**
 * Reading progress bar (natively, no plugin).
 *
 * A thin fixed bar that fills as the reader scrolls through a post/page.
 * Self-contained: prints its own scoped CSS + a few lines of vanilla JS in the
 * footer (no dependency on main.js or its .min twin). Options-driven via Theme
 * Options → Reading bar.
 *
 * Options:
 *   rodolfo_progress_enabled  '1'|''            (default on)
 *   rodolfo_progress_height   int px, 2–20      (default 4; <2 falls back to 4)
 *   rodolfo_progress_position 'top'|'bottom'    (default top)
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Should the bar render on the current view? Singular posts/pages only. */
function rodolfo_progress_active() {
    if ( '1' !== get_option( 'rodolfo_progress_enabled', '1' ) ) {
        return false;
    }
    return (bool) apply_filters( 'rodolfo_progress_active', is_singular( array( 'post', 'page' ) ) );
}

function rodolfo_progress_render() {
    if ( ! rodolfo_progress_active() ) {
        return;
    }
    $height   = max( 2, (int) get_option( 'rodolfo_progress_height', 4 ) );
    $position = ( 'bottom' === get_option( 'rodolfo_progress_position', 'top' ) ) ? 'bottom' : 'top';
    $accent   = function_exists( 'rodolfo_accent' ) ? rodolfo_accent() : '#3d54d6'; // Customizer accent (inc/accent.php).
    ?>
    <div id="rodolfo-progress" role="progressbar" aria-label="<?php esc_attr_e( 'Reading progress', 'rodolfo' ); ?>" aria-hidden="true"
         style="position:fixed;<?php echo esc_attr( $position ); ?>:0;left:0;width:100%;height:<?php echo (int) $height; ?>px;z-index:60;background:transparent;pointer-events:none">
        <span id="rodolfo-progress-fill" style="display:block;height:100%;width:0;background:<?php echo esc_attr( $accent ); ?>;transition:width .1s linear"></span>
    </div>
    <script>
    (function () {
        var fill = document.getElementById('rodolfo-progress-fill');
        if (!fill) { return; }
        function update() {
            var h = document.documentElement;
            var max = (h.scrollHeight - h.clientHeight);
            var pct = max > 0 ? (h.scrollTop || document.body.scrollTop) / max * 100 : 0;
            fill.style.width = Math.min(100, Math.max(0, pct)) + '%';
        }
        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });
        update();
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'rodolfo_progress_render' );

/* ---- Sticky title strip (Phase 7.4) — off by default -------------------- */

/**
 * A slim post-title bar for long reads, sharing this module's footprint: it
 * needs no scroll math at all — an IntersectionObserver on the post <h1>
 * toggles it, which is cheaper than any scroll handler. Sits directly below
 * the sticky site header (height measured at runtime); appears without
 * animation under prefers-reduced-motion (style.css). Pairs with the resume
 * pill: title context up top, position memory below.
 */
function rodolfo_sticky_title_render() {
    if ( ! get_option( 'rodolfo_sticky_title' ) || ! is_singular( 'post' ) ) {
        return;
    }
    ?>
    <div id="rodolfo-sticky-title" class="rodolfo-sticky-title" aria-hidden="true">
        <span class="rodolfo-sticky-title-text"><?php echo esc_html( get_the_title() ); ?></span>
    </div>
    <script>
    (function () {
        var strip = document.getElementById('rodolfo-sticky-title');
        var title = document.querySelector('article h1');
        if (!strip || !title || typeof IntersectionObserver === 'undefined') { return; }
        var header = document.querySelector('header.sticky');
        strip.style.top = (header ? header.getBoundingClientRect().height : 0) + 'px';
        new IntersectionObserver(function (entries) {
            strip.classList.toggle('is-visible', !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0);
        }).observe(title);
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'rodolfo_sticky_title_render' );
