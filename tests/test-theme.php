<?php
/**
 * Rodolfo test harness — runs WITHOUT a WordPress installation.
 *
 *   php tests/test-theme.php
 *
 * Every assertion here corresponds to a defect that actually shipped. When a
 * regression is found the hard way, add a test for it in the same release —
 * that is the entire point of the suite. These are static source checks
 * (substring / regex over the theme files), not runtime tests.
 *
 * @package Rodolfo
 */

$root  = dirname( __DIR__ );
$pass  = 0;
$fail  = 0;
$fails = array();

function rd_read( $path ) {
    global $root;
    $full = $root . '/' . $path;
    return is_readable( $full ) ? file_get_contents( $full ) : '';
}

function rd_check( $label, $cond ) {
    global $pass, $fail, $fails;
    if ( $cond ) {
        $pass++;
    } else {
        $fail++;
        $fails[] = $label;
    }
}

$style    = rd_read( 'style.css' );
$header   = rd_read( 'header.php' );
$footer   = rd_read( 'footer.php' );
// Core code: functions.php plus the structural modules extracted from it in
// 3.4.0. Assertions against $funcs test behaviour, not file layout, so a pure
// move must not fail them.
$funcs    = rd_read( 'functions.php' )
          . rd_read( 'inc/icons.php' )
          . rd_read( 'inc/nav.php' )
          . rd_read( 'inc/settings.php' )
          . rd_read( 'inc/options-actions.php' )
          . rd_read( 'inc/options-page.php' )
          . rd_read( 'inc/maintenance.php' )
          . rd_read( 'inc/performance.php' );
$index    = rd_read( 'index.php' );
$card     = rd_read( 'template-parts/content/content-card.php' );

// 1.6.1 — pagination class is REPLACED by a custom class arg; CSS must target
// .navigation / .page-numbers, never .pagination.
rd_check( 'pagination CSS targets .navigation', strpos( $style, '.navigation .page-numbers' ) !== false );
rd_check( 'pagination CSS does not rely on .pagination selector', strpos( $style, '.pagination .page-numbers' ) === false );

// 1.11.1 — header logo is a constrained <img> (.rodolfo-logo-img), never a bare
// the_custom_logo() (which would nest an <a> and render at native size).
rd_check( 'header uses has_custom_logo guard', strpos( $header, 'has_custom_logo()' ) !== false );
rd_check( 'header constrains logo via .rodolfo-logo-img', strpos( $header, 'rodolfo-logo-img' ) !== false );
rd_check( 'header never OUTPUTS the_custom_logo()', preg_match( '/the_custom_logo\s*\(\s*\)\s*;/', $header ) === 0 );

// 1.6.1 — nav must fall back through the shared helper when a menu is empty.
rd_check( 'primary nav helper exists', strpos( $funcs, 'function rodolfo_primary_nav_links' ) !== false );
rd_check( 'header uses primary nav helper', strpos( $header, 'rodolfo_primary_nav_links' ) !== false );

// 1.5.0 — no CDN icon library, no remote fonts.
foreach ( array( 'header.php', 'footer.php', 'functions.php', 'index.php', 'search.php', 'archive.php', 'category.php', 'tag.php', '404.php' ) as $f ) {
    $src = rd_read( $f );
    rd_check( "no data-lucide in $f", strpos( $src, 'data-lucide' ) === false );
    rd_check( "no unpkg/lucide CDN in $f", strpos( $src, 'unpkg.com' ) === false );
    rd_check( "no Google Fonts in $f", strpos( $src, 'fonts.googleapis' ) === false );
}

// 1.5.0 — admin email must never leak as a Gravatar hash in public templates.
rd_check( 'no admin-email md5 gravatar in header', strpos( $header, 'md5(' ) === false );
rd_check( 'no admin-email md5 gravatar in footer', strpos( $footer, 'md5(' ) === false );

// 1.5.1 — footer bottom bar must go side-by-side at md (utility present in style.css).
rd_check( 'style.css defines md:flex-row', strpos( $style, '.md\\:flex-row' ) !== false );

// 1.5.0 — every option must have a sanitizer entry.
rd_check( 'option sanitizer map present', strpos( $funcs, 'function rodolfo_option_sanitizers' ) !== false );
foreach ( array( 'rodolfo_footer_text', 'rodolfo_social_twitter', 'rodolfo_maintenance_mode', 'rodolfo_nav_locations', 'rodolfo_link_sitemap' ) as $opt ) {
    rd_check( "sanitizer registered for $opt", strpos( $funcs, "'$opt'" ) !== false );
}

// 1.7.0 — SEO/schema modules self-disable when a plugin owns head meta.
$meta   = rd_read( 'inc/meta-tags.php' );
$schema = rd_read( 'inc/schema.php' );
rd_check( 'meta module guards on rodolfo_seo_plugin_active', strpos( $meta, 'rodolfo_seo_plugin_active' ) !== false );
rd_check( 'schema module guards on rodolfo_seo_plugin_active', strpos( $schema, 'rodolfo_seo_plugin_active' ) !== false );

// 1.7.0 — category colour must validate the picked hex and always return one.
$catcol = rd_read( 'inc/category-color.php' );
rd_check( 'category colour validates hex', strpos( $catcol, '#[0-9a-fA-F]{6}' ) !== false );
rd_check( 'category colour has a palette fallback', strpos( $catcol, 'rodolfo_category_palette' ) !== false );

// 1.7.0 — setup must never delete; guard against a stray delete/trash call.
$setup = rd_read( 'inc/setup.php' );
rd_check( 'setup never deletes posts', strpos( $setup, 'wp_delete_post' ) === false );
rd_check( 'setup never trashes posts', strpos( $setup, 'wp_trash_post' ) === false );

// 1.7.0 / 2.0.x — all inc/ modules are actually loaded. Modules load via a
// foreach loop over their basenames, so assert the basename appears in the
// module list AND the file exists on disk.
foreach ( array( 'meta-tags', 'schema', 'social-card', 'favicon', 'category-color', 'toc', 'related-posts', 'search-palette', 'tags', 'fonts', 'setup' ) as $mod ) {
    rd_check( "functions.php loads inc/$mod.php", strpos( $funcs, "'$mod'," ) !== false || strpos( $funcs, "'$mod' " ) !== false );
    rd_check( "inc/$mod.php exists", rd_read( "inc/$mod.php" ) !== '' );
}

// Every source asset must have a .min twin (asset-suffix discipline).
rd_check( 'main.min.js exists', rd_read( 'assets/js/main.min.js' ) !== '' );

// 1.7.1 — Inter is bundled and fonts.php points at the TTF.
rd_check( 'Inter variable TTF bundled', is_readable( $root . '/assets/fonts/Inter-Variable.ttf' ) );
rd_check( 'OFL licence bundled', is_readable( $root . '/assets/fonts/OFL.txt' ) );
rd_check( 'fonts.php references the TTF', strpos( rd_read( 'inc/fonts.php' ), 'Inter-Variable.ttf' ) !== false );

// 1.8.0 — Tailwind v4 build source present and CSS-first (no tailwind.config.js).
$input = rd_read( 'assets/css/input.css' );
rd_check( 'input.css uses v4 @import', strpos( $input, '@import "tailwindcss"' ) !== false );
rd_check( 'no tailwind.config.js (v4 is CSS-first)', ! is_readable( $root . '/tailwind.config.js' ) );
rd_check( 'package.json has a build script', strpos( rd_read( 'package.json' ), '"build"' ) !== false );

// 1.8.0 — brand accent must be mirrored in BOTH the build source and the
// runtime layer, or they drift.
rd_check( 'input.css remaps blue ramp to brand', strpos( $input, '--color-blue-600: #3d54d6' ) !== false );
rd_check( 'style.css defines the runtime accent', strpos( $style, '--rodolfo-accent: #3d54d6' ) !== false );
rd_check( 'style.css defines the display serif', strpos( $style, '.font-display' ) !== false );

// 1.8.0 — TOC and related-posts modules wired and used.
rd_check( 'toc module defines rodolfo_the_toc', strpos( rd_read( 'inc/toc.php' ), 'function rodolfo_the_toc' ) !== false );
rd_check( 'single template renders the TOC', strpos( rd_read( 'template-parts/content/content-single.php' ), 'rodolfo_the_toc' ) !== false );
rd_check( 'related module defines renderer', strpos( rd_read( 'inc/related-posts.php' ), 'function rodolfo_render_related_posts' ) !== false );
rd_check( 'single.php renders related posts', strpos( rd_read( 'single.php' ), 'rodolfo_render_related_posts' ) !== false );

