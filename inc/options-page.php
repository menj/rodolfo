<?php
/**
 * Theme Options screen — the rendered page.
 *
 * Markup only: tabs, panels and fields. The option registry and sanitizers live
 * in inc/settings.php; export/import/reset and the menu in inc/options-actions.php.
 *
 * Extracted from inc/options-page.php in 3.8.0 — a pure structural move, no
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/** Option-screen helpers: one section row each, same markup as the hand-written ones. */
function rodolfo_opt_open( $title, $desc ) {
    echo '<div class="tk-section"><div class="tk-section-label"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $desc ) . '</p></div><div class="tk-section-control">';
}
function rodolfo_opt_close() {
    echo '</div></div>';
}
function rodolfo_opt_select( $option, $title, $desc ) {
    $registry = rodolfo_choice_registry();
    $current  = rodolfo_choice( $option );
    rodolfo_opt_open( $title, $desc );
    echo '<div class="tk-field"><select id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '">';
    foreach ( $registry[ $option ] as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( (string) $current, (string) $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select></div>';
    rodolfo_opt_close();
}
function rodolfo_opt_text( $option, $title, $desc, $placeholder = '' ) {
    rodolfo_opt_open( $title, $desc );
    echo '<div class="tk-field"><input type="text" class="regular-text" id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '" value="' . esc_attr( get_option( $option, '' ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" /></div>';
    rodolfo_opt_close();
}
function rodolfo_opt_check( $option, $title, $desc, $label, $default = '' ) {
    rodolfo_opt_open( $title, $desc );
    echo '<div class="tk-toggle-row"><input type="checkbox" id="' . esc_attr( $option ) . '" name="' . esc_attr( $option ) . '" value="1"' . checked( '1', get_option( $option, $default ), false ) . ' /><div class="tk-toggle-info"><label for="' . esc_attr( $option ) . '">' . esc_html( $label ) . '</label></div></div>';
    rodolfo_opt_close();
}

// ============================================================
// THEME OPTIONS PAGE
// ============================================================
function rodolfo_theme_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
    // Ordered to follow the natural setup flow of a new site: identity/footer
    // first, then social presence, speed tweaks, tracking scripts, and finally
    // the read-only environment report.
    $tabs = array(
        'general'     => __( 'General', 'rodolfo' ),
        'navigation'  => __( 'Navigation', 'rodolfo' ),
        'content'     => __( 'Content', 'rodolfo' ),
        'social'      => __( 'Social Links', 'rodolfo' ),
        'performance' => __( 'Performance', 'rodolfo' ),
        'features'    => __( 'Features', 'rodolfo' ),
        'scripts'     => __( 'Custom Scripts', 'rodolfo' ),
        'system'      => __( 'System Info', 'rodolfo' ),
    );

    // Clamp to a known tab — an unknown ?tab= value would leave no panel active.
    if ( ! isset( $tabs[ $active_tab ] ) ) {
        $active_tab = 'general';
    }

    // Admin notices
    $notice = isset( $_GET['rodolfo_notice'] ) ? sanitize_key( $_GET['rodolfo_notice'] ) : '';
    $messages = array(
        'import_ok'      => array( 'success', __( 'Settings imported successfully.', 'rodolfo' ) ),
        'import_empty'   => array( 'error',   __( 'No file uploaded. Please select a JSON export file.', 'rodolfo' ) ),
        'import_invalid' => array( 'error',   __( 'Invalid file. Please upload a valid Rodolfo settings JSON.', 'rodolfo' ) ),
        'reset_ok'       => array( 'success', __( 'All settings have been reset to defaults.', 'rodolfo' ) ),
        'setup_ok'       => array( 'success', __( 'Setup complete. Any missing pages were created.', 'rodolfo' ) ),
        'setup_nothing'  => array( 'success', __( 'Nothing to create — every required page already exists.', 'rodolfo' ) ),
    );
    ?>
    <div class="wrap tk-opts">

        <div class="tk-hero">
            <div class="tk-hero-titles">
                <h1>
                    <?php esc_html_e( 'Theme Options', 'rodolfo' ); ?>
                    <span class="tk-version">
                        <?php
                        /* translators: %s: theme version number */
                        printf( esc_html__( 'v%s', 'rodolfo' ), esc_html( wp_get_theme()->get( 'Version' ) ) );
                        ?>
                    </span>
                </h1>
                <p class="tk-hero-sub"><?php esc_html_e( 'Configure your site\'s footer, social links, performance, and scripts.', 'rodolfo' ); ?></p>
            </div>
            <div class="tk-topbar-actions">
                <!-- Export -->
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'rodolfo_export_settings' ); ?>
                    <input type="hidden" name="action" value="rodolfo_export_settings" />
                    <button type="submit" class="button"><?php esc_html_e( 'Export Settings', 'rodolfo' ); ?></button>
                </form>
                <!-- Import -->
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'rodolfo_import_settings' ); ?>
                    <input type="hidden" name="action" value="rodolfo_import_settings" />
                    <label class="button tk-import-label">
                        <?php esc_html_e( 'Import Settings', 'rodolfo' ); ?>
                        <input type="file" name="rodolfo_import_file" accept=".json" style="display:none" onchange="this.form.submit()" />
                    </label>
                </form>
            </div>
        </div>

        <?php if ( $notice && isset( $messages[ $notice ] ) ) :
            list( $type, $msg ) = $messages[ $notice ];
            echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
        endif; ?>

        <nav class="tk-tabs">
            <?php foreach ( $tabs as $slug => $label ) : ?>
                <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'tk-theme-options', 'tab' => $slug ), admin_url( 'themes.php' ) ) ); ?>"
                   class="tk-tab-btn<?php echo $slug === $active_tab ? ' is-active' : ''; ?>"
                   data-tab="<?php echo esc_attr( $slug ); ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <form method="post" action="options.php" id="tk-options-form">
            <?php settings_fields( 'rodolfo_options_group' ); ?>
            <input type="hidden" name="rodolfo_active_tab" id="tk-active-tab" value="<?php echo esc_attr( $active_tab ); ?>" />

            <!-- GENERAL TAB -->
            <div class="tk-panel<?php echo 'general' === $active_tab ? ' is-active' : ''; ?>" data-panel="general">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Footer text', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'The copyright line shown at the bottom of every page.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <label for="rodolfo_footer_text"><?php esc_html_e( 'Copyright line', 'rodolfo' ); ?></label>
                            <input type="text" id="rodolfo_footer_text" name="rodolfo_footer_text"
                                   value="<?php echo esc_attr( get_option( 'rodolfo_footer_text' ) ); ?>"
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Replaces the auto-generated "Site Name blogging since YEAR" text. Leave blank to use the default.', 'rodolfo' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Footer links', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'The Sitemap URL for the footer. Privacy uses the page set in Settings → Privacy; Terms, Accessibility and other pages are managed under the Navigation tab.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <label for="rodolfo_link_sitemap"><?php esc_html_e( 'Sitemap URL override', 'rodolfo' ); ?></label>
                            <input type="url" id="rodolfo_link_sitemap" name="rodolfo_link_sitemap"
                                   value="<?php echo esc_attr( get_option( 'rodolfo_link_sitemap' ) ); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr( home_url( '/wp-sitemap.xml' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'Leave blank to use the WordPress core XML sitemap automatically. Set this if an SEO plugin provides the sitemap (e.g. /sitemap_index.xml).', 'rodolfo' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Site setup', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Create the utility pages the theme references (FAQ, Terms, Privacy, Accessibility) and add starter content to any that exist but are still empty. Safe to run anytime — pages that already have content are never touched, and nothing is ever deleted.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <table class="tk-sysinfo" style="margin-bottom:14px">
                            <?php foreach ( rodolfo_setup_status() as $item ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $item['title'] ); ?></td>
                                    <?php if ( ! $item['exists'] ) : ?>
                                        <td class="warn"><?php esc_html_e( 'Missing', 'rodolfo' ); ?></td>
                                    <?php elseif ( ! empty( $item['empty'] ) ) : ?>
                                        <td class="warn">
                                            <?php esc_html_e( 'Present but empty', 'rodolfo' ); ?>
                                            <?php if ( $item['id'] ) : ?>
                                                — <a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php esc_html_e( 'add content', 'rodolfo' ); ?></a>
                                            <?php endif; ?>
                                        </td>
                                    <?php else : ?>
                                        <td class="ok"><?php esc_html_e( 'Present', 'rodolfo' ); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <button type="submit" form="tk-setup-form" class="button">
                            <?php esc_html_e( 'Create missing pages & fill empty ones', 'rodolfo' ); ?>
                        </button>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Maintenance mode', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Show a holding page to visitors while you work. Logged-in admins always see the live site.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <label for="rodolfo_maintenance_mode"><?php esc_html_e( 'Mode', 'rodolfo' ); ?></label>
                            <select id="rodolfo_maintenance_mode" name="rodolfo_maintenance_mode" style="max-width:240px">
                                <option value="" <?php selected( get_option( 'rodolfo_maintenance_mode' ), '' ); ?>><?php esc_html_e( 'Disabled', 'rodolfo' ); ?></option>
                                <option value="maintenance" <?php selected( get_option( 'rodolfo_maintenance_mode' ), 'maintenance' ); ?>><?php esc_html_e( 'Maintenance (503)', 'rodolfo' ); ?></option>
                                <option value="coming_soon" <?php selected( get_option( 'rodolfo_maintenance_mode' ), 'coming_soon' ); ?>><?php esc_html_e( 'Coming soon (200)', 'rodolfo' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Maintenance returns a 503 status (tells search engines to return later). Coming soon returns 200 (allows indexing).', 'rodolfo' ); ?></p>
                        </div>
                        <div class="tk-field">
                            <label for="rodolfo_maintenance_title"><?php esc_html_e( 'Headline (optional)', 'rodolfo' ); ?></label>
                            <input type="text" id="rodolfo_maintenance_title" name="rodolfo_maintenance_title"
                                   value="<?php echo esc_attr( get_option( 'rodolfo_maintenance_title' ) ); ?>"
                                   class="regular-text" placeholder="<?php esc_attr_e( 'The fish are swimming away', 'rodolfo' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Leave blank to use the default. Keep it light, but make sure visitors can tell the site is coming back.', 'rodolfo' ); ?></p>
                        </div>
                        <div class="tk-field">
                            <label for="rodolfo_maintenance_body"><?php esc_html_e( 'Message (optional)', 'rodolfo' ); ?></label>
                            <textarea id="rodolfo_maintenance_body" name="rodolfo_maintenance_body" rows="3" class="large-text"
                                      placeholder="<?php esc_attr_e( 'We are doing some quick maintenance and will be back shortly.', 'rodolfo' ); ?>"><?php echo esc_textarea( get_option( 'rodolfo_maintenance_body' ) ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Shown under the headline. The holding page uses your site logo when one is set, and follows the visitor\'s light or dark mode.', 'rodolfo' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NAVIGATION TAB -->
            <div class="tk-panel<?php echo 'navigation' === $active_tab ? ' is-active' : ''; ?>" data-panel="navigation">
                <?php
                $rodolfo_menu_overrides = array();
                if ( has_nav_menu( 'primary' ) ) {
                    $rodolfo_menu_overrides[] = __( 'header menu', 'rodolfo' );
                }
                if ( has_nav_menu( 'secondary' ) ) {
                    $rodolfo_menu_overrides[] = __( 'footer row', 'rodolfo' );
                }
                if ( $rodolfo_menu_overrides ) :
                    ?>
                    <div class="tk-callout tk-callout-warn">
                        <p>
                            <strong><?php esc_html_e( 'These settings are currently being overridden.', 'rodolfo' ); ?></strong>
                            <?php
                            printf(
                                /* translators: %s: list of affected nav areas, e.g. "header menu, footer row" */
                                esc_html__( 'A WordPress menu is assigned to your %s, and an assigned menu always takes priority over the per-page settings below. Until you unassign it, changes here will not appear on the site.', 'rodolfo' ),
                                esc_html( implode( __( ', ', 'rodolfo' ), $rodolfo_menu_overrides ) )
                            );
                            ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url( admin_url( 'nav-menus.php?action=locations' ) ); ?>">
                                <?php esc_html_e( 'Manage menu locations →', 'rodolfo' ); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Navigation', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Decide where each top-level Page appears — the header menu, the footer row, or nowhere — and set the order (lower numbers come first). Below, choose which built-in links show in the footer and where they sit in the same order.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <?php
                        $rodolfo_nav_pages = rodolfo_nav_pages();
                        if ( empty( $rodolfo_nav_pages ) ) :
                            ?>
                            <p class="description"><?php esc_html_e( 'No published top-level pages yet. Create a Page (e.g. About) and it will appear in the header menu automatically.', 'rodolfo' ); ?></p>
                        <?php else : ?>
                            <table class="tk-navtable">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Page', 'rodolfo' ); ?></th>
                                        <th><?php esc_html_e( 'Location', 'rodolfo' ); ?></th>
                                        <th class="tk-navtable-order"><?php esc_html_e( 'Order', 'rodolfo' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $rodolfo_nav_pages as $rodolfo_page ) :
                                        $rodolfo_pid = (int) $rodolfo_page->ID;
                                        $rodolfo_loc = rodolfo_nav_location( $rodolfo_pid );
                                        $rodolfo_ord = rodolfo_nav_order_val( $rodolfo_page );
                                        ?>
                                        <tr>
                                            <td class="tk-navtable-title"><?php echo esc_html( get_the_title( $rodolfo_pid ) ); ?></td>
                                            <td>
                                                <select name="rodolfo_nav_locations[<?php echo esc_attr( $rodolfo_pid ); ?>]">
                                                    <option value="header" <?php selected( 'header', $rodolfo_loc ); ?>><?php esc_html_e( 'Header menu', 'rodolfo' ); ?></option>
                                                    <option value="footer" <?php selected( 'footer', $rodolfo_loc ); ?>><?php esc_html_e( 'Footer links', 'rodolfo' ); ?></option>
                                                    <option value="hidden" <?php selected( 'hidden', $rodolfo_loc ); ?>><?php esc_html_e( 'Hidden', 'rodolfo' ); ?></option>
                                                </select>
                                            </td>
                                            <td class="tk-navtable-order">
                                                <input type="number" name="rodolfo_nav_order[<?php echo esc_attr( $rodolfo_pid ); ?>]"
                                                       value="<?php echo esc_attr( $rodolfo_ord ); ?>" class="small-text" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php $rodolfo_util = rodolfo_footer_util_config(); ?>
                            <h4 class="tk-navtable-sub"><?php esc_html_e( 'Built-in footer links', 'rodolfo' ); ?></h4>
                            <table class="tk-navtable">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Link', 'rodolfo' ); ?></th>
                                        <th><?php esc_html_e( 'Show', 'rodolfo' ); ?></th>
                                        <th><?php esc_html_e( 'Label', 'rodolfo' ); ?></th>
                                        <th class="tk-navtable-order"><?php esc_html_e( 'Order', 'rodolfo' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $rodolfo_util as $rodolfo_uk => $rodolfo_ucfg ) : ?>
                                        <tr>
                                            <td class="tk-navtable-title"><?php echo esc_html( rodolfo_footer_util_default_label( $rodolfo_uk ) ); ?></td>
                                            <td>
                                                <input type="hidden" name="rodolfo_footer_util[<?php echo esc_attr( $rodolfo_uk ); ?>][show]" value="0" />
                                                <input type="checkbox" name="rodolfo_footer_util[<?php echo esc_attr( $rodolfo_uk ); ?>][show]" value="1" <?php checked( $rodolfo_ucfg['show'] ); ?> />
                                            </td>
                                            <td>
                                                <input type="text" name="rodolfo_footer_util[<?php echo esc_attr( $rodolfo_uk ); ?>][label]"
                                                       value="<?php echo esc_attr( $rodolfo_ucfg['label'] ); ?>"
                                                       placeholder="<?php echo esc_attr( rodolfo_footer_util_default_label( $rodolfo_uk ) ); ?>" class="regular-text" />
                                            </td>
                                            <td class="tk-navtable-order">
                                                <input type="number" name="rodolfo_footer_util[<?php echo esc_attr( $rodolfo_uk ); ?>][order]"
                                                       value="<?php echo esc_attr( $rodolfo_ucfg['order'] ); ?>" class="small-text" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="description"><?php esc_html_e( 'Footer links (footer-assigned Pages plus the built-in links above) are shown in ascending order value. Untick any you don\'t need, rename them in the Label field, or reorder them. A built-in link is skipped automatically if its destination doesn\'t exist (e.g. no FAQ or Terms page). FAQ links to a page with slug "faq"; Terms of Use to "terms"; Privacy Policy to Settings → Privacy; Sitemap to the URL below or WordPress\'s built-in sitemap.', 'rodolfo' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CONTENT TAB -->
            <div class="tk-panel<?php echo 'content' === $active_tab ? ' is-active' : ''; ?>" data-panel="content">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Reading bar', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'A scroll progress bar shown on posts and pages.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_progress_enabled" name="rodolfo_progress_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_progress_enabled', '1' ) ); ?> />
                            <label for="rodolfo_progress_enabled"><?php esc_html_e( 'Show the reading progress bar on posts and pages', 'rodolfo' ); ?></label>
                        </div>
                        <div class="tk-field">
                            <label for="rodolfo_progress_height"><?php esc_html_e( 'Bar height (px)', 'rodolfo' ); ?></label>
                            <input type="number" min="2" max="20" id="rodolfo_progress_height" name="rodolfo_progress_height"
                                   value="<?php echo esc_attr( get_option( 'rodolfo_progress_height', 4 ) ); ?>" class="small-text" />
                            <p class="description"><?php esc_html_e( 'Between 2 and 20 pixels. Values below 2 fall back to 4 so the bar stays visible.', 'rodolfo' ); ?></p>
                        </div>
                        <div class="tk-field">
                            <label for="rodolfo_progress_position"><?php esc_html_e( 'Bar position', 'rodolfo' ); ?></label>
                            <select id="rodolfo_progress_position" name="rodolfo_progress_position">
                                <option value="top" <?php selected( 'top', get_option( 'rodolfo_progress_position', 'top' ) ); ?>><?php esc_html_e( 'Top of the screen', 'rodolfo' ); ?></option>
                                <option value="bottom" <?php selected( 'bottom', get_option( 'rodolfo_progress_position', 'top' ) ); ?>><?php esc_html_e( 'Bottom of the screen', 'rodolfo' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Reading extras', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Small additions to the reading experience. Each is independent and off unless you switch it on.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_reading_time_enabled" name="rodolfo_reading_time_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_reading_time_enabled', '1' ) ); ?> />
                            <label for="rodolfo_reading_time_enabled"><?php esc_html_e( 'Show estimated reading time on posts', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'On by default, as the theme has always shown this. Calculated from word count (about 220 words a minute), with code blocks weighted more slowly. Nothing is stored.', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_series_enabled" name="rodolfo_series_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_series_enabled' ) ); ?> />
                            <label for="rodolfo_series_enabled"><?php esc_html_e( 'Show series navigation on posts', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: link to the Series taxonomy screen */
                                esc_html__( 'For multi-part guides. Assign posts to a series under %s, then order them with the page-attributes order field. A "Part 2 of 5" box with previous/next links appears on each post.', 'rodolfo' ),
                                '<a href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=rodolfo_series&post_type=post' ) ) . '">' . esc_html__( 'Posts → Series', 'rodolfo' ) . '</a>'
                            );
                            ?>
                        </p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_feed_full_content" name="rodolfo_feed_full_content" value="1" <?php checked( '1', get_option( 'rodolfo_feed_full_content' ) ); ?> />
                            <label for="rodolfo_feed_full_content"><?php esc_html_e( 'Send full posts to RSS readers', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'Overrides the excerpt setting in Settings → Reading for the theme\'s feeds. Subscribers get the whole post instead of a teaser.', 'rodolfo' ); ?></p>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Reader engagement', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Optional touches for returning readers and long posts. All off by default and self-hosted — no external service, no accounts.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_views_enabled" name="rodolfo_views_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_views_enabled' ) ); ?> />
                            <label for="rodolfo_views_enabled"><?php esc_html_e( 'Count post views', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'One integer per post, stored in post meta. Bots, logged-in editors and repeat visits within an hour do not count. Available to templates via rodolfo_views_get() and rodolfo_popular_posts().', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_views_show_meta" name="rodolfo_views_show_meta" value="1" <?php checked( '1', get_option( 'rodolfo_views_show_meta' ) ); ?> />
                            <label for="rodolfo_views_show_meta"><?php esc_html_e( 'Show the view count in the post meta line', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'Appears beside the date and reading time on single posts. Nothing to display until the counter above is on and has recorded some views.', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_resume_enabled" name="rodolfo_resume_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_resume_enabled' ) ); ?> />
                            <label for="rodolfo_resume_enabled"><?php esc_html_e( 'Remember where readers left off in a post', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'A small "Resume where you left off" pill appears in the corner when a returning reader lands on a post they scrolled at least 15% through. Never scrolls automatically. Stored in the reader\'s own browser (localStorage) — no server, no tracking.', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_privacy_embeds" name="rodolfo_privacy_embeds" value="1" <?php checked( '1', get_option( 'rodolfo_privacy_embeds' ) ); ?> /> <label for="rodolfo_privacy_embeds"><?php esc_html_e( 'Consent-gate video and audio embeds', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'YouTube, Vimeo, SoundCloud and Spotify embeds render as a click-to-load placeholder, so no third-party connection is made until the reader presses play. Faster pages, no tracking cookies on first visit. Readers can opt to always load a provider (remembered in their browser only).', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_comment_guard" name="rodolfo_comment_guard" value="1" <?php checked( '1', get_option( 'rodolfo_comment_guard' ) ); ?> /> <label for="rodolfo_comment_guard"><?php esc_html_e( 'Guard the comment form against bots', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'A hidden honeypot field plus a minimum-time check — zero dependencies, invisible to readers, catches drive-by comment spam. Logged-in users and pingbacks are never checked.', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_sticky_title" name="rodolfo_sticky_title" value="1" <?php checked( '1', get_option( 'rodolfo_sticky_title' ) ); ?> /> <label for="rodolfo_sticky_title"><?php esc_html_e( 'Sticky post title on long reads', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'A slim bar with the post title slides in under the site header once the reader scrolls past the headline. No effect on pages or archives; appears without animation for readers who prefer reduced motion.', 'rodolfo' ); ?></p>

                        <div class="tk-field-grid" style="margin-top:10px">
                            <div><label for="rodolfo_turnstile_site"><?php esc_html_e( 'Cloudflare Turnstile site key (optional)', 'rodolfo' ); ?></label>
                                <input type="text" id="rodolfo_turnstile_site" name="rodolfo_turnstile_site" value="<?php echo esc_attr( get_option( 'rodolfo_turnstile_site' ) ); ?>" autocomplete="off" /></div>
                            <div><label for="rodolfo_turnstile_secret"><?php esc_html_e( 'Turnstile secret key', 'rodolfo' ); ?></label>
                                <input type="password" id="rodolfo_turnstile_secret" name="rodolfo_turnstile_secret" value="<?php echo esc_attr( get_option( 'rodolfo_turnstile_secret' ) ); ?>" autocomplete="new-password" /></div>
                        </div>
                        <p class="description"><?php esc_html_e( 'A second layer for sites under targeted spam. Leave empty to skip it entirely — with keys set, the Turnstile script loads only when a reader focuses the comment field, so clean page views never contact Cloudflare.', 'rodolfo' ); ?></p>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Excerpt length', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'One number does not fit both contexts. Card excerpts on the index and archives read best as a hook, while search results benefit from a longer teaser so a matching keyword actually appears in the snippet.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <label for="rodolfo_excerpt_length_card"><?php esc_html_e( 'Cards (index and archives)', 'rodolfo' ); ?></label>
                            <input type="number" min="5" max="200" step="1" id="rodolfo_excerpt_length_card" name="rodolfo_excerpt_length_card"
                                   value="<?php echo esc_attr( (int) get_option( 'rodolfo_excerpt_length_card', 24 ) ); ?>" style="width:100px" />
                            <span class="description"><?php esc_html_e( 'words. Default: 24.', 'rodolfo' ); ?></span>
                        </div>
                        <div class="tk-field" style="margin-top:10px">
                            <label for="rodolfo_excerpt_length_search"><?php esc_html_e( 'Search results', 'rodolfo' ); ?></label>
                            <input type="number" min="5" max="200" step="1" id="rodolfo_excerpt_length_search" name="rodolfo_excerpt_length_search"
                                   value="<?php echo esc_attr( (int) get_option( 'rodolfo_excerpt_length_search', 40 ) ); ?>" style="width:100px" />
                            <span class="description"><?php esc_html_e( 'words. Default: 40.', 'rodolfo' ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Navigation & freshness', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Two small touches for readers who return often. Both are off by default.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_keynav_enabled" name="rodolfo_keynav_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_keynav_enabled' ) ); ?> />
                            <label for="rodolfo_keynav_enabled"><?php esc_html_e( 'Number the header menu for keyboard access', 'rodolfo' ); ?></label>
                        </div>
                        <p class="description"><?php esc_html_e( 'Pressing 1–9 jumps to the matching header link. Ignored while typing and when a modifier key is held, so browser shortcuts still work. Complements the existing ⌘K search palette.', 'rodolfo' ); ?></p>

                        <div class="tk-toggle-row" style="margin-top:14px">
                            <input type="checkbox" id="rodolfo_site_updated_enabled" name="rodolfo_site_updated_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_site_updated_enabled' ) ); ?> />
                            <label for="rodolfo_site_updated_enabled"><?php esc_html_e( 'Show the latest post date in the footer', 'rodolfo' ); ?></label>
                        </div>
                        <div class="tk-field" style="margin-top:10px">
                            <label for="rodolfo_site_updated_mode"><?php esc_html_e( 'Which date', 'rodolfo' ); ?></label>
                            <select id="rodolfo_site_updated_mode" name="rodolfo_site_updated_mode" style="max-width:320px">
                                <option value="published" <?php selected( 'published', get_option( 'rodolfo_site_updated_mode', 'published' ) ); ?>><?php esc_html_e( 'Last post — when your newest post was published', 'rodolfo' ); ?></option>
                                <option value="modified" <?php selected( 'modified', get_option( 'rodolfo_site_updated_mode', 'published' ) ); ?>><?php esc_html_e( 'Updated — when any post was last edited', 'rodolfo' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( '"Last post" is the honest freshness signal: editing a typo in an old article will not reset it. "Updated" reflects any edit, which suits sites that revise older posts as a matter of course. The label changes to match. Either way, both dates are published as schema on your homepage for search engines, whether or not this stamp is shown.', 'rodolfo' ); ?></p>
                        </div>

                        <p class="description" style="margin-top:14px">
                            <?php esc_html_e( 'Pages can also be marked "coming soon" individually — look for the Coming soon box when editing any Page. The page stays in your menu and shows what is planned, instead of being hidden.', 'rodolfo' ); ?>
                        </p>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Social share cards', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'The preview image generated when a post is shared. Uses the post title and its category accent colour.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <label for="rodolfo_card_variant"><?php esc_html_e( 'Card style', 'rodolfo' ); ?></label>
                            <select id="rodolfo_card_variant" name="rodolfo_card_variant" style="max-width:240px">
                                <option value="light" <?php selected( 'light', get_option( 'rodolfo_card_variant', 'light' ) ); ?>><?php esc_html_e( 'Light (default)', 'rodolfo' ); ?></option>
                                <option value="dark" <?php selected( 'dark', get_option( 'rodolfo_card_variant', 'light' ) ); ?>><?php esc_html_e( 'Dark', 'rodolfo' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Light matches the site\'s default appearance. Choose dark if it suits your branding better.', 'rodolfo' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Sidebar', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'The theme is designed as a single centred column, which is what makes it comfortable to read. A sidebar is supported for sites that need widgets, but it works against that design — leave it off unless you have a specific reason.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_sidebar_enabled" name="rodolfo_sidebar_enabled" value="1" <?php checked( '1', get_option( 'rodolfo_sidebar_enabled' ) ); ?> />
                            <label for="rodolfo_sidebar_enabled"><?php esc_html_e( 'Enable the widget sidebar', 'rodolfo' ); ?></label>
                        </div>
                        <div class="tk-field" style="margin-top:12px">
                            <label for="rodolfo_sidebar_scope"><?php esc_html_e( 'Show it on', 'rodolfo' ); ?></label>
                            <select id="rodolfo_sidebar_scope" name="rodolfo_sidebar_scope" style="max-width:280px">
                                <option value="archives" <?php selected( 'archives', get_option( 'rodolfo_sidebar_scope', 'archives' ) ); ?>><?php esc_html_e( 'Blog index, archives and search', 'rodolfo' ); ?></option>
                                <option value="posts" <?php selected( 'posts', get_option( 'rodolfo_sidebar_scope', 'archives' ) ); ?>><?php esc_html_e( 'Single posts only', 'rodolfo' ); ?></option>
                                <option value="everywhere" <?php selected( 'everywhere', get_option( 'rodolfo_sidebar_scope', 'archives' ) ); ?>><?php esc_html_e( 'Everywhere', 'rodolfo' ); ?></option>
                            </select>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: link to the Widgets screen */
                                    esc_html__( 'Add widgets under %s. If the sidebar is empty it stays hidden, so enabling this alone changes nothing.', 'rodolfo' ),
                                    '<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">' . esc_html__( 'Appearance → Widgets', 'rodolfo' ) . '</a>'
                                );
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <?php
                // ---- Layout & link-equity switches --------------------------------
                rodolfo_opt_select( 'rodolfo_home_layout', __( 'Homepage layout', 'rodolfo' ), __( 'Stream is a single-column editorial feed, Grid a uniform card grid, Magazine a lead post followed by a grid.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_home_content_mode', __( 'Homepage content depth', 'rodolfo' ), __( 'Full post content puts complete articles (and any contextual links in them) on the homepage; long excerpt shows 150+ words; card excerpt is the short teaser.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_header_layout', __( 'Header layout', 'rodolfo' ), __( 'Arrangement of logo and navigation.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_footer_columns', __( 'Footer columns', 'rodolfo' ), __( 'Number of footer columns. Copyright text is set under General; the footer carries no theme attribution.', 'rodolfo' ) );

                // ---- Content / GEO ---------------------------------------------------
                rodolfo_opt_select( 'rodolfo_llms_txt_template', __( 'llms.txt template', 'rodolfo' ), __( 'Four structural variants that differ in headings, bullet style, summary and post limit, so installs do not share one llms.txt signature.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_ps_mode', __( 'Google Preferred Sources', 'rodolfo' ), __( 'A plain text link needs no JavaScript. The button mode loads Google\'s publisher script.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_ps_placement', __( 'Preferred Sources placement', 'rodolfo' ), __( 'Where the link or button appears. The [preferred_source] shortcode works in any mode except Disabled.', 'rodolfo' ) );
                rodolfo_opt_text( 'rodolfo_ps_anchor', __( 'Preferred Sources link text', 'rodolfo' ), __( 'For example: Follow us on Google, or Add as a Preferred Source on Google.', 'rodolfo' ), __( 'Add as a Preferred Source on Google', 'rodolfo' ) );

                // ---- Guest authors -----------------------------------------------
                rodolfo_opt_text( 'rodolfo_ga_default', __( 'Default byline', 'rodolfo' ), __( 'Shown on every post that has no guest author of its own. Leave blank to show each post\'s WordPress author. Per-post names are set in the Guest authors box of the editor.', 'rodolfo' ), '' );
                rodolfo_opt_select( 'rodolfo_ga_join', __( 'Joining several names', 'rodolfo' ), __( 'How multiple guest authors are written.', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_ga_scope', __( 'Guest bylines apply', 'rodolfo' ), __( 'Per-post names can be limited to single post views; the default byline always applies.', 'rodolfo' ) );

                // ---- End mark -----------------------------------------------------
                rodolfo_opt_check( 'rodolfo_em_enabled', __( 'End mark', 'rodolfo' ), __( 'A small symbol after the last paragraph of each article. Turn it off for one post in the End mark box of the editor, or place it by hand with [end_mark].', 'rodolfo' ), __( 'Show the end mark', 'rodolfo' ), '1' );
                rodolfo_opt_check( 'rodolfo_em_pages', __( 'End mark on pages', 'rodolfo' ), __( 'Posts always; tick to include pages.', 'rodolfo' ), __( 'Also on pages', 'rodolfo' ) );
                rodolfo_opt_select( 'rodolfo_em_type', __( 'End mark type', 'rodolfo' ), __( 'A text symbol or a small image.', 'rodolfo' ) );
                rodolfo_opt_text( 'rodolfo_em_symbol', __( 'End mark symbol', 'rodolfo' ), __( 'Any character or short text. Default: ∎', 'rodolfo' ), '∎' );
                rodolfo_opt_text( 'rodolfo_em_image', __( 'End mark image URL', 'rodolfo' ), __( 'Used when the type is Image. Square images work best; SVG is allowed.', 'rodolfo' ), 'https://' );
                rodolfo_opt_text( 'rodolfo_em_size', __( 'End mark image size (px)', 'rodolfo' ), __( 'Width and height of the image, 8–64. Default 16.', 'rodolfo' ), '16' );
                rodolfo_opt_select( 'rodolfo_em_placement', __( 'End mark placement', 'rodolfo' ), __( 'It is never placed inside FAQ, how-to, comparison or proof blocks, tables, lists, quotes or code.', 'rodolfo' ) );
                rodolfo_opt_text( 'rodolfo_em_selector', __( 'End mark selector', 'rodolfo' ), __( 'For "Before a CSS class or id": a simple .class or #id.', 'rodolfo' ), '.my-class' );
                rodolfo_opt_text( 'rodolfo_em_min_words', __( 'End mark minimum length', 'rodolfo' ), __( 'Skip posts shorter than this many words. 0 = always.', 'rodolfo' ), '0' );
                rodolfo_opt_text( 'rodolfo_em_exclude', __( 'End mark excluded post IDs', 'rodolfo' ), __( 'Comma-separated post IDs that never get an end mark.', 'rodolfo' ), '12, 34' );
                rodolfo_opt_text( 'rodolfo_em_margin', __( 'End mark left spacing', 'rodolfo' ), __( 'CSS length, for example .25em or 4px.', 'rodolfo' ), '.25em' );
                rodolfo_opt_check( 'rodolfo_em_hide_small', __( 'End mark on small screens', 'rodolfo' ), __( 'Hide the mark below 768 px wide.', 'rodolfo' ), __( 'Hide on small screens', 'rodolfo' ) );

                // ---- Structured data entity ------------------------------------------
                rodolfo_opt_select( 'rodolfo_org_type', __( 'Publisher entity type', 'rodolfo' ), __( 'Whether the site publisher is described as an Organization or a Person in Schema.org markup.', 'rodolfo' ) );
                rodolfo_opt_text( 'rodolfo_org_name', __( 'Publisher name', 'rodolfo' ), __( 'Leave blank to use the site title.', 'rodolfo' ), '' );
                rodolfo_opt_text( 'rodolfo_org_knows_about', __( 'Topics (knowsAbout)', 'rodolfo' ), __( 'Comma-separated topics the site covers. Social profile URLs from the Social tab are used for sameAs.', 'rodolfo' ), '' );
                rodolfo_opt_select( 'rodolfo_article_type', __( 'Post schema type', 'rodolfo' ), __( 'Schema.org type used for single posts.', 'rodolfo' ) );
                ?>
            </div>

            <!-- SOCIAL TAB -->
            <div class="tk-panel<?php echo 'social' === $active_tab ? ' is-active' : ''; ?>" data-panel="social">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Social links', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'URLs entered here render as icon links in the site footer.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <?php foreach ( rodolfo_social_platforms() as $rodolfo_sk => $rodolfo_sp ) :
                            $rodolfo_stype = isset( $rodolfo_sp[2] ) ? $rodolfo_sp[2] : 'url';
                            ?>
                            <div class="tk-field">
                                <label for="rodolfo_social_<?php echo esc_attr( $rodolfo_sk ); ?>"><?php echo esc_html( $rodolfo_sp[0] ); ?></label>
                                <input type="<?php echo esc_attr( $rodolfo_stype ); ?>" id="rodolfo_social_<?php echo esc_attr( $rodolfo_sk ); ?>" name="rodolfo_social_<?php echo esc_attr( $rodolfo_sk ); ?>"
                                       value="<?php echo esc_attr( get_option( 'rodolfo_social_' . $rodolfo_sk ) ); ?>"
                                       class="regular-text" placeholder="<?php echo esc_attr( $rodolfo_sp[1] ); ?>" />
                                <?php if ( 'email' === $rodolfo_stype ) : ?>
                                    <p class="description"><?php esc_html_e( 'Enter the address only — the mailto: link is built for you.', 'rodolfo' ); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- PERFORMANCE TAB -->
            <div class="tk-panel<?php echo 'performance' === $active_tab ? ' is-active' : ''; ?>" data-panel="performance">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Disable emojis', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Removes the WordPress emoji script and DNS prefetch. Safe to enable if you use plain-text or HTML emojis instead.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_disable_emojis" name="rodolfo_disable_emojis" value="1"
                                   <?php checked( get_option( 'rodolfo_disable_emojis', '1' ), '1' ); ?> />
                            <div class="tk-toggle-info">
                                <label for="rodolfo_disable_emojis"><?php esc_html_e( 'Disable emoji scripts', 'rodolfo' ); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Disable embeds', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Removes the oEmbed script that powers WordPress auto-embeds. Enable if you paste embed codes manually and do not use the auto-embed feature.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_disable_embeds" name="rodolfo_disable_embeds" value="1"
                                   <?php checked( get_option( 'rodolfo_disable_embeds', '1' ), '1' ); ?> />
                            <div class="tk-toggle-info">
                                <label for="rodolfo_disable_embeds"><?php esc_html_e( 'Disable oEmbed scripts', 'rodolfo' ); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Disable jQuery Migrate', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Removes the jQuery Migrate compatibility script. Safe if all your plugins and the theme do not use deprecated jQuery APIs.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-toggle-row">
                            <input type="checkbox" id="rodolfo_disable_jquery_migrate" name="rodolfo_disable_jquery_migrate" value="1"
                                   <?php checked( get_option( 'rodolfo_disable_jquery_migrate' ), '1' ); ?> />
                            <div class="tk-toggle-info">
                                <label for="rodolfo_disable_jquery_migrate"><?php esc_html_e( 'Disable jQuery Migrate', 'rodolfo' ); ?></label>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                rodolfo_opt_check( 'rodolfo_inline_css', __( 'Inline the theme CSS', 'rodolfo' ), __( 'Prints the core stylesheet inside a style tag so the page needs no render-blocking CSS request.', 'rodolfo' ), __( 'Inline the core stylesheet', 'rodolfo' ), '' );
                rodolfo_opt_check( 'rodolfo_font_woff2', __( 'Self-hosted WOFF2 font', 'rodolfo' ), __( 'Off by default: the theme uses the native system font stack (zero bytes). Enable only if you add WOFF2 subsets to assets/fonts/.', 'rodolfo' ), __( 'Load self-hosted WOFF2 fonts', 'rodolfo' ), '' );
                rodolfo_opt_check( 'rodolfo_dequeue_block_library', __( 'Block library CSS', 'rodolfo' ), __( 'Removes wp-block-library styles on the front end. Leave off if your posts use core blocks that need their styles.', 'rodolfo' ), __( 'Dequeue wp-block-library', 'rodolfo' ), '1' );
                rodolfo_opt_check( 'rodolfo_dequeue_global_styles', __( 'Global styles', 'rodolfo' ), __( 'Removes the inline global-styles and SVG filter output added by core.', 'rodolfo' ), __( 'Dequeue global-styles', 'rodolfo' ), '1' );
                ?>
            </div>

            <!-- SCRIPTS TAB -->
            <div class="tk-panel<?php echo 'features' === $active_tab ? ' is-active' : ''; ?>" data-panel="features">
                <div class="tk-section">
                    <div class="tk-section-head">
                        <h2><?php esc_html_e( 'Feature modules', 'rodolfo' ); ?></h2>
                        <p><?php esc_html_e( 'Switch off what this site does not use — a disabled module is never loaded at all, so it costs nothing. Structural modules cannot be disabled. Content written with a disabled feature degrades politely: shortcodes render as plain text, virtual routes 404.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-body">
                        <?php foreach ( rodolfo_optional_modules() as $rodolfo_slug => $rodolfo_label ) : ?>
                        <div class="tk-toggle-row" style="margin-top:8px">
                            <?php // Inverted storage: the option is rodolfo_disable_<slug>, so an unsaved install has everything ON. The hidden input submits '1' (disable) unless the checkbox — value '' — overrides it. ?>
                            <input type="hidden" name="rodolfo_disable_<?php echo esc_attr( $rodolfo_slug ); ?>" value="1" />
                            <input type="checkbox" id="rodolfo_disable_<?php echo esc_attr( $rodolfo_slug ); ?>" name="rodolfo_disable_<?php echo esc_attr( $rodolfo_slug ); ?>" value="" <?php checked( ! get_option( 'rodolfo_disable_' . $rodolfo_slug ) ); ?> /> <label for="rodolfo_disable_<?php echo esc_attr( $rodolfo_slug ); ?>"><?php echo esc_html( $rodolfo_label ); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="tk-panel<?php echo 'scripts' === $active_tab ? ' is-active' : ''; ?>" data-panel="scripts">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Header scripts', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Output immediately before the closing </head> tag on every page. Wrap code in <script> tags.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <textarea id="rodolfo_header_scripts" name="rodolfo_header_scripts"
                                      rows="6" class="large-text code"
                                      placeholder="<!-- e.g. analytics, tag managers -->"><?php echo esc_textarea( get_option( 'rodolfo_header_scripts' ) ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Body scripts', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Output immediately after the opening <body> tag on every page. Useful for GTM noscript snippets.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <textarea id="rodolfo_body_scripts" name="rodolfo_body_scripts"
                                      rows="6" class="large-text code"
                                      placeholder="<!-- e.g. GTM noscript iframe -->"><?php echo esc_textarea( get_option( 'rodolfo_body_scripts' ) ); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'Footer scripts', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Output immediately before the closing </body> tag on every page.', 'rodolfo' ); ?></p>
                    </div>
                    <div class="tk-section-control">
                        <div class="tk-field">
                            <textarea id="rodolfo_footer_scripts" name="rodolfo_footer_scripts"
                                      rows="6" class="large-text code"
                                      placeholder="<!-- e.g. chat widgets, deferred analytics -->"><?php echo esc_textarea( get_option( 'rodolfo_footer_scripts' ) ); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SYSTEM INFO TAB (no form fields — read only) -->
            <div class="tk-panel<?php echo 'system' === $active_tab ? ' is-active' : ''; ?>" data-panel="system">
                <div class="tk-section">
                    <div class="tk-section-label">
                        <h3><?php esc_html_e( 'System status', 'rodolfo' ); ?></h3>
                        <p><?php esc_html_e( 'Diagnostics for troubleshooting the theme: whether each feature has what it needs to run, and which settings are affecting the site. Download the JSON when reporting an issue.', 'rodolfo' ); ?></p>
                        <p><?php
                            printf(
                                /* translators: %s: link to Site Health */
                                esc_html__( 'For general environment checks (server, database, plugin conflicts) use %s.', 'rodolfo' ),
                                '<a href="' . esc_url( admin_url( 'site-health.php' ) ) . '">' . esc_html__( 'Tools → Site Health', 'rodolfo' ) . '</a>'
                            );
                        ?></p>
                        <p>
                            <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rodolfo_system_report' ), 'rodolfo_system_report' ) ); ?>">
                                <?php esc_html_e( 'Download system report (JSON)', 'rodolfo' ); ?>
                            </a>
                        </p>
                    </div>
                    <div class="tk-section-control">
                        <table class="tk-sysinfo">
                            <?php foreach ( rodolfo_system_report() as $rodolfo_sec ) : ?>
                                <tr class="tk-sysinfo-group"><td colspan="2"><?php echo esc_html( $rodolfo_sec['label'] ); ?></td></tr>
                                <?php foreach ( $rodolfo_sec['rows'] as $rodolfo_h => $rodolfo_v ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $rodolfo_h ); ?></td>
                                        <td><?php echo nl2br( esc_html( $rodolfo_v ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tk-form-footer">
                <div class="button-group">
                    <?php submit_button( __( 'Save Settings', 'rodolfo' ), 'primary', 'submit', false ); ?>
                    <button type="submit" form="tk-reset-form" class="button"
                            onclick="return confirm('<?php echo esc_js( __( 'Reset all settings to defaults? This cannot be undone.', 'rodolfo' ) ); ?>')">
                        <?php esc_html_e( 'Reset Settings', 'rodolfo' ); ?>
                    </button>
                </div>
            </div>

        </form>

        <?php
        // The reset form lives OUTSIDE the settings form — nested forms are
        // invalid HTML and browsers drop the inner tag, which made the Reset
        // button submit the settings form instead. The button above targets
        // this form via its HTML5 form attribute.
        ?>
        <form id="tk-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'rodolfo_reset_settings' ); ?>
            <input type="hidden" name="action" value="rodolfo_reset_settings" />
            <input type="hidden" name="rodolfo_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
        </form>

        <?php // Setup runner — also outside the settings form (see note above). ?>
        <form id="tk-setup-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'rodolfo_run_setup' ); ?>
            <input type="hidden" name="action" value="rodolfo_run_setup" />
        </form>
    </div>
    <?php
}
