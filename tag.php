<?php
/**
 * Tag archive template.
 *
 * @package Rodolfo
 */

get_header();

$term = get_queried_object();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <div class="mb-16">

        <div class="mb-10 pb-10 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-start gap-5">
                <div class="flex-grow">
                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">
                        <?php esc_html_e( 'Tag', 'rodolfo' ); ?>
                    </p>
                    <h1 class="text-5xl font-bold text-slate-900 dark:text-white mb-3">
                        #<?php single_tag_title(); ?>
                    </h1>
                    <?php if ( tag_description() ) : ?>
                        <p class="text-xl text-slate-600 dark:text-slate-400 mb-4">
                            <?php echo esc_html( wp_strip_all_tags( tag_description() ) ); ?>
                        </p>
                    <?php endif; ?>
                    <span class="inline-block px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-semibold rounded-full text-sm">
                        <?php
                        printf(
                            /* translators: %s: number of articles */
                            esc_html( _n( '%s article', '%s articles', $term->count, 'rodolfo' ) ),
                            esc_html( number_format_i18n( $term->count ) )
                        );
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php get_template_part( 'template-parts/content/content-card' ); ?>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="col-span-full py-12 text-center text-slate-500 dark:text-slate-400">
                    <?php echo rodolfo_icon( 'file-x', 'w-12 h-12 mx-auto mb-4 opacity-50' ); ?>
                    <p class="text-lg"><?php esc_html_e( 'No articles found.', 'rodolfo' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php
    the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => rodolfo_icon( 'chevron-left', 'w-5 h-5' ),
        'next_text' => rodolfo_icon( 'chevron-right', 'w-5 h-5' ),
        'class'     => 'flex justify-center items-center gap-2 mt-12 pagination-links',
    ) );
    ?>

</main>

<?php get_footer(); ?>
