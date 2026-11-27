<?php
/**
 * JSON-LD structured data.
 *
 * Coverage by view:
 *   - Front page      WebSite (+ datePublished/dateModified) + Organization
 *   - Single post     Article (+ CreativeWorkSeries isPartOf/position in a series)
 *   - Page            WebPage, narrowed to ContactPage / AboutPage / FAQPage by slug
 *   - Category/tag/tax/date  CollectionPage + ItemList of the posts shown
 *   - Author          ProfilePage + Person
 *   - Search          SearchResultsPage
 *   - Singular        BreadcrumbList
 *
 * FAQPage questions themselves come from inc/aeo.php, built from the [faqs]
 * shortcode content — this file only claims the type on an FAQ-slugged page and
 * never duplicates the Q/As.
 *
 * Self-disables when an SEO plugin is active (see rodolfo_seo_plugin_active()
 * in inc/meta-tags.php), since two competing graphs is worse than one.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_schema_output() {
    if ( function_exists( 'rodolfo_seo_plugin_active' ) && rodolfo_seo_plugin_active() ) {
        return;
    }
    if ( is_404() ) {
        return;
    }
    if ( is_singular() ) {
        $post = get_queried_object();
        if ( ! $post || 'publish' !== $post->post_status || ! empty( $post->post_password ) ) {
            return;
        }
    }

    $graph = array();
    $home  = home_url( '/' );
    $org   = array(
        '@type' => 'Organization',
        '@id'   => $home . '#organization',
        'name'  => get_bloginfo( 'name' ),
        'url'   => $home,
    );
    $logo_id = get_theme_mod( 'custom_logo' );
    if ( $logo_id ) {
        $src = wp_get_attachment_image_src( $logo_id, 'full' );
        if ( $src ) {
            $org['logo'] = array( '@type' => 'ImageObject', 'url' => $src[0] );
        }
    }

    if ( is_front_page() || is_home() ) {
        $graph[] = $org;

        // Site-level freshness. The visible footer stamp (inc/page-states.php)
        // is what answer engines quote, but crawlers read the machine-readable
        // pair here: datePublished = first post, dateModified = newest content.
        // Emitted regardless of whether the visible stamp is switched on —
        // schema costs nothing and helps even when the stamp is hidden.
        $site_dates = array();
        $oldest = get_posts( array(
            'post_type'        => 'post',
            'post_status'      => 'publish',
            'posts_per_page'   => 1,
            'orderby'          => 'date',
            'order'            => 'ASC',
            'fields'           => 'ids',
            'suppress_filters' => false,
        ) );
        if ( $oldest ) {
            $site_dates['datePublished'] = get_post_time( 'c', true, $oldest[0] );
        }
        // Always the true modified time — schema's dateModified means "content
        // last changed", so it must not follow the footer stamp's display mode.
        if ( function_exists( 'rodolfo_site_modified_time' ) ) {
            $updated = rodolfo_site_modified_time();
            if ( $updated ) {
                $site_dates['dateModified'] = gmdate( 'c', $updated );
            }
        }

        $graph[] = array_merge( array(
            '@type'           => 'WebSite',
            '@id'             => $home . '#website',
            'url'             => $home,
            'name'            => get_bloginfo( 'name' ),
            'description'     => get_bloginfo( 'description' ),
            'publisher'       => array( '@id' => $home . '#organization' ),
        ), $site_dates, array(
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => array(
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}',
                ),
                'query-input' => 'required name=search_term_string',
            ),
        ) );
    }

    if ( is_singular( 'post' ) ) {
        $post    = get_queried_object();
        $desc    = function_exists( 'rodolfo_meta_description' ) ? rodolfo_meta_description() : '';
        $image   = function_exists( 'rodolfo_meta_image' ) ? rodolfo_meta_image() : '';
        $article = array(
            '@type'            => 'Article',
            '@id'              => get_permalink( $post ) . '#article',
            'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
            'datePublished'    => get_the_date( 'c', $post ),
            'dateModified'     => get_the_modified_date( 'c', $post ),
            'mainEntityOfPage' => get_permalink( $post ),
            'author'           => array(
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $post->post_author ),
            ),
            'publisher'        => array( '@id' => $home . '#organization' ),
        );
        if ( $desc ) {
            $article['description'] = $desc;
        }
        if ( $image ) {
            $article['image'] = $image;
        }

        // Series position. Multi-part guides are exactly the content AI tools
        // mis-summarise by treating part 3 as standalone; isPartOf + position
        // states the sequence explicitly.
        if ( function_exists( 'rodolfo_series_context' ) ) {
            $series = rodolfo_series_context( $post );
            if ( $series && $series['total'] > 1 ) {
                $term_link = get_term_link( $series['term'] );
                $article['isPartOf'] = array(
                    '@type'           => 'CreativeWorkSeries',
                    'name'            => $series['term']->name,
                    'url'             => is_string( $term_link ) ? $term_link : $home,
                    'numberOfItems'   => $series['total'],
                );
                $article['position'] = $series['index'];
            }
        }
        $graph[] = $org;
        $graph[] = $article;
    }

    // ---------------------------------------------------------------
    // Static Pages — WebPage, with ContactPage / AboutPage / FAQPage
    // narrowed by slug. A coming-soon page is skipped: it has no content
    // to describe, and is noindexed anyway.
    // ---------------------------------------------------------------
    if ( is_page() && ! ( function_exists( 'rodolfo_is_coming_soon' ) && rodolfo_is_coming_soon() ) ) {
        $page = get_queried_object();
        $slug = $page ? $page->post_name : '';

        $page_type = 'WebPage';
        if ( in_array( $slug, array( 'contact', 'contact-us' ), true ) ) {
            $page_type = 'ContactPage';
        } elseif ( in_array( $slug, array( 'about', 'about-us', 'about-me' ), true ) ) {
            $page_type = 'AboutPage';
        }
        // aeo.php emits FAQPage only when actual questions are rendered.
        // A page slug alone does not establish a FAQPage with mainEntity.

        $desc = function_exists( 'rodolfo_meta_description' ) ? rodolfo_meta_description() : '';
        $node = array(
            '@type'         => $page_type,
            '@id'           => get_permalink() . '#webpage',
            'url'           => get_permalink(),
            'name'          => wp_strip_all_tags( get_the_title() ),
            'isPartOf'      => array( '@id' => $home . '#website' ),
            'datePublished' => get_the_date( 'c' ),
            'dateModified'  => get_the_modified_date( 'c' ),
        );
        if ( $desc ) {
            $node['description'] = $desc;
        }
        $graph[] = $node;
    }

    // ---------------------------------------------------------------
    // Archives — CollectionPage listing the posts actually shown, so a
    // crawler can see what the term covers without re-crawling each URL.
    // ---------------------------------------------------------------
    if ( is_category() || is_tag() || is_tax() || is_date() || is_author() ) {
        $term = get_queried_object();

        if ( is_author() ) {
            $name = $term instanceof WP_User ? $term->display_name : '';
            $desc = $term instanceof WP_User ? $term->description : '';
            $url  = $term instanceof WP_User ? get_author_posts_url( $term->ID ) : $home;
            $type = 'ProfilePage';
        } elseif ( is_date() ) {
            $name = wp_strip_all_tags( get_the_archive_title() );
            $desc = '';
            $url  = home_url( add_query_arg( array() ) );
            $type = 'CollectionPage';
        } else {
            $name = $term instanceof WP_Term ? $term->name : '';
            $desc = $term instanceof WP_Term ? wp_strip_all_tags( term_description( $term ) ) : '';
            $url  = $term instanceof WP_Term ? get_term_link( $term ) : $home;
            $type = 'CollectionPage';
        }

        $node = array(
            '@type'    => $type,
            '@id'      => is_string( $url ) ? $url . '#collection' : $home . '#collection',
            'url'      => is_string( $url ) ? $url : $home,
            'name'     => $name,
            'isPartOf' => array( '@id' => $home . '#website' ),
        );
        if ( $desc ) {
            $node['description'] = $desc;
        }

        // The posts on this page, in order — an ItemList a crawler can follow.
        $items = array();
        $pos   = 0;
        if ( have_posts() ) {
            global $wp_query;
            foreach ( (array) $wp_query->posts as $p ) {
                $pos++;
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'url'      => get_permalink( $p ),
                    'name'     => wp_strip_all_tags( get_the_title( $p ) ),
                );
            }
        }
        if ( $items ) {
            $node['mainEntity'] = array(
                '@type'           => 'ItemList',
                'itemListElement' => $items,
            );
        }

        // ProfilePage should say whose profile it is.
        if ( is_author() && $term instanceof WP_User ) {
            $node['mainEntityOfPage'] = array(
                '@type' => 'Person',
                'name'  => $term->display_name,
                'url'   => get_author_posts_url( $term->ID ),
            );
        }
        $graph[] = $node;
    }

    // ---------------------------------------------------------------
    // Search results — SearchResultsPage. Always noindexed, but AI
    // crawlers still read the page and benefit from knowing what it is.
    // ---------------------------------------------------------------
    if ( is_search() ) {
        $graph[] = array(
            '@type'    => 'SearchResultsPage',
            '@id'      => $home . '?s=' . rawurlencode( get_search_query() ) . '#search',
            'url'      => $home . '?s=' . rawurlencode( get_search_query() ),
            'name'     => sprintf(
                /* translators: %s: search term */
                __( 'Search results for %s', 'rodolfo' ),
                get_search_query()
            ),
            'isPartOf' => array( '@id' => $home . '#website' ),
        );
    }

    // Breadcrumbs for singular views.
    if ( is_singular() ) {
        $items = array(
            array( 'name' => __( 'Home', 'rodolfo' ), 'item' => $home ),
        );
        if ( is_singular( 'post' ) ) {
            $cats = get_the_category();
            if ( ! empty( $cats ) ) {
                $items[] = array(
                    'name' => $cats[0]->name,
                    'item' => get_category_link( $cats[0]->term_id ),
                );
            }
        }
        $items[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'item' => get_permalink() );

        $elements = array();
        foreach ( $items as $i => $it ) {
            $elements[] = array(
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $it['name'],
                'item'     => $it['item'],
            );
        }
        $graph[] = array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        );
    }

    if ( empty( $graph ) ) {
        return;
    }

    $data = array( '@context' => 'https://schema.org', '@graph' => $graph );
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
        . "</script>\n";
}
add_action( 'wp_head', 'rodolfo_schema_output', 6 );
