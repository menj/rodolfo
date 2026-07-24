<?php
/**
 * 404 template
 *
 * @package Rodolfo
 */

get_header();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <div class="max-w-2xl mx-auto text-center py-24 md:py-32">

        <div class="text-8xl md:text-9xl font-bold text-slate-100 dark:text-slate-800 select-none leading-none mb-4">
            404
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white mb-4">
            <?php esc_html_e( 'Page not found', 'rodolfo' ); ?>
        </h1>

        <p class="text-slate-500 dark:text-slate-400 text-lg mb-10 leading-relaxed">
            <?php esc_html_e( "The page you're looking for doesn't exist or may have been moved.", 'rodolfo' ); ?>
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
               class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php echo rodolfo_icon( 'arrow-left', 'w-4 h-4' ); ?>
                <?php esc_html_e( 'Back to home', 'rodolfo' ); ?>
            </a>
        </div>

        <div class="border-t border-slate-100 dark:border-slate-800 pt-12">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-5">
                <?php esc_html_e( 'Or search for something', 'rodolfo' ); ?>
            </p>
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center max-w-sm mx-auto gap-2">
                <div class="relative flex-grow">
                    <?php echo rodolfo_icon( 'search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400' ); ?>
                    <input
                        type="search"
                        name="s"
                        value="<?php echo esc_attr( get_search_query() ); ?>"
                        placeholder="<?php esc_attr_e( 'Search articles…', 'rodolfo' ); ?>"
                        class="w-full pl-9 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                    />
                </div>
                <button type="submit"
                        class="flex-shrink-0 h-10 px-4 bg-slate-900 dark:bg-slate-100 hover:bg-slate-700 dark:hover:bg-white text-white dark:text-slate-900 text-sm font-medium rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php esc_html_e( 'Search', 'rodolfo' ); ?>
                </button>
            </form>
        </div>

    </div>
</main>

<?php get_footer(); ?>