// 1.10.0 — image-less placeholder + author card.
rd_check( 'category-color defines thumbnail placeholder', strpos( rd_read( 'inc/category-color.php' ), 'function rodolfo_post_thumbnail' ) !== false );
rd_check( 'author card renderer exists', strpos( $funcs, 'function rodolfo_render_author_card' ) !== false );
rd_check( 'single.php renders author card', strpos( rd_read( 'single.php' ), 'rodolfo_render_author_card' ) !== false );

// 1.10.2 — homepage featured hero is a balanced two-column block, and its
// image uses a utility present in the shipped compiled sheet (aspect-[16/10]).
rd_check( 'index.php featured hero is 2-col', strpos( rd_read( 'index.php' ), 'md:grid-cols-2' ) !== false );
rd_check( 'featured hero uses items-center balance', strpos( rd_read( 'index.php' ), 'items-center' ) !== false );

// 1.10.3 — the search overlay must not capture clicks when closed, or every
// link on the page dies. The hidden-state display:none guard must be present.
rd_check( 'search palette hidden guard present', strpos( $style, '.rodolfo-search[hidden]' ) !== false );

// 1.10.4 — shared brand mark (header/footer parity) and card row-gap must not
// depend on un-compiled utilities.
rd_check( 'brand mark class defined', strpos( $style, '.rodolfo-brand-mark' ) !== false );
rd_check( 'header uses brand mark', strpos( $header, 'rodolfo-brand-mark' ) !== false );
rd_check( 'footer uses brand mark', strpos( $footer, 'rodolfo-brand-mark' ) !== false );
rd_check( 'card grid row-gap defined', strpos( $style, '.rodolfo-cards' ) !== false );

// 2.1.0 — primary nav is Page-driven; per-page exclusion moves a page to the
// secondary (footer) nav, controlled from Theme Options → Navigation.
rd_check( 'primary nav pages helper', strpos( $funcs, 'function rodolfo_primary_nav_pages' ) !== false );
rd_check( 'secondary nav pages helper', strpos( $funcs, 'function rodolfo_secondary_nav_pages' ) !== false );
rd_check( 'per-page location option sanitized', strpos( $funcs, "'rodolfo_nav_locations'" ) !== false );
rd_check( 'per-page order option sanitized', strpos( $funcs, "'rodolfo_nav_order'" ) !== false );
rd_check( 'footer built-in links option sanitized', strpos( $funcs, "'rodolfo_footer_util'" ) !== false );
rd_check( 'built-in links include FAQ + Terms of Use', strpos( $funcs, "'faq'" ) !== false && strpos( $funcs, 'Terms of Use' ) !== false );
rd_check( 'built-in links are renamable (label field)', strpos( $funcs, '][label]' ) !== false );
rd_check( 'setup scaffolds FAQ + Terms pages', strpos( rd_read( 'inc/setup.php' ), "'faq'" ) !== false && strpos( rd_read( 'inc/setup.php' ), "'terms'" ) !== false );
rd_check( 'location sanitizer defined', strpos( $funcs, 'function rodolfo_sanitize_nav_locations' ) !== false );
rd_check( 'options UI has Navigation section', strpos( $funcs, "esc_html_e( 'Navigation', 'rodolfo' )" ) !== false );
rd_check( 'footer uses ordered nav items', strpos( $footer, 'rodolfo_footer_nav_items()' ) !== false );
rd_check( 'legacy excluded-pages option migrated away', strpos( $funcs, 'rodolfo_migrate_nav_excluded' ) !== false );
rd_check( 'legacy terms URL option removed', strpos( $funcs, "'rodolfo_link_terms'" ) === false );
rd_check( 'primary nav no longer injects a Tags link', strpos( $funcs, "__( 'Tags', 'rodolfo' )" ) === false );

// 2.6.0 — data-driven social platform registry.
rd_check( 'social platform registry defined', strpos( $funcs, 'function rodolfo_social_platforms' ) !== false );
rd_check( 'options UI loops the registry', strpos( $funcs, 'foreach ( rodolfo_social_platforms()' ) !== false );
rd_check( 'footer loops the registry', strpos( $footer, 'rodolfo_social_platforms()' ) !== false );
foreach ( array( 'github', 'facebook', 'instagram', 'youtube', 'tiktok', 'threads', 'pinterest', 'reddit', 'whatsapp', 'telegram', 'medium', 'substack' ) as $sp ) {
    rd_check( "platform $sp registered + sanitized", strpos( $funcs, "'rodolfo_social_$sp'" ) !== false );
}
rd_check( 'github option restored', strpos( $funcs, "'rodolfo_social_github'" ) !== false );
rd_check( 'gitlab removed', strpos( $funcs, "'rodolfo_social_gitlab'" ) === false );

// 3.1.0 — styled, customisable maintenance page.
rd_check( 'maintenance uses wp_die', strpos( $funcs, 'wp_die(' ) !== false );
rd_check( 'themed wp_die handler', strpos( $funcs, 'function rodolfo_maintenance_die_handler' ) !== false );
rd_check( 'wp_die_handler filter registered', strpos( $funcs, "add_filter( 'wp_die_handler'" ) !== false );
rd_check( 'maintenance copy helper', strpos( $funcs, 'function rodolfo_maintenance_copy' ) !== false );
rd_check( 'maintenance copy filterable', strpos( $funcs, "apply_filters( 'rodolfo_maintenance_copy'" ) !== false );
rd_check( 'maintenance title option', strpos( $funcs, "'rodolfo_maintenance_title'" ) !== false );
rd_check( 'maintenance body option', strpos( $funcs, "'rodolfo_maintenance_body'" ) !== false );
rd_check( 'handler sets status', strpos( $funcs, 'status_header( $status )' ) !== false );
rd_check( 'handler sends noindex', strpos( $funcs, "'noindex, nofollow'" ) !== false );

// 3.4.0 — functions.php split into structural modules.
$fp_only = rd_read( 'functions.php' );
rd_check( 'functions.php is lean (<420 lines)', substr_count( $fp_only, "\n" ) < 420 ); // Budget 400 → 420 (Phase 7): the feature-toggle whitelist + loader check and Phase 1's wp_localize_script block are bootstrap infrastructure, which is exactly what this file is for. The guard still exists to keep FEATURE code out; raise it again only for loader/bootstrap growth.
foreach ( array( 'icons', 'nav', 'settings', 'options-actions', 'options-page', 'maintenance', 'performance' ) as $sm ) {
    rd_check( "structural module $sm exists", file_exists( dirname( __DIR__ ) . "/inc/$sm.php" ) );
    rd_check( "structural module $sm loaded", strpos( $fp_only, "'$sm'," ) !== false );
}
rd_check( 'icons module owns rodolfo_icon', strpos( rd_read( 'inc/icons.php' ), 'function rodolfo_icon' ) !== false );
rd_check( 'nav module owns privacy resolver', strpos( rd_read( 'inc/nav.php' ), 'function rodolfo_privacy_page' ) !== false );
rd_check( 'author card stayed in functions.php', strpos( $fp_only, 'function rodolfo_render_author_card' ) !== false );

// 3.8.0 — options-page split into settings / actions / render.
$set = rd_read( 'inc/settings.php' );
$act = rd_read( 'inc/options-actions.php' );
$opg = rd_read( 'inc/options-page.php' );
rd_check( 'settings owns the sanitizer map', strpos( $set, 'function rodolfo_option_sanitizers' ) !== false );
rd_check( 'settings owns registration', strpos( $set, 'function rodolfo_register_settings' ) !== false );
rd_check( 'actions owns export/import/reset', strpos( $act, 'function rodolfo_export_settings' ) !== false && strpos( $act, 'function rodolfo_reset_settings' ) !== false );
rd_check( 'actions owns the menu', strpos( $act, 'function rodolfo_theme_options_menu' ) !== false );
rd_check( 'page is render only', strpos( $opg, 'function rodolfo_theme_options_page' ) !== false && strpos( $opg, 'function rodolfo_option_sanitizers' ) === false );
rd_check( 'settings loads before its consumers', strpos( $fp_only, "'settings'," ) < strpos( $fp_only, "'options-page'," ) );
rd_check( 'options-page under 850 lines', substr_count( $opg, "\n" ) < 850 ); // Budget raised 800 → 850 for the Phase 5 fields (privacy embeds, comment guard, Turnstile keys). The guard's job is stopping a second render monolith, not forbidding fields; revisit the budget only with a comparable justification.

