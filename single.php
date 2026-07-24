<?php
/**
 * Single post template.
 *
 * @package Rodolfo
 */

get_header();
?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
            if ( function_exists( 'rodolfo_breadcrumbs' ) ) {
                rodolfo_breadcrumbs();
            }
            get_template_part( 'template-parts/content/content-single' );
            rodolfo_render_author_card();
            if ( function_exists( 'rodolfo_render_related_posts' ) ) {
                rodolfo_render_related_posts();
            }
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
        endwhile;
    endif;
    ?>
</main>

<?php get_footer(); ?>
