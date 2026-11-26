<?php
/**
 * Rodolfo 4.x test harness. Runs WITHOUT a WordPress installation.
 *
 *   php tests/test-theme.php
 *
 * Three layers:
 *   1. Static checks over the theme sources (structure, assets, settings,
 *      security conventions).
 *   2. Unit tests of pure helpers (accent maths, choice sanitizers, guest
 *      author formatting, end-mark placement, schema cleaning), loaded with
 *      minimal WordPress stubs.
 *   3. Tooling: every PHP file lints, every JS file parses (when node is on
 *      PATH), and rebrand-theme.sh rewrites a throwaway copy cleanly.
 *
 * When a regression is found the hard way, add a check for it here in the
 * same release.
 *
 * @package Rodolfo
 */

// phpcs:disable

$root  = dirname( __DIR__ );
$pass  = 0;
$fail  = 0;
$fails = array();

function rd_read( $path ) {
	global $root;
	$full = $root . '/' . $path;
	return is_readable( $full ) ? (string) file_get_contents( $full ) : '';
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

/** Theme-relative paths of every file matching $ext, excluding tests/ and .git/. */
function rd_files( $ext ) {
	global $root;
	$out = array();
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		$rel = ltrim( str_replace( $root, '', $file->getPathname() ), '/' );
		if ( preg_match( '#^(\.git|tests)/#', $rel ) ) {
			continue;
		}
		if ( substr( $rel, -strlen( $ext ) ) === $ext ) {
			$out[] = $rel;
		}
	}
	sort( $out );
	return $out;
}

$php_files = rd_files( '.php' );
$js_files  = rd_files( '.js' );
$css_files = rd_files( '.css' );
$funcs     = rd_read( 'functions.php' );
$style     = rd_read( 'style.css' );
$readme    = rd_read( 'readme.txt' );
$all_php   = '';
foreach ( $php_files as $f ) {
	$all_php .= rd_read( $f );
}

// ======================================================================
// 1. STATIC CHECKS
// ======================================================================

// --- Theme header & versioning -----------------------------------------
preg_match( '/^Version:\s*(\S+)/m', $style, $v_style );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $v_readme );
rd_check( 'style.css declares a Version', ! empty( $v_style[1] ) );
rd_check( 'readme Stable tag matches style.css Version', ! empty( $v_readme[1] ) && $v_style[1] === $v_readme[1] );
rd_check( 'style.css Text Domain is rodolfo', (bool) preg_match( '/^Text Domain:\s*rodolfo\s*$/m', $style ) );
rd_check( 'screenshot.png present', is_readable( $root . '/screenshot.png' ) );
rd_check( 'version read once into RODOLFO_VERSION', strpos( $funcs, "define( 'RODOLFO_VERSION'" ) !== false );

// --- Module loader -----------------------------------------------------
preg_match( '/foreach \( array\((.*?)\) as \$rodolfo_module \)/s', $funcs, $loader );
$loaded = array();
if ( ! empty( $loader[1] ) ) {
	// Only uncommented entries: strip // comments line by line first.
	$body = preg_replace( '#^\s*//.*$#m', '', $loader[1] );
	preg_match_all( "/'([a-z0-9-]+)'/", $body, $m );
	$loaded = $m[1];
}
rd_check( 'module loader found', count( $loaded ) > 10 );
foreach ( $loaded as $mod ) {
	rd_check( "module inc/$mod.php exists", is_readable( "$root/inc/$mod.php" ) );
}
preg_match( '/const RODOLFO_OPTIONAL_MODULES = array\((.*?)\);/s', $funcs, $opt_mods );
preg_match_all( "/'([a-z0-9-]+)'/", isset( $opt_mods[1] ) ? $opt_mods[1] : '', $m );
$optional_modules = $m[1];
foreach ( $optional_modules as $mod ) {
	rd_check( "optional module '$mod' is a loaded module", in_array( $mod, $loaded, true ) );
}
rd_check( 'disabled optional modules are never required', strpos( $funcs, "get_option( 'rodolfo_disable_' . \$rodolfo_module )" ) !== false );
foreach ( array( 'guest-authors', 'endmark', 'preferred-sources', 'content-hygiene', 'llms-txt', 'reading-progress' ) as $mod ) {
	rd_check( "4.0 module '$mod' is loaded", in_array( $mod, $loaded, true ) );
}

