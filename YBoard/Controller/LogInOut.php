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
            $this->badRequest(_('Login failed'), _('Invalid username or password.'));
        }

        $login = $this->user->validateLogin($_POST['username'], $_POST['password']);

        if (!$login) {
            $this->blockAccess(_('Login failed'), _('Invalid username or password.'));
        }

        $this->user->destroyCurrentSession();
        $this->user->createSession($this->user->id);

        $this->setLoginCookie($this->user->sessionId);

        if ($this->user->class != 0) {
            // TODO: write mod login to log
        }

        // Update password hash on each login
        $this->user->setPassword($_POST['password'], $this->user->id);

        if (empty($_POST['redirto'])) {
            HttpResponse::redirectExit('/');
        } else {
            HttpResponse::redirectExit(urldecode($_POST['redirto']));
        }
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
