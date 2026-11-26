<?php
/**
 * Schema.org structured data — one interlinked JSON-LD @graph per request.
 *
 * Every region and content element the theme renders is declared with a
 * Schema.org type and tied to the rest of the graph by @id:
 *
 *   Site level      Organization | Person (#organization), WebSite (#website,
 *                   SearchAction), ImageObject (logo)
 *   Page level      WebPage / AboutPage / ContactPage / FAQPage / CollectionPage /
 *                   ProfilePage / SearchResultsPage (#webpage) with
 *                   primaryImageOfPage, breadcrumb, speakable, relatedLink,
 *                   potentialAction, copyright*
 *   Page regions    WPHeader, SiteNavigationElement (per link), WebPageElement
 *                   (main content), WPSideBar, WPFooter
 *   Breadcrumb      BreadcrumbList (#breadcrumb) — same list as the visible trail
 *   Post            BlogPosting | Article | NewsArticle (#article): author Person,
 *                   publisher, image, section, keywords, wordCount, timeRequired,
 *                   about (DefinedTerm per category/tag), comments (Comment),
 *                   associatedMedia (ImageObject), video (VideoObject),
 *                   CreativeWorkSeries membership, ReadAction/CommentAction
 *   Content blocks  FAQPage + Question/Answer, HowTo + HowToStep, Table (the
 *                   [compare] block), Claim (the [proof] block), ItemList of the
 *                   table of contents, speakable quick answer
 *   Listings        ItemList + ListItem (home, category, tag, taxonomy, date,
 *                   sitemap), Blog (posts index)
 *   Other views     ProfilePage + Person (author), SearchResultsPage, WebPage (404)
 *
 * Self-disables when an SEO plugin is active (rodolfo_seo_plugin_active());
 * FAQ/HowTo then fall back to the small emitters in inc/aeo.php.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Remember what the graph already covers so inc/aeo.php never double-emits. */
function rodolfo_schema_state( $key = null, $set = null ) {
    static $state = array();
    if ( null === $key ) {
        return $state;
    }
    if ( null !== $set ) {
        $state[ $key ] = $set;
    }
    return isset( $state[ $key ] ) ? $state[ $key ] : false;
}

/** Recursively drop empty values (null, '', empty arrays) — keeps 0 and false. */
function rodolfo_schema_clean( $data ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }
    foreach ( $data as $k => $v ) {
        $v = rodolfo_schema_clean( $v );
        if ( null === $v || '' === $v || array() === $v ) {
            unset( $data[ $k ] );
        } else {
            $data[ $k ] = $v;
        }
    }
    return $data;
}

function rodolfo_schema_ref( $id ) {
    return array( '@id' => $id );
}

/** ImageObject for an attachment id (with dimensions), or null. */
function rodolfo_schema_image_by_id( $attachment_id, $node_id = '' ) {
    $src = wp_get_attachment_image_src( $attachment_id, 'full' );
    if ( ! $src ) {
        return null;
    }
    $alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
    return rodolfo_schema_clean( array(
        '@type'      => 'ImageObject',
        '@id'        => $node_id,
        'url'        => $src[0],
        'contentUrl' => $src[0],
        'width'      => (int) $src[1],
        'height'     => (int) $src[2],
        'caption'    => $alt ? $alt : wp_strip_all_tags( get_the_title( $attachment_id ) ),
    ) );
}

/** ImageObject for a bare URL, resolving the attachment (and its size) when it is ours. */
function rodolfo_schema_image_by_url( $url, $node_id = '' ) {
    $id = attachment_url_to_postid( $url );
    if ( $id ) {
        return rodolfo_schema_image_by_id( $id, $node_id );
    }
    return array_filter( array( '@type' => 'ImageObject', '@id' => $node_id, 'url' => $url, 'contentUrl' => $url ) );
}

/** sameAs profile URLs from Theme Options → Social (one per configured platform). */
function rodolfo_schema_same_as() {
    $urls = array();
    if ( function_exists( 'rodolfo_social_platforms' ) && function_exists( 'rodolfo_social_href' ) ) {
        foreach ( rodolfo_social_platforms() as $key => $platform ) {
            $value = trim( (string) get_option( 'rodolfo_social_' . $key, '' ) );
            if ( '' === $value ) {
                continue;
            }
            $href = rodolfo_social_href( $key, $value );
            if ( $href && 0 === strpos( $href, 'http' ) ) {
                $urls[] = $href;
            }
        }
    }
    return array_values( array_unique( $urls ) );
}

/** Words in the post body, ignoring shortcode tags and markup. */
function rodolfo_schema_word_count( $post ) {
    $text = preg_replace( '/\[\/?[A-Za-z_][^\]]*\]/', ' ', (string) $post->post_content );
    $text = wp_strip_all_tags( $text );
    return (int) preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', $text );
}

