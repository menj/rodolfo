<?php
/**
 * Per-category accent colour.
 *
 * Each category carries an accent colour used on its pill/badge and the card
 * hairline. The author may pick one on the category term screen (reusing the
 * category-icon save path); if none is set, a stable colour is auto-assigned
 * from a muted palette by term ID, so every category is coloured out of the box
 * without turning the theme into a rainbow. Slate/blue stays the primary UI.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The muted accent palette. Mid-tone hues (L≈55–60%) that read as their own
 * colour for text and, at ~12% alpha, as a soft tint behind it — on both the
 * light and dark grounds — without a second stored value.
 */
function rodolfo_category_palette() {
    return apply_filters( 'rodolfo_category_palette', array(
        '#4f7cac', // dusty blue
        '#5a8f7b', // sage
        '#b06f6a', // clay
        '#a58547', // ochre
        '#7a6faf', // muted violet
        '#4a9a9a', // teal
        '#b0728f', // mauve
        '#6f8f4f', // olive
    ) );
}

/**
 * Resolve a category's accent hex: author-picked meta if valid, else a stable
 * palette colour derived from the term ID.
 */
function rodolfo_category_color( $term_id ) {
    $term_id = (int) $term_id;
    $picked  = get_term_meta( $term_id, 'category_color', true );
    if ( $picked && preg_match( '/^#[0-9a-fA-F]{6}$/', $picked ) ) {
        return $picked;
    }
    $palette = array_values( rodolfo_category_palette() );
    return $palette[ $term_id % count( $palette ) ];
}

/**
 * Inline style for a category pill: tinted background + coloured text.
 * `--rc` carries the hex for anything else that wants it (e.g. a hairline).
 */
function rodolfo_category_pill_style( $term_id ) {
    $hex = rodolfo_category_color( $term_id );
    // 12% alpha tint via 8-digit hex; text is the solid hue.
    return sprintf( '--rc:%1$s;background-color:%1$s1f;color:%1$s;', $hex );
}

/**
 * Convenience: pill style for the current post's first category, or a neutral
 * slate style when the post has no category.
 */
function rodolfo_post_category_pill_style() {
    $cats = get_the_category();
    if ( empty( $cats ) ) {
        return '';
    }
    return rodolfo_category_pill_style( $cats[0]->term_id );
}

/**
 * Echo a post's featured image, or a category-coloured gradient placeholder
 * (with the category emoji or site initial) when there is none — so image-less
 * posts still read as designed rather than as an empty grey box.
 *
 * @param int    $post_id   Post ID.
 * @param string $size      Image size for the thumbnail branch.
 * @param string $img_class Classes for the <img>.
 */
function rodolfo_post_thumbnail( $post_id, $size = 'medium_large', $img_class = 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-700' ) {
    if ( has_post_thumbnail( $post_id ) ) {
        echo get_the_post_thumbnail( $post_id, $size, array( 'class' => $img_class ) );
        return;
    }

    $cats = get_the_category( $post_id );
    $hex  = ! empty( $cats ) ? rodolfo_category_color( $cats[0]->term_id ) : '#3d54d6';

    // Glyph: the category's emoji icon if set, else the site's initial.
    $glyph = '';
    if ( ! empty( $cats ) ) {
        $glyph = get_term_meta( $cats[0]->term_id, 'category_icon', true );
    }
    if ( '' === $glyph ) {
        $name  = get_bloginfo( 'name' );
        $glyph = strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 ) );
    }

    printf(
        '<div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,%1$s22,%1$s0d)" aria-hidden="true">'
            . '<span class="text-4xl font-display font-bold opacity-70" style="color:%1$s">%2$s</span>'
            . '</div>',
        esc_attr( $hex ),
        esc_html( $glyph )
    );
}

// ---- Admin: colour picker on the category term screens ---------------------

function rodolfo_category_color_add_field() {
    ?>
    <div class="form-field term-color-wrap">
        <label for="category_color"><?php esc_html_e( 'Category Colour', 'rodolfo' ); ?></label>
        <input type="color" name="category_color" id="category_color" value="#4f7cac" />
        <p class="description"><?php esc_html_e( 'Accent colour for this category’s badge and card hairline. Leave at the default to let the theme auto-assign one.', 'rodolfo' ); ?></p>
    </div>
    <?php
}
add_action( 'category_add_form_fields', 'rodolfo_category_color_add_field', 10, 1 );

function rodolfo_category_color_edit_field( $term ) {
    $color = get_term_meta( $term->term_id, 'category_color', true );
    if ( ! $color ) {
        $color = rodolfo_category_color( $term->term_id );
    }
    ?>
    <tr class="form-field term-color-wrap">
        <th scope="row"><label for="category_color"><?php esc_html_e( 'Category Colour', 'rodolfo' ); ?></label></th>
        <td>
            <input type="color" name="category_color" id="category_color" value="<?php echo esc_attr( $color ); ?>" />
            <p class="description"><?php esc_html_e( 'Accent colour for this category’s badge and card hairline. Auto-assigned from a muted palette when not set.', 'rodolfo' ); ?></p>
        </td>
    </tr>
    <?php
}
add_action( 'category_edit_form_fields', 'rodolfo_category_color_edit_field', 10, 1 );

/**
 * Save the colour. Reuses the category-icon nonce that both term forms already
 * emit (rodolfo_save_category_icon), so no second nonce is needed.
 */
function rodolfo_category_color_save( $term_id ) {
    $nonce = isset( $_POST['rodolfo_category_icon_nonce'] )
        ? sanitize_text_field( wp_unslash( $_POST['rodolfo_category_icon_nonce'] ) )
        : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rodolfo_save_category_icon' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_categories' ) ) {
        return;
    }
    if ( ! isset( $_POST['category_color'] ) ) {
        return;
    }
    $color = sanitize_hex_color( wp_unslash( $_POST['category_color'] ) );
    if ( $color ) {
        update_term_meta( $term_id, 'category_color', $color );
    }
}
add_action( 'edited_category', 'rodolfo_category_color_save', 10, 1 );
add_action( 'create_category', 'rodolfo_category_color_save', 10, 1 );
