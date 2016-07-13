<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;
use YBoard\Library\ReCaptcha;
use YBoard\Model\UserSessions;

class Session extends ExtendedController
{
    public function logIn()
    {
        $this->validatePostCsrfToken();

        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->badRequest(_('Login failed'), _('Invalid username or password'));
        }

        $newUser = $this->user->getByLogin($_POST['username'], $_POST['password']);
        if (!$newUser) {
            $this->blockAccess(_('Login failed'), _('Invalid username or password'));
        }

        $this->user->session->destroy();

        $newUser->session = new UserSessions($this->db, $newUser->id);
        $newUser->session->create();

        $this->setLoginCookie($newUser->id, $newUser->session->id);

        if ($newUser->class != 0) {
            // TODO: write mod login to log
        }

        // Redirect
        if (empty($_POST['redirto'])) {
            HttpResponse::redirectExit('/');
        } else {
            HttpResponse::redirectExit(urldecode($_POST['redirto']));
        }
    }

    public function logOut()
    {
        $this->validatePostCsrfToken();

        $destroySession = $this->user->session->destroy();
        if (!$destroySession) {
            $this->dieWithError(_('What the!? Can\'t logout!?'));
        }

        $this->deleteLoginCookie();
        HttpResponse::redirectExit('/');
    }

    public function signUp()
    {
        $this->validatePostCsrfToken();

        if ($this->user->loggedIn) {
            $this->badRequest(_('Signup failed'), _('You are already logged in'));
        }

        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['repassword'])) {
            $this->badRequest(_('Signup failed'), _('Missing username or password'));
        }

        if ($_POST['password'] !== $_POST['repassword']) {
            $this->badRequest(_('Signup failed'), _('The two passwords do not match'));
        }

        if (empty($_POST["g-recaptcha-response"])) {
            $this->badRequest(_('Signup failed'), _('Please fill the CAPTCHA'));
        }

        $captchaOk = ReCaptcha::verify($_POST["g-recaptcha-response"], $this->config['reCaptcha']['privateKey']);
        if (!$captchaOk) {
            $this->badRequest(_('Signup failed'), _('Invalid CAPTCHA response, please try again'));
        }

        if (mb_strlen($_POST['username']) > $this->config['view']['usernameMaxLength']) {
            $this->badRequest(_('Signup failed'), _('Username is too long'));
        }

        if (!$this->user->usernameIsFree($_POST['username'])) {
            $this->badRequest(_('Signup failed'), _('This username is already taken, please choose another one'));
        }

        $this->user->setUsername($_POST['username']);
        $this->user->setPassword($_POST['password']);

        // Redirect
        if (empty($_POST['redirto'])) {
            HttpResponse::redirectExit('/');
        } else {
            HttpResponse::redirectExit(urldecode($_POST['redirto']));
        }
    }
}