// 3.8.1 — CSP-proof tabs with no-JS fallback.
$opg = rd_read( 'inc/options-page.php' );
$act = rd_read( 'inc/options-actions.php' );
rd_check( 'no inline script in options page', preg_match( '/^\s*<script>/m', $opg ) === 0 );
rd_check( 'admin tab js enqueued as file', strpos( $act, "wp_enqueue_script(\n        'rodolfo-admin'" ) !== false );
rd_check( 'admin js file exists', file_exists( dirname( __DIR__ ) . '/assets/js/admin.js' ) );
rd_check( 'admin js min twin exists', file_exists( dirname( __DIR__ ) . '/assets/js/admin.min.js' ) );
rd_check( 'tab hrefs are real urls not hashes', strpos( $opg, "add_query_arg( array( 'page' => 'rodolfo-theme-options', 'tab' => \$slug )" ) !== false );
rd_check( 'active tab clamped to known tabs', strpos( $opg, 'isset( $tabs[ $active_tab ] )' ) !== false ); // The old needle contained literal backslash-dollar sequences, so it could never match.

// 3.6.0 — numbered nav, coming-soon pages, site-updated stamp.
$ps  = rd_read( 'inc/page-states.php' );
$js  = rd_read( 'assets/js/main.js' );
$nav = rd_read( 'inc/nav.php' );
rd_check( 'page-states module loaded', strpos( rd_read( 'functions.php' ), "'page-states'," ) !== false );
rd_check( 'keynav injector present', strpos( $nav, 'function rodolfo_nav_keynav' ) !== false );
rd_check( 'keynav numbers first nav only', strpos( $nav, 'static $done = false;' ) !== false );
rd_check( 'keynav caps at 9', strpos( $nav, '$i > 9' ) !== false );
rd_check( 'keynav js ignores modifiers', strpos( $js, 'if (e.metaKey || e.ctrlKey || e.altKey) return;' ) !== false );
rd_check( 'keynav js ignores typing', strpos( $js, "e.target.isContentEditable) return;" ) !== false );
rd_check( 'keynav mirrored into min.js', strpos( rd_read( 'assets/js/main.min.js' ), 'data-keynav' ) !== false );
rd_check( 'coming-soon meta box', strpos( $ps, "add_meta_box(" ) !== false );
rd_check( 'coming-soon save is nonce + cap guarded', strpos( $ps, 'wp_verify_nonce' ) !== false && strpos( $ps, "current_user_can( 'edit_page'" ) !== false );
rd_check( 'coming-soon pages noindex', strpos( $ps, "\$robots['noindex'] = true;" ) !== false ); // Set via the wp_robots filter array, not a literal 'noindex, follow' string.
rd_check( 'site updated cached', strpos( $ps, "get_transient( 'rodolfo_site_updated_'" ) !== false ); // Key is mode-scoped ('rodolfo_site_updated_' . $mode) since the stamp gained modes.
rd_check( 'site updated flushed on save', strpos( $ps, "add_action( 'save_post_post', 'rodolfo_site_updated_flush' )" ) !== false );
rd_check( 'stamp mode option', strpos( $ps, 'function rodolfo_site_updated_mode' ) !== false );
rd_check( 'stamp mode sanitizer', strpos( $funcs, 'function rodolfo_sanitize_site_updated_mode' ) !== false );
rd_check( 'stamp caches per mode', strpos( $ps, "'rodolfo_site_updated_' . \$mode" ) !== false );
rd_check( 'schema modified time decoupled from display mode', strpos( $ps, 'function rodolfo_site_modified_time' ) !== false );
$schema = rd_read( 'inc/schema.php' );
rd_check( 'homepage schema has datePublished', strpos( $schema, "'datePublished'" ) !== false );
rd_check( 'homepage schema has dateModified', strpos( $schema, "\$site_dates['dateModified']" ) !== false );
rd_check( 'schema uses true modified time', strpos( $schema, 'rodolfo_site_modified_time()' ) !== false );

// 3.7.0 — schema coverage across all major components.
rd_check( 'schema covers Pages', strpos( $schema, "'@type'         => \$page_type" ) !== false );
rd_check( 'schema narrows Contact/About/FAQ by slug', strpos( $schema, "'ContactPage'" ) !== false && strpos( $schema, "'AboutPage'" ) !== false );
rd_check( 'schema does not duplicate aeo FAQ questions', strpos( $schema, "'Question'" ) === false );
rd_check( 'schema covers archives', strpos( $schema, "is_category() || is_tag()" ) !== false );
rd_check( 'archives list their posts', strpos( $schema, "'@type'           => 'ItemList'" ) !== false );
rd_check( 'schema covers author archives', strpos( $schema, "'ProfilePage'" ) !== false );
rd_check( 'schema covers search', strpos( $schema, "'SearchResultsPage'" ) !== false );
rd_check( 'schema declares series position', strpos( $schema, "'CreativeWorkSeries'" ) !== false );
rd_check( 'coming-soon pages omitted from schema', strpos( $schema, 'rodolfo_is_coming_soon()' ) !== false );

// 3.7.1 — SEO plugin coexistence.
rd_check( 'schema stands down under SEO plugin', strpos( $schema, 'rodolfo_seo_plugin_active()' ) !== false );
rd_check( 'meta tags stand down under SEO plugin', strpos( rd_read( 'inc/meta-tags.php' ), 'rodolfo_seo_plugin_active()' ) !== false );
rd_check( 'no raw robots tag in page-states', strpos( $ps, 'meta name="robots" content="noindex' ) === false );
rd_check( 'coming-soon uses wp_robots filter', strpos( $ps, "add_filter( 'wp_robots'" ) !== false );
rd_check( 'breadcrumbs defer to plugin trail', strpos( rd_read( 'inc/breadcrumbs.php' ), 'rank_math_the_breadcrumbs' ) !== false );
rd_check( 'FAQ jsonld has documented opt-out', strpos( rd_read( 'inc/aeo.php' ), "apply_filters( 'rodolfo_faq_jsonld'" ) !== false );
rd_check( 'seo plugin namer defined beside detection', strpos( rd_read( 'inc/meta-tags.php' ), 'function rodolfo_detected_seo_plugin' ) !== false );
rd_check( 'report guards the namer call', strpos( rd_read( 'inc/system-report.php' ), "function_exists( 'rodolfo_detected_seo_plugin' )" ) !== false ); // Inlined read: $sr is defined later (3.5.0 section); using it here was an undefined-variable bug.
rd_check( 'stamp flushed when scheduled post goes live', strpos( $ps, "add_action( 'transition_post_status'" ) !== false );
rd_check( 'footer renders updated stamp', strpos( rd_read( 'footer.php' ), 'rodolfo_site_updated_stamp' ) !== false );

// 3.5.0 — system report.
$sr = rd_read( 'inc/system-report.php' );
rd_check( 'system-report module exists', file_exists( dirname( __DIR__ ) . '/inc/system-report.php' ) );
rd_check( 'system-report loaded before options-page', strpos( rd_read( 'functions.php' ), "'system-report'," ) !== false && strpos( rd_read( 'functions.php' ), "'system-report'," ) < strpos( rd_read( 'functions.php' ), "'options-page'," ) );
rd_check( 'report is sectioned', strpos( $sr, "'environment'" ) !== false && strpos( $sr, "'features'" ) !== false && strpos( $sr, "'recommendations'" ) !== false ); // Loosened from exact spacing when the keys were aligned and the recommendations section joined (Phase 5).
rd_check( 'report scoped to theme, not environment', strpos( $sr, 'get_plugins()' ) === false && strpos( $sr, 'DB_NAME' ) === false && strpos( $sr, 'SERVER_SOFTWARE' ) === false );
rd_check( 'report flags SEO plugin standing meta down', strpos( $sr, 'rodolfo_seo_plugin_active' ) !== false );
rd_check( 'report flags plain permalinks breaking routes', strpos( $sr, 'permalink_structure' ) !== false );
rd_check( 'report flags menu overriding nav settings', strpos( $sr, "has_nav_menu( 'primary' )" ) !== false );
rd_check( 'system tab points to Site Health', strpos( $funcs, 'site-health.php' ) !== false );
rd_check( 'report filterable', strpos( $sr, "apply_filters( 'rodolfo_system_report'" ) !== false );
rd_check( 'export capability-gated', strpos( $sr, "current_user_can( 'manage_options' )" ) !== false );
rd_check( 'export nonce-checked', strpos( $sr, "check_admin_referer( 'rodolfo_system_report' )" ) !== false );
rd_check( 'export streams json attachment', strpos( $sr, 'Content-Disposition: attachment' ) !== false );
rd_check( 'report checks GD for social cards', strpos( $sr, "imagettftext" ) !== false );
rd_check( 'report shows docs parser tier', strpos( $sr, 'Parsedown (bundled)' ) !== false );
rd_check( 'panel renders from report', strpos( $funcs, 'rodolfo_system_report() as' ) !== false );

