<?php
/**
 * Footnotes: write ((a note)) inside a post and get numbered, linked notes.
 *
 * - ((text))        inline note; replaced by a superscript link, the text is
 *                   listed under the article.
 * - ((ref:N))       reuses the text of note N.
 * - <!--startnum=N--> starts numbering at N.
 *
 * Options (Theme Options → Content): delimiters, list style, back-link text and
 * position, superscript on/off, merge identical notes, show in listings,
 * heading above the list. Per post, the list style can be overridden from the
 * "Footnotes" box in the editor. Notes are never created inside <script>,
 * <pre>, <code> or HTML comments. In excerpts and meta descriptions the markers
 * are removed. The Schema.org graph declares the list as a WebPageElement.
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

const RODOLFO_FN_STYLE_META = '_rodolfo_footnote_style';

/** Allowed list styles. */
function rodolfo_fn_styles() {
    return array(
        'decimal'              => '1, 2, 3',
        'decimal-leading-zero' => '01, 02, 03',
        'lower-alpha'          => 'a, b, c',
        'upper-alpha'          => 'A, B, C',
        'lower-roman'          => 'i, ii, iii',
        'upper-roman'          => 'I, II, III',
    );
}

function rodolfo_fn_settings() {
    static $s = null;
    if ( null !== $s ) {
        return $s;
    }
    $open  = (string) get_option( 'rodolfo_fn_open', '((' );
    $close = (string) get_option( 'rodolfo_fn_close', '))' );
    if ( '' === $open || '' === $close || $open === $close ) {
        $open  = '((';
        $close = '))';
    }
    $style = (string) get_option( 'rodolfo_fn_style', 'decimal' );
    $back  = (string) get_option( 'rodolfo_fn_backlink', '' );
    $s     = array(
        'open'     => $open,
        'close'    => $close,
        'style'    => isset( rodolfo_fn_styles()[ $style ] ) ? $style : 'decimal',
        'back'     => '' !== $back ? $back : '↩',
        'back_pos' => 'start' === get_option( 'rodolfo_fn_back_pos', 'end' ) ? 'start' : 'end',
        'sup'      => '0' !== (string) get_option( 'rodolfo_fn_sup', '1' ),
        'combine'  => '0' !== (string) get_option( 'rodolfo_fn_combine', '1' ),
        'listings' => '' !== (string) get_option( 'rodolfo_fn_listings', '' ),
        'heading'  => trim( (string) get_option( 'rodolfo_fn_heading', '' ) ),
    );
    return $s;
}

/** Effective list style for a post (per-post override wins). */
function rodolfo_fn_post_style( $post_id ) {
    $own = (string) get_post_meta( $post_id, RODOLFO_FN_STYLE_META, true );
    if ( '' === $own ) {
        $own = (string) get_post_meta( $post_id, 'footnote_style', true ); // Value saved before this feature shipped in the theme.
    }
    return isset( rodolfo_fn_styles()[ $own ] ) ? $own : rodolfo_fn_settings()['style'];
}

/** Class names (prefix-aware, so installs do not share a fixed signature). */
function rodolfo_fn_class( $part ) {
    return RODOLFO_PREFIX . '-' . $part;
}

/** Regex that finds footnote markers. */
function rodolfo_fn_regex() {
    $s = rodolfo_fn_settings();
    return '/(' . preg_quote( $s['open'], '/' ) . ')(.*)(' . preg_quote( $s['close'], '/' ) . ')/Us';
}

/** Number of notes in a post's stored content (0 when none). Used by the schema graph. */
function rodolfo_fn_count( $post = null ) {
    $post = get_post( $post );
    if ( ! $post instanceof WP_Post || ! rodolfo_fn_applies_to_post( $post ) ) {
        return 0;
    }
    $body = rodolfo_fn_protect( $post->post_content, $store );
    return (int) preg_match_all( rodolfo_fn_regex(), $body );
}

function rodolfo_fn_applies_to_post( $post ) {
    return (bool) apply_filters( 'rodolfo_footnotes_for_post', 'revision' !== $post->post_type, $post );
}

/** Remove the markers (keeping nothing) — for excerpts and descriptions. */
function rodolfo_fn_strip( $text ) {
    $text = (string) $text;
    $s    = rodolfo_fn_settings();
    if ( false === strpos( $text, $s['open'] ) ) {
        return $text;
    }
    return preg_replace( rodolfo_fn_regex(), '', $text );
}

