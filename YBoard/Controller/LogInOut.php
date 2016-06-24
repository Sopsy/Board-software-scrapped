<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;

class LogInOut extends ExtendedController
{
    public function login()
    {
        if (!$this->isPostRequest() || empty($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            $this->badRequest();
        }

        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->badRequest();
        }

        // TODO: Do something here

    }

    public function logout()
    {
        if (!$this->isPostRequest() || empty($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            $this->badRequest();
        }

        $destroySession = $this->user->destroyCurrentSession();
        if (!$destroySession) {
            $this->dieWithError(_('What the!? Can\'t logout!?'));
        }

        $this->deleteLoginCookie(false);
        HttpResponse::redirectExit('/');
    }
}
