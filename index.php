<?php
/**
 * Home / posts-index template.
 *
 * Layouts (Theme Options → Content → Homepage layout):
 *   stream    single-column editorial feed
 *   grid      uniform 2/3-column card grid
 *   magazine  featured lead post + grid (page 1 only)
 *
 * Content depth (Homepage content depth): card excerpt, long excerpt (150+
 * words) or full post HTML — see template-parts/content/content-card.php.
 *
 * Exactly one <h1>: the site headline. Post titles are <h2>/<h3>.
 *
 * Archive, category, tag, and search requests are handled by their dedicated
 * templates (archive.php, category.php, tag.php, search.php).
 *
 * @package Rodolfo
 */

get_header();

$home_layout = function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_home_layout' ) : 'magazine';
$is_lead     = ( 'magazine' === $home_layout && is_home() && ! is_paged() );
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-6 md:pt-8 pb-8 md:pb-12">
    <?php if ( have_posts() ) : ?>

        <header class="home-intro mb-8 md:mb-10">
            <h1 class="text-2xl md:text-3xl font-display font-bold text-slate-900 dark:text-white leading-tight">
                <?php
                bloginfo( 'name' );
                if ( is_paged() ) {
                    /* translators: %d: page number */
                    echo ' <span class="text-slate-500 dark:text-slate-400 font-medium">&mdash; ' . esc_html( sprintf( __( 'Page %d', 'rodolfo' ), (int) get_query_var( 'paged' ) ) ) . '</span>';
                }
                ?>
            </h1>
            <?php if ( get_bloginfo( 'description' ) && ! is_paged() ) : ?>
                <p class="mt-2 text-base md:text-lg text-slate-600 dark:text-slate-300 max-w-3xl"><?php bloginfo( 'description' ); ?></p>
            <?php endif; ?>
        </header>

        <?php
        if ( $is_lead ) :
            the_post();
            $categories = get_the_category();
            $cat_name   = ! empty( $categories ) ? $categories[0]->name : __( 'Uncategorized', 'rodolfo' );
            ?>
        <!-- Featured story: the first post of the MAIN query (no secondary query, so pagination stays exact). -->
        <a href="<?php the_permalink(); ?>" class="group block mb-10 md:mb-12 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 rounded-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 items-center">
                <div class="tk-hero-cover overflow-hidden rounded-xl aspect-[16/10] bg-slate-100 dark:bg-slate-800 relative">
                    <?php if ( ! empty( $categories ) ) : ?>
                        <span class="absolute left-0 top-0 h-1.5 w-full z-10" style="background-color:<?php echo esc_attr( rodolfo_category_color( $categories[0]->term_id ) ); ?>" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?php if ( has_post_thumbnail( get_the_ID() ) ) : ?>
                        <?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-700', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); // LCP image: never lazy-load. ?>
                    <?php else : ?>
                        <?php rodolfo_post_thumbnail( get_the_ID(), 'large' ); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="mb-3 flex items-center gap-3">
                        <span class="inline-block px-3 py-1 text-xs font-bold rounded-full" style="<?php echo esc_attr( ! empty( $categories ) ? rodolfo_category_pill_style( $categories[0]->term_id ) : '' ); ?>">
                            <?php echo esc_html( $cat_name ); ?>
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium"><?php echo esc_html( get_the_date() ); ?></span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-display font-bold text-slate-900 dark:text-white mb-3 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-[1.12]">
                        <?php the_title(); ?>
                    </h2>
                    <p class="text-base md:text-lg text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-3"><?php echo esc_html( get_the_excerpt() ); ?></p>
                    <div class="mt-4 flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                        <?php if ( ! ( function_exists( 'rodolfo_has_guest_author' ) && rodolfo_has_guest_author() ) ) echo get_avatar( get_the_author_meta( 'ID' ), 28, '', get_the_author(), array( 'class' => 'w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800' ) ); ?>
                        <span class="font-medium text-slate-700 dark:text-slate-300"><?php echo esc_html( get_the_author() ); ?></span>
                        <?php $rodolfo_rt = function_exists( 'rodolfo_get_reading_time' ) ? rodolfo_get_reading_time() : ''; ?>
                        <?php if ( $rodolfo_rt ) : ?>
                            <span aria-hidden="true">·</span>
                            <span><?php echo $rodolfo_rt; // Escaped in rodolfo_get_reading_time(). ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </a>
        <?php endif; ?>

        <?php if ( have_posts() ) : ?>
        <section class="mb-10" aria-labelledby="latest-heading">
            <?php if ( 'magazine' === $home_layout && is_home() ) : ?>
                <h2 id="latest-heading" class="text-2xl md:text-3xl font-bold font-display text-slate-900 dark:text-white mb-6"><?php esc_html_e( 'Latest Articles', 'rodolfo' ); ?></h2>
            <?php else : ?>
                <h2 id="latest-heading" class="screen-reader-text"><?php esc_html_e( 'Latest Articles', 'rodolfo' ); ?></h2>
            <?php endif; ?>

            <?php
            // Card headings: h3 under the visible "Latest Articles" h2 (magazine); h2 elsewhere.
            $card_heading = ( 'magazine' === $home_layout && is_home() ) ? 'h3' : 'h2';
            $wrap_class   = ( 'stream' === $home_layout ) ? 'tk-cards home-stream' : 'tk-cards grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8';
            ?>
            <div class="<?php echo esc_attr( $wrap_class ); ?>">
                <?php
                while ( have_posts() ) :
                    the_post();
                    get_template_part( 'template-parts/content/content-card', null, array( 'heading' => $card_heading ) );
                endwhile;
                ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Pagination: plain crawlable links -->
        <?php
        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => rodolfo_icon( 'chevron-left', 'w-5 h-5' ),
            'next_text' => rodolfo_icon( 'chevron-right', 'w-5 h-5' ),
            'class'     => 'flex justify-center items-center gap-2 mt-12 pagination-links',
        ) );
        ?>

    <?php else : ?>
        <div class="py-12 text-center text-slate-500 dark:text-slate-400">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-3"><?php bloginfo( 'name' ); ?></h1>
            <?php echo rodolfo_icon( 'file-x', 'w-12 h-12 mx-auto mb-4 opacity-50' ); ?>
            <p class="text-lg"><?php esc_html_e( 'No articles found.', 'rodolfo' ); ?></p>
        </div>
    <?php endif; ?>
</main>

<?php get_footer(); ?>
