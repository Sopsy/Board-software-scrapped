<?php

namespace YBoard\Abstracts;

use Library\DbConnection;
use Library\HttpResponse;
use Library\i18n;
use Library\TemplateEngine;
use YBoard;
use YBoard\Model;

abstract class ExtendedController extends YBoard\Controller
{
    protected $i18n;
    protected $db;
    protected $boardList;

    public function __construct()
    {
        $this->loadConfig();
        $this->dbConnect();
        $this->loadRequiredData();
        $this->loadInternalization();
    }

    protected function dbConnect()
    {
        $this->db = new DbConnection(require(ROOT_PATH . '/YBoard/Config/DbConnection.php'));
    }

    protected function loadRequiredData()
    {
        // Load some data that are required on almost every page, like the list of boards and user data
        $boardsModel = new Model\Boards($this->db);
        $this->boardList = $boardsModel->getBoards();
    }

    protected function loadInternalization()
    {

        if (empty($this->config['app']['locale'])) {
            return false;
        }

        $this->i18n = new i18n(ROOT_PATH . '/YBoard/i18n', $this->config['app']['locale']);
    }

    protected function loadTemplateEngine($templateFile = 'Default')
    {
        $templateEngine = new TemplateEngine(dirname(__DIR__) . '/View/', $templateFile);

        foreach ($this->config['app'] as $var => $val) {
            $templateEngine->$var = $val;
        }

        $templateEngine->boardList = $this->boardList;

        return $templateEngine;
    }

    public function notFound()
    {
        HttpResponse::setStatusCode(404);
        $view = $this->loadTemplateEngine();

        $view->pageTitle = 'Sivua ei löydy';

        // Get a random 404-image
        $images = glob(ROOT_PATH . '/static/img/404/*.*');
        $view->imageSrc = $this->pathToUrl($images[array_rand($images)]);

        $view->display('NotFound');
        $this->stopExecution();
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

        $view = $this->loadTemplateEngine();

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

    protected function blockAccess($pageTitle, $errorMessage)
    {
        $this->showMessage($pageTitle, $errorMessage, 403);
    }

    protected function showMessage($errorTitle, $errorMessage, $httpStatus = false)
    {
        if ($httpStatus && is_int($httpStatus)) {
            HttpResponse::setStatusCode($httpStatus);
        }
        $view = $this->loadTemplateEngine();

        $view->pageTitle = $view->errorTitle = $errorTitle;
        $view->errorMessage = $errorMessage;

        $view->display('Error');
        $this->stopExecution();
    }

    protected function badRequest($pageTitle, $errorMessage)
    {
        $this->showMessage($pageTitle, $errorMessage, 400);
    }
}
