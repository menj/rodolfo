<?php
/**
 * Navigation resolution: primary/secondary Page nav, per-page location & order, privacy-page resolver, built-in footer utility links.
 *
 * Extracted verbatim from functions.php in 3.4.0 — a pure structural move,
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Primary navigation links — shared by the desktop nav and the mobile panel.
// Falls back to category links when the primary menu is unassigned OR assigned
// but empty (an empty menu previously produced a blank nav).
function rodolfo_primary_nav_links( $menu_class ) {
    $html = '';
    if ( has_nav_menu( 'primary' ) ) {
        $html = (string) wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => $menu_class,
            'fallback_cb'    => false,
            'depth'          => 1,
            'echo'           => false,
        ) );
    }
    if ( ! trim( $html ) ) {
        // Automatic, Page-driven fallback: every top-level published Page appears
        // here on its own, EXCEPT pages the owner flags (in Theme Options) to live
        // only in the secondary/footer nav. No menu to build, no hardcoded links.
        $link_class = 'hover:text-blue-600 dark:hover:text-blue-400 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1';
        foreach ( rodolfo_primary_nav_pages() as $page ) {
            $html .= '<a href="' . esc_url( get_permalink( $page->ID ) ) . '" class="' . esc_attr( $link_class ) . '">' . esc_html( get_the_title( $page->ID ) ) . '</a>';
        }
    }
    return rodolfo_nav_keynav( $html );
}

/**
 * Number the first nine nav links so 1–9 jumps to them (see assets/js/main.js).
 *
 * Applied after rendering so it works for both branches above — a WordPress
 * menu and the Page-driven fallback produce different markup but both end as
 * anchors. Off by default; the digits render as a small badge only on wide
 * screens, so the mobile panel is unaffected.
 *
 * Only the FIRST occurrence in a page load is numbered: header.php calls the
 * nav renderer twice (desktop + mobile panel), and duplicate data-keynav
 * attributes would make document.querySelector ambiguous.
 */
function rodolfo_nav_keynav( $html ) {
    static $done = false;

    if ( $done || ! get_option( 'rodolfo_keynav_enabled' ) || '' === trim( $html ) ) {
        return $html;
    }
    $done = true;

    $i = 0;
    return (string) preg_replace_callback(
        '/<a\s/i',
        function ( $m ) use ( &$i ) {
            $i++;
            if ( $i > 9 ) {
                return $m[0];
            }
            return '<a data-keynav="' . $i . '" ';
        },
        $html
    );
}

/**
 * The site's Privacy Policy page, tolerating either common slug.
 *
 * Resolution order: the page registered in Settings → Privacy (authoritative),
 * then a page at /privacy-policy, then /privacy. Returns a WP_Post or null.
 * Used by setup (so it never creates a duplicate), the footer utility link,
 * and nav exclusion — so all three agree on which page is "the" privacy page.
 */
function rodolfo_privacy_page() {
    $registered = (int) get_option( 'wp_page_for_privacy_policy' );
    if ( $registered ) {
        $page = get_post( $registered );
        if ( $page && 'publish' === $page->post_status ) {
            return $page;
        }
    }
    foreach ( array( 'privacy-policy', 'privacy' ) as $slug ) {
        $page = get_page_by_path( $slug );
        if ( $page ) {
            return $page;
        }
    }
    return null;
}

// All top-level published Pages (raw order; nav order is applied per-location).
// Excludes the designated Privacy Policy page — it is controlled once, via the
// built-in "Privacy Policy" footer link, so it never appears twice.
function rodolfo_nav_pages() {
    $exclude    = array();
    $privacy    = rodolfo_privacy_page();
    if ( $privacy ) {
        $exclude[] = (int) $privacy->ID;
    }
    $sitemap = function_exists( 'rodolfo_sitemap_page' ) ? rodolfo_sitemap_page() : null;
    if ( $sitemap ) {
        $exclude[] = (int) $sitemap->ID;
    }
    return get_pages( array(
        'parent'      => 0,
        'sort_column' => 'menu_order,post_title',
        'exclude'     => $exclude,
    ) );
}

// Per-page location map: page_id => 'header' | 'footer' | 'hidden'.
// Default (no stored value) is 'header'.
function rodolfo_nav_locations() {
    $value = get_option( 'rodolfo_nav_locations', array() );
    return is_array( $value ) ? $value : array();
}

// A single page's location, defaulting to 'header'.
function rodolfo_nav_location( $page_id ) {
    $map = rodolfo_nav_locations();
    $loc = isset( $map[ (int) $page_id ] ) ? $map[ (int) $page_id ] : 'header';
    return in_array( $loc, array( 'header', 'footer', 'hidden' ), true ) ? $loc : 'header';
}

