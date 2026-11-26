<?php
/**
 * Built-in Tags page template (virtual /tags/ route — see inc/tags.php).
 * Lists every non-empty tag as a linked pill showing its post count.
 *
 * @package Rodolfo
 */

get_header();

$rodolfo_tags = get_terms( array(
    'taxonomy'   => 'post_tag',
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
) );
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-10 md:pt-16 pb-16">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <header class="text-center mb-10 md:mb-14">
        <h1 class="text-4xl md:text-5xl font-display font-bold text-slate-900 dark:text-white"><?php esc_html_e( 'Tags', 'rodolfo' ); ?></h1>
        <?php if ( ! is_wp_error( $rodolfo_tags ) && ! empty( $rodolfo_tags ) ) : ?>
            <p class="mt-3 text-slate-500 dark:text-slate-400">
                <?php
                /* translators: %s: number of tags */
                printf( esc_html( _n( 'Browse %s topic', 'Browse %s topics', count( $rodolfo_tags ), 'rodolfo' ) ), number_format_i18n( count( $rodolfo_tags ) ) );
                ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if ( ! is_wp_error( $rodolfo_tags ) && ! empty( $rodolfo_tags ) ) : ?>
        <div class="tk-tagcloud flex flex-wrap justify-center max-w-4xl mx-auto">
            <?php foreach ( $rodolfo_tags as $rodolfo_tag ) : ?>
                <a href="<?php echo esc_url( get_term_link( $rodolfo_tag ) ); ?>"
                   class="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-2 text-base text-slate-700 dark:text-slate-300 hover:border-blue-600 hover:text-blue-600 dark:hover:border-blue-400 dark:hover:text-blue-400 hover:-translate-y-0.5 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php echo esc_html( $rodolfo_tag->name ); ?>
                    <span class="ml-1.5 text-slate-500 dark:text-slate-400">(<?php echo esc_html( number_format_i18n( $rodolfo_tag->count ) ); ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="text-center text-slate-500 dark:text-slate-400"><?php esc_html_e( 'No tags yet.', 'rodolfo' ); ?></p>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
