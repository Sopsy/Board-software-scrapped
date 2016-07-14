<?php
namespace YBoard\Controller;

use YBoard\Abstracts\ExtendedController;
use YBoard\Exceptions\FileUploadException;
use YBoard\Library\TemplateEngine;
use YBoard\Model\Files;

class File Extends ExtendedController
{
    public function upload()
    {
        if (empty($_FILES['file'])) {
            $this->throwJsonError(400, _('No file uploaded'));
        }

        // Check that we have enough free space for files
        if (disk_free_space($this->config['files']['savePath']) <= $this->config['files']['diskMinFree']) {
            $this->throwJsonError(403, _('File uploads are temporarily disabled'));
        }

        // Process file
        $files = new Files($this->db);
        $files->setConfig($this->config['files']);

        if ($_FILES['file']['size'] >= $this->config['files']['maxSize']) {
            $this->throwJsonError(400, _('Your files exceed the maximum upload size.'));
        }

        try {
            $file = $files->processUpload($_FILES['file']);
        } catch (FileUploadException $e) {
            $this->throwJsonError(400, $e->getMessage());
        }

        $this->user->statistics->increment('uploadedFiles');
        $this->user->statistics->increment('uploadedFilesTotalSize', $_FILES['file']['size']);

        $this->jsonMessage($file->id);
    }

    public function getMediaPlayer()
    {
        $this->validateAjaxCsrfToken();

        if (empty($_POST['file_id'])) {
            $this->invalidAjaxData();
        }

        $files = new Files($this->db);
        $file = $files->get($_POST['file_id']);

        if (!$file) {
            $this->throwJsonError(404);
        }
        if ($file->inProgress) {
            $this->throwJsonError(418, _('This file is being processed...'));
        }

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'Blank');
        $view->fileUrl = $this->config['view']['staticUrl'] . '/files/' . $file->folder . '/o/' . $file->name . '/'
            . '1.' . $file->extension;
        $view->poster = $this->config['view']['staticUrl'] . '/files/' . $file->folder . '/t/' . $file->name . '.jpg';

        $view->loop = $file->isGif;

        $view->display('Ajax/MediaPlayer');
    }
}