/** Swap protected regions for placeholders; $store receives the originals. */
function rodolfo_fn_protect( $data, &$store ) {
    $store = array();
    return preg_replace_callback(
        '#<(script|pre|code|style)\b[^>]*>.*?</\1>|<!--(?!\s*startnum).*?-->#is',
        function ( $m ) use ( &$store ) {
            $key     = '<!--tkfn' . count( $store ) . '-->';
            $store[] = array( $key, $m[0] );
            return $key;
        },
        $data
    );
}

function rodolfo_fn_restore( $data, array $store ) {
    foreach ( $store as $item ) {
        $data = str_replace( $item[0], $item[1], $data );
    }
    return $data;
}

/** 1 → a, 27 → aa. */
function rodolfo_fn_alpha( $n, $upper ) {
    $out = '';
    while ( $n > 0 ) {
        $n--;
        $out = chr( 97 + ( $n % 26 ) ) . $out;
        $n   = intdiv( $n, 26 );
    }
    return $upper ? strtoupper( $out ) : $out;
}

function rodolfo_fn_roman( $n, $upper ) {
    $map = array( 'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1 );
    $out = '';
    foreach ( $map as $r => $v ) {
        $out .= str_repeat( $r, intdiv( $n, $v ) );
        $n   %= $v;
    }
    return $upper ? $out : strtolower( $out );
}

/** Label shown in the text for note number $n under $style. */
function rodolfo_fn_label( $n, $style, $total ) {
    switch ( $style ) {
        case 'decimal-leading-zero':
            return sprintf( '%0' . max( 2, strlen( (string) $total ) ) . 'd', $n );
        case 'lower-alpha':
            return rodolfo_fn_alpha( $n, false );
        case 'upper-alpha':
            return rodolfo_fn_alpha( $n, true );
        case 'lower-roman':
            return rodolfo_fn_roman( $n, false );
        case 'upper-roman':
            return rodolfo_fn_roman( $n, true );
    }
    return (string) $n;
}

/** the_content filter (after wpautop and shortcodes). */
function rodolfo_fn_process( $data ) {
    $post = get_post();
    if ( ! $post instanceof WP_Post || ! rodolfo_fn_applies_to_post( $post ) ) {
        return $data;
    }
    $s = rodolfo_fn_settings();
    if ( false === strpos( $data, $s['open'] ) ) {
        return $data;
    }
    // Excerpts run the_content too: drop the markers, never append the list.
    if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) ) {
        return rodolfo_fn_strip( $data );
    }

    $data = rodolfo_fn_protect( $data, $store );

    $start_number = ( 1 === preg_match( '/<!--\s*startnum=(\d+)\s*-->/', $data, $sm ) ) ? max( 1, (int) $sm[1] ) : 1;

    $data = preg_replace( '/<!--\s*startnum=\d+\s*-->/', '', $data );
    if ( ! preg_match_all( rodolfo_fn_regex(), $data, $ids, PREG_SET_ORDER ) ) {
        return rodolfo_fn_restore( $data, $store );
    }

    $display = true;
    if ( is_feed() ) {
        $full_link = true;
    } else {
        $full_link = false;
        if ( ! is_singular() && ! $s['listings'] ) {
            $display = false;
        }
    }
    if ( is_preview() ) {
        $full_link = false;
    }

    $style = rodolfo_fn_post_style( $post->ID );
    $notes = array();

    foreach ( $ids as $i => $hit ) {
        $text = $hit[2];
        if ( 0 === strpos( $text, 'ref:' ) ) {
            $ref  = (int) substr( $text, 4 );
            $text = ( $ref >= 1 && isset( $ids[ $ref - 1 ] ) && 0 !== strpos( $ids[ $ref - 1 ][2], 'ref:' ) ) ? $ids[ $ref - 1 ][2] : '';
        }
        $ids[ $i ]['text'] = $text;

        $use = null;
        if ( $s['combine'] ) {
            foreach ( $notes as $j => $note ) {
                if ( $note['text'] === $text ) {
                    $use                    = $j;
                    $notes[ $j ]['ids'][]   = $i;
                    break;
                }
            }
        }
        if ( null === $use ) {
            $use             = count( $notes );
            $notes[ $use ]   = array( 'text' => $text, 'ids' => array( $i ) );
        }
        $ids[ $i ]['use'] = $use;
    }

    $total = count( $notes );
    $base  = $full_link ? get_permalink( $post->ID ) : '';

    foreach ( $ids as $i => $hit ) {
        $num   = $hit['use'] + $start_number;
        $label = rodolfo_fn_label( $num, $style, $total + $start_number );
        $title = trim( html_entity_decode( wp_strip_all_tags( $hit['text'] ), ENT_QUOTES, 'UTF-8' ) );
        $link  = '<a href="' . esc_url( $base . '#fn-' . $num . '-' . $post->ID ) . '" id="fnref-' . ( $i + 1 ) . '-' . $post->ID . '" class="' . esc_attr( rodolfo_fn_class( 'note-ref' ) ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $label ) . '</a>';
        $repl  = $display ? ( $s['sup'] ? '<sup>' . $link . '</sup>' : $link ) : '';
        $pos   = strpos( $data, $hit[0] );
        if ( false !== $pos ) {
            $data = substr_replace( $data, $repl, $pos, strlen( $hit[0] ) );
        }
    }

    if ( $display ) {
        $out = '';
        if ( '' !== $s['heading'] ) {
            $out .= '<h2 class="' . esc_attr( rodolfo_fn_class( 'notes-title' ) ) . '">' . esc_html( $s['heading'] ) . '</h2>';
        }
        $out .= '<ol' . ( 1 !== $start_number ? ' start="' . $start_number . '"' : '' ) . ' class="' . esc_attr( rodolfo_fn_class( 'notes' ) ) . '" style="list-style-type:' . esc_attr( $style ) . '">';
        foreach ( $notes as $k => $note ) {
            $back = '';
            if ( ! is_feed() ) {
                foreach ( $note['ids'] as $ref_index ) {
                    $back .= ' <a href="' . esc_url( $base . '#fnref-' . ( $ref_index + 1 ) . '-' . $post->ID ) . '" class="' . esc_attr( rodolfo_fn_class( 'note-back' ) ) . '" aria-label="' . esc_attr__( 'Back to the text', 'rodolfo' ) . '">' . esc_html( $s['back'] ) . '</a>';
                }
            }
            $body = wp_kses_post( $note['text'] );
            $out .= '<li id="fn-' . ( $k + $start_number ) . '-' . $post->ID . '" class="' . esc_attr( rodolfo_fn_class( 'note' ) ) . '">' . ( 'start' === $s['back_pos'] ? ltrim( $back ) . ' ' . $body : $body . $back ) . '</li>';
        }
        $out .= '</ol>';
        $data .= $out;
    }

    return rodolfo_fn_restore( $data, $store );
}
add_filter( 'the_content', 'rodolfo_fn_process', 11 );

