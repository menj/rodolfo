<?php
/**
 * Category archive template.
 * Displays the emoji icon, category title, description, and article count
 * as registered via the custom category_icon term meta field.
 *
 * @package Rodolfo
 */

get_header();

$term = get_queried_object();
$icon = get_term_meta( $term->term_id, 'category_icon', true );
if ( empty( $icon ) ) {
    $icon = '📁';
}
$cat_accent = rodolfo_category_color( $term->term_id );
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <div class="mb-16">

        <div class="mb-16 py-12 border-b border-slate-100 dark:border-slate-800">
            <span class="block h-1 w-16 rounded-full mb-8" style="background-color:<?php echo esc_attr( $cat_accent ); ?>" aria-hidden="true"></span>
            <div class="flex items-start gap-6 mb-6">
                <div class="text-6xl"><?php echo esc_html( $icon ); ?></div>
                <div class="flex-grow">
                    <h1 class="text-5xl font-display font-bold text-slate-900 dark:text-white mb-3 capitalize">
                        <?php single_cat_title(); ?>
                    </h1>
                    <?php if ( category_description() ) : ?>
                        <p class="text-xl text-slate-600 dark:text-slate-400 mb-4">
                            <?php echo esc_html( wp_strip_all_tags( category_description() ) ); ?>
                        </p>
                    <?php endif; ?>
                    <div class="flex items-center gap-4">
                        <span class="inline-block px-4 py-2 font-semibold rounded-full text-sm" style="<?php echo esc_attr( rodolfo_category_pill_style( $term->term_id ) ); ?>">
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
        </div>

        <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-8">
            <?php
            printf(
                /* translators: %s: category name */
                esc_html__( 'Latest in %s', 'rodolfo' ),
                '<span>' . single_cat_title( '', false ) . '</span>'
            );
            ?>
        </h2>

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
