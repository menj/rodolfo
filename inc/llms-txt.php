<?php
/**
 * llms.txt — GEO (generative engine optimisation) site summary.
 *
 * Serves a virtual /llms.txt (llmstxt.org convention): a Markdown summary AI
 * crawlers read to understand and cite the site — name, tagline, key pages,
 * posts, and topics, in one of four structural variants (editorial, minimal,
 * structured, directory — Theme Options → Content). No file to create; built live from real content,
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

/**
 * Layout settings per template. The four variants differ in section headings,
 * bullet character, intro, excerpt length and post limits, so two installs do
 * not publish the same llms.txt skeleton.
 */
function rodolfo_llms_template_config( $template ) {
    $all = array(
        'editorial'  => array(
            'bullet' => '-', 'intro' => 'summary', 'posts' => 20, 'excerpt' => 25, 'cats' => 10,
            'h' => array( 'pages' => 'Start here', 'posts' => 'Latest writing', 'topics' => 'Subjects' ),
        ),
        'minimal'    => array(
            'bullet' => '-', 'intro' => 'none', 'posts' => 10, 'excerpt' => 0, 'cats' => 8,
            'h' => array( 'pages' => 'Pages', 'posts' => 'Posts', 'topics' => 'Topics' ),
        ),
        'structured' => array(
            'bullet' => '*', 'intro' => 'facts', 'posts' => 30, 'excerpt' => 15, 'cats' => 12,
            'h' => array( 'pages' => 'Key pages', 'posts' => 'Articles', 'topics' => 'Categories' ),
        ),
        'directory'  => array(
            'bullet' => '*', 'intro' => 'none', 'posts' => 5, 'excerpt' => 0, 'cats' => 6,
            'h' => array( 'pages' => 'Pages', 'posts' => 'Index', 'topics' => 'Index' ),
        ),
    );
    return isset( $all[ $template ] ) ? $all[ $template ] : $all['editorial'];
}

/** Published, listed posts (unlisted posts stay out of the AI-crawler summary). */
function rodolfo_llms_posts( $limit, $category = 0 ) {
    $args = array(
        'numberposts'  => (int) $limit,
        'post_status'  => 'publish',
        // get_posts() bypasses the unlist module's main-query filter, so exclude explicitly.
        'post__not_in' => function_exists( 'rodolfo_unlisted_ids' ) ? rodolfo_unlisted_ids() : array(),
    );
    if ( $category ) {
        $args['category'] = (int) $category;
    }
    return get_posts( $args );
}

function rodolfo_llms_post_line( $post, $bullet, $excerpt_words ) {
    $line = $bullet . ' [' . wp_strip_all_tags( get_the_title( $post ) ) . '](' . get_permalink( $post ) . ')';
    if ( $excerpt_words > 0 ) {
        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post ) ), $excerpt_words, '…' );
        if ( $excerpt ) {
            $line .= ': ' . $excerpt;
        }
    }
    return $line;
}

