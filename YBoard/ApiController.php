<?php
namespace YBoard;

use YFW\Library\HttpResponse;

abstract class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->csrfTokenIsValid()) {
            $this->throwJsonError(401, _('Your session has expired. Please refresh this page and try again.'));
            $this->stopExecution();
        }
    }

    protected function csrfTokenIsValid(): bool
    {
        if ($this->user->id === null) {
            return false;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $sentCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrfToken'] ?? null);

        if (empty($sentCsrfToken) || empty($this->user->session->csrfToken)) {
            return false;
        }

        if (hash_equals($this->user->session->csrfToken, $sentCsrfToken)) {
            return true;
        }

        return false;
    }

    protected function sendJsonPageReload(string $url = null): void
    {
        echo json_encode(['reload' => true, 'url' => $url]);
    }

    protected function sendJsonMessage($message, string $title = null, bool $reload = false, string $url = null): void
    {
        $args = [
            'title' => $title,
            'message' => $message,
            'reload' => $reload,
            'url' => $url,
        ];

        echo json_encode($args);
    }

    protected function throwJsonError(int $statusCode, string $message = null, string $title = null): void
    {
        HttpResponse::setStatusCode($statusCode);

        if ($message) {
            $this->sendJsonMessage($message, $title);
        }

        $this->stopExecution();
    }
}
