<?php
/**
 * Visible breadcrumbs — one item list drives both the HTML trail and the
 * BreadcrumbList in inc/schema.php, so the two can never disagree.
 *
 * Trail: Home › [Category ancestors › Category] › Title, on single posts, pages,
 * categories, tags, taxonomies, authors, date archives, search and 404.
 * Rank Math / Yoast render their own trail (and schema) when active.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ordered trail for the current view: list of array( 'name', 'url' ).
 * The last item is the current page (its url is the canonical URL).
 */
function rodolfo_breadcrumb_items() {
    $items = array( array( 'name' => __( 'Home', 'rodolfo' ), 'url' => home_url( '/' ) ) );

    if ( is_front_page() || is_home() && ! is_paged() ) {
        return $items;
    }

    if ( is_singular( 'post' ) ) {
        $cats = get_the_category();
        if ( ! empty( $cats ) ) {
            $ancestors = array_reverse( get_ancestors( $cats[0]->term_id, 'category', 'taxonomy' ) );
            foreach ( $ancestors as $anc_id ) {
                $anc     = get_term( $anc_id, 'category' );
                $items[] = array( 'name' => $anc->name, 'url' => get_category_link( $anc_id ) );
            }
            $items[] = array( 'name' => $cats[0]->name, 'url' => get_category_link( $cats[0]->term_id ) );
        }
        $items[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'url' => get_permalink() );
    } elseif ( is_page() ) {
        $page = get_queried_object();
        foreach ( array_reverse( get_post_ancestors( $page ) ) as $anc_id ) {
            $items[] = array( 'name' => wp_strip_all_tags( get_the_title( $anc_id ) ), 'url' => get_permalink( $anc_id ) );
        }
        $items[] = array( 'name' => wp_strip_all_tags( get_the_title( $page ) ), 'url' => get_permalink( $page ) );
    } elseif ( is_singular() ) {
        $items[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'url' => get_permalink() );
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            if ( is_taxonomy_hierarchical( $term->taxonomy ) ) {
                foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $anc_id ) {
                    $anc     = get_term( $anc_id, $term->taxonomy );
                    $items[] = array( 'name' => $anc->name, 'url' => get_term_link( $anc ) );
                }
            }
            $link    = get_term_link( $term );
            $items[] = array( 'name' => $term->name, 'url' => is_string( $link ) ? $link : home_url( '/' ) );
        }
    } elseif ( is_author() ) {
        $author = get_queried_object();
        if ( $author instanceof WP_User ) {
            $items[] = array( 'name' => $author->display_name, 'url' => get_author_posts_url( $author->ID ) );
        }
    } elseif ( is_date() ) {
        $items[] = array( 'name' => wp_strip_all_tags( get_the_archive_title() ), 'url' => rodolfo_current_url() );
    } elseif ( is_search() ) {
        /* translators: %s: search term */
        $items[] = array( 'name' => sprintf( __( 'Search results for %s', 'rodolfo' ), get_search_query() ), 'url' => rodolfo_current_url() );
    } elseif ( is_404() ) {
        $items[] = array( 'name' => __( 'Page not found', 'rodolfo' ), 'url' => rodolfo_current_url() );
    } elseif ( is_home() ) {
        $items[] = array( 'name' => get_bloginfo( 'name' ), 'url' => rodolfo_current_url() );
    }

    /** Filter the breadcrumb trail (also feeds the BreadcrumbList schema). */
    return apply_filters( 'rodolfo_breadcrumb_items', $items );
}

/** Canonical-style URL of the current request (path + query minus tracking noise). */
function rodolfo_current_url() {
    global $wp;
    $url = home_url( add_query_arg( array(), $wp->request ? '/' . $wp->request . '/' : '/' ) );
    if ( is_search() ) {
        $url = add_query_arg( 's', rawurlencode( get_search_query( false ) ), home_url( '/' ) );
    }
    $paged = (int) get_query_var( 'paged' );
    if ( $paged > 1 && ! is_search() ) {
        $url = get_pagenum_link( $paged );
    }
    return $url;
}

function rodolfo_breadcrumbs() {
    $default = is_singular() || is_category() || is_tag() || is_tax() || is_author() || is_date() || is_search() || is_404();
    if ( ! apply_filters( 'rodolfo_breadcrumbs_enabled', $default ) || is_front_page() ) {
        return;
    }
    echo '<nav class="tk-crumbs max-w-4xl mx-auto mb-4 px-1 text-sm text-slate-500 dark:text-slate-400" aria-label="' . esc_attr__( 'Breadcrumb', 'rodolfo' ) . '">';
    if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
        rank_math_the_breadcrumbs();
    } elseif ( function_exists( 'yoast_breadcrumb' ) ) {
        yoast_breadcrumb();
    } else {
        $items = rodolfo_breadcrumb_items();
        $last  = count( $items ) - 1;
        $sep   = '<span class="mx-2 text-slate-300 dark:text-slate-700" aria-hidden="true">&rsaquo;</span>';
        foreach ( $items as $i => $item ) {
            if ( $i > 0 ) {
                echo $sep; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
            }
            if ( $i === $last ) {
                echo '<span aria-current="page" class="text-slate-700 dark:text-slate-300">' . esc_html( wp_trim_words( $item['name'], 8, '…' ) ) . '</span>';
            } else {
                echo '<a href="' . esc_url( $item['url'] ) . '" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">' . esc_html( $item['name'] ) . '</a>';
            }
        }
    }
    echo '</nav>';
}
