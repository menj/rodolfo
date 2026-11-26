<?php
/**
 * Content hygiene: image alt text, link rel handling and the per-post SEO box.
 *
 *  - Every <img> gets a descriptive alt: its own, else the attachment alt, the
 *    attachment title, then the post title. (Width/height come from core, which
 *    also lazy-loads below-the-fold images; templates mark the LCP image eager
 *    with fetchpriority="high".)
 *  - Comment links always carry rel="nofollow ugc".
 *  - Post meta _theme_nofollow_external adds rel="nofollow" to the post's
 *    external outbound links.
 *  - Meta box: meta description (_theme_meta_description), alternative headline
 *    (_theme_alt_headline) and the nofollow toggle.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** Is this URL on another host than the site? Relative and same-host URLs are internal. */
function rodolfo_is_external_url( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url || '#' === $url[0] || 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
        return false;
    }
    if ( ! preg_match( '#^(?:https?:)?//#i', $url ) ) {
        return false; // mailto:, tel:, javascript:, relative paths.
    }
    $host = wp_parse_url( $url, PHP_URL_HOST );
    $home = wp_parse_url( home_url(), PHP_URL_HOST );
    return $host && $home && 0 !== strcasecmp( preg_replace( '/^www\./i', '', $host ), preg_replace( '/^www\./i', '', $home ) );
}

/**
 * Add rel tokens to every external <a href> in an HTML fragment, keeping any
 * rel the link already has.
 */
function rodolfo_add_rel_to_external_links( $html, array $tokens ) {
    if ( false === stripos( $html, '<a ' ) ) {
        return $html;
    }
    return preg_replace_callback( '#<a\s([^>]*?)>#i', function ( $m ) use ( $tokens ) {
        $attrs = $m[1];
        if ( ! preg_match( '#\bhref\s*=\s*(["\'])(.*?)\1#i', $attrs, $hm ) || ! rodolfo_is_external_url( html_entity_decode( $hm[2] ) ) ) {
            return $m[0];
        }
        $existing = array();
        if ( preg_match( '#\brel\s*=\s*(["\'])(.*?)\1#i', $attrs, $rm ) ) {
            $existing = preg_split( '/\s+/', trim( $rm[2] ) );
            $attrs    = preg_replace( '#\s*\brel\s*=\s*(["\']).*?\1#i', '', $attrs );
        }
        $rel = implode( ' ', array_values( array_unique( array_filter( array_merge( $existing, $tokens ) ) ) ) );
        return '<a ' . trim( $attrs ) . ' rel="' . esc_attr( $rel ) . '">';
    }, $html );
}

// ---------------------------------------------------------------- comments

function rodolfo_comment_text_rel( $text ) {
    return rodolfo_add_rel_to_external_links( $text, array( 'nofollow', 'ugc' ) );
}
add_filter( 'comment_text', 'rodolfo_comment_text_rel', 99 );

function rodolfo_comment_author_link_rel( $link ) {
    return rodolfo_add_rel_to_external_links( $link, array( 'external', 'nofollow', 'ugc' ) );
}
add_filter( 'get_comment_author_link', 'rodolfo_comment_author_link_rel', 99 );

// ----------------------------------------------------------- post-level rel

function rodolfo_post_nofollow_external( $content ) {
    if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    if ( ! get_post_meta( get_the_ID(), '_theme_nofollow_external', true ) ) {
        return $content;
    }
    return rodolfo_add_rel_to_external_links( $content, array( 'nofollow' ) );
}
add_filter( 'the_content', 'rodolfo_post_nofollow_external', 20 );

// -------------------------------------------------------------- image alt

/** Best available alt text for an attachment. */
function rodolfo_image_alt_fallback( $attachment_id = 0 ) {
    if ( $attachment_id ) {
        $alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
        if ( '' !== $alt ) {
            return $alt;
        }
        $title = trim( wp_strip_all_tags( get_the_title( $attachment_id ) ) );
        // Camera filenames ("IMG_2041") are not descriptions.
        if ( '' !== $title && ! preg_match( '/^(img|dsc|dscn|pxl|photo|image|screenshot)[-_ ]?\d+/i', $title ) ) {
            return $title;
        }
    }
    $post_title = trim( wp_strip_all_tags( get_the_title() ) );
    return '' !== $post_title ? $post_title : get_bloginfo( 'name' );
}

