<?php
/**
 * Theme functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Theme version — single constant so PHP never re-reads style.css per call.
if ( ! defined( 'RODOLFO_VERSION' ) ) {
    define( 'RODOLFO_VERSION', wp_get_theme()->get( 'Version' ) );
}

/**
 * Front-end prefix for CSS classes, HTML ids and script/style handles.
 * Static assets ship with the same default prefix; rebrand-theme.sh rewrites
 * both together so each install gets its own vocabulary.
 */
if ( ! defined( 'RODOLFO_PREFIX' ) ) {
    define( 'RODOLFO_PREFIX', 'tk' );
}

/** Build a prefixed handle / id, e.g. rodolfo_handle( 'theme' ) => 'tk-theme'. */
function rodolfo_handle( $slug ) {
    return RODOLFO_PREFIX . '-' . $slug;
}

// Every shipped asset is already minified.
if ( ! defined( 'RODOLFO_ASSET_SUFFIX' ) ) {
    define( 'RODOLFO_ASSET_SUFFIX', '.min' );
}

// Feature-toggle whitelist (Theme Options → Features). Slugs only; labels in
// settings.php. Structural modules and modules owning content structures are
// deliberately not offered — disabling those breaks data, not a feature.
const RODOLFO_OPTIONAL_MODULES = array( 'toc', 'related-posts', 'search-palette',
    'tags', 'unlist', 'aeo', 'llms-txt', 'breadcrumbs',
    'reading-progress', 'views', 'author-social', 'privacy-embeds',
    'comment-guard', 'post-display', 'guest-authors', 'endmark', 'footnotes' );

foreach ( array(
    'icons',          // inline SVG icon library
    'nav',            // Page-driven nav, privacy resolver, footer utility links
    'system-report',  // sectioned diagnostics + JSON export for System Info
    'settings',       // option registry + sanitizer map (the persistence contract)
    'options-actions',// admin CSS, export/import/reset, menu registration
    'options-page',   // Theme Options screen markup
    'maintenance',    // maintenance / coming-soon holding page
    'performance',    // head cleanup, emoji/embed toggles, script injection
    'meta-tags',      // titles, descriptions, canonical, robots, OG/Twitter
    'schema',         // JSON-LD @graph (Schema.org)
    'social-card',    // generated share card when no featured image
    'favicon',        // site-icon fallback until a core Site Icon is set
    'category-color', // per-category accent colour
    'toc',            // auto table of contents on long-form posts
    'related-posts',  // related posts on single view
    'search-palette', // ⌘K live search overlay
    'tags',           // built-in virtual /tags/ page
    // 'click-to-tweet', // disabled: non-GEO footprint module
    'unlist',         // hide posts from listings, keep single URL live
    'aeo',            // [answer], [faqs]/[faq], [compare], [proof], [howto]
    'llms-txt',       // GEO: virtual /llms.txt + AI-crawler robots guidance
    'preferred-sources', // Google Search "Preferred Sources" link / button
    'breadcrumbs',    // visible breadcrumb trail (shared with the schema graph)
    // 'login',       // disabled: non-GEO footprint module
    'reading-progress', // scroll reading-progress bar on posts/pages
    // 'donate',      // disabled: non-GEO footprint module
    'fonts',          // optional self-hosted WOFF2 (system stack by default)
    'setup',          // idempotent page/menu scaffolding
    'editor',         // block editor parity: styles, palette, font sizes
    'reading-time',   // reading-time estimate, post series, feed content
    'sidebar',        // optional widget area (off by default)
    'page-states',    // coming-soon pages + site-updated stamp
    'views',          // per-post view count + popular/trending helpers
    'privacy-embeds', // consent-gated iframe embeds (off by default)
    'comment-guard',  // honeypot + time trap + optional Turnstile (off by default)
    'accent',         // Customizer accent colour → the accent CSS variables
    'post-display',   // per-post hide-title / hide-featured / full-width flags
    'author-social',  // per-user contact fields for the author card
    'guest-authors',  // per-post custom bylines (one or several names)
    'endmark',        // end-of-article symbol after the last paragraph
    'footnotes',      // ((note)) footnotes with linked list under the article
    'content-hygiene',// image alt/size, nofollow toggle, comment link rel, meta boxes
) as $rodolfo_module ) {
    // A disabled optional module is never even required; templates guard every
    // optional helper with function_exists.
    if ( in_array( $rodolfo_module, RODOLFO_OPTIONAL_MODULES, true ) && get_option( 'rodolfo_disable_' . $rodolfo_module ) ) {
        continue;
    }
    require_once get_template_directory() . '/inc/' . $rodolfo_module . '.php';
}
unset( $rodolfo_module );

