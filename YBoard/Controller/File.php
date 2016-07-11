<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Library\TemplateEngine;
use YBoard\Traits\Ajax;
use YBoard\Traits\PostChecks;

class File Extends Controller
{
    use PostChecks;
    use Ajax;

    public function getMediaPlayer()
    {
        $this->disallowNonPost();

        // TODO: This does not validate request source. Might need referrer checks or something.

        if (empty($_POST['file_url']) || empty($_POST['poster'])) {
            $this->invalidAjaxData();
        }

        $loop = empty($_POST['loop']) ? false : $_POST['loop'];

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'Blank');
        $view->fileUrl = htmlspecialchars($_POST['file_url']);
        $view->poster = htmlspecialchars($_POST['poster']);
        $view->loop = $loop == 'true';

        $view->display('Ajax/MediaPlayer');
    }
}
