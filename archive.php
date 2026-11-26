<?php
/**
 * Archive template — date, author, and custom post type archives.
 * Tag archives are handled by tag.php.
 * Category archives are handled by category.php.
 *
 * @package Rodolfo
 */

get_header();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <div class="mb-16">

        <div class="mb-10 pb-10 border-b border-slate-100 dark:border-slate-800">
            <?php if ( is_author() ) :
                $author = get_queried_object();
            ?>
                <div class="flex items-center gap-5">
                    <?php echo get_avatar( $author->user_email, 64, '', '', array( 'class' => 'w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex-shrink-0' ) ); ?>
                    <div>
                        <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">
                            <?php esc_html_e( 'Author', 'rodolfo' ); ?>
                        </p>
                        <h1 class="text-4xl font-bold text-slate-900 dark:text-white leading-tight">
                            <?php echo esc_html( $author->display_name ); ?>
                        </h1>
                        <?php if ( $author->description ) : ?>
                            <p class="text-slate-500 dark:text-slate-400 mt-2 text-base leading-relaxed">
                                <?php echo esc_html( $author->description ); ?>
                            </p>
                        <?php endif; ?>
                        <?php
                        // The same social row the single-post author card shows —
                        // "author card everywhere": archives get the full treatment.
                        $rodolfo_socials = function_exists( 'rodolfo_author_social_links' ) ? rodolfo_author_social_links( (int) $author->ID ) : array();
                        if ( ! empty( $rodolfo_socials ) ) :
                        ?>
                            <div class="flex flex-wrap items-center gap-2 mt-3" aria-label="<?php esc_attr_e( 'Author links', 'rodolfo' ); ?>">
                                <?php foreach ( $rodolfo_socials as $rodolfo_s ) : ?>
                                    <a href="<?php echo esc_url( $rodolfo_s['url'] ); ?>"
                                       rel="me noopener"
                                       target="_blank"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-600 dark:hover:border-blue-400 transition-colors"
                                       aria-label="<?php echo esc_attr( sprintf( /* translators: %1$s: author name, %2$s: platform */ __( '%1$s on %2$s', 'rodolfo' ), $author->display_name, $rodolfo_s['label'] ) ); ?>">
                                        <?php echo rodolfo_icon( $rodolfo_s['icon'], 'w-4 h-4' ); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ( is_year() ) : ?>
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">
                    <?php esc_html_e( 'Year', 'rodolfo' ); ?>
                </p>
                <h1 class="text-5xl font-bold text-slate-900 dark:text-white"><?php echo esc_html( get_the_date( 'Y' ) ); ?></h1>
            <?php elseif ( is_month() ) : ?>
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">
                    <?php esc_html_e( 'Month', 'rodolfo' ); ?>
                </p>
                <h1 class="text-5xl font-bold text-slate-900 dark:text-white"><?php echo esc_html( get_the_date( 'F Y' ) ); ?></h1>
            <?php elseif ( is_day() ) : ?>
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-2">
                    <?php esc_html_e( 'Day', 'rodolfo' ); ?>
                </p>
                <h1 class="text-5xl font-bold text-slate-900 dark:text-white"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></h1>
            <?php else : ?>
                <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2"><?php the_archive_title(); ?></h1>
                <?php if ( the_archive_description( '<p class="text-slate-500 dark:text-slate-400 mt-2 text-base leading-relaxed">', '</p>' ) ) : ?>
                <?php endif; ?>
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
