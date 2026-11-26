<?php
/**
 * SEO head: titles, meta descriptions, canonical, robots, Open Graph, Twitter Card.
 *
 * Self-disabling: if a general SEO plugin is active it already emits these
 * tags, so this module stands down to avoid duplicates. The JSON-LD
 * (inc/schema.php) follows the same rule.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Detect a general SEO plugin that owns head meta output.
 * Shared by this module and inc/schema.php.
 */
function rodolfo_seo_plugin_active() {
    $active = defined( 'WPSEO_VERSION' )          // Yoast SEO
        || defined( 'RANK_MATH_VERSION' )         // Rank Math
        || defined( 'AIOSEO_VERSION' )            // All in One SEO
        || defined( 'SEOPRESS_VERSION' )          // SEOPress
        || class_exists( 'The_SEO_Framework\Load' ); // The SEO Framework
    /**
     * Allow forcing Rodolfo's meta/schema on or off regardless of detection.
     * Return true to suppress Rodolfo output.
     */
    return (bool) apply_filters( 'rodolfo_seo_plugin_active', $active );
}

/**
 * Name the detected SEO plugin, for the System Info report. Lives beside the
 * detection above so the two constant lists cannot drift apart.
 */
function rodolfo_detected_seo_plugin() {
    if ( defined( 'WPSEO_VERSION' ) ) {
        return 'Yoast SEO';
    }
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return 'Rank Math';
    }
    if ( defined( 'AIOSEO_VERSION' ) ) {
        return 'All in One SEO';
    }
    if ( defined( 'SEOPRESS_VERSION' ) ) {
        return 'SEOPress';
    }
    if ( class_exists( 'The_SEO_Framework\Load' ) ) {
        return 'The SEO Framework';
    }
    return __( 'an SEO plugin (via filter)', 'rodolfo' );
}

/**
 * Trim text to a sentence boundary inside [min, max] characters. Falls back to
 * the last word boundary plus an ellipsis, never cutting mid-word.
 */
function rodolfo_trim_sentence( $text, $min = 120, $max = 155 ) {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
    $len  = mb_strlen( $text );
    if ( $len <= $max ) {
        return $text;
    }
    $window = mb_substr( $text, 0, $max + 1 );
    // Last sentence terminator that leaves at least $min characters.
    if ( preg_match_all( '/[.!?](?=\s|$)/u', $window, $m, PREG_OFFSET_CAPTURE ) ) {
        foreach ( array_reverse( $m[0] ) as $hit ) {
            $pos = mb_strlen( substr( $window, 0, $hit[1] ) ) + 1;
            if ( $pos >= $min && $pos <= $max ) {
                return rtrim( mb_substr( $text, 0, $pos ) );
            }
        }
    }
    $cut = mb_substr( $text, 0, $max - 1 );
    $sp  = mb_strrpos( $cut, ' ' );
    if ( false !== $sp && $sp >= $min - 20 ) {
        $cut = mb_substr( $cut, 0, $sp );
    }
    return rtrim( $cut, " \t,;:-–—" ) . '…';
}

/** Titles of the posts on the current archive page, for a unique archive description. */
function rodolfo_archive_post_titles( $limit = 4 ) {
    global $wp_query;
    $titles = array();
    foreach ( (array) $wp_query->posts as $p ) {
        $titles[] = wp_strip_all_tags( get_the_title( $p ) );
        if ( count( $titles ) >= $limit ) {
            break;
        }
    }
    return $titles;
}

/**
 * A unique 120–155 character description for the current view. Order of
 * preference: per-post override (_theme_meta_description), excerpt, sentence-
 * trimmed content; for archives the term/author description, else a summary
 * built from the posts actually listed. Never generic boilerplate.
 */
