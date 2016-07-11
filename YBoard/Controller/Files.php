<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Library\TemplateEngine;
use YBoard\Traits\Ajax;
use YBoard\Traits\PostChecks;

class Files Extends Controller
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

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'Blank');
        $view->fileUrl = urlencode($_POST['file_url']);
        $view->poster = urlencode($_POST['poster']);
        $view->loop = empty($_POST['loop']) ? false : true;

        $view->display('Ajax/MediaPlayer');
    }
}