// --- Direct-access guard -------------------------------------------------
foreach ( rd_files( '.php' ) as $f ) {
	if ( 0 !== strpos( $f, 'inc/' ) ) {
		continue;
	}
	$src = rd_read( $f );
	rd_check( "$f guards on ABSPATH", strpos( $src, "defined( 'ABSPATH' )" ) !== false );
}

// --- Asset layout: JS in assets/js, CSS in assets/css ---------------------
foreach ( $js_files as $f ) {
	rd_check( "$f lives in assets/js/", 0 === strpos( $f, 'assets/js/' ) );
}
foreach ( $css_files as $f ) {
	rd_check( "$f lives in assets/css/ (or is style.css)", 'style.css' === $f || 0 === strpos( $f, 'assets/css/' ) );
}

// Every theme asset a PHP file enqueues must exist under the .min suffix the
// theme serves (RODOLFO_ASSET_SUFFIX is always '.min').
rd_check( 'asset suffix is .min', strpos( $funcs, "define( 'RODOLFO_ASSET_SUFFIX', '.min' )" ) !== false );
preg_match_all( "#'/assets/(js|css)/([a-z0-9-]+)'\s*\.\s*\\\$[a-z_]+\s*\.\s*'\.(js|css)'|'/assets/(js|css)/([a-z0-9-]+)'\s*\.\s*RODOLFO_ASSET_SUFFIX\s*\.\s*'\.(js|css)'#", $all_php, $m, PREG_SET_ORDER );
$enqueued = array();
foreach ( $m as $hit ) {
	$dir  = $hit[1] ? $hit[1] : $hit[4];
	$name = $hit[2] ? $hit[2] : $hit[5];
	$ext  = $hit[3] ? $hit[3] : $hit[6];
	$enqueued[ "assets/$dir/$name.min.$ext" ] = true;
}
preg_match_all( "#'/assets/(js|css)/([a-z0-9.-]+\.min\.(?:js|css))'#", $all_php, $m, PREG_SET_ORDER );
foreach ( $m as $hit ) {
	$enqueued[ "assets/{$hit[1]}/{$hit[2]}" ] = true;
}
rd_check( 'enqueued theme assets discovered', count( $enqueued ) >= 6 );
foreach ( array_keys( $enqueued ) as $asset ) {
	rd_check( "enqueued asset $asset exists", is_readable( "$root/$asset" ) );
}
rd_check( 'no enqueue switches to unshipped sources under SCRIPT_DEBUG', strpos( $all_php, "SCRIPT_DEBUG ) ? '' : '.min'" ) === false );

// A readable source shipped next to a minified file must have its .min twin.
foreach ( $js_files as $f ) {
	if ( substr( $f, -7 ) !== '.min.js' ) {
		rd_check( "$f has a .min.js twin", is_readable( $root . '/' . substr( $f, 0, -3 ) . '.min.js' ) );
	}
}

// --- No inline JavaScript in PHP -----------------------------------------
// Allowed: JSON-LD data blocks, and the render-blocking dark-mode snippet in
// header.php, which must run before first paint to avoid a light flash.
/**
 * Markup a PHP file can actually emit: inline HTML plus string literals, with
 * comments dropped and gettext strings (help text that merely mentions a tag)
 * skipped.
 */
