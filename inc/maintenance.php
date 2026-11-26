<?php
/**
 * Maintenance / coming-soon mode: copy, themed wp_die handler, template_redirect gate.
 *
 * Extracted verbatim from functions.php in 3.4.0 — a pure structural move,
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// MAINTENANCE MODE
// ============================================================

/**
 * Default holding-page copy. Playful, but unmistakably temporary — the goal is
 * that a visitor smiles and understands the site is coming back, rather than
 * wondering whether it is permanently broken. The subtext always states the
 * plain fact; only the headline is whimsical.
 *
 * Filterable via `rodolfo_maintenance_copy` and overridable per-site from
 * Theme Options → General.
 */
function rodolfo_maintenance_copy( $mode = 'maintenance' ) {
    if ( 'coming_soon' === $mode ) {
        $copy = array(
            'title' => __( 'The fish are still being arranged', 'rodolfo' ),
            'body'  => __( 'Something new is being set up here. Check back soon.', 'rodolfo' ),
        );
    } else {
        $copy = array(
            'title' => __( 'The fish are swimming away', 'rodolfo' ),
            'body'  => __( 'We are doing some quick maintenance and will be back shortly. Nothing is lost — the fish always come back.', 'rodolfo' ),
        );
    }

    $custom_title = trim( (string) get_option( 'rodolfo_maintenance_title', '' ) );
    $custom_body  = trim( (string) get_option( 'rodolfo_maintenance_body', '' ) );
    if ( '' !== $custom_title ) {
        $copy['title'] = $custom_title;
    }
    if ( '' !== $custom_body ) {
        $copy['body'] = $custom_body;
    }

    /** Filter the maintenance/coming-soon copy. */
    return apply_filters( 'rodolfo_maintenance_copy', $copy, $mode );
}

/**
 * Custom wp_die() handler for the holding page.
 *
 * Maintenance mode goes through wp_die() — the canonical WordPress path, so
 * plugins filtering wp_die still see it and core handles the status code and
 * bail-out semantics. What wp_die() does NOT allow is restyling: it prints its
 * own complete document with a hardcoded stylesheet (Open Sans, #f1f1f1 page,
 * thin-bordered white box), and it emits that <style> AFTER the message body,
 * so inline styles passed in the message are overridden.
 *
 * The `wp_die_handler` filter is the supported way around this: it replaces the
 * handler wholesale, so this function owns the entire document while wp_die()
 * remains the entry point. Registered only for the duration of the maintenance
 * bail-out, then it applies to nothing else.
 */
function rodolfo_maintenance_die_handler( $message, $title = '', $args = array() ) {
    $args    = wp_parse_args( $args, array( 'response' => 503 ) );
    $status  = (int) $args['response'];
    $mode    = ( 503 === $status ) ? 'maintenance' : 'coming_soon';
    $copy    = rodolfo_maintenance_copy( $mode );
    $accent  = function_exists( 'rodolfo_accent' ) ? rodolfo_accent() : '#3d54d6';
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    $logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';

    if ( ! headers_sent() ) {
        status_header( $status );
        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
    }
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="<?php echo 503 === $status ? 'noindex, nofollow' : 'noindex, follow'; ?>" />
<title><?php echo esc_html( $copy['title'] . ' — ' . get_bloginfo( 'name' ) ); ?></title>
<style>
    :root { --accent: <?php echo esc_attr( $accent ); ?>; --bg: #f8fafc; --card: #fff; --ink: #0f172a; --muted: #475569; --line: #e2e8f0; }
    * { box-sizing: border-box; }
    body {
        margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
        padding: 24px; background: var(--bg); color: var(--ink);
        font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        line-height: 1.6; -webkit-font-smoothing: antialiased;
    }
    .card {
        width: 100%; max-width: 520px; background: var(--card); border: 1px solid var(--line);
        border-radius: 20px; padding: 44px 40px; text-align: center;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 12px 32px rgba(15,23,42,.06);
    }
    .logo { max-width: 160px; height: auto; margin: 0 auto 24px; display: block; }
    .fish { color: var(--accent); margin: 0 auto 22px; display: block; }
    @media (prefers-reduced-motion: no-preference) {
        .fish { animation: swim 3.5s ease-in-out infinite; }
        @keyframes swim {
            0%, 100% { transform: translateX(-6px) rotate(-3deg); }
            50%      { transform: translateX(6px) rotate(3deg); }
        }
    }
    h1 { margin: 0 0 12px; font-size: 25px; line-height: 1.3; letter-spacing: -.02em; }
    p  { margin: 0; color: var(--muted); font-size: 15.5px; }
    .meta { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line); font-size: 13px; color: var(--muted); }
    .meta a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .meta a:hover { text-decoration: underline; }
</style>
</head>
<body>
    <main class="card">
        <?php if ( $logo ) : ?>
            <img class="logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
        <?php else : ?>
            <svg class="fish" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6.5 12c.94-3.46 4.94-6 8.5-6 3.56 0 6.06 2.54 7 6-.94 3.47-3.44 6-7 6-3.56 0-7.56-2.53-8.5-6Z"/>
                <path d="M18 12v.01"/>
                <path d="M6.5 12c-1.5 0-3-.5-4.5-2 .5 2 .5 2 0 4 1.5-1.5 3-2 4.5-2Z"/>
            </svg>
        <?php endif; ?>
        <h1><?php echo esc_html( $copy['title'] ); ?></h1>
        <p><?php echo esc_html( $copy['body'] ); ?></p>
        <div class="meta">
            <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
            <?php if ( is_user_logged_in() ) : ?>
                &middot; <a href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Dashboard', 'rodolfo' ); ?></a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

/** Swap in the themed handler. Scoped to the maintenance bail-out only. */
function rodolfo_maintenance_die_handler_filter() {
    return 'rodolfo_maintenance_die_handler';
}

function rodolfo_maintenance_mode() {
    $mode = get_option( 'rodolfo_maintenance_mode' );
    // Capability check, not just is_user_logged_in() — subscribers should see
    // the holding page too; only users who can work on the site bypass it.
    if ( ! $mode || current_user_can( 'edit_theme_options' ) ) {
        return;
    }
    $status = ( 'maintenance' === $mode ) ? 503 : 200;
    if ( 503 === $status && ! headers_sent() ) {
        header( 'Retry-After: 3600' );
    }

    $copy = rodolfo_maintenance_copy( $mode );

    // Theme the output by replacing the handler; wp_die() stays the entry
    // point so core's bail-out semantics and any plugin filters still apply.
    add_filter( 'wp_die_handler', 'rodolfo_maintenance_die_handler_filter' );

    wp_die(
        '<h1>' . esc_html( $copy['title'] ) . '</h1><p>' . esc_html( $copy['body'] ) . '</p>',
        esc_html( $copy['title'] ),
        array( 'response' => $status )
    );
}
add_action( 'template_redirect', 'rodolfo_maintenance_mode' );
