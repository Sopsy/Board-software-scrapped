<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\UserException;
use YBoard\Library\HttpResponse;
use YBoard\Library\Text;
use YBoard\Model\Posts;
use YBoard\Model\Users;
use YBoard\Model\UserSessions;

class User extends ExtendedController
{
    public function profile($userId = false)
    {
        if (empty($userId)) {
            $user = $this->user;
            $pageTitle = _('Your profile');
        } else {
            if (!$this->user->isAdmin) {
                $this->notFound();
            }

            $users = new Users($this->db);
            $user = $users->getById($userId);

            if ($user->id === null) {
                $this->notFound();
            }

            if (!empty($user->username)) {
                $pageTitle = sprintf(_('Profile for user %s'), $user->username);
            } else {
                $pageTitle = _('Profile for unregistered user');
            }
        }

        $sessions = new UserSessions($this->db);

        $view = $this->loadTemplateEngine();
        $view->pageTitle = $pageTitle;
        $view->profile = $user;

        if ($this->user->id !== null) {
            $view->loginSessions = $sessions->getAll($this->user->id);
        }
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

        $users = new Users($this->db);
        if (!$users->usernameIsFree($_POST['new_name'])) {
            $this->throwJsonError(400, _('This username is already taken, please choose another one'));
        }

        if (!$this->user->validatePassword($_POST['password'])) {
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

        if (!$this->user->validatePassword($_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        $this->user->setPassword($_POST['new_password']);
    }

    public function destroySession()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['session_id'])) {
            $this->throwJsonError(400);
        }

        $sessionId = Text::filterHex($_POST['session_id']);

        $destroySession = $this->user->session->destroy(hex2bin($sessionId));
        if (!$destroySession) {
            $this->throwJsonError(500);
        }
    }

    public function delete()
    {
        $this->validatePostCsrfToken();

        if (empty($_POST['password'])) {
            $this->badRequest();
        }

        if (!$this->user->validatePassword($_POST['password'])) {
            $this->unauthorized(_('User account not deleted'), _('Invalid password'));
        }

        if (!empty($_POST['delete_posts'])) {
            $posts = new Posts($this->db);
            $posts->deleteByUser($this->user->id);
        }

        try {
            $this->user->delete();
        } catch (UserException $e) {
            $this->badRequest(_('User account not deleted'), $e->getMessage());
        }

        $this->deleteLoginCookie(false);
        HttpResponse::redirectExit('/');
    }
}