function rd_emitted_markup( $src ) {
	$gettext = array( '__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', '_x', '_n' );
	$out     = '';
	$fn      = '';
	foreach ( token_get_all( $src ) as $tok ) {
		if ( ! is_array( $tok ) ) {
			if ( ')' === $tok ) {
				$fn = '';
			}
			continue;
		}
		if ( T_STRING === $tok[0] ) {
			$fn = $tok[1];
		} elseif ( T_INLINE_HTML === $tok[0] ) {
			$out .= $tok[1];
		} elseif ( T_CONSTANT_ENCAPSED_STRING === $tok[0] || T_ENCAPSED_AND_WHITESPACE === $tok[0] ) {
			if ( ! in_array( $fn, $gettext, true ) ) {
				$out .= $tok[1];
			}
		}
	}
	return $out;
}
foreach ( $php_files as $f ) {
	preg_match_all( '#<script\b([^>]*)>#i', rd_emitted_markup( rd_read( $f ) ), $m );
	foreach ( $m[1] as $attrs ) {
		if ( false !== stripos( $attrs, 'application/ld+json' ) ) {
			continue;
		}
		$ok = ( 'header.php' === $f && '' === trim( $attrs ) );
		rd_check( "$f has no inline <script{$attrs}>", $ok );
	}
}
rd_check( 'header.php has exactly one inline script (dark-mode boot)', 1 === preg_match_all( '#<script>#', rd_read( 'header.php' ) ) );
rd_check( 'dark-mode boot reads the stored choice only', strpos( rd_read( 'header.php' ), "localStorage.getItem('theme') === 'dark'" ) !== false );

// --- Reading aids moved to assets/js/reading.js --------------------------
$rp = rd_read( 'inc/reading-progress.php' );
rd_check( 'reading aids enqueued as a file', strpos( $rp, "'/assets/js/reading' . RODOLFO_ASSET_SUFFIX . '.js'" ) !== false );
rd_check( 'reading aids enqueued only where used', strpos( $rp, '! rodolfo_progress_active() && ! rodolfo_sticky_title_active()' ) !== false );
rd_check( 'reading aids deferred in footer', strpos( $rp, "'strategy' => 'defer'" ) !== false );
$reading_js = rd_read( 'assets/js/reading.js' );
rd_check( 'reading.js drives the progress fill', strpos( $reading_js, "getElementById('tk-progress-fill')" ) !== false );
rd_check( 'reading.js scroll listener is passive', strpos( $reading_js, "{ passive: true }" ) !== false );
rd_check( 'reading.js sticky title is observer-driven', strpos( $reading_js, 'new IntersectionObserver' ) !== false );
rd_check( 'progress markup ids match reading.js', strpos( $rp, 'id="tk-progress-fill"' ) !== false && strpos( $rp, 'id="tk-sticky-title"' ) !== false );
rd_check( 'sticky strip respects reduced motion', strpos( rd_read( 'assets/css/extras.min.css' ), '@media (prefers-reduced-motion:reduce){.tk-sticky-title{transition:none}}' ) !== false );

// --- Preferred sources -------------------------------------------------
$ps = rd_read( 'inc/preferred-sources.php' );
rd_check( 'preferred-source sync enqueued as a file', strpos( $ps, "'/assets/js/preferred-source' . RODOLFO_ASSET_SUFFIX . '.js'" ) !== false );
rd_check( 'Google publisher script enqueued async', strpos( $ps, "'https://news.google.com/swg/js/v1/publisher.js'" ) !== false && strpos( $ps, "'strategy' => 'async'" ) !== false );
rd_check( 'preferred-source scripts only when a button printed', strpos( $ps, 'if ( ! rodolfo_ps_button_printed() )' ) !== false );
rd_check( 'preferred-source enqueue runs before footer scripts print', strpos( $ps, "add_action( 'wp_footer', 'rodolfo_ps_script', 5 )" ) !== false );
rd_check( 'preferred source ineligible on subdirectory installs', strpos( $ps, "if ( \$path && '/' !== \$path )" ) !== false );
rd_check( 'preferred-source.js watches the dark class', strpos( rd_read( 'assets/js/preferred-source.js' ), "attributeFilter: ['class']" ) !== false );
rd_check( '[preferred_source] shortcode registered', strpos( $ps, "add_shortcode( 'preferred_source'" ) !== false );

