<?php
/**
 * Auto-generated social card.
 *
 * When a post has no featured image, its Open Graph image points at a
 * generated card that renders the post title on the theme's gradient — the
 * honest equivalent of a stock photo: the post's own words, set as an object.
 *
 * Served from home_url( '?rodolfo_card=<id>' ). Requires the GD extension;
 * if GD is unavailable, rodolfo_social_card_url() returns '' and the meta
 * layer falls back to the site icon.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function rodolfo_social_card_available() {
    return function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagettftext' );
}

/**
 * URL for a post's generated card, or '' when unavailable.
 */
function rodolfo_social_card_url( $post_id ) {
    if ( ! rodolfo_social_card_available() ) {
        return '';
    }
    return add_query_arg( 'rodolfo_card', (int) $post_id, home_url( '/' ) );
}

function rodolfo_social_card_query_var( $vars ) {
    $vars[] = 'rodolfo_card';
    return $vars;
}
add_filter( 'query_vars', 'rodolfo_social_card_query_var' );

/**
 * Fetch an author's avatar and return a circular GD image of $size px, or false.
 * Network fetch is capped short and failures degrade gracefully (no avatar).
 */
function rodolfo_social_card_avatar( $user_id, $size = 72 ) {
    if ( ! get_option( 'show_avatars' ) ) {
        return false;
    }
    $url = get_avatar_url( $user_id, array( 'size' => $size * 2 ) );
    if ( ! $url ) {
        return false;
    }
    // Avatar plugins/filters can change the URL. Validate destinations
    // (including redirects), and bound downloaded bytes.
    $resp = wp_safe_remote_get( $url, array(
        'timeout'             => 3,
        'redirection'         => 1,
        'limit_response_size' => 512 * 1024,
    ) );
    if ( is_wp_error( $resp ) || 200 !== (int) wp_remote_retrieve_response_code( $resp ) ) {
        return false;
    }
    $body = wp_remote_retrieve_body( $resp );
    $info = @getimagesizefromstring( $body );
    if ( ! $info || $info[0] < 1 || $info[1] < 1 || $info[0] > 1024 || $info[1] > 1024 ) {
        return false;
    }
    $src = @imagecreatefromstring( $body );
    if ( ! $src ) {
        return false;
    }

    $out = imagecreatetruecolor( $size, $size );
    imagesavealpha( $out, true );
    imagefill( $out, 0, 0, imagecolorallocatealpha( $out, 0, 0, 0, 127 ) );
    imagecopyresampled( $out, $src, 0, 0, 0, 0, $size, $size, imagesx( $src ), imagesy( $src ) );
    imagedestroy( $src );

    // Circular mask: clear everything outside the inscribed circle.
    $transparent = imagecolorallocatealpha( $out, 0, 0, 0, 127 );
    $cx = $cy = $size / 2;
    $r  = $size / 2;
    for ( $x = 0; $x < $size; $x++ ) {
        for ( $y = 0; $y < $size; $y++ ) {
            if ( ( ( $x - $cx ) ** 2 + ( $y - $cy ) ** 2 ) > $r * $r ) {
                imagesetpixel( $out, $x, $y, $transparent );
            }
        }
    }
    return $out;
}

/**
 * The custom logo as a GD image scaled to $max_h, or false. Reads the
 * attachment file straight from disk — no network round-trip like avatars.
 */
