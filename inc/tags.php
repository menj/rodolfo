<?php
/**
 * Built-in Tags page.
 *
 * Provides a virtual /tags/ route that lists every tag as a linked pill cloud,
 * with NO setup required — no page to create, no menu to configure. WordPress
 * has no native "all tags" archive (the tag base without a slug 404s), so this
 * registers a rewrite rule and renders a bundled template.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Register the /tags/ rewrite rule and its query var. */
function rodolfo_tags_rewrite() {
    add_rewrite_rule( '^tags/?$', 'index.php?rodolfo_tags=1', 'top' );
}
add_action( 'init', 'rodolfo_tags_rewrite' );

function rodolfo_tags_query_var( $vars ) {
    $vars[] = 'rodolfo_tags';
    return $vars;
}
add_filter( 'query_vars', 'rodolfo_tags_query_var' );

/**
 * Flush rewrite rules once, the first time this version of the module runs,
 * so the /tags/ route works on existing installs without re-activating.
 */
function rodolfo_tags_maybe_flush() {
    if ( RODOLFO_VERSION !== get_option( 'rodolfo_tags_rewrite_v' ) ) {
        rodolfo_tags_rewrite();
        flush_rewrite_rules();
        update_option( 'rodolfo_tags_rewrite_v', RODOLFO_VERSION );
    }
}
add_action( 'init', 'rodolfo_tags_maybe_flush', 11 );

/** True on the virtual tags page. */
function rodolfo_is_tags_page() {
    return (bool) get_query_var( 'rodolfo_tags' );
}

/** Canonical URL for the tags page (pretty permalinks or plain fallback). */
function rodolfo_tags_url() {
    return get_option( 'permalink_structure' )
        ? home_url( '/tags/' )
        : add_query_arg( 'rodolfo_tags', '1', home_url( '/' ) );
}

/** The virtual page is a real 200, never a 404. */
function rodolfo_tags_no_404( $preempt ) {
    if ( rodolfo_is_tags_page() ) {
        status_header( 200 );
        return true;
    }
    return $preempt;
}
add_filter( 'pre_handle_404', 'rodolfo_tags_no_404' );

/** Render the tags page from the bundled template. */
function rodolfo_tags_template( $template ) {
    if ( rodolfo_is_tags_page() ) {
        $bundled = get_template_directory() . '/tags-page.php';
        if ( file_exists( $bundled ) ) {
            return $bundled;
        }
    }
    return $template;
}
add_filter( 'template_include', 'rodolfo_tags_template' );

/** Give the page a sensible <title>. */
function rodolfo_tags_document_title( $parts ) {
    if ( rodolfo_is_tags_page() ) {
        $parts['title'] = __( 'Tags', 'rodolfo' );
    }
    return $parts;
}
add_filter( 'document_title_parts', 'rodolfo_tags_document_title' );

/**
 * One-time cleanup for installs scaffolded by pre-1.11.0 setup, which created a
 * "Footer" menu (Terms/Accessibility/…) and could leave it assigned to the
 * primary and/or footer locations — surfacing those utility links in the main
 * nav and footer column. Both locations are now automatic, so unassign ONLY our
 * own auto-created menu (matched by name) from them; never touches a menu the
 * owner named or assigned themselves, and never deletes anything.
 */
function rodolfo_menu_locations_cleanup() {
    if ( '1' === get_option( 'rodolfo_menu_cleanup_v' ) ) {
        return;
    }
    $auto = wp_get_nav_menu_object( 'Footer' );
    if ( $auto ) {
        $locations = get_theme_mod( 'nav_menu_locations', array() );
        $changed   = false;
        foreach ( array( 'primary', 'footer' ) as $loc ) {
            if ( isset( $locations[ $loc ] ) && (int) $locations[ $loc ] === (int) $auto->term_id ) {
                unset( $locations[ $loc ] );
                $changed = true;
            }
        }
        if ( $changed ) {
            set_theme_mod( 'nav_menu_locations', $locations );
        }
    }
    update_option( 'rodolfo_menu_cleanup_v', '1' );
}
add_action( 'admin_init', 'rodolfo_menu_locations_cleanup' );
