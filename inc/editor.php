<?php
/**
 * Block editor parity.
 *
 * The front end renders post content through Tailwind Typography (`prose-lg
 * prose-slate dark:prose-invert`), but the block editor knows nothing about
 * that — so authors compose in one typeface and publish in another, and
 * long-form posts only look right after a preview. This module closes that gap:
 *
 *   1. `editor-styles` + `add_editor_style()` load the compiled theme CSS into
 *      the editor canvas, with a small shim mapping the editor's own wrapper to
 *      the same typographic scale the front end uses.
 *   2. `editor-color-palette` registers the brand cobalt plus the category
 *      accent ramp, so the colour swatches an author sees are the ones the site
 *      actually uses — not WordPress's generic defaults.
 *   3. `editor-font-sizes` mirrors the front-end type scale.
 *
 * Palette and sizes are filterable, and the palette is derived from
 * `rodolfo_category_palette()` so the two can never drift apart.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Editor colour swatches. Brand cobalt first, then neutrals, then the category
 * accent ramp — the same values `inc/category-color.php` assigns to terms.
 */
function rodolfo_editor_palette() {
    $palette = array(
        array(
            'name'  => __( 'Brand cobalt', 'rodolfo' ),
            'slug'  => 'rodolfo-accent',
            'color' => '#3d54d6',
        ),
        array(
            'name'  => __( 'Ink', 'rodolfo' ),
            'slug'  => 'rodolfo-ink',
            'color' => '#0f172a',
        ),
        array(
            'name'  => __( 'Muted', 'rodolfo' ),
            'slug'  => 'rodolfo-muted',
            'color' => '#475569',
        ),
        array(
            'name'  => __( 'Hairline', 'rodolfo' ),
            'slug'  => 'rodolfo-line',
            'color' => '#e2e8f0',
        ),
        array(
            'name'  => __( 'Page', 'rodolfo' ),
            'slug'  => 'rodolfo-page',
            'color' => '#f8fafc',
        ),
    );

    // Category accents, labelled by hue so the picker is readable.
    $labels = array(
        __( 'Dusty blue', 'rodolfo' ),
        __( 'Sage', 'rodolfo' ),
        __( 'Clay', 'rodolfo' ),
        __( 'Ochre', 'rodolfo' ),
        __( 'Muted violet', 'rodolfo' ),
        __( 'Teal', 'rodolfo' ),
        __( 'Mauve', 'rodolfo' ),
        __( 'Olive', 'rodolfo' ),
    );
    if ( function_exists( 'rodolfo_category_palette' ) ) {
        foreach ( array_values( rodolfo_category_palette() ) as $i => $hex ) {
            $palette[] = array(
                'name'  => isset( $labels[ $i ] ) ? $labels[ $i ] : sprintf( __( 'Accent %d', 'rodolfo' ), $i + 1 ),
                'slug'  => 'rodolfo-cat-' . ( $i + 1 ),
                'color' => $hex,
            );
        }
    }

    /** Filter the block editor colour palette. */
    return apply_filters( 'rodolfo_editor_palette', $palette );
}

/**
 * Editor font sizes, mirroring the front-end scale (prose-lg on posts, with
 * prose-xl at the top breakpoint).
 */
function rodolfo_editor_font_sizes() {
    /** Filter the block editor font-size presets. */
    return apply_filters( 'rodolfo_editor_font_sizes', array(
        array( 'name' => __( 'Small', 'rodolfo' ),  'slug' => 'small',  'size' => 14 ),
        array( 'name' => __( 'Normal', 'rodolfo' ), 'slug' => 'normal', 'size' => 18 ),
        array( 'name' => __( 'Medium', 'rodolfo' ), 'slug' => 'medium', 'size' => 21 ),
        array( 'name' => __( 'Large', 'rodolfo' ),  'slug' => 'large',  'size' => 27 ),
        array( 'name' => __( 'Huge', 'rodolfo' ),   'slug' => 'huge',   'size' => 36 ),
    ) );
}

/**
 * Register editor supports. Runs on after_setup_theme, after rodolfo_setup().
 */
function rodolfo_editor_setup() {
    // Load the compiled theme CSS into the editor canvas.
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/theme.min.css' );
    add_editor_style( 'assets/css/editor.css' );

    add_theme_support( 'editor-color-palette', rodolfo_editor_palette() );
    add_theme_support( 'editor-font-sizes', rodolfo_editor_font_sizes() );

    // Authors should not invent off-brand colours or arbitrary gradients; the
    // palette above is the design system. Custom colour picking stays enabled
    // (disabling it frustrates more than it protects), but gradients — which
    // the theme never uses — are switched off.
    add_theme_support( 'disable-custom-gradients' );
    add_theme_support( 'editor-gradient-presets', array() );

    // Match the front-end content width so wide/full alignment previews are
    // truthful rather than optimistic.
    add_theme_support( 'custom-spacing' );
}
add_action( 'after_setup_theme', 'rodolfo_editor_setup', 20 );

/**
 * Dark-mode parity inside the editor.
 *
 * The front end toggles dark mode with a `.dark` class on <html>, driven by an
 * inline head script. The editor canvas is a separate document that never gets
 * that class, so `dark:prose-invert` rules would never apply. Rather than
 * shipping a broken half-state, the editor is pinned to the light palette —
 * which is what the overwhelming majority of authoring happens in — and the
 * shim in editor.css scopes the prose rules to the editor's own wrapper.
 */
function rodolfo_editor_body_class( $classes ) {
    return $classes . ' rodolfo-editor';
}
add_filter( 'admin_body_class', 'rodolfo_editor_body_class' );
