<?php
/**
 * Search results template
 *
 * @package Rodolfo
 */

get_header();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <div class="mb-16">

        <div class="mb-10">
            <?php if ( have_posts() ) : ?>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    <?php
                    printf(
                        /* translators: %s: search query */
                        esc_html__( 'Results for: "%s"', 'rodolfo' ),
                        '<span class="text-blue-600 dark:text-blue-400">' . esc_html( get_search_query() ) . '</span>'
                    );
                    ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm">
                    <?php
                    printf(
                        /* translators: %d: number of results */
                        esc_html( _n( '%d result found', '%d results found', $wp_query->found_posts, 'rodolfo' ) ),
                        number_format_i18n( $wp_query->found_posts )
                    );
                    ?>
                </p>
            <?php else : ?>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    <?php
                    printf(
                        /* translators: %s: search query */
                        esc_html__( 'No results for: "%s"', 'rodolfo' ),
                        esc_html( get_search_query() )
                    );
                    ?>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mb-8">
                    <?php esc_html_e( 'Try different keywords or browse the latest articles below.', 'rodolfo' ); ?>
                </p>
                <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center max-w-sm gap-2">
                    <div class="relative flex-grow">
                        <?php echo rodolfo_icon( 'search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400' ); ?>
                        <input
                            type="search"
                            name="s"
                            value="<?php echo esc_attr( get_search_query() ); ?>"
                            placeholder="<?php esc_attr_e( 'Try again…', 'rodolfo' ); ?>"
                            class="w-full pl-9 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                        />
                    </div>
                    <button type="submit"
                            class="flex-shrink-0 h-10 px-4 bg-slate-900 dark:bg-slate-100 hover:bg-slate-700 dark:hover:bg-white text-white dark:text-slate-900 text-sm font-medium rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php esc_html_e( 'Search', 'rodolfo' ); ?>
                    </button>
                </form>
            <?php endif; ?>
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
