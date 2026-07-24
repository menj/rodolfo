<?php
/**
 * Estimated reading time.
 *
 * Companion to the reading-progress bar: the bar shows how far through a post
 * you are, this sets the expectation before you start. Derived from word count,
 * so there is nothing to store and nothing to keep in sync.
 *
 * Rate defaults to 220 wpm — a common figure for adult silent reading of
 * general prose. Code blocks are read far more slowly than prose, so their
 * words are weighted rather than counted at the same rate.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Estimated minutes to read a post. Always at least 1 for a non-empty post.
 *
 * @param int|WP_Post|null $post Post to measure. Defaults to the current post.
 * @return int Whole minutes.
 */
function rodolfo_reading_time( $post = null ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return 0;
    }

    /** Filter the words-per-minute rate used for the estimate. */
    $wpm = (int) apply_filters( 'rodolfo_reading_wpm', 220, $post );
    if ( $wpm < 60 ) {
        $wpm = 220; // Guard against a filter returning something unusable.
    }

    $content = (string) $post->post_content;

    // Pull fenced/preformatted code out and weight it separately: scanning code
    // is slower than reading prose, so counting it at the prose rate
    // understates the real time.
    $code_words = 0;
    if ( preg_match_all( '#<pre\b[^>]*>(.*?)</pre>#is', $content, $m ) ) {
        foreach ( $m[1] as $chunk ) {
            $code_words += str_word_count( wp_strip_all_tags( $chunk ) );
        }
        $content = preg_replace( '#<pre\b[^>]*>.*?</pre>#is', ' ', $content );
    }

    $prose_words = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );

    // Code counted at half rate.
    $effective = $prose_words + ( $code_words * 2 );
    $minutes   = (int) ceil( $effective / $wpm );

    if ( $minutes < 1 && ( $prose_words + $code_words ) > 0 ) {
        $minutes = 1;
    }

    /** Filter the final reading-time estimate in minutes. */
    return (int) apply_filters( 'rodolfo_reading_time', $minutes, $post );
}

/**
 * Human-readable reading time, e.g. "6 min read". Empty string for empty posts.
 */
function rodolfo_reading_time_text( $post = null ) {
    $minutes = rodolfo_reading_time( $post );
    if ( $minutes < 1 ) {
        return '';
    }
    /* translators: %s: number of minutes */
    return sprintf( _n( '%s min read', '%s min read', $minutes, 'rodolfo' ), number_format_i18n( $minutes ) );
}

// ============================================================
// POST SERIES
// ============================================================

/**
 * A lightweight `rodolfo_series` taxonomy for multi-part guides.
 *
 * Categories group by subject and tags by topic; neither expresses sequence.
 * A series does: posts belong to one, they have an order, and each one should
 * link to its neighbours. Ordering uses menu_order on the post so it can be set
 * without another meta box, falling back to publication date.
 */
function rodolfo_register_series() {
    register_taxonomy( 'rodolfo_series', array( 'post' ), array(
        'labels'            => array(
            'name'          => __( 'Series', 'rodolfo' ),
            'singular_name' => __( 'Series', 'rodolfo' ),
            'search_items'  => __( 'Search series', 'rodolfo' ),
            'all_items'     => __( 'All series', 'rodolfo' ),
            'edit_item'     => __( 'Edit series', 'rodolfo' ),
            'update_item'   => __( 'Update series', 'rodolfo' ),
            'add_new_item'  => __( 'Add new series', 'rodolfo' ),
            'new_item_name' => __( 'New series name', 'rodolfo' ),
            'menu_name'     => __( 'Series', 'rodolfo' ),
        ),
        'public'            => true,
        'hierarchical'      => false,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'series', 'with_front' => false ),
    ) );
}
add_action( 'init', 'rodolfo_register_series' );

/**
 * The series a post belongs to, or null. A post in several series uses the
 * first — series are meant to be exclusive, and silently picking one is better
 * than rendering contradictory navigation.
 */
function rodolfo_post_series( $post = null ) {
    $post  = get_post( $post );
    if ( ! $post ) {
        return null;
    }
    $terms = get_the_terms( $post, 'rodolfo_series' );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return null;
    }
    return $terms[0];
}

/**
 * All posts in a series, in reading order (menu_order, then oldest first).
 *
 * @return WP_Post[]
 */
function rodolfo_series_posts( $term ) {
    if ( ! $term ) {
        return array();
    }
    $cached = wp_cache_get( 'rodolfo_series_' . $term->term_id, 'rodolfo' );
    if ( is_array( $cached ) ) {
        return $cached;
    }
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
        'tax_query'      => array( array(
            'taxonomy' => 'rodolfo_series',
            'field'    => 'term_id',
            'terms'    => $term->term_id,
        ) ),
    ) );
    wp_cache_set( 'rodolfo_series_' . $term->term_id, $posts, 'rodolfo', HOUR_IN_SECONDS );
    return $posts;
}

/**
 * Position of a post within its series.
 *
 * @return array{term:WP_Term,index:int,total:int,prev:?WP_Post,next:?WP_Post}|null
 */
function rodolfo_series_context( $post = null ) {
    $post = get_post( $post );
    $term = rodolfo_post_series( $post );
    if ( ! $post || ! $term ) {
        return null;
    }
    $posts = rodolfo_series_posts( $term );
    $ids   = wp_list_pluck( $posts, 'ID' );
    $i     = array_search( (int) $post->ID, array_map( 'intval', $ids ), true );
    if ( false === $i ) {
        return null;
    }
    return array(
        'term'  => $term,
        'index' => $i + 1,
        'total' => count( $posts ),
        'prev'  => $i > 0 ? $posts[ $i - 1 ] : null,
        'next'  => isset( $posts[ $i + 1 ] ) ? $posts[ $i + 1 ] : null,
        'posts' => $posts,
    );
}

/** Bust the cached series order whenever a post changes. */
function rodolfo_series_flush( $post_id ) {
    $term = rodolfo_post_series( $post_id );
    if ( $term ) {
        wp_cache_delete( 'rodolfo_series_' . $term->term_id, 'rodolfo' );
    }
}
add_action( 'save_post', 'rodolfo_series_flush' );
add_action( 'deleted_post', 'rodolfo_series_flush' );

// ============================================================
// FEED CONTENT
// ============================================================

/**
 * Optional full-content RSS.
 *
 * WordPress's Settings → Reading offers excerpt-or-full globally; this lets the
 * theme express the indie-web posture it already leans into elsewhere (llms.txt,
 * self-hosted fonts, no third-party CDN) by serving complete posts to feed
 * readers. Off by default — changing what subscribers receive should be a
 * deliberate choice, not a side effect of switching theme.
 */
function rodolfo_feed_full_content( $content ) {
    if ( ! is_feed() || ! get_option( 'rodolfo_feed_full_content' ) ) {
        return $content;
    }
    $post = get_post();
    if ( ! $post ) {
        return $content;
    }
    return apply_filters( 'the_content', $post->post_content );
}
add_filter( 'the_excerpt_rss', 'rodolfo_feed_full_content' );
