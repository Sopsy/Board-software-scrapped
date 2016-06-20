<?php
namespace YBoard\Controller;

use YBoard\Controller;
use YBoard\Model;
use YFW\Library\Text;

class Profile extends Controller
{
    public function index(?int $userId = null): void
    {
        if ($userId === null) {
            $user = $this->user;
            $pageTitle = _('Your profile');
        } else {
            if (!$this->user->isAdmin) {
                $this->notFound();
            }

            $user = Model\User::getById($this->db, $userId);

            if ($user->id === null) {
                $this->notFound();
            }

            if (!empty($user->username)) {
                $pageTitle = sprintf(_('Profile for user %s'), $user->username);
            } else {
                $pageTitle = _('Profile for unregistered user');
            }
        }

        $view = $this->loadTemplateEngine();
        $view->setVar('pageTitle', $pageTitle);
        $view->setVar('profile', $user);

        if ($this->user->id !== null) {
            $view->setVar('loginSessions', Model\UserSession::getAll($this->db, $this->user->id));
        }

        $view->display('Profile');
    }
}
