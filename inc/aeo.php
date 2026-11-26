<?php
/**
 * AEO (AI answer engine optimisation) content blocks.
 *
 * Gives authors the on-page structures AI Overviews/chatbots actually cite,
 * without a plugin:
 *
 *   [answer]…[/answer]                       — "Quick answer" box: a 2-3 sentence
 *       direct answer placed at the top of the post, before specs/details.
 *   [faqs]                                   — FAQ group; each item:
 *     [faq q="Real objection?"]Direct answer.[/faq]
 *   [/faqs]                                  — renders accessible <details> items
 *       AND emits FAQPage JSON-LD built from the actual on-page Q/As.
 *   [howto name="" time="PT10M"] [step name="" image=""]Text[/step] [/howto]
 *                                            — numbered instructions + HowTo JSON-LD.
 *   [compare a="This" b="Alternative" caption=""]
 *     Factor | value for A | value for B     — one row per line
 *   [/compare]                               — honest 3-4 factor comparison table.
 *
 * The FAQPage JSON-LD derives from shortcode content the author wrote, so it
 * never duplicates an SEO plugin's site-level schema; it prints in wp_footer
 * only on pages where [faq] was rendered.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------- Quick answer ----------

function rodolfo_answer_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'label' => __( 'Quick answer', 'rodolfo' ),
    ), $atts, 'answer' );
    if ( null === $content || '' === trim( $content ) ) {
        return '';
    }
    return '<div class="quick-answer">'
        . '<span class="quick-answer-label">' . esc_html( $atts['label'] ) . '</span>'
        . '<div class="quick-answer-body">' . wp_kses_post( wpautop( do_shortcode( $content ) ) ) . '</div>'
        . '</div>';
}
add_shortcode( 'answer', 'rodolfo_answer_shortcode' );

// ---------- FAQ group + FAQPage JSON-LD ----------

/** Q/A pairs rendered on the current request, for the JSON-LD emitter. */
function rodolfo_faq_items( $add = null ) {
    static $items = array();
    if ( null !== $add ) {
        $items[] = $add;
    }
    return $items;
}

function rodolfo_faqs_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'title' => '',
    ), $atts, 'faqs' );
    $out = '<section class="faq-block">';
    if ( '' !== $atts['title'] ) {
        $out .= '<h2 class="faq-block-title">' . esc_html( $atts['title'] ) . '</h2>';
    }
    $out .= do_shortcode( (string) $content ) . '</section>';
    return $out;
}
add_shortcode( 'faqs', 'rodolfo_faqs_shortcode' );

function rodolfo_faq_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'q'    => '',
        'open' => 'no',
    ), $atts, 'faq' );
    $question = trim( (string) $atts['q'] );
    $answer   = trim( (string) $content );
    if ( '' === $question || '' === $answer ) {
        return '';
    }
    rodolfo_faq_items( array(
        'q' => $question,
        'a' => wp_strip_all_tags( do_shortcode( $answer ) ),
    ) );
    return '<details class="faq-item"' . ( 'yes' === $atts['open'] ? ' open' : '' ) . '>'
        . '<summary class="faq-question">' . esc_html( $question ) . '</summary>'
        . '<div class="faq-answer">' . wp_kses_post( wpautop( do_shortcode( $answer ) ) ) . '</div>'
        . '</details>';
}
add_shortcode( 'faq', 'rodolfo_faq_shortcode' );

/** Emit FAQPage JSON-LD for the Q/As actually rendered on this page. */
function rodolfo_faq_jsonld() {
    $items = rodolfo_faq_items();
    if ( empty( $items ) ) {
        return;
    }
    // inc/schema.php already put these Q/As in the @graph.
    if ( function_exists( 'rodolfo_schema_state' ) && rodolfo_schema_state( 'faq' ) ) {
        return;
    }
    /**
     * FAQPage JSON-LD stays on even when an SEO plugin is active, because it
     * derives from the theme's own [faq] shortcodes, which the plugin doesn't
     * know about. If a page mixes [faq] with e.g. Rank Math's FAQ block,
     * suppress it: add_filter( 'rodolfo_faq_jsonld', '__return_false' );
     */
    if ( ! apply_filters( 'rodolfo_faq_jsonld', true ) ) {
        return;
    }
    $entities = array();
    foreach ( $items as $item ) {
        $entities[] = array(
            '@type'          => 'Question',
            'name'           => $item['q'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $item['a'],
            ),
        );
    }
    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'rodolfo_faq_jsonld' );

// ---------- Usage proof ----------

/**
 * [proof metric="20 min" label="setup time, down from 3 hours" cite="" cite_label=""]
 * Optional body text.[/proof]
 * A verifiable-result callout for the first 200 words: a big exact number, what
 * it measures, and an optional link to the case study/review backing it.
 */
function rodolfo_proof_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'metric'     => '',
        'label'      => '',
        'cite'       => '',
        'cite_label' => __( 'See the case study', 'rodolfo' ),
    ), $atts, 'proof' );
    if ( '' === trim( $atts['metric'] ) ) {
        return '';
    }
    $out = '<aside class="proof-callout">'
        . '<span class="proof-callout-metric">' . esc_html( $atts['metric'] ) . '</span>';
    if ( '' !== $atts['label'] ) {
        $out .= '<span class="proof-callout-label">' . esc_html( $atts['label'] ) . '</span>';
    }
    if ( null !== $content && '' !== trim( $content ) ) {
        $out .= '<div class="proof-callout-body">' . wp_kses_post( wpautop( do_shortcode( $content ) ) ) . '</div>';
    }
    if ( '' !== $atts['cite'] ) {
        $out .= '<a class="proof-callout-cite" href="' . esc_url( $atts['cite'] ) . '">' . esc_html( $atts['cite_label'] ) . ' &rarr;</a>';
    }
    return $out . '</aside>';
}
add_shortcode( 'proof', 'rodolfo_proof_shortcode' );

// ---------- Comparison table ----------

function rodolfo_compare_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'a'       => __( 'This', 'rodolfo' ),
        'b'       => __( 'Alternative', 'rodolfo' ),
        'caption' => '',
    ), $atts, 'compare' );

    $rows  = array();
    $lines = preg_split( '/\r\n|\r|\n/', wp_strip_all_tags( (string) $content ) );
    foreach ( $lines as $line ) {
        $cells = array_map( 'trim', explode( '|', $line ) );
        if ( count( $cells ) >= 3 && '' !== $cells[0] ) {
            $rows[] = array_slice( $cells, 0, 3 );
        }
    }
    if ( empty( $rows ) ) {
        return '';
    }

    $out = '<figure class="comparison-table"><table>';
    if ( '' !== $atts['caption'] ) {
        $out .= '<caption>' . esc_html( $atts['caption'] ) . '</caption>';
    }
    $out .= '<thead><tr><th scope="col">' . esc_html__( 'Factor', 'rodolfo' ) . '</th>'
        . '<th scope="col">' . esc_html( $atts['a'] ) . '</th>'
        . '<th scope="col">' . esc_html( $atts['b'] ) . '</th></tr></thead><tbody>';
    foreach ( $rows as $row ) {
        $out .= '<tr><th scope="row">' . esc_html( $row[0] ) . '</th>'
            . '<td>' . esc_html( $row[1] ) . '</td>'
            . '<td>' . esc_html( $row[2] ) . '</td></tr>';
    }
    $out .= '</tbody></table></figure>';
    return $out;
}
add_shortcode( 'compare', 'rodolfo_compare_shortcode' );

// ---------- HowTo ----------

function rodolfo_howto_steps( $add = null ) {
    static $steps = array();
    if ( 'reset' === $add ) {
        $steps = array();
        return $steps;
    }
    if ( null !== $add ) {
        $steps[] = $add;
    }
    return $steps;
}

/**
 * [howto name="Replace a tap washer" time="PT20M" description=""]
 *   [step name="Shut off the water" image="https://…/1.jpg"]Turn the valve…[/step]
 * [/howto]
 */
function rodolfo_howto_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array( 'name' => '', 'time' => '', 'description' => '' ), $atts, 'howto' );
    rodolfo_howto_steps( 'reset' );
    $body = do_shortcode( (string) $content );
    $out  = '<section class="howto-block">';
    if ( '' !== $atts['name'] ) {
        $out .= '<h2 class="howto-title">' . esc_html( $atts['name'] ) . '</h2>';
    }
    if ( '' !== $atts['description'] ) {
        $out .= '<p class="howto-description">' . esc_html( $atts['description'] ) . '</p>';
    }
    $out .= '<ol class="howto-steps">' . $body . '</ol></section>';
    return $out;
}
add_shortcode( 'howto', 'rodolfo_howto_shortcode' );

function rodolfo_howto_step_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array( 'name' => '', 'image' => '' ), $atts, 'step' );
    $text = trim( (string) $content );
    if ( '' === $text && '' === $atts['name'] ) {
        return '';
    }
    $n = count( rodolfo_howto_steps() ) + 1;
    rodolfo_howto_steps( array( 'name' => $atts['name'], 'text' => wp_strip_all_tags( do_shortcode( $text ) ), 'image' => $atts['image'] ) );
    $out = '<li class="howto-step" id="step-' . (int) $n . '">';
    if ( '' !== $atts['name'] ) {
        $out .= '<h3 class="howto-step-name">' . esc_html( $atts['name'] ) . '</h3>';
    }
    if ( '' !== $atts['image'] ) {
        $out .= '<img src="' . esc_url( $atts['image'] ) . '" alt="' . esc_attr( $atts['name'] ) . '" loading="lazy" decoding="async" />';
    }
    return $out . wp_kses_post( wpautop( do_shortcode( $text ) ) ) . '</li>';
}
add_shortcode( 'step', 'rodolfo_howto_step_shortcode' );

/** Stand-alone HowTo JSON-LD, used only when the unified graph is not running (SEO plugin active). */
function rodolfo_howto_jsonld() {
    static $done = false;
    $steps = rodolfo_howto_steps();
    if ( $done || empty( $steps ) || ( function_exists( 'rodolfo_schema_state' ) && rodolfo_schema_state( 'howto' ) ) ) {
        return;
    }
    $done = true;
    $list = array();
    foreach ( $steps as $i => $st ) {
        $list[] = array_filter( array(
            '@type'    => 'HowToStep',
            'position' => $i + 1,
            'name'     => $st['name'] ? $st['name'] : wp_trim_words( $st['text'], 8, '' ),
            'text'     => $st['text'],
            'image'    => $st['image'] ? $st['image'] : null,
        ) );
    }
    $schema = array( '@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => wp_strip_all_tags( get_the_title() ), 'step' => $list );
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>' . "\n";
}
add_action( 'wp_footer', 'rodolfo_howto_jsonld' );

// ---------- Read the blocks out of stored post content ----------

/**
 * Parse the AEO shortcodes straight from post content. The JSON-LD graph prints
 * in <head>, before the body (and its shortcodes) render, so it cannot wait for
 * the render-time collectors above.
 *
 * @return array answer (string), faqs (q/a list), howto (array|null),
 *               compare (list of a/b/caption), proof (list of metric/label/cite)
 */
function rodolfo_aeo_extract( $content ) {
    $out = array( 'answer' => '', 'faqs' => array(), 'howto' => null, 'compare' => array(), 'proof' => array() );
    $content = (string) $content;

    if ( preg_match( '/\[answer\b[^\]]*\](.*?)\[\/answer\]/is', $content, $m ) ) {
        $out['answer'] = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $m[1] ) ) ) );
    }

    if ( preg_match_all( '/\[faq\b([^\]]*)\](.*?)\[\/faq\]/is', $content, $fm, PREG_SET_ORDER ) ) {
        foreach ( $fm as $f ) {
            $a = shortcode_parse_atts( $f[1] );
            $q = isset( $a['q'] ) ? trim( $a['q'] ) : '';
            $t = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $f[2] ) ) ) );
            if ( '' !== $q && '' !== $t ) {
                $out['faqs'][] = array( 'q' => $q, 'a' => $t );
            }
        }
    }

    if ( preg_match( '/\[howto\b([^\]]*)\](.*?)\[\/howto\]/is', $content, $hm ) ) {
        $a     = shortcode_parse_atts( $hm[1] );
        $steps = array();
        if ( preg_match_all( '/\[step\b([^\]]*)\](.*?)\[\/step\]/is', $hm[2], $sm, PREG_SET_ORDER ) ) {
            foreach ( $sm as $s ) {
                $sa   = shortcode_parse_atts( $s[1] );
                $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $s[2] ) ) ) );
                $nm   = isset( $sa['name'] ) ? trim( $sa['name'] ) : '';
                if ( '' === $nm && '' === $text ) {
                    continue;
                }
                $steps[] = array(
                    'name'  => '' !== $nm ? $nm : wp_trim_words( $text, 8, '' ),
                    'text'  => $text,
                    'image' => isset( $sa['image'] ) ? esc_url_raw( $sa['image'] ) : '',
                );
            }
        }
        if ( $steps ) {
            $out['howto'] = array(
                'name'        => isset( $a['name'] ) ? trim( $a['name'] ) : '',
                'time'        => ( isset( $a['time'] ) && preg_match( '/^P(T?\d+[HMS])+$/', $a['time'] ) ) ? $a['time'] : '',
                'description' => isset( $a['description'] ) ? trim( $a['description'] ) : '',
                'steps'       => $steps,
            );
        }
    }

    if ( preg_match_all( '/\[compare\b([^\]]*)\]/i', $content, $cm ) ) {
        foreach ( $cm[1] as $attr ) {
            $a = shortcode_parse_atts( $attr );
            $out['compare'][] = array(
                'a'       => isset( $a['a'] ) ? $a['a'] : __( 'This', 'rodolfo' ),
                'b'       => isset( $a['b'] ) ? $a['b'] : __( 'Alternative', 'rodolfo' ),
                'caption' => isset( $a['caption'] ) ? $a['caption'] : '',
            );
        }
    }

    if ( preg_match_all( '/\[proof\b([^\]]*)\]/i', $content, $pm ) ) {
        foreach ( $pm[1] as $attr ) {
            $a = shortcode_parse_atts( $attr );
            if ( ! empty( $a['metric'] ) ) {
                $out['proof'][] = array(
                    'metric' => $a['metric'],
                    'label'  => isset( $a['label'] ) ? $a['label'] : '',
                    'cite'   => isset( $a['cite'] ) ? $a['cite'] : '',
                );
            }
        }
    }
    return $out;
}
