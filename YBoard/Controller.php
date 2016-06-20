<?php
namespace YBoard;

use YBoard\Model\Ban;
use YBoard\Model\Board;
use YBoard\Model\PostReport;
use YBoard\Model\User;
use YBoard\Model\UserNotification;
use YBoard\Model\UserPreferences;
use YBoard\Model\UserSession;
use YBoard\Model\UserStatistics;
use YBoard\Model\UserThreadFollow;
use YBoard\Model\UserThreadHide;
use YFW\Library\BotDetection;
use YFW\Library\Database;
use YFW\Library\HttpResponse;
use YFW\Library\i18n;
use YFW\Library\ReCaptcha;
use YFW\Library\TemplateEngine;

abstract class Controller extends \YFW\Controller
{
    protected $config;
    protected $i18n;
    protected $db;
    protected $boards;
    protected $user;
    protected $locale;
    protected $localeDomain;
    protected $requireCaptcha = false;

    public function __construct()
    {
        // Load config
        $this->config = require(ROOT_PATH . '/YBoard/Config/App.php');

        // Get a database connection
        $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));

        // Load some data that are required on almost every page, like the list of boards and user data
        $this->boards = Board::getAll($this->db);

        // Load internalization
        $this->i18n = new i18n(ROOT_PATH . '/YBoard/Locale');

        // Load user
        BotDetection::setUserAgents(require(ROOT_PATH . '/YBoard/Config/Bots.php'));
        $this->loadUser();

        // Get locale
        $this->locale = $this->i18n->getPreferredLocale();
        if (!$this->locale) {
            // Fallback
            $this->locale = $this->config['i18n']['defaultLocale'];
        }
        $this->localeDomain = 'default'; // TODO: Add support for custom domains

        // Set locale
        $this->i18n->loadLocale($this->locale);
    }

    public function __destruct()
    {
        $resourceUsage = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000,
                2) . ' ms ' . round(memory_get_usage() / 1024 / 1024, 2) . ' MB';

        // Only for non-ajax requests
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            // Debug: Execution time and memory usage
            echo '<!-- ' . $resourceUsage . ' -->';
        } else {
            //error_log($resourceUsage);
        }
    }

    protected function verifyCaptcha(): bool
    {
        if (!$this->requireCaptcha) {
            return true;
        }

        if (empty($_POST["captchaResponse"])) {
            return false;
        }

        $captchaOk = ReCaptcha::verify($_POST['captchaResponse'], $this->config['captcha']['privateKey']);
        if (!$captchaOk) {
            return false;
        }

        return true;
    }

    protected function loadUser(): bool
    {
        $cookie = $this->getLoginCookie();
        if ($cookie !== null) {
            // Load session
            $session = UserSession::get($this->db, $cookie['userId'], $cookie['sessionId'], $cookie['verifyKey']);
            if ($session === null) {
                $this->deleteLoginCookie(true);
            }

            // Load user
            $this->user = User::getById($this->db, $session->userId);
            if ($this->user === null) {
                $this->deleteLoginCookie(true);
            }

            $this->user->session = $session;


            // Update last active timestamps
            $this->user->updateLastActive();
            $this->user->session->updateLastActive();
        } else {
            // Session does not exist
            if (BotDetection::isBot('user')) {
                $this->user = User::createTemporary($this->db);
                $this->user->session = new UserSession($this->db);
                $this->user->preferences = new UserPreferences($this->db);
                $this->user->statistics = new UserStatistics($this->db);
                $this->user->threadHide = UserThreadHide::getEmpty();
                $this->user->threadFollow = UserThreadFollow::getEmpty();
                $this->user->notifications = new UserNotification($this->db);

                return false;
            }

            $this->user = User::create($this->db);
            $this->user->session = UserSession::create($this->db, $this->user->id);

            $this->setLoginCookie($this->user->id, $this->user->session->id, $this->user->session->verifyKey);
        }

        $this->user->preferences = UserPreferences::getByUser($this->db, $this->user->id);
        $this->user->statistics = UserStatistics::getByUser($this->db, $this->user->id);
        $this->user->threadHide = UserThreadHide::getByUser($this->db, $this->user->id);
        $this->user->threadFollow = UserThreadFollow::getByUser($this->db, $this->user->id);
        $this->user->notifications = UserNotification::getByUser($this->db, $this->user->id,
            $this->user->preferences->hiddenNotificationTypes);

        // Require captcha if enabled and requiredPosts === true|int
        if ($this->config['captcha']['enabled'] && ($this->config['captcha']['requiredPosts'] === true
                || $this->user->statistics->totalPosts < $this->config['captcha']['requiredPosts'])) {
            $this->requireCaptcha = true;
        }

        return true;
    }

    /*
     * Templates / themes
     */

    protected function dieWithMessage(
        string $errorTitle,
        string $errorMessage,
        ?int $httpStatus = null,
        ?string $bodyClass = null,
        ?string $image = null
    ): void {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            if ($httpStatus === null) {
                $httpStatus = 500;
            }
            HttpResponse::setStatusCode($httpStatus);
            echo json_encode(['message' => $errorMessage, 'title' => $errorTitle]);

            return;
        }

        if ($httpStatus !== null) {
            HttpResponse::setStatusCode($httpStatus);
        }

        $view = $this->loadTemplateEngine();

        $view->setVar('pageTitle', $errorTitle);
        $view->setVar('errorTitle', $errorTitle);
        $view->setVar('errorMessage', $errorMessage);

        // Support for "state saving", for login etc.
        if (!empty($_POST['redirto'])) {
            $view->redirTo = $_POST['redirto'];
        }

        $bc = 'error';
        if (!empty($bodyClass)) {
            $bc .= ' ' . $bodyClass;
        }
        $view->setVar('bodyClass', $bc);
        if (!empty($image)) {
            $view->setVar('errorImageSrc', $image);
        }

        $view->display('Error');
        $this->stopExecution();
    }

    protected function initializePagination(
        TemplateEngine $view,
        int $pageNum,
        int $maxPages,
        bool $isLastPage,
        bool $catalog = false
    ): void {
        if ($isLastPage) {
            $maxPages = $pageNum;
        }

        // Calculate the end and start pages of the pagination
        // We don't count the total number of pages to save some resources.
        $startPage = $pageNum - 1;
        if ($startPage < 2) {
            $startPage = 2;
        }

        $endPage = $pageNum + 2;
        if ($endPage > $maxPages) {
            $endPage = $maxPages;
        }

        $view->setVar('pagination', [
            'catalog' => $catalog,
            'page' => $pageNum,
            'maxPages' => $maxPages,
            'startPage' => $startPage,
            'endPage' => $endPage,
        ]);
    }

    protected function limitPages(int $pageNum, int $maxPages): void
    {
        if ($pageNum > $maxPages) {
            $this->notFound(_('Not found'), sprintf(_('Please don\'t. %s pages is enough.'), $maxPages));
        }
    }

    protected function loadTemplateEngine(string $templateFile = 'Default'): TemplateEngine
    {
        $templateEngine = new TemplateEngine(ROOT_PATH . '/YBoard/View', $templateFile);

        $templateEngine->setVar('config', $this->config);

        // Some things are only done when loading regular pages with the "Default" template
        if ($templateFile == 'Default') {
            $this->user->statistics->increment('pageLoads');

            // Mod functions
            if ($this->user->isMod) {
                // Get unchecked reports
                $reports = new PostReport($this->db);

                // Get ban appeals
                $bans = new Ban($this->db);
                $templateEngine->setVar('moderation', [
                    'uncheckedReports' => $reports->getUncheckedCount(),
                    'uncheckedBanAppeals' => $bans->getUncheckedAppealCount(),
                ]);
            }
        }

        // Verify theme exists
        if (!array_key_exists($this->user->preferences->theme, $this->config['themes'])) {
            $this->user->preferences->reset('theme');
        }

        if ($this->user->preferences->theme === null) {
            $theme = $this->config['view']['defaultTheme'];
        } else {
            $theme = $this->user->preferences->theme;
        }

        $activeStylesheet = !$this->user->preferences->darkTheme ? $this->config['themes'][$theme]['light'] : $this->config['themes'][$theme]['dark'];
        $templateEngine->setVar('stylesheet', [
            'active' => $activeStylesheet,
            'color' => $this->config['themes'][$theme]['color'],
            'light' => $this->config['themes'][$theme]['light'],
            'dark' => $this->config['themes'][$theme]['dark'],
            'darkTheme' => $this->user->preferences->darkTheme ? 'true' : 'false',
        ]);

        $templateEngine->setVar('locale', [
            'name' => $this->locale,
            'domain' => $this->localeDomain,
        ]);

        $templateEngine->setVar('user', $this->user);
        $templateEngine->setVar('boardList', $this->boards);
        $templateEngine->setVar('requireCaptcha', $this->requireCaptcha);

        // Preload hints, TODO: fix locale and theme hints
        header('Link: <' . $this->config['url']['static'] . $this->config['app']['logo'] . '>; rel=preload; as=image; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . '/font/icomoon.woff2>; rel=preload; as=font; crossorigin; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . '/js/config.js>; rel=preload; as=script; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . '/img/header_bg.jpg>; rel=preload; as=image; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . '/js/locale/'. $this->locale . '.' . $this->localeDomain . '.js>; rel=preload; as=script; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . '/js/yboard' . (APP_ENV !== 'development' ? '.min' : '') . '.js>; rel=preload; as=script; nopush',
            false);
        header('Link: <' . $this->config['url']['static'] . $activeStylesheet . '>; rel=preload; as=style; nopush',
            false);

        return $templateEngine;
    }

    protected function modOnly(): void
    {
        if (!$this->user->isMod) {
            $this->notFound();
        }
    }

    /*
     * LOGIN COOKIE
     */

    protected function getLoginCookie(): ?array
    {
        if (empty($_COOKIE['user'])) {
            return null;
        }

        if (strlen($_COOKIE['user']) <= 128) {
            return null;
        }

        $userId = substr($_COOKIE['user'], 128);
        [$sessionId, $verifyKey] = str_split($_COOKIE['user'], 64);

        return ['userId' => (int)$userId, 'sessionId' => hex2bin($sessionId), 'verifyKey' => hex2bin($verifyKey)];
    }

    protected function setLoginCookie(int $userId, string $sessionId, string $verifyKey): bool
    {
        $sessionId = bin2hex($sessionId);
        $verifyKey = bin2hex($verifyKey);
        HttpResponse::setCookie('user', $sessionId . $verifyKey . $userId);

        return true;
    }

    protected function deleteLoginCookie(bool $reload = false): bool
    {
        HttpResponse::setCookie('user', '', false);
        if ($reload) {
            HttpResponse::redirectExit($_SERVER['REQUEST_URI']);
        }

        return true;
    }

    /*
     * ERROR PAGES
     */

    protected function badRequest(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Bad request') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Your request did not complete because it contained invalid information.') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 400);
    }

    protected function unauthorized(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Unauthorized') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('You are not authorized to perform this operation') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 401);
    }

    protected function blockAccess(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Forbidden') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Thou shalt not access this resource!') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 403);
    }

    public function notFound(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Page not found') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Whatever you were looking for does not exist here. Probably never did.') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 404, 'notfound', $this->getErrorImage(404));
    }

    public function gone(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Gone') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('Whatever you were looking for does not exist here. It did once, however.') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 404, 'notfound', $this->getErrorImage(404));
    }

    protected function internalError(?string $errorTitle = null, ?string $errorMessage = null): void
    {
        $errorTitle = empty($errorTitle) ? _('Oh noes!') : $errorTitle;
        $errorMessage = empty($errorMessage) ? _('We\'re terribly sorry. An internal error occurred when we tried to complete your request.') : $errorMessage;

        $this->dieWithMessage($errorTitle, $errorMessage, 500, 'internalerror', $this->getErrorImage(500));
    }

    protected function getErrorImage(int $errorCode): ?string
    {
        $images = glob(ROOT_PATH . '/static/img/' . $errorCode . '/*.*');
        if (!empty($images)) {
            return $this->config['url']['static'] . str_replace(ROOT_PATH . '/static', '',
                    $images[array_rand($images)]);
        }

        return null;
    }
}
