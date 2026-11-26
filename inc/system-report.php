<?php
/**
 * System report — diagnostics for troubleshooting **this theme**.
 *
 * Scope is the point. WordPress already ships Site Health (Tools → Site Health)
 * for auditing the environment, so this deliberately does not restate server
 * paths, plugin inventories, database tables or unrelated constants: a theme
 * has no business reporting on them, and duplicating Site Health would bury the
 * handful of rows that actually explain Rodolfo's behaviour.
 *
 * What is here answers theme questions:
 *   - Environment  — only versions a theme feature depends on.
 *   - Feature health — is each optional feature actually able to run? (GD for
 *     social cards, permalinks for virtual routes, an SEO plugin standing the
 *     theme's meta tags down, which Markdown tier the docs viewer resolved.)
 *   - Active settings — values that explain reported behaviour, e.g. an
 *     assigned menu silently overriding the Navigation tab.
 *   - Utility pages — whether the pages the footer links to exist and have content.
 *
 * One source of truth: rodolfo_system_report() returns the data; the options
 * page renders it and the admin_post handler streams the same array as JSON.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The report: array of sections, each { label, rows: { header => value } }.
 *
 * Scope is deliberately narrow — **only what helps diagnose this theme**.
 * WordPress ships Site Health (Tools → Site Health) for general environment
 * auditing, and duplicating it here would mean a theme reporting on inactive
 * plugins, trash retention and table prefixes it has no relationship to. Every
 * row below exists because some Rodolfo feature depends on it, is disabled by
 * it, or is a setting whose value explains reported behaviour.
 *
 * Filterable so child themes can append their own section.
 */
