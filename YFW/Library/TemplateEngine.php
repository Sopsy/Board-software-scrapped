<?php
namespace YFW\Library;

class TemplateEngine
{
    protected $variables = [];
    protected $templateFile = false;
    protected $contentType = 'text/html';
    protected $viewFilesPath;
    protected $viewFile;

    public function __construct(string $viewFilesPath, ?string $templateFile = null)
    {
        if (!is_dir($viewFilesPath)) {
            throw new \Exception('Invalid path for view files: ' . $viewFilesPath);
        }
        $this->viewFilesPath = $viewFilesPath;

        if ($templateFile === null) {
            $templateFile = 'Default';
        }

        $template = $this->viewFilesPath . '/Template/' . $templateFile . '.phtml';
        if (!file_exists($template)) {
            throw new \Exception('Error loading the template file ' . $template . ': file does not exist.');
        }
        $this->templateFile = $templateFile;
    }

    public function getVar(string $name)
    {
        return $this->variables[$name];
    }

    public function setVar(string $name, $content): void
    {
        $this->variables[$name] = $content;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function display(string $viewFile, bool $returnAsString = false): ?string
    {
        // Validate viewFile
        if (!preg_match('/^[a-z0-9_\-\/]+$/i', $viewFile)) {
            throw new \Exception('Invalid view file: ' . $viewFile . '.');
        }
        $this->viewFile = $this->viewFilesPath . '/Page/' . $viewFile . '.phtml';

        if (!file_exists($this->viewFile)) {
            throw new \Exception('Error loading the view file "' . $viewFile . '"": file does not exist.');
        }

        // Extract variables set for template
        extract($this->variables, EXTR_OVERWRITE);
        $viewFilesPath = $this->viewFilesPath;

        // Needs output buffering to just get the executed content as a variable
        ob_start();
        require($this->viewFile);

        // $output is used inside the template file
        $output = ob_get_clean();

        if ($returnAsString) {
            ob_start();
        } else {
            header('Content-Type: ' . $this->contentType . '; charset=utf-8');
        }

        require($this->viewFilesPath . '/Template/' . $this->templateFile . '.phtml');

        if ($returnAsString) {
            return ob_get_clean();
        }

        return null;
    }

    protected function getTitle(string $siteName = ''): string
    {
        $title = '';
        if (!empty($this->variables['pageTitle'])) {
            $title .= $this->variables['pageTitle'];

            if (!empty($siteName)) {
                $title .= ' | ';
            }
        }
        $title .= $siteName;

        return $title;
    }
}
