<?php
/**
 * End mark: a small symbol or image after the last paragraph of an article.
 *
 * Inserted into the_content (never into FAQ, how-to, comparison or proof
 * blocks, tables, lists, quotes, code or footnotes). Options (Theme Options →
 * Content): on/off, also on pages, symbol or image, size, placement, minimum
 * length, excluded post IDs, hide on small screens. Per post it can be turned
 * off from the "End mark" box in the editor. `[end_mark]` places it by hand and
 * suppresses the automatic one. The Schema.org graph declares it as a
 * WebPageElement (see rodolfo_schema_graph()).
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

/** Per-post "off" flag. */
const RODOLFO_EM_META = '_rodolfo_endmark_off';

/** Settings with defaults (stored in individual options). */
function rodolfo_em_settings() {
    static $s = null;
    if ( null !== $s ) {
        return $s;
    }
    $placements = array( 'last_paragraph', 'before_footnotes', 'append', 'selector' );
    $placement  = (string) get_option( 'rodolfo_em_placement', 'last_paragraph' );
    $s = array(
        'enabled'    => '0' !== (string) get_option( 'rodolfo_em_enabled', '1' ),
        'pages'      => '' !== (string) get_option( 'rodolfo_em_pages', '' ),
        'type'       => 'image' === get_option( 'rodolfo_em_type', 'symbol' ) ? 'image' : 'symbol',
        'symbol'     => (string) get_option( 'rodolfo_em_symbol', '' ) !== '' ? (string) get_option( 'rodolfo_em_symbol' ) : '∎',
        'image'      => esc_url_raw( (string) get_option( 'rodolfo_em_image', '' ) ),
        'size'       => max( 8, min( 64, (int) get_option( 'rodolfo_em_size', 16 ) ) ),
        'placement'  => in_array( $placement, $placements, true ) ? $placement : 'last_paragraph',
        'selector'   => (string) get_option( 'rodolfo_em_selector', '' ),
        'min_words'  => max( 0, (int) get_option( 'rodolfo_em_min_words', 0 ) ),
        'exclude'    => array_filter( array_map( 'absint', explode( ',', (string) get_option( 'rodolfo_em_exclude', '' ) ) ) ),
        'hide_small' => '' !== (string) get_option( 'rodolfo_em_hide_small', '' ),
        'margin'     => (string) get_option( 'rodolfo_em_margin', '' ) !== '' ? (string) get_option( 'rodolfo_em_margin' ) : '.25em',
    );
    return $s;
}

/**
 * Would the automatic end mark be placed on this post? Deterministic from the
 * stored post, so the head (JSON-LD) and the body agree.
 */
function rodolfo_endmark_applies( $post = null ) {
    $post = get_post( $post );
    $s    = rodolfo_em_settings();
    if ( ! $post instanceof WP_Post || ! $s['enabled'] ) {
        return false;
    }
    $types = array( 'post' );
    if ( $s['pages'] ) {
        $types[] = 'page';
    }
    if ( ! in_array( $post->post_type, (array) apply_filters( 'rodolfo_endmark_post_types', $types ), true ) ) {
        return false;
    }
    if ( get_post_meta( $post->ID, RODOLFO_EM_META, true ) || in_array( (int) $post->ID, $s['exclude'], true ) ) {
        return false;
    }
    if ( $s['min_words'] > 0 && str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) < $s['min_words'] ) {
        return false;
    }
    return true;
}

/** The marker markup. */
function rodolfo_endmark_html() {
    $s     = rodolfo_em_settings();
    $class = RODOLFO_PREFIX . '-end' . ( $s['hide_small'] ? ' ' . RODOLFO_PREFIX . '-end-s' : '' );
    $style = preg_match( '/^-?[0-9.]+(em|rem|px|%)?$/', $s['margin'] ) ? ' style="margin-left:' . esc_attr( $s['margin'] ) . '"' : '';
    if ( 'image' === $s['type'] && $s['image'] ) {
        $inner = '<img class="' . esc_attr( RODOLFO_PREFIX . '-end-img' ) . '" src="' . esc_url( $s['image'] ) . '" alt="" width="' . (int) $s['size'] . '" height="' . (int) $s['size'] . '" loading="lazy" decoding="async" />';
    } else {
        $inner = esc_html( $s['symbol'] );
    }
    return '<span class="' . esc_attr( $class ) . '" aria-hidden="true"' . $style . '>' . $inner . '</span>';
}

