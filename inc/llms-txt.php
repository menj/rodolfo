<?php
/**
 * llms.txt — GEO (generative engine optimisation) site summary.
 *
 * Serves a virtual /llms.txt (llmstxt.org convention): a Markdown summary AI
 * crawlers read to understand and cite the site — name, tagline, key pages,
 * recent posts, and topics. No file to create; built live from real content,
 * modelled on the /tags/ virtual route. A physical llms.txt in the web root
 * still wins (the server serves it before WordPress routing runs).
 *
 * Disable or modify: `rodolfo_llms_txt_enabled` / `rodolfo_llms_txt` filters.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_llms_rewrite() {
    add_rewrite_rule( '^llms\.txt$', 'index.php?rodolfo_llms=1', 'top' );
}
add_action( 'init', 'rodolfo_llms_rewrite' );

function rodolfo_llms_query_var( $vars ) {
    $vars[] = 'rodolfo_llms';
    return $vars;
}
add_filter( 'query_vars', 'rodolfo_llms_query_var' );

/** One-time rewrite flush so the route works on existing installs. */
function rodolfo_llms_maybe_flush() {
    if ( RODOLFO_VERSION !== get_option( 'rodolfo_llms_rewrite_v' ) ) {
        rodolfo_llms_rewrite();
        flush_rewrite_rules();
        update_option( 'rodolfo_llms_rewrite_v', RODOLFO_VERSION );
    }
}
add_action( 'init', 'rodolfo_llms_maybe_flush', 11 );

/** Build the llms.txt Markdown body. */
function rodolfo_llms_txt_body() {
    $lines   = array();
    $lines[] = '# ' . get_bloginfo( 'name' );
    $desc    = get_bloginfo( 'description' );
    if ( $desc ) {
        $lines[] = '';
        $lines[] = '> ' . $desc;
    }

    $pages = function_exists( 'rodolfo_primary_nav_pages' ) ? rodolfo_primary_nav_pages() : array();
    if ( ! empty( $pages ) ) {
        $lines[] = '';
        $lines[] = '## Pages';
        $lines[] = '';
        foreach ( $pages as $page ) {
            $lines[] = '- [' . get_the_title( $page->ID ) . '](' . get_permalink( $page->ID ) . ')';
        }
    }

    $posts = get_posts( array(
        'numberposts'  => 20,
        'post_status'  => 'publish',
        // Unlisted posts are hidden from listings, search, and feeds; keep
        // them out of the AI-crawler summary too. get_posts() bypasses the
        // unlist module's main-query filter, so exclude explicitly.
        'post__not_in' => function_exists( 'rodolfo_unlisted_ids' ) ? rodolfo_unlisted_ids() : array(),
    ) );
    if ( ! empty( $posts ) ) {
        $lines[] = '';
        $lines[] = '## Recent posts';
        $lines[] = '';
        foreach ( $posts as $post ) {
            $excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
            $excerpt = wp_trim_words( $excerpt, 25, '…' );
            $lines[] = '- [' . get_the_title( $post ) . '](' . get_permalink( $post ) . ')' . ( $excerpt ? ': ' . $excerpt : '' );
        }
    }

    $cats = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hide_empty' => true ) );
    if ( ! empty( $cats ) ) {
        $lines[] = '';
        $lines[] = '## Topics';
        $lines[] = '';
        foreach ( $cats as $cat ) {
            $lines[] = '- [' . $cat->name . '](' . get_category_link( $cat->term_id ) . ')';
        }
    }

    /** Filter the final llms.txt Markdown. */
    return apply_filters( 'rodolfo_llms_txt', implode( "\n", $lines ) . "\n" );
}

/** Emit the llms.txt response and stop. Shared by both routes below. */
function rodolfo_llms_output() {
    if ( ! apply_filters( 'rodolfo_llms_txt_enabled', true ) ) {
        return;
    }
    status_header( 200 );
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Robots-Tag: noindex' ); // The file informs AI, it isn't a search result.
    echo rodolfo_llms_txt_body(); // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text Markdown, built from escaped-at-source titles.
    exit;
}

/** Serve the rewrite route (pretty permalinks). */
function rodolfo_llms_serve() {
    if ( get_query_var( 'rodolfo_llms' ) ) {
        rodolfo_llms_output();
    }
}
add_action( 'template_redirect', 'rodolfo_llms_serve' );

/**
 * Plain-permalink fallback. Rewrite rules only fire when pretty permalinks
 * are enabled, and crawlers request the literal /llms.txt path — so on plain
 * permalinks the route above never matches. Answer the request URI directly.
 * Path comparison is against home_url() so subdirectory installs resolve.
 */
function rodolfo_llms_request_fallback() {
    if ( get_option( 'permalink_structure' ) ) {
        return; // Pretty permalinks — the rewrite rule handles it.
    }
    if ( empty( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }
    $request = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- path-compared verbatim below, never output.
    $target  = (string) wp_parse_url( home_url( '/llms.txt' ), PHP_URL_PATH );
    if ( untrailingslashit( $request ) === untrailingslashit( $target ) ) {
        rodolfo_llms_output();
    }
}
add_action( 'init', 'rodolfo_llms_request_fallback', 20 );

/**
 * AI-crawler guidance in robots.txt (GEO): explicitly welcome the major AI
 * crawlers and point them at llms.txt. Sites that do NOT want AI training/
 * citation traffic can disable with:
 *   add_filter( 'rodolfo_ai_crawlers_welcome', '__return_false' );
 * Only appends to the WordPress-generated virtual robots.txt — a physical
 * robots.txt file is never touched.
 */
function rodolfo_ai_robots( $output ) {
    if ( ! apply_filters( 'rodolfo_ai_crawlers_welcome', true ) ) {
        return $output;
    }
    $bots = array( 'GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot', 'Google-Extended', 'CCBot' );
    $output .= "\n# AI crawlers — see also: " . home_url( '/llms.txt' ) . "\n";
    foreach ( $bots as $bot ) {
        $output .= "User-agent: $bot\nAllow: /\n";
    }
    return $output;
}
add_filter( 'robots_txt', 'rodolfo_ai_robots' );
