<?php
namespace YBoard\Controller\Api;

use YBoard\ApiController;
use YBoard\Model;
use YFW\Exception\FileUploadException;
use YFW\Library\TemplateEngine;

class File Extends ApiController
{
    public function delete(): void
    {
        if (empty($_POST['fileId'])) {
            $this->throwJsonError(400);
        }

        $file = Model\File::get($this->db, $_POST['fileId']);
        if ($file === null) {
            $this->throwJsonError(400, _('File does not exist'));
        }

        if ($file->userId != $this->user->id && !$this->user->isMod) {
            $this->throwJsonError(403, _('This isn\'t your file!'));
        }

        $thumbnail = $this->config['file']['savePath'] . '/' . $file->folder . '/t/' . $file->name . '.jpg';
        $full = $this->config['file']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;
        if (is_file($thumbnail)) {
            unlink($thumbnail);
        }
        if (is_file($full)) {
            unlink($full);
        }

        $file->delete();
    }

    public function create(): void
    {
        if (empty($_FILES['files']) || !is_array($_FILES['files'])) {
            $this->throwJsonError(400, _('No file uploaded'));
        }

        if (!is_dir($this->config['file']['savePath'])) {
            $this->throwJsonError(500, _('File uploads are temporarily disabled due to a configuration error'));
        }

        // Check that we have enough free space for files
        if (disk_free_space($this->config['file']['savePath']) <= $this->config['file']['diskMinFree']) {
            $this->throwJsonError(403, _('File uploads are temporarily disabled'));
        }

        // Process file
        $uploadedFile = new Model\UploadedFile($this->db);
        $uploadedFile->setConfig($this->config['file']);

        // The default array ordering is stupid...
        $files = [];
        foreach ($_FILES['files'] as $key => $file) {
            foreach ($file as $i => $val) {
                $files[$i][$key] = $val;
            }
        }

        // Calculate file sizes
        $uploadSize = 0;
        foreach ($files as $file) {
            $uploadSize += $file['size'];
        }

        if ($uploadSize >= $this->config['file']['maxSize']) {
            $this->throwJsonError(400, _('Your files exceed the maximum upload size'));
        }

        $ids = [];
        foreach ($files as $file) {
            try {
                $uploadedFile->processUpload($file, $this->user->id, true);
            } catch (FileUploadException $e) {
                $this->throwJsonError(400, $e->getMessage());
            }

            $this->user->statistics->increment('uploadedFiles');
            $this->user->statistics->increment('uploadedFilesTotalSize', $file['size']);

            $ids[] = $uploadedFile->id;

            // Limit to one file per upload for now
            //break;
        }

        $this->sendJsonMessage($ids);
    }

    public function getMediaPlayer(): void
    {
        if (empty($_POST['fileId'])) {
            $this->throwJsonError(400, _('Invalid file ID'));
        }

        $file = Model\File::get($this->db, $_POST['fileId']);

        if ($file === null) {
            $this->throwJsonError(400, _('File does not exist'));
        }
        if ($file->inProgress) {
            $this->throwJsonError(418, _('This file is being processed...'));
        }

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/View/', 'Blank');
        $view->setVar('fileUrl', $this->config['url']['files'] . '/' . $file->folder . '/o/' . $file->name . '/' . '1.' . $file->extension);
        $view->setVar('poster', $this->config['url']['files'] . '/' . $file->folder . '/t/' . $file->name . '.jpg');
        $view->setVar('loop', $file->isGif);

        $view->display('Ajax/MediaPlayer');
    }
}