function rodolfo_setup() {
    // Load theme translations. Must be the first call in setup.
    load_theme_textdomain( 'rodolfo', get_template_directory() . '/languages' );

    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );

    // Register menus. The footer "Explore Topics" column is content-driven
    // (top categories), so it has no menu location — only header + secondary.
    register_nav_menus( array(
        'primary'   => esc_html__( 'Primary Menu (header)', 'rodolfo' ),
        'secondary' => esc_html__( 'Secondary Menu (below copyright)', 'rodolfo' ),
    ) );

    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );

    // Custom logo — lets users upload a site logo via Appearance → Customize.
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 80,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'rodolfo_setup' );

/**
 * Front-end assets.
 *
 * Core CSS (assets/css/theme.min.css) carries the layout; extras.min.css holds
 * the optional-module styles. No UI-toolkit library, no web fonts, no
 * jQuery-dependent scripts. With "Inline the theme CSS" on, the core sheet is
 * printed in a <style> tag and there is no render-blocking CSS request.
 */
function rodolfo_scripts() {
    $version = RODOLFO_VERSION;
    $css_dir = get_template_directory() . '/assets/css/';
    $css_uri = get_template_directory_uri() . '/assets/css/';
    $handle  = rodolfo_handle( 'theme' );

    if ( get_option( 'rodolfo_inline_css' ) && is_readable( $css_dir . 'theme.min.css' ) ) {
        wp_register_style( $handle, false, array(), $version );
        wp_enqueue_style( $handle );
        wp_add_inline_style( $handle, (string) file_get_contents( $css_dir . 'theme.min.css' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
    } else {
        wp_enqueue_style( $handle, $css_uri . 'theme.min.css', array(), $version );
    }
    wp_enqueue_style( rodolfo_handle( 'extras' ), $css_uri . 'extras.min.css', array( $handle ), $version );

    // Dark-mode toggle, share/bookmark, mobile menu, TOC, search palette.
    wp_enqueue_script(
        rodolfo_handle( 'script' ),
        get_template_directory_uri() . '/assets/js/main' . RODOLFO_ASSET_SUFFIX . '.js',
        array(),
        $version,
        array( 'in_footer' => true, 'strategy' => 'defer' )
    );
    wp_localize_script( rodolfo_handle( 'script' ), 'tkL10n', array(
        'share'          => __( 'Share', 'rodolfo' ),
        'linkCopied'     => __( 'Link copied!', 'rodolfo' ),
        'bookmark'       => __( 'Bookmark', 'rodolfo' ),
        'removeBookmark' => __( 'Remove bookmark', 'rodolfo' ),
    ) );

    // Reading-position memory: opt-in, posts only.
    if ( is_singular( 'post' ) && get_option( 'rodolfo_resume_enabled' ) ) {
        wp_enqueue_script(
            rodolfo_handle( 'resume' ),
            get_template_directory_uri() . '/assets/js/resume' . RODOLFO_ASSET_SUFFIX . '.js',
            array(),
            $version,
            array( 'in_footer' => true, 'strategy' => 'defer' )
        );
    }

    // Threaded replies.
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'rodolfo_scripts' );

/**
 * Optional assets stay off the front end unless explicitly enabled: the
 * click-to-tweet block script is editor-only and its module ships disabled.
 */
function rodolfo_dequeue_optional_assets() {
    if ( ! get_option( 'rodolfo_enable_bctt' ) ) {
        wp_dequeue_script( rodolfo_handle( 'bctt-block' ) );
        wp_deregister_script( rodolfo_handle( 'bctt-block' ) );
    }
    if ( ! ( is_singular( 'post' ) && get_option( 'rodolfo_resume_enabled' ) ) ) {
        wp_dequeue_script( rodolfo_handle( 'resume' ) );
    }
}
add_action( 'wp_enqueue_scripts', 'rodolfo_dequeue_optional_assets', 100 );

// ============================================================
// SECURITY HARDENING
// ============================================================

// XML-RPC off, and no pingback advertisement.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function ( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
} );
add_filter( 'pings_open', '__return_false', 20 );

