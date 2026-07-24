<!DOCTYPE html>
<html <?php language_attributes(); ?> class="antialiased">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Typography: the font-sans stack falls back to system-ui when Inter is not
    // installed locally. Remote Google Fonts requests were removed (GDPR — a
    // German court has ruled embedding Google Fonts unlawful without consent,
    // and WordPress.org flags remote font loading). To restore Inter, self-host
    // the woff2 files in assets/fonts/ and add @font-face rules to input.css.
    ?>
    <script>
        // Dark mode is opt-in: the site starts light unless the visitor has
        // explicitly chosen dark via the toggle. The OS `prefers-color-scheme`
        // setting is deliberately NOT consulted for the initial state — a
        // stored choice is the only thing that turns dark mode on.
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class('min-h-screen flex flex-col font-sans bg-white dark:bg-slate-950 transition-colors relative text-slate-900 dark:text-slate-100'); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'rodolfo' ); ?></a>

<?php $bg_video = get_theme_mod( 'rodolfo_background_video' ); ?>
<?php if ( $bg_video ) : ?>
    <!-- Video Background (decorative; hidden under prefers-reduced-motion via style.css) -->
    <div 
        class="rodolfo-bg-video fixed top-0 left-0 right-0 h-[800px] z-0 pointer-events-none opacity-[0.2] dark:opacity-[0.05] mix-blend-multiply dark:mix-blend-screen overflow-hidden"
        style="mask-image: linear-gradient(to bottom, black 0%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 0%, transparent 100%);"
        aria-hidden="true"
    >
        <video autoplay loop muted playsinline class="w-full h-full object-cover">
            <source src="<?php echo esc_url( $bg_video ); ?>" type="video/mp4">
        </video>
    </div>
<?php else : ?>
    <!-- Subtle Background Art -->
    <div 
        class="fixed top-0 left-0 right-0 h-[800px] z-0 pointer-events-none opacity-[0.08] dark:opacity-[0.02] mix-blend-multiply dark:mix-blend-screen"
        style="background-image: url('<?php echo esc_url( get_template_directory_uri() ); ?>/assets/theme-bg.jpg'); background-size: cover; background-position: center top; mask-image: linear-gradient(to bottom, black 0%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 0%, transparent 100%);"
        aria-hidden="true"
    ></div>
<?php endif; ?>

