<?php
/**
 * Template part — single post article header.
 *
 * Renders: featured image, meta row (category, date, reading time),
 * post title, author block with Share and Bookmark buttons.
 *
 * Used by: single.php.
 * Called via get_template_part( 'template-parts/content/content-single' ).
 *
 * @package Rodolfo
 */

$categories = get_the_category();
?>
<?php
// Per-post display flags (inc/post-display.php); all-false when the module is off.
$rodolfo_display = function_exists( 'rodolfo_post_display' ) ? rodolfo_post_display() : array( 'hide_title' => false, 'hide_featured' => false, 'full_width' => false );
$rodolfo_width   = $rodolfo_display['full_width'] ? 'max-w-none' : 'max-w-4xl';
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $rodolfo_width . ' mx-auto bg-white dark:bg-slate-900 rounded-xl md:rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden' ); ?>>

    <?php if ( has_post_thumbnail() && ! $rodolfo_display['hide_featured'] ) : ?>
        <div class="w-full aspect-[21/9] md:aspect-[2.5/1] overflow-hidden relative">
            <?php the_post_thumbnail( 'full', array( 'class' => 'w-full h-full object-cover', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); // LCP image — never lazy-load ?>
        </div>
    <?php endif; ?>

    <div class="p-6 md:p-10 lg:p-12">
        <header class="mb-8 md:mb-12">
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <?php if ( ! empty( $categories ) ) : ?>
                    <span class="inline-block px-3 py-1 text-xs font-bold rounded-full" style="<?php echo esc_attr( rodolfo_category_pill_style( $categories[0]->term_id ) ); ?>">
                        <?php echo esc_html( $categories[0]->name ); ?>
                    </span>
                <?php endif; ?>
                <span class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                    <?php echo rodolfo_icon( 'calendar', 'w-4 h-4' ); ?>
                    <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                </span>
                <?php
                // Visible freshness signal (AEO): show the modified date when the
                // post was meaningfully updated (> 1 day after publish). AI answer
                // engines weight and quote visible dates; schema alone isn't cited.
                if ( get_the_modified_time( 'U' ) > get_the_time( 'U' ) + DAY_IN_SECONDS ) :
                    ?>
                    <span class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                        <?php
                        printf(
                            /* translators: %s: last updated date */
                            esc_html__( 'Updated %s', 'rodolfo' ),
                            '<time datetime="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . esc_html( get_the_modified_date() ) . '</time>'
                        );
                        ?>
                    </span>
                <?php endif; ?>
                <?php
                if ( get_option( 'rodolfo_views_show_meta' ) && function_exists( 'rodolfo_views_text' ) ) :
                    $rodolfo_vt = rodolfo_views_text();
                    if ( $rodolfo_vt ) :
                        ?>
                        <span class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                            <?php echo rodolfo_icon( 'eye', 'w-4 h-4' ); ?>
                            <?php echo esc_html( $rodolfo_vt ); ?>
                        </span>
                        <?php
                    endif;
                endif;
                ?>
                <?php $rodolfo_rt = rodolfo_get_reading_time(); ?>
                <?php if ( $rodolfo_rt ) : ?>
                    <span class="text-sm text-slate-500 font-medium flex items-center gap-1.5">
                        <?php echo rodolfo_icon( 'clock', 'w-4 h-4' ); ?>
                        <?php echo $rodolfo_rt; // Escaped in rodolfo_get_reading_time(). ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ( ! $rodolfo_display['hide_title'] ) : ?>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-display font-bold text-slate-900 dark:text-white mb-6 leading-tight">
                <?php the_title(); ?>
            </h1>
            <?php else : ?>
            <h1 class="screen-reader-text"><?php the_title(); ?></h1><?php // Hidden visually, kept for the accessibility tree and document outline. ?>
            <?php endif; ?>

            <div class="flex items-center gap-4 pt-6 border-t border-slate-100 dark:border-slate-800">
                <?php echo get_avatar( get_the_author_meta( 'user_email' ), 48, '', '', array( 'class' => 'w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800' ) ); ?>
                <div>
                    <div class="font-semibold text-slate-900 dark:text-white"><?php the_author(); ?></div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        <?php echo esc_html( get_the_author_meta( 'description' ) ?: __( 'Author', 'rodolfo' ) ); ?>
                    </div>
                </div>

                <div class="ml-auto flex gap-2">
                    <button
                        id="share-btn"
                        data-title="<?php echo esc_attr( get_the_title() ); ?>"
                        data-url="<?php echo esc_url( get_permalink() ); ?>"
                        class="w-10 h-10 rounded-full bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="<?php esc_attr_e( 'Share', 'rodolfo' ); ?>">
                        <?php echo rodolfo_icon( 'share-2', 'w-5 h-5' ); ?>
                    </button>
                    <button
                        id="bookmark-btn"
                        data-post-id="<?php echo esc_attr( get_the_ID() ); ?>"
                        class="w-10 h-10 rounded-full bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="<?php esc_attr_e( 'Bookmark', 'rodolfo' ); ?>"
                        aria-pressed="false">
                        <?php echo rodolfo_icon( 'bookmark', 'w-5 h-5' ); ?>
                    </button>
                </div>
            </div>
        </header>

        <?php rodolfo_the_toc(); ?>

        <div class="prose prose-slate dark:prose-invert prose-lg md:prose-xl max-w-none
            prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white
            prose-p:text-slate-700 dark:prose-p:text-slate-300 prose-p:leading-relaxed
            prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline
            prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:bg-slate-50 dark:prose-blockquote:bg-slate-800/50 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-lg prose-blockquote:not-italic
            prose-img:rounded-xl">
            <?php the_content(); ?>
        </div>

        <?php get_template_part( 'template-parts/content/series-nav' ); ?>

        <?php
        wp_link_pages( array(
            'before'      => '<nav class="page-links mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2" aria-label="' . esc_attr__( 'Post pages', 'rodolfo' ) . '"><span class="text-sm font-medium text-slate-500 dark:text-slate-400">' . esc_html__( 'Pages:', 'rodolfo' ) . '</span>',
            'after'       => '</nav>',
            'link_before' => '<span class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-md text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">',
            'link_after'  => '</span>',
        ) );
        ?>

        <?php
        $tags = get_the_tags();
        if ( $tags ) :
        ?>
            <div class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                    <?php echo rodolfo_icon( 'tag', 'w-5 h-5 text-slate-400' ); ?>
                    <?php foreach ( $tags as $tag ) : ?>
                        <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>"
                           class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-medium rounded-full transition-colors">
                            <?php echo esc_html( $tag->name ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</article>
