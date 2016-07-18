<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Model\Bans;
use YBoard\Model\PostReports;
use YBoard\Model\Posts;

class PostReport extends ExtendedController
{
    public function uncheckedReports()
    {
        $this->modOnly();

        $postReports = new PostReports($this->db);
        $view = $this->loadTemplateEngine();
        $view->pageTitle = _('Reports');

        $view->reports = $postReports->getUnchecked();
        $view->display('Mod/Reports');
    }

    public function setChecked()
    {
        $this->modOnly();
        $this->validateAjaxCsrfToken();

        if (empty($_POST['post_id'])) {
            $this->throwJsonError(400);
        }

        $reports = new PostReports($this->db);
        $report = $reports->get($_POST['post_id']);
        if ($report === false) {
            $this->throwJsonError(404, _('Report does not exist'));
        }

        $report->setChecked($this->user->id);
    }

    public function getForm()
    {
        $view = $this->loadTemplateEngine('Blank');

        $view->reasons = Bans::getReasons();
        $view->display('Ajax/ReportForm');
    }

    public function submit()
    {
        $this->validateAjaxCsrfToken();

        if ($this->user->ban) {
            $this->throwJsonError(403, _('You are banned!'));
        }

        if (empty($_POST['post_id']) || empty($_POST['reason_id'])) {
            $this->throwJsonError(400);
        }

        $posts = new Posts($this->db);
        if ($posts->get($_POST['post_id'], false) === false) {
            $this->throwJsonError(404, _('Post does not exist'));
        }

        $postReports = new PostReports($this->db);

        if ($postReports->isReported($_POST['post_id'])) {
            $this->throwJsonError(418, _('This message has already been reported and is waiting for a review'));
        }

        $additionalInfo = empty($_POST['report_additional_info']) ? null : mb_substr($_POST['report_additional_info'], 0, 120);
        $postReports->add($_POST['post_id'], $_POST['reason_id'], $additionalInfo);
    }
}
