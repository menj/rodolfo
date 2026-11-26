<?php
/**
 * Optional widget sidebar.
 *
 * The theme's layout is a centred single column, and that is the intended
 * reading experience — a sidebar competes with the text for attention and is
 * why the design omitted one originally. It is offered here because some sites
 * genuinely need widget support (subscribe forms, ad units, archives), but it
 * is **off by default**: nothing about the default layout changes unless the
 * `rodolfo_sidebar_enabled` option is switched on AND at least one widget has
 * been placed.
 *
 * Both conditions matter — an enabled-but-empty sidebar would otherwise leave a
 * dead column on every page.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Register the widget area. Always registered so widgets can be staged before enabling. */
function rodolfo_register_sidebar() {
    register_sidebar( array(
        'name'          => __( 'Blog sidebar', 'rodolfo' ),
        'id'            => 'tk-sidebar',
        'description'   => __( 'Optional. Only appears once you enable the sidebar under Theme Options → Content, and only on the views you choose there. The theme is designed as a single column — a sidebar is supported, not recommended.', 'rodolfo' ),
        'before_widget' => '<section id="%1$s" class="tk-widget %2$s mb-8 bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 p-6">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-4">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'rodolfo_register_sidebar' );

/**
 * Should the sidebar render on the current view?
 *
 * Requires: the feature switched on, a scope that matches, and actual widgets.
 */
function rodolfo_has_sidebar() {
    if ( ! get_option( 'rodolfo_sidebar_enabled' ) ) {
        return false;
    }
    if ( ! is_active_sidebar( 'tk-sidebar' ) ) {
        return false; // Enabled but empty — render nothing rather than a dead column.
    }

    $scope = (string) get_option( 'rodolfo_sidebar_scope', 'archives' );
    switch ( $scope ) {
        case 'everywhere':
            $show = ! is_singular( 'page' ) || is_page();
            break;
        case 'posts':
            $show = is_singular( 'post' );
            break;
        case 'archives':
        default:
            $show = ( is_home() || is_archive() || is_search() );
            break;
    }

    /** Filter whether the sidebar renders on this view. */
    return (bool) apply_filters( 'rodolfo_has_sidebar', $show, $scope );
}

/** Whitelist sanitizer for the scope setting. */
function rodolfo_sanitize_sidebar_scope( $value ) {
    return in_array( $value, array( 'archives', 'posts', 'everywhere' ), true ) ? $value : 'archives';
}