function rodolfo_meta_description() {
    $desc = '';
    if ( is_singular() ) {
        $post = get_queried_object();
        if ( $post ) {
            $desc = (string) get_post_meta( $post->ID, '_theme_meta_description', true );
            if ( '' === trim( $desc ) && ! empty( $post->post_excerpt ) ) {
                $desc = $post->post_excerpt;
            }
            if ( '' === trim( $desc ) ) {
                $desc = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
            }
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        $desc = $term instanceof WP_Term ? wp_strip_all_tags( term_description( $term ) ) : '';
        if ( mb_strlen( trim( $desc ) ) < 60 && $term instanceof WP_Term ) {
            $titles = rodolfo_archive_post_titles();
            $desc   = trim( $desc . ' ' ) . sprintf(
                /* translators: 1: topic name, 2: list of article titles */
                __( '%1$s on %2$s: %3$s.', 'rodolfo' ),
                $term->name,
                get_bloginfo( 'name' ),
                implode( '; ', $titles )
            );
        }
    } elseif ( is_author() ) {
        $author = get_queried_object();
        $desc   = $author instanceof WP_User ? (string) $author->description : '';
        if ( mb_strlen( trim( $desc ) ) < 60 && $author instanceof WP_User ) {
            $desc = trim( $desc . ' ' ) . sprintf(
                /* translators: 1: author name, 2: list of article titles */
                __( 'Articles by %1$s: %2$s.', 'rodolfo' ),
                $author->display_name,
                implode( '; ', rodolfo_archive_post_titles() )
            );
        }
    } elseif ( is_date() ) {
        $desc = sprintf(
            /* translators: 1: archive title, 2: article titles */
            __( '%1$s: %2$s.', 'rodolfo' ),
            wp_strip_all_tags( get_the_archive_title() ),
            implode( '; ', rodolfo_archive_post_titles() )
        );
    } else {
        $desc = get_bloginfo( 'description' );
        $paged = (int) get_query_var( 'paged' );
        if ( $paged > 1 ) {
            $desc = trim( $desc . ' ' ) . implode( '; ', rodolfo_archive_post_titles( 3 ) ) . '.';
        }
    }
    return rodolfo_trim_sentence( $desc );
}

/** Share image for the current view; the generated card only when its font exists. */
function rodolfo_meta_image() {
    if ( is_singular() && has_post_thumbnail() ) {
        $src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
        if ( $src ) {
            return $src[0];
        }
    }
    if ( is_singular( 'post' ) && function_exists( 'rodolfo_social_card_url' ) && function_exists( 'rodolfo_social_card_available' ) && rodolfo_social_card_available() ) {
        return rodolfo_social_card_url( get_queried_object_id() );
    }
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $src = wp_get_attachment_image_src( $logo_id, 'full' );
        if ( $src ) {
            return $src[0];
        }
    }
    $site_icon = get_site_icon_url( 512 );
    return $site_icon ? $site_icon : get_template_directory_uri() . '/assets/icons/site-icon-512.png';
}

/** Is the current view thin / empty enough to keep out of the index? */
function rodolfo_is_thin_view() {
    if ( is_search() || is_404() || is_date() ) {
        return true;
    }
    if ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        $min  = (int) apply_filters( 'rodolfo_thin_archive_threshold', 2 );
        if ( ! have_posts() ) {
            return true;
        }
        return $term instanceof WP_Term && (int) $term->count < $min;
    }
    if ( is_author() ) {
        return ! have_posts();
    }
    return false;
}

/** Titles: unique across paginated archives ("Page X of Y"). */
function rodolfo_document_title_parts( $parts ) {
    if ( rodolfo_seo_plugin_active() ) {
        return $parts;
    }
    $paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
    global $wp_query;
    $max = (int) $wp_query->max_num_pages;
    if ( $paged > 1 ) {
        $parts['page'] = $max > 1
            ? sprintf( /* translators: 1: current page, 2: total pages */ __( 'Page %1$d of %2$d', 'rodolfo' ), $paged, $max )
            : sprintf( /* translators: %d: page number */ __( 'Page %d', 'rodolfo' ), $paged );
    }
    if ( is_404() ) {
        $parts['title'] = __( 'Page not found', 'rodolfo' );
    }
    return $parts;
}
add_filter( 'document_title_parts', 'rodolfo_document_title_parts' );