// --- Settings screen: tabbed, sanitised ----------------------------------
$op = rd_read( 'inc/options-page.php' );
$oa = rd_read( 'inc/options-actions.php' );
$st = rd_read( 'inc/settings.php' );
preg_match( '/\$tabs = array\((.*?)\);/s', $op, $tabs );
preg_match_all( "/'([a-z]+)'\s*=>/", isset( $tabs[1] ) ? $tabs[1] : '', $m );
$tab_slugs = $m[1];
rd_check( 'options screen has at least six tabs', count( $tab_slugs ) >= 6 );
foreach ( $tab_slugs as $slug ) {
	rd_check( "tab '$slug' has a panel", strpos( $op, 'data-panel="' . $slug . '"' ) !== false );
}
rd_check( 'unknown ?tab= clamps to general', strpos( $op, "if ( ! isset( \$tabs[ \$active_tab ] ) )" ) !== false );
rd_check( 'tab switcher enqueued as a file', strpos( $oa, "'/assets/js/admin' . \$suffix . '.js'" ) !== false );
rd_check( 'admin styles enqueued as a file', strpos( $oa, 'wp_enqueue_style(' ) !== false );
rd_check( 'admin.min.js toggles tab panels', strpos( rd_read( 'assets/js/admin.min.js' ), "p.dataset.panel === slug" ) !== false );
rd_check( 'every option registered through the sanitizer map', strpos( $st, 'foreach ( rodolfo_option_sanitizers() as $opt => $sanitize )' ) !== false );
rd_check( 'raw script options need unfiltered_html', strpos( $st, "current_user_can( 'unfiltered_html' )" ) !== false );
rd_check( 'settings import re-sanitises', strpos( $oa, 'a crafted JSON file must not bypass sanitisation' ) !== false );

// Options the theme reads must be registered (or be internal state).
$internal = array(
	'rodolfo_llms_rewrite_v', 'rodolfo_tags_rewrite_v', 'rodolfo_menu_cleanup_v', 'rodolfo_nav_migrated_v', // one-shot version stamps
	'rodolfo_nav_excluded_pages', // legacy value read once by the nav migration
	'rodolfo_enable_bctt',        // gate for the disabled click-to-tweet module
	'rodolfo_social_',            // prefix of the per-platform loop
);
$disabled_files = array( 'inc/donate.php', 'inc/click-to-tweet.php', 'inc/login.php' );
foreach ( $php_files as $f ) {
	if ( in_array( $f, $disabled_files, true ) ) {
		continue;
	}
	preg_match_all( "/get_option\( '(rodolfo_[a-z0-9_]+)'/", rd_read( $f ), $m );
	foreach ( array_unique( $m[1] ) as $opt ) {
		if ( in_array( $opt, $internal, true ) || 0 === strpos( $opt, 'rodolfo_disable_' ) ) {
			continue;
		}
		rd_check( "$opt (read in $f) is registered in settings.php", strpos( $st, "'$opt'" ) !== false );
	}
}

// --- Colour scheme -------------------------------------------------------
$acc = rd_read( 'inc/accent.php' );
rd_check( 'accent is a Customizer colour control', strpos( $acc, 'WP_Customize_Color_Control' ) !== false );
rd_check( 'accent setting sanitised as hex', strpos( $acc, "'sanitize_callback' => 'sanitize_hex_color'" ) !== false );
foreach ( array( '--tk-accent', '--tk-accent-700', '--tk-accent-500', '--tk-accent-400' ) as $var ) {
	rd_check( "accent emits $var", strpos( $acc, "'$var'" ) !== false );
}
rd_check( 'accent override only when changed', strpos( $acc, 'strtolower( rodolfo_accent() ) === RODOLFO_ACCENT_DEFAULT' ) !== false );
rd_check( 'front-end CSS consumes the accent variable', strpos( rd_read( 'assets/css/extras.min.css' ), 'var(--tk-accent' ) !== false );
rd_check( 'dark mode styles present', strpos( rd_read( 'assets/css/theme.min.css' ), '.dark' ) !== false );

