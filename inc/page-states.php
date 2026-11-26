<?php
/**
 * Page states and freshness signals.
 *
 * Two small features that reward publishing in the open:
 *
 * 1. **Coming soon pages.** A Page can be marked as planned-but-unwritten. It
 *    stays in the navigation and states what it will contain, instead of being
 *    hidden until it is perfect. This is the deliberate inverse of
 *    inc/unlist.php, which hides a published post from listings while keeping
 *    its URL live; here the page is visible and listed but its body is replaced
 *    by a short notice.
 *
 * 2. **Site updated stamp.** A site-wide "Updated <date>" showing when the most
 *    recent post was *published*. Publication date, not modified date: editing
 *    a typo in an old post should not make the whole site look freshly updated,
 *    and readers reasonably read the stamp as "there is new writing here as of
 *    this date". The theme already surfaces per-post modified dates for AEO
 *    reasons (answer engines quote visible dates); this is the site-level
 *    equivalent, and costs one cached query.
 *
 * @package Rodolfo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// COMING SOON PAGES
// ============================================================

/** Is this page flagged as coming soon? */
function rodolfo_is_coming_soon( $post = null ) {
    $post = get_post( $post );
    if ( ! $post || 'page' !== $post->post_type ) {
        return false;
    }
    return (bool) get_post_meta( $post->ID, '_rodolfo_coming_soon', true );
}

/** The owner's note about what the page will contain, if any. */
function rodolfo_coming_soon_note( $post = null ) {
    $post = get_post( $post );
    return $post ? (string) get_post_meta( $post->ID, '_rodolfo_coming_soon_note', true ) : '';
}

/**
 * Replace the body of a coming-soon page with the notice.
 *
 * Runs late on the_content so other filters (shortcodes, embeds) have already
 * done their work and are simply discarded — cheaper to reason about than
 * trying to unhook them. Only affects the main query on the page itself, so a
 * nav listing or search result still shows the real title.
 */
function rodolfo_coming_soon_content( $content ) {
    if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    if ( ! rodolfo_is_coming_soon() ) {
        return $content;
    }

    $note = rodolfo_coming_soon_note();
    if ( '' === $note ) {
        $note = __( 'This page is being written. Check back soon.', 'rodolfo' );
    }

    $html  = '<div class="tk-coming-soon not-prose my-8 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 p-8 text-center">';
    $html .= '<p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-3">' . esc_html__( 'Coming soon', 'rodolfo' ) . '</p>';
    $html .= '<p class="text-slate-600 dark:text-slate-300 max-w-prose mx-auto">' . esc_html( $note ) . '</p>';
    $html .= '</div>';

    /** Filter the coming-soon notice markup. */
    return apply_filters( 'rodolfo_coming_soon_notice', $html, $note, get_post() );
}
add_filter( 'the_content', 'rodolfo_coming_soon_content', 20 );

/**
 * Keep coming-soon pages out of search results: they have no content to rank,
 * and indexing an empty page is worse than not indexing it. They stay in the
 * navigation, which is the point of the feature.
 *
 * Implemented through core's `wp_robots` filter rather than by echoing a
 * `<meta name="robots">` tag. SEO plugins emit their own robots tag, and two
 * tags on one page is an unpredictable conflict — crawlers may take the most
 * restrictive, so ours could silently override a plugin's deliberate `index`.
 * Filtering the shared array instead means exactly one tag is emitted, and a
 * plugin that also filters it can still have the final say.
 */
