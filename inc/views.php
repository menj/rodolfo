<?php
/**
 * Post view counts.
 *
 * A minimal, self-hosted view counter — no external service, no cookies, no
 * fingerprinting. One integer per post, in post meta. Feeds two things:
 *
 *   - a "most read" query (rodolfo_popular_posts) that inc/related-posts.php
 *     can prefer over taxonomy matches when a post has no clear signal
 *   - an optional "Popular this week" list surface via the same helper
 *
 * Design deliberately narrow:
 *   - Off by default. Counting page loads is a small privacy footprint but
 *     still a footprint; opting in should be a decision.
 *   - Bots and logged-in editors don't count, so backfills and admin browsing
 *     don't skew the numbers.
 *   - A per-visitor once-per-hour throttle via a short transient prevents F5
 *     inflation without needing sessions or cookies.
 *   - Filterable throttle window and counting eligibility.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const RODOLFO_VIEWS_META = '_rodolfo_views';
const RODOLFO_VIEWS_DAILY_META = '_rodolfo_views_daily';

/** Should this request count? All guards in one place so they're easy to audit. */
function rodolfo_views_should_count() {
    if ( ! is_singular( 'post' ) )                     { return false; }
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return false; }
    if ( is_preview() || is_customize_preview() )      { return false; }
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) { return false; }

    // Basic bot filter. wp_is_serving_search_engine() would be nicer but is 6.4+;
    // this covers the common cases and is filterable.
    $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    if ( '' === $ua || preg_match( '/bot|crawler|spider|slurp|facebookexternalhit|preview|python-requests|curl|wget|headless/i', $ua ) ) {
        return false;
    }

    /** Filter whether the current request counts as a view. */
    return (bool) apply_filters( 'rodolfo_views_should_count', true, get_queried_object() );
}

/** Record a view for the current post, with a per-visitor throttle. */
function rodolfo_views_record() {
    if ( ! get_option( 'rodolfo_views_enabled' ) || ! rodolfo_views_should_count() ) {
        return;
    }
    $post_id = (int) get_queried_object_id();
    if ( ! $post_id ) {
        return;
    }

    // Per-visitor throttle. IP + UA is not a privacy identifier, it's an
    // opaque hash used only to prevent one visitor inflating a count in the
    // same window. Never stored — just the transient key.
    $ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
    $window = (int) apply_filters( 'rodolfo_views_throttle', HOUR_IN_SECONDS );
    $key    = 'rodolfo_v_' . substr( md5( $ip . '|' . $ua . '|' . $post_id ), 0, 20 );
    if ( $window > 0 && get_transient( $key ) ) {
        return;
    }
    if ( $window > 0 ) {
        set_transient( $key, 1, $window );
    }

    $count = (int) get_post_meta( $post_id, RODOLFO_VIEWS_META, true );
    update_post_meta( $post_id, RODOLFO_VIEWS_META, $count + 1 );

    // Rolling daily counts alongside the all-time total: one small
    // date-keyed array in a second meta key, pruned on write — so
    // "trending today" needs no cron, no midnight reset, no cleanup job.
    $daily = get_post_meta( $post_id, RODOLFO_VIEWS_DAILY_META, true );
    $daily = is_array( $daily ) ? $daily : array();
    $today = wp_date( 'Y-m-d' );
    $daily[ $today ] = isset( $daily[ $today ] ) ? (int) $daily[ $today ] + 1 : 1;
    $floor = wp_date( 'Y-m-d', time() - 7 * DAY_IN_SECONDS );
    foreach ( array_keys( $daily ) as $day ) {
        if ( $day < $floor ) {
            unset( $daily[ $day ] );
        }
    }
    update_post_meta( $post_id, RODOLFO_VIEWS_DAILY_META, $daily );
}
add_action( 'wp', 'rodolfo_views_record' );

/**
 * Views in the last N days (1–7; the rolling window keeps a week).
 *
 * @param int|WP_Post|null $post Post.
 * @param int              $days Window size in days, capped at 7.
 * @return int
 */
function rodolfo_views_window( $post = null, $days = 1 ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return 0;
    }
    $days  = max( 1, min( 7, (int) $days ) );
    $daily = get_post_meta( $post->ID, RODOLFO_VIEWS_DAILY_META, true );
    if ( ! is_array( $daily ) || empty( $daily ) ) {
        return 0;
    }
    $floor = wp_date( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
    $sum   = 0;
    foreach ( $daily as $day => $n ) {
        if ( $day >= $floor ) {
            $sum += (int) $n;
        }
    }
    return $sum;
}

/** Read a post's view count. Returns 0 for posts never viewed. */
function rodolfo_views_get( $post = null ) {
    $post = get_post( $post );
    return $post ? (int) get_post_meta( $post->ID, RODOLFO_VIEWS_META, true ) : 0;
}

/**
 * Human-readable count for display, e.g. "1.2k views". Empty string when the
 * count is zero or the feature is off — so themes can call this unconditionally.
 */