function rodolfo_social_card_logo( $max_h = 44 ) {
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    if ( ! $logo_id ) {
        return false;
    }
    $path = get_attached_file( $logo_id );
    if ( ! $path || ! is_readable( $path ) ) {
        return false;
    }
    $bytes = @filesize( $path );
    $info  = @getimagesize( $path );
    if ( false === $bytes || $bytes > 8 * 1024 * 1024 || ! $info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 8 * 1024 * 1024 ) {
        return false;
    }
    $src = @imagecreatefromstring( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local attachment file.
    if ( ! $src ) {
        return false;
    }
    $ratio = imagesy( $src ) > 0 ? $max_h / imagesy( $src ) : 0;
    $out_w = max( 1, (int) round( imagesx( $src ) * $ratio ) );
    if ( $out_w > 480 ) {
        imagedestroy( $src );
        return false;
    }
    $out   = imagecreatetruecolor( $out_w, $max_h );
    imagesavealpha( $out, true );
    imagefill( $out, 0, 0, imagecolorallocatealpha( $out, 0, 0, 0, 127 ) );
    imagecopyresampled( $out, $src, 0, 0, 0, 0, $out_w, $max_h, imagesx( $src ), imagesy( $src ) );
    imagedestroy( $src );
    return $out;
}

/**
 * Cache only anonymous, public responses in a persistent object cache.
 * No database transients or public files containing obsolete post titles.
 * A single cache slot per post prevents key churn when branding changes.
 */
function rodolfo_social_card_cache_enabled() {
    return wp_using_ext_object_cache() && ! is_user_logged_in()
        && (bool) apply_filters( 'rodolfo_social_card_cache_enabled', true );
}

function rodolfo_social_card_cache_signature( $post, $category, $accent, $variant ) {
    $logo_id = (int) get_theme_mod( 'custom_logo' );
    $logo = $logo_id ? get_post( $logo_id ) : null;
    return hash( 'sha256', (string) wp_json_encode( array(
        RODOLFO_VERSION,
        $post->ID,
        $post->post_modified_gmt,
        get_the_title( $post ),
        $post->post_author,
        get_the_author_meta( 'display_name', $post->post_author ),
        $category,
        $accent,
        $variant,
        get_bloginfo( 'name' ),
        get_locale(),
        $logo_id,
        $logo ? $logo->post_modified_gmt : '',
        (bool) get_option( 'show_avatars' ),
    ) ) );
}

function rodolfo_social_card_cache_get( $post_id, $signature ) {
    if ( ! rodolfo_social_card_cache_enabled() ) {
        return false;
    }
    $entry = wp_cache_get( 'card:' . (int) $post_id, 'rodolfo_social_cards' );
    if ( ! is_array( $entry ) || ! isset( $entry['signature'], $entry['png'] )
        || $signature !== $entry['signature'] || ! is_string( $entry['png'] )
        || strlen( $entry['png'] ) > 512 * 1024 || 0 !== strpos( $entry['png'], "\x89PNG\r\n\x1a\n" ) ) {
        return false;
    }
    return $entry['png'];
}

function rodolfo_social_card_cache_set( $post_id, $signature, $png ) {
    if ( rodolfo_social_card_cache_enabled() && is_string( $png )
        && strlen( $png ) <= 512 * 1024 && 0 === strpos( $png, "\x89PNG\r\n\x1a\n" ) ) {
        wp_cache_set( 'card:' . (int) $post_id, array( 'signature' => $signature, 'png' => $png ), 'rodolfo_social_cards', HOUR_IN_SECONDS );
    }
}

function rodolfo_social_card_send( $png ) {
    if ( ! headers_sent() ) {
        status_header( 200 );
        header( 'Content-Type: image/png' );
        header( 'X-Content-Type-Options: nosniff' );
        // Limit downstream staleness if a public post becomes protected.
        if ( is_user_logged_in() ) {
            nocache_headers();
        } else {
            header( 'Cache-Control: public, max-age=300' );
        }
    }
    echo $png; // phpcs:ignore WordPress.Security.EscapeOutput -- validated binary PNG, not HTML.
    exit;
}

function rodolfo_social_card_render() {
    $raw_id = get_query_var( 'rodolfo_card' );
    if ( '' === $raw_id || null === $raw_id || false === $raw_id ) {
        return;
    }
    $id = is_scalar( $raw_id ) && preg_match( '/^[0-9]+$/D', (string) $raw_id ) ? (int) $raw_id : 0;
    $post = $id > 0 ? get_post( $id ) : null;
    if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || ! empty( $post->post_password ) || ! rodolfo_social_card_available() ) {
        status_header( 404 );
        nocache_headers();
        exit;
    }

    // Vertical gradient. Light is the default, matching the site's own default
    // appearance; a dark variant is offered for sites whose branding calls for
    // it. Both are legible against light and dark social feeds.
    // Category first: its colour now tints the background, not just the bar.
    $cats     = get_the_category( $post->ID );
    $cat_name = ! empty( $cats ) ? strtoupper( $cats[0]->name ) : '';
    $accent   = ! empty( $cats ) && function_exists( 'rodolfo_category_color' )
        ? rodolfo_category_color( $cats[0]->term_id )
        : '#3d54d6';
    list( $ar, $ag, $ab ) = sscanf( $accent, '#%02x%02x%02x' );

    $variant = (string) get_option( 'rodolfo_card_variant', 'light' );
    $signature = rodolfo_social_card_cache_signature( $post, $cat_name, $accent, $variant );
    $cached = rodolfo_social_card_cache_get( $post->ID, $signature );
    if ( false !== $cached ) {
        rodolfo_social_card_send( $cached );
    }

    $w = 1200;
    $h = 630;
    $img = imagecreatetruecolor( $w, $h );
    if ( ! $img ) {
        status_header( 500 );
        nocache_headers();
        exit;
    }

    if ( 'light' === $variant ) {
        $c1 = array( 248, 250, 252 ); // #f8fafc
        $c2 = array( 219, 234, 254 ); // #dbeafe
        $mix = 0.22; // subtle wash on light
    } else {
        $c1 = array( 15, 23, 42 );   // #0f172a
        $c2 = array( 37, 99, 235 ); // #2563eb
        $mix = 0.55; // the dark card leans harder into the hue
    }
    // Per-category background treatment: blend the gradient's far endpoint
    // toward the category colour, so each topic's cards share a family look.
    foreach ( array( 0, 1, 2 ) as $ch ) {
        $c2[ $ch ] = (int) round( $c2[ $ch ] + ( array( $ar, $ag, $ab )[ $ch ] - $c2[ $ch ] ) * $mix );
    }
    for ( $y = 0; $y < $h; $y++ ) {
        $t = $y / $h;
        $r = (int) ( $c1[0] + ( $c2[0] - $c1[0] ) * $t );
        $g = (int) ( $c1[1] + ( $c2[1] - $c1[1] ) * $t );
        $b = (int) ( $c1[2] + ( $c2[2] - $c1[2] ) * $t );
        $line = imagecolorallocate( $img, $r, $g, $b );
        imagefilledrectangle( $img, 0, $y, $w, $y, $line );
    }

    if ( 'light' === $variant ) {
        $white = imagecolorallocate( $img, 15, 23, 42 );    // ink on light
        $muted = imagecolorallocate( $img, 71, 85, 105 );   // slate-600
    } else {
        $white = imagecolorallocate( $img, 255, 255, 255 );
        $muted = imagecolorallocate( $img, 191, 219, 254 );
    }
    $font  = get_template_directory() . '/assets/fonts/Inter-Bold.ttf';
    $pad   = 90;

    // Category accent bar + label reuse the colour resolved above.
    $accent_col = imagecolorallocate( $img, (int) $ar, (int) $ag, (int) $ab );
    imagefilledrectangle( $img, 0, 0, $w, 14, $accent_col );

    if ( is_readable( $font ) ) {
        // Category label (accent) near the top.
        if ( $cat_name ) {
            imagettftext( $img, 22, 0, $pad, 120, $accent_col, $font, $cat_name );
        }

        // Wrap the title to the card width.
        $title = wp_strip_all_tags( get_the_title( $post ) );
        $size  = 58;
        $words = preg_split( '/\s+/u', $title );
        $lines = array();
        $line  = '';
        foreach ( $words as $word ) {
            $try = trim( $line . ' ' . $word );
            $box = imagettfbbox( $size, 0, $font, $try );
            if ( false !== $box && $box[2] - $box[0] > $w - $pad * 2 && $line ) {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $try;
            }
        }
        if ( $line ) {
            $lines[] = $line;
        }
        $lines = array_slice( $lines, 0, 4 );

        $y = 230;
        foreach ( $lines as $l ) {
            imagettftext( $img, $size, 0, $pad, $y, $white, $font, $l );
            $y += (int) ( $size * 1.4 );
        }

        // Author avatar (circle) + name + site name, bottom-left.
        $author  = get_the_author_meta( 'display_name', $post->post_author );
        $ax      = $pad;
        $line_y  = $h - 68;
        $avatar  = rodolfo_social_card_avatar( $post->post_author, 72 );
        if ( $avatar ) {
            imagecopy( $img, $avatar, $ax, $line_y - 52, 0, 0, 72, 72 );
            imagedestroy( $avatar );
            $ax += 92;
        }
        imagettftext( $img, 26, 0, $ax, $line_y - 20, $white, $font, $author );
        imagettftext( $img, 20, 0, $ax, $line_y + 12, $muted, $font, get_bloginfo( 'name' ) );

        // Site logo lockup, bottom-right — the custom logo scaled to a 44px
        // cap. Failures degrade to nothing: the site name already prints in
        // the author block, so the card is never unbranded.
        $logo = rodolfo_social_card_logo( 44 );
        if ( $logo ) {
            imagecopy( $img, $logo, $w - $pad - imagesx( $logo ), $line_y - 46, 0, 0, imagesx( $logo ), imagesy( $logo ) );
            imagedestroy( $logo );
        }
    } else {
        // No bundled font: built-in bitmap font fallback so the card is never blank.
        if ( $cat_name ) {
            imagestring( $img, 5, $pad, 60, $cat_name, $accent_col );
        }
        imagestring( $img, 5, $pad, 260, wp_strip_all_tags( get_the_title( $post ) ), $white );
        imagestring( $img, 5, $pad, $h - 80, get_the_author_meta( 'display_name', $post->post_author ) . ' · ' . get_bloginfo( 'name' ), $muted );
    }

    ob_start();
    $success = imagepng( $img );
    $png = ob_get_clean();
    imagedestroy( $img );
    if ( ! $success || ! is_string( $png ) || 0 !== strpos( $png, "\x89PNG\r\n\x1a\n" ) ) {
        status_header( 500 );
        nocache_headers();
        exit;
    }
    rodolfo_social_card_cache_set( $post->ID, $signature, $png );
    rodolfo_social_card_send( $png );
}
add_action( 'template_redirect', 'rodolfo_social_card_render' );
