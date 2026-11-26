<?php
/**
 * Donation button/prompt (native theme surface).
 *
 * IMPORTANT — scope: this is the PRESENTATIONAL donation surface only. The
 * button links to PayPal's own hosted donation flow, so no card data, tokens,
 * or webhooks are ever handled by the theme — PayPal does the processing on its
 * side. Reimplementing a full gateway (orders, IPN/webhooks, PCI-sensitive
 * handling) still belongs in a dedicated plugin.
 *
 * Two ways to configure, in priority order:
 *   1. rodolfo_donate_url    — an explicit URL (PayPal.me, a Stripe payment
 *                              link, a donation page, etc.). Wins if set.
 *   2. rodolfo_paypal_email  — just a PayPal account email; the module builds
 *                              the hosted PayPal donation URL automatically.
 * Also:
 *   rodolfo_donate_currency  — 3-letter code for the PayPal flow (default USD)
 *   rodolfo_donate_label     — button text (default "Donate")
 *
 * Shortcode: [donate url="…" email="…" label="…" currency="…"]  (all optional)
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'donate', 'rodolfo_donate_shortcode' );

/**
 * Build PayPal's hosted donation URL from an account email.
 *
 * Uses the current /donate/ endpoint with business + item name + currency. The
 * email is validated; an invalid/empty one yields '' so no dead link renders.
 */
function rodolfo_paypal_donate_url( $email, $currency = 'USD' ) {
    $email = sanitize_email( (string) $email );
    if ( ! is_email( $email ) ) {
        return '';
    }
    $currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) $currency ) );
    if ( 3 !== strlen( $currency ) ) {
        $currency = 'USD';
    }
    return add_query_arg(
        array(
            'business'      => $email,
            'no_recurring'  => '0',
            'item_name'     => rawurlencode( sprintf(
                /* translators: %s: site name */
                __( 'Support %s', 'rodolfo' ),
                get_bloginfo( 'name' )
            ) ),
            'currency_code' => $currency,
        ),
        'https://www.paypal.com/donate/'
    );
}

/**
 * Resolve the button's destination: explicit URL first, else a PayPal email.
 * Returns '' when nothing is configured.
 */
function rodolfo_donate_url( $url_override = '', $email_override = '', $currency_override = '' ) {
    $url = $url_override !== '' ? $url_override : (string) get_option( 'rodolfo_donate_url', '' );
    if ( '' !== trim( $url ) ) {
        return esc_url_raw( $url );
    }
    $email    = $email_override !== '' ? $email_override : (string) get_option( 'rodolfo_paypal_email', '' );
    $currency = $currency_override !== '' ? $currency_override : (string) get_option( 'rodolfo_donate_currency', 'USD' );
    return rodolfo_paypal_donate_url( $email, $currency );
}

/**
 * Render the donate button markup from resolved settings, or '' when nothing
 * is configured. Shared by the shortcode and the automatic-placement filter so
 * the URL-resolution and escaping logic lives in one place.
 */
function rodolfo_donate_button( $label = '' ) {
    $url = esc_url( rodolfo_donate_url() );
    if ( '' === $url ) {
        return '';
    }
    if ( '' === $label ) {
        $label = (string) get_option( 'rodolfo_donate_label', '' );
    }
    if ( '' === $label ) {
        $label = __( 'Donate', 'rodolfo' );
    }
    return sprintf(
        '<a class="tk-donate" href="%1$s" target="_blank" rel="noopener">%2$s%3$s</a>',
        $url,
        rodolfo_icon( 'heart', 'tk-donate-icon' ),
        esc_html( $label )
    );
}

/**
 * Automatic placement: append the donate button to the end of the content on
 * singular views when `rodolfo_donate_auto` is set and a destination exists.
 * No shortcode or block required. 'posts' = posts only; 'posts_pages' = both.
 * Skips feeds, and only runs on the main query's primary content.
 */
function rodolfo_donate_auto_append( $content ) {
    if ( is_feed() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    $mode = (string) get_option( 'rodolfo_donate_auto', '' );
    if ( '' === $mode ) {
        return $content;
    }
    if ( 'posts' === $mode && ! is_singular( 'post' ) ) {
        return $content;
    }
    if ( 'posts_pages' === $mode && ! ( is_singular( 'post' ) || is_page() ) ) {
        return $content;
    }
    $button = rodolfo_donate_button();
    if ( '' === $button ) {
        return $content;
    }
    /**
     * Filter the auto-placed donate block. Return '' to suppress on a given view.
     */
    $block = apply_filters(
        'rodolfo_donate_auto_block',
        '<p class="tk-donate-auto">' . $button . '</p>',
        $content
    );
    return $content . $block;
}
add_filter( 'the_content', 'rodolfo_donate_auto_append', 20 );

function rodolfo_donate_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'url'      => '',
        'email'    => '',
        'currency' => '',
        'label'    => get_option( 'rodolfo_donate_label', '' ),
    ), $atts, 'donate' );

    $url = esc_url( rodolfo_donate_url( $atts['url'], $atts['email'], $atts['currency'] ) );
    if ( '' === $url ) {
        return ''; // Nothing configured — render nothing rather than a dead link.
    }
    $label = $atts['label'] !== '' ? $atts['label'] : __( 'Donate', 'rodolfo' );

    return sprintf(
        '<a class="tk-donate" href="%1$s" target="_blank" rel="noopener">%2$s%3$s</a>',
        $url,
        rodolfo_icon( 'heart', 'tk-donate-icon' ),
        esc_html( $label )
    );
}