// Per-page order map: page_id => int. Falls back to the page's menu_order.
function rodolfo_nav_order_map() {
    $value = get_option( 'rodolfo_nav_order', array() );
    return is_array( $value ) ? $value : array();
}

// A single page's order value.
function rodolfo_nav_order_val( $page ) {
    $map = rodolfo_nav_order_map();
    $id  = (int) $page->ID;
    if ( isset( $map[ $id ] ) && '' !== $map[ $id ] ) {
        return (int) $map[ $id ];
    }
    return (int) $page->menu_order;
}

// Pages assigned to a given location, sorted by order then title.
function rodolfo_nav_pages_in( $location ) {
    $pages = array_filter( rodolfo_nav_pages(), function ( $page ) use ( $location ) {
        return rodolfo_nav_location( $page->ID ) === $location;
    } );
    usort( $pages, function ( $a, $b ) {
        $oa = rodolfo_nav_order_val( $a );
        $ob = rodolfo_nav_order_val( $b );
        if ( $oa === $ob ) {
            return strcasecmp( get_the_title( $a->ID ), get_the_title( $b->ID ) );
        }
        return $oa <=> $ob;
    } );
    return array_values( $pages );
}

// Top-level Pages shown in the PRIMARY (header) nav.
function rodolfo_primary_nav_pages() {
    return rodolfo_nav_pages_in( 'header' );
}

// Top-level Pages moved to the SECONDARY (footer) nav.
function rodolfo_secondary_nav_pages() {
    return rodolfo_nav_pages_in( 'footer' );
}

// One-time migration: pre-2.4.0 stored footer pages in rodolfo_nav_excluded_pages
// (a flat ID list). Convert any such IDs to the new per-page location map.
function rodolfo_migrate_nav_excluded() {
    if ( '1' === get_option( 'rodolfo_nav_migrated_v' ) ) {
        return;
    }
    $legacy = get_option( 'rodolfo_nav_excluded_pages', null );
    if ( is_array( $legacy ) && ! empty( $legacy ) ) {
        $locations = rodolfo_nav_locations();
        foreach ( $legacy as $id ) {
            $id = absint( $id );
            if ( $id && ! isset( $locations[ $id ] ) ) {
                $locations[ $id ] = 'footer';
            }
        }
        update_option( 'rodolfo_nav_locations', $locations );
    }
    delete_option( 'rodolfo_nav_excluded_pages' );
    update_option( 'rodolfo_nav_migrated_v', '1' );
}
add_action( 'admin_init', 'rodolfo_migrate_nav_excluded' );

// Built-in footer utility links — visibility, custom label, and order.
// Keys: faq, terms, privacy, sitemap, rss. Stored as rodolfo_footer_util:
// key => array( 'show' => bool, 'label' => string|'', 'order' => int ).
// A blank label falls back to the default. Defaults shown, ordered after pages.
function rodolfo_footer_util_defaults() {
    return array(
        'faq'     => array( 'show' => true, 'label' => '', 'order' => 100 ),
        'terms'   => array( 'show' => true, 'label' => '', 'order' => 110 ),
        'privacy' => array( 'show' => true, 'label' => '', 'order' => 120 ),
        'sitemap' => array( 'show' => true, 'label' => '', 'order' => 130 ),
        'rss'     => array( 'show' => true, 'label' => '', 'order' => 140 ),
    );
}

function rodolfo_footer_util_config() {
    $defaults = rodolfo_footer_util_defaults();
    $stored   = get_option( 'rodolfo_footer_util', array() );
    if ( ! is_array( $stored ) ) {
        return $defaults;
    }
    $out = array();
    foreach ( $defaults as $key => $def ) {
        $out[ $key ] = array(
            'show'  => isset( $stored[ $key ]['show'] ) ? (bool) $stored[ $key ]['show'] : $def['show'],
            'label' => isset( $stored[ $key ]['label'] ) ? (string) $stored[ $key ]['label'] : $def['label'],
            'order' => isset( $stored[ $key ]['order'] ) && '' !== $stored[ $key ]['order'] ? (int) $stored[ $key ]['order'] : $def['order'],
        );
    }
    return $out;
}

// Default (untranslated-context) label for a built-in link key.
function rodolfo_footer_util_default_label( $key ) {
    $labels = array(
        'faq'     => __( 'FAQ', 'rodolfo' ),
        'terms'   => __( 'Terms of Use', 'rodolfo' ),
        'privacy' => __( 'Privacy Policy', 'rodolfo' ),
        'sitemap' => __( 'Sitemap', 'rodolfo' ),
        'rss'     => __( 'RSS', 'rodolfo' ),
    );
    return isset( $labels[ $key ] ) ? $labels[ $key ] : '';
}