/** Nav links as plain [name, url] pairs, from the same sources the templates use. */
function rodolfo_schema_nav_links( $which ) {
    $links = array();
    if ( 'primary' === $which ) {
        if ( has_nav_menu( 'primary' ) ) {
            $locations = get_nav_menu_locations();
            $menu_items = ! empty( $locations['primary'] ) ? wp_get_nav_menu_items( $locations['primary'] ) : array();
            foreach ( (array) $menu_items as $mi ) {
                if ( empty( $mi->menu_item_parent ) ) {
                    $links[] = array( wp_strip_all_tags( $mi->title ), $mi->url );
                }
            }
        } elseif ( function_exists( 'rodolfo_primary_nav_pages' ) ) {
            foreach ( rodolfo_primary_nav_pages() as $page ) {
                $links[] = array( wp_strip_all_tags( get_the_title( $page->ID ) ), get_permalink( $page->ID ) );
            }
        }
    } elseif ( 'footer' === $which && function_exists( 'rodolfo_footer_nav_items' ) ) {
        foreach ( rodolfo_footer_nav_items() as $it ) {
            $links[] = array( wp_strip_all_tags( $it['label'] ), $it['url'] );
        }
    } elseif ( 'topics' === $which && function_exists( 'rodolfo_footer_topics' ) ) {
        foreach ( rodolfo_footer_topics() as $term ) {
            $link = get_term_link( $term );
            if ( is_string( $link ) ) {
                $links[] = array( $term->name, $link );
            }
        }
    }
    return $links;
}

/** One SiteNavigationElement graph node per link; returns the @id refs. */
function rodolfo_schema_nav_nodes( $which, $home, &$graph ) {
    $refs = array();
    foreach ( rodolfo_schema_nav_links( $which ) as $i => $link ) {
        $id      = $home . '#nav-' . $which . '-' . ( $i + 1 );
        $graph[] = array(
            '@type' => 'SiteNavigationElement',
            '@id'   => $id,
            'name'  => $link[0],
            'url'   => $link[1],
        );
        $refs[] = rodolfo_schema_ref( $id );
    }
    return $refs;
}

/** ISO 8601 duration from minutes. */
function rodolfo_schema_duration( $minutes ) {
    $minutes = max( 1, (int) $minutes );
    $h = intdiv( $minutes, 60 );
    $m = $minutes % 60;
    return 'PT' . ( $h ? $h . 'H' : '' ) . ( $m || ! $h ? $m . 'M' : '' );
}

/** Post-body elements, read from the stored content (the head prints before the body renders). */
function rodolfo_schema_content_parts( $post ) {
    $content = (string) $post->post_content;
    $parts   = function_exists( 'rodolfo_aeo_extract' ) ? rodolfo_aeo_extract( $content ) : array();
    $parts   = wp_parse_args( $parts, array( 'answer' => '', 'faqs' => array(), 'howto' => null, 'compare' => array(), 'proof' => array() ) );

    // Headings (same ids toc.php generates) for the table-of-contents list.
    $parts['headings'] = array();
    if ( preg_match_all( '/<h([23])(\b[^>]*)>(.*?)<\/h\1>/is', $content, $hm, PREG_SET_ORDER ) ) {
        $used = array();
        foreach ( $hm as $h ) {
            $text = trim( wp_strip_all_tags( $h[3] ) );
            if ( '' === $text ) {
                continue;
            }
            if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $h[2], $idm ) ) {
                $id = $idm[1];
            } else {
                $base = sanitize_title( $text );
                $base = '' === $base ? 'section' : $base;
                $id   = $base;
                $n    = 2;
                while ( in_array( $id, $used, true ) ) {
                    $id = $base . '-' . $n++;
                }
            }
            $used[]              = $id;
            $parts['headings'][] = array( 'name' => $text, 'id' => $id );
        }
    }

    // Inline images and embedded video.
    $parts['images'] = array();
    if ( preg_match_all( '/<img\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i', $content, $im, PREG_SET_ORDER ) ) {
        foreach ( array_slice( $im, 0, 10 ) as $i ) {
            $alt = '';
            if ( preg_match( '/\balt=["\']([^"\']*)["\']/i', $i[0], $am ) ) {
                $alt = $am[1];
            }
            $parts['images'][] = array( 'url' => $i[1], 'alt' => $alt );
        }
    }
    $parts['videos'] = array();
    if ( preg_match_all( '/<iframe\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $content, $vm ) ) {
        foreach ( $vm[1] as $src ) {
            if ( preg_match( '#(youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)#i', $src ) ) {
                $parts['videos'][] = $src;
            }
        }
    }

    // Plain body elements: tables, quotations, lists, definition lists.
    $parts['tables'] = $parts['quotes'] = $parts['lists'] = $parts['defs'] = array();
    $body = preg_replace( '/\[[^\]]*\]/', '', $content ); // Shortcodes render their own markup/schema.
    if ( preg_match_all( '/<table\b[^>]*>(.*?)<\/table>/is', $body, $tm ) ) {
        foreach ( array_slice( $tm[1], 0, 10 ) as $t ) {
            $cap = preg_match( '/<caption[^>]*>(.*?)<\/caption>/is', $t, $c ) ? trim( wp_strip_all_tags( $c[1] ) ) : '';
            preg_match_all( '/<th\b[^>]*>(.*?)<\/th>/is', $t, $th );
            $parts['tables'][] = array( 'caption' => $cap, 'cols' => array_map( 'wp_strip_all_tags', array_slice( $th[1], 0, 12 ) ) );
        }
    }
    if ( preg_match_all( '/<blockquote\b[^>]*>(.*?)<\/blockquote>/is', $body, $qm ) ) {
        foreach ( array_slice( $qm[1], 0, 10 ) as $q ) {
            $text = trim( wp_strip_all_tags( $q ) );
            if ( '' !== $text ) {
                $parts['quotes'][] = mb_substr( $text, 0, 400 );
            }
        }
    }
    if ( preg_match_all( '/<(ul|ol)\b[^>]*>(.*?)<\/\1>/is', $body, $lm, PREG_SET_ORDER ) ) {
        foreach ( array_slice( $lm, 0, 8 ) as $l ) {
            if ( preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/is', $l[2], $items ) ) {
                $names = array_values( array_filter( array_map( function ( $x ) { return trim( wp_strip_all_tags( $x ) ); }, array_slice( $items[1], 0, 20 ) ) ) );
                if ( $names ) {
                    $parts['lists'][] = array( 'ordered' => 'ol' === strtolower( $l[1] ), 'items' => $names );
                }
            }
        }
    }
    if ( preg_match_all( '/<dl\b[^>]*>(.*?)<\/dl>/is', $body, $dm ) ) {
        foreach ( array_slice( $dm[1], 0, 5 ) as $d ) {
            if ( preg_match_all( '/<dt\b[^>]*>(.*?)<\/dt>\s*<dd\b[^>]*>(.*?)<\/dd>/is', $d, $pairs, PREG_SET_ORDER ) ) {
                $terms = array();
                foreach ( array_slice( $pairs, 0, 30 ) as $pr ) {
                    $terms[] = array( 'name' => trim( wp_strip_all_tags( $pr[1] ) ), 'text' => trim( wp_strip_all_tags( $pr[2] ) ) );
                }
                $parts['defs'][] = $terms;
            }
        }
    }
    return $parts;
}

