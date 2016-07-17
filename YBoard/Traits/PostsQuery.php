<?php
namespace YBoard\Traits;

trait PostsQuery
{
    protected function getPostsQuery(string $append = '') : string
    {
        return "SELECT
            a.id, a.board_id, a.thread_id, user_id, ip, country_code, time, locked, sticky, username, subject, message,
            b.file_name AS file_display_name, c.id AS file_id, c.folder AS file_folder, c.name AS file_name,
            c.extension AS file_extension, c.size AS file_size, c.width AS file_width, c.height AS file_height,
            c.duration AS file_duration, c.has_thumbnail AS file_has_thumbnail, c.has_sound AS file_has_sound,
            c.is_gif AS file_is_gif, c.in_progress AS file_in_progress, d.read_count, d.reply_count,
            d.distinct_reply_count, e.url AS board_url,
            (SELECT GROUP_CONCAT(post_id) FROM posts_replies WHERE post_id_replied = a.id) AS post_replies
            FROM posts a
            LEFT JOIN posts_files b ON a.id = b.post_id
            LEFT JOIN files c ON b.file_id = c.id
            LEFT JOIN thread_statistics d ON a.id = d.thread_id
            LEFT JOIN boards e ON e.id = a.board_id
            " . $append;
    }
}
