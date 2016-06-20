<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;

class UserPreferences extends ApiController
{
    public function set(): void
    {
        if (empty($_POST)) {
            $this->throwJsonError(400);
        }

        foreach ($_POST as $key => $val) {
            $this->user->preferences->set($key, $val);
        }
    }
}