<div class="relative z-10 flex flex-col flex-grow">
    <header class="border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-950/80 backdrop-blur-md sticky top-0 z-40 transition-colors">
        <div class="max-w-[1400px] mx-auto px-4 md:px-8 py-4 flex items-center justify-between gap-8">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="no-underline">
                <div class="flex items-center gap-3 group cursor-pointer flex-shrink-0 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded px-2 py-1">
                    <?php if ( has_custom_logo() ) :
                        // Constrained manually — the_custom_logo() outputs the
                        // full-size image wrapped in its own home link, which
                        // would nest an <a> inside this one (invalid HTML) and
                        // render the logo at its native dimensions.
                        $logo_id  = get_theme_mod( 'custom_logo' );
                        $logo_src = wp_get_attachment_image_src( $logo_id, 'full' );
                        if ( $logo_src ) : ?>
                            <img
                                src="<?php echo esc_url( $logo_src[0] ); ?>"
                                alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                                class="rodolfo-logo-img group-hover:opacity-80 transition-opacity"
                            />
                        <?php endif; ?>
                    <?php else : ?>
                        <?php
                        // Fallback: the site's initial in a neutral disc.
                        // (Deliberately NOT a Gravatar of the admin email — the MD5
                        // hash would expose the admin address in public HTML.)
                        $site_name = get_bloginfo( 'name' );
                        $initial   = function_exists( 'mb_substr' ) ? mb_substr( $site_name, 0, 1 ) : substr( $site_name, 0, 1 );
                        ?>
                        <div class="rodolfo-brand-mark bg-slate-100 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 group-hover:ring-2 ring-blue-500 transition-all flex items-center justify-center font-bold text-lg text-slate-600 dark:text-slate-300" aria-hidden="true">
                            <?php echo esc_html( strtoupper( $initial ) ); ?>
                        </div>
                    <?php endif; ?>
                    <div class="hidden sm:block max-w-sm">
                        <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-white"><?php bloginfo( 'name' ); ?></div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium truncate"><?php bloginfo( 'description' ); ?></div>
                    </div>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-4 text-sm text-slate-600 dark:text-slate-300 font-medium flex-grow" aria-label="<?php esc_attr_e( 'Primary', 'rodolfo' ); ?>">
                <?php echo rodolfo_primary_nav_links( 'flex items-center gap-4' ); ?>
            </nav>

            <div class="flex items-center gap-3">
                <div class="hidden sm:relative sm:flex sm:items-center sm:group/search">
                    <form role="search" method="get" class="search-form flex relative items-center" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <?php echo rodolfo_icon( 'search', 'w-4 h-4 absolute left-3 text-slate-400 dark:text-slate-500 group-focus-within/search:text-blue-500 transition-colors' ); ?>
                        <input type="search" class="pl-9 h-9 w-52 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 focus-visible:ring-2 focus-visible:ring-blue-500/20 focus-visible:border-blue-500 transition-all text-sm dark:text-white dark:placeholder:text-slate-500 outline-none" placeholder="<?php esc_attr_e( 'Search…', 'rodolfo' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" aria-label="<?php esc_attr_e( 'Search', 'rodolfo' ); ?>" />
                    </form>
                </div>
                
                <?php
                // Built-in /tags/ route (inc/tags.php) — always available, no page
                // to create. Lists every tag as a linked pill cloud.
                ?>
                <a href="<?php echo esc_url( rodolfo_tags_url() ); ?>" class="p-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 active:bg-slate-200 dark:active:bg-slate-700 active:text-blue-700 dark:active:text-blue-300 active:shadow-inner transition-all focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer" aria-label="<?php esc_attr_e( 'View tags', 'rodolfo' ); ?>">
                    <?php echo rodolfo_icon( 'tag', 'w-5 h-5' ); ?>
                </a>
                
                <a href="<?php echo esc_url( get_bloginfo( 'rss2_url' ) ); ?>" class="p-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 active:bg-slate-200 dark:active:bg-slate-700 active:text-blue-700 dark:active:text-blue-300 active:shadow-inner transition-all hidden sm:block focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="<?php esc_attr_e( 'Subscribe to RSS feed', 'rodolfo' ); ?>">
                    <?php echo rodolfo_icon( 'rss', 'w-5 h-5' ); ?>
                </a>

                <button id="theme-toggle" class="p-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 active:bg-slate-200 dark:active:bg-slate-700 active:text-blue-700 dark:active:text-blue-300 active:shadow-inner transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'rodolfo' ); ?>">
                    <?php echo rodolfo_icon( 'moon', 'w-5 h-5 block dark:hidden' ); ?>
                    <?php echo rodolfo_icon( 'sun', 'w-5 h-5 hidden dark:block' ); ?>
                </button>

                <button id="rodolfo-mobile-toggle" class="rodolfo-mobile-toggle p-1.5 rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-blue-600 dark:hover:text-blue-400 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" aria-expanded="false" aria-controls="rodolfo-mobile-menu" aria-label="<?php esc_attr_e( 'Menu', 'rodolfo' ); ?>">
                    <span class="rodolfo-menu-open"><?php echo rodolfo_icon( 'menu', 'w-5 h-5' ); ?></span>
                    <span class="rodolfo-menu-close"><?php echo rodolfo_icon( 'x', 'w-5 h-5' ); ?></span>
                </button>
            </div>
        </div>

        <?php // Mobile navigation panel — toggled by the hamburger; hidden ≥768px via style.css. ?>
        <div id="rodolfo-mobile-menu" class="rodolfo-mobile-menu">
            <div class="max-w-[1400px] mx-auto px-4 py-4 flex flex-col gap-4">
                <form role="search" method="get" class="search-form flex relative items-center" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <?php echo rodolfo_icon( 'search', 'w-4 h-4 absolute left-3 text-slate-400 dark:text-slate-500' ); ?>
                    <input type="search" class="pl-9 h-9 w-full rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 transition-all text-sm dark:text-white dark:placeholder:text-slate-500 outline-none" placeholder="<?php esc_attr_e( 'Search…', 'rodolfo' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" aria-label="<?php esc_attr_e( 'Search', 'rodolfo' ); ?>" />
                </form>
                <nav class="flex flex-col gap-3 text-sm text-slate-600 dark:text-slate-300 font-medium" aria-label="<?php esc_attr_e( 'Mobile', 'rodolfo' ); ?>">
                    <?php echo rodolfo_primary_nav_links( 'flex flex-col gap-3' ); ?>
                </nav>
            </div>
        </div>
    </header>
