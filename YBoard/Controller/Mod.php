<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\Bans;
use YBoard\Model\Boards;
use YBoard\Model\PostReports;
use YBoard\Model\Posts;
use YBoard\Model\Users;

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
        $view->banReasons = Bans::getReasons(true);

        $view->display('Mod/BanForm');
    }

    public function addBan()
    {
        $this->modOnly();
        $this->validateAjaxCsrfToken();

        $banIp = empty($_POST['ban_ip']) ? null : filter_var($_POST['ban_ip'], FILTER_VALIDATE_IP);
        $banUser = empty($_POST['ban_user']) ? null : (int)$_POST['ban_user'];
        $postId = empty($_POST['ban_post_id']) ? null : (int)$_POST['ban_post_id'];
        $postId = empty($postId) ? null : $postId; // So invalid value just gets a NULL instead of 0
        $additionalInfo = empty($_POST['ban_additional_info']) ? null : mb_substr($_POST['ban_additional_info'], 0, 120);
        $banLength = empty($_POST['ban_length']) ? null : (int)$_POST['ban_length'];
        $banReason = empty($_POST['ban_reason']) ? null : (int)$_POST['ban_reason'];

        if ($banIp === false) {
            $this->throwJsonError(400, _('Invalid IP-address'));
        }

        // Verify user
        if (!empty($banUser)) {
            $users = new Users($this->db);
            $user = $users->getById($_POST['ban_user']);
            if ($user === false) {
                $this->throwJsonError(400, _('User does not exist, maybe add the ban without the user?'));
            }
        }

        // Require either UID or IP. Or both.
        if (empty($banIp) && empty($banUser)) {
            $this->throwJsonError(400, _('Please fill all the required fields'));
        }

        if (empty($banReason) || empty($banLength)) {
            $this->throwJsonError(400, _('Please fill all the required fields'));
        }

        $bans = new Bans($this->db);
        $bans->add($banIp, $banUser, $banLength, $banReason, $additionalInfo, $postId, $this->user->id);

        if (empty($postId)) {
            return true;
        }

        if (!empty($_POST['ban_delete_post'])) {
            // Delete posts?
            $posts = new Posts($this->db);
            $post = $posts->get($_POST['ban_post_id'], false);
            if ($post !== false) {
                if (!empty($_POST['ban_delete_posts_24h'])) {
                    $posts->deleteByUser($post->userId, 24);
                }
                $post->delete();
            }
        } else {
            // Mark any possible reports as checked
            $reports = new PostReports($this->db);
            $report = $reports->get($postId);
            if ($report !== false) {
                $report->setChecked($this->user->id);
            }
        }
    }
}
