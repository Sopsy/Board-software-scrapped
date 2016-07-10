<?php

namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\UserException;
use YBoard\Library\HttpResponse;
use YBoard\Library\ReCaptcha;
use YBoard\Model\Posts;

class User extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();
        $view->pageTitle = _('Your profile');

        $view->display('Profile');
    }

    public function login()
    {
        if (!$this->isPostRequest() || empty($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            $this->badRequest();
        }

        if (isset($_POST['login'])) {
            $this->doLogin();
        } elseif (isset($_POST['signup'])) {
            $this->doSignup();
        } else {
            $this->badRequest();
        }

        // Redirect after a successful login or signup
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

        $destroySession = $this->user->destroySession();
        if (!$destroySession) {
            $this->dieWithError(_('What the!? Can\'t logout!?'));
        }

        $this->deleteLoginCookie(false);
        HttpResponse::redirectExit('/');
    }

    public function delete()
    {
        if (!$this->isPostRequest() || empty($_POST['csrf_token']) || !$this->validateCsrfToken($_POST['csrf_token'])) {
            $this->badRequest();
        }

        if (!empty($_POST['delete_posts'])) {
            $posts = new Posts($this->db);
            $posts->deleteByUser($this->user->id);
        }

        try {
            $this->user->delete($this->user->id, $_POST['password']);
        } catch (UserException $e) {
            $this->badRequest(_('User account not deleted'), $e->getMessage());
        }

        $this->deleteLoginCookie(false);
        HttpResponse::redirectExit('/');
    }

    protected function doLogin()
    {

        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->badRequest(_('Login failed'), _('Invalid username or password.'));
        }

        $login = $this->user->validateLogin($_POST['username'], $_POST['password']);

        if (!$login) {
            $this->blockAccess(_('Login failed'), _('Invalid username or password.'));
        }

        $this->user->destroySession();
        $this->user->createSession($this->user->id);

        $this->setLoginCookie($this->user->sessionId);

        if ($this->user->class != 0) {
            // TODO: write mod login to log
        }

        return true;
    }

    protected function doSignup()
    {
        if ($this->user->loggedIn) {
            $this->badRequest(_('Signup failed'), _('You are already logged in.'));
        }

        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['repassword'])) {
            $this->badRequest(_('Signup failed'), _('Missing username or password.'));
        }

        if ($_POST['password'] !== $_POST['repassword']) {
            $this->badRequest(_('Signup failed'), _('The two passwords do not match.'));
        }

        if (empty($_POST["g-recaptcha-response"])) {
            $this->badRequest(_('Signup failed'), _('Please fill the CAPTCHA.'));
        }

        $captchaOk = ReCaptcha::verify($_POST["g-recaptcha-response"], $this->config['reCaptcha']['privateKey']);
        if (!$captchaOk) {
            $this->badRequest(_('Signup failed'), _('Invalid CAPTCHA response. Please try again.'));
        }

        if (!$this->user->usernameIsFree($_POST['username'])) {
            $this->badRequest(_('Signup failed'), _('This username is already taken. Please choose another one.'));
        }

        $this->user->setUsername($_POST['username']);
        $this->user->setPassword($_POST['password']);

        return true;
    }
}
