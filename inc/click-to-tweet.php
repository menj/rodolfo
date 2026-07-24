<?php
/**
 * Click-to-Tweet quote blocks (natively, no plugin).
 *
 * Provides the [bctt] shortcode — compatible with the "Better Click To Tweet"
 * plugin's basic attributes — rendering a styled, accented pull-quote with an
 * "X / Twitter" intent link. Self-disables if that plugin is active so the two
 * never register the same shortcode.
 *
 * Attributes:
 *   tweet    — the quote text (defaults to the enclosed content, else post title)
 *   via      — X handle to credit (no @); defaults to the theme option
 *   url      — 'yes' (permalink, default), 'no', or an explicit URL
 *   prompt   — reader-facing call to action (default "Click to Tweet")
 *   nofollow — 'yes' to mark the link rel=nofollow
 *
 * Usage: [bctt tweet="Your quote here" via="handle"]  (or wrap: [bctt]…[/bctt])
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Stand down entirely if the Better Click To Tweet plugin owns [bctt].
if ( ! shortcode_exists( 'bctt' ) ) {
    add_action( 'init', function () {
        if ( ! shortcode_exists( 'bctt' ) ) {
            add_shortcode( 'bctt', 'rodolfo_bctt_shortcode' );
        }
    } );
}

/** The default X/Twitter handle, from Theme Options (rodolfo_twitter_handle, no @). */
function rodolfo_bctt_default_handle() {
    $handle = (string) get_option( 'rodolfo_twitter_handle', '' );
    return ltrim( trim( $handle ), '@' );
}

/**
 * Build the X intent URL. Optional UTM tags are appended to the shared link so
 * click-throughs are attributable in analytics.
 */
function rodolfo_bctt_intent_url( $text, $url, $via, $nofollow ) {
    $args = array( 'text' => $text );
    if ( $url ) {
        // Tag the shared URL for analytics (absorbs the plugin's "UTM tags" extra).
        $url = add_query_arg(
            apply_filters( 'rodolfo_bctt_utm', array(
                'utm_source'   => 'twitter',
                'utm_medium'   => 'social',
                'utm_campaign' => 'click-to-tweet',
            ) ),
            $url
        );
        $args['url'] = $url;
    }
    if ( $via ) {
        $args['via'] = $via;
    }
    return 'https://twitter.com/intent/tweet?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
}

function rodolfo_bctt_shortcode( $atts, $content = null ) {
    $atts = shortcode_atts( array(
        'tweet'    => '',
        'via'      => rodolfo_bctt_default_handle(),
        'url'      => 'yes',
        'prompt'   => __( 'Click to Tweet', 'rodolfo' ),
        'nofollow' => 'no',
    ), $atts, 'bctt' );

    // Quote text: explicit attr, else enclosed content, else the post title.
    $tweet = $atts['tweet'];
    if ( '' === trim( (string) $tweet ) && $content ) {
        $tweet = wp_strip_all_tags( $content );
    }
    if ( '' === trim( (string) $tweet ) ) {
        $tweet = get_the_title();
    }
    $tweet = trim( wp_strip_all_tags( (string) $tweet ) );
    if ( '' === $tweet ) {
        return '';
    }

    // Resolve the shared URL.
    if ( filter_var( $atts['url'], FILTER_VALIDATE_URL ) ) {
        $url = $atts['url'];
    } elseif ( 'no' === $atts['url'] ) {
        $url = '';
    } else {
        $url = get_permalink();
    }

    $via      = ltrim( trim( (string) $atts['via'] ), '@' );
    $intent   = rodolfo_bctt_intent_url( $tweet, $url, $via, $atts['nofollow'] );
    $rel       = ( 'yes' === $atts['nofollow'] ) ? 'nofollow noopener' : 'noopener';

    ob_start();
    ?>
    <figure class="rodolfo-ctt">
        <blockquote class="rodolfo-ctt-quote"><?php echo esc_html( $tweet ); ?></blockquote>
        <a class="rodolfo-ctt-action" href="<?php echo esc_url( $intent ); ?>"
           target="_blank" rel="<?php echo esc_attr( $rel ); ?>">
            <span class="rodolfo-ctt-prompt"><?php echo esc_html( $atts['prompt'] ); ?></span>
            <?php echo rodolfo_icon( 'twitter', 'rodolfo-ctt-bird' ); ?>
        </a>
    </figure>
    <?php
    return ob_get_clean();
}

/**
 * Native block wrapping the shortcode: editors get a visual quote block, the
 * server renders through rodolfo_bctt_shortcode(), so the block and the
 * shortcode can never drift apart. Registered even when the BCTT plugin owns
 * the [bctt] shortcode — render_callback calls do_shortcode, so whichever
 * implementation is active (plugin or theme) renders the block.
 */
function rodolfo_bctt_register_block() {
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
    wp_register_script(
        'rodolfo-bctt-block',
        get_template_directory_uri() . '/assets/js/bctt-block' . $suffix . '.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
        RODOLFO_VERSION,
        true
    );
    register_block_type( 'rodolfo/click-to-tweet', array(
        'api_version'     => 2,
        'editor_script'   => 'rodolfo-bctt-block',
        'attributes'      => array(
            'tweet'  => array( 'type' => 'string', 'default' => '' ),
            'via'    => array( 'type' => 'string', 'default' => '' ),
            'prompt' => array( 'type' => 'string', 'default' => '' ),
        ),
        'render_callback' => 'rodolfo_bctt_render_block',
    ) );
}
add_action( 'init', 'rodolfo_bctt_register_block', 20 );

/**
 * Render the block through the shortcode.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function rodolfo_bctt_render_block( $attributes ) {
    $tweet = isset( $attributes['tweet'] ) ? trim( wp_strip_all_tags( (string) $attributes['tweet'] ) ) : '';
    if ( '' === $tweet ) {
        return '';
    }
    $sc = '[bctt tweet="' . esc_attr( $tweet ) . '"';
    if ( ! empty( $attributes['via'] ) ) {
        $sc .= ' via="' . esc_attr( sanitize_text_field( (string) $attributes['via'] ) ) . '"';
    }
    if ( ! empty( $attributes['prompt'] ) ) {
        $sc .= ' prompt="' . esc_attr( sanitize_text_field( (string) $attributes['prompt'] ) ) . '"';
    }
    return do_shortcode( $sc . ']' );
}