// 3.2.0 — editor parity, reading time, series, searchform, sidebar, feed, cards.
$ed = rd_read( 'inc/editor.php' );
$rt = rd_read( 'inc/reading-time.php' );
$sb = rd_read( 'inc/sidebar.php' );
rd_check( 'editor module loaded', strpos( $funcs, "'editor'," ) !== false );
rd_check( 'editor-styles supported', strpos( $ed, "add_theme_support( 'editor-styles' )" ) !== false );
rd_check( 'theme css loaded into editor', strpos( $ed, "add_editor_style( 'assets/css/theme.min.css' )" ) !== false );
rd_check( 'editor shim css exists', file_exists( dirname( __DIR__ ) . '/assets/css/editor.css' ) );
rd_check( 'editor palette registered', strpos( $ed, "add_theme_support( 'editor-color-palette'" ) !== false );
rd_check( 'palette derives from category palette', strpos( $ed, 'rodolfo_category_palette()' ) !== false );
rd_check( 'editor font sizes registered', strpos( $ed, "add_theme_support( 'editor-font-sizes'" ) !== false );
rd_check( 'comment-reply enqueued', strpos( $funcs, "wp_enqueue_script( 'comment-reply' )" ) !== false );
rd_check( 'reading time helper', strpos( $rt, 'function rodolfo_reading_time' ) !== false );
rd_check( 'reading time weights code blocks', strpos( $rt, '$code_words' ) !== false );
rd_check( 'old reading fn delegates to engine', strpos( $funcs, 'rodolfo_reading_time_text()' ) !== false );
rd_check( 'no second 200wpm implementation', strpos( $funcs, '/ 200 )' ) === false );
rd_check( 'reading time defaults on', strpos( $funcs, "rodolfo_reading_time_enabled', '1' )" ) !== false );
rd_check( 'series taxonomy registered', strpos( $rt, "register_taxonomy( 'rodolfo_series'" ) !== false );
rd_check( 'series context helper', strpos( $rt, 'function rodolfo_series_context' ) !== false );
rd_check( 'series cache flushed on save', strpos( $rt, "add_action( 'save_post', 'rodolfo_series_flush' )" ) !== false );
rd_check( 'series template part', file_exists( dirname( __DIR__ ) . '/template-parts/content/series-nav.php' ) );
rd_check( 'searchform template', file_exists( dirname( __DIR__ ) . '/searchform.php' ) );
rd_check( 'feed full content filter', strpos( $rt, "add_filter( 'the_excerpt_rss'" ) !== false );
rd_check( 'card variant option', strpos( rd_read( 'inc/social-card.php' ), 'rodolfo_card_variant' ) !== false );
rd_check( 'card defaults light', strpos( rd_read( 'inc/social-card.php' ), "'rodolfo_card_variant', 'light'" ) !== false );
rd_check( 'front end defaults light', strpos( rd_read( 'header.php' ), 'matchMedia' ) === false ); // The design comment legitimately *mentions* prefers-color-scheme; what matters is the inline script never consults it.
rd_check( 'sidebar registered', strpos( $sb, "register_sidebar(" ) !== false );
rd_check( 'sidebar off by default', strpos( $sb, "if ( ! get_option( 'rodolfo_sidebar_enabled' ) ) {" ) !== false );
rd_check( 'sidebar hidden when empty', strpos( $sb, "is_active_sidebar( 'rodolfo-sidebar' )" ) !== false );
rd_check( 'sidebar template', file_exists( dirname( __DIR__ ) . '/sidebar.php' ) );
rd_check( 'maintenance respects reduced motion', strpos( $funcs, 'prefers-reduced-motion' ) !== false );
rd_check( 'maintenance pinned light', strpos( $funcs, 'prefers-color-scheme: dark' ) === false );

// 3.0.0 — email social link.
$footer_php = rd_read( 'footer.php' );
rd_check( 'email platform registered', strpos( $funcs, "'email'     => array( __( 'Email'" ) !== false );
rd_check( 'email uses sanitize_email not esc_url_raw', strpos( $funcs, "'rodolfo_social_email'           => 'rodolfo_sanitize_social_email'" ) !== false );
rd_check( 'email sanitizer strips mailto prefix', strpos( $funcs, 'function rodolfo_sanitize_social_email' ) !== false );
rd_check( 'href resolver present', strpos( $funcs, 'function rodolfo_social_href' ) !== false );
rd_check( 'href builds mailto', strpos( $funcs, "'mailto:' . \$email" ) !== false );
rd_check( 'mail glyph added', strpos( $funcs, "'email'         => '<rect" ) !== false );
rd_check( 'footer uses href resolver', strpos( $footer_php, 'rodolfo_social_href(' ) !== false );
rd_check( 'mailto omits target blank', strpos( $footer_php, '$rodolfo_smail ?' ) !== false );
rd_check( 'admin input type is dynamic', strpos( $funcs, '$rodolfo_stype' ) !== false );

// 2.11.0 — options tab reorg + automatic Donate placement.
$donate = rd_read( 'inc/donate.php' );
rd_check( 'Navigation tab registered', strpos( $funcs, "'navigation'  => __( 'Navigation'" ) !== false );
rd_check( 'Content tab registered', strpos( $funcs, "'content'     => __( 'Content'" ) !== false );
rd_check( 'navigation panel present', strpos( $funcs, 'data-panel="navigation"' ) !== false );
rd_check( 'content panel present', strpos( $funcs, 'data-panel="content"' ) !== false );
rd_check( 'nav table not in general panel', strpos( $opg, 'data-panel="navigation"' ) !== false && strpos( $opg, 'rodolfo-navtable' ) !== false && strpos( $opg, 'data-panel="navigation"' ) < strpos( $opg, 'rodolfo-navtable' ) ); // 3.4.0 moved the page to inc/options-page.php; the old check searched functions.php.
rd_check( 'donate_auto option registered', strpos( $funcs, "'rodolfo_donate_auto'" ) !== false );
rd_check( 'donate_auto sanitizer whitelist', strpos( $funcs, 'function rodolfo_sanitize_donate_auto' ) !== false );
rd_check( 'donate auto-append filter', strpos( $donate, "add_filter( 'the_content', 'rodolfo_donate_auto_append'" ) !== false );
rd_check( 'donate auto guards main query', strpos( $donate, 'is_main_query()' ) !== false && strpos( $donate, 'in_the_loop()' ) !== false );
rd_check( 'donate button helper shared', strpos( $donate, 'function rodolfo_donate_button' ) !== false );
rd_check( 'donate auto spacing style', strpos( $style, '.rodolfo-donate-auto' ) !== false );
rd_check( 'progress height clamp sanitizer', strpos( $funcs, 'function rodolfo_sanitize_progress_height' ) !== false );
rd_check( 'progress height no longer absint', strpos( $funcs, "'rodolfo_progress_height'        => 'absint'" ) === false );
rd_check( 'progress render floors at 2px', strpos( rd_read( 'inc/reading-progress.php' ), 'max( 2,' ) !== false );

// 2.12.0 — privacy slug tolerance, setup content, menu-override warning.
$setup = rd_read( 'inc/setup.php' );
rd_check( 'shared privacy page resolver', strpos( $funcs, 'function rodolfo_privacy_page' ) !== false );
rd_check( 'privacy resolver checks both slugs', strpos( $funcs, "array( 'privacy-policy', 'privacy' )" ) !== false );
rd_check( 'nav excludes resolved privacy page', strpos( $funcs, 'rodolfo_privacy_page();' ) !== false );
rd_check( 'setup entries carry aliases', strpos( $setup, "'aliases'" ) !== false );
rd_check( 'setup alias-aware finder', strpos( $setup, 'function rodolfo_setup_find_page' ) !== false );
rd_check( 'setup ships starter content', strpos( $setup, 'function rodolfo_setup_content_faq' ) !== false );
rd_check( 'legal skeletons flagged as drafts', strpos( $setup, 'function rodolfo_setup_placeholder_notice' ) !== false );
rd_check( 'setup no longer writes empty content', strpos( $setup, "'post_content' => ''" ) === false );
rd_check( 'status reports empty pages', strpos( $setup, "'empty'" ) !== false );
rd_check( 'setup backfills empty pages', strpos( $setup, 'wp_update_post(' ) !== false );
rd_check( 'backfill guarded on empty only', strpos( $setup, "'' === trim( (string) \$existing->post_content )" ) !== false );
rd_check( 'menu override warning present', strpos( $funcs, 'rodolfo_menu_overrides' ) !== false );
rd_check( 'callout styles defined', strpos( rd_read( 'assets/css/admin.css' ), '.rodolfo-callout-warn' ) !== false ); // Followed the admin CSS out of the PHP aggregate when it became a file.

