<?php
/**
 * Template Name: HTML Sitemap
 *
 * A human- and crawler-readable sitemap: Pages, Categories (with their post
 * counts) and Recent Posts as plain <a href> links. It complements the XML
 * sitemap WordPress serves at /wp-sitemap.xml. Created and linked automatically
 * by inc/setup.php and the footer.
 *
 * @package Rodolfo
 */

get_header();

if ( have_posts() ) {
    the_post(); // Prime the page so the title and id resolve.
}

$sitemap_posts = (int) apply_filters( 'rodolfo_sitemap_recent_posts', 100 );
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class( 'max-w-4xl mx-auto' ); ?>>
        <header class="mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white leading-tight"><?php the_title(); ?></h1>
            <p class="mt-3 text-slate-600 dark:text-slate-300">
                <?php
                printf(
                    /* translators: %s: link to the XML sitemap */
                    esc_html__( 'Every page, topic and recent article on this site. Search engines can also read the %s.', 'rodolfo' ),
                    '<a class="text-blue-600 dark:text-blue-400 hover:underline" href="' . esc_url( function_exists( 'get_sitemap_url' ) ? get_sitemap_url( 'index' ) : home_url( '/wp-sitemap.xml' ) ) . '">' . esc_html__( 'XML sitemap', 'rodolfo' ) . '</a>'
                );
                ?>
            </p>
        </header>

        <?php
        $pages = get_pages( array( 'sort_column' => 'menu_order,post_title', 'post_status' => 'publish' ) );
        ?>
        <section id="pages" class="mb-10" aria-labelledby="pages-heading">
            <h2 id="pages-heading" class="text-xl font-bold text-slate-900 dark:text-white mb-4"><?php esc_html_e( 'Pages', 'rodolfo' ); ?></h2>
            <ul class="space-y-2 list-none pl-0">
                <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php esc_html_e( 'Home', 'rodolfo' ); ?></a></li>
                <?php foreach ( $pages as $sm_page ) : ?>
                    <?php
                    // Skip pages that must not be indexed (coming-soon stubs).
                    if ( function_exists( 'rodolfo_is_coming_soon' ) && rodolfo_is_coming_soon( $sm_page ) ) {
                        continue;
                    }
                    $depth = count( get_post_ancestors( $sm_page ) );
                    ?>
                    <li<?php echo $depth ? ' style="padding-left:' . (int) ( $depth * 1.25 * 16 ) . 'px"' : ''; ?>>
                        <a href="<?php echo esc_url( get_permalink( $sm_page ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( get_the_title( $sm_page ) ); ?></a>
                    </li>
                <?php endforeach; ?>
                <?php if ( function_exists( 'rodolfo_tags_url' ) ) : ?>
                    <li><a href="<?php echo esc_url( rodolfo_tags_url() ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php esc_html_e( 'All tags', 'rodolfo' ); ?></a></li>
                <?php endif; ?>
            </ul>
        </section>

        <?php $cats = get_categories( array( 'orderby' => 'name', 'hide_empty' => true ) ); ?>
        <?php if ( $cats ) : ?>
        <section id="categories" class="mb-10" aria-labelledby="categories-heading">
            <h2 id="categories-heading" class="text-xl font-bold text-slate-900 dark:text-white mb-4"><?php esc_html_e( 'Categories', 'rodolfo' ); ?></h2>
            <ul class="space-y-2 list-none pl-0">
                <?php foreach ( $cats as $sm_cat ) : ?>
                    <li<?php echo $sm_cat->parent ? ' style="padding-left:1.25rem"' : ''; ?>>
                        <a href="<?php echo esc_url( get_category_link( $sm_cat->term_id ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( $sm_cat->name ); ?></a>
                        <span class="text-sm text-slate-500 dark:text-slate-400">(<?php echo (int) $sm_cat->count; ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <?php
        $recent = get_posts( array(
            'numberposts'         => $sitemap_posts,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'suppress_filters'    => false,
        ) );
        ?>
        <?php if ( $recent ) : ?>
        <section id="posts" class="mb-10" aria-labelledby="posts-heading">
            <h2 id="posts-heading" class="text-xl font-bold text-slate-900 dark:text-white mb-4"><?php esc_html_e( 'Recent posts', 'rodolfo' ); ?></h2>
            <ul class="space-y-2 list-none pl-0">
                <?php foreach ( $recent as $sm_post ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $sm_post ) ); ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( get_the_title( $sm_post ) ); ?></a>
                        <time class="text-sm text-slate-500 dark:text-slate-400" datetime="<?php echo esc_attr( get_the_date( 'c', $sm_post ) ); ?>">&middot; <?php echo esc_html( get_the_date( '', $sm_post ) ); ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </article>
</main>

<?php get_footer(); ?>
