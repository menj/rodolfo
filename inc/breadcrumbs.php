<?php
/**
 * Visible breadcrumbs (AEO/SEO).
 *
 * Renders a Home › Category › Title trail on single posts. If Rank Math or
 * Yoast is active their breadcrumb renderer is used instead (matching their
 * schema), so trails and BreadcrumbList JSON-LD never disagree. The theme's
 * own BreadcrumbList schema (inc/schema.php) already self-disables under an
 * SEO plugin.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_breadcrumbs() {
    if ( ! apply_filters( 'rodolfo_breadcrumbs_enabled', is_singular( 'post' ) ) ) {
        return;
    }
    echo '<nav class="rodolfo-crumbs max-w-4xl mx-auto mb-4 px-1 text-sm text-slate-500 dark:text-slate-400" aria-label="' . esc_attr__( 'Breadcrumb', 'rodolfo' ) . '">';
    if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
        rank_math_the_breadcrumbs();
    } elseif ( function_exists( 'yoast_breadcrumb' ) ) {
        yoast_breadcrumb();
    } else {
        $sep  = '<span class="mx-2 text-slate-300 dark:text-slate-700" aria-hidden="true">&rsaquo;</span>';
        $link = 'class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"';
        echo '<a href="' . esc_url( home_url( '/' ) ) . '" ' . $link . '>' . esc_html__( 'Home', 'rodolfo' ) . '</a>';
        $cats = get_the_category();
        if ( ! empty( $cats ) ) {
            echo $sep . '<a href="' . esc_url( get_category_link( $cats[0]->term_id ) ) . '" ' . $link . '>' . esc_html( $cats[0]->name ) . '</a>';
        }
        echo $sep . '<span aria-current="page" class="text-slate-700 dark:text-slate-300">' . esc_html( wp_trim_words( get_the_title(), 8, '…' ) ) . '</span>';
    }
    echo '</nav>';
}
