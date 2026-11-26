<?php
/**
 * Theme Options plumbing: admin CSS, export / import / reset handlers, menu registration.
 *
 * Everything around the settings screen except the screen itself. All three
 * admin_post handlers are nonce-checked and manage_options-gated.
 *
 * Extracted from inc/options-page.php in 3.8.0 — a pure structural move, no
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// ADMIN STYLES — Theme Options page only
// ============================================================
function rodolfo_admin_enqueue( $hook ) {
    if ( $hook !== 'appearance_page_tk-theme-options' ) {
        return;
    }

    // Tab switcher as an enqueued FILE, not an inline <script>: inline scripts
    // die under a strict CSP (common with security plugins in wp-admin), which
    // left the tabs unusable. Enqueued sources pass script-src by URL.
    $suffix = RODOLFO_ASSET_SUFFIX; // Only .min files ship; SCRIPT_DEBUG must not point at missing sources.
    wp_enqueue_script(
        'tk-admin',
        get_template_directory_uri() . '/assets/js/admin' . $suffix . '.js',
        array(),
        RODOLFO_VERSION,
        true
    );
    // Admin screen styles as an enqueued FILE for the same reason as the tab
    // switcher above: inline styles die under a strict style-src CSP, and a
    // file is cacheable and overridable where an inline string is neither.
    wp_enqueue_style(
        'tk-admin',
        get_template_directory_uri() . '/assets/css/admin' . $suffix . '.css',
        array(),
        RODOLFO_VERSION
    );
}
add_action( 'admin_enqueue_scripts', 'rodolfo_admin_enqueue' );

// ============================================================
// EXPORT SETTINGS
// ============================================================
function rodolfo_export_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'rodolfo' ) );
    }
    check_admin_referer( 'rodolfo_export_settings' );

    // Single source of truth for option keys — export, import, and reset all
    // derive from the sanitizer map so new options can't be missed here.
    $keys = array_keys( rodolfo_option_sanitizers() );
    $data = array();
    foreach ( $keys as $key ) {
        $data[ $key ] = get_option( $key );
    }

    $filename = 'tk-settings-' . gmdate( 'Y-m-d' ) . '.json';
    header( 'Content-Type: application/json' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Pragma: no-cache' );
    echo wp_json_encode( $data, JSON_PRETTY_PRINT );
    exit;
}
add_action( 'admin_post_rodolfo_export_settings', 'rodolfo_export_settings' );

// ============================================================
// IMPORT SETTINGS
// ============================================================
function rodolfo_import_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'rodolfo' ) );
    }
    check_admin_referer( 'rodolfo_import_settings' );

    $redirect = admin_url( 'themes.php?page=tk-theme-options&tab=general' );

    $tmp = isset( $_FILES['rodolfo_import_file']['tmp_name'] ) ? $_FILES['rodolfo_import_file']['tmp_name'] : '';
    if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
        wp_safe_redirect( add_query_arg( 'rodolfo_notice', 'import_empty', $redirect ) );
        exit;
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- server-generated upload path, verified by is_uploaded_file() above.
    $raw = file_get_contents( $tmp );
    $data = json_decode( $raw, true );

    if ( ! is_array( $data ) ) {
        wp_safe_redirect( add_query_arg( 'rodolfo_notice', 'import_invalid', $redirect ) );
        exit;
    }

    // Imported values are sanitised per key with the same callbacks used by
    // register_setting() — a crafted JSON file must not bypass sanitisation.
    $can_scripts = current_user_can( 'unfiltered_html' );
    foreach ( rodolfo_option_sanitizers() as $key => $sanitize ) {
        if ( ! array_key_exists( $key, $data ) ) {
            continue;
        }
        if ( 'scripts' === $sanitize ) {
            // Raw HTML/script fields require the unfiltered_html capability
            // (regular admins on multisite do not have it).
            if ( $can_scripts ) {
                update_option( $key, (string) $data[ $key ] );
            }
            continue;
        }
        update_option( $key, call_user_func( $sanitize, $data[ $key ] ) );
    }

    wp_safe_redirect( add_query_arg( 'rodolfo_notice', 'import_ok', $redirect ) );
    exit;
}
add_action( 'admin_post_rodolfo_import_settings', 'rodolfo_import_settings' );

// ============================================================
// RESET SETTINGS
// ============================================================
function rodolfo_reset_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'rodolfo' ) );
    }
    check_admin_referer( 'rodolfo_reset_settings' );

    // Derived from the sanitizer map — same key list as export/import.
    $keys = array_keys( rodolfo_option_sanitizers() );
    foreach ( $keys as $key ) {
        delete_option( $key );
    }

    $tab = isset( $_POST['rodolfo_active_tab'] ) ? sanitize_key( $_POST['rodolfo_active_tab'] ) : 'general';
    wp_safe_redirect( add_query_arg(
        array( 'rodolfo_notice' => 'reset_ok', 'tab' => $tab ),
        admin_url( 'themes.php?page=tk-theme-options' )
    ) );
    exit;
}
add_action( 'admin_post_rodolfo_reset_settings', 'rodolfo_reset_settings' );

// ============================================================
// THEME OPTIONS MENU
// ============================================================
function rodolfo_theme_options_menu() {
    add_theme_page(
        __( 'Theme Options', 'rodolfo' ),
        __( 'Theme Options', 'rodolfo' ),
        'manage_options',
        'tk-theme-options',
        'rodolfo_theme_options_page'
    );
}
add_action( 'admin_menu', 'rodolfo_theme_options_menu' );