// Author enumeration: /?author=N → home (301). Logged-in editors are exempt so
// admin screens that rely on the query keep working.
function rodolfo_block_author_enumeration() {
    if ( is_admin() || is_user_logged_in() ) {
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification -- read-only routing check, value never output.
    if ( isset( $_GET['author'] ) && '' !== (string) $_GET['author'] && preg_match( '/^\d+$/', (string) wp_unslash( $_GET['author'] ) ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'rodolfo_block_author_enumeration', 1 );
// Run before the canonical redirect can turn /?author=N into /author/name/.
add_action( 'parse_request', function () {
    if ( ! is_admin() && ! is_user_logged_in() && isset( $_GET['author'] ) && preg_match( '/^\d+$/', (string) wp_unslash( $_GET['author'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}, 0 );

// REST: no unauthenticated user listing.
add_filter( 'rest_endpoints', function ( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        foreach ( array_keys( $endpoints ) as $route ) {
            if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
                unset( $endpoints[ $route ] );
            }
        }
    }
    return $endpoints;
} );


/**
 * Excerpt length. Two contexts, since one number does not fit both: cards on
 * the index and archives need to be terse (there is other structure around
 * them), the search results list benefits from a longer teaser so a matching
 * keyword actually appears in the snippet. Filterable — the intent is a
 * sensible default per context, not a hard rule.
 */
function rodolfo_custom_excerpt_length( $length ) {
    $context = 'default';
    if ( is_search() ) {
        $context = 'search';
    } elseif ( is_home() || is_front_page() || is_archive() ) {
        $context = 'card';
    }

    $lengths = array(
        'card'    => (int) get_option( 'rodolfo_excerpt_length_card', 24 ),
        'search'  => (int) get_option( 'rodolfo_excerpt_length_search', 40 ),
        'default' => (int) $length,
    );

    /** Filter the excerpt length used for a given context. */
    return (int) apply_filters( 'rodolfo_excerpt_length', $lengths[ $context ], $context );
}
add_filter( 'excerpt_length', 'rodolfo_custom_excerpt_length', 999 );

// Get reading time. Unicode-aware word counting — str_word_count() only
// recognises ASCII letters and undercounts (or zeroes out) CJK/multibyte text.
/**
 * Back-compat wrapper. The reading-time engine lives in inc/reading-time.php
 * (code-weighted, filterable); this earlier 200wpm implementation was
 * superseded in 3.3.2 after 3.2.0 accidentally shipped both, which could show
 * two different estimates on the same post. Templates and child themes calling
 * this keep working; returns '' when the display option is switched off.
 */
function rodolfo_get_reading_time() {
    if ( ! get_option( 'rodolfo_reading_time_enabled', '1' ) ) {
        return '';
    }
    return function_exists( 'rodolfo_reading_time_text' ) ? esc_html( rodolfo_reading_time_text() ) : '';
}

// Add Category Icon Meta Field
function rodolfo_category_add_meta_field() {
    ?>
    <div class="form-field">
        <?php wp_nonce_field( 'rodolfo_save_category_icon', 'rodolfo_category_icon_nonce' ); ?>
        <label for="category_icon"><?php _e( 'Category Icon (Emoji)', 'rodolfo' ); ?></label>
        <input type="text" name="category_icon" id="category_icon" value="" />
        <p class="description"><?php _e( 'Enter an emoji to use as the icon for this category on the category archive page (e.g., ⚡, 📊, 💻).', 'rodolfo' ); ?></p>
    </div>
    <?php
}
add_action( 'category_add_form_fields', 'rodolfo_category_add_meta_field', 10, 2 );

function rodolfo_category_edit_meta_field( $term ) {
    $term_id = $term->term_id;
    $category_icon = get_term_meta( $term_id, 'category_icon', true );
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="category_icon"><?php _e( 'Category Icon (Emoji)', 'rodolfo' ); ?></label></th>
        <td>
            <?php wp_nonce_field( 'rodolfo_save_category_icon', 'rodolfo_category_icon_nonce' ); ?>
            <input type="text" name="category_icon" id="category_icon" value="<?php echo esc_attr( $category_icon ) ? esc_attr( $category_icon ) : ''; ?>" />
            <p class="description"><?php _e( 'Enter an emoji to use as the icon for this category on the category archive page (e.g., ⚡, 📊, 💻).', 'rodolfo' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'category_edit_form_fields', 'rodolfo_category_edit_meta_field', 10, 2 );

function rodolfo_save_category_meta( $term_id ) {
    // Verify nonce before processing any input.
    $nonce = isset( $_POST['rodolfo_category_icon_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['rodolfo_category_icon_nonce'] ) )
        : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rodolfo_save_category_icon' ) ) {
        return;
    }

    // Confirm the current user has permission to edit terms.
    if ( ! current_user_can( 'manage_categories' ) ) {
        return;
    }

    if ( isset( $_POST['category_icon'] ) ) {
        update_term_meta( $term_id, 'category_icon', sanitize_text_field( wp_unslash( $_POST['category_icon'] ) ) );
    }
}
add_action( 'edited_category', 'rodolfo_save_category_meta', 10, 2 );  
add_action( 'create_category', 'rodolfo_save_category_meta', 10, 2 );

// Customizer settings
function rodolfo_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'rodolfo_background_section' , array(
        'title'      => __( 'Background Media', 'rodolfo' ),
        'priority'   => 30,
    ) );

    $wp_customize->add_setting( 'rodolfo_background_video', array(
        'default'           => '',
        'transport'         => 'refresh',
        'sanitize_callback' => 'esc_url_raw',
    ) );

    $wp_customize->add_control( 'rodolfo_background_video', array(
        'label'      => __( 'Background Video URL (mp4)', 'rodolfo' ),
        'description'=> __( 'Enter the URL of an MP4 video to use as the background instead of the static image.', 'rodolfo' ),
        'section'    => 'rodolfo_background_section',
        'type'       => 'url',
    ) );
}
add_action( 'customize_register', 'rodolfo_customize_register' );

// Comment template callback
function rodolfo_render_author_card() {
    $bio = get_the_author_meta( 'description' );
    if ( '' === trim( (string) $bio ) || ( function_exists( 'rodolfo_has_guest_author' ) && rodolfo_has_guest_author() ) ) {
        return;
    }
    $name      = get_the_author();
    $author_id = (int) get_the_author_meta( 'ID' );
    ?>
    <section class="max-w-4xl mx-auto mt-12 md:mt-16" aria-label="<?php esc_attr_e( 'About the author', 'rodolfo' ); ?>">
        <div class="flex flex-col sm:flex-row gap-5 items-start rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 p-6 md:p-8">
            <?php echo get_avatar( $author_id, 72, '', $name, array( 'class' => 'w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-700 flex-shrink-0' ) ); ?>
            <div class="min-w-0">
                <span class="block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1"><?php esc_html_e( 'Written by', 'rodolfo' ); ?></span>
                <h2 class="text-xl font-display font-bold text-slate-900 dark:text-white mb-2"><?php echo esc_html( $name ); ?></h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-3"><?php echo esc_html( $bio ); ?></p>
                <?php
                $rodolfo_socials = function_exists( 'rodolfo_author_social_links' ) ? rodolfo_author_social_links( $author_id ) : array();
                if ( ! empty( $rodolfo_socials ) ) :
                    ?>
                    <div class="flex flex-wrap items-center gap-2 mb-3" aria-label="<?php esc_attr_e( 'Author links', 'rodolfo' ); ?>">
                        <?php foreach ( $rodolfo_socials as $rodolfo_s ) : ?>
                            <a href="<?php echo esc_url( $rodolfo_s['url'] ); ?>"
                               rel="me noopener"
                               target="_blank"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-600 dark:hover:border-blue-400 transition-colors"
                               aria-label="<?php echo esc_attr( sprintf( /* translators: %1$s: author name, %2$s: platform */ __( '%1$s on %2$s', 'rodolfo' ), $name, $rodolfo_s['label'] ) ); ?>">
                                <?php echo rodolfo_icon( $rodolfo_s['icon'], 'w-4 h-4' ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-blue-600 dark:text-blue-400 hover:gap-2 transition-all">
                    <?php
                    /* translators: %s: author name */
                    printf( esc_html__( 'More from %s', 'rodolfo' ), esc_html( $name ) );
                    echo rodolfo_icon( 'chevron-right', 'w-4 h-4' );
                    ?>
                </a>
            </div>
        </div>
    </section>
    <?php
}

function rodolfo_comment( $comment, $args, $depth ) {
    $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
    ?>
    <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( 'group', $comment ); ?>>
        <div class="flex gap-4">
            <div class="flex-shrink-0">
                <?php echo get_avatar( $comment, 44, '', '', array( 'class' => 'w-11 h-11 rounded-full bg-slate-100 dark:bg-slate-800' ) ); ?>
            </div>
            <div class="flex-grow min-w-0">
                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl px-5 py-4">
                    <div class="flex items-baseline gap-3 mb-2 flex-wrap">
                        <span class="text-sm font-semibold text-slate-900 dark:text-white">
                            <?php comment_author_link( $comment ); ?>
                        </span>
                        <time class="text-xs text-slate-500 dark:text-slate-400" datetime="<?php comment_date( 'c' ); ?>">
                            <?php comment_date( get_option( 'date_format' ) ); ?>
                        </time>
                        <?php if ( '0' === $comment->comment_approved ) : ?>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                <?php esc_html_e( 'Awaiting moderation', 'rodolfo' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed comment-content">
                        <?php comment_text(); ?>
                    </div>
                </div>
                <div class="mt-2 ml-2">
                    <?php
                    comment_reply_link( array_merge( $args, array(
                        'add_below' => 'comment',
                        'depth'     => $depth,
                        'max_depth' => $args['max_depth'],
                        'before'    => '<span class="text-xs font-medium text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer">',
                        'after'     => '</span>',
                    ) ) );
                    ?>
                </div>
            </div>
        </div>
    </<?php echo $tag; ?>>
    <?php
}
