<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
use YBoard\Model;
use YFW\Library\ReCaptcha;

class User extends ApiController
{
    public function create(): void
    {
        if ($this->user->loggedIn) {
            $this->throwJsonError(400, _('You are already logged in'), _('Signup failed'));
        }

        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['repassword'])) {
            $this->throwJsonError(400, _('Missing username or password'), _('Signup failed'));
        }

        if (!$this->verifyCaptcha()) {
            $this->throwJsonError(403, _('Validating the CAPTCHA response failed. Please try again.'), _('Signup failed'));
        }

        if ($_POST['password'] !== $_POST['repassword']) {
            $this->throwJsonError(400, _('The two passwords do not match'), _('Signup failed'));
        }
        if (mb_strlen($_POST['username']) > $this->config['user']['usernameMaxLength']) {
            $this->throwJsonError(400, _('Username is too long'), _('Signup failed'));
        }

        if (!Model\User::usernameIsFree($this->db, $_POST['username'])) {
            $this->throwJsonError(400, _('This username is already taken, please choose another one'), _('Signup failed'));
        }

        $this->user->setUsername($_POST['username']);
        $this->user->setPassword($_POST['password']);

        $this->sendJsonPageReload();
    }

    public function changeName(): void
    {
        if (!$this->user->loggedIn) {
            $this->throwJsonError(400, _('You should log in first'));
        }

        if (empty($_POST['newName']) || empty($_POST['password'])) {
            $this->throwJsonError(400, _('Please fill all of the required fields'));
        }

        if (mb_strlen($_POST['newName']) > $this->config['user']['usernameMaxLength']) {
            $this->throwJsonError(400, _('Username is too long'));
        }

        if ($this->user->username == $_POST['newName']) {
            $this->throwJsonError(400, _('This is your current username'));
        }

        if (!Model\User::usernameIsFree($this->db, $_POST['newName'])) {
            $this->throwJsonError(400, _('This username is already taken, please choose another one'));
        }

        if (!$this->user->validatePassword($_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        $this->user->setUsername($_POST['newName']);

        $this->sendJsonPageReload();
    }

    public function changePassword(): void
    {
        if (!$this->user->loggedIn) {
            $this->throwJsonError(400, _('You should log in first'));
        }

        if (empty($_POST['newPassword']) || empty($_POST['newPasswordAgain']) || empty($_POST['password'])) {
            $this->throwJsonError(400, _('Please fill all of the required fields'));
        }

        if ($_POST['newPassword'] != $_POST['newPasswordAgain']) {
            $this->throwJsonError(400, _('The two passwords do not match'));
        }

        if (!$this->user->validatePassword($_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        $this->user->setPassword($_POST['newPassword']);

        $this->sendJsonMessage(_('Password changed'));
    }

    public function delete(): void
    {
        if (!$this->user->loggedIn) {
            $this->throwJsonError(400, _('You should log in first'));
        }

        if (empty($_POST['password'])) {
            $this->throwJsonError(401, _('Please type your current password'));
        }

        if (empty($_POST['confirm'])) {
            $this->throwJsonError(401, _('Please confirm the deletion'));
        }

        if (!$this->user->validatePassword($_POST['password'])) {
            $this->throwJsonError(401, _('Invalid password'));
        }

        if (!empty($_POST['deletePosts'])) {
            Model\Post::deleteByUser($this->db, $this->user->id);
        }

        $this->user->delete();

        $this->deleteLoginCookie(false);
        $this->sendJsonPageReload('/');
    }
}
