<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
use YBoard\Model;
use YFW\Library\Text;

class UserSession extends ApiController
{
    public function create(): void
    {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->throwJsonError(401, _('Invalid username or password'), _('Login failed'));
        }

        $newUser = Model\User::getByLogin($this->db, $_POST['username'], $_POST['password']);
        if (!$newUser) {
            $this->throwJsonError(403, _('Invalid username or password'), _('Login failed'));
        }

        $this->user->session->destroy();

        $newUser->session = Model\UserSession::create($this->db, $newUser->id);

        $this->setLoginCookie($newUser->id, $newUser->session->id, $newUser->session->verifyKey);

        // Log mod logins
        if ($newUser->class !== 0) {
            Model\Log::write($this->db, Model\Log::ACTION_MOD_LOGIN, $newUser->id);
        }

        // Delete old user if it has no sessions and there's no password set.
        if (Model\User::isUnusable($this->db, $this->user->id)) {
            $this->user->delete();
        }

        $this->sendJsonPageReload();
    }

    public function delete(): void
    {
        $session = null;
        if (empty($_POST['sessionId'])) {
            $session = $this->user->session;
        } elseif (empty($_POST['verifyKey'])) {
            $this->throwJsonError(400);
        } else {
            $sessionId = Text::filterHex($_POST['sessionId']);
            $verifyKey = Text::filterHex($_POST['verifyKey']);

            $userId = $this->user->id;
            if ($this->user->isAdmin && !empty($_POST['userId'])) {
                $userId = (int)$_POST['userId'];
            }

            $session = Model\UserSession::get($this->db, $userId, hex2bin($sessionId), hex2bin($verifyKey));
        }

        if ($session === null) {
            $this->throwJsonError(400, _('Session does not exist'));
        }

        $session->destroy();

        if (empty($_POST['sessionId'])) {
            $this->sendJsonPageReload();
        } else {
            $this->sendJsonMessage(_('Session destroyed'));
        }
    }
}
