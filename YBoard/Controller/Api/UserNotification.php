<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
use YBoard\Model;

class UserNotification extends ApiController
{
    function getAll(): void
    {
        $view = $this->loadTemplateEngine('Blank');

        $view->setVar('notifications', Model\UserNotification::getByUser($this->db, $this->user->id));
        $view->display('Ajax/NotificationList');
    }

    function markRead(): void
    {
        if (empty($_POST['id'])) {
            $this->throwJsonError(400);
        }

        $notification = Model\UserNotification::get($this->db, $_POST['id']);
        if ($notification !== null) {
            $notification->markRead();
        }
    }

    function markAllRead(): void
    {
        Model\UserNotification::markReadByUser($this->db, $this->user->id);
    }
}
