<?php
/**
 * Unlist posts (natively, no plugin).
 *
 * Adds an "Unlist this post" checkbox to the Publish box. Unlisted posts are
 * removed from the home page, archives, category/tag/date/author lists, search,
 * and feeds — but their single URL keeps working (great for sharing a link
 * without listing it publicly). Unlisted singular views also emit noindex.
 *
 * IDs are kept in the `rodolfo_unlisted_posts` option (mirroring the post meta)
 * so front-end queries exclude them without a meta lookup on every request.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const RODOLFO_UNLIST_META   = '_rodolfo_unlisted';
const RODOLFO_UNLIST_OPTION = 'rodolfo_unlisted_posts';

/** The list of unlisted post IDs (ints). */
function rodolfo_unlisted_ids() {
    $ids = get_option( RODOLFO_UNLIST_OPTION, array() );
    return is_array( $ids ) ? array_values( array_filter( array_map( 'absint', $ids ) ) ) : array();
}

/** Post types that can be unlisted: every public type except media. */
function rodolfo_unlist_types() {
    $types = array_diff( get_post_types( array( 'public' => true ), 'names' ), array( 'attachment' ) );
    return (array) apply_filters( 'rodolfo_unlist_post_types', array_values( $types ) );
}

/** Add or remove one ID in the fast-lookup option. */
function rodolfo_unlist_set( $post_id, $unlist ) {
    $post_id = (int) $post_id;
    $ids     = rodolfo_unlisted_ids();
    if ( $unlist ) {
        update_post_meta( $post_id, RODOLFO_UNLIST_META, '1' );
        $ids[] = $post_id;
    } else {
        delete_post_meta( $post_id, RODOLFO_UNLIST_META );
        $ids = array_diff( $ids, array( $post_id ) );
    }
    update_option( RODOLFO_UNLIST_OPTION, array_values( array_unique( array_map( 'absint', $ids ) ) ) );
}

/** One-time import of IDs saved by the standalone plugin this feature replaces. */
function rodolfo_unlist_migrate_legacy() {
    if ( get_option( 'rodolfo_unlist_migrated' ) ) {
        return;
    }
    $legacy = get_option( 'unlist_posts', array() );
    if ( is_array( $legacy ) ) {
        foreach ( array_filter( array_map( 'absint', $legacy ) ) as $id ) {
            rodolfo_unlist_set( $id, true );
        }
    }
    update_option( 'rodolfo_unlist_migrated', '1', false );
}
add_action( 'admin_init', 'rodolfo_unlist_migrate_legacy' );
add_action( 'init', function () {
    if ( ! is_admin() && ! get_option( 'rodolfo_unlist_migrated' ) ) {
        rodolfo_unlist_migrate_legacy();
    }
}, 30 );

/** Publish-box checkbox. */
function rodolfo_unlist_checkbox() {
    $post = get_post();
    if ( ! $post || ! in_array( $post->post_type, rodolfo_unlist_types(), true ) ) {
        return;
    }
    $checked = (bool) get_post_meta( $post->ID, RODOLFO_UNLIST_META, true );
    wp_nonce_field( 'rodolfo_unlist_save', 'rodolfo_unlist_nonce' );
    ?>
    <div class="misc-pub-section" style="display:flex;align-items:center;gap:6px">
        <label>
            <input type="checkbox" name="rodolfo_unlisted" value="1" <?php checked( $checked ); ?> />
            <?php esc_html_e( 'Unlist this post', 'rodolfo' ); ?>
        </label>
    </div>
    <p class="description" style="padding:0 12px 6px">
        <?php esc_html_e( 'Hidden from listings, search, and feeds — but still reachable by its direct link.', 'rodolfo' ); ?>
    </p>
    <?php
}
add_action( 'post_submitbox_misc_actions', 'rodolfo_unlist_checkbox' );

/** Persist the flag from the editor, Quick Edit or Bulk Edit. */
function rodolfo_unlist_save( $post_id ) {
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    // Bulk Edit: core has already verified the bulk-posts nonce; re-check it here.
    if ( isset( $_REQUEST['bulk_edit'], $_REQUEST['rodolfo_unlist_bulk'] ) ) {
        if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-posts' ) ) {
            return;
        }
        $value = sanitize_key( wp_unslash( $_REQUEST['rodolfo_unlist_bulk'] ) );
        if ( 'unlist' === $value || 'list' === $value ) {
            rodolfo_unlist_set( $post_id, 'unlist' === $value );
        }
        return;
    }
    if ( ! isset( $_POST['rodolfo_unlist_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rodolfo_unlist_nonce'] ), 'rodolfo_unlist_save' ) ) {
        return;
    }
    rodolfo_unlist_set( $post_id, ! empty( $_POST['rodolfo_unlisted'] ) );
}
add_action( 'save_post', 'rodolfo_unlist_save' );

