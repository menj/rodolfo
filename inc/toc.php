<?php
/**
 * Table of contents.
 *
 * On long-form single posts (3+ h2/h3 headings) this injects stable anchor
 * IDs into the headings during the_content and exposes a generated TOC via
 * rodolfo_the_toc(), which the single template renders above the article body.
 * Short posts get nothing — no empty box.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Minimum number of h2/h3 headings before a TOC is shown.
 */
function rodolfo_toc_min_headings() {
    return (int) apply_filters( 'rodolfo_toc_min_headings', 3 );
}

/**
 * Parse post content: add IDs to h2/h3 and stash the TOC markup for the
 * template. Runs before wpautop / shortcodes so heading markup is still clean.
 */
function rodolfo_toc_process( $content ) {
    unset( $GLOBALS['rodolfo_toc_html'] );

    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    if ( ! preg_match_all( '/<h([23])\b[^>]*>.*?<\/h\1>/is', $content, $probe ) ) {
        return $content;
    }
    if ( count( $probe[0] ) < rodolfo_toc_min_headings() ) {
        return $content;
    }

    $items = array();
    $used  = array();

    $content = preg_replace_callback(
        '/<h([23])(\b[^>]*)>(.*?)<\/h\1>/is',
        function ( $m ) use ( &$items, &$used ) {
            $level = (int) $m[1];
            $attrs = $m[2];
            $inner = $m[3];
            $text  = trim( wp_strip_all_tags( $inner ) );
            if ( '' === $text ) {
                return $m[0];
            }

            if ( preg_match( '/\sid=["\']([^"\']+)["\']/', $attrs, $idm ) ) {
                $id = $idm[1];
            } else {
                $base = sanitize_title( $text );
                if ( '' === $base ) {
                    $base = 'section';
                }
                $id = $base;
                $n  = 2;
                while ( in_array( $id, $used, true ) ) {
                    $id = $base . '-' . $n;
                    $n++;
                }
                $attrs .= ' id="' . esc_attr( $id ) . '"';
            }
            $used[]  = $id;
            $items[] = array( 'level' => $level, 'id' => $id, 'text' => $text );

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $content
    );

    if ( count( $items ) < rodolfo_toc_min_headings() ) {
        return $content;
    }

    // Build the TOC markup once, store for the template.
    $li = '';
    foreach ( $items as $it ) {
        $indent = 3 === $it['level'] ? ' style="padding-left:1rem"' : '';
        $li    .= sprintf(
            '<li%s><a href="#%s" class="rodolfo-toc-link block py-1 text-sm text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">%s</a></li>',
            $indent,
            esc_attr( $it['id'] ),
            esc_html( $it['text'] )
        );
    }

    // The heading doubles as a disclosure button on small screens (main.js
    // wires it; CSS collapses the list below md). Desktop stays always-open —
    // the button is display-inert there, so no behaviour changes.
    $GLOBALS['rodolfo_toc_html'] = sprintf(
        '<nav class="rodolfo-toc mb-10 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 p-5 md:p-6" aria-label="%s">'
            . '<h2 class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-3">'
            . '<button type="button" class="rodolfo-toc-toggle" aria-expanded="false" aria-controls="rodolfo-toc-list">%s</button>'
            . '</h2>'
            . '<ol id="rodolfo-toc-list" class="rodolfo-toc-list space-y-0.5 list-none pl-0 m-0">%s</ol>'
            . '</nav>',
        esc_attr__( 'Table of contents', 'rodolfo' ),
        esc_html__( 'On this page', 'rodolfo' ),
        $li
    );

    return $content;
}
add_filter( 'the_content', 'rodolfo_toc_process', 7 );

/**
 * Echo the generated TOC, if one exists for the current post.
 * Safe to call unconditionally in the single template.
 */
function rodolfo_the_toc() {
    if ( empty( $GLOBALS['rodolfo_toc_html'] ) ) {
        return;
    }
    // Markup assembled above from escaped parts.
    echo $GLOBALS['rodolfo_toc_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