function rodolfo_coming_soon_robots( $robots ) {
    if ( is_singular( 'page' ) && rodolfo_is_coming_soon() ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}
add_filter( 'wp_robots', 'rodolfo_coming_soon_robots' );

/** Editor control: a checkbox plus a one-line note, in the Publish box. */
function rodolfo_coming_soon_meta_box() {
    add_meta_box(
        'rodolfo_coming_soon',
        __( 'Coming soon', 'rodolfo' ),
        'rodolfo_coming_soon_meta_box_render',
        'page',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'rodolfo_coming_soon_meta_box' );

function rodolfo_coming_soon_meta_box_render( $post ) {
    wp_nonce_field( 'rodolfo_coming_soon_save', 'rodolfo_coming_soon_nonce' );
    $on   = rodolfo_is_coming_soon( $post );
    $note = rodolfo_coming_soon_note( $post );
    ?>
    <p>
        <label>
            <input type="checkbox" name="rodolfo_coming_soon" value="1" <?php checked( $on ); ?> />
            <?php esc_html_e( 'Mark this page as coming soon', 'rodolfo' ); ?>
        </label>
    </p>
    <p>
        <label for="rodolfo_coming_soon_note"><?php esc_html_e( 'What will it contain?', 'rodolfo' ); ?></label>
        <textarea id="rodolfo_coming_soon_note" name="rodolfo_coming_soon_note" rows="3" class="widefat"
                  placeholder="<?php esc_attr_e( 'Timestamped predictions, starting now. Publishing the first set soon.', 'rodolfo' ); ?>"><?php echo esc_textarea( $note ); ?></textarea>
    </p>
    <p class="description">
        <?php esc_html_e( 'The page stays in your navigation but its content is replaced by this note, and it is set to noindex. Saying what is planned reads better than hiding the page.', 'rodolfo' ); ?>
    </p>
    <?php
}

function rodolfo_coming_soon_save( $post_id ) {
    if ( ! isset( $_POST['rodolfo_coming_soon_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['rodolfo_coming_soon_nonce'] ) ), 'rodolfo_coming_soon_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_page', $post_id ) ) {
        return;
    }

    if ( empty( $_POST['rodolfo_coming_soon'] ) ) {
        delete_post_meta( $post_id, '_rodolfo_coming_soon' );
    } else {
        update_post_meta( $post_id, '_rodolfo_coming_soon', '1' );
    }

    $note = isset( $_POST['rodolfo_coming_soon_note'] )
        ? sanitize_textarea_field( wp_unslash( $_POST['rodolfo_coming_soon_note'] ) )
        : '';
    if ( '' === $note ) {
        delete_post_meta( $post_id, '_rodolfo_coming_soon_note' );
    } else {
        update_post_meta( $post_id, '_rodolfo_coming_soon_note', $note );
    }
}
add_action( 'save_post_page', 'rodolfo_coming_soon_save' );

// ============================================================
// SITE UPDATED STAMP
// ============================================================

/**
 * Publication timestamp of the newest published post, cached for an hour.
 * Returns 0 when the site has no posts yet.
 *
 * Ordered and read by publish date rather than modified date on purpose: a
 * stamp driven by `modified` jumps to today whenever any old post is touched,
 * which overstates freshness. Filter `rodolfo_site_updated_time` if a site
 * genuinely wants last-touched semantics instead.
 */
function rodolfo_site_updated_time() {
    $mode   = rodolfo_site_updated_mode();
    $cached = get_transient( 'rodolfo_site_updated_' . $mode );
    if ( false !== $cached ) {
        return (int) $cached;
    }

    $latest = get_posts( array(
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'posts_per_page'   => 1,
        'orderby'          => ( 'modified' === $mode ) ? 'modified' : 'date',
        'order'            => 'DESC',
        'fields'           => 'ids',
        'suppress_filters' => false,
    ) );

    $time = 0;
    if ( $latest ) {
        $time = ( 'modified' === $mode )
            ? (int) get_post_modified_time( 'U', true, $latest[0] )
            : (int) get_post_time( 'U', true, $latest[0] );
    }

    /** Filter the site-updated timestamp (Unix, GMT). */
    $time = (int) apply_filters( 'rodolfo_site_updated_time', $time );

    set_transient( 'rodolfo_site_updated_' . $mode, $time, HOUR_IN_SECONDS );

    return $time;
}

/**
 * Timestamp of the newest *modified* published post, irrespective of the
 * display mode. Schema's dateModified means "when content last changed", so it
 * must not follow the owner's cosmetic choice of what the footer stamp shows.
 * Cached under its own key.
 */
function rodolfo_site_modified_time() {
    $cached = get_transient( 'rodolfo_site_updated_modified' );
    if ( false !== $cached ) {
        return (int) $cached;
    }
    $latest = get_posts( array(
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'posts_per_page'   => 1,
        'orderby'          => 'modified',
        'order'            => 'DESC',
        'fields'           => 'ids',
        'suppress_filters' => false,
    ) );
    $time = $latest ? (int) get_post_modified_time( 'U', true, $latest[0] ) : 0;
    set_transient( 'rodolfo_site_updated_modified', $time, HOUR_IN_SECONDS );
    return $time;
}

/** Which date the stamp reports: 'published' (default) or 'modified'. */
function rodolfo_site_updated_mode() {
    $mode = (string) get_option( 'rodolfo_site_updated_mode', 'published' );
    return ( 'modified' === $mode ) ? 'modified' : 'published';
}

/**
 * Flush the cached stamp when the newest publication date could have changed.
 *
 * `transition_post_status` is the important one: a scheduled post going live
 * fires it but not save_post, so without this the stamp could lag a freshly
 * published post by up to an hour.
 */
function rodolfo_site_updated_flush() {
    delete_transient( 'rodolfo_site_updated_published' );
    delete_transient( 'rodolfo_site_updated_modified' );
}
add_action( 'save_post_post', 'rodolfo_site_updated_flush' );
add_action( 'deleted_post', 'rodolfo_site_updated_flush' );
add_action( 'transition_post_status', 'rodolfo_site_updated_flush' );

/**
 * "Last post 18 Jul 2026" for the footer, or '' when disabled or empty.
 *
 * Wording matches the semantics: the date is when the newest post was
 * published, so "Updated" would imply the whole site was touched then.
 * A visible date is an AEO signal — answer engines quote what they can see,
 * and schema alone is not cited. Filter `rodolfo_site_updated_stamp` to
 * reword.
 */
function rodolfo_site_updated_stamp() {
    if ( ! get_option( 'rodolfo_site_updated_enabled' ) ) {
        return '';
    }
    $time = rodolfo_site_updated_time();
    if ( ! $time ) {
        return '';
    }
    $date = esc_html( wp_date( get_option( 'date_format' ), $time ) );
    $stamp = ( 'modified' === rodolfo_site_updated_mode() )
        /* translators: %s: date the newest content was last edited */
        ? sprintf( esc_html__( 'Updated %s', 'rodolfo' ), $date )
        /* translators: %s: publication date of the most recent post */
        : sprintf( esc_html__( 'Last post %s', 'rodolfo' ), $date );

    /** Filter the rendered site-updated stamp. */
    return (string) apply_filters( 'rodolfo_site_updated_stamp', $stamp, $time );
}
