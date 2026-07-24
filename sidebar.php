<?php
/**
 * Optional widget sidebar.
 *
 * Guarded by rodolfo_has_sidebar(), which requires the feature to be enabled,
 * the current view to match the chosen scope, and at least one widget to be
 * present. Safe to include unconditionally.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'rodolfo_has_sidebar' ) || ! rodolfo_has_sidebar() ) {
    return;
}
?>
<aside class="rodolfo-sidebar w-full lg:w-80 shrink-0" aria-label="<?php esc_attr_e( 'Sidebar', 'rodolfo' ); ?>">
    <?php dynamic_sidebar( 'rodolfo-sidebar' ); ?>
</aside>
