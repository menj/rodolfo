<?php
/**
 * Google Search "Preferred Sources".
 *
 * Modes (Theme Options → Content):
 *   deeplink     default — a plain text link, no JavaScript.
 *   js_standard  Google's publisher button; loads Google's script, and only on
 *                views where a button is actually printed.
 *   disabled     nothing is output.
 *
 * Placements: none (shortcode only) | after_post | footer | sidebar, plus the
 * [preferred_source] shortcode. Per Google, only a domain or subdomain can be a
 * preferred source, so a subdirectory install (home URL with a path) outputs
 * nothing.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_ps_mode() {
    return function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_ps_mode' ) : 'deeplink';
}

function rodolfo_ps_placement() {
    return function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_ps_placement' ) : 'none';
}

/** Host of this site, or '' when the install lives in a subdirectory (not eligible). */
function rodolfo_ps_domain() {
    $home = home_url();
    $path = wp_parse_url( $home, PHP_URL_PATH );
    if ( $path && '/' !== $path ) {
        return '';
    }
    $host = wp_parse_url( $home, PHP_URL_HOST );
    return $host ? (string) $host : '';
}

function rodolfo_ps_active() {
    return 'disabled' !== rodolfo_ps_mode() && '' !== rodolfo_ps_domain();
}

function rodolfo_ps_anchor() {
    $text = trim( (string) get_option( 'rodolfo_ps_anchor', '' ) );
    return '' !== $text ? $text : __( 'Add as a Preferred Source on Google', 'rodolfo' );
}

function rodolfo_ps_url() {
    return 'https://www.google.com/preferences/source?q=' . rawurlencode( rodolfo_ps_domain() );
}

/** Set once a JS button has been printed, so the script loads only where needed. */
function rodolfo_ps_button_printed( $set = false ) {
    static $printed = false;
    if ( $set ) {
        $printed = true;
    }
    return $printed;
}

/** Markup for the current mode, or '' when inactive. $context only adds a class. */
function rodolfo_ps_markup( $context = 'inline' ) {
    if ( ! rodolfo_ps_active() ) {
        return '';
    }
    $class = 'preferred-source preferred-source-' . sanitize_html_class( $context );

    if ( 'js_standard' === rodolfo_ps_mode() ) {
        rodolfo_ps_button_printed( true );
        $lang = substr( (string) get_bloginfo( 'language' ), 0, 2 );
        return '<div class="' . esc_attr( $class ) . '"><div google-add-preferred-source-btn data-theme="light"'
            . ( $lang ? ' data-lang="' . esc_attr( $lang ) . '"' : '' ) . '></div></div>';
    }

    return '<p class="' . esc_attr( $class ) . '"><a href="' . esc_url( rodolfo_ps_url() ) . '" rel="noopener" target="_blank">'
        . esc_html( rodolfo_ps_anchor() ) . '</a></p>';
}

/** Template hook: echo the markup when the configured placement matches. */
function rodolfo_preferred_source( $location ) {
    if ( rodolfo_ps_placement() === $location ) {
        echo rodolfo_ps_markup( $location ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in rodolfo_ps_markup().
    }
}

function rodolfo_preferred_source_shortcode() {
    return rodolfo_ps_markup( 'shortcode' );
}
add_shortcode( 'preferred_source', 'rodolfo_preferred_source_shortcode' );

/** After-post placement. */
function rodolfo_ps_after_post( $content ) {
    if ( is_singular( 'post' ) && in_the_loop() && is_main_query() && 'after_post' === rodolfo_ps_placement() ) {
        $content .= rodolfo_ps_markup( 'after-post' );
    }
    return $content;
}
add_filter( 'the_content', 'rodolfo_ps_after_post', 30 );

/**
 * Google's script, printed in the footer only when a button was rendered, plus a
 * tiny observer that keeps the button's data-theme in step with the site's
 * light/dark mode (the theme toggles the "dark" class on <html>).
 */
function rodolfo_ps_script() {
    if ( ! rodolfo_ps_button_printed() ) {
        return;
    }
    ?>
<script>(function(){var d=document.documentElement;function s(){var t=d.classList.contains('dark')?'dark':'light';document.querySelectorAll('[google-add-preferred-source-btn]').forEach(function(e){e.setAttribute('data-theme',t)})}s();new MutationObserver(s).observe(d,{attributes:true,attributeFilter:['class']})})();</script>
<script async src="https://news.google.com/swg/js/v1/publisher.js"></script>
    <?php
}
add_action( 'wp_footer', 'rodolfo_ps_script', 20 );