/** Robots: "noindex, follow" on search, 404, date, empty and thin archives. */
function rodolfo_robots( $robots ) {
    if ( rodolfo_seo_plugin_active() || ! rodolfo_is_thin_view() ) {
        return $robots;
    }
    unset( $robots['index'], $robots['max-image-preview'] );
    $robots['noindex'] = true;
    $robots['follow']  = true;
    return $robots;
}
add_filter( 'wp_robots', 'rodolfo_robots', 20 );

/** Self-referencing canonical on every indexable URL (replaces core's singular-only tag). */
function rodolfo_canonical_url() {
    if ( is_singular() ) {
        $url = get_permalink( get_queried_object_id() );
        $pg  = (int) get_query_var( 'page' );
        if ( $pg > 1 ) {
            $url = trailingslashit( $url ) . user_trailingslashit( $pg, 'single_paged' );
        }
        return $url;
    }
    if ( is_front_page() && ! is_paged() ) {
        return home_url( '/' );
    }
    if ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        $url  = $term instanceof WP_Term ? get_term_link( $term ) : '';
        $pg   = (int) get_query_var( 'paged' );
        return ( is_string( $url ) && $url ) ? ( $pg > 1 ? get_pagenum_link( $pg ) : $url ) : '';
    }
    if ( is_author() ) {
        $pg = (int) get_query_var( 'paged' );
        return $pg > 1 ? get_pagenum_link( $pg ) : get_author_posts_url( get_queried_object_id() );
    }
    $pg = (int) get_query_var( 'paged' );
    if ( is_home() || is_archive() ) {
        return $pg > 1 ? get_pagenum_link( $pg ) : rodolfo_current_url();
    }
    return '';
}

function rodolfo_meta_tags() {
    if ( rodolfo_seo_plugin_active() ) {
        return;
    }
    $thin = rodolfo_is_thin_view();

    if ( ! $thin && ! is_404() ) {
        $canonical = function_exists( 'rodolfo_canonical_url' ) ? rodolfo_canonical_url() : '';
        if ( $canonical ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }
    }
    if ( is_search() || is_404() ) {
        return; // Nothing else worth emitting.
    }

    $desc  = rodolfo_meta_description();
    $image = rodolfo_meta_image();
    $url   = ! empty( $canonical ) ? $canonical : rodolfo_current_url();
    $title = wp_get_document_title();
    $type  = is_singular( 'post' ) ? 'article' : 'website';

    $tags = array();
    if ( $desc ) {
        $tags[] = array( 'name', 'description', $desc );
    }
    $tags[] = array( 'property', 'og:site_name', get_bloginfo( 'name' ) );
    $tags[] = array( 'property', 'og:locale', str_replace( '-', '_', get_bloginfo( 'language' ) ) );
    $tags[] = array( 'property', 'og:type', $type );
    $tags[] = array( 'property', 'og:title', $title );
    if ( $desc ) {
        $tags[] = array( 'property', 'og:description', $desc );
    }
    $tags[] = array( 'property', 'og:url', $url );
    if ( $image ) {
        $tags[] = array( 'property', 'og:image', $image );
    }
    $tags[] = array( 'name', 'twitter:card', $image ? 'summary_large_image' : 'summary' );
    $tags[] = array( 'name', 'twitter:title', $title );
    if ( $desc ) {
        $tags[] = array( 'name', 'twitter:description', $desc );
    }
    if ( $image ) {
        $tags[] = array( 'name', 'twitter:image', $image );
    }
    if ( is_singular( 'post' ) ) {
        $tags[] = array( 'property', 'article:published_time', get_the_date( 'c' ) );
        $tags[] = array( 'property', 'article:modified_time', get_the_modified_date( 'c' ) );
    }

    foreach ( $tags as $t ) {
        list( $attr, $key, $val ) = $t;
        printf( "<meta %s=\"%s\" content=\"%s\" />\n", esc_attr( $attr ), esc_attr( $key ), esc_attr( $val ) );
    }
}
add_action( 'wp_head', 'rodolfo_meta_tags', 5 );

// Core prints its own singular-only canonical; ours covers every indexable view.
add_action( 'init', function () {
    if ( ! rodolfo_seo_plugin_active() ) {
        remove_action( 'wp_head', 'rel_canonical' );
    }
} );
