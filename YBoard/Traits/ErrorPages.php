<?php
namespace YBoard\Traits;

trait ErrorPages
{
    protected function badRequest($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Bad request') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Your request did not complete because it contained invalid information.') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 400);
    }

    protected function blockAccess($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Bad request') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Your request did not complete because it contained invalid information.') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 403);
    }

    public function notFound($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('404 Not Found') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Whatever you were looking for does not exist here. Probably never did.') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 404);
    }

    protected function internalError($errorTitle = false, $errorMessage = false)
    {
        $errorTitle = empty($errorTitle) ? _('Oh noes!') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('We\'re terribly sorry. An internal error occurred when we tried to complete your request.') : $errorMessage;
        $this->dieWithMessage($errorTitle, $errorMessage, 500);
    }
}
