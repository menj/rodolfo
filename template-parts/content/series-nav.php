<?php
/**
 * Series navigation box.
 *
 * Renders "Part N of M" with previous/next links when the post belongs to a
 * series and the feature is enabled. Returns silently otherwise, so it is safe
 * to include unconditionally.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! get_option( 'rodolfo_series_enabled' ) || ! function_exists( 'rodolfo_series_context' ) ) {
    return;
}

$rodolfo_series = rodolfo_series_context();
if ( ! $rodolfo_series || $rodolfo_series['total'] < 2 ) {
    return; // A "series" of one is just a post.
}
?>
<aside class="rodolfo-series my-10 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 p-6"
       aria-label="<?php esc_attr_e( 'Series navigation', 'rodolfo' ); ?>">

    <div class="flex items-center gap-2 mb-3 text-slate-500 dark:text-slate-400">
        <?php echo rodolfo_icon( 'layers', 'w-4 h-4' ); ?>
        <span class="text-xs font-semibold uppercase tracking-wide">
            <?php
            printf(
                /* translators: 1: current part number, 2: total parts */
                esc_html__( 'Part %1$s of %2$s', 'rodolfo' ),
                esc_html( number_format_i18n( $rodolfo_series['index'] ) ),
                esc_html( number_format_i18n( $rodolfo_series['total'] ) )
            );
            ?>
        </span>
    </div>

    <h2 class="text-lg font-bold font-display text-slate-900 dark:text-white mb-4">
        <a href="<?php echo esc_url( get_term_link( $rodolfo_series['term'] ) ); ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <?php echo esc_html( $rodolfo_series['term']->name ); ?>
        </a>
    </h2>

    <ol class="space-y-1.5 mb-5 list-none">
        <?php foreach ( $rodolfo_series['posts'] as $rodolfo_i => $rodolfo_p ) :
            $rodolfo_current = ( (int) $rodolfo_p->ID === (int) get_the_ID() );
            ?>
            <li class="text-sm flex gap-2.5">
                <span class="text-slate-400 tabular-nums w-5 shrink-0"><?php echo esc_html( number_format_i18n( $rodolfo_i + 1 ) ); ?>.</span>
                <?php if ( $rodolfo_current ) : ?>
                    <span class="font-semibold text-slate-900 dark:text-white" aria-current="true"><?php echo esc_html( get_the_title( $rodolfo_p ) ); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url( get_permalink( $rodolfo_p ) ); ?>" class="text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <?php echo esc_html( get_the_title( $rodolfo_p ) ); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
        <?php if ( $rodolfo_series['prev'] ) : ?>
            <a href="<?php echo esc_url( get_permalink( $rodolfo_series['prev'] ) ); ?>"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                <?php echo rodolfo_icon( 'chevron-left', 'w-4 h-4' ); ?>
                <?php esc_html_e( 'Previous part', 'rodolfo' ); ?>
            </a>
        <?php endif; ?>
        <?php if ( $rodolfo_series['next'] ) : ?>
            <a href="<?php echo esc_url( get_permalink( $rodolfo_series['next'] ) ); ?>"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline ml-auto">
                <?php esc_html_e( 'Next part', 'rodolfo' ); ?>
                <?php echo rodolfo_icon( 'chevron-right', 'w-4 h-4' ); ?>
            </a>
        <?php endif; ?>
    </div>
</aside>
