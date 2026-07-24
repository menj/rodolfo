<?php
/**
 * Favicon / site-icon fallback.
 *
 * Serves the bundled site-icon mark ONLY while no core Site Icon is set
 * (Appearance → Customize → Site Identity). The moment a real Site Icon is
 * configured, has_site_icon() short-circuits this whole module so WordPress
 * takes over.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_favicon_links() {
    if ( has_site_icon() ) {
        return;
    }
    $base = get_template_directory_uri() . '/assets/icons';
    printf( '<link rel="icon" href="%s/site-icon.svg" type="image/svg+xml" />' . "\n", esc_url( $base ) );
    printf( '<link rel="icon" href="%s/site-icon-32.png" sizes="32x32" />' . "\n", esc_url( $base ) );
    printf( '<link rel="icon" href="%s/site-icon-192.png" sizes="192x192" />' . "\n", esc_url( $base ) );
    printf( '<link rel="apple-touch-icon" href="%s/site-icon-180.png" />' . "\n", esc_url( $base ) );
}
add_action( 'wp_head', 'rodolfo_favicon_links', 2 );
add_action( 'admin_head', 'rodolfo_favicon_links', 2 );
