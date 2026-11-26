<?php
/**
 * Guest authors: show one or more custom bylines instead of the WordPress user.
 *
 * Per post, a comma-separated list of names is stored in post meta. When set,
 * every byline the theme (or a plugin) prints through the_author() /
 * get_the_author_meta( 'display_name' ) shows those names, the author archive
 * link and WordPress-user avatar/bio are suppressed for that post, and the
 * Schema.org graph declares one Person per guest author. An optional site-wide
 * default byline applies to every post without its own list.
 *
 * Options (Theme Options → Content): default byline, name joining style,
 * whether overrides apply on every view or only on single posts.
 * Filter `rodolfo_guest_author_post_types` changes the supported post types.
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

/** Post meta key holding the comma-separated names. */
const RODOLFO_GA_META = '_rodolfo_guest_author';

/** Post types the override applies to. */
function rodolfo_ga_post_types() {
    return (array) apply_filters( 'rodolfo_guest_author_post_types', array( 'post' ) );
}

/**
 * Guest author names for a post, as an array (empty when none apply).
 * Falls back to the site-wide default byline.
 *
 * @param int|WP_Post|null $post Post.
 * @return string[]
 */
function rodolfo_ga_names( $post = null ) {
    static $memo = array();

    $post = get_post( $post );
    if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, rodolfo_ga_post_types(), true ) ) {
        return array();
    }
    $scope_blocks = ( 'singular' === get_option( 'rodolfo_ga_scope', 'all' ) && ! is_singular() );

    $key = $post->ID . ( $scope_blocks ? ':s' : ':a' );
    if ( isset( $memo[ $key ] ) ) {
        return $memo[ $key ];
    }

    $raw = '';
    if ( ! $scope_blocks ) {
        $raw = (string) get_post_meta( $post->ID, RODOLFO_GA_META, true );
        if ( '' === $raw ) {
            $raw = (string) get_post_meta( $post->ID, 'guest-author', true ); // Bylines saved before this feature shipped in the theme.
        }
    }
    $names = array_values( array_unique( array_filter( array_map( 'trim', array_map( 'sanitize_text_field', explode( ',', $raw ) ) ) ) ) );
    if ( ! $names ) {
        $default = trim( sanitize_text_field( (string) get_option( 'rodolfo_ga_default', '' ) ) );
        if ( '' !== $default ) {
            $names = array( $default );
        }
    }
    $memo[ $key ] = $names;
    return $names;
}

/** True when the post prints a guest byline. */
function rodolfo_has_guest_author( $post = null ) {
    return (bool) rodolfo_ga_names( $post );
}

/** Names joined per the Theme Options style ("A and B", "A, B", "A & B"). */
function rodolfo_ga_format( array $names ) {
    $count = count( $names );
    if ( $count < 2 ) {
        return $count ? $names[0] : '';
    }
    $style = get_option( 'rodolfo_ga_join', 'natural' );
    if ( 'comma' === $style ) {
        return implode( ', ', $names );
    }
    if ( 'ampersand' === $style ) {
        return implode( ' & ', $names );
    }
    $last = array_pop( $names );
    return implode( ', ', $names ) . ' ' . __( 'and', 'rodolfo' ) . ' ' . $last;
}

/** Classic path: the_author() / get_the_author(). */
function rodolfo_ga_filter_name( $name ) {
    $names = rodolfo_ga_names();
    return $names ? rodolfo_ga_format( $names ) : $name;
}
add_filter( 'the_author', 'rodolfo_ga_filter_name', 20 );

/**
 * get_the_author_meta( 'display_name' ) path. Only the current post's own
 * author is replaced, so comment authors, the author archive heading and
 * other users keep their real names.
 */
function rodolfo_ga_filter_display_name( $value, $user_id = 0 ) {
    $post = get_post();
    if ( ! $post instanceof WP_Post || (int) $user_id !== (int) $post->post_author ) {
        return $value;
    }
    $names = rodolfo_ga_names( $post );
    return $names ? rodolfo_ga_format( $names ) : $value;
}
add_filter( 'get_the_author_display_name', 'rodolfo_ga_filter_display_name', 20, 2 );

/** A guest has no author archive: drop the link, keep the name. */
function rodolfo_ga_filter_author_link( $url ) {
    return rodolfo_has_guest_author() ? '' : $url;
}
add_filter( 'author_link', 'rodolfo_ga_filter_author_link' );

function rodolfo_ga_filter_posts_link( $link ) {
    return rodolfo_has_guest_author() ? wp_strip_all_tags( $link ) : $link;
}
add_filter( 'the_author_posts_link', 'rodolfo_ga_filter_posts_link' );

/** Block editor / REST need the meta registered and the post type to allow custom fields. */
function rodolfo_ga_register_meta() {
    foreach ( rodolfo_ga_post_types() as $type ) {
        if ( ! post_type_exists( $type ) ) {
            continue;
        }
        add_post_type_support( $type, 'custom-fields' );
        register_post_meta( $type, RODOLFO_GA_META, array(
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }
}
add_action( 'init', 'rodolfo_ga_register_meta', 9 );

// ---------------------------------------------------------------- editing UI

function rodolfo_ga_box() {
    add_meta_box( 'tk-guest-authors', __( 'Guest authors', 'rodolfo' ), 'rodolfo_ga_box_render', rodolfo_ga_post_types(), 'side', 'default' );
}
add_action( 'add_meta_boxes', 'rodolfo_ga_box' );

function rodolfo_ga_box_render( $post ) {
    wp_nonce_field( 'rodolfo_ga_save', 'rodolfo_ga_nonce' );
    $value = (string) get_post_meta( $post->ID, RODOLFO_GA_META, true );
    if ( '' === $value ) {
        $value = (string) get_post_meta( $post->ID, 'guest-author', true );
    }
    echo '<p><label for="rodolfo_ga_field" class="screen-reader-text">' . esc_html__( 'Guest author name(s)', 'rodolfo' ) . '</label>';
    echo '<input type="text" class="widefat" id="rodolfo_ga_field" name="rodolfo_ga_field" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr__( 'e.g. Jane Doe, John Smith', 'rodolfo' ) . '" /></p>';
    echo '<p class="description">' . esc_html__( 'Separate several authors with commas. Leave blank to show the post\'s WordPress author.', 'rodolfo' ) . '</p>';
}

function rodolfo_ga_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_ga_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rodolfo_ga_nonce'] ), 'rodolfo_ga_save' ) ) {
        return;
    }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $value = isset( $_POST['rodolfo_ga_field'] ) ? sanitize_text_field( wp_unslash( $_POST['rodolfo_ga_field'] ) ) : '';
    if ( '' !== $value ) {
        update_post_meta( $post_id, RODOLFO_GA_META, $value );
    } else {
        delete_post_meta( $post_id, RODOLFO_GA_META );
        delete_post_meta( $post_id, 'guest-author' );
    }
}
add_action( 'save_post', 'rodolfo_ga_save' );
