<?php
/**
 * 404 template — strict 404 status, same layout as the rest of the site.
 *
 * @package Rodolfo
 */

// WordPress already sends 404 for is_404(); this keeps the status explicit when
// the template is reached by another route (and stops caches storing it).
status_header( 404 );
nocache_headers();

get_header();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>

    <div class="max-w-2xl mx-auto text-center py-12 md:py-20">

        <div class="text-8xl md:text-9xl font-bold text-slate-100 dark:text-slate-800 select-none leading-none mb-4" aria-hidden="true">404</div>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white mb-4">
            <?php esc_html_e( 'Page not found', 'rodolfo' ); ?>
        </h1>

        <p class="text-slate-500 dark:text-slate-400 text-lg mb-8 leading-relaxed">
            <?php esc_html_e( "The page you're looking for doesn't exist or may have been moved.", 'rodolfo' ); ?>
        </p>

        <p class="mb-10">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"
               class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-full transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php echo rodolfo_icon( 'arrow-left', 'w-4 h-4' ); ?>
                <?php esc_html_e( 'Back to the homepage', 'rodolfo' ); ?>
            </a>
        </p>

        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center max-w-sm mx-auto gap-2">
            <div class="relative flex-grow">
                <?php echo rodolfo_icon( 'search', 'w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400' ); ?>
                <input type="search" name="s" value="" aria-label="<?php esc_attr_e( 'Search', 'rodolfo' ); ?>"
                       placeholder="<?php esc_attr_e( 'Search articles…', 'rodolfo' ); ?>"
                       class="w-full pl-9 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" />
            </div>
            <button type="submit"
                    class="flex-shrink-0 h-10 px-4 bg-slate-900 dark:bg-slate-100 hover:bg-slate-700 dark:hover:bg-white text-white dark:text-slate-900 text-sm font-medium rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                <?php esc_html_e( 'Search', 'rodolfo' ); ?>
            </button>
        </form>
    </div>

    <div class="max-w-4xl mx-auto grid gap-10 md:grid-cols-2 border-t border-slate-100 dark:border-slate-800 pt-10">
        <?php $topics = function_exists( 'rodolfo_footer_topics' ) ? rodolfo_footer_topics( 8 ) : array(); ?>
        <?php if ( $topics ) : ?>
        <section>
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wide"><?php esc_html_e( 'Popular topics', 'rodolfo' ); ?></h2>
            <ul class="space-y-2 list-none pl-0">
                <?php foreach ( $topics as $topic ) : ?>
                    <li><a href="<?php echo esc_url( get_category_link( $topic->term_id ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( $topic->name ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <?php
        $recent = get_posts( array( 'numberposts' => 6, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
        if ( $recent ) :
            ?>
        <section>
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wide"><?php esc_html_e( 'Recent posts', 'rodolfo' ); ?></h2>
            <ul class="space-y-2 list-none pl-0">
                <?php foreach ( $recent as $recent_post ) : ?>
                    <li><a href="<?php echo esc_url( get_permalink( $recent_post ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( get_the_title( $recent_post ) ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
