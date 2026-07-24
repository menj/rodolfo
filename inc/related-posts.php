<?php
/**
 * Related posts.
 *
 * Surfaces up to N posts related to the current one, preferring shared tags
 * (a stronger topical signal) and falling back to shared categories. Returns
 * WP_Post objects; rendering is done by rodolfo_render_related_posts(), which
 * reuses the post-card visual language.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get related posts for a given post ID.
 *
 * @param int $post_id Post to relate to.
 * @param int $limit   Max posts to return.
 * @return WP_Post[]
 */
function rodolfo_get_related_posts( $post_id, $limit = 3 ) {
    $limit = (int) $limit;

    $tag_ids = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
    $cat_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

    $base = array(
        'post__not_in'        => array( (int) $post_id ),
        'posts_per_page'      => $limit,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );

    $found = array();

    // 0) Manual override: an editor can pin related posts via the
    //    _rodolfo_related_ids meta (comma-separated post IDs, in order).
    //    Pinned posts come first; the algorithm tops up the remainder.
    $pinned_raw = (string) get_post_meta( $post_id, '_rodolfo_related_ids', true );
    if ( '' !== trim( $pinned_raw ) ) {
        foreach ( array_map( 'absint', explode( ',', $pinned_raw ) ) as $pin ) {
            $pin_post = $pin ? get_post( $pin ) : null;
            if ( $pin_post && 'publish' === $pin_post->post_status && (int) $post_id !== $pin_post->ID ) {
                $found[ $pin_post->ID ] = $pin_post;
            }
        }
        $found = array_slice( array_values( $found ), 0, $limit );
        if ( count( $found ) >= $limit ) {
            return $found;
        }
    }

    // 1) Shared tags, ranked by HOW MANY tags overlap — three shared tags is
    //    a far stronger signal than one. A wider candidate pool is fetched
    //    and scored in PHP (overlap count desc, then recency), because SQL
    //    can't order by tag-intersection size without a custom join.
    if ( ! empty( $tag_ids ) ) {
        $exclude = array_merge( array( (int) $post_id ), wp_list_pluck( $found, 'ID' ) );
        $q = new WP_Query( $base + array(
            'tag__in'        => $tag_ids,
            'post__not_in'   => $exclude,
            'posts_per_page' => max( $limit * 4, 12 ),
        ) );
        $scored = array();
        foreach ( $q->posts as $candidate ) {
            $overlap = count( array_intersect(
                $tag_ids,
                wp_get_post_tags( $candidate->ID, array( 'fields' => 'ids' ) )
            ) );
            $scored[] = array( $overlap, strtotime( $candidate->post_date_gmt ), $candidate );
        }
        usort( $scored, function ( $a, $b ) {
            return ( $b[0] <=> $a[0] ) ?: ( $b[1] <=> $a[1] );
        } );
        foreach ( array_slice( $scored, 0, $limit - count( $found ) ) as $row ) {
            $found[] = $row[2];
        }
    }

    // 2) Top up from shared categories, without duplicating.
    if ( count( $found ) < $limit && ! empty( $cat_ids ) ) {
        $exclude = array_merge( array( (int) $post_id ), wp_list_pluck( $found, 'ID' ) );
        $q = new WP_Query( $base + array(
            'category__in' => $cat_ids,
            'post__not_in' => $exclude,
            'posts_per_page' => $limit - count( $found ),
        ) );
        $found = array_merge( $found, $q->posts );
    }

    return array_slice( $found, 0, $limit );
}

/**
 * Render the related-posts section for the current post. Outputs nothing when
 * there are no related posts.
 */
function rodolfo_render_related_posts() {
    $related = rodolfo_get_related_posts( get_the_ID(), 3 );
    if ( empty( $related ) ) {
        return;
    }
    ?>
    <section class="max-w-4xl mx-auto mt-12 md:mt-16" aria-label="<?php esc_attr_e( 'Related articles', 'rodolfo' ); ?>">
        <h2 class="text-2xl font-bold font-display text-slate-900 dark:text-white mb-8"><?php esc_html_e( 'Related articles', 'rodolfo' ); ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <?php foreach ( $related as $rel ) :
                $cats  = get_the_category( $rel->ID );
                $accent = ! empty( $cats ) ? rodolfo_category_color( $cats[0]->term_id ) : '';
                ?>
                <a href="<?php echo esc_url( get_permalink( $rel->ID ) ); ?>" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded-lg">
                    <div class="overflow-hidden rounded-lg aspect-[16/10] bg-slate-100 dark:bg-slate-800 mb-4 relative">
                        <?php if ( $accent ) : ?>
                            <span class="absolute left-0 top-0 h-1 w-full z-10" style="background-color:<?php echo esc_attr( $accent ); ?>" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php if ( has_post_thumbnail( $rel->ID ) ) : ?>
                            <?php echo get_the_post_thumbnail( $rel->ID, 'medium_large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
                        <?php else : ?>
                            <?php rodolfo_post_thumbnail( $rel->ID, 'medium_large', 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ); ?>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $cats ) ) : ?>
                        <span class="inline-block px-2.5 py-0.5 text-[11px] font-bold rounded-full mb-2" style="<?php echo esc_attr( rodolfo_category_pill_style( $cats[0]->term_id ) ); ?>">
                            <?php echo esc_html( $cats[0]->name ); ?>
                        </span>
                    <?php endif; ?>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white leading-snug group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors line-clamp-2">
                        <?php echo esc_html( get_the_title( $rel->ID ) ); ?>
                    </h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}