/** [end_mark] — manual placement. */
function rodolfo_endmark_shortcode() {
    return rodolfo_endmark_html();
}
add_shortcode( 'end_mark', 'rodolfo_endmark_shortcode' );

/**
 * Byte ranges the marker must never enter: AEO blocks, microdata FAQ/HowTo
 * and similar structured containers.
 */
function rodolfo_endmark_protected_ranges( $content ) {
    $ranges = array();
    $pattern = '/<(div|section|aside|details|table|ol|ul|dl)\b[^>]*(?:class=["\'][^"\']*\b(?:faq-block|howto-block|quick-answer|proof-callout|comparison-table|footnotes|wp-block-yoast-faq-block|wp-block-yoast-how-to-block)\b[^"\']*["\']|itemtype=["\']https?:\/\/schema\.org\/(?:FAQPage|HowTo|QAPage|Recipe|Review|Product)["\'])[^>]*>/i';
    if ( preg_match_all( $pattern, $content, $m, PREG_OFFSET_CAPTURE ) ) {
        foreach ( $m[0] as $i => $hit ) {
            $end = rodolfo_endmark_closing_pos( $content, strtolower( $m[1][ $i ][0] ), $hit[1] );
            if ( false !== $end ) {
                $ranges[] = array( $hit[1], $end );
            }
        }
    }
    return $ranges;
}

/** End offset of the element opened at $start (depth-aware). */
function rodolfo_endmark_closing_pos( $content, $tag, $start ) {
    $depth = 0;
    $pos   = $start;
    $len   = strlen( $content );
    while ( $pos < $len ) {
        $open  = stripos( $content, '<' . $tag, $pos + 1 );
        $close = stripos( $content, '</' . $tag, $pos + 1 );
        if ( false === $close ) {
            return false;
        }
        if ( false !== $open && $open < $close ) {
            $depth++;
            $pos = $open;
            continue;
        }
        if ( 0 === $depth ) {
            $gt = strpos( $content, '>', $close );
            return false === $gt ? false : $gt + 1;
        }
        $depth--;
        $pos = $close;
    }
    return false;
}

function rodolfo_endmark_in_ranges( $pos, array $ranges ) {
    foreach ( $ranges as $r ) {
        if ( $pos >= $r[0] && $pos < $r[1] ) {
            return true;
        }
    }
    return false;
}

/** Offset just before the closing tag of the last real paragraph, or false. */
function rodolfo_endmark_last_paragraph( $content, $limit, array $ranges ) {
    if ( ! preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/is', $content, $m, PREG_OFFSET_CAPTURE ) ) {
        return false;
    }
    // Open/close counts of unsafe containers are tracked incrementally.
    $unsafe = array( 'table', 'ul', 'ol', 'blockquote', 'figure', 'pre', 'code' );
    $found  = false;
    foreach ( $m[0] as $i => $hit ) {
        $start = $hit[1];
        $end   = $start + strlen( $hit[0] );
        if ( false !== $limit && $end > $limit ) {
            continue;
        }
        if ( rodolfo_endmark_in_ranges( $start, $ranges ) ) {
            continue;
        }
        $before = substr( $content, 0, $start );
        $inside = false;
        foreach ( $unsafe as $tag ) {
            if ( preg_match_all( '/<' . $tag . '\b[^>]*>/i', $before ) > preg_match_all( '/<\/' . $tag . '>/i', $before ) ) {
                $inside = true;
                break;
            }
        }
        if ( $inside ) {
            continue;
        }
        $text = preg_replace( '/(?:\s|&nbsp;|&#160;|\x{00A0})+/u', '', wp_strip_all_tags( preg_replace( '/<br\s*\/?>/i', '', $m[1][ $i ][0] ) ) );
        if ( '' !== $text ) {
            $found = $start + strripos( $hit[0], '</p>' );
        }
    }
    return $found;
}

