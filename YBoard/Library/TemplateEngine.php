<?php

namespace YBoard\Library;

class TemplateEngine
{
    protected $variables = [];
    protected $templateFile = false;
    protected $viewFilesPath;

    public function __construct($viewFilesPath, $templateFile = false)
    {
        if (!is_dir($viewFilesPath)) {
            throw new \Exception('Invalid path for view files: ' . $viewFilesPath);
        }
        $this->viewFilesPath = $viewFilesPath;

        if (!$templateFile) {
            $templateFile = 'Default';
        }

        $template = $this->viewFilesPath . 'Template/' . $templateFile . '.phtml';
        if (!file_exists($template)) {
            throw new \Exception('Error loading the template file ' . $template . ': file does not exist.');
        }
        $this->templateFile = $templateFile;
    }

    public function __get($name)
    {
        return $this->variables[$name];
    }

    public function __set($name, $content)
    {
        $this->variables[$name] = $content;
    }

    public function display($viewFile, $returnAsString = false)
    {
        // Validate viewFile
        if (!preg_match('/^[a-z0-9_\-\/]+$/i', $viewFile)) {
            throw new \Exception('Invalid view file: ' . $viewFile . '.');
        }
        $viewFile = $this->viewFilesPath . 'Page/' . $viewFile . '.phtml';

        if (!file_exists($viewFile)) {
            throw new \Exception('Error loading the view file ' . $viewFile . ': file does not exist.');
        }

        // Extract variables set for template
        extract($this->variables, EXTR_OVERWRITE);

        // Needs output buffering to just get the executed content as a variable
        ob_start();
        require($viewFile);
        $output = ob_get_clean();

        if ($returnAsString) {
            ob_start();
        }

        // $output is used inside the template file
        require($this->viewFilesPath . 'Template/' . $this->templateFile . '.phtml');

        if ($returnAsString) {
            return ob_get_clean();
        }

        return false;
    }

    protected function getTitle()
    {
        $title = '';
        if (!empty($this->variables['pageTitle'])) {
            $title .= $this->variables['pageTitle'] . ' | ';
        }
        $title .= $this->variables['siteName'];

        return $title;
    }

    protected function getPartial($file) {
        // Might use quite a bit of memory if getPartial is used extensively...
        // Maybe test it out later.
        extract($this->variables, EXTR_OVERWRITE);

        return include($this->viewFilesPath . $file . '.phtml');
    }
}
