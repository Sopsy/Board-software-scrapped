<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\Boards;
use YBoard\Model\PostReports;
use YBoard\Model\Posts;

class Mod extends ExtendedController
{
    public function banForm()
    {
        $this->modOnly();
        $this->validateAjaxCsrfToken();

        if (empty($_POST['post_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        $boards = new Boards($this->db);
        $reports = new PostReports($this->db);

        $post = $posts->get($_POST['post_id'], false);
        if (!$post->threadId) {
            $thread = $post;
        } else {
            $thread = $posts->get($post->threadId, false);
        }
        $board = $boards->getById($thread->boardId);

        $view = $this->loadTemplateEngine('Blank');
        $view->post = $post;
        $view->thread = $thread;
        $view->board = $board;
        $view->banReasons = $reports->getReasons(true);

        $view->display('Ajax/Mod/BanForm');
    }

    public function addBan()
    {
        $this->modOnly();
        $this->validateAjaxCsrfToken();

        // Require either UID or IP. Or both.
        if (empty($_POST['ban_ip']) && empty($_POST['ban_user'])) {
            $this->throwJsonError(400, _('Please fill all the required fields'));
        }

        if (empty($_POST['ban_reason']) || empty($_POST['ban_length'])) {
            $this->throwJsonError(400, _('Please fill all the required fields'));
        }


    }
}
