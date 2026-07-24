<?php
/**
 * Comments template
 *
 * @package Rodolfo
 */

if ( post_password_required() ) {
    return;
}
?>

<div class="max-w-4xl mx-auto mt-8 px-0">

    <?php if ( have_comments() ) : ?>
        <div class="bg-white dark:bg-slate-900 rounded-xl md:rounded-2xl border border-slate-100 dark:border-slate-800 overflow-hidden mb-8">
            <div class="p-6 md:p-10 lg:p-12">

                <h2 class="flex items-center gap-2.5 text-2xl font-bold font-display text-slate-900 dark:text-white mb-8">
                    <?php echo rodolfo_icon( 'message-circle', 'w-6 h-6 text-slate-400' ); ?>
                    <?php
                    $comment_count = get_comments_number();
                    printf(
                        esc_html( _n( '%s comment', '%s comments', $comment_count, 'rodolfo' ) ),
                        number_format_i18n( $comment_count )
                    );
                    ?>
                </h2>

                <ol class="list-none space-y-8">
                    <?php
                    wp_list_comments( array(
                        'style'       => 'ol',
                        'short_ping'  => true,
                        'avatar_size' => 44,
                        'callback'    => 'rodolfo_comment',
                    ) );
                    ?>
                </ol>

                <?php the_comments_navigation( array(
                    'prev_text' => rodolfo_icon( 'chevron-left', 'w-4 h-4 inline' ) . ' ' . esc_html__( 'Older comments', 'rodolfo' ),
                    'next_text' => esc_html__( 'Newer comments', 'rodolfo' ) . ' ' . rodolfo_icon( 'chevron-right', 'w-4 h-4 inline' ),
                ) ); ?>

            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-8"><?php esc_html_e( 'Comments are closed.', 'rodolfo' ); ?></p>
    <?php endif; ?>

    <?php
    comment_form( array(
        'title_reply'          => esc_html__( 'Leave a comment', 'rodolfo' ),
        'title_reply_to'       => esc_html__( 'Reply to %s', 'rodolfo' ),
        'cancel_reply_link'    => esc_html__( 'Cancel reply', 'rodolfo' ),
        'label_submit'         => esc_html__( 'Post comment', 'rodolfo' ),
        'class_form'           => 'space-y-5',
        'class_submit'         => 'inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 cursor-pointer',
        'comment_field'        => '<p class="comment-form-comment"><label for="comment" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">' . esc_html__( 'Comment', 'rodolfo' ) . ' <span class="text-red-500">*</span></label><textarea id="comment" name="comment" rows="6" required class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 text-sm p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-y"></textarea></p>',
        'fields'               => array(
            'author' => '<p class="comment-form-author"><label for="author" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">' . esc_html__( 'Name', 'rodolfo' ) . ' <span class="text-red-500">*</span></label><input id="author" name="author" type="text" required autocomplete="name" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 text-sm px-3 h-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" /></p>',
            'email'  => '<p class="comment-form-email"><label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">' . esc_html__( 'Email', 'rodolfo' ) . ' <span class="text-red-500">*</span></label><input id="email" name="email" type="email" required autocomplete="email" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 text-sm px-3 h-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" /></p>',
            'url'    => '<p class="comment-form-url"><label for="url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">' . esc_html__( 'Website', 'rodolfo' ) . '</label><input id="url" name="url" type="url" autocomplete="url" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 text-sm px-3 h-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors" /></p>',
        ),
        'comment_notes_before' => '',
        'comment_notes_after'  => '',
    ) );
    ?>

</div>
