<?php
/**
 * Settings data layer: option registry, sanitizer map, and registration.
 *
 * This is the contract every other module writes against: an option only
 * persists if it appears in rodolfo_option_sanitizers(). Kept separate from the
 * admin UI so the rules can be read without wading through 700 lines of markup.
 *
 * Extracted from inc/options-page.php in 3.8.0 — a pure structural move, no
 * behavioural change. See docs/ssot.md for the module map.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Labels for the optional-module whitelist (slugs in functions.php:
 * RODOLFO_OPTIONAL_MODULES — the loader needs them before any module loads).
 * Rendered by the Features tab; each maps to a rodolfo_disable_<slug> option.
 */
function rodolfo_optional_modules() {
    $labels = array(
        'toc'              => __( 'Table of contents', 'rodolfo' ),
        'related-posts'    => __( 'Related posts', 'rodolfo' ),
        'search-palette'   => __( '⌘K search palette', 'rodolfo' ),
        'tags'             => __( 'Virtual /tags/ page', 'rodolfo' ),
        'click-to-tweet'   => __( 'Click to Tweet', 'rodolfo' ),
        'unlist'           => __( 'Unlist posts', 'rodolfo' ),
        'aeo'              => __( 'AEO answer blocks', 'rodolfo' ),
        'llms-txt'         => __( 'llms.txt for AI crawlers', 'rodolfo' ),
        'breadcrumbs'      => __( 'Breadcrumbs', 'rodolfo' ),
        'reading-progress' => __( 'Reading progress bar', 'rodolfo' ),
        'donate'           => __( 'Donate button', 'rodolfo' ),
        'views'            => __( 'Post view counts', 'rodolfo' ),
        'author-social'    => __( 'Author social links', 'rodolfo' ),
        'privacy-embeds'   => __( 'Consent-gated embeds', 'rodolfo' ),
        'comment-guard'    => __( 'Comment guard', 'rodolfo' ),
        'post-display'     => __( 'Per-post display options', 'rodolfo' ),
    );
    // The constant is the single source of the slugs; labels must match it.
    return array_intersect_key( $labels, array_flip( RODOLFO_OPTIONAL_MODULES ) );
}

// ============================================================
// REGISTER SETTINGS
// ============================================================
// Map of option name → sanitize callback ('scripts' is handled specially:
// Social platforms offered in Theme Options → Social Links. Data-driven: the
// options UI, sanitizers, and footer icons all derive from this map, so adding
// a platform here is the only change needed. key => array( label, placeholder ).
/**
 * Social platforms rendered as footer icons.
 *
 * Each entry: array( label, placeholder, type ). Type defaults to 'url' and
 * determines both the admin input type and how the stored value is turned
 * into an href — 'email' stores a bare address and is rendered as a mailto:
 * link, so users are not asked to type the scheme themselves.
 */
function rodolfo_social_platforms() {
    return array(
        'twitter'   => array( __( 'Twitter / X', 'rodolfo' ), 'https://x.com/username' ),
        'github'    => array( __( 'GitHub', 'rodolfo' ), 'https://github.com/username' ),
        'facebook'  => array( __( 'Facebook', 'rodolfo' ), 'https://facebook.com/username' ),
        'instagram' => array( __( 'Instagram', 'rodolfo' ), 'https://instagram.com/username' ),
        'youtube'   => array( __( 'YouTube', 'rodolfo' ), 'https://youtube.com/@channel' ),
        'tiktok'    => array( __( 'TikTok', 'rodolfo' ), 'https://tiktok.com/@username' ),
        'threads'   => array( __( 'Threads', 'rodolfo' ), 'https://threads.net/@username' ),
        'linkedin'  => array( __( 'LinkedIn', 'rodolfo' ), 'https://linkedin.com/in/username' ),
        'pinterest' => array( __( 'Pinterest', 'rodolfo' ), 'https://pinterest.com/username' ),
        'reddit'    => array( __( 'Reddit', 'rodolfo' ), 'https://reddit.com/user/username' ),
        'whatsapp'  => array( __( 'WhatsApp', 'rodolfo' ), 'https://wa.me/15551234567' ),
        'telegram'  => array( __( 'Telegram', 'rodolfo' ), 'https://t.me/username' ),
        'medium'    => array( __( 'Medium', 'rodolfo' ), 'https://medium.com/@username' ),
        'substack'  => array( __( 'Substack', 'rodolfo' ), 'https://yourname.substack.com' ),
        'email'     => array( __( 'Email', 'rodolfo' ), 'you@example.com', 'email' ),
    );
}

/**
 * Resolve a stored social value to an href. Email addresses gain the mailto:
 * scheme at render time so the stored option stays a plain address.
 */