function rodolfo_system_report() {

    // --- Environment: only what a theme feature actually depends on ------
    $gd_ok = extension_loaded( 'gd' ) && function_exists( 'imagettftext' );
    $font  = get_template_directory() . '/assets/fonts/Inter-Bold.ttf';

    $environment = array(
        __( 'Theme Version', 'rodolfo' )     => RODOLFO_VERSION,
        __( 'Child Theme', 'rodolfo' )       => is_child_theme()
            ? sprintf( /* translators: %s: child theme name */ __( 'Yes — %s', 'rodolfo' ), wp_get_theme()->get( 'Name' ) )
            : __( 'No', 'rodolfo' ),
        __( 'WordPress Version', 'rodolfo' ) => get_bloginfo( 'version' ),
        __( 'PHP Version', 'rodolfo' )       => PHP_VERSION,
        // The theme's own CSS/JS are versioned off this; a stale cache is a
        // common "my change did not appear" cause.
        __( 'Asset Cache Key', 'rodolfo' )   => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
            ? __( 'SCRIPT_DEBUG on — serving unminified sources', 'rodolfo' )
            : __( 'Serving minified assets', 'rodolfo' ),
    );

    // --- Feature health: does each optional feature have what it needs? --
    $seo_active = function_exists( 'rodolfo_seo_plugin_active' ) && rodolfo_seo_plugin_active();
    $permalinks = get_option( 'permalink_structure' );

    $features = array(
        // GD drives the generated share card; without FreeType there is no text.
        __( 'Social Cards', 'rodolfo' ) => $gd_ok
            ? __( 'Working — GD with FreeType available', 'rodolfo' )
            : __( 'Disabled — GD or FreeType missing, so cards cannot be drawn', 'rodolfo' ),
        // Meta tags + schema stand down when an SEO plugin is present. This is
        // the single most common "why is the theme not outputting X" question.
        __( 'Meta Tags & Schema', 'rodolfo' ) => $seo_active
            ? sprintf(
                /* translators: %s: detected SEO plugin name */
                __( 'Standing down — %s is active and owns these', 'rodolfo' ),
                function_exists( 'rodolfo_detected_seo_plugin' ) ? rodolfo_detected_seo_plugin() : __( 'an SEO plugin', 'rodolfo' )
            )
            : __( 'Active — theme is outputting meta tags and JSON-LD', 'rodolfo' ),
        // Virtual routes need pretty permalinks; on Plain they 404 silently.
        __( 'Virtual Routes (/tags/, /llms.txt)', 'rodolfo' ) => $permalinks
            ? __( 'Working — pretty permalinks enabled', 'rodolfo' )
            : __( 'Broken — permalinks are set to Plain, so these routes 404', 'rodolfo' ),
        __( 'Self-hosted Inter', 'rodolfo' ) => is_readable( $font )
            ? __( 'Present', 'rodolfo' )
            : __( 'Not bundled — falling back to system sans-serif', 'rodolfo' ),
    );

    // --- Settings whose values explain reported behaviour ----------------
    $maint = get_option( 'rodolfo_maintenance_mode' );
    $settings = array(
        __( 'Maintenance Mode', 'rodolfo' ) => $maint
            ? sprintf(
                /* translators: %s: maintenance mode value */
                __( 'ON (%s) — visitors see the holding page', 'rodolfo' ),
                'maintenance' === $maint ? __( 'Maintenance, 503', 'rodolfo' ) : __( 'Coming soon, 200', 'rodolfo' )
            )
            : __( 'Off', 'rodolfo' ),
        // An assigned menu silently overrides the Navigation tab entirely.
        __( 'Navigation Source', 'rodolfo' ) => ( has_nav_menu( 'primary' ) || has_nav_menu( 'secondary' ) )
            ? __( 'WordPress menu assigned — Theme Options → Navigation is overridden', 'rodolfo' )
            : __( 'Theme Options → Navigation (no menu assigned)', 'rodolfo' ),
        __( 'Appearance Default', 'rodolfo' ) => __( 'Light (dark mode is opt-in per visitor)', 'rodolfo' ),
        __( 'Reading Bar', 'rodolfo' )  => get_option( 'rodolfo_progress_enabled', '1' )
            ? sprintf( /* translators: %d: height in pixels */ __( 'On, %dpx', 'rodolfo' ), (int) get_option( 'rodolfo_progress_height', 4 ) )
            : __( 'Off', 'rodolfo' ),
        __( 'Reading Time', 'rodolfo' ) => get_option( 'rodolfo_reading_time_enabled', '1' ) ? __( 'On', 'rodolfo' ) : __( 'Off', 'rodolfo' ),
        __( 'Series Nav', 'rodolfo' )   => get_option( 'rodolfo_series_enabled' ) ? __( 'On', 'rodolfo' ) : __( 'Off', 'rodolfo' ),
        __( 'Full-content RSS', 'rodolfo' ) => get_option( 'rodolfo_feed_full_content' ) ? __( 'On', 'rodolfo' ) : __( 'Off', 'rodolfo' ),
        __( 'Sidebar', 'rodolfo' ) => get_option( 'rodolfo_sidebar_enabled' )
            ? ( is_active_sidebar( 'tk-sidebar' )
                ? sprintf( /* translators: %s: scope */ __( 'On (%s)', 'rodolfo' ), (string) get_option( 'rodolfo_sidebar_scope', 'archives' ) )
                : __( 'Enabled but empty — nothing renders until a widget is added', 'rodolfo' ) )
            : __( 'Off', 'rodolfo' ),
        __( 'Custom Scripts', 'rodolfo' ) => ( get_option( 'rodolfo_header_scripts' ) || get_option( 'rodolfo_body_scripts' ) || get_option( 'rodolfo_footer_scripts' ) )
            ? __( 'Present — check these first when debugging front-end JS', 'rodolfo' )
            : __( 'None', 'rodolfo' ),
    );

    // --- Utility pages the theme links to --------------------------------
    $pages = array();
    if ( function_exists( 'rodolfo_setup_status' ) ) {
        foreach ( rodolfo_setup_status() as $item ) {
            $pages[ $item['title'] ] = ! $item['exists']
                ? __( 'Missing — footer link is skipped', 'rodolfo' )
                : ( ! empty( $item['empty'] ) ? __( 'Present but empty', 'rodolfo' ) : __( 'Present', 'rodolfo' ) );
        }
    }

    // --- Recommendations: advice, not just diagnostics -------------------
    // Each row is a check the theme has a stake in — a Rodolfo feature
    // degrades, or a common WordPress default undermines one. Rows keep the
    // report's label => string shape so the JSON export needs no changes.
    $ok   = "\xE2\x9C\x93 "; // ✓
    $warn = "\xE2\x9A\xA0 "; // ⚠
    $recommendations = array();

    $recommendations[ __( 'Permalinks', 'rodolfo' ) ] = $permalinks
        ? $ok . __( 'Pretty permalinks — /tags/ and /llms.txt use their clean routes', 'rodolfo' )
        : $warn . __( 'Plain permalinks — /llms.txt is served via the request-path fallback and /tags/ links fall back to query strings. Pretty permalinks recommended (Settings → Permalinks).', 'rodolfo' );

    $recommendations[ __( 'Debug output', 'rodolfo' ) ] = ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY )
        ? $warn . __( 'WP_DEBUG with on-screen display is enabled — fine locally, but on a live site notices leak into pages and feeds. Set WP_DEBUG_DISPLAY to false.', 'rodolfo' )
        : $ok . __( 'No debug output reaches visitors', 'rodolfo' );

    $recommendations[ __( 'Asset minification', 'rodolfo' ) ] = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
        ? $warn . __( 'SCRIPT_DEBUG is on — every asset is served unminified. Intended for development only.', 'rodolfo' )
        : $ok . __( 'Serving minified assets', 'rodolfo' );

    $recommendations[ __( 'Comment pagination', 'rodolfo' ) ] = get_option( 'page_comments' )
        ? $ok . __( 'Comments break into pages', 'rodolfo' )
        : $warn . __( 'Comments render as one unbroken list — on a popular post that means hundreds of comments on one page load. Enable "Break comments into pages" (Settings → Discussion).', 'rodolfo' );

    if ( function_exists( 'get_num_queries' ) ) {
        global $wpdb;
        $autoload_bytes = (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto')" );
        $recommendations[ __( 'Autoloaded options', 'rodolfo' ) ] = ( $autoload_bytes > 800 * KB_IN_BYTES )
            ? $warn . sprintf( /* translators: %s: size, e.g. 1.2 MB */ __( '%s load on every request — usually residue from removed plugins. Worth an audit.', 'rodolfo' ), size_format( $autoload_bytes ) )
            : $ok . sprintf( /* translators: %s: size */ __( '%s — healthy', 'rodolfo' ), size_format( max( 0, $autoload_bytes ) ) );
    }

    if ( get_option( 'rodolfo_views_enabled' ) ) {
        $has_views = (bool) get_transient( 'rodolfo_has_view_data' );
        if ( ! $has_views ) {
            global $wpdb;
            $has_views = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1", '_rodolfo_views' ) );
            if ( $has_views ) {
                set_transient( 'rodolfo_has_view_data', 1, DAY_IN_SECONDS );
            }
        }
        $recommendations[ __( 'View counting', 'rodolfo' ) ] = $has_views
            ? $ok . __( 'Recording — rodolfo_popular_posts() and rodolfo_trending_posts() have data to rank', 'rodolfo' )
            : $ok . __( 'Enabled — counting begins with the next uncached, non-editor visit', 'rodolfo' );
    }

    /** Filter the recommendation rows. */
    $recommendations = apply_filters( 'rodolfo_system_recommendations', $recommendations );

    $sections = array(
        'environment'     => array( 'label' => __( 'Environment', 'rodolfo' ),     'rows' => $environment ),
        'features'        => array( 'label' => __( 'Feature health', 'rodolfo' ),  'rows' => $features ),
        'recommendations' => array( 'label' => __( 'Recommendations', 'rodolfo' ), 'rows' => $recommendations ),
        'settings'        => array( 'label' => __( 'Active settings', 'rodolfo' ), 'rows' => $settings ),
    );
    if ( $pages ) {
        $sections['pages'] = array( 'label' => __( 'Utility pages', 'rodolfo' ), 'rows' => $pages );
    }

    /** Filter the system report sections. */
    return apply_filters( 'rodolfo_system_report', $sections );
}

/**
 * Stream the report as a downloadable JSON, mirroring the export format
 * support teams expect: { section: { label, results: [ {header, value} ] } }.
 */
function rodolfo_system_report_download() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'rodolfo' ) );
    }
    check_admin_referer( 'rodolfo_system_report' );

    $out = array();
    foreach ( rodolfo_system_report() as $key => $section ) {
        $results = array();
        foreach ( $section['rows'] as $header => $value ) {
            $results[] = array( 'header' => $header, 'value' => $value );
        }
        $out[ $key ] = array( 'label' => $section['label'], 'results' => $results );
    }

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=tk-system-status-' . gmdate( 'Y-m-d' ) . '.json' );
    echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    exit;
}
add_action( 'admin_post_rodolfo_system_report', 'rodolfo_system_report_download' );