// Resolve a built-in footer link's URL + label, or null if unavailable.
// $custom_label (from options) overrides the default when non-empty.
function rodolfo_footer_util_link( $key, $custom_label = '' ) {
    $url = null;
    switch ( $key ) {
        case 'faq':
            $page = get_page_by_path( 'faq' );
            $url  = $page ? get_permalink( $page->ID ) : '';
            break;
        case 'terms':
            $page = get_page_by_path( 'terms' );
            if ( ! $page ) {
                $page = get_page_by_path( 'terms-of-use' );
            }
            $url = $page ? get_permalink( $page->ID ) : '';
            break;
        case 'privacy':
            $url = get_privacy_policy_url();
            if ( ! $url ) {
                // Settings → Privacy unset: fall back to a page at either slug.
                $page = rodolfo_privacy_page();
                $url  = $page ? get_permalink( $page->ID ) : '';
            }
            break;
        case 'sitemap':
            $url = get_option( 'rodolfo_link_sitemap' );
            if ( ! $url && function_exists( 'rodolfo_sitemap_page_url' ) ) {
                $url = rodolfo_sitemap_page_url();
            }
            if ( ! $url && function_exists( 'get_sitemap_url' ) ) {
                $url = get_sitemap_url( 'index' );
            }
            break;
        case 'rss':
            $url = get_bloginfo( 'rss2_url' );
            break;
    }
    if ( empty( $url ) ) {
        return null; // No destination — the link simply doesn't render.
    }
    $label = '' !== trim( (string) $custom_label ) ? $custom_label : rodolfo_footer_util_default_label( $key );
    return array( 'url' => $url, 'label' => $label );
}

/**
 * The full, ordered footer secondary link list: footer-assigned Pages merged
 * with the enabled built-in utility links, all sorted by their order value.
 * Returns a list of array( 'url', 'label' ).
 */
function rodolfo_footer_nav_items() {
    $items = array();

    foreach ( rodolfo_secondary_nav_pages() as $page ) {
        $items[] = array(
            'url'   => get_permalink( $page->ID ),
            'label' => get_the_title( $page->ID ),
            'order' => rodolfo_nav_order_val( $page ),
        );
    }

    foreach ( rodolfo_footer_util_config() as $key => $cfg ) {
        if ( ! $cfg['show'] ) {
            continue;
        }
        $link = rodolfo_footer_util_link( $key, $cfg['label'] );
        if ( $link ) {
            $items[] = array(
                'url'   => $link['url'],
                'label' => $link['label'],
                'order' => $cfg['order'],
            );
        }
    }

    usort( $items, function ( $a, $b ) {
        if ( $a['order'] === $b['order'] ) {
            return strcasecmp( $a['label'], $b['label'] );
        }
        return $a['order'] <=> $b['order'];
    } );

    return $items;
}

// ---------------------------------------------------------------
// HTML sitemap + topic helpers (shared by footer, page-sitemap.php, 404, schema)
// ---------------------------------------------------------------

/** The page using the HTML sitemap template, or null. */
function rodolfo_sitemap_page() {
    static $page = false;
    if ( false === $page ) {
        $found = get_pages( array(
            'meta_key'    => '_wp_page_template',
            'meta_value'  => 'page-sitemap.php',
            'number'      => 1,
            'post_status' => 'publish',
        ) );
        $page = $found ? $found[0] : null;
    }
    return $page;
}

function rodolfo_sitemap_page_url() {
    $page = rodolfo_sitemap_page();
    return $page ? get_permalink( $page->ID ) : '';
}

/** Most-used categories (footer "Explore Topics", 404 page). */
function rodolfo_footer_topics( $limit = 6 ) {
    $terms = get_categories( array(
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => (int) $limit,
        'hide_empty' => true,
    ) );
    return is_array( $terms ) ? $terms : array();
}

/** Sections of the HTML sitemap as array( name, url ) — drives its ItemList schema. */
function rodolfo_sitemap_links() {
    $base = rodolfo_sitemap_page_url();
    if ( ! $base ) {
        return array();
    }
    return array(
        array( __( 'Pages', 'rodolfo' ), $base . '#pages' ),
        array( __( 'Categories', 'rodolfo' ), $base . '#categories' ),
        array( __( 'Recent posts', 'rodolfo' ), $base . '#posts' ),
    );
}