function rodolfo_attachment_image_attributes( $attr, $attachment ) {
    if ( empty( $attr['alt'] ) ) {
        $attr['alt'] = rodolfo_image_alt_fallback( $attachment ? (int) $attachment->ID : 0 );
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'rodolfo_attachment_image_attributes', 10, 2 );

/** Images inside post content (core 6.0+ passes each processed <img> tag). */
function rodolfo_content_img_alt( $filtered_image, $context, $attachment_id ) {
    if ( preg_match( '#\salt\s*=\s*(["\'])\s*\1#i', $filtered_image ) || ! preg_match( '#\salt\s*=#i', $filtered_image ) ) {
        $alt   = esc_attr( rodolfo_image_alt_fallback( (int) $attachment_id ) );
        $clean = preg_replace( '#\s+alt\s*=\s*(["\'])\s*\1#i', '', $filtered_image );
        return preg_replace( '#<img\b#i', '<img alt="' . $alt . '"', $clean, 1 );
    }
    return $filtered_image;
}
add_filter( 'wp_content_img_tag', 'rodolfo_content_img_alt', 10, 3 );

// ------------------------------------------------------------ SEO meta box

function rodolfo_seo_meta_box_register() {
    foreach ( array( 'post', 'page' ) as $type ) {
        add_meta_box( 'rodolfo-seo', __( 'Search & sharing', 'rodolfo' ), 'rodolfo_seo_meta_box_render', $type, 'normal', 'default' );
    }
}
add_action( 'add_meta_boxes', 'rodolfo_seo_meta_box_register' );

function rodolfo_seo_meta_box_render( $post ) {
    wp_nonce_field( 'rodolfo_seo_meta', 'rodolfo_seo_meta_nonce' );
    $desc = (string) get_post_meta( $post->ID, '_theme_meta_description', true );
    $alt  = (string) get_post_meta( $post->ID, '_theme_alt_headline', true );
    $nofo = (bool) get_post_meta( $post->ID, '_theme_nofollow_external', true );
    ?>
    <p>
        <label for="theme_meta_description"><strong><?php esc_html_e( 'Meta description', 'rodolfo' ); ?></strong></label><br>
        <textarea id="theme_meta_description" name="theme_meta_description" rows="3" class="widefat" maxlength="320"><?php echo esc_textarea( $desc ); ?></textarea>
        <span class="description"><?php esc_html_e( 'Aim for 120–155 characters. Leave blank to use a clean, sentence-trimmed excerpt of the content.', 'rodolfo' ); ?></span>
    </p>
    <p>
        <label for="theme_alt_headline"><strong><?php esc_html_e( 'Alternative headline', 'rodolfo' ); ?></strong></label><br>
        <input type="text" id="theme_alt_headline" name="theme_alt_headline" class="widefat" value="<?php echo esc_attr( $alt ); ?>" />
    </p>
    <p>
        <label><input type="checkbox" name="theme_nofollow_external" value="1" <?php checked( $nofo ); ?> />
        <?php esc_html_e( 'Add rel="nofollow" to external links in this post', 'rodolfo' ); ?></label>
    </p>
    <?php
}

function rodolfo_seo_meta_box_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rodolfo_seo_meta_nonce'] ) ), 'rodolfo_seo_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $map = array(
        '_theme_meta_description' => isset( $_POST['theme_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['theme_meta_description'] ) ) : '',
        '_theme_alt_headline'     => isset( $_POST['theme_alt_headline'] ) ? sanitize_text_field( wp_unslash( $_POST['theme_alt_headline'] ) ) : '',
    );
    foreach ( $map as $key => $value ) {
        if ( '' === $value ) {
            delete_post_meta( $post_id, $key );
        } else {
            update_post_meta( $post_id, $key, $value );
        }
    }
    if ( ! empty( $_POST['theme_nofollow_external'] ) ) {
        update_post_meta( $post_id, '_theme_nofollow_external', '1' );
    } else {
        delete_post_meta( $post_id, '_theme_nofollow_external' );
    }
}
add_action( 'save_post', 'rodolfo_seo_meta_box_save' );
