<?php
/**
 * Accent colour (Customizer → Colors → "Accent colour").
 *
 * The entire front-end accent system is already variable-driven: the compiled
 * Tailwind sheet ships stock blues and style.css re-points every blue utility
 * the templates use to --tk-accent (+ the -700/-500/-400 shades). So a
 * user-chosen accent needs exactly one thing: override those four variables.
 * When the picker sits at the default cobalt, this module prints NOTHING —
 * zero cost for the default install.
 *
 * The three shades are derived from the picked hue (mix toward black for
 * hover, toward white for focus ring and dark-mode text) so one control
 * stays one decision — never a second stored value to drift.
 *
 * Consumers that render outside the stylesheet pipeline read the same source:
 * the maintenance page, branded login, reading-progress fill, and the options
 * screen (a one-line dynamic variable override — the only inline admin style,
 * because a user-chosen value cannot live in a static file).
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

const RODOLFO_ACCENT_DEFAULT = '#3d54d6';

/** The active accent hex ('#rrggbb'), validated, defaulting to brand cobalt. */
function rodolfo_accent() {
    $hex = sanitize_hex_color( (string) get_theme_mod( 'rodolfo_accent', RODOLFO_ACCENT_DEFAULT ) );
    return $hex ? $hex : RODOLFO_ACCENT_DEFAULT;
}

/**
 * Mix a hex colour toward black (negative $amount) or white (positive).
 *
 * @param string $hex    '#rrggbb'.
 * @param float  $amount -1..1; fraction of the distance to black/white.
 * @return string '#rrggbb'
 */
function rodolfo_accent_mix( $hex, $amount ) {
    $hex = ltrim( $hex, '#' );
    if ( 3 === strlen( $hex ) ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $out = '#';
    foreach ( str_split( $hex, 2 ) as $channel ) {
        $c    = hexdec( $channel );
        $c    = $amount >= 0 ? $c + ( 255 - $c ) * $amount : $c * ( 1 + $amount );
        $out .= str_pad( dechex( (int) round( max( 0, min( 255, $c ) ) ) ), 2, '0', STR_PAD_LEFT );
    }
    return $out;
}

/** The four variables style.css expects, derived from one picked hue. */
function rodolfo_accent_vars() {
    $accent = rodolfo_accent();
    return array(
        '--tk-accent'     => $accent,
        '--tk-accent-700' => rodolfo_accent_mix( $accent, -0.22 ),
        '--tk-accent-500' => rodolfo_accent_mix( $accent, 0.14 ),
        '--tk-accent-400' => rodolfo_accent_mix( $accent, 0.36 ),
    );
}

/** Customizer control, in core's own Colors section. */
function rodolfo_accent_customize( $wp_customize ) {
    $wp_customize->add_setting( 'rodolfo_accent', array(
        'default'           => RODOLFO_ACCENT_DEFAULT,
        'transport'         => 'refresh',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'rodolfo_accent', array(
        'label'       => __( 'Accent colour', 'rodolfo' ),
        'description' => __( 'Recolours links, buttons, category pills, the reading-progress bar, the login screen and the maintenance page. Hover and focus shades are derived automatically.', 'rodolfo' ),
        'section'     => 'colors',
    ) ) );
}
add_action( 'customize_register', 'rodolfo_accent_customize' );

/** Print the override — only when the accent differs from the default. */
function rodolfo_accent_output() {
    if ( strtolower( rodolfo_accent() ) === RODOLFO_ACCENT_DEFAULT ) {
        return;
    }
    $css = ':root{';
    foreach ( rodolfo_accent_vars() as $var => $value ) {
        $css .= $var . ':' . $value . ';';
    }
    $css .= '}';
    // Attached to the runtime layer so it always outputs after style.css.
    wp_add_inline_style( rodolfo_handle( 'theme' ), $css );
}
add_action( 'wp_enqueue_scripts', 'rodolfo_accent_output', 20 );

/** The options screen carries the accent in its own scoped variables. */
function rodolfo_accent_admin( $hook ) {
    if ( 'appearance_page_tk-theme-options' !== $hook || strtolower( rodolfo_accent() ) === RODOLFO_ACCENT_DEFAULT ) {
        return;
    }
    wp_add_inline_style( 'tk-admin', sprintf(
        '.tk-opts{--rd-accent:%s;--rd-accent-700:%s}',
        rodolfo_accent(),
        rodolfo_accent_mix( rodolfo_accent(), -0.22 )
    ) );
}
add_action( 'admin_enqueue_scripts', 'rodolfo_accent_admin', 20 );