/** Keep the option clean if an unlisted post is deleted. */
function rodolfo_unlist_cleanup( $post_id ) {
    $ids = array_diff( rodolfo_unlisted_ids(), array( (int) $post_id ) );
    update_option( RODOLFO_UNLIST_OPTION, array_values( $ids ) );
}
add_action( 'before_delete_post', 'rodolfo_unlist_cleanup' );

/**
 * Exclude unlisted posts from front-end listing queries. Singular views and the
 * admin are left untouched so the direct URL and editing keep working.
 */
function rodolfo_unlist_exclude( $query ) {
    if ( is_admin() || $query->is_singular() ) {
        return;
    }
    if ( ! apply_filters( 'rodolfo_unlist_enabled', true ) ) {
        return;
    }
    $ids = rodolfo_unlisted_ids();
    if ( empty( $ids ) ) {
        return;
    }
    $existing = (array) $query->get( 'post__not_in' );
    $query->set( 'post__not_in', array_merge( $existing, $ids ) );
}
add_action( 'pre_get_posts', 'rodolfo_unlist_exclude' );

/** noindex an unlisted post's own single view. */
function rodolfo_unlist_robots( $robots ) {
    if ( is_singular() && get_post_meta( get_queried_object_id(), RODOLFO_UNLIST_META, true ) ) {
        $robots['noindex']  = true;
        $robots['nofollow'] = false;
    }
    return $robots;
}
add_filter( 'wp_robots', 'rodolfo_unlist_robots' );

/* ---- Admin list table: column, quick-edit toggle, filter (Phase 6.3) ---- */

/** "Unlisted" column on the Posts list table. */
function rodolfo_unlist_column( $columns ) {
    $columns['rodolfo_unlisted'] = __( 'Unlisted', 'rodolfo' );
    return $columns;
}
function rodolfo_unlist_register_columns() {
    foreach ( rodolfo_unlist_types() as $type ) {
        add_filter( "manage_{$type}_posts_columns", 'rodolfo_unlist_column' );
        add_action( "manage_{$type}_posts_custom_column", 'rodolfo_unlist_column_cell', 10, 2 );
    }
}
add_action( 'admin_init', 'rodolfo_unlist_register_columns' );

/**
 * Column cell. The data attribute is what the quick-edit JS reads to prefill
 * the checkbox — WordPress gives quick-edit no access to meta otherwise.
 */
function rodolfo_unlist_column_cell( $column, $post_id ) {
    if ( 'rodolfo_unlisted' !== $column ) {
        return;
    }
    $unlisted = in_array( (int) $post_id, rodolfo_unlisted_ids(), true );
    printf(
        '<span class="tk-unlist-state" data-unlisted="%s">%s</span>',
        $unlisted ? '1' : '0',
        $unlisted ? esc_html__( 'Unlisted', 'rodolfo' ) : '—'
    );
}

/** Quick-edit checkbox (rendered once per column, standard WP behaviour). */
function rodolfo_unlist_quick_edit( $column, $post_type ) {
    if ( 'rodolfo_unlisted' !== $column || ! in_array( $post_type, rodolfo_unlist_types(), true ) ) {
        return;
    }
    wp_nonce_field( 'rodolfo_unlist_save', 'rodolfo_unlist_nonce' );
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="alignleft">
                <input type="checkbox" name="rodolfo_unlisted" value="1" />
                <span class="checkbox-title"><?php esc_html_e( 'Unlist this post', 'rodolfo' ); ?></span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'quick_edit_custom_box', 'rodolfo_unlist_quick_edit', 10, 2 );

