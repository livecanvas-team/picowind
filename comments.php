<?php
/**
 * Custom Comments Template
 *
 * @link https://developer.wordpress.org/themes/classic-themes/templates/partial-and-miscellaneous-template-files/
 *
 * @package Picowind
 * @subpackage Picowind
 * @since 1.0.0
 */

namespace Picowind;

if (post_password_required()) {
    return;
}

$comments_open = comments_open();

$context = [
    'comments' => get_comments([
        'post_id' => get_the_ID(),
        'status' => 'approve',
        'hierarchical' => 'threaded',
    ]),
    'comment_count' => get_comments_number(),
    'comments_open' => $comments_open,
    'post_id' => get_the_ID(),
    'show_closed_message' => ! $comments_open && post_type_supports(get_post_type(), 'comments'),
];

render('components/comments.twig', $context, null, true, true);
