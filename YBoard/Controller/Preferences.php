<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;

class Preferences extends ExtendedController
{
    public function index()
    {
        $view = $this->loadTemplateEngine();

        $view->pageTitle = _('Preferences');
        $view->display('Preferences');
    }

    public function save()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['set']) || !is_array($_POST['set'])) {
            $this->throwJsonError(400);
        }

        foreach ($_POST['set'] as $key => $val) {
            $this->user->preferences->set($key, $val);
        }
    }

    public function setThemeVariation()
    {
        $this->validateAjaxCsrfToken();

        $currentTheme = $this->user->preferences->theme;
        if ((empty($_POST['id']) && $_POST['id'] === 0)
            || !array_key_exists($_POST['id'], $this->config['view']['themes'][$currentTheme]['css'])) {
            $this->throwJsonError(400);
        }

        $this->user->preferences->set('themeVariation', $_POST['id']);
    }

    public function toggleHideSidebar()
    {
        $this->validateAjaxCsrfToken();
        $this->user->preferences->set('hideSidebar', !$this->user->preferences->hideSidebar);
    }
}