function rodolfo_views_text( $post = null ) {
    if ( ! get_option( 'rodolfo_views_enabled' ) ) {
        return '';
    }
    $n = rodolfo_views_get( $post );
    if ( $n < 1 ) {
        return '';
    }
    if ( $n >= 1000 ) {
        $short = ( $n >= 10000 ) ? (string) intval( $n / 1000 ) . 'k' : number_format( $n / 1000, 1 ) . 'k';
    } else {
        $short = number_format_i18n( $n );
    }
    /* translators: %s: view count (already formatted) */
    return sprintf( _n( '%s view', '%s views', $n, 'rodolfo' ), $short );
}

/**
 * Popular posts, ordered by view count, optionally scoped to a time window.
 * Cached for an hour and per-scope so a "Popular this week" surface is cheap.
 *
 * @param int    $count      Max posts to return.
 * @param string $scope      'all' (default) or 'week' / 'month'.
 * @param int    $exclude_id Post ID to skip (e.g. the current post).
 * @return WP_Post[]
 */
function rodolfo_popular_posts( $count = 5, $scope = 'all', $exclude_id = 0, $window = 0 ) {
    $count  = max( 1, (int) $count );
    $scope  = in_array( $scope, array( 'week', 'month', 'all' ), true ) ? $scope : 'all';
    $window = max( 0, min( 7, (int) $window ) );

    // $window > 0 ranks by views RECEIVED in the last N days ("trending"),
    // distinct from $scope, which restricts how recently posts were
    // PUBLISHED. The two compose: scope narrows candidates, window ranks.
    if ( $window > 0 ) {
        return rodolfo_trending_posts( $count, $window, $exclude_id );
    }
    $cache  = 'rodolfo_popular_' . $scope . '_' . $count . '_' . (int) $exclude_id;
    $cached = get_transient( $cache );
    if ( false !== $cached ) {
        return $cached;
    }

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'meta_key'       => RODOLFO_VIEWS_META,
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => array( array(
            'key'     => RODOLFO_VIEWS_META,
            'value'   => 0,
            'compare' => '>',
            'type'    => 'NUMERIC',
        ) ),
    );
    if ( 'week' === $scope ) {
        $args['date_query'] = array( array( 'after' => '1 week ago' ) );
    } elseif ( 'month' === $scope ) {
        $args['date_query'] = array( array( 'after' => '1 month ago' ) );
    }
    if ( $exclude_id ) {
        $args['post__not_in'] = array( (int) $exclude_id );
    }

    $posts = get_posts( $args );
    set_transient( $cache, $posts, HOUR_IN_SECONDS );
    return $posts;
}

/**
 * Posts ranked by views received in the last N days.
 *
 * Rolling-window sums live in serialized post meta, so ordering happens in
 * PHP over a bounded candidate set rather than in SQL — cheap at blog scale
 * and cached for an hour like the all-time ranking.
 *
 * @param int $count      Max posts to return.
 * @param int $window     Window size in days (1–7).
 * @param int $exclude_id Post ID to skip.
 * @return WP_Post[]
 */
function rodolfo_trending_posts( $count = 5, $window = 1, $exclude_id = 0 ) {
    $count  = max( 1, (int) $count );
    $window = max( 1, min( 7, (int) $window ) );
    $cache  = 'rodolfo_trending_' . $window . '_' . $count . '_' . (int) $exclude_id;
    $cached = get_transient( $cache );
    if ( false !== $cached ) {
        return $cached;
    }

    $candidates = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'fields'         => 'ids',
        'meta_query'     => array( array(
            'key'     => RODOLFO_VIEWS_DAILY_META,
            'compare' => 'EXISTS',
        ) ),
        'post__not_in'   => $exclude_id ? array( (int) $exclude_id ) : array(),
    ) );

    $scored = array();
    foreach ( $candidates as $id ) {
        $views = rodolfo_views_window( $id, $window );
        if ( $views > 0 ) {
            $scored[ $id ] = $views;
        }
    }
    arsort( $scored, SORT_NUMERIC );
    $ids   = array_slice( array_keys( $scored ), 0, $count );
    $posts = $ids ? array_filter( array_map( 'get_post', $ids ) ) : array();

    set_transient( $cache, $posts, HOUR_IN_SECONDS );
    return $posts;
}

/** Flush the popular-posts cache when a post is saved or deleted. */
function rodolfo_popular_flush() {
    global $wpdb;
    // Cheap: transient names all share a prefix.
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rodolfo_popular_%' OR option_name LIKE '_transient_timeout_rodolfo_popular_%' OR option_name LIKE '_transient_rodolfo_trending_%' OR option_name LIKE '_transient_timeout_rodolfo_trending_%'" );
}
add_action( 'save_post_post', 'rodolfo_popular_flush' );
add_action( 'deleted_post', 'rodolfo_popular_flush' );