function rodolfo_schema_graph() {
    $home  = home_url( '/' );
    $lang  = get_bloginfo( 'language' );
    $site  = get_bloginfo( 'name' );
    $graph = array();

    $url = function_exists( 'rodolfo_current_url' ) ? rodolfo_current_url() : $home;
    if ( is_singular() ) {
        $url = get_permalink( get_queried_object_id() );
    } elseif ( is_front_page() && ! is_paged() ) {
        $url = $home;
    }
    $webpage_id = $url . '#webpage';

    // ---------------------------------------------------------------- publisher
    $org_type = function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_org_type' ) : 'Organization';
    $org_name = trim( (string) get_option( 'rodolfo_org_name', '' ) );
    $org_name = '' !== $org_name ? $org_name : $site;
    $topics   = array_values( array_filter( array_map( 'trim', explode( ',', (string) get_option( 'rodolfo_org_knows_about', '' ) ) ) ) );

    $logo_id  = (int) get_theme_mod( 'custom_logo' );
    $logo     = $logo_id ? rodolfo_schema_image_by_id( $logo_id, $home . '#logo' ) : null;
    if ( ! $logo ) {
        $icon = get_site_icon_url( 512 );
        $logo = $icon ? array( '@type' => 'ImageObject', '@id' => $home . '#logo', 'url' => $icon, 'contentUrl' => $icon, 'width' => 512, 'height' => 512 ) : null;
    }

    $org = array(
        '@type'       => $org_type,
        '@id'         => $home . '#organization',
        'name'        => $org_name,
        'url'         => $home,
        'description' => get_bloginfo( 'description' ),
        'sameAs'      => rodolfo_schema_same_as(),
        'knowsAbout'  => $topics,
    );
    if ( 'Person' === $org_type ) {
        $org['image'] = $logo;
    } else {
        $org['logo']  = $logo;
        $org['image'] = $logo ? rodolfo_schema_ref( $home . '#logo' ) : null;
    }
    // "Add as a Preferred Source on Google" is a follow-style action on the publisher.
    if ( function_exists( 'rodolfo_ps_active' ) && rodolfo_ps_active() ) {
        $org['potentialAction'] = array(
            '@type'  => 'FollowAction',
            'name'   => rodolfo_ps_anchor(),
            'target' => rodolfo_ps_url(),
        );
    }
    $graph[] = $org;

    $graph[] = array(
        '@type'           => 'WebSite',
        '@id'             => $home . '#website',
        'url'             => $home,
        'name'            => $site,
        'description'     => get_bloginfo( 'description' ),
        'inLanguage'      => $lang,
        'publisher'       => rodolfo_schema_ref( $home . '#organization' ),
        'copyrightHolder' => rodolfo_schema_ref( $home . '#organization' ),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => $home . '?s={search_term_string}' ),
            'query'       => 'required name=search_term_string',
        ),
    );

    // ----------------------------------------------------------- view detection
    $post       = is_singular() ? get_queried_object() : null;
    $is_post    = is_singular( 'post' );
    $page_type  = 'WebPage';
    $name       = $site;
    $desc       = function_exists( 'rodolfo_meta_description' ) ? rodolfo_meta_description() : '';
    $page_extra = array();

    if ( is_404() ) {
        $name = __( 'Page not found', 'rodolfo' );
    } elseif ( is_search() ) {
        $page_type = 'SearchResultsPage';
        /* translators: %s: search term */
        $name = sprintf( __( 'Search results for %s', 'rodolfo' ), get_search_query() );
    } elseif ( is_author() ) {
        $page_type = 'ProfilePage';
        $author    = get_queried_object();
        $name      = $author instanceof WP_User ? $author->display_name : $site;
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $page_type = 'CollectionPage';
        $term      = get_queried_object();
        $name      = $term instanceof WP_Term ? $term->name : $site;
    } elseif ( is_date() ) {
        $page_type = 'CollectionPage';
        $name      = wp_strip_all_tags( get_the_archive_title() );
    } elseif ( is_front_page() || is_home() ) {
        $page_type = ( is_home() || ( is_front_page() && 'posts' === get_option( 'show_on_front' ) ) ) ? 'CollectionPage' : 'WebPage';
        $name      = is_paged() ? wp_get_document_title() : $site;
    } elseif ( $post ) {
        $name = wp_strip_all_tags( get_the_title( $post ) );
        if ( is_page() ) {
            $slug = $post->post_name;
            if ( in_array( $slug, array( 'contact', 'contact-us' ), true ) ) {
                $page_type = 'ContactPage';
            } elseif ( in_array( $slug, array( 'about', 'about-us', 'about-me' ), true ) ) {
                $page_type = 'AboutPage';
            } elseif ( in_array( $slug, array( 'faq', 'faqs', 'frequently-asked-questions' ), true ) ) {
                $page_type = 'FAQPage';
            } elseif ( is_page_template( 'page-sitemap.php' ) ) {
                $page_type = 'CollectionPage';
            }
        }
    }

    $primary_image = null;
    if ( $post && has_post_thumbnail( $post ) ) {
        $primary_image = rodolfo_schema_image_by_id( get_post_thumbnail_id( $post ), $url . '#primaryimage' );
    }
    if ( $primary_image ) {
        $graph[] = $primary_image;
    }

    // ------------------------------------------------------------- breadcrumbs
    $breadcrumb_id = '';
    if ( function_exists( 'rodolfo_breadcrumb_items' ) && ! is_front_page() ) {
        $els = array();
        foreach ( rodolfo_breadcrumb_items() as $i => $it ) {
            $els[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['name'], 'item' => $it['url'] );
        }
        if ( count( $els ) > 1 ) {
            $breadcrumb_id = $url . '#breadcrumb';
            $graph[]       = array( '@type' => 'BreadcrumbList', '@id' => $breadcrumb_id, 'itemListElement' => $els );
        }
    }

    // ------------------------------------------------------------ page regions
    $px      = RODOLFO_PREFIX;
    $nav_pri = rodolfo_schema_nav_nodes( 'primary', $home, $graph );
    $nav_ftr = rodolfo_schema_nav_nodes( 'footer', $home, $graph );
    $nav_top = rodolfo_schema_nav_nodes( 'topics', $home, $graph );

    $graph[] = array( '@type' => 'WPHeader', '@id' => $url . '#header', 'name' => $site, 'cssSelector' => '#site-header', 'hasPart' => $nav_pri );
    $graph[] = array( '@type' => 'WebPageElement', '@id' => $url . '#main', 'name' => $name, 'cssSelector' => '#content' );
    $parts_refs = array( rodolfo_schema_ref( $url . '#header' ), rodolfo_schema_ref( $url . '#main' ) );
    if ( function_exists( 'rodolfo_has_sidebar' ) && rodolfo_has_sidebar() ) {
        $graph[]      = array( '@type' => 'WPSideBar', '@id' => $url . '#sidebar', 'name' => __( 'Sidebar', 'rodolfo' ), 'cssSelector' => '.' . $px . '-sidebar' );
        $parts_refs[] = rodolfo_schema_ref( $url . '#sidebar' );
    }
    $graph[]      = array(
        '@type'           => 'WPFooter',
        '@id'             => $url . '#footer',
        'name'            => $site,
        'cssSelector'     => '#site-footer',
        'copyrightHolder' => rodolfo_schema_ref( $home . '#organization' ),
        'copyrightYear'   => (int) gmdate( 'Y' ),
        'hasPart'         => array_merge( $nav_ftr, $nav_top ),
    );
    $parts_refs[] = rodolfo_schema_ref( $url . '#footer' );

    // --------------------------------------------------------------- web page
    $webpage = array(
        '@type'              => $page_type,
        '@id'                => $webpage_id,
        'url'                => $url,
        'name'               => $name,
        'description'        => $desc,
        'isPartOf'           => rodolfo_schema_ref( $home . '#website' ),
        'about'              => rodolfo_schema_ref( $home . '#organization' ),
        'inLanguage'         => $lang,
        'primaryImageOfPage' => $primary_image ? rodolfo_schema_ref( $url . '#primaryimage' ) : null,
        'breadcrumb'         => $breadcrumb_id ? rodolfo_schema_ref( $breadcrumb_id ) : null,
        'mainContentOfPage'  => rodolfo_schema_ref( $url . '#main' ),
        'hasPart'            => $parts_refs,
        'copyrightHolder'    => rodolfo_schema_ref( $home . '#organization' ),
        'potentialAction'    => array( array( '@type' => 'ReadAction', 'target' => array( $url ) ) ),
    );
    if ( $post ) {
        $webpage['datePublished'] = get_the_date( 'c', $post );
        $webpage['dateModified']  = get_the_modified_date( 'c', $post );
        $webpage['copyrightYear'] = (int) get_the_date( 'Y', $post );
    } elseif ( function_exists( 'rodolfo_site_modified_time' ) && ( is_front_page() || is_home() ) ) {
        $mod = rodolfo_site_modified_time();
        if ( $mod ) {
            $webpage['dateModified'] = gmdate( 'c', $mod );
        }
    }
    $webpage['speakable'] = array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( 'h1', '.quick-answer' ) );
    $webpage['potentialAction'][] = array( '@type' => 'ShareAction', 'target' => $url );
    $webpage['potentialAction'][] = array( '@type' => 'BookmarkAction', 'target' => $url );

    // ------------------------------------------------------------ single posts
    if ( $is_post ) {
        $parts    = rodolfo_schema_content_parts( $post );
        $art_type = function_exists( 'rodolfo_choice' ) ? rodolfo_choice( 'rodolfo_article_type' ) : 'BlogPosting';
        $art_id   = $url . '#article';
        $author_id = (int) $post->post_author;
        $author_url = get_author_posts_url( $author_id );
        $person_id  = $author_url . '#person';

        // Author: guest bylines declare one Person per name; otherwise the WordPress user.
        $guest_names = function_exists( 'rodolfo_ga_names' ) ? rodolfo_ga_names( $post ) : array();
        if ( $guest_names ) {
            $author_ref = array();
            foreach ( $guest_names as $gi => $gname ) {
                $gid        = $url . '#author-' . ( $gi + 1 );
                $graph[]    = array( '@type' => 'Person', '@id' => $gid, 'name' => $gname );
                $author_ref[] = rodolfo_schema_ref( $gid );
            }
            if ( 1 === count( $author_ref ) ) {
                $author_ref = $author_ref[0];
            }
        } else {
            $author_same = array();
            if ( function_exists( 'rodolfo_author_social_links' ) ) {
                foreach ( rodolfo_author_social_links( $author_id ) as $l ) {
                    $author_same[] = $l['url'];
                }
            }
            $avatar = get_avatar_url( $author_id, array( 'size' => 256 ) );
            $graph[] = array(
                '@type'       => 'Person',
                '@id'         => $person_id,
                'name'        => get_the_author_meta( 'display_name', $author_id ),
                'url'         => $author_url,
                'description' => get_the_author_meta( 'description', $author_id ),
                'image'       => $avatar ? array( '@type' => 'ImageObject', 'url' => $avatar, 'width' => 256, 'height' => 256 ) : null,
                'sameAs'      => array_values( array_unique( $author_same ) ),
            );
            $author_ref = rodolfo_schema_ref( $person_id );
        }

        // Terms
        $cats = get_the_category( $post->ID );
        $tags = get_the_tags( $post->ID );
        $about = array();
        foreach ( array_merge( (array) $cats, is_array( $tags ) ? $tags : array() ) as $t ) {
            $tl = get_term_link( $t );
            $about[] = array( '@type' => 'DefinedTerm', 'name' => $t->name, 'url' => is_string( $tl ) ? $tl : null, 'inDefinedTermSet' => $home );
        }

        // Comments
        $comment_refs = array();
        $comments     = get_comments( array( 'post_id' => $post->ID, 'status' => 'approve', 'type' => 'comment', 'number' => 20, 'orderby' => 'comment_date_gmt', 'order' => 'ASC' ) );
        foreach ( $comments as $c ) {
            $cid     = $url . '#comment-' . $c->comment_ID;
            $graph[] = array(
                '@type'       => 'Comment',
                '@id'         => $cid,
                'text'        => rodolfo_trim_sentence( wp_strip_all_tags( $c->comment_content ), 20, 500 ),
                'dateCreated' => mysql2date( 'c', $c->comment_date_gmt, false ),
                'url'         => get_comment_link( $c ),
                'author'      => array( '@type' => 'Person', 'name' => $c->comment_author, 'url' => ( $c->comment_author_url && 0 === strpos( $c->comment_author_url, 'http' ) ) ? $c->comment_author_url : null ),
                'parentItem'  => $c->comment_parent ? rodolfo_schema_ref( $url . '#comment-' . $c->comment_parent ) : null,
                'about'       => rodolfo_schema_ref( $art_id ),
            );
            $comment_refs[] = rodolfo_schema_ref( $cid );
        }

        // Reading time / words
        $minutes = function_exists( 'rodolfo_reading_time' ) ? rodolfo_reading_time( $post ) : 0;
        $words   = rodolfo_schema_word_count( $post );

        // Content blocks → graph nodes
        $has_part = array();
        if ( $parts['faqs'] ) {
            $qs = array();
            foreach ( $parts['faqs'] as $f ) {
                $qs[] = array( '@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['a'] ) );
            }
            $graph[]    = array( '@type' => 'FAQPage', '@id' => $url . '#faq', 'url' => $url . '#faq', 'name' => $name, 'isPartOf' => rodolfo_schema_ref( $webpage_id ), 'inLanguage' => $lang, 'mainEntity' => $qs );
            $has_part[] = rodolfo_schema_ref( $url . '#faq' );
            rodolfo_schema_state( 'faq', true );
        }
        if ( $parts['howto'] ) {
            $h     = $parts['howto'];
            $steps = array();
            foreach ( $h['steps'] as $i => $s ) {
                $steps[] = array( '@type' => 'HowToStep', '@id' => $url . '#step-' . ( $i + 1 ), 'position' => $i + 1, 'name' => $s['name'], 'text' => $s['text'], 'url' => $url . '#step-' . ( $i + 1 ), 'image' => $s['image'] ? array( '@type' => 'ImageObject', 'url' => $s['image'] ) : null );
            }
            $graph[]    = array(
                '@type'            => 'HowTo',
                '@id'              => $url . '#howto',
                'name'             => $h['name'] ? $h['name'] : $name,
                'description'      => $h['description'],
                'totalTime'        => $h['time'],
                'step'             => $steps,
                'inLanguage'       => $lang,
                'mainEntityOfPage' => rodolfo_schema_ref( $webpage_id ),
            );
            $has_part[] = rodolfo_schema_ref( $url . '#howto' );
            rodolfo_schema_state( 'howto', true );
        }
        foreach ( $parts['compare'] as $i => $c ) {
            $tid        = $url . '#table-' . ( $i + 1 );
            $graph[]    = array(
                '@type'       => 'Table',
                '@id'         => $tid,
                'name'        => $c['caption'] ? $c['caption'] : sprintf( /* translators: 1,2: compared items */ __( '%1$s compared with %2$s', 'rodolfo' ), $c['a'], $c['b'] ),
                'about'       => array( array( '@type' => 'Thing', 'name' => $c['a'] ), array( '@type' => 'Thing', 'name' => $c['b'] ) ),
                'cssSelector' => '.comparison-table',
            );
            $has_part[] = rodolfo_schema_ref( $tid );
        }
        foreach ( $parts['proof'] as $i => $p ) {
            $pid        = $url . '#proof-' . ( $i + 1 );
            $graph[]    = array(
                '@type'       => 'Claim',
                '@id'         => $pid,
                'text'        => trim( $p['metric'] . ' ' . $p['label'] ),
                'citation'    => $p['cite'] ? esc_url_raw( $p['cite'] ) : null,
                'author'      => rodolfo_schema_ref( $home . '#organization' ),
                'cssSelector' => '.proof-callout',
            );
            $has_part[] = rodolfo_schema_ref( $pid );
        }
        if ( count( $parts['headings'] ) >= 2 ) {
            $li = array();
            foreach ( $parts['headings'] as $i => $hd ) {
                $li[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $hd['name'], 'url' => $url . '#' . $hd['id'] );
            }
            $graph[]    = array( '@type' => 'ItemList', '@id' => $url . '#toc', 'name' => __( 'Table of contents', 'rodolfo' ), 'numberOfItems' => count( $li ), 'itemListElement' => $li );
            $has_part[] = rodolfo_schema_ref( $url . '#toc' );
        }

        foreach ( $parts['tables'] as $i => $t ) {
            $tid        = $url . '#bodytable-' . ( $i + 1 );
            $graph[]    = array( '@type' => 'Table', '@id' => $tid, 'name' => $t['caption'] ? $t['caption'] : sprintf( /* translators: %d: table number */ __( 'Table %d', 'rodolfo' ), $i + 1 ), 'about' => array_map( function ( $c ) { return array( '@type' => 'Thing', 'name' => $c ); }, $t['cols'] ), 'inLanguage' => $lang );
            $has_part[] = rodolfo_schema_ref( $tid );
        }
        foreach ( $parts['quotes'] as $i => $q ) {
            $qid        = $url . '#quote-' . ( $i + 1 );
            $graph[]    = array( '@type' => 'Quotation', '@id' => $qid, 'text' => $q, 'isPartOf' => rodolfo_schema_ref( $webpage_id ), 'inLanguage' => $lang );
            $has_part[] = rodolfo_schema_ref( $qid );
        }
        foreach ( $parts['lists'] as $i => $l ) {
            $lid = $url . '#list-' . ( $i + 1 );
            $li  = array();
            foreach ( $l['items'] as $k => $it ) {
                $li[] = array( '@type' => 'ListItem', 'position' => $k + 1, 'name' => mb_substr( $it, 0, 200 ) );
            }
            $graph[]    = array( '@type' => 'ItemList', '@id' => $lid, 'itemListOrder' => $l['ordered'] ? 'https://schema.org/ItemListOrderAscending' : 'https://schema.org/ItemListUnordered', 'numberOfItems' => count( $li ), 'itemListElement' => $li );
            $has_part[] = rodolfo_schema_ref( $lid );
        }
        foreach ( $parts['defs'] as $i => $terms ) {
            $did = $url . '#terms-' . ( $i + 1 );
            $dt  = array();
            foreach ( $terms as $t ) {
                $dt[] = array( '@type' => 'DefinedTerm', 'name' => $t['name'], 'description' => $t['text'], 'inDefinedTermSet' => rodolfo_schema_ref( $did ) );
            }
            $graph[]    = array( '@type' => 'DefinedTermSet', '@id' => $did, 'hasDefinedTerm' => $dt );
            $has_part[] = rodolfo_schema_ref( $did );
        }

        if ( function_exists( 'rodolfo_endmark_applies' ) && rodolfo_endmark_applies( $post ) ) {
            $eid        = $url . '#end';
            $graph[]    = array( '@type' => 'WebPageElement', '@id' => $eid, 'name' => __( 'End of article', 'rodolfo' ), 'description' => __( 'Marks the end of the article text.', 'rodolfo' ), 'isPartOf' => rodolfo_schema_ref( $webpage_id ), 'cssSelector' => '.' . RODOLFO_PREFIX . '-end' );
            $has_part[] = rodolfo_schema_ref( $eid );
        }

        // Media
        $media = array();
        foreach ( $parts['images'] as $i => $im ) {
            $o = rodolfo_schema_image_by_url( $im['url'] );
            if ( $o && empty( $o['caption'] ) && $im['alt'] ) {
                $o['caption'] = $im['alt'];
            }
            $media[] = $o;
        }
        $videos = array();
        foreach ( $parts['videos'] as $v ) {
            $videos[] = array( '@type' => 'VideoObject', 'name' => $name, 'description' => $desc ? $desc : $name, 'embedUrl' => $v, 'uploadDate' => get_the_date( 'c', $post ), 'thumbnailUrl' => $primary_image ? $primary_image['url'] : ( $logo ? $logo['url'] : null ) );
        }

        // Related reading
        $related = array();
        if ( function_exists( 'rodolfo_get_related_posts' ) ) {
            foreach ( (array) rodolfo_get_related_posts( $post->ID, 3 ) as $rp ) {
                $rp = is_object( $rp ) ? $rp : get_post( $rp );
                if ( $rp ) {
                    $related[] = get_permalink( $rp );
                }
            }
        }
        $webpage['relatedLink'] = $related;

        $headline = wp_strip_all_tags( get_the_title( $post ) );
        $alt_head = trim( (string) get_post_meta( $post->ID, '_theme_alt_headline', true ) );
        if ( '' === $alt_head && $desc ) {
            $alt_head = rodolfo_trim_sentence( $desc, 20, 110 );
        }

        $series_part = null;
        $series_pos  = null;
        if ( function_exists( 'rodolfo_series_context' ) ) {
            $series = rodolfo_series_context( $post );
            if ( $series && $series['total'] > 1 ) {
                $tl          = get_term_link( $series['term'] );
                $series_part = array( '@type' => 'CreativeWorkSeries', 'name' => $series['term']->name, 'url' => is_string( $tl ) ? $tl : $home, 'numberOfItems' => $series['total'] );
                $series_pos  = $series['index'];
            }
        }

        $actions = array( array( '@type' => 'ReadAction', 'target' => array( $url ) ) );
        if ( comments_open( $post ) ) {
            $actions[] = array( '@type' => 'CommentAction', 'name' => __( 'Comment', 'rodolfo' ), 'target' => $url . '#respond' );
        }

        $graph[] = array(
            '@type'               => $art_type,
            '@id'                 => $art_id,
            'isPartOf'            => $series_part ? array( rodolfo_schema_ref( $webpage_id ), $series_part ) : rodolfo_schema_ref( $webpage_id ),
            'position'            => $series_pos,
            'mainEntityOfPage'    => rodolfo_schema_ref( $webpage_id ),
            'headline'            => mb_substr( $headline, 0, 110 ),
            'alternativeHeadline' => $alt_head !== $headline ? $alt_head : null,
            'description'         => $desc,
            'abstract'            => $parts['answer'],
            'image'               => $primary_image ? rodolfo_schema_ref( $url . '#primaryimage' ) : ( $logo ? rodolfo_schema_ref( $home . '#logo' ) : null ),
            'thumbnailUrl'        => $primary_image ? $primary_image['url'] : null,
            'datePublished'       => get_the_date( 'c', $post ),
            'dateModified'        => get_the_modified_date( 'c', $post ),
            'author'              => $author_ref,
            'publisher'           => rodolfo_schema_ref( $home . '#organization' ),
            'copyrightHolder'     => rodolfo_schema_ref( $home . '#organization' ),
            'copyrightYear'       => (int) get_the_date( 'Y', $post ),
            'articleSection'      => wp_list_pluck( (array) $cats, 'name' ),
            'keywords'            => is_array( $tags ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : null,
            'about'               => $about,
            'wordCount'           => $words,
            'timeRequired'        => $minutes ? rodolfo_schema_duration( $minutes ) : null,
            'inLanguage'          => $lang,
            'isAccessibleForFree' => true,
            'commentCount'        => (int) get_comments_number( $post ),
            'comment'             => $comment_refs,
            'associatedMedia'     => $media,
            'video'               => $videos,
            'hasPart'             => $has_part,
            'speakable'           => array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( 'h1', '.quick-answer' ) ),
            'potentialAction'     => $actions,
        );
    }

    // ---------------------------------------------------- listings (ItemList)
    $listing = null;
    if ( ( is_front_page() || is_home() || is_category() || is_tag() || is_tax() || is_date() || is_author() || is_search() ) && have_posts() ) {
        global $wp_query;
        $li  = array();
        $pos = ( max( 1, (int) get_query_var( 'paged' ) ) - 1 ) * (int) get_query_var( 'posts_per_page' );
        foreach ( (array) $wp_query->posts as $p ) {
            $pos++;
            $thumb = get_the_post_thumbnail_url( $p, 'full' );
            $li[]  = array(
                '@type'    => 'ListItem',
                'position' => $pos,
                'url'      => get_permalink( $p ),
                'name'     => wp_strip_all_tags( get_the_title( $p ) ),
                'item'     => array(
                    '@type'         => 'BlogPosting',
                    '@id'           => get_permalink( $p ) . '#article',
                    'headline'      => mb_substr( wp_strip_all_tags( get_the_title( $p ) ), 0, 110 ),
                    'url'           => get_permalink( $p ),
                    'datePublished' => get_the_date( 'c', $p ),
                    'image'         => $thumb ? $thumb : null,
                ),
            );
        }
        $listing = array( '@type' => 'ItemList', '@id' => $url . '#list', 'numberOfItems' => count( $li ), 'itemListElement' => $li );
        $graph[] = $listing;
        $webpage['mainEntity'] = rodolfo_schema_ref( $url . '#list' );
    }
    if ( ( is_front_page() || is_home() ) && ! is_paged() && 'posts' === get_option( 'show_on_front' ) ) {
        $graph[] = array( '@type' => 'Blog', '@id' => $home . '#blog', 'name' => $site, 'url' => $home, 'inLanguage' => $lang, 'publisher' => rodolfo_schema_ref( $home . '#organization' ), 'isPartOf' => rodolfo_schema_ref( $home . '#website' ) );
        $webpage['about'] = rodolfo_schema_ref( $home . '#blog' );
    }

    // HTML sitemap page: ItemList of the sections it links to.
    if ( is_page_template( 'page-sitemap.php' ) && function_exists( 'rodolfo_sitemap_links' ) ) {
        $li = array();
        foreach ( rodolfo_sitemap_links() as $i => $l ) {
            $li[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $l[0], 'url' => $l[1] );
        }
        $graph[] = array( '@type' => 'ItemList', '@id' => $url . '#sitemap', 'name' => $name, 'numberOfItems' => count( $li ), 'itemListElement' => $li );
        $webpage['mainEntity'] = rodolfo_schema_ref( $url . '#sitemap' );
    }

    // Author archive: whose profile it is.
    if ( is_author() && $author instanceof WP_User ) {
        $a_same = array();
        if ( function_exists( 'rodolfo_author_social_links' ) ) {
            foreach ( rodolfo_author_social_links( $author->ID ) as $l ) {
                $a_same[] = $l['url'];
            }
        }
        $avatar  = get_avatar_url( $author->ID, array( 'size' => 256 ) );
        $graph[] = array(
            '@type'       => 'Person',
            '@id'         => get_author_posts_url( $author->ID ) . '#person',
            'name'        => $author->display_name,
            'url'         => get_author_posts_url( $author->ID ),
            'description' => $author->description,
            'image'       => $avatar ? array( '@type' => 'ImageObject', 'url' => $avatar, 'width' => 256, 'height' => 256 ) : null,
            'sameAs'      => $a_same,
        );
        $webpage['mainEntity'] = rodolfo_schema_ref( get_author_posts_url( $author->ID ) . '#person' );
    }

    // Coming-soon pages describe no content yet.
    if ( function_exists( 'rodolfo_is_coming_soon' ) && is_page() && rodolfo_is_coming_soon() ) {
        unset( $webpage['speakable'], $webpage['mainEntity'] );
    }

    array_splice( $graph, 2, 0, array( $webpage ) );

    /** Filter the finished graph (add or amend nodes). */
    return apply_filters( 'rodolfo_schema_graph', $graph );
}

function rodolfo_schema_output() {
    if ( function_exists( 'rodolfo_seo_plugin_active' ) && rodolfo_seo_plugin_active() ) {
        return;
    }
    $graph = rodolfo_schema_clean( rodolfo_schema_graph() );
    if ( empty( $graph ) ) {
        return;
    }
    $data = array( '@context' => 'https://schema.org', '@graph' => array_values( $graph ) );
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
        . "</script>\n";
}
add_action( 'wp_head', 'rodolfo_schema_output', 6 );
