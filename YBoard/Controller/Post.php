<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Model;
use YFW\Library\HttpResponse;

class Post extends Controller
{
    public function redirect(int $postId): void
    {
        $post = Model\Post::get($this->db, $postId, false);

        if ($post === null) {
            $this->notFound(_('Not found'), _('Post does not exist'));
        }

        if (!$post->boardId) {
            $thread = Model\Thread::get($this->db, $post->threadId, false);

            if ($thread === null) {
                $this->internalError();
            }

            $boardId = $thread->boardId;
        } else {
            $boardId = $post->boardId;
        }

        $board = Model\Board::getById($this->db, $boardId);
        if (!$board) {
            $this->internalError();
        }

        if (empty($post->threadId)) {
            $thread = $post->id;
            $hash = '';
        } else {
            $thread = $post->threadId;
            $hash = '#post-' . $post->id;
        }

        HttpResponse::redirectExit('/' . $board->url . '/' . $thread . $hash, 301);
    }
}
