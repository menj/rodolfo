<?php
/**
 * SEO meta tags — description, Open Graph, and Twitter Card.
 *
 * Self-disabling: if a general SEO plugin is active it already emits these
 * tags, so this module stands down to avoid duplicates. Rodolfo's JSON-LD
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
 * A clean, ~160-char description for the current view.
 */
function rodolfo_meta_description() {
    $desc = '';
    if ( is_singular() ) {
        $post = get_queried_object();
        if ( $post && ! empty( $post->post_excerpt ) ) {
            $desc = $post->post_excerpt;
        } elseif ( $post ) {
            $desc = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
        }
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $desc = term_description();
    } elseif ( is_author() ) {
        $desc = get_the_author_meta( 'description', get_query_var( 'author' ) );
    } else {
        $desc = get_bloginfo( 'description' );
    }
    $desc = wp_strip_all_tags( (string) $desc );
    $desc = preg_replace( '/\s+/u', ' ', $desc );
    $desc = trim( $desc );
    if ( function_exists( 'mb_strimwidth' ) ) {
        $desc = mb_strimwidth( $desc, 0, 160, '…' );
    } elseif ( strlen( $desc ) > 160 ) {
        $desc = substr( $desc, 0, 157 ) . '…';
    }
    return $desc;
}

/**
 * The best available share image URL for the current view, or ''.
 * Falls back to the generated social card for singular posts (inc/social-card.php).
 */
function rodolfo_meta_image() {
    if ( is_singular() && has_post_thumbnail() ) {
        $src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' );
        if ( $src ) {
            return $src[0];
        }
    }
    if ( is_singular( 'post' ) && function_exists( 'rodolfo_social_card_url' ) ) {
        return rodolfo_social_card_url( get_queried_object_id() );
    }
    // Site-wide fallback: custom logo, else the bundled site-icon PNG.
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $src = wp_get_attachment_image_src( $logo_id, 'full' );
        if ( $src ) {
            return $src[0];
        }
    }
    return get_template_directory_uri() . '/assets/icons/site-icon-512.png';
}

function rodolfo_meta_tags() {
    if ( rodolfo_seo_plugin_active() || is_404() ) {
        return;
    }

    // Keep thin/duplicate-content views out of search indexes. Search results,
    // paged subpages, and author/date archives add no unique indexable value.
    // (Folds in the old "WP SEO Search" plugin behaviour, natively.)
    if ( is_search() || is_paged() || is_author() || is_date() ) {
        echo "\n<meta name=\"robots\" content=\"noindex,follow\" />\n";
        if ( is_search() ) {
            return; // Nothing else worth emitting for a search results page.
        }
    }

    $desc  = rodolfo_meta_description();
    $image = rodolfo_meta_image();
    $url   = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
    $title = wp_get_document_title();
    $type  = is_singular( 'post' ) ? 'article' : 'website';

    $tags = array();
    if ( $desc ) {
        $tags[] = array( 'name', 'description', $desc );
    }
    $tags[] = array( 'property', 'og:site_name', get_bloginfo( 'name' ) );
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

    // Article-specific timestamps.
    if ( is_singular( 'post' ) ) {
        $tags[] = array( 'property', 'article:published_time', get_the_date( 'c' ) );
        $tags[] = array( 'property', 'article:modified_time', get_the_modified_date( 'c' ) );
    }

    echo "\n<!-- Rodolfo SEO meta -->\n";
    foreach ( $tags as $t ) {
        list( $attr, $key, $val ) = $t;
        printf(
            "<meta %s=\"%s\" content=\"%s\" />\n",
            esc_attr( $attr ),
            esc_attr( $key ),
            esc_attr( $val )
        );
    }
}
add_action( 'wp_head', 'rodolfo_meta_tags', 5 );
