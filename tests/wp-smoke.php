<?php
/**
 * WordPress runtime checks. Run only via WP-CLI in a disposable CI install.
 * Never run this fixture-creating script against production.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'RODOLFO_CI_SMOKE' ) ) {
    exit( 1 );
}
function rd_wp_check( $label, $ok ) {
    if ( ! $ok ) {
        WP_CLI::error( $label );
    }
    WP_CLI::log( 'PASS: ' . $label );
}
function rd_wp_post( $slug, $body, $extra = array() ) {
    $id = wp_insert_post( array_merge( array(
        'post_name' => $slug,
        'post_title' => $slug,
        'post_content' => $body,
        'post_status' => 'publish',
        'post_type' => 'post',
        'post_author' => 1,
    ), $extra ), true );
    if ( is_wp_error( $id ) || ! $id ) {
        WP_CLI::error( 'Fixture creation failed: ' . $slug );
    }
    return (int) $id;
}
function rd_wp_query( $args, $id ) {
    $GLOBALS['wp_query'] = new WP_Query( $args );
    $GLOBALS['post'] = get_post( $id );
}
rd_wp_check( 'Theme active', 'rodolfo' === get_stylesheet() );
wp_set_current_user( 0 );
update_option( 'blog_public', 1 );
update_option( 'show_avatars', 0 );
update_option( 'rodolfo_feed_full_content', '1' );
update_option( 'rss_use_excerpt', 1 );
update_option( 'posts_per_rss', 50 );
$public = rd_wp_post( 'rd-ci-public', 'RD_CI_PUBLIC_BODY [faq q="What is the test?"]A real WordPress smoke test.[/faq]' );
$protected = rd_wp_post( 'rd-ci-protected', 'RD_CI_PROTECTED_BODY', array( 'post_password' => 'ci-only-password', 'post_excerpt' => 'RD_CI_PROTECTED_EXCERPT' ) );
$unlisted = rd_wp_post( 'rd-ci-unlisted', 'RD_CI_UNLISTED_BODY' );
$related = rd_wp_post( 'rd-ci-related', 'RD_CI_RELATED_BODY' );
foreach ( array( $public, $protected, $unlisted, $related ) as $id ) {
    wp_set_post_tags( $id, 'rd-ci-tag' );
}
update_post_meta( $unlisted, RODOLFO_UNLIST_META, '1' );
update_option( RODOLFO_UNLIST_OPTION, array( $unlisted ) );
update_post_meta( $public, '_rodolfo_related_ids', implode( ',', array( $protected, $unlisted, $related ) ) );

rd_wp_query( array( 'p' => $protected ), $protected );
rd_wp_check( 'Protected post has no native meta description', '' === rodolfo_meta_description() );
rd_wp_check( 'Protected post has no native share image', '' === rodolfo_meta_image() );
ob_start();
rodolfo_schema_output();
rd_wp_check( 'Protected post has no native JSON-LD', '' === ob_get_clean() );
rd_wp_query( array( 'feed' => 'rss2', 'post__in' => array( $protected ) ), $protected );
rd_wp_check( 'Protected RSS preserves core result', 'protected stub' === rodolfo_feed_full_content( 'protected stub' ) );
rd_wp_query( array( 'feed' => 'rss2', 'post__in' => array( $public ) ), $public );
rd_wp_check( 'Public RSS retains full-content feature', false !== strpos( rodolfo_feed_full_content( '' ), 'RD_CI_PUBLIC_BODY' ) );

rd_wp_query( array( 'p' => $public ), $public );
$ids = wp_list_pluck( rodolfo_get_related_posts( $public, 3 ), 'ID' );
rd_wp_check( 'Related posts preserve valid pin', in_array( $related, $ids, true ) );
rd_wp_check( 'Related posts exclude self, protected and unlisted', ! array_intersect( $ids, array( $public, $protected, $unlisted ) ) );
rd_wp_check( 'Related posts have unique IDs', count( $ids ) === count( array_unique( $ids ) ) );
$llms = rodolfo_llms_txt_body();
rd_wp_check( 'llms.txt lists public post', false !== strpos( $llms, 'rd-ci-public' ) );
rd_wp_check( 'llms.txt omits protected and unlisted posts', false === strpos( $llms, 'rd-ci-protected' ) && false === strpos( $llms, 'rd-ci-unlisted' ) );
$robots = "User-agent: *\nDisallow: /private/\n";
rd_wp_check( 'Private robots policy unchanged', $robots === rodolfo_ai_robots( $robots, false ) );
rd_wp_check( 'Public robots policy preserves restrictions', 0 === strpos( rodolfo_ai_robots( $robots, true ), $robots ) && false === strpos( rodolfo_ai_robots( $robots, true ), 'Allow: /' ) );

rd_wp_query( array( 's' => 'rd-ci-public' ), $public );
rd_wp_check( 'Search robots filter emits noindex', ! empty( rodolfo_meta_robots( array() )['noindex'] ) );
rd_wp_check( 'Disabled avatars skip fetch', false === rodolfo_social_card_avatar( 1 ) );

rd_wp_query( array( 'p' => $public ), $public );
$fixture = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+XhXcAAAAASUVORK5CYII=' );
$signature = rodolfo_social_card_cache_signature( get_post( $public ), '', '#3d54d6', 'light' );
// Exercise cache logic with the core object-cache implementation. This does
// not claim to validate Redis/Memcached persistence between HTTP requests.
$previous = wp_using_ext_object_cache( true );
rodolfo_social_card_cache_set( $public, $signature, $fixture );
rd_wp_check( 'Cache returns matching PNG', $fixture === rodolfo_social_card_cache_get( $public, $signature ) );
rd_wp_check( 'Cache rejects stale signature', false === rodolfo_social_card_cache_get( $public, 'stale' ) );
wp_set_current_user( 1 );
rd_wp_check( 'Logged-in requests bypass card cache', ! rodolfo_social_card_cache_enabled() );
wp_set_current_user( 0 );
wp_using_ext_object_cache( $previous );

WP_CLI::success( 'Rodolfo runtime smoke assertions passed.' );
