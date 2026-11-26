<?php
/**
 * Consent-gated embeds (off by default).
 *
 * Replaces iframe-based oEmbeds (YouTube, Vimeo, SoundCloud, Spotify) with a
 * click-to-load facade, so the reader's browser contacts NO third party until
 * they press play. The facade is deliberately a generic placeholder — the
 * provider thumbnail would itself be a third-party request, which defeats the
 * point. Same posture as the self-hosted fonts and llms.txt: nothing leaves
 * the reader's machine without a reason they chose.
 *
 * Scope: iframe embeds only. Script-based embeds (e.g. X/Twitter, which
 * render a blockquote plus widgets.js) pass through ungated — stripping and
 * re-executing third-party scripts safely is a different problem, documented
 * here so nobody mistakes the omission for an oversight.
 *
 * The click handler and the "always allow" memory (localStorage, same posture
 * as the dark-mode toggle) live in assets/js/main.js; styles in style.css.
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Providers gated by this module: host fragment => human-readable label.
 * Filterable so a site can gate more iframe providers (or fewer).
 */
function rodolfo_privacy_embed_providers() {
    return apply_filters( 'rodolfo_privacy_embed_providers', array(
        'youtube.com'         => 'YouTube',
        'youtube-nocookie.com' => 'YouTube',
        'youtu.be'            => 'YouTube',
        'player.vimeo.com'    => 'Vimeo',
        'vimeo.com'           => 'Vimeo',
        'soundcloud.com'      => 'SoundCloud',
        'open.spotify.com'    => 'Spotify',
    ) );
}

/**
 * Swap a gateable oEmbed for the consent facade.
 *
 * @param string $html The oEmbed HTML.
 * @param string $url  The original URL being embedded.
 * @return string Facade markup, or the untouched HTML when not gateable.
 */
function rodolfo_privacy_embed_gate( $html, $url = '' ) {
    if ( ! get_option( 'rodolfo_privacy_embeds' ) || is_admin() || is_feed() ) {
        return $html;
    }

    // Only iframe embeds are gateable; anything else passes through.
    if ( ! preg_match( '/<iframe[^>]*\ssrc=["\']([^"\']+)["\'][^>]*>/i', $html, $m ) ) {
        return $html;
    }
    $src  = $m[1];
    $host = (string) wp_parse_url( $src, PHP_URL_HOST );

    $provider = '';
    foreach ( rodolfo_privacy_embed_providers() as $fragment => $label ) {
        if ( $host === $fragment || str_ends_with( $host, '.' . $fragment ) ) {
            $provider = $label;
            break;
        }
    }
    if ( '' === $provider ) {
        return $html;
    }

    // Carry the attributes an iframe needs to work once loaded.
    $allow = preg_match( '/\sallow=["\']([^"\']*)["\']/i', $html, $a ) ? $a[1] : '';
    $title = preg_match( '/\stitle=["\']([^"\']*)["\']/i', $html, $t ) ? $t[1] : '';

    ob_start();
    ?>
    <div class="tk-embed-gate"
         data-embed-src="<?php echo esc_url( $src ); ?>"
         data-embed-provider="<?php echo esc_attr( $provider ); ?>"
         data-embed-allow="<?php echo esc_attr( $allow ); ?>"
         data-embed-title="<?php echo esc_attr( $title ); ?>">
        <button type="button" class="tk-embed-gate-btn">
            <span class="tk-embed-gate-play" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" focusable="false"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
            </span>
            <span class="tk-embed-gate-label">
                <?php
                /* translators: %s: embed provider name, e.g. YouTube */
                printf( esc_html__( 'Load embed from %s', 'rodolfo' ), esc_html( $provider ) );
                if ( '' !== $title ) {
                    echo '<small>' . esc_html( $title ) . '</small>';
                }
                ?>
            </span>
        </button>
        <p class="tk-embed-gate-note">
            <?php
            /* translators: %s: embed provider name */
            printf( esc_html__( 'Nothing is loaded from %s until you press play.', 'rodolfo' ), esc_html( $provider ) );
            ?>
            <label class="tk-embed-gate-remember">
                <input type="checkbox" class="tk-embed-gate-always" />
                <?php
                /* translators: %s: embed provider name */
                printf( esc_html__( 'Always load %s embeds', 'rodolfo' ), esc_html( $provider ) );
                ?>
            </label>
        </p>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_filter( 'embed_oembed_html', 'rodolfo_privacy_embed_gate', 20, 2 );