function rodolfo_social_href( $key, $value ) {
    $platforms = rodolfo_social_platforms();
    $type      = isset( $platforms[ $key ][2] ) ? $platforms[ $key ][2] : 'url';

    if ( 'email' === $type ) {
        // Tolerate a user-typed scheme: every other field in this tab wants a
        // full URL, so "mailto:you@example.com" is a natural thing to enter.
        $email = sanitize_email( preg_replace( '/^\s*mailto:/i', '', (string) $value ) );
        return $email ? 'mailto:' . $email : '';
    }
    return esc_url_raw( $value );
}

// stored raw, but only for users with the unfiltered_html capability).
function rodolfo_option_sanitizers() {
    $map = array(
        'rodolfo_sticky_title'           => 'rodolfo_sanitize_checkbox',
        'rodolfo_footer_text'            => 'sanitize_text_field',
        'rodolfo_twitter_handle'         => 'sanitize_text_field',
        'rodolfo_donate_url'             => 'esc_url_raw',
        'rodolfo_paypal_email'           => 'sanitize_email',
        'rodolfo_donate_currency'        => 'rodolfo_sanitize_currency',
        'rodolfo_donate_label'           => 'sanitize_text_field',
        'rodolfo_donate_auto'            => 'rodolfo_sanitize_donate_auto',
        'rodolfo_progress_enabled'       => 'rodolfo_sanitize_checkbox',
        'rodolfo_progress_height'        => 'rodolfo_sanitize_progress_height',
        'rodolfo_progress_position'      => 'rodolfo_sanitize_progress_position',
        'rodolfo_link_sitemap'           => 'esc_url_raw',
        // Social platform URLs — one per entry in rodolfo_social_platforms().
        'rodolfo_social_twitter'         => 'esc_url_raw',
        'rodolfo_social_github'          => 'esc_url_raw',
        'rodolfo_social_facebook'        => 'esc_url_raw',
        'rodolfo_social_instagram'       => 'esc_url_raw',
        'rodolfo_social_youtube'         => 'esc_url_raw',
        'rodolfo_social_tiktok'          => 'esc_url_raw',
        'rodolfo_social_threads'         => 'esc_url_raw',
        'rodolfo_social_linkedin'        => 'esc_url_raw',
        'rodolfo_social_pinterest'       => 'esc_url_raw',
        'rodolfo_social_reddit'          => 'esc_url_raw',
        'rodolfo_social_whatsapp'        => 'esc_url_raw',
        'rodolfo_social_telegram'        => 'esc_url_raw',
        'rodolfo_social_medium'          => 'esc_url_raw',
        'rodolfo_social_substack'        => 'esc_url_raw',
        'rodolfo_social_email'           => 'rodolfo_sanitize_social_email',
        'rodolfo_disable_emojis'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_disable_embeds'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_disable_jquery_migrate' => 'rodolfo_sanitize_checkbox',
        'rodolfo_maintenance_mode'       => 'rodolfo_sanitize_maintenance_mode',
        'rodolfo_maintenance_title'      => 'sanitize_text_field',
        'rodolfo_maintenance_body'       => 'sanitize_textarea_field',
        'rodolfo_reading_time_enabled'   => 'rodolfo_sanitize_checkbox',
        'rodolfo_series_enabled'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_feed_full_content'      => 'rodolfo_sanitize_checkbox',
        'rodolfo_card_variant'           => 'rodolfo_sanitize_card_variant',
        'rodolfo_sidebar_enabled'        => 'rodolfo_sanitize_checkbox',
        'rodolfo_sidebar_scope'          => 'rodolfo_sanitize_sidebar_scope',
        'rodolfo_keynav_enabled'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_site_updated_enabled'   => 'rodolfo_sanitize_checkbox',
        'rodolfo_views_enabled'          => 'rodolfo_sanitize_checkbox',
        'rodolfo_privacy_embeds'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_comment_guard'          => 'rodolfo_sanitize_checkbox',
        'rodolfo_turnstile_site'         => 'sanitize_text_field',
        'rodolfo_turnstile_secret'       => 'sanitize_text_field',
        'rodolfo_views_show_meta'        => 'rodolfo_sanitize_checkbox',
        'rodolfo_resume_enabled'         => 'rodolfo_sanitize_checkbox',
        'rodolfo_excerpt_length_card'    => 'absint',
        'rodolfo_excerpt_length_search'  => 'absint',
        'rodolfo_site_updated_mode'      => 'rodolfo_sanitize_site_updated_mode',
        'rodolfo_nav_locations'          => 'rodolfo_sanitize_nav_locations',
        'rodolfo_nav_order'              => 'rodolfo_sanitize_int_map',
        'rodolfo_footer_util'            => 'rodolfo_sanitize_footer_util',
        'rodolfo_header_scripts'         => 'scripts',
        'rodolfo_body_scripts'           => 'scripts',
        'rodolfo_footer_scripts'         => 'scripts',
    );

    // One disable switch per optional module (Features tab) — generated from
    // the whitelist, so a new optional module needs no registry edit here.
    foreach ( RODOLFO_OPTIONAL_MODULES as $slug ) {
        $map[ 'rodolfo_disable_' . $slug ] = 'rodolfo_sanitize_checkbox';
    }
    return $map;
}