/** Markers must not leak into the SEO description or manual excerpts. */
add_filter( 'get_the_excerpt', function ( $excerpt ) {
    return rodolfo_fn_strip( $excerpt );
}, 5 );

// ---------------------------------------------------------------- editing UI

function rodolfo_fn_box() {
    add_meta_box( 'tk-footnotes', __( 'Footnotes', 'rodolfo' ), 'rodolfo_fn_box_render', array( 'post', 'page' ), 'side', 'low' );
}
add_action( 'add_meta_boxes', 'rodolfo_fn_box' );

function rodolfo_fn_box_render( $post ) {
    wp_nonce_field( 'rodolfo_fn_save', 'rodolfo_fn_nonce' );
    $s       = rodolfo_fn_settings();
    $current = (string) get_post_meta( $post->ID, RODOLFO_FN_STYLE_META, true );
    echo '<p><label for="rodolfo_fn_style_field">' . esc_html__( 'List style for this post', 'rodolfo' ) . '</label><br /><select id="rodolfo_fn_style_field" name="rodolfo_fn_style_field" class="widefat"><option value="">' . esc_html__( 'Site default', 'rodolfo' ) . '</option>';
    foreach ( rodolfo_fn_styles() as $key => $label ) {
        echo '<option value="' . esc_attr( $key ) . '"' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select></p>';
    /* translators: 1: opening delimiter, 2: closing delimiter */
    echo '<p class="description">' . esc_html( sprintf( __( 'Write a note as %1$sthe note text%2$s inside the post.', 'rodolfo' ), $s['open'], $s['close'] ) ) . '</p>';
}

function rodolfo_fn_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_fn_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rodolfo_fn_nonce'] ), 'rodolfo_fn_save' ) ) {
        return;
    }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $value = isset( $_POST['rodolfo_fn_style_field'] ) ? sanitize_key( wp_unslash( $_POST['rodolfo_fn_style_field'] ) ) : '';
    if ( isset( rodolfo_fn_styles()[ $value ] ) ) {
        update_post_meta( $post_id, RODOLFO_FN_STYLE_META, $value );
    } else {
        delete_post_meta( $post_id, RODOLFO_FN_STYLE_META );
        delete_post_meta( $post_id, 'footnote_style' );
    }
}
add_action( 'save_post', 'rodolfo_fn_save' );
