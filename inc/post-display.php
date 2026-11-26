<?php
/**
 * Per-post display options: hide title / hide featured image / full width.
 *
 * Extends the pattern the unlist toggle established — small per-post
 * decisions live in post meta, saved with their own nonce, applied by the
 * templates through one helper. Three honest use cases: a post whose hero
 * image already carries the title (hide title), a post where the featured
 * image exists only for the OG card (hide featured), and a data-heavy piece
 * that needs the room (full width).
 *
 * @package Rodolfo
 */

defined( 'ABSPATH' ) || exit;

/** Meta keys, one per flag; '1' when set, absent otherwise. */
function rodolfo_post_display_keys() {
    return array(
        'hide_title'    => '_rodolfo_hide_title',
        'hide_featured' => '_rodolfo_hide_featured',
        'full_width'    => '_rodolfo_full_width',
    );
}

/**
 * Read a post's display flags.
 *
 * @param int|WP_Post|null $post Post.
 * @return array{hide_title:bool,hide_featured:bool,full_width:bool}
 */
function rodolfo_post_display( $post = null ) {
    $post  = get_post( $post );
    $flags = array();
    foreach ( rodolfo_post_display_keys() as $flag => $meta_key ) {
        $flags[ $flag ] = $post ? (bool) get_post_meta( $post->ID, $meta_key, true ) : false;
    }
    return $flags;
}

/** Side meta box on posts. */
function rodolfo_post_display_box() {
    add_meta_box(
        'tk-post-display',
        __( 'Display', 'rodolfo' ),
        'rodolfo_post_display_box_render',
        'post',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'rodolfo_post_display_box' );

/** Meta box body. */
function rodolfo_post_display_box_render( $post ) {
    wp_nonce_field( 'rodolfo_post_display_save', 'rodolfo_post_display_nonce' );
    $flags  = rodolfo_post_display( $post );
    $labels = array(
        'hide_title'    => __( 'Hide the title', 'rodolfo' ),
        'hide_featured' => __( 'Hide the featured image', 'rodolfo' ),
        'full_width'    => __( 'Full width', 'rodolfo' ),
    );
    foreach ( $labels as $flag => $label ) {
        printf(
            '<p><label><input type="checkbox" name="rodolfo_display[%1$s]" value="1" %2$s /> %3$s</label></p>',
            esc_attr( $flag ),
            checked( $flags[ $flag ], true, false ),
            esc_html( $label )
        );
    }
    echo '<p class="description">' . esc_html__( 'Affects this post only. The featured image is still used for share cards when hidden here.', 'rodolfo' ) . '</p>';
}

/** Persist the flags. */
function rodolfo_post_display_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_post_display_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rodolfo_post_display_nonce'] ), 'rodolfo_post_display_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $submitted = isset( $_POST['rodolfo_display'] ) && is_array( $_POST['rodolfo_display'] ) ? array_map( 'sanitize_key', array_keys( wp_unslash( $_POST['rodolfo_display'] ) ) ) : array();
    foreach ( rodolfo_post_display_keys() as $flag => $meta_key ) {
        if ( in_array( $flag, $submitted, true ) ) {
            update_post_meta( $post_id, $meta_key, '1' );
        } else {
            delete_post_meta( $post_id, $meta_key );
        }
    }
}
add_action( 'save_post', 'rodolfo_post_display_save' );
