<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\PostReports;
use YBoard\Model\Posts;

class PostReport extends ExtendedController
{
    public function getForm()
    {
        $postReports = new PostReports($this->db);
        $view = $this->loadTemplateEngine('Blank');

        $view->reasons = $postReports->getReasons();
        $view->display('Ajax/ReportForm');
    }

    public function submit()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['post_id']) || empty($_POST['reason_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        if (!$posts->getMeta($_POST['post_id'])) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        $postReports = new PostReports($this->db);

        if ($postReports->isReported($_POST['post_id'])) {
            $this->throwJsonError(418, _('This message has already been reported'));
        }

        $additionalInfo = empty($_POST['report_additional_info']) ? null : $_POST['report_additional_info'];
        $postReports->add($_POST['post_id'], $_POST['reason_id'], $additionalInfo);
    }
}