function rodolfo_sanitize_checkbox( $value ) {
    return $value ? '1' : '';
}

function rodolfo_sanitize_maintenance_mode( $value ) {
    return in_array( $value, array( 'maintenance', 'coming_soon' ), true ) ? $value : '';
}

function rodolfo_sanitize_progress_position( $value ) {
    return in_array( $value, array( 'top', 'bottom' ), true ) ? $value : 'top';
}

// Clamp bar height to a visible, sane range. Blank/0/1 would render an
// invisible hairline; anything over 20px is unreasonable for a progress bar.
function rodolfo_sanitize_progress_height( $value ) {
    $value = (int) $value;
    if ( $value < 2 ) {
        return 4; // fall back to the default rather than an invisible bar
    }
    return min( 20, $value );
}

function rodolfo_sanitize_currency( $value ) {
    $value = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $value ) );
    return ( 3 === strlen( $value ) ) ? $value : 'USD';
}

// Accept a bare address or a pasted "mailto:you@example.com"; store bare.
function rodolfo_sanitize_social_email( $value ) {
    return sanitize_email( preg_replace( '/^\s*mailto:/i', '', (string) $value ) );
}

function rodolfo_sanitize_site_updated_mode( $value ) {
    return ( 'modified' === $value ) ? 'modified' : 'published';
}

function rodolfo_sanitize_card_variant( $value ) {
    return in_array( $value, array( 'dark', 'light' ), true ) ? $value : 'light';
}

function rodolfo_sanitize_donate_auto( $value ) {
    return in_array( $value, array( 'posts', 'posts_pages' ), true ) ? $value : '';
}

// page_id => 'header'|'footer'|'hidden'. Drops invalid ids/values.
function rodolfo_sanitize_nav_locations( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    $out = array();
    foreach ( $value as $id => $loc ) {
        $id = absint( $id );
        if ( $id && in_array( $loc, array( 'header', 'footer', 'hidden' ), true ) ) {
            $out[ $id ] = $loc;
        }
    }
    return $out;
}

// key/id => int order. Keys kept as-is (int-cast), values absint.
function rodolfo_sanitize_int_map( $value ) {
    if ( ! is_array( $value ) ) {
        return array();
    }
    $out = array();
    foreach ( $value as $id => $order ) {
        $id = absint( $id );
        if ( $id && '' !== $order ) {
            $out[ $id ] = absint( $order );
        }
    }
    return $out;
}

// Built-in footer link config: key => array( show:bool, label:string, order:int ).
function rodolfo_sanitize_footer_util( $value ) {
    $out = array();
    foreach ( array( 'faq', 'terms', 'privacy', 'sitemap', 'rss' ) as $key ) {
        $out[ $key ] = array(
            'show'  => ! empty( $value[ $key ]['show'] ),
            'label' => isset( $value[ $key ]['label'] ) ? sanitize_text_field( $value[ $key ]['label'] ) : '',
            'order' => isset( $value[ $key ]['order'] ) ? absint( $value[ $key ]['order'] ) : 0,
        );
    }
    return $out;
}

// Sanitise a list of Page IDs (from checkbox array or CSV) to unique positive ints.
function rodolfo_sanitize_id_list( $value ) {
    if ( ! is_array( $value ) ) {
        $value = explode( ',', (string) $value );
    }
    return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
}

function rodolfo_register_settings() {
    foreach ( rodolfo_option_sanitizers() as $opt => $sanitize ) {
        if ( 'scripts' === $sanitize ) {
            register_setting( 'rodolfo_options_group', $opt, array(
                'type'              => 'string',
                'sanitize_callback' => function ( $value ) use ( $opt ) {
                    // Only users with unfiltered_html may store raw markup;
                    // for anyone else the previous value is kept unchanged.
                    if ( current_user_can( 'unfiltered_html' ) ) {
                        return (string) $value;
                    }
                    return (string) get_option( $opt, '' );
                },
            ) );
        } else {
            $array_sanitizers = array( 'rodolfo_sanitize_id_list', 'rodolfo_sanitize_nav_locations', 'rodolfo_sanitize_int_map', 'rodolfo_sanitize_footer_util' );
            register_setting( 'rodolfo_options_group', $opt, array(
                'type'              => in_array( $sanitize, $array_sanitizers, true ) ? 'array' : 'string',
                'sanitize_callback' => $sanitize,
            ) );
        }
    }
}
add_action( 'admin_init', 'rodolfo_register_settings' );