// 2.13.0 — in-dashboard documentation viewer.
$docs = rd_read( 'inc/docs.php' );
rd_check( 'docs module loaded', strpos( $funcs, "'docs',           //" ) !== false );
rd_check( 'Documentation tab registered', strpos( $funcs, "'docs'        => __( 'Documentation'" ) !== false );
rd_check( 'docs panel present', strpos( $funcs, 'data-panel="docs"' ) !== false );
rd_check( 'docs file registry filterable', strpos( $docs, "apply_filters( 'rodolfo_docs_files'" ) !== false );
rd_check( 'docs path containment check', strpos( $docs, "0 !== strpos( \$path, \$base )" ) !== false );
rd_check( 'docs cache keyed on filemtime', strpos( $docs, 'filemtime( $path )' ) !== false );
rd_check( 'docs renderer escapes first', strpos( $docs, '$text = esc_html( $text );' ) !== false );
rd_check( 'docs output passes wp_kses', strpos( $docs, 'return wp_kses( $html' ) !== false );
rd_check( 'docs links restricted to safe protocols', strpos( $docs, "array( 'http', 'https', 'mailto' )" ) !== false );
rd_check( 'docs renders tables', strpos( $docs, "'table'" ) !== false && strpos( $docs, "'thead'" ) !== false ); // Tables are plain elements allow-listed in rodolfo_docs_kses(); the bespoke class this once asserted no longer exists.
rd_check( 'docs styles defined', strpos( rd_read( 'assets/css/admin.css' ), '.rodolfo-docs-body' ) !== false ); // Followed the admin CSS out of the PHP aggregate when it became a file.
rd_check( 'no duplicated doc copy shipped', ! file_exists( dirname( __DIR__ ) . '/docs/readme.html' ) );
rd_check( 'parsedown bundled', file_exists( dirname( __DIR__ ) . '/vendor/parsedown/Parsedown.php' ) );
rd_check( 'parsedown licence shipped', file_exists( dirname( __DIR__ ) . '/vendor/parsedown/LICENSE.txt' ) );
rd_check( 'parser guarded by class_exists', strpos( $docs, "class_exists( 'Parsedown' )" ) !== false );
rd_check( 'parsedown safe mode enabled', strpos( $docs, 'setSafeMode( true )' ) !== false );
rd_check( 'parsedown markup escaped', strpos( $docs, 'setMarkupEscaped( true )' ) !== false );
rd_check( 'builtin renderer retained as fallback', strpos( $docs, 'rodolfo_docs_markdown_to_html( $markdown )' ) !== false );
rd_check( 'fallback rationale documented in-file', strpos( $docs, 'DO NOT delete this as dead code' ) !== false );
rd_check( 'fallback rationale in ssot', strpos( rd_read( 'docs/ssot.md' ), 'Docs parser fallback' ) !== false );
rd_check( 'fallback is minimal, not a 2nd full parser', strpos( $docs, 'function rodolfo_docs_table_cells' ) === false );
rd_check( 'fallback inline renamed', strpos( $docs, 'function rodolfo_docs_fallback_inline' ) !== false );
rd_check( 'task lists converted post-Parsedown', strpos( $docs, 'rodolfo-docs-task' ) !== false );
rd_check( 'fallback output also kses-filtered', substr_count( $docs, 'rodolfo_docs_kses(' ) >= 3 );
rd_check( 'docs table styles target parsedown output', strpos( rd_read( 'assets/css/admin.css' ), '.rodolfo-docs-body table{' ) !== false ); // Followed the admin CSS out of the PHP aggregate when it became a file.
rd_check( 'shared kses allowlist', strpos( $docs, 'function rodolfo_docs_kses' ) !== false );
rd_check( 'cache key includes engine', strpos( $docs, "'|' . \$engine" ) !== false );
foreach ( array( 'readme', 'upgrading', 'changelog', 'ssot' ) as $d ) {
    rd_check( "docs source $d.md present", file_exists( dirname( __DIR__ ) . "/docs/$d.md" ) );
}

// 2.10.0 — breadcrumbs, LCP images, AI-crawler robots guidance.
rd_check( 'breadcrumbs module loaded', strpos( $funcs, "'breadcrumbs'" ) !== false );
rd_check( 'breadcrumbs defer to Rank Math', strpos( rd_read( 'inc/breadcrumbs.php' ), 'rank_math_the_breadcrumbs' ) !== false );
rd_check( 'single.php renders breadcrumbs', strpos( rd_read( 'single.php' ), 'rodolfo_breadcrumbs()' ) !== false );
rd_check( 'hero image is not lazy (LCP)', strpos( rd_read( 'index.php' ), "'fetchpriority' => 'high'" ) !== false );
rd_check( 'single featured image is not lazy (LCP)', strpos( rd_read( 'template-parts/content/content-single.php' ), "'fetchpriority' => 'high'" ) !== false );
rd_check( 'AI robots guidance present', strpos( rd_read( 'inc/llms-txt.php' ), 'rodolfo_ai_crawlers_welcome' ) !== false );

// 2.9.0 — llms.txt, updated-date freshness, WCAG pass.
rd_check( 'llms-txt module loaded', strpos( $funcs, "'llms-txt'" ) !== false );
rd_check( 'llms.txt route registered', strpos( rd_read( 'inc/llms-txt.php' ), "add_rewrite_rule( '^llms" ) !== false );
rd_check( 'llms.txt served as text/plain', strpos( rd_read( 'inc/llms-txt.php' ), 'text/plain' ) !== false );
rd_check( 'single shows Updated date', strpos( rd_read( 'template-parts/content/content-single.php' ), 'get_the_modified_date' ) !== false );
rd_check( 'reduced-motion override present', strpos( $style, 'prefers-reduced-motion' ) !== false );
rd_check( 'palette restores focus on close', strpos( rd_read( 'assets/js/main.js' ), 'lastFocus' ) !== false );
rd_check( 'card topics meet AA contrast', strpos( rd_read( 'template-parts/content/content-card.php' ), 'text-xs text-slate-400 dark:text-slate-500' ) === false );

// 2.7.0 — AEO content blocks.
rd_check( 'aeo module loaded', strpos( $funcs, "'aeo'" ) !== false );
rd_check( 'answer shortcode', strpos( rd_read( 'inc/aeo.php' ), "add_shortcode( 'answer'" ) !== false );
rd_check( 'faq emits FAQPage JSON-LD', strpos( rd_read( 'inc/aeo.php' ), 'FAQPage' ) !== false );
rd_check( 'compare shortcode', strpos( rd_read( 'inc/aeo.php' ), "add_shortcode( 'compare'" ) !== false );
rd_check( 'proof shortcode', strpos( rd_read( 'inc/aeo.php' ), "add_shortcode( 'proof'" ) !== false );
rd_check( 'proof styles present', strpos( $style, '.rodolfo-proof' ) !== false );
rd_check( 'aeo styles present', strpos( $style, '.rodolfo-answer' ) !== false );

// 2.3.0 — reading progress bar + donate surface.
rd_check( 'reading-progress module loaded', strpos( $funcs, "'reading-progress'" ) !== false );
rd_check( 'reading-progress file exists', rd_read( 'inc/reading-progress.php' ) !== '' );
rd_check( 'donate module loaded', strpos( $funcs, "'donate'" ) !== false );
rd_check( 'donate shortcode registered', strpos( rd_read( 'inc/donate.php' ), "add_shortcode( 'donate'" ) !== false );
rd_check( 'donate builds PayPal URL from email', strpos( rd_read( 'inc/donate.php' ), 'paypal.com/donate/' ) !== false );
rd_check( 'donate validates the PayPal email', strpos( rd_read( 'inc/donate.php' ), 'sanitize_email' ) !== false );
rd_check( 'donate does NOT handle card data/webhooks', strpos( php_strip_whitespace( $root . '/inc/donate.php' ), 'curl' ) === false && strpos( php_strip_whitespace( $root . '/inc/donate.php' ), 'webhook' ) === false ); // Stripped source: the docblock legitimately mentions IPN/webhooks to say the theme never touches them.
rd_check( 'currency sanitizer present', strpos( $funcs, 'function rodolfo_sanitize_currency' ) !== false );
rd_check( 'progress position sanitizer', strpos( $funcs, 'function rodolfo_sanitize_progress_position' ) !== false );
rd_check( 'donate styles present', strpos( $style, '.rodolfo-donate' ) !== false );