/** Prefill JS, only on the Posts list table. */
function rodolfo_unlist_admin_assets( $hook ) {
    if ( 'edit.php' !== $hook || ! in_array( get_current_screen()->post_type, rodolfo_unlist_types(), true ) ) {
        return;
    }
    $suffix = RODOLFO_ASSET_SUFFIX; // Only .min files ship; SCRIPT_DEBUG must not point at missing sources.
    wp_enqueue_script(
        'tk-unlist-quick-edit',
        get_template_directory_uri() . '/assets/js/unlist-quick-edit' . $suffix . '.js',
        array( 'inline-edit-post' ),
        RODOLFO_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'rodolfo_unlist_admin_assets' );

/** Filter dropdown above the list table. */
function rodolfo_unlist_filter_dropdown( $post_type ) {
    if ( ! in_array( $post_type, rodolfo_unlist_types(), true ) ) {
        return;
    }
    $current = isset( $_GET['rodolfo_unlist_filter'] ) ? sanitize_key( $_GET['rodolfo_unlist_filter'] ) : '';
    ?>
    <select name="rodolfo_unlist_filter">
        <option value=""><?php esc_html_e( 'Listed & unlisted', 'rodolfo' ); ?></option>
        <option value="unlisted" <?php selected( 'unlisted', $current ); ?>><?php esc_html_e( 'Unlisted only', 'rodolfo' ); ?></option>
    </select>
    <?php
}
add_action( 'restrict_manage_posts', 'rodolfo_unlist_filter_dropdown' );

/** Apply the filter — admin list table only, never the front end. */
function rodolfo_unlist_filter_query( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( isset( $_GET['rodolfo_unlist_filter'] ) && 'unlisted' === sanitize_key( $_GET['rodolfo_unlist_filter'] ) ) {
        $ids = rodolfo_unlisted_ids();
        // post__in with an empty array is ignored by WP_Query; force no rows.
        $query->set( 'post__in', $ids ? $ids : array( 0 ) );
    }
}
add_action( 'pre_get_posts', 'rodolfo_unlist_filter_query' );

/** Bulk Edit: change several posts at once. */
function rodolfo_unlist_bulk_edit( $column, $post_type ) {
    if ( 'rodolfo_unlisted' !== $column || ! in_array( $post_type, rodolfo_unlist_types(), true ) ) {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <span class="title"><?php esc_html_e( 'Unlisted', 'rodolfo' ); ?></span>
                <select name="rodolfo_unlist_bulk">
                    <option value=""><?php esc_html_e( '— No change —', 'rodolfo' ); ?></option>
                    <option value="unlist"><?php esc_html_e( 'Unlist', 'rodolfo' ); ?></option>
                    <option value="list"><?php esc_html_e( 'List again', 'rodolfo' ); ?></option>
                </select>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action( 'bulk_edit_custom_box', 'rodolfo_unlist_bulk_edit', 10, 2 );

/** "Unlisted" label next to the title in the list table. */
function rodolfo_unlist_post_state( $states, $post ) {
    if ( in_array( (int) $post->ID, rodolfo_unlisted_ids(), true ) ) {
        $states['rodolfo_unlisted'] = __( 'Unlisted', 'rodolfo' );
    }
    return $states;
}
add_filter( 'display_post_states', 'rodolfo_unlist_post_state', 10, 2 );

/* ---- Everything that bypasses WP_Query ---- */

/** Pages: get_pages() (navigation, HTML sitemap) and wp_list_pages(). */
function rodolfo_unlist_filter_pages( $pages ) {
    if ( is_admin() || ! apply_filters( 'rodolfo_unlist_enabled', true ) ) {
        return $pages;
    }
    $ids = rodolfo_unlisted_ids();
    if ( ! $ids ) {
        return $pages;
    }
    return array_values( array_filter( $pages, function ( $p ) use ( $ids ) {
        return ! in_array( (int) $p->ID, $ids, true );
    } ) );
}
add_filter( 'get_pages', 'rodolfo_unlist_filter_pages' );

function rodolfo_unlist_list_pages_excludes( $excludes ) {
    return apply_filters( 'rodolfo_unlist_enabled', true ) ? array_merge( (array) $excludes, rodolfo_unlisted_ids() ) : $excludes;
}
add_filter( 'wp_list_pages_excludes', 'rodolfo_unlist_list_pages_excludes' );

/** Previous / next post links never point at an unlisted post. */
function rodolfo_unlist_adjacent_where( $where ) {
    $ids = rodolfo_unlisted_ids();
    if ( is_admin() || ! $ids || ! apply_filters( 'rodolfo_unlist_enabled', true ) ) {
        return $where;
    }
    return $where . ' AND p.ID NOT IN ( ' . implode( ', ', $ids ) . ' ) ';
}
add_filter( 'get_next_post_where', 'rodolfo_unlist_adjacent_where', 20 );
add_filter( 'get_previous_post_where', 'rodolfo_unlist_adjacent_where', 20 );