/** Build the llms.txt Markdown body for the selected template. */
function rodolfo_llms_txt_body() {
    $template = function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_llms_txt_template' ) : 'editorial';
    $cfg      = rodolfo_llms_template_config( $template );
    $b        = $cfg['bullet'];
    $desc     = get_bloginfo( 'description' );
    $counts   = wp_count_posts();
    $n_posts  = isset( $counts->publish ) ? (int) $counts->publish : 0;
    $cats     = get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => (int) $cfg['cats'], 'hide_empty' => true ) );
    $pages    = function_exists( 'rodolfo_primary_nav_pages' ) ? rodolfo_primary_nav_pages() : array();

    $lines   = array();
    $lines[] = '# ' . get_bloginfo( 'name' );

    if ( $desc ) {
        $lines[] = '';
        $lines[] = '> ' . $desc;
    }
    if ( 'summary' === $cfg['intro'] ) {
        $lines[] = '';
        $lines[] = sprintf(
            '%1$s articles across %2$s topics, published at %3$s.',
            number_format_i18n( $n_posts ),
            number_format_i18n( count( $cats ) ),
            wp_parse_url( home_url(), PHP_URL_HOST )
        );
    } elseif ( 'facts' === $cfg['intro'] ) {
        $lines[] = '';
        $lines[] = '## About';
        $lines[] = '';
        $lines[] = $b . ' Site: ' . home_url( '/' );
        $lines[] = $b . ' Language: ' . get_bloginfo( 'language' );
        $lines[] = $b . ' Articles: ' . $n_posts;
        $lines[] = $b . ' Topics: ' . count( $cats );
        $lines[] = $b . ' Feed: ' . get_bloginfo( 'rss2_url' );
    }

    if ( ! empty( $pages ) ) {
        $lines[] = '';
        $lines[] = '## ' . $cfg['h']['pages'];
        $lines[] = '';
        foreach ( $pages as $page ) {
            $lines[] = $b . ' [' . wp_strip_all_tags( get_the_title( $page->ID ) ) . '](' . get_permalink( $page->ID ) . ')';
        }
    }

    if ( 'directory' === $template ) {
        // Posts grouped under their category, newest first.
        $lines[] = '';
        $lines[] = '## ' . $cfg['h']['posts'];
        foreach ( $cats as $cat ) {
            $in_cat = rodolfo_llms_posts( $cfg['posts'], $cat->term_id );
            if ( empty( $in_cat ) ) {
                continue;
            }
            $lines[] = '';
            $lines[] = '### [' . $cat->name . '](' . get_category_link( $cat->term_id ) . ')';
            $lines[] = '';
            foreach ( $in_cat as $post ) {
                $lines[] = rodolfo_llms_post_line( $post, $b, 0 );
            }
        }
    } else {
        $posts = rodolfo_llms_posts( $cfg['posts'] );
        if ( ! empty( $posts ) ) {
            $lines[] = '';
            $lines[] = '## ' . $cfg['h']['posts'];
            $lines[] = '';
            foreach ( $posts as $post ) {
                $lines[] = rodolfo_llms_post_line( $post, $b, (int) $cfg['excerpt'] );
            }
        }
        if ( ! empty( $cats ) ) {
            $lines[] = '';
            $lines[] = '## ' . $cfg['h']['topics'];
            $lines[] = '';
            foreach ( $cats as $cat ) {
                $suffix = 'structured' === $template ? ' (' . (int) $cat->count . ')' : '';
                $lines[] = $b . ' [' . $cat->name . '](' . get_category_link( $cat->term_id ) . ')' . $suffix;
            }
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
 * robots.txt additions (virtual file only; a physical robots.txt is never
 * touched):
 *   - internal search is a crawl trap, so /search/ and ?s= are disallowed for
 *     every crawler (Google's SEO Starter Guide);
 *   - the major AI crawlers are explicitly allowed. Sites that do not want AI
 *     crawler traffic can disable that part with:
 *       add_filter( 'rodolfo_ai_crawlers_welcome', '__return_false' );
 * No comments or theme names are written, so installs share no fixed signature.
 */
function rodolfo_ai_robots( $output ) {
    $traps = "Disallow: /search/\nDisallow: /?s=\nDisallow: /*?s=\n";

    // Insert the traps into the generic group.
    if ( preg_match( '/^User-agent:\s*\*\s*$/mi', $output ) ) {
        $output = preg_replace( '/^(User-agent:\s*\*\s*)$/mi', "$1\n" . rtrim( $traps ), $output, 1 );
    } else {
        $output = rtrim( $output ) . "\n\nUser-agent: *\n" . $traps;
    }

    if ( apply_filters( 'rodolfo_ai_crawlers_welcome', true ) ) {
        $bots   = array( 'GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'Claude-SearchBot', 'PerplexityBot', 'Google-Extended', 'CCBot' );
        $output = rtrim( $output ) . "\n";
        foreach ( $bots as $bot ) {
            // A named group replaces the generic one, so the traps repeat here.
            $output .= "\nUser-agent: $bot\nAllow: /\n" . $traps;
        }
    }
    return $output;
}
add_filter( 'robots_txt', 'rodolfo_ai_robots' );

/** Never canonical-redirect /llms.txt to /llms.txt/. */
function rodolfo_llms_no_redirect( $redirect, $requested ) {
    if ( get_query_var( 'rodolfo_llms' ) ) {
        return false;
    }
    return $redirect;
}
add_filter( 'redirect_canonical', 'rodolfo_llms_no_redirect', 10, 2 );
