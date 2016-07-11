<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Library\Database;
use YBoard\Library\TemplateEngine;
use YBoard\Model\Files;
use YBoard\Traits\Ajax;
use YBoard\Traits\PostChecks;

class File Extends Controller
{
    use PostChecks;
    use Ajax;

    protected $config;
    protected $db;

    public function __construct()
    {
        $this->config = require(ROOT_PATH . '/YBoard/Config/YBoard.php');
        $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));
    }

    public function getMediaPlayer()
    {
        $this->disallowNonPost();

        if (empty($_POST['file_id'])) {
            $this->invalidAjaxData();
        }

        $files = new Files($this->db);
        $file = $files->get($_POST['file_id']);

        if (!$file) {
            $this->throwJsonError(404);
        }
        if ($file->inProgress) {
            $this->throwJsonError(418, _('This file is being processed...'));
        }

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'Blank');
        $view->fileUrl = $this->config['view']['staticUrl'] . '/files/' . $file->folder . '/o/' . $file->name . '/'
            . '1.' . $file->extension;
        $view->poster = $this->config['view']['staticUrl'] . '/files/' . $file->folder . '/t/' . $file->name . '.jpg';

        $view->loop = $file->isGif;

        $view->display('Ajax/MediaPlayer');
    }
}
