<?php
namespace YBoard\Traits;

use YBoard\Library\HttpResponse;

trait Ajax
{
    protected function invalidAjaxData()
    {
        HttpResponse::setStatusCode(400);
        $this->jsonMessage(_('Invalid request'), true);
        $this->stopExecution();
    }

    protected function jsonMessage($str, $error = false)
    {
        echo json_encode(['error' => $error, 'message' => $str]);
    }

    protected function throwJsonError($statusCode, $message = false)
    {
        if ($message) {
            $this->jsonMessage($message, true);
        }

        HttpResponse::setStatusCode($statusCode);
        $this->stopExecution();
    }
    
    protected abstract function stopExecution();
}
