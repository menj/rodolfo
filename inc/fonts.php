<?php
/**
 * Self-hosted fonts (Inter).
 *
 * Inter is now bundled with the theme (assets/fonts/, OFL licence). This
 * module emits the @font-face + preload when the files are present; if they
 * are ever removed the theme silently falls back to the system font stack.
 *
 * TTF variable fonts are shipped (not woff2) — WordPress.org allows them and
 * they need no build step; convert to woff2 later for a smaller download.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_font_file() {
    return get_template_directory() . '/assets/fonts/Inter-Variable.ttf';
}

function rodolfo_fonts_enabled() {
    return is_readable( rodolfo_font_file() );
}

/**
 * Emit @font-face (upright + italic variable) + preload when the fonts exist.
 */
function rodolfo_font_face() {
    if ( ! rodolfo_fonts_enabled() ) {
        return;
    }
    $dir     = get_template_directory();
    $uri     = get_template_directory_uri() . '/assets/fonts';
    $upright = $uri . '/Inter-Variable.ttf';
    $italic  = $uri . '/Inter-Italic-Variable.ttf';
    $has_ital = is_readable( $dir . '/assets/fonts/Inter-Italic-Variable.ttf' );

    printf(
        '<link rel="preload" href="%s" as="font" type="font/ttf" crossorigin />' . "\n",
        esc_url( $upright )
    );

    $css  = sprintf(
        '@font-face{font-family:"Inter";font-style:normal;font-weight:100 900;font-display:swap;src:url("%s") format("truetype");}',
        esc_url( $upright )
    );
    if ( $has_ital ) {
        $css .= sprintf(
            '@font-face{font-family:"Inter";font-style:italic;font-weight:100 900;font-display:swap;src:url("%s") format("truetype");}',
            esc_url( $italic )
        );
    }
    wp_add_inline_style( 'rodolfo-theme', $css );
}
add_action( 'wp_enqueue_scripts', 'rodolfo_font_face' );
