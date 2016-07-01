<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class Posts extends ExtendedController
{
    public function submit()
    {
        if (!$this->validateAjaxCsrfToken()) {
            $this->badRequest();
        }
        
        if (empty($_POST['message'])) {
            $this->badRequest(_('Message not saved'), _('Well, there was nothing to save. Please type a message.'));
        }
    }
}
