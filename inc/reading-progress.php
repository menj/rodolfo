<?php
/**
 * Reading progress bar (natively, no plugin).
 *
 * A thin fixed bar that fills as the reader scrolls through a post/page.
 * Self-contained: prints its own markup in the footer and enqueues
 * assets/js/reading.js (shared with the sticky title strip below), with no
 * dependency on main.js. Options-driven via Theme Options → Reading bar.
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

/** Is the sticky title strip on for the current view? Singular posts only. */
function rodolfo_sticky_title_active() {
    return get_option( 'rodolfo_sticky_title' ) && is_singular( 'post' );
}

/**
 * Enqueue the reading-aids script as a FILE (not an inline <script>), so it
 * passes a strict script-src CSP and is cached between page views. Loaded
 * only where the progress bar or the sticky title strip renders.
 */
function rodolfo_reading_assets() {
    if ( ! rodolfo_progress_active() && ! rodolfo_sticky_title_active() ) {
        return;
    }
    wp_enqueue_script(
        rodolfo_handle( 'reading' ),
        get_template_directory_uri() . '/assets/js/reading' . RODOLFO_ASSET_SUFFIX . '.js',
        array(),
        RODOLFO_VERSION,
        array( 'in_footer' => true, 'strategy' => 'defer' )
    );
}
add_action( 'wp_enqueue_scripts', 'rodolfo_reading_assets' );

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
    <div id="tk-progress" role="progressbar" aria-label="<?php esc_attr_e( 'Reading progress', 'rodolfo' ); ?>" aria-hidden="true"
         style="position:fixed;<?php echo esc_attr( $position ); ?>:0;left:0;width:100%;height:<?php echo (int) $height; ?>px;z-index:60;background:transparent;pointer-events:none">
        <span id="tk-progress-fill" style="display:block;height:100%;width:0;background:<?php echo esc_attr( $accent ); ?>;transition:width .1s linear"></span>
    </div>
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
    if ( ! rodolfo_sticky_title_active() ) {
        return;
    }
    ?>
    <div id="tk-sticky-title" class="tk-sticky-title" aria-hidden="true">
        <span class="tk-sticky-title-text"><?php echo esc_html( get_the_title() ); ?></span>
    </div>
    <?php
}
add_action( 'wp_footer', 'rodolfo_sticky_title_render' );
