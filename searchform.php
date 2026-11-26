<?php
/**
 * Search form.
 *
 * Without this file `get_search_form()` falls back to core's unstyled markup,
 * which any plugin, widget, or block calling it would render — visibly foreign
 * to the theme. This is that same contract, styled to match.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rodolfo_sf_id = 'tk-search-' . wp_unique_id();
?>
<form role="search" method="get" class="tk-searchform relative" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <label class="sr-only" for="<?php echo esc_attr( $rodolfo_sf_id ); ?>">
        <?php echo esc_html_x( 'Search for:', 'label', 'rodolfo' ); ?>
    </label>
    <div class="flex items-center gap-2">
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                <?php echo rodolfo_icon( 'search', 'w-4 h-4' ); ?>
            </span>
            <input
                type="search"
                id="<?php echo esc_attr( $rodolfo_sf_id ); ?>"
                class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/40 focus:border-blue-600 transition"
                placeholder="<?php echo esc_attr_x( 'Search articles…', 'placeholder', 'rodolfo' ); ?>"
                value="<?php echo get_search_query(); ?>"
                name="s"
            />
        </div>
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600/40 transition">
            <?php echo esc_html_x( 'Search', 'submit button', 'rodolfo' ); ?>
        </button>
    </div>
</form>
