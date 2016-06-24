<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;

class LogInOut extends ExtendedController
{
    public function login()
    {
        // TODO: Do something here
    }
    public function logout()
    {
        if (empty($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
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