// 2.2.0 — natively merged plugin features.
rd_check( 'click-to-tweet module loaded', strpos( $funcs, "'click-to-tweet'" ) !== false );
rd_check( 'click-to-tweet file exists', rd_read( 'inc/click-to-tweet.php' ) !== '' );
rd_check( 'ctt guards against BCTT plugin', strpos( rd_read( 'inc/click-to-tweet.php' ), 'shortcode_exists' ) !== false );
rd_check( 'unlist module loaded', strpos( $funcs, "'unlist'" ) !== false );
rd_check( 'unlist excludes via pre_get_posts', strpos( rd_read( 'inc/unlist.php' ), 'pre_get_posts' ) !== false );
rd_check( 'unlist keeps singular live', strpos( rd_read( 'inc/unlist.php' ), 'is_singular()' ) !== false );
rd_check( 'login module loaded', strpos( $funcs, "'login'" ) !== false );
rd_check( 'login brands wp-login', strpos( rd_read( 'inc/login.php' ), 'login_enqueue_scripts' ) !== false );
rd_check( 'search results noindex', strpos( rd_read( 'inc/meta-tags.php' ), 'noindex,follow' ) !== false );
rd_check( 'ctt styles present', strpos( $style, '.rodolfo-ctt' ) !== false );

// 1.11.0 — built-in Tags page, nav taxonomy, card redesign.
rd_check( 'tags module loaded', strpos( $funcs, "'tags'" ) !== false );
rd_check( 'tags route file exists', rd_read( 'inc/tags.php' ) !== '' );
rd_check( 'tags template exists', rd_read( 'tags-page.php' ) !== '' );
rd_check( 'header links built-in tags url', strpos( $header, 'rodolfo_tags_url' ) !== false );
rd_check( 'secondary menu location registered', strpos( $funcs, "'secondary'" ) !== false );
rd_check( 'footer tertiary auto-categories (no dead # link)', strpos( $footer, 'Explore Topics' ) !== false );
rd_check( 'card uses .rodolfo-card', strpos( rd_read( 'template-parts/content/content-card.php' ), 'rodolfo-card' ) !== false );

// 1.11.1 — logo presentation, menu cleanup, curated nav, comma topic line.
rd_check( 'logo image class defined', strpos( $style, '.rodolfo-logo-img' ) !== false );
rd_check( 'header uses logo image class', strpos( $header, 'rodolfo-logo-img' ) !== false );
rd_check( 'footer uses logo image class', strpos( $footer, 'rodolfo-logo-img' ) !== false );
rd_check( 'menu-location cleanup migration present', strpos( rd_read( 'inc/tags.php' ), 'rodolfo_menu_locations_cleanup' ) !== false );
rd_check( 'card uses comma topic line', strpos( rd_read( 'template-parts/content/content-card.php' ), 'topic_terms' ) !== false );

// 2.0.0 — vendored UI layer bundled locally under native names, enqueued
// before the core sheet (core Preflight wins resets).
rd_check( 'ui css bundled (native name)', rd_read( 'assets/lib/ui.min.css' ) !== '' );
rd_check( 'ui js bundled (native name)', rd_read( 'assets/lib/ui.bundle.min.js' ) !== '' );
rd_check( 'ui enqueued under rodolfo-ui handle', strpos( $funcs, "'rodolfo-ui'" ) !== false );
rd_check( 'core sheet depends on rodolfo-ui (loads after)', strpos( $funcs, "array( 'rodolfo-ui' )" ) !== false );
rd_check( 'no bootstrap-branded vendor path remains', strpos( $funcs, 'vendor/bootstrap' ) === false );

// 2.0.2 — UI layer ancestor-scoped under .rodolfo-ui (replaces the 2.0.1 @layer,
// which inverted !important precedence); :root vars kept global. Compiled-utility
// backfill in style.css. Card uses stretched-link (no nested anchors).
rd_check( 'ui rules scoped under .rodolfo-ui', strpos( rd_read( 'assets/lib/ui.min.css' ), '.rodolfo-ui ' ) !== false );
rd_check( 'ui :root vars kept global (not scoped)', strpos( rd_read( 'assets/lib/ui.min.css' ), '.rodolfo-ui :root' ) === false );
rd_check( 'no cascade @layer wrap remains', strpos( rd_read( 'assets/lib/ui.min.css' ), '@layer rodolfo-ui' ) === false );
rd_check( 'compiled-utility backfill present', strpos( $style, '@backfill-start' ) !== false );
rd_check( 'card uses stretched-link (no nested anchors)', strpos( rd_read( 'template-parts/content/content-card.php' ), 'rodolfo-card-link' ) !== false );
rd_check( 'card container is not an anchor wrapper', strpos( rd_read( 'template-parts/content/content-card.php' ), '<a href="<?php the_permalink(); ?>" class="block h-full">' ) === false );

// ---------------------------------------------------------------------
// Whole-theme call graph: every rodolfo_* function CALLED anywhere must be
// DEFINED somewhere. This is the class of bug that shipped in 3.7.1: an
// edit inserting a function silently failed to apply while the call to it
// did — an instant fatal, but only on installs meeting the call's runtime
// condition (an active SEO plugin), so no per-file check caught it.
// ---------------------------------------------------------------------
$rd_all_files = array_merge(
    array( dirname( __DIR__ ) . '/functions.php' ),
    glob( dirname( __DIR__ ) . '/inc/*.php' ),
    glob( dirname( __DIR__ ) . '/*.php' ),
    glob( dirname( __DIR__ ) . '/template-parts/content/*.php' )
);
$rd_defined = array();
$rd_called  = array();
foreach ( $rd_all_files as $rd_f ) {
    $rd_src = file_get_contents( $rd_f );
    if ( preg_match_all( '/function\s+(rodolfo_\w+)\s*\(/', $rd_src, $rd_m ) ) {
        foreach ( $rd_m[1] as $rd_fn ) { $rd_defined[ $rd_fn ] = true; }
    }
    if ( preg_match_all( '/(?<!function )(rodolfo_\w+)\s*\(/', $rd_src, $rd_m ) ) {
        foreach ( $rd_m[1] as $rd_fn ) { $rd_called[ $rd_fn ] = true; }
    }
}
$rd_undefined = array_diff_key( $rd_called, $rd_defined );
rd_check( 'no calls to undefined rodolfo_* functions', empty( $rd_undefined ) );
if ( ! empty( $rd_undefined ) ) {
    echo '    undefined: ' . implode( ', ', array_keys( $rd_undefined ) ) . "\n";
}


// Phase 1 — re-landed 3.3.x remediation batch (docs/upgrading.md, Roadmap).
// One assertion per re-landed item; these fixes regressed once already, so
// they stay pinned here permanently.
$p1_llms  = rd_read( 'inc/llms-txt.php' );
$p1_tags  = rd_read( 'inc/tags.php' );
$p1_acts  = rd_read( 'inc/options-actions.php' );
$p1_js    = rd_read( 'assets/js/main.js' );
$p1_jsmin = rd_read( 'assets/js/main.min.js' );

// 1.1 — every Customizer setting carries a sanitize_callback.
rd_check( 'background video setting has sanitize_callback', strpos( $funcs, "'sanitize_callback' => 'esc_url_raw'" ) !== false );
// 1.2 — import reads only verified uploads, never a text-sanitised path.
rd_check( 'import guards with is_uploaded_file', strpos( $p1_acts, 'is_uploaded_file(' ) !== false );
rd_check( 'import no longer text-sanitises the tmp path', strpos( $p1_acts, "sanitize_text_field( \$_FILES" ) === false );
// 1.3 — llms.txt answers the literal path on plain permalinks.
rd_check( 'llms.txt has plain-permalink fallback', strpos( $p1_llms, 'rodolfo_llms_request_fallback' ) !== false );
rd_check( 'llms.txt fallback compares against home_url path', strpos( $p1_llms, "wp_parse_url( home_url( '/llms.txt' )" ) !== false );
// 1.4 — unlisted posts stay out of the llms.txt body.
rd_check( 'llms.txt excludes unlisted posts', strpos( $p1_llms, 'rodolfo_unlisted_ids' ) !== false );
// 1.5 — JS strings localized; source and minified both use the L10N object.
rd_check( 'main.js strings localized via wp_localize_script', strpos( $funcs, 'rodolfoL10n' ) !== false );
rd_check( 'main.js consumes rodolfoL10n', strpos( $p1_js, 'rodolfoL10n' ) !== false );
rd_check( 'main.min.js regenerated with rodolfoL10n', strpos( $p1_jsmin, 'rodolfoL10n' ) !== false );
// 1.6 — the stale "no rules" comment is gone.
rd_check( 'stale style.css comment removed', strpos( $funcs, 'contains only the theme header' ) === false );
// 1.7 — enqueues use the version constant, not a per-request style.css read.
rd_check( 'rodolfo_scripts uses RODOLFO_VERSION', strpos( $funcs, '$version = RODOLFO_VERSION;' ) !== false );
// 1.8 — rewrite-flush flags invalidate on every release.
rd_check( 'tags flush keyed to RODOLFO_VERSION', strpos( $p1_tags, "RODOLFO_VERSION !== get_option( 'rodolfo_tags_rewrite_v' )" ) !== false );
rd_check( 'llms flush keyed to RODOLFO_VERSION', strpos( $p1_llms, "RODOLFO_VERSION !== get_option( 'rodolfo_llms_rewrite_v' )" ) !== false );
// 1.9 / 1.10 — background media is decorative and motion-safe.
rd_check( 'reduced-motion hides background video', strpos( $style, '.rodolfo-bg-video video { display: none; }' ) !== false );
rd_check( 'background wrappers aria-hidden', strpos( $header, 'rodolfo-bg-video' ) !== false && substr_count( $header, 'aria-hidden="true"' ) >= 3 );


