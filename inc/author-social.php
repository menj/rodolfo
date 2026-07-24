<?php
/**
 * Per-user social contact fields.
 *
 * Rodolfo has a Social Links tab for the *site's* accounts (footer icons).
 * That does not fit multi-author sites — every author writes under those same
 * icons. This adds a small set of per-user fields on the profile screen so the
 * author card on single posts can carry the writer's own links.
 *
 * Uses WordPress's native user_contactmethods filter rather than a custom
 * meta box. The stored keys go straight into user_meta, so any plugin or
 * theme reading get_the_author_meta('twitter') works out of the box.
 *
 * Scope is deliberately narrow — the six most likely to be filled. Sites that
 * want more can add them via the same filter.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add fields to the profile screen. Labels intentionally match the platform
 * names so authors know exactly what to paste.
 */
function rodolfo_user_contact_fields( $methods ) {
    $methods['twitter']   = __( 'X / Twitter (URL)', 'rodolfo' );
    $methods['github']    = __( 'GitHub (URL)', 'rodolfo' );
    $methods['linkedin']  = __( 'LinkedIn (URL)', 'rodolfo' );
    $methods['mastodon']  = __( 'Mastodon (URL)', 'rodolfo' );
    $methods['bluesky']   = __( 'Bluesky (URL)', 'rodolfo' );
    $methods['website']   = __( 'Personal website (URL)', 'rodolfo' );
    return $methods;
}
add_filter( 'user_contactmethods', 'rodolfo_user_contact_fields' );

/**
 * The links to render on an author card, in a stable order.
 *
 * @return array List of { key, label, url, icon } for author's populated fields.
 */
function rodolfo_author_social_links( $user_id = null ) {
    $user_id = $user_id ? (int) $user_id : (int) get_the_author_meta( 'ID' );
    if ( ! $user_id ) {
        return array();
    }

    $catalog = array(
        'website'  => __( 'Website', 'rodolfo' ),
        'twitter'  => 'X / Twitter',
        'github'   => 'GitHub',
        'linkedin' => 'LinkedIn',
        'mastodon' => 'Mastodon',
        'bluesky'  => 'Bluesky',
    );

    // WordPress's own user_url field is a natural website slot too — prefer
    // the explicit 'website' contact field, fall back to user_url.
    $links = array();
    foreach ( $catalog as $key => $label ) {
        $url = trim( (string) get_the_author_meta( $key, $user_id ) );
        if ( '' === $url && 'website' === $key ) {
            $url = trim( (string) get_the_author_meta( 'user_url', $user_id ) );
        }
        if ( '' === $url ) {
            continue;
        }
        // Normalise: allow "twitter.com/name" without a scheme.
        if ( ! preg_match( '#^https?://#i', $url ) ) {
            $url = 'https://' . ltrim( $url, '/' );
        }
        $links[] = array(
            'key'   => $key,
            'label' => $label,
            'url'   => esc_url_raw( $url ),
            'icon'  => ( 'website' === $key ) ? 'link' : $key,
        );
    }

    /** Filter the resolved author social links. */
    return apply_filters( 'rodolfo_author_social_links', $links, $user_id );
}
