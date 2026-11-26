<?php
/**
 * Typography.
 *
 * Default: the native system font stack — zero bytes, zero requests. Optional:
 * self-hosted WOFF2 subsets in assets/fonts/ (assets/fonts/body.woff2 and
 * assets/fonts/body-italic.woff2), enabled under Theme Options → Performance.
 * TTF files are never loaded.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_system_font_stack() {
    return 'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif';
}

/** The WOFF2 files that exist; empty when the option is off or nothing is shipped. */
function rodolfo_woff2_files() {
    if ( ! get_option( 'rodolfo_font_woff2' ) ) {
        return array();
    }
    $dir   = get_template_directory() . '/assets/fonts/';
    $files = array();
    foreach ( array( 'normal' => 'body.woff2', 'italic' => 'body-italic.woff2' ) as $style => $file ) {
        if ( is_readable( $dir . $file ) ) {
            $files[ $style ] = get_template_directory_uri() . '/assets/fonts/' . $file;
        }
    }
    return $files;
}

function rodolfo_fonts_enabled() {
    return (bool) rodolfo_woff2_files();
}

function rodolfo_font_face() {
    $files = rodolfo_woff2_files();
    if ( ! $files ) {
        return; // System stack: nothing to emit.
    }
    $css = '';
    foreach ( $files as $style => $url ) {
        $css .= sprintf(
            '@font-face{font-family:"BodyFont";font-style:%s;font-weight:100 900;font-display:swap;src:url("%s") format("woff2")}',
            $style,
            esc_url( $url )
        );
    }
    $css .= ':root{--font-sans:"BodyFont",' . rodolfo_system_font_stack() . '}';
    wp_add_inline_style( rodolfo_handle( 'theme' ), $css );
}
add_action( 'wp_enqueue_scripts', 'rodolfo_font_face', 15 );

/** Preload the upright face only when a WOFF2 is actually in use. */
function rodolfo_font_preload() {
    $files = rodolfo_woff2_files();
    if ( ! empty( $files['normal'] ) ) {
        printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n", esc_url( $files['normal'] ) );
    }
}
add_action( 'wp_head', 'rodolfo_font_preload', 2 );
