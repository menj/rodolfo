<?php
/**
 * Rodolfo Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly.
}

// Theme version — single constant so PHP never re-reads style.css per call.
if ( ! defined( 'RODOLFO_VERSION' ) ) {
    define( 'RODOLFO_VERSION', wp_get_theme()->get( 'Version' ) );
}

// Asset suffix: serve un-minified sources under SCRIPT_DEBUG, minified live.
// Every source asset ships with a .min twin — editing a source WITHOUT
// regenerating its .min file changes nothing on a live site.
if ( ! defined( 'RODOLFO_ASSET_SUFFIX' ) ) {
    define( 'RODOLFO_ASSET_SUFFIX', ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' );
}

// Feature modules. The first block is structural (extracted from this file in
// 3.4.0) and loads first: icons and nav define helpers the templates and other
// modules call at render time.
// Feature-toggle whitelist (Theme Options → Features). Slugs only; labels in
// settings.php. Structural modules and modules owning content structures are
// deliberately not offered — disabling those breaks data, not a feature.
const RODOLFO_OPTIONAL_MODULES = array( 'toc', 'related-posts', 'search-palette',
    'tags', 'click-to-tweet', 'unlist', 'aeo', 'llms-txt', 'breadcrumbs',
    'reading-progress', 'donate', 'views', 'author-social', 'privacy-embeds',
    'comment-guard', 'post-display' );

foreach ( array(
    'icons',          // inline SVG icon library (rodolfo_icon)
    'nav',            // Page-driven nav, privacy resolver, footer utility links
    'system-report',  // sectioned diagnostics + JSON export for System Info
    'settings',       // option registry + sanitizer map (the persistence contract)
    'options-actions',// admin CSS, export/import/reset, menu registration
    'options-page',   // Theme Options screen markup
    'maintenance',    // maintenance / coming-soon holding page
    'performance',    // emoji/embed/jQuery-Migrate toggles + script injection
    'meta-tags',      // SEO description / OG / Twitter (self-disables under an SEO plugin)
    'schema',         // JSON-LD structured data
    'social-card',    // generated share card when no featured image
    'favicon',        // site-icon fallback until a core Site Icon is set
    'category-color', // per-category accent colour
    'toc',            // auto table of contents on long-form posts
    'related-posts',  // related posts on single view
    'search-palette', // ⌘K live search overlay
    'tags',           // built-in virtual /tags/ page (no setup needed)
    'click-to-tweet', // [bctt] click-to-tweet quote blocks
    'unlist',         // hide posts from listings, keep single URL live
    'aeo',            // AI-answer blocks: [answer], [faqs]/[faq] + FAQPage JSON-LD, [compare]
    'llms-txt',       // GEO: virtual /llms.txt site summary + AI-crawler robots guidance
    'breadcrumbs',    // visible breadcrumb trail (defers to Rank Math/Yoast)
    'login',          // branded wp-login screen
    'reading-progress', // scroll reading-progress bar on posts/pages
    'donate',         // [donate] button (presentational; gateway stays a plugin)
    'fonts',          // self-hosted Inter (only if the woff2 is present)
    'setup',          // idempotent page/menu scaffolding
    'docs',           // renders docs/*.md inside Theme Options → Documentation
    'editor',         // block editor parity: styles, palette, font sizes
    'reading-time',   // reading-time estimate, post series, feed content
    'sidebar',        // optional widget area (off by default)
    'page-states',    // coming-soon pages + site-updated stamp
    'views',          // per-post view count + popular/trending helpers
    'privacy-embeds', // consent-gated iframe embeds (off by default)
    'comment-guard',  // honeypot + time trap + optional Turnstile (off by default)
    'accent',         // Customizer accent colour → the --rodolfo-accent variables
    'post-display',   // per-post hide-title / hide-featured / full-width flags
    'author-social',  // per-user contact fields for the author card
) as $rodolfo_module ) {
    // A disabled optional module is never even required; templates guard every
    // optional helper with function_exists (kept so by the undefined-call test).
    if ( in_array( $rodolfo_module, RODOLFO_OPTIONAL_MODULES, true ) && get_option( 'rodolfo_disable_' . $rodolfo_module ) ) {
        continue;
    }
    require_once get_template_directory() . '/inc/' . $rodolfo_module . '.php';
}
unset( $rodolfo_module );

function rodolfo_setup() {
    // Load theme translations. Must be the first call in setup.
    load_theme_textdomain( 'rodolfo', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title.
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support( 'post-thumbnails' );

    // Register menus. The footer "Explore Topics" column is content-driven
    // (top categories), so it has no menu location — only header + secondary.
    register_nav_menus( array(
        'primary'   => esc_html__( 'Primary Menu (header)', 'rodolfo' ),
        'secondary' => esc_html__( 'Secondary Menu (below copyright)', 'rodolfo' ),
    ) );

    // Add theme support for HTML5 markup.
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Custom logo — lets users upload a site logo via Appearance → Customize.
    // Replaces the admin email Gravatar in the header and footer when set.
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 80,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // Block editor support — wide/full alignments and responsive embed wrappers.
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'rodolfo_setup' );

function rodolfo_scripts() {
    $version = RODOLFO_VERSION;

    // Rodolfo UI layer (bundled locally, MIT — vendored UI framework, name-
    // neutral by design). All of its rules are ancestor-scoped under `.rodolfo-ui`
    // (its `:root`/`[data-bs-theme]` variables kept global), so the UI framework —
    // including its many `!important` utilities — only applies inside opt-in
    // `.rodolfo-ui` containers and can never touch the theme's own markup.
    // Enqueued first as a dependency of the core sheet for predictable order.
    wp_enqueue_style(
        'rodolfo-ui',
        get_template_directory_uri() . '/assets/lib/ui.min.css',
        array(),
        $version
    );

    // Compiled core CSS — generated from assets/css/input.css via the CLI.
    wp_enqueue_style(
        'rodolfo-theme',
        get_template_directory_uri() . '/assets/css/theme.min.css',
        array( 'rodolfo-ui' ),
        $version
    );

    // Theme stylesheet — the header plus a runtime layer that overrides the
    // compiled sheet (see the note at the top of style.css). Loads after
    // theme.min.css by dependency so equal-specificity rules win.
    wp_enqueue_style( 'rodolfo-style', get_stylesheet_uri(), array( 'rodolfo-theme' ), $version );

    // Theme script — dark mode toggle, share button, bookmark button, mobile menu.
    // Icons are inlined server-side via rodolfo_icon(), so there is no external
    // icon library dependency (WordPress.org disallows CDN-loaded scripts, and
    // JS-rendered icons flash in late and disappear entirely without JS).
    // RODOLFO_ASSET_SUFFIX serves main.min.js live and main.js under SCRIPT_DEBUG.
    wp_enqueue_script(
        'rodolfo-script',
        get_template_directory_uri() . '/assets/js/main' . RODOLFO_ASSET_SUFFIX . '.js',
        array(),
        $version,
        true
    );

    // Translatable strings for main.js — the share/bookmark aria-labels were
    // previously hardcoded English, the theme's only untranslated strings.
    wp_localize_script( 'rodolfo-script', 'rodolfoL10n', array(
        'share'          => __( 'Share', 'rodolfo' ),
        'linkCopied'     => __( 'Link copied!', 'rodolfo' ),
        'bookmark'       => __( 'Bookmark', 'rodolfo' ),
        'removeBookmark' => __( 'Remove bookmark', 'rodolfo' ),
    ) );

    // Rodolfo UI JS bundle (Popper included) — modals, dropdowns, collapse,
    // carousel, tabs, tooltips. Bundled locally (no CDN), loaded in the footer.
    wp_enqueue_script(
        'rodolfo-ui',
        get_template_directory_uri() . '/assets/lib/ui.bundle.min.js',
        array(),
        $version,
        true
    );

    // Reading-position memory: only where there is a long form to remember.
    if ( is_singular( 'post' ) && get_option( 'rodolfo_resume_enabled' ) ) {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        wp_enqueue_script(
            'rodolfo-resume',
            get_template_directory_uri() . '/assets/js/resume' . $suffix . '.js',
            array(),
            $version,
            true
        );
    }

    // Threaded replies. Without this, "Reply" only jumps to the form and every
    // comment lands flat at depth 1, regardless of the Discussion settings.
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'rodolfo_scripts' );

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
    if ( '' === trim( (string) $bio ) ) {
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