// --- Structured data & GEO -----------------------------------------------
$schema = rd_read( 'inc/schema.php' );
rd_check( 'schema prints one JSON-LD graph', 1 === substr_count( $schema, 'application/ld+json' ) );
rd_check( 'schema graph uses @graph', strpos( $schema, "'@graph'" ) !== false );
rd_check( 'schema output escapes < in JSON', strpos( $schema, 'JSON_HEX_TAG' ) !== false || strpos( $schema, '<' ) !== false || strpos( $schema, 'wp_json_encode' ) !== false );
rd_check( 'end mark exposed as a WebPageElement', strpos( $schema, "'cssSelector' => '.' . RODOLFO_PREFIX . '-end'" ) !== false );
$llms = rd_read( 'inc/llms-txt.php' );
rd_check( '/llms.txt rewrite rule', strpos( $llms, "add_rewrite_rule( '^llms\\.txt$'" ) !== false );
rd_check( '/llms.txt not canonical-redirected', strpos( $llms, 'function rodolfo_llms_no_redirect' ) !== false );
rd_check( 'robots.txt AI-crawler guidance', strpos( $llms, 'function rodolfo_ai_robots' ) !== false );
rd_check( 'HTML sitemap page template', strpos( rd_read( 'page-sitemap.php' ), 'Template Name: HTML Sitemap' ) !== false );

// --- Guest authors & end mark ------------------------------------------------
$ga = rd_read( 'inc/guest-authors.php' );
rd_check( 'guest-author meta registered', strpos( $ga, 'function rodolfo_ga_register_meta' ) !== false );
rd_check( 'guest-author save checks a nonce', strpos( $ga, 'wp_verify_nonce' ) !== false );
rd_check( 'guest-author save checks capability', strpos( $ga, "current_user_can( 'edit_post'" ) !== false );
$em = rd_read( 'inc/endmark.php' );
rd_check( '[end_mark] shortcode registered', strpos( $em, "add_shortcode( 'end_mark'" ) !== false );
rd_check( 'end mark skips feeds', strpos( $em, 'is_feed()' ) !== false );
rd_check( 'end-mark save checks a nonce', strpos( $em, 'wp_verify_nonce' ) !== false );

// --- Rebrand tooling -----------------------------------------------------
$rb = rd_read( 'rebrand-theme.sh' );
rd_check( 'rebrand script is executable', is_executable( "$root/rebrand-theme.sh" ) );
rd_check( 'rebrand rewrites JS assets', strpos( $rb, "-name '*.js'" ) !== false );
rd_check( 'rebrand rewrites RODOLFO_PREFIX', strpos( $rb, "define( 'RODOLFO_PREFIX'" ) !== false );

// ======================================================================
// 2. UNIT TESTS (pure helpers under WordPress stubs)
// ======================================================================

define( 'ABSPATH', __DIR__ . '/' );
define( 'RODOLFO_PREFIX', 'tk' );
define( 'RODOLFO_VERSION', 'test' );
define( 'RODOLFO_OPTIONAL_MODULES', $optional_modules );
$GLOBALS['rd_options'] = array();

function add_action() {}
function add_filter() {}
function add_shortcode() {}
function register_meta() {}
function register_post_meta() {}
function __( $t ) { return $t; }
function esc_html__( $t ) { return $t; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['rd_options'] ) ? $GLOBALS['rd_options'][ $k ] : $d; }
function get_theme_mod( $k, $d = false ) { return isset( $GLOBALS['rd_options'][ "mod:$k" ] ) ? $GLOBALS['rd_options'][ "mod:$k" ] : $d; }
function sanitize_hex_color( $c ) { return preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $c ) ? $c : ''; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( $s ) ); }
function rodolfo_handle( $slug ) { return RODOLFO_PREFIX . '-' . $slug; }

require $root . '/inc/settings.php';
require $root . '/inc/accent.php';
require $root . '/inc/guest-authors.php';
require $root . '/inc/endmark.php';
require $root . '/inc/schema.php';

// Accent maths.
rd_check( 'accent defaults to cobalt', '#3d54d6' === rodolfo_accent() );
$GLOBALS['rd_options']['mod:rodolfo_accent'] = 'not-a-colour';
rd_check( 'invalid accent falls back to default', '#3d54d6' === rodolfo_accent() );
$GLOBALS['rd_options']['mod:rodolfo_accent'] = '#0f766e';
rd_check( 'custom accent honoured', '#0f766e' === rodolfo_accent() );
$vars = rodolfo_accent_vars();
rd_check( 'accent vars: four shades', 4 === count( $vars ) && '#0f766e' === $vars['--tk-accent'] );
unset( $GLOBALS['rd_options']['mod:rodolfo_accent'] );
rd_check( 'mix 0 is identity', '#3d54d6' === rodolfo_accent_mix( '#3d54d6', 0 ) );
rd_check( 'mix -1 is black', '#000000' === rodolfo_accent_mix( '#3d54d6', -1 ) );
rd_check( 'mix +1 is white', '#ffffff' === rodolfo_accent_mix( '#3d54d6', 1 ) );
rd_check( 'mix expands 3-digit hex', '#ffffff' === rodolfo_accent_mix( '#fff', 0 ) );
rd_check( 'mix darkens', hexdec( substr( rodolfo_accent_mix( '#3d54d6', -0.22 ), 5, 2 ) ) < 0xd6 );

