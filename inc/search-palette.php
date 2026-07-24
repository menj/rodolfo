<?php
/**
 * ⌘K / live search command palette.
 *
 * Renders a hidden overlay in the footer and exposes the WordPress core search
 * REST endpoint to the theme script. Opened with ⌘K / Ctrl-K (or "/" when not
 * typing), or by clicking the header search field. Progressive enhancement —
 * the normal search form still works with JavaScript disabled.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_search_palette() {
    ?>
    <div id="rodolfo-search" class="rodolfo-search" data-rest="<?php echo esc_url( rest_url( 'wp/v2/search' ) ); ?>" hidden>
        <div class="rodolfo-search-backdrop" data-search-close></div>
        <div class="rodolfo-search-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search', 'rodolfo' ); ?>">
            <div class="rodolfo-search-inputwrap">
                <?php echo rodolfo_icon( 'search', 'w-5 h-5 text-slate-400 flex-shrink-0' ); ?>
                <input type="search" id="rodolfo-search-input" class="rodolfo-search-input" autocomplete="off"
                    placeholder="<?php esc_attr_e( 'Search articles…', 'rodolfo' ); ?>"
                    aria-label="<?php esc_attr_e( 'Search articles', 'rodolfo' ); ?>"
                    aria-controls="rodolfo-search-results" />
                <kbd class="rodolfo-search-esc">ESC</kbd>
            </div>
            <ul id="rodolfo-search-results" class="rodolfo-search-results" role="listbox"></ul>
            <div class="rodolfo-search-empty" hidden><?php esc_html_e( 'No results found.', 'rodolfo' ); ?></div>
            <div class="rodolfo-search-hint">
                <span><kbd>↑</kbd><kbd>↓</kbd> <?php esc_html_e( 'to navigate', 'rodolfo' ); ?></span>
                <span><kbd>↵</kbd> <?php esc_html_e( 'to open', 'rodolfo' ); ?></span>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'rodolfo_search_palette' );
