<?php
/**
 * Comment guard (off by default): honeypot + minimum-time trap, with
 * Cloudflare Turnstile as an optional second layer.
 *
 * The zero-dependency default catches drive-by comment bots: a visually
 * hidden field that humans never fill (bots auto-fill everything) and a
 * signed render timestamp that rejects submissions faster than any human
 * types (bots post in milliseconds). Neither adds a request, a cookie, or a
 * byte of third-party JS.
 *
 * Turnstile is opt-in via a site key + secret. Its script is NOT enqueued on
 * page load — main.js injects it only when the comment field gains focus, so
 * a reader who never comments never contacts Cloudflare. Server-side, the
 * token is verified against siteverify; the theme stores nothing but the two
 * keys. If the secret is unset, no verification request is ever made.
 *
 * Page caches are safe: the time trap only enforces a MINIMUM age, so a
 * timestamp rendered hours ago by a cache passes trivially — the trap exists
 * to catch instant submissions, not old ones.
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

/** Whether the guard is active for this request. */
function rodolfo_comment_guard_active() {
    return (bool) get_option( 'rodolfo_comment_guard' ) && ! is_user_logged_in();
}

/** Render the honeypot + signed timestamp (and the Turnstile slot) in the form. */
function rodolfo_comment_guard_fields() {
    if ( ! rodolfo_comment_guard_active() ) {
        return;
    }
    $now = time();
    ?>
    <p class="tk-hp-wrap" aria-hidden="true">
        <label for="rodolfo_hp"><?php esc_html_e( 'Leave this field empty', 'rodolfo' ); ?></label>
        <input type="text" name="rodolfo_hp" id="rodolfo_hp" value="" tabindex="-1" autocomplete="off" />
    </p>
    <input type="hidden" name="rodolfo_hp_time" value="<?php echo esc_attr( (string) $now ); ?>" />
    <input type="hidden" name="rodolfo_hp_sig" value="<?php echo esc_attr( wp_hash( $now . '|tk-comment-guard' ) ); ?>" />
    <?php
    $site_key = trim( (string) get_option( 'rodolfo_turnstile_site' ) );
    if ( '' !== $site_key && '' !== trim( (string) get_option( 'rodolfo_turnstile_secret' ) ) ) {
        // Rendered empty; main.js injects the Turnstile script on first focus.
        printf( '<div class="cf-turnstile" data-sitekey="%s"></div>', esc_attr( $site_key ) );
    }
}
add_action( 'comment_form_after_fields', 'rodolfo_comment_guard_fields' );

/** Reject a blocked comment with a polite, non-technical message. */
function rodolfo_comment_guard_reject() {
    wp_die(
        esc_html__( 'Your comment could not be posted. If you are a human seeing this, please go back, wait a moment, and try again.', 'rodolfo' ),
        esc_html__( 'Comment not posted', 'rodolfo' ),
        array(
            'response'  => 403,
            'back_link' => true,
        )
    );
}

/**
 * Gate on preprocess_comment. Runs before the comment touches the database.
 *
 * @param array $commentdata Comment data.
 * @return array Unchanged data when the submission passes.
 */
function rodolfo_comment_guard_check( $commentdata ) {
    if ( ! rodolfo_comment_guard_active() ) {
        return $commentdata;
    }
    // Only gate real reader comments — never pingbacks/trackbacks.
    $type = isset( $commentdata['comment_type'] ) ? $commentdata['comment_type'] : '';
    if ( ! in_array( $type, array( '', 'comment' ), true ) ) {
        return $commentdata;
    }

    // Honeypot: humans never see the field; bots fill everything.
    if ( ! empty( $_POST['rodolfo_hp'] ) ) {
        rodolfo_comment_guard_reject();
    }

    // Time trap: reject submissions faster than any human types. The
    // timestamp is signed so it cannot simply be rewound by the client.
    $time = isset( $_POST['rodolfo_hp_time'] ) ? (int) $_POST['rodolfo_hp_time'] : 0;
    $sig  = isset( $_POST['rodolfo_hp_sig'] ) ? (string) wp_unslash( $_POST['rodolfo_hp_sig'] ) : '';
    $min  = (int) apply_filters( 'rodolfo_comment_guard_min_seconds', 3 );
    if ( 0 === $time || ! hash_equals( wp_hash( $time . '|tk-comment-guard' ), $sig ) || ( time() - $time ) < $min ) {
        rodolfo_comment_guard_reject();
    }

    // Optional Turnstile layer — only when BOTH keys are configured.
    $secret = trim( (string) get_option( 'rodolfo_turnstile_secret' ) );
    if ( '' !== $secret && '' !== trim( (string) get_option( 'rodolfo_turnstile_site' ) ) ) {
        $token    = isset( $_POST['cf-turnstile-response'] ) ? (string) wp_unslash( $_POST['cf-turnstile-response'] ) : '';
        $verified = false;
        if ( '' !== $token ) {
            $response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
                'timeout' => 8,
                'body'    => array(
                    'secret'   => $secret,
                    'response' => $token,
                ),
            ) );
            if ( ! is_wp_error( $response ) ) {
                $body     = json_decode( (string) wp_remote_retrieve_body( $response ), true );
                $verified = is_array( $body ) && ! empty( $body['success'] );
            }
        }
        if ( ! $verified ) {
            rodolfo_comment_guard_reject();
        }
    }

    return $commentdata;
}
add_filter( 'preprocess_comment', 'rodolfo_comment_guard_check', 5 );