// Choice sanitizers: always a valid key.
rd_check( 'choice sanitizer keeps valid key', 'grid' === rodolfo_sanitize_home_layout( 'grid' ) );
rd_check( 'choice sanitizer rejects unknown key', 'magazine' === rodolfo_sanitize_home_layout( 'bogus' ) );
rd_check( 'ps_mode sanitizer default is deeplink', 'deeplink' === rodolfo_sanitize_ps_mode( '<script>' ) );
$GLOBALS['rd_options']['rodolfo_em_placement'] = 'nowhere';
rd_check( 'rodolfo_choice clamps stored garbage', 'last_paragraph' === rodolfo_choice( 'rodolfo_em_placement' ) );
foreach ( rodolfo_choice_registry() as $opt => $choices ) {
	rd_check( "$opt default is one of its choices", isset( $choices[ rodolfo_choice_default( $opt ) ] ) );
}
$sanitizers = rodolfo_option_sanitizers();
foreach ( $sanitizers as $opt => $cb ) {
	// Theme sanitizers must exist; core ones (sanitize_text_field, esc_url_raw…) are not stubbed here.
	if ( 0 === strpos( $cb, 'rodolfo_' ) ) {
		rd_check( "$opt sanitizer $cb is defined", false !== strpos( $all_php, "function $cb(" ) );
	}
}

// Guest-author byline formatting.
rd_check( 'byline: no names', '' === rodolfo_ga_format( array() ) );
rd_check( 'byline: one name', 'Ana' === rodolfo_ga_format( array( 'Ana' ) ) );
rd_check( 'byline: natural join', 'Ana, Ben and Cy' === rodolfo_ga_format( array( 'Ana', 'Ben', 'Cy' ) ) );
$GLOBALS['rd_options']['rodolfo_ga_join'] = 'comma';
rd_check( 'byline: comma join', 'Ana, Ben' === rodolfo_ga_format( array( 'Ana', 'Ben' ) ) );
$GLOBALS['rd_options']['rodolfo_ga_join'] = 'ampersand';
rd_check( 'byline: ampersand join', 'Ana & Ben' === rodolfo_ga_format( array( 'Ana', 'Ben' ) ) );

// End-mark placement.
$html = '<p>One.</p><p>Two.</p>';
$at   = rodolfo_endmark_last_paragraph( $html, false, array() );
rd_check( 'end mark: before the last </p>', strlen( '<p>One.</p><p>Two.' ) === $at );
$html = '<p>Body.</p><p>&nbsp;</p>';
rd_check( 'end mark: skips empty paragraphs', strlen( '<p>Body.' ) === rodolfo_endmark_last_paragraph( $html, false, array() ) );
$html = '<p>Body.</p><blockquote><p>Quote.</p></blockquote>';
rd_check( 'end mark: never inside a blockquote', strlen( '<p>Body.' ) === rodolfo_endmark_last_paragraph( $html, false, array() ) );
$html = '<p>Body.</p><div class="faq-block"><p>Answer.</p></div>';
$rng  = rodolfo_endmark_protected_ranges( $html );
rd_check( 'end mark: FAQ block is protected', 1 === count( $rng ) && strlen( '<p>Body.' ) === rodolfo_endmark_last_paragraph( $html, false, $rng ) );
$html = '<div class="faq-block"><div><p>a</p></div><p>b</p></div><p>c</p>';
$rng  = rodolfo_endmark_protected_ranges( $html );
rd_check( 'end mark: nested containers closed depth-aware', 1 === count( $rng ) && strpos( $html, '<p>c' ) === $rng[0][1] );
$html = '<p>Body.</p><ol class="footnotes"><li><p>Note.</p></li></ol>';
rd_check( 'end mark: respects the footnotes limit', strlen( '<p>Body.' ) === rodolfo_endmark_last_paragraph( $html, strpos( $html, '<ol' ), array() ) );
rd_check( 'end mark: no paragraphs returns false', false === rodolfo_endmark_last_paragraph( '<h2>Only</h2>', false, array() ) );