// Phase 3 — admin asset separation completed (docs/upgrading.md, Roadmap).
$p3_acts = rd_read( 'inc/options-actions.php' );
rd_check( 'admin CSS extracted to a file', rd_read( 'assets/css/admin.css' ) !== '' );
rd_check( 'admin CSS min twin exists', rd_read( 'assets/css/admin.min.css' ) !== '' );
rd_check( 'options screen enqueues admin.css by suffix', strpos( $p3_acts, "/assets/css/admin' . \$suffix . '.css'" ) !== false );
rd_check( 'no inline admin styles remain', strpos( $p3_acts, 'wp_add_inline_style' ) === false );


// Phase 5 — quick-wins feature batch (docs/upgrading.md, Roadmap).
$p5_embeds = rd_read( 'inc/privacy-embeds.php' );
$p5_guard  = rd_read( 'inc/comment-guard.php' );
$p5_views  = rd_read( 'inc/views.php' );
$p5_sr     = rd_read( 'inc/system-report.php' );
$p5_js     = rd_read( 'assets/js/main.js' );

// 5.1 — consent-gated embeds: facade, not the provider thumbnail.
rd_check( 'privacy embeds module gated behind its option', strpos( $p5_embeds, "get_option( 'rodolfo_privacy_embeds' )" ) !== false );
rd_check( 'embed facade fetches no provider thumbnail', strpos( $p5_embeds, 'ytimg' ) === false && strpos( $p5_embeds, 'vumbnail' ) === false );
rd_check( 'embed gate consumed by main.js', strpos( $p5_js, 'rodolfo-embed-gate' ) !== false );
rd_check( 'embed always-allow stored client-side only', strpos( $p5_js, 'rodolfoEmbedAllow' ) !== false );
// 5.2 — comment guard: honeypot + signed time trap; Turnstile only with keys.
rd_check( 'comment guard hooks preprocess_comment', strpos( $p5_guard, "add_filter( 'preprocess_comment'" ) !== false );
rd_check( 'time trap signature verified with hash_equals', strpos( $p5_guard, 'hash_equals(' ) !== false );
rd_check( 'guard never checks pingbacks/trackbacks', strpos( $p5_guard, "comment_type" ) !== false );
rd_check( 'turnstile verify requires the secret', strpos( $p5_guard, "trim( (string) get_option( 'rodolfo_turnstile_secret' ) )" ) !== false );
rd_check( 'turnstile script lazy-loaded on focus', strpos( $p5_js, 'challenges.cloudflare.com/turnstile' ) !== false && strpos( $p5_js, 'focusin' ) !== false );
rd_check( 'no turnstile script on page load', strpos( rd_read( 'functions.php' ), 'challenges.cloudflare.com' ) === false );
// 5.3 — system report gained recommendations.
rd_check( 'system report has recommendations section', strpos( $p5_sr, "'recommendations'" ) !== false );
rd_check( 'recommendations flag plain permalinks', strpos( $p5_sr, 'request-path fallback' ) !== false );
// 5.4 — rolling daily views, no cron.
rd_check( 'daily views meta pruned on write', strpos( $p5_views, '_rodolfo_views_daily' ) !== false && strpos( $p5_views, 'unset( $daily[ $day ] )' ) !== false );
rd_check( 'popular_posts gained a window argument', strpos( $p5_views, '$window = 0 )' ) !== false );
rd_check( 'trending helper cached and capped', strpos( $p5_views, 'rodolfo_trending_' ) !== false && strpos( $p5_views, "min( 7, (int) \$window" ) !== false );
rd_check( 'no cron scheduled for daily views', strpos( $p5_views, 'wp_schedule_event' ) === false );
// Wiring.
rd_check( 'phase 5 options registered', strpos( rd_read( 'inc/settings.php' ), 'rodolfo_privacy_embeds' ) !== false && strpos( rd_read( 'inc/settings.php' ), 'rodolfo_turnstile_secret' ) !== false );
rd_check( 'phase 5 modules loaded', strpos( rd_read( 'functions.php' ), "'privacy-embeds'" ) !== false && strpos( rd_read( 'functions.php' ), "'comment-guard'" ) !== false );
rd_check( 'honeypot removed from a11y tree and styled off-screen', strpos( $p5_guard, 'aria-hidden="true"' ) !== false && strpos( rd_read( 'style.css' ), '.rodolfo-hp-wrap' ) !== false );


// Phase 6 — near-term feature backlog (docs/upgrading.md, Roadmap).
$p6_ctt    = rd_read( 'inc/click-to-tweet.php' );
$p6_unlist = rd_read( 'inc/unlist.php' );
$p6_arch   = rd_read( 'archive.php' );

// 6.1 — AEO author guidance shipped in the rendered docs.
rd_check( 'aeo author guidance documented', strpos( rd_read( 'docs/readme.md' ), 'Writing for answer engines' ) !== false );
// 6.2 — click-to-tweet is a dynamic block rendered through the shortcode.
rd_check( 'bctt block registered', strpos( $p6_ctt, "register_block_type( 'rodolfo/click-to-tweet'" ) !== false );
rd_check( 'bctt block renders via the shortcode', strpos( $p6_ctt, 'do_shortcode( $sc' ) !== false );
rd_check( 'bctt block editor script + min twin', rd_read( 'assets/js/bctt-block.js' ) !== '' && rd_read( 'assets/js/bctt-block.min.js' ) !== '' );
rd_check( 'bctt block save is dynamic (null)', strpos( rd_read( 'assets/js/bctt-block.js' ), 'return null;' ) !== false );
// 6.3 — unlist list-table affordances.
rd_check( 'unlist admin column registered', strpos( $p6_unlist, 'manage_post_posts_columns' ) !== false );
rd_check( 'unlist quick edit prefilled from column state', strpos( rd_read( 'assets/js/unlist-quick-edit.js' ), 'data-unlisted' ) !== false && strpos( $p6_unlist, 'data-unlisted="%s"' ) !== false );
rd_check( 'unlist quick edit script + min twin', rd_read( 'assets/js/unlist-quick-edit.min.js' ) !== '' );
rd_check( 'unlist filter admin-only', strpos( $p6_unlist, "rodolfo_unlist_filter_query" ) !== false && strpos( $p6_unlist, '! is_admin() || ! $query->is_main_query()' ) !== false );
rd_check( 'unlist empty filter cannot list everything', strpos( $p6_unlist, 'array( 0 )' ) !== false );
// 6.4 — author archives carry the social row.
rd_check( 'author archive renders social links', strpos( $p6_arch, 'rodolfo_author_social_links' ) !== false );


// Phase 7 — design-decision features (docs/upgrading.md, Roadmap).
$p7_accent = rd_read( 'inc/accent.php' );
$p7_disp   = rd_read( 'inc/post-display.php' );
$p7_prog   = rd_read( 'inc/reading-progress.php' );
$p7_fp     = rd_read( 'functions.php' );
$p7_set    = rd_read( 'inc/settings.php' );

