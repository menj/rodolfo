<?php
/**
 * Template part — post card.
 *
 * Used by: index.php, archive.php, category.php, tag.php, search.php.
 * Called via get_template_part( 'template-parts/content/content-card' ).
 *
 * All standard template tags are available as this runs inside
 * the calling template's loop after the_post() has been called.
 *
 * @package Rodolfo
 */

$categories = get_the_category();
$post_tags  = get_the_tags();

// Comma-separated “topic” line, matching the original Rodolfo cards: the post's
// tags when present, otherwise its categories. Muted, lowercase, each linked.
$topic_terms = ! empty( $post_tags ) ? $post_tags : $categories;
?>
<div class="rodolfo-card relative flex flex-col h-full group cursor-pointer focus-within:ring-2 focus-within:ring-blue-500 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-3 overflow-hidden">

        <div class="overflow-hidden rounded-lg aspect-[16/10] bg-slate-100 dark:bg-slate-800 mb-4 relative">
            <?php if ( ! empty( $categories ) ) : ?>
                <span class="absolute left-0 top-0 h-1 w-full z-10" style="background-color:<?php echo esc_attr( rodolfo_category_color( $categories[0]->term_id ) ); ?>" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-700' ) ); ?>
            <?php else : ?>
                <?php rodolfo_post_thumbnail( get_the_ID() ); ?>
            <?php endif; ?>
        </div>

        <div class="flex-grow px-1">
            <?php if ( ! empty( $topic_terms ) && ! is_wp_error( $topic_terms ) ) : ?>
                <div class="rodolfo-card-topics relative z-20 mb-2 text-xs text-slate-500 dark:text-slate-400">
                    <?php
                    $topic_links = array();
                    foreach ( array_slice( $topic_terms, 0, 3 ) as $topic_term ) {
                        $topic_links[] = '<a href="' . esc_url( get_term_link( $topic_term ) ) . '" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">' . esc_html( strtolower( $topic_term->name ) ) . '</a>';
                    }
                    echo implode( '<span class="mx-1" aria-hidden="true">,</span>', $topic_links ); // Links escaped above.
                    ?>
                </div>
            <?php endif; ?>

            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight line-clamp-2">
                <a href="<?php the_permalink(); ?>" class="rodolfo-card-link"><?php the_title(); ?></a>
            </h3>

            <div class="text-slate-600 dark:text-slate-300 text-sm line-clamp-2">
                <?php the_excerpt(); ?>
            </div>
        </div>

        <div class="rodolfo-card-meta relative z-20 mt-4 pt-4 px-1 border-t border-slate-100 dark:border-slate-800 flex items-center gap-3">
            <?php echo get_avatar( get_the_author_meta( 'ID' ), 32, '', '', array( 'class' => 'w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex-shrink-0' ) ); ?>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 leading-tight truncate"><?php the_author(); ?></div>
                <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo esc_html( get_the_date() ); ?></div>
            </div>
        </div>

    </div>
