<?php
/**
 * Performance toggles (emojis, embeds, jQuery Migrate) + custom script injection.
 *
 * Extracted verbatim from functions.php in 3.4.0 — a pure structural move,
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
    if ( ! get_option( 'rodolfo_disable_emojis', '1' ) ) {
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
    if ( ! get_option( 'rodolfo_disable_embeds', '1' ) ) {
        return;
    }
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
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
// CORE HEAD CLEANUP — always on
// ============================================================
function rodolfo_head_cleanup() {
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    remove_action( 'template_redirect', 'rest_output_link_header', 11 );
    remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
    add_filter( 'the_generator', '__return_empty_string' );
    // wp-embed script on the front end.
    add_action( 'wp_enqueue_scripts', function () {
        wp_dequeue_script( 'wp-embed' );
    }, 100 );
    add_action( 'wp_footer', function () {
        wp_dequeue_script( 'wp-embed' );
    }, 1 );
}
add_action( 'init', 'rodolfo_head_cleanup', 20 );

/**
 * Block-library / global-styles / auto-sizes output. On by default (the theme
 * ships its own CSS); unset options count as enabled, '0' disables.
 */
function rodolfo_flag( $option ) {
    return '0' !== (string) get_option( $option, '1' );
}

function rodolfo_optional_style_dequeue() {
    if ( rodolfo_flag( 'rodolfo_dequeue_block_library' ) ) {
        global $wp_styles;
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'classic-theme-styles' );
        if ( $wp_styles instanceof WP_Styles ) {
            foreach ( array_keys( $wp_styles->registered ) as $handle ) {
                if ( 0 === strpos( $handle, 'wp-block-' ) ) {
                    wp_dequeue_style( $handle );
                }
            }
        }
    }
    if ( rodolfo_flag( 'rodolfo_dequeue_global_styles' ) ) {
        wp_dequeue_style( 'global-styles' );
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
    }
}
add_action( 'wp_enqueue_scripts', 'rodolfo_optional_style_dequeue', 100 );
add_action( 'wp_print_styles', 'rodolfo_optional_style_dequeue', 100 );

// Core's inline img auto-sizes rule and per-block asset loading are not needed.
add_action( 'init', function () {
    if ( rodolfo_flag( 'rodolfo_dequeue_block_library' ) ) {
        remove_action( 'wp_head', 'wp_print_auto_sizes_contain_css_fix', 1 );
        remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
        add_filter( 'should_load_separate_core_block_assets', '__return_false' );
        add_filter( 'styles_inline_size_limit', '__return_zero' );
    }
    if ( rodolfo_flag( 'rodolfo_dequeue_global_styles' ) ) {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
    }
}, 20 );

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
