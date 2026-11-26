    <?php $footer_cols = function_exists( 'rodolfo_choice' ) ? (int) rodolfo_choice( 'rodolfo_footer_columns' ) : 2; ?>
    <footer id="site-footer" class="bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 mt-auto z-10 relative text-slate-600 dark:text-slate-400">

        <?php // ── Top area: brand block + About column ─────────────────────── ?>
        <div class="ftr-grid ftr-cols-<?php echo (int) $footer_cols; ?> max-w-[1400px] mx-auto px-4 md:px-8 py-12 gap-10">

            <div class="flex flex-col items-start gap-4">
                <div class="flex items-center gap-3">
                    <?php
                    if ( has_custom_logo() ) :
                        $logo_id  = get_theme_mod( 'custom_logo' );
                        $logo_src = wp_get_attachment_image_src( $logo_id, 'full' );
                        if ( $logo_src ) : ?>
                            <img
                                src="<?php echo esc_url( $logo_src[0] ); ?>"
                                alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                                width="<?php echo (int) $logo_src[1]; ?>" height="<?php echo (int) $logo_src[2]; ?>" loading="lazy" decoding="async"
                                class="tk-logo-img tk-logo-img--lg"
                            />
                        <?php endif;
                    else :
                        // Fallback: the site's initial in a neutral disc (no Gravatar —
                        // the admin email's MD5 hash must not appear in public HTML).
                        $site_name = get_bloginfo( 'name' );
                        $initial   = function_exists( 'mb_substr' ) ? mb_substr( $site_name, 0, 1 ) : substr( $site_name, 0, 1 );
                        ?>
                        <div class="tk-brand-mark tk-brand-mark--lg border border-slate-100 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-xl text-slate-600 dark:text-slate-300" aria-hidden="true">
                            <?php echo esc_html( strtoupper( $initial ) ); ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Wordmark — the last word of the site name is emphasised in the
                    // accent colour (the accent colour).
                    $site_name = get_bloginfo( 'name' );
                    $words     = preg_split( '/\s+/', trim( $site_name ) );
                    $last_word = array_pop( $words );
                    $lead      = implode( ' ', $words );
                    ?>
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        <?php if ( $lead ) : ?>
                            <?php echo esc_html( $lead ); ?>
                        <?php endif; ?>
                        <span class="text-blue-600 dark:text-blue-400"><?php echo esc_html( $last_word ); ?></span>
                    </span>
                </div>
                <?php if ( get_bloginfo( 'description' ) ) : ?>
                    <p class="text-sm leading-relaxed max-w-sm"><?php bloginfo( 'description' ); ?></p>
                <?php endif; ?>
                <?php if ( $footer_cols < 3 && function_exists( 'rodolfo_preferred_source' ) ) { rodolfo_preferred_source( 'footer' ); } ?>
            </div>

            <?php
            // Topics: the site's most-used categories — automatic, zero setup.
            $footer_terms      = rodolfo_footer_topics( 6 );
            $footer_links_html = '';
            foreach ( $footer_terms as $footer_term ) {
                $footer_links_html .= '<a href="' . esc_url( get_category_link( $footer_term->term_id ) ) . '" class="text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors">' . esc_html( $footer_term->name ) . '</a>';
            }
            $topics_block = '';
            if ( trim( $footer_links_html ) ) {
                $topics_block = '<h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wide">' . esc_html__( 'Explore Topics', 'rodolfo' ) . '</h2>'
                    . '<nav class="flex flex-wrap gap-x-6 gap-y-3" aria-label="' . esc_attr__( 'Explore topics', 'rodolfo' ) . '">' . $footer_links_html . '</nav>';
            }
            ?>
            <?php if ( 1 === $footer_cols && $topics_block ) : ?>
                <div><?php echo $topics_block; // Escaped above. ?></div>
            <?php endif; ?>

            <?php if ( $footer_cols >= 2 && $topics_block ) : ?>
            <div><?php echo $topics_block; // Escaped above. ?></div>
            <?php endif; ?>

            <?php if ( $footer_cols >= 3 ) : ?>
            <div>
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 mb-4 uppercase tracking-wide"><?php esc_html_e( 'Pages', 'rodolfo' ); ?></h2>
                <nav class="flex flex-wrap gap-x-6 gap-y-3" aria-label="<?php esc_attr_e( 'Site pages', 'rodolfo' ); ?>">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php esc_html_e( 'Home', 'rodolfo' ); ?></a>
                    <?php foreach ( rodolfo_primary_nav_pages() as $rodolfo_fp ) : ?>
                        <a href="<?php echo esc_url( get_permalink( $rodolfo_fp->ID ) ); ?>" class="text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php echo esc_html( get_the_title( $rodolfo_fp->ID ) ); ?></a>
                    <?php endforeach; ?>
                    <?php if ( rodolfo_sitemap_page_url() ) : ?>
                        <a href="<?php echo esc_url( rodolfo_sitemap_page_url() ); ?>" class="text-sm hover:text-blue-600 dark:hover:text-blue-400 transition-colors"><?php esc_html_e( 'Sitemap', 'rodolfo' ); ?></a>
                    <?php endif; ?>
                </nav>
                <?php if ( function_exists( 'rodolfo_preferred_source' ) ) { rodolfo_preferred_source( 'footer' ); } ?>
            </div>
            <?php endif; ?>
        </div>

        <?php // ── Accent divider with centred ornament (accent colour) ── ?>
        <div class="max-w-[1400px] mx-auto px-4 md:px-8" aria-hidden="true">
            <div class="flex items-center gap-4">
                <span class="flex-grow h-px bg-slate-200 dark:bg-slate-800"></span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="currentColor"><path d="M12 2l2.6 7.4L22 12l-7.4 2.6L12 22l-2.6-7.4L2 12l7.4-2.6z"/></svg>
                <span class="flex-grow h-px bg-slate-200 dark:bg-slate-800"></span>
            </div>
        </div>

        <?php // ── Bottom bar: © line, legal row, social circles ─────────────── ?>
        <div class="max-w-[1400px] mx-auto px-4 md:px-8 py-8 flex flex-col lg:flex-row items-center justify-between gap-6">

            <div class="text-sm font-medium tracking-tight text-center lg:text-left order-2 lg:order-1">
                <?php
                $first_post  = get_posts( array( 'numberposts' => 1, 'order' => 'ASC', 'post_status' => 'publish' ) );
                $start_year  = ! empty( $first_post ) ? get_the_date( 'Y', $first_post[0]->ID ) : wp_date( 'Y' );
                $footer_text = get_option( 'rodolfo_footer_text' );
                if ( empty( $footer_text ) ) {
                    /* translators: 1: site name, 2: year of first published post */
                    $footer_text = sprintf( __( '%1$s blogging since %2$s.', 'rodolfo' ), get_bloginfo( 'name' ), $start_year );
                }
                /* translators: %s: current year */
                echo '&copy; ' . esc_html( wp_date( 'Y' ) ) . ' ';
                echo esc_html( $footer_text );

                // Site-wide freshness signal (AEO), when enabled.
                $rodolfo_updated = function_exists( 'rodolfo_site_updated_stamp' ) ? rodolfo_site_updated_stamp() : '';
                if ( $rodolfo_updated ) {
                    echo ' <span class="text-slate-400 dark:text-slate-500 whitespace-nowrap">&middot; ' . esc_html( $rodolfo_updated ) . '</span>';
                }
                ?>
            </div>

            <nav class="flex flex-wrap items-center justify-center gap-3 text-xs font-semibold uppercase tracking-widest order-1 lg:order-2" aria-label="<?php esc_attr_e( 'Secondary', 'rodolfo' ); ?>">
                <?php
                // Prefer a menu assigned to the dedicated "secondary" location so
                // this row is user-controlled and distinct from the header (primary)
                // and the "About the Site" column (footer). When that location is
                // unassigned, fall back to auto-built legal/utility links.
                $secondary_html = '';
                if ( has_nav_menu( 'secondary' ) ) {
                    $secondary_html = (string) wp_nav_menu( array(
                        'theme_location' => 'secondary',
                        'container'      => false,
                        'menu_class'     => 'flex flex-wrap items-center justify-center gap-x-3 gap-y-2',
                        'fallback_cb'    => false,
                        'depth'          => 1,
                        'echo'           => false,
                    ) );
                }

                if ( trim( $secondary_html ) ) {
                    echo $secondary_html; // Escaped by wp_nav_menu.
                } else {
                    // Fully controllable from Theme Options → Navigation: footer-
                    // assigned Pages merged with the enabled built-in links
                    // (Privacy / Sitemap / RSS), all sorted by their order value.
                    $legal_links = array();
                    foreach ( rodolfo_footer_nav_items() as $item ) {
                        $legal_links[] = array( $item['url'], $item['label'] );
                    }

                    foreach ( $legal_links as $i => $link ) {
                        // Separator + link travel as one nowrap unit, so a
                        // line break can never strand a dot at a line end.
                        echo '<span class="whitespace-nowrap">';
                        if ( $i > 0 ) {
                            echo '<span class="text-slate-300 dark:text-slate-700 mr-3" aria-hidden="true">&middot;</span>';
                        }
                        echo '<a href="' . esc_url( $link[0] ) . '" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">' . esc_html( $link[1] ) . '</a></span>';
                    }
                }
                ?>
            </nav>

            <div class="flex flex-wrap items-center justify-center lg:justify-end gap-3 text-slate-500 dark:text-slate-400 order-3 max-w-xs sm:max-w-sm lg:max-w-[19rem]">
                <?php foreach ( rodolfo_social_platforms() as $rodolfo_sk => $rodolfo_sp ) :
                    $rodolfo_sval = get_option( 'rodolfo_social_' . $rodolfo_sk );
                    if ( ! $rodolfo_sval ) {
                        continue;
                    }
                    $rodolfo_surl = rodolfo_social_href( $rodolfo_sk, $rodolfo_sval );
                    if ( ! $rodolfo_surl ) {
                        continue;
                    }
                    // mailto: hands off to a mail client — a new tab would just
                    // leave an empty window behind.
                    $rodolfo_smail = ( 0 === strpos( $rodolfo_surl, 'mailto:' ) );
                    ?>
                    <a href="<?php echo esc_url( $rodolfo_surl ); ?>"<?php echo $rodolfo_smail ? '' : ' target="_blank" rel="noopener noreferrer"'; ?> class="w-9 h-9 rounded-full border border-slate-200 dark:border-slate-700 flex items-center justify-center hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-600 dark:hover:border-blue-400 transition-colors" aria-label="<?php echo esc_attr( $rodolfo_sp[0] ); ?>"><?php echo rodolfo_icon( $rodolfo_sk, 'w-4 h-4' ); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </footer>
</div><!-- .relative.z-10 -->

<?php wp_footer(); ?>
</body>
</html>
