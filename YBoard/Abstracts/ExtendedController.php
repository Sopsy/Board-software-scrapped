<?php

namespace YBoard\Abstracts;

use Library\DbConnection;
use Library\HttpResponse;
use Library\TemplateEngine;
use YBoard;
use YBoard\Model;

abstract class ExtendedController extends YBoard\Controller
{
    protected $i18n;
    protected $db;
    protected $requiredData = [];

    public function __construct()
    {
        $this->loadConfig();
        $this->dbConnect();
        $this->loadRequiredData();
    }

    protected function dbConnect()
    {
        $this->db = new DbConnection(require(ROOT_PATH . '/YBoard/Config/DbConnection.php'));
    }

    protected function loadRequiredData()
    {
        // Load some data and insert them into the application config
        // so they are automatically available in templates
        $boardsModel = new Model\Boards($this->db);
        $boards = $boardsModel->getBoards();

        $this->config['app']['boardList'] = $boards;
    }

    protected function loadTemplateEngine($templateFile = false) {
        return new TemplateEngine($this->config, $templateFile);
    }

    protected function disallowNonPost()
    {
        if (!$this->isPostRequest()) {
            HttpResponse::setStatusCode(405, ['Allowed' => 'POST']);
            $this->stopExecution();
        }
    }

    protected function isPostRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        }

        return false;
    }

    protected function invalidAjaxData()
    {
        HttpResponse::setStatusCode(401);
        $this->jsonMessage('Virheellinen pyyntö', true);
        $this->stopExecution();
    }

    protected function jsonMessage($str = '', $error = false)
    {
        echo json_encode(['error' => $error, 'message' => $str]);
    }

    protected function invalidData()
    {
        HttpResponse::setStatusCode(401);

        $view = new TemplateEngine();

        $view->errorTitle = 'Virheellinen pyyntö';
        $view->errorMessage = 'Pyyntöäsi ei voitu käsitellä, koska se sisälsi virheellistä tietoa. Yritä uudelleen.';

        $view->display('Error');

        $this->stopExecution();
    }

    protected function validateCsrfToken($token)
    {
        if (empty($token) || empty($_SESSION['csrfToken'])) {
            return false;
        }

        if ($token == $_SESSION['csrfToken']) {
            return true;
        }

        return false;
    }

    protected function validateAjaxCsrfToken()
    {
        if (!$this->isPostRequest()) {
            $this->ajaxCsrfValidationFail();
        }

        if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || empty($_SESSION['csrfToken'])) {
            $this->ajaxCsrfValidationFail();
        }

        if ($_SERVER['HTTP_X_CSRF_TOKEN'] == $_SESSION['csrfToken']) {
            return true;
        }

        $this->ajaxCsrfValidationFail();
    }

    protected function ajaxCsrfValidationFail()
    {
        HttpResponse::setStatusCode(401);
        $this->jsonMessage('Istuntosi on vanhentunut. Ole hyvä ja päivitä tämä sivu.', true);
        $this->stopExecution();
    }

    protected function jsonError($str = '')
    {
        $this->jsonMessage($str, true);
        $this->stopExecution();
    }
}
