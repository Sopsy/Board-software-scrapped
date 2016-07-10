<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\UserException;
use YBoard\Library\HttpResponse;
use YBoard\Library\ReCaptcha;
use YBoard\Library\Text;
use YBoard\Model\Posts;

class User extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();
        $view->pageTitle = _('Your profile');

        $view->loginSessions = $this->user->getSessions($this->user->id);
        $view->display('Profile');
    }

    public function changeName()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['new_name']) || empty($_POST['password'])) {
            $this->throwJsonError(400);
        }

        if (mb_strlen($_POST['new_name']) > $this->config['view']['usernameMaxLength']) {
            $this->throwJsonError(400, _('Username is too long'));
        }

        if ($this->user->username == $_POST['new_name']) {
            $this->throwJsonError(400, _('This is your current username'));
        }

        if (!$this->user->usernameIsFree($_POST['new_name'])) {
            $this->throwJsonError(400, _('This username is already taken, please choose another one'));
        }

        if (!$this->user->validateLogin($this->user->username, $_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        $this->user->setUsername($_POST['new_name']);
    }

    public function changePassword()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['new_password']) || empty($_POST['password'])) {
            $this->throwJsonError(400);
        }

        if (!$this->user->validateLogin($this->user->username, $_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        $this->user->setPassword($_POST['new_password']);
    }

    public function login()
    {
        $this->validatePostCsrfToken();

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

    public function destroySession()
    {
        $this->validateAjaxCsrfToken();

        $sessionId = Text::filterHex($_POST['session_id']);

        $destroySession = $this->user->destroySession(hex2bin($sessionId), $this->user->id);
        if (!$destroySession) {
            $this->throwJsonError(500);
        }
    }

    public function logout()
    {
        $this->validatePostCsrfToken();

        $destroySession = $this->user->destroySession($_POST['session_id'], $this->user->id);
        if (!$destroySession) {
            $this->dieWithError(_('What the!? Can\'t logout!?'));
        }

        $this->deleteLoginCookie(false);
        HttpResponse::redirectExit('/');
    }

    public function delete()
    {
        $this->validatePostCsrfToken();

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
            $this->badRequest(_('Login failed'), _('Invalid username or password'));
        }

        if (!$this->user->validateLogin($_POST['username'], $_POST['password'])) {
            $this->blockAccess(_('Login failed'), _('Invalid username or password'));
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

        return true;
    }
}