// 7.1 — one control, one variable; zero cost at the default.
rd_check( 'accent setting sanitized as hex', strpos( $p7_accent, "'sanitize_callback' => 'sanitize_hex_color'" ) !== false );
rd_check( 'accent prints nothing at the default', strpos( $p7_accent, 'RODOLFO_ACCENT_DEFAULT ) {' ) !== false && strpos( $p7_accent, 'return;' ) !== false );
rd_check( 'accent shades derived, never stored', strpos( $p7_accent, 'rodolfo_accent_mix' ) !== false && strpos( $p7_accent, "get_theme_mod( 'rodolfo_accent'" ) !== false );
rd_check( 'maintenance page reads the accent', strpos( rd_read( 'inc/maintenance.php' ), 'rodolfo_accent()' ) !== false );
rd_check( 'login screen reads the accent', strpos( rd_read( 'inc/login.php' ), 'rodolfo_accent()' ) !== false );
rd_check( 'progress fill reads the accent', strpos( $p7_prog, 'rodolfo_accent()' ) !== false );
rd_check( 'embed play button on the accent variable', strpos( $style, 'var(--rodolfo-accent); color: #fff' ) !== false );
// 7.2 — toggle loader: whitelist const, generated registry, inverted storage.
rd_check( 'optional-module whitelist is a constant', strpos( $p7_fp, 'RODOLFO_OPTIONAL_MODULES = array(' ) !== false );
rd_check( 'loader skips disabled optional modules', strpos( $p7_fp, "get_option( 'rodolfo_disable_' . \$rodolfo_module )" ) !== false );
rd_check( 'disable options generated from the whitelist', strpos( $p7_set, "\$map[ 'rodolfo_disable_' . \$slug ]" ) !== false );
rd_check( 'features tab present with inverted storage documented', strpos( $opg, "data-panel=\"features\"" ) !== false && strpos( $opg, 'Inverted storage' ) !== false );
rd_check( 'structural modules not in the whitelist', strpos( $p7_fp, "'settings'," ) !== false && ! preg_match( "/RODOLFO_OPTIONAL_MODULES = array\([^)]*'settings'/s", $p7_fp ) );
rd_check( 'single.php guards optional template calls', substr_count( rd_read( 'single.php' ), 'function_exists(' ) >= 2 );
// 7.3 — per-post display flags.
rd_check( 'post display flags saved with nonce + capability', strpos( $p7_disp, 'rodolfo_post_display_nonce' ) !== false && strpos( $p7_disp, "current_user_can( 'edit_post'" ) !== false );
rd_check( 'hidden title stays in the accessibility tree', strpos( rd_read( 'template-parts/content/content-single.php' ), 'screen-reader-text' ) !== false );
rd_check( 'template guards the display helper', strpos( rd_read( 'template-parts/content/content-single.php' ), "function_exists( 'rodolfo_post_display' )" ) !== false );
// 7.4 — sticky strip: observer-driven, motion-safe.
rd_check( 'sticky strip uses IntersectionObserver, no scroll handler', strpos( $p7_prog, 'IntersectionObserver' ) !== false && substr_count( $p7_prog, "addEventListener('scroll'" ) === 1 );
rd_check( 'sticky strip motion-safe', strpos( $style, '.rodolfo-sticky-title { transition: none; }' ) !== false );


// Footer bottom-bar wrap repairs (screenshot regression, 3.10.x). Three
// groups + many social icons must degrade into tidy stacked rows, never a
// squeezed row with stranded separators, a split date, or an orphan icon.
$p_footer = rd_read( 'footer.php' );
rd_check( 'bottom bar defers row layout to lg', strpos( $p_footer, 'flex flex-col lg:flex-row items-center justify-between' ) !== false );
rd_check( 'legal separators travel with their link', strpos( $p_footer, "echo '<span class=\"whitespace-nowrap\">';" ) !== false );
rd_check( 'freshness stamp never splits mid-date', strpos( $p_footer, 'whitespace-nowrap">&middot; ' ) !== false );
rd_check( 'social circles wrap centred with a capped row', strpos( $p_footer, 'justify-center lg:justify-end' ) !== false );


// Phase 8 — mid-term polish (docs/upgrading.md, Roadmap).
$p8_rel  = rd_read( 'inc/related-posts.php' );
$p8_card = rd_read( 'inc/social-card.php' );
$p8_toc  = rd_read( 'inc/toc.php' );
$p8_js   = rd_read( 'assets/js/main.js' );

// 8.1 — TOC spy + mobile collapse.
rd_check( 'toc heading is a disclosure button', strpos( $p8_toc, 'rodolfo-toc-toggle' ) !== false && strpos( $p8_toc, 'aria-controls="rodolfo-toc-list"' ) !== false );
rd_check( 'toc scroll-spy is observer-driven', strpos( $p8_js, "rootMargin: '0px 0px -70% 0px'" ) !== false );
rd_check( 'toc collapse styles present', strpos( $style, '.rodolfo-toc:not(.is-open) .rodolfo-toc-list { display: none; }' ) !== false );
// 8.2 — relevance + pinning.
rd_check( 'related ranks by shared-tag overlap', strpos( $p8_rel, 'array_intersect(' ) !== false && strpos( $p8_rel, '$b[0] <=> $a[0]' ) !== false );
rd_check( 'related manual override honoured first', strpos( $p8_rel, '_rodolfo_related_ids' ) !== false );
rd_check( 'pinned posts must be published', strpos( $p8_rel, "'publish' === \$pin_post->post_status" ) !== false );
// 8.3 — per-category tint + logo lockup.
rd_check( 'card gradient tinted by category colour', strpos( $p8_card, 'toward the category colour' ) !== false && strpos( $p8_card, '$mix' ) !== false );
rd_check( 'card logo lockup reads the local file', strpos( $p8_card, 'rodolfo_social_card_logo' ) !== false && strpos( $p8_card, 'get_attached_file( $logo_id )' ) !== false );
// 8.4 — palette surfaces.
rd_check( 'palette searches categories in parallel', strpos( $p8_js, 'type=term&subtype=category' ) !== false );
rd_check( 'palette recents local-only, capped at five', strpos( $p8_js, 'rodolfoRecentSearches' ) !== false && strpos( $p8_js, '.slice(0, 5)' ) !== false );
// 8.5 — comment form skin.
rd_check( 'comment fields grid on wider screens', strpos( $style, '.comment-form .comment-form-author { grid-column: 1 / 2; }' ) !== false );

// Security hardening regressions. Static checks supplement, not replace, WP integration tests.
rd_check( 'RSS full content excludes password-protected posts', strpos( rd_read( 'inc/reading-time.php' ), '! empty( $post->post_password )' ) !== false );
rd_check( 'metadata excludes protected posts', strpos( rd_read( 'inc/meta-tags.php' ), '! empty( $post->post_password )' ) !== false );
rd_check( 'schema JSON is HTML-safe', strpos( $schema, 'JSON_HEX_TAG' ) !== false );
rd_check( 'FAQ JSON is HTML-safe', strpos( rd_read( 'inc/aeo.php' ), 'JSON_HEX_TAG' ) !== false );
rd_check( 'social cards use safe bounded HTTP requests', strpos( $p8_card, 'wp_safe_remote_get(' ) !== false && strpos( $p8_card, 'limit_response_size' ) !== false && strpos( $p8_card, 'getimagesizefromstring' ) !== false );
rd_check( 'social cards exclude protected posts', strpos( $p8_card, '! empty( $post->post_password )' ) !== false );
rd_check( 'llms.txt excludes protected posts and respects site visibility', strpos( $p1_llms, "'has_password' => false" ) !== false && strpos( $p1_llms, "get_option( 'blog_public' )" ) !== false );
rd_check( 'AI crawler guidance does not override wildcard robots restrictions', strpos( $p1_llms, 'User-agent: $bot' ) === false );
rd_check( 'related queries override base parameters via array_merge', strpos( $p8_rel, 'new WP_Query( array_merge( $base' ) !== false );
rd_check( 'settings export excludes Turnstile secret', strpos( $p1_acts, "'rodolfo_turnstile_secret' === \$key" ) !== false );
rd_check( 'esbuild dependency has security patch baseline', strpos( rd_read( 'package.json' ), '"esbuild": "^0.25.0"' ) !== false );

rd_check( 'card cache requires persistent object cache', strpos( $p8_card, 'wp_using_ext_object_cache()' ) !== false );
rd_check( 'card cache excludes authenticated requests', strpos( $p8_card, '! is_user_logged_in()' ) !== false );
rd_check( 'card cache checks signature and bounds size', strpos( $p8_card, "\$signature !== \$entry['signature']" ) !== false && strpos( $p8_card, '512 * 1024' ) !== false );
rd_check( 'card cache uses short public TTL', strpos( $p8_card, 'max-age=300' ) !== false );
rd_check( 'card honors disabled avatars', strpos( $p8_card, "get_option( 'show_avatars' )" ) !== false );

echo "\n";
echo str_repeat( '=', 48 ) . "\n";
printf( "Rodolfo tests: %d passed, %d failed\n", $pass, $fail );
if ( $fail ) {
    echo "\nFAILURES:\n";
    foreach ( $fails as $f ) {
        echo "  ✗ $f\n";
    }
    exit( 1 );
}
echo "All checks passed.\n";
exit( 0 );
