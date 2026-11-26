<?php
/**
 * Branded login screen (natively, no plugin).
 *
 * Replaces the WordPress logo on wp-login.php with the site's Custom Logo (or
 * Site Icon), links it to the home page, and tints the primary button/links
 * with the theme accent. Absorbs the "Login Logo" plugin's purpose without
 * requiring a hand-placed wp-content/login-logo.png — it uses the media the
 * owner already set in the Customizer.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The best available brand image for the login screen: Custom Logo, else the
 * Site Icon, else '' (leaves the default WordPress logo in place).
 */
function rodolfo_login_logo_src() {
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $src = wp_get_attachment_image_src( $logo_id, 'full' );
        if ( $src ) {
            return $src[0];
        }
    }
    if ( function_exists( 'get_site_icon_url' ) && has_site_icon() ) {
        return get_site_icon_url( 192 );
    }
    return '';
}

function rodolfo_login_styles() {
    $logo   = rodolfo_login_logo_src();
    $accent = function_exists( 'rodolfo_accent' ) ? rodolfo_accent() : '#3d54d6'; // Customizer accent (inc/accent.php), brand cobalt by default.
    ?>
    <style id="tk-login">
        <?php if ( $logo ) : ?>
        .login h1 a {
            background-image: url(<?php echo esc_url( $logo ); ?>);
            background-size: contain;
            background-position: center;
            width: 100%;
            max-width: 320px;
            height: 84px;
        }
        <?php endif; ?>
        body.login { background: #f8fafc; }
        .login h1 a { margin-bottom: 8px; }
        .wp-core-ui .button-primary {
            background: <?php echo esc_attr( $accent ); ?>;
            border-color: <?php echo esc_attr( $accent ); ?>;
            box-shadow: none;
            text-shadow: none;
        }
        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus { filter: brightness(0.94); }
        .login #backtoblog a,
        .login #nav a { color: #475569; }
        .login #backtoblog a:hover,
        .login #nav a:hover,
        .login form .input:focus { border-color: <?php echo esc_attr( $accent ); ?>; }
        input:focus { box-shadow: 0 0 0 1px <?php echo esc_attr( $accent ); ?> !important; }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'rodolfo_login_styles' );

// Point the login logo link at the site home, titled with the site name.
function rodolfo_login_headerurl() {
    return esc_url( home_url( '/' ) );
}
add_filter( 'login_headerurl', 'rodolfo_login_headerurl' );

function rodolfo_login_headertext() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'rodolfo_login_headertext' );
