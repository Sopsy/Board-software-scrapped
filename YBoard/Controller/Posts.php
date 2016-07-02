<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Library\HttpResponse;

class Posts extends ExtendedController
{
    public function submit()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['message'])) {
            $this->jsonMessage(_('Please type a message.'), true, 400, true);
        }

        //$this->jsonMessage('Ketä', true, 400, true);
    }
}
