<?php
/**
 * Performance toggles (emojis, embeds, jQuery Migrate) + custom script injection.
 *
 * Extracted verbatim from functions.php in 3.4.0 — a pure structural move,
 * no behavioural change. See docs/ssot.md for the module map.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// PERFORMANCE — Disable emojis
// ============================================================
function rodolfo_disable_emojis() {
    if ( ! get_option( 'rodolfo_disable_emojis' ) ) {
        return;
    }
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', function( $plugins ) {
        return array_diff( (array) $plugins, array( 'wpemoji' ) );
    } );
    add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $urls = array_diff( $urls, array( 'https://s.w.org' ) );
        }
        return $urls;
    }, 10, 2 );
}
add_action( 'init', 'rodolfo_disable_emojis' );

// ============================================================
// PERFORMANCE — Disable embeds
// ============================================================
function rodolfo_disable_embeds() {
    if ( ! get_option( 'rodolfo_disable_embeds' ) ) {
        return;
    }
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    add_filter( 'rewrite_rules_array', function( $rules ) {
        foreach ( $rules as $rule => $rewrite ) {
            if ( str_contains( $rewrite, 'embed=true' ) ) {
                unset( $rules[ $rule ] );
            }
        }
        return $rules;
    } );
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}
add_action( 'init', 'rodolfo_disable_embeds' );

// ============================================================
// PERFORMANCE — Disable jQuery Migrate
// ============================================================
function rodolfo_disable_jquery_migrate( $scripts ) {
    if ( ! get_option( 'rodolfo_disable_jquery_migrate' ) ) {
        return;
    }
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
}
add_action( 'wp_default_scripts', 'rodolfo_disable_jquery_migrate' );

// ============================================================
// CUSTOM SCRIPTS INJECTION
// ============================================================
function rodolfo_inject_header_scripts() {
    $scripts = get_option( 'rodolfo_header_scripts' );
    if ( $scripts ) {
        echo "\n" . $scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
add_action( 'wp_head', 'rodolfo_inject_header_scripts', 99 );

function rodolfo_inject_body_scripts() {
    $scripts = get_option( 'rodolfo_body_scripts' );
    if ( $scripts ) {
        echo "\n" . $scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
add_action( 'wp_body_open', 'rodolfo_inject_body_scripts', 99 );

function rodolfo_inject_footer_scripts() {
    $scripts = get_option( 'rodolfo_footer_scripts' );
    if ( $scripts ) {
        echo "\n" . $scripts . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
add_action( 'wp_footer', 'rodolfo_inject_footer_scripts', 99 );

/**
 * Render an author card on single posts: avatar, name, bio, and a link to the
 * author's archive. Skips output when the author has no bio to avoid a hollow
 * box on sites that never fill in the field.
 */