/** the_content filter: place the marker. */
function rodolfo_endmark_filter( $content ) {
    if ( ! is_singular() || ! in_the_loop() || ! is_main_query() || is_feed() || ! rodolfo_endmark_applies() ) {
        return $content;
    }
    $class = RODOLFO_PREFIX . '-end';
    if ( false !== strpos( $content, 'class="' . $class ) ) {
        return $content; // Placed by hand with [end_mark].
    }
    $s       = rodolfo_em_settings();
    $mark    = rodolfo_endmark_html();
    $content = rtrim( $content );
    $content = preg_replace( '/(<p[^>]*>(?:\s|&nbsp;|&#160;|\x{00A0})*<\/p>\s*)+$/iu', '', $content );
    $content = preg_replace( '/(<br\s*\/?>\s*){2,}$/i', '', $content );

    $ranges = rodolfo_endmark_protected_ranges( $content );
    $notes  = false;
    foreach ( array( '<ol class="footnotes"', '<div class="footnotes"', '<div id="footnotes"', '<section class="footnotes"', '<div class="simple-footnotes"' ) as $needle ) {
        $p = stripos( $content, $needle );
        if ( false !== $p && ( false === $notes || $p < $notes ) ) {
            $notes = $p;
        }
    }

    if ( 'append' === $s['placement'] ) {
        return $content . $mark;
    }
    if ( 'selector' === $s['placement'] && preg_match( '/^([.#])([A-Za-z0-9_-]+)$/', $s['selector'], $sel ) ) {
        $attr    = '.' === $sel[1] ? 'class\s*=\s*["\'][^"\']*\b' . preg_quote( $sel[2], '/' ) . '\b[^"\']*["\']' : 'id\s*=\s*["\']' . preg_quote( $sel[2], '/' ) . '["\']';
        if ( preg_match( '/<[^>]*\b' . $attr . '[^>]*>/i', $content, $hit, PREG_OFFSET_CAPTURE ) && ! rodolfo_endmark_in_ranges( $hit[0][1], $ranges ) ) {
            return substr_replace( $content, $mark, $hit[0][1], 0 );
        }
        return $content . $mark;
    }
    if ( 'before_footnotes' === $s['placement'] && false !== $notes && ! rodolfo_endmark_in_ranges( $notes, $ranges ) ) {
        return substr_replace( $content, $mark, $notes, 0 );
    }
    $at = rodolfo_endmark_last_paragraph( $content, $notes, $ranges );
    if ( false !== $at ) {
        return substr_replace( $content, $mark, $at, 0 );
    }
    return $content . $mark;
}
add_filter( 'the_content', 'rodolfo_endmark_filter', 9999 );

// ---------------------------------------------------------------- editing UI

function rodolfo_endmark_box() {
    $types = array( 'post' );
    if ( rodolfo_em_settings()['pages'] ) {
        $types[] = 'page';
    }
    add_meta_box( 'tk-endmark', __( 'End mark', 'rodolfo' ), 'rodolfo_endmark_box_render', $types, 'side', 'low' );
}
add_action( 'add_meta_boxes', 'rodolfo_endmark_box' );

function rodolfo_endmark_box_render( $post ) {
    wp_nonce_field( 'rodolfo_em_save', 'rodolfo_em_nonce' );
    echo '<p><label><input type="checkbox" name="rodolfo_em_off" value="1"' . checked( (bool) get_post_meta( $post->ID, RODOLFO_EM_META, true ), true, false ) . ' /> ' . esc_html__( 'No end mark on this post', 'rodolfo' ) . '</label></p>';
}

function rodolfo_endmark_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_em_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rodolfo_em_nonce'] ), 'rodolfo_em_save' ) ) {
        return;
    }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! empty( $_POST['rodolfo_em_off'] ) ) {
        update_post_meta( $post_id, RODOLFO_EM_META, '1' );
    } else {
        delete_post_meta( $post_id, RODOLFO_EM_META );
    }
}
add_action( 'save_post', 'rodolfo_endmark_save' );