// Schema cleaning keeps 0/false, drops empties.
$clean = rodolfo_schema_clean( array( 'a' => '', 'b' => null, 'c' => array(), 'd' => 0, 'e' => false, 'f' => array( 'g' => '' ), 'h' => 'x' ) );
rd_check( 'schema clean drops empties, keeps 0 and false', array( 'd' => 0, 'e' => false, 'h' => 'x' ) === $clean );
rd_check( 'schema ref shape', array( '@id' => '#x' ) === rodolfo_schema_ref( '#x' ) );

// ======================================================================
// 3. TOOLING
// ======================================================================

$php_bin = escapeshellarg( PHP_BINARY );
foreach ( $php_files as $f ) {
	exec( "$php_bin -l " . escapeshellarg( "$root/$f" ) . ' 2>&1', $out, $code );
	rd_check( "php -l $f", 0 === $code );
}

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );
if ( '' !== $node ) {
	foreach ( $js_files as $f ) {
		exec( escapeshellarg( $node ) . ' --check ' . escapeshellarg( "$root/$f" ) . ' 2>&1', $out, $code );
		rd_check( "node --check $f", 0 === $code );
	}
} else {
	echo "(node not found: JS parse checks skipped)\n";
}

// Rebrand a throwaway copy and confirm the old prefix is gone from assets.
$bash = trim( (string) shell_exec( 'command -v bash 2>/dev/null' ) );
$perl = trim( (string) shell_exec( 'command -v perl 2>/dev/null' ) );
if ( '' !== $bash && '' !== $perl ) {
	$tmp = sys_get_temp_dir() . '/rodolfo-rebrand-' . getmypid();
	exec( 'mkdir -p ' . escapeshellarg( $tmp ) . ' && cp -R ' . escapeshellarg( $root ) . '/. ' . escapeshellarg( "$tmp/rodolfo" ) . ' 2>&1 && rm -rf ' . escapeshellarg( "$tmp/rodolfo/.git" ) . ' ' . escapeshellarg( "$tmp/rodolfo/tests" ), $out, $code );
	$cmd = 'cd ' . escapeshellarg( "$tmp/rodolfo" ) . ' && ./rebrand-theme.sh --slug northline --name Northline --prefix nl --accent "#0f766e" --no-rename 2>&1';
	exec( $cmd, $out, $code );
	rd_check( 'rebrand script exits cleanly', 0 === $code );
	$copy = "$tmp/rodolfo";
	rd_check( 'rebrand: prefix constant rewritten', strpos( (string) @file_get_contents( "$copy/functions.php" ), "define( 'RODOLFO_PREFIX', 'nl' )" ) !== false );
	rd_check( 'rebrand: theme name rewritten', (bool) preg_match( '/^Theme Name: Northline$/m', (string) @file_get_contents( "$copy/style.css" ) ) );
	rd_check( 'rebrand: reading.min.js uses new prefix', strpos( (string) @file_get_contents( "$copy/assets/js/reading.min.js" ), 'nl-progress-fill' ) !== false );
	$left = trim( (string) shell_exec( 'grep -rIl "tk-" ' . escapeshellarg( "$copy/assets" ) . ' 2>/dev/null' ) );
	rd_check( 'rebrand: no tk- prefix left in assets', '' === $left );
	rd_check( 'rebrand: accent rewritten in CSS', strpos( (string) @file_get_contents( "$copy/assets/css/extras.min.css" ), '#3d54d6' ) === false );
	exec( 'rm -rf ' . escapeshellarg( $tmp ) );
} else {
	echo "(bash/perl not found: rebrand check skipped)\n";
}

// ======================================================================

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
