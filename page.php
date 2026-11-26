<?php get_header(); ?>

<main id="content" class="flex-grow max-w-[1400px] mx-auto px-4 md:px-8 w-full pt-8 md:pt-12 pb-8 md:pb-12">
    <?php if ( function_exists( 'rodolfo_breadcrumbs' ) ) { rodolfo_breadcrumbs(); } ?>
    <?php
    if ( have_posts() ) :
        while ( have_posts() ) : the_post();
    ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('max-w-4xl mx-auto bg-white dark:bg-slate-900 rounded-xl md:rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden'); ?>>
            
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="w-full aspect-[21/9] md:aspect-[2.5/1] overflow-hidden relative">
                    <?php the_post_thumbnail( 'full', array( 'class' => 'w-full h-full object-cover', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
                </div>
            <?php endif; ?>

            <div class="p-6 md:p-10 lg:p-12">
                <header class="mb-8 md:mb-12">
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-6 leading-tight">
                        <?php the_title(); ?>
                    </h1>
                </header>

                <div class="prose prose-slate dark:prose-invert prose-lg md:prose-xl max-w-none 
                    prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white
                    prose-p:text-slate-700 dark:prose-p:text-slate-300 prose-p:leading-relaxed
                    prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline
                    prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:bg-slate-50 dark:prose-blockquote:bg-slate-800/50 prose-blockquote:py-2 prose-blockquote:px-6 prose-blockquote:rounded-r-lg prose-blockquote:not-italic
                    prose-img:rounded-xl">
                    <?php the_content(); ?>
                </div>

                <?php
                wp_link_pages( array(
                    'before'      => '<nav class="page-links mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center gap-2" aria-label="' . esc_attr__( 'Page pages', 'rodolfo' ) . '"><span class="text-sm font-medium text-slate-500 dark:text-slate-400">' . esc_html__( 'Pages:', 'rodolfo' ) . '</span>',
                    'after'       => '</nav>',
                    'link_before' => '<span class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-md text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">',
                    'link_after'  => '</span>',
                ) );
                ?>

            </div>
        </article>
    <?php
        endwhile;
    endif;
    ?>
</main>

<?php get_footer(); ?>