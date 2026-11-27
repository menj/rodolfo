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
    return '<div class="rodolfo-answer">'
        . '<span class="rodolfo-answer-label">' . esc_html( $atts['label'] ) . '</span>'
        . '<div class="rodolfo-answer-body">' . wp_kses_post( wpautop( do_shortcode( $content ) ) ) . '</div>'
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
    $out = '<section class="rodolfo-faqs">';
    if ( '' !== $atts['title'] ) {
        $out .= '<h2 class="rodolfo-faqs-title">' . esc_html( $atts['title'] ) . '</h2>';
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
    // Render nested shortcodes once: they may be expensive or have side effects.
    $rendered_answer = wp_kses_post( wpautop( do_shortcode( $answer ) ) );
    rodolfo_faq_items( array(
        'q' => wp_strip_all_tags( $question ),
        'a' => wp_strip_all_tags( $rendered_answer ),
    ) );
    return '<details class="rodolfo-faq"' . ( 'yes' === $atts['open'] ? ' open' : '' ) . '>'
        . '<summary class="rodolfo-faq-q">' . esc_html( $question ) . '</summary>'
        . '<div class="rodolfo-faq-a">' . $rendered_answer . '</div>'
        . '</details>';
}
add_shortcode( 'faq', 'rodolfo_faq_shortcode' );

/** Emit FAQPage JSON-LD for the Q/As actually rendered on this page. */
function rodolfo_faq_jsonld() {
    if ( ! is_singular() ) {
        return;
    }
    $post = get_queried_object();
    if ( ! $post || 'publish' !== $post->post_status || ! empty( $post->post_password ) || ( function_exists( 'rodolfo_is_coming_soon' ) && rodolfo_is_coming_soon( $post ) ) ) {
        return;
    }
    $items = rodolfo_faq_items();
    if ( empty( $items ) ) {
        return;
    }
    /**
     * FAQPage JSON-LD stays on even when an SEO plugin is active, because it
     * derives from Rodolfo's own [faq] shortcodes, which the plugin doesn't
     * know about. If a page mixes [faq] with e.g. Rank Math's FAQ block,
     * suppress ours: add_filter( 'rodolfo_faq_jsonld', '__return_false' );
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
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
    $out = '<aside class="rodolfo-proof">'
        . '<span class="rodolfo-proof-metric">' . esc_html( $atts['metric'] ) . '</span>';
    if ( '' !== $atts['label'] ) {
        $out .= '<span class="rodolfo-proof-label">' . esc_html( $atts['label'] ) . '</span>';
    }
    if ( null !== $content && '' !== trim( $content ) ) {
        $out .= '<div class="rodolfo-proof-body">' . wp_kses_post( wpautop( do_shortcode( $content ) ) ) . '</div>';
    }
    if ( '' !== $atts['cite'] ) {
        $out .= '<a class="rodolfo-proof-cite" href="' . esc_url( $atts['cite'] ) . '">' . esc_html( $atts['cite_label'] ) . ' &rarr;</a>';
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

    $out = '<figure class="rodolfo-compare"><table>';
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
