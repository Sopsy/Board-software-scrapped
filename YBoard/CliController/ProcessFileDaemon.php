<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Library\CliLogger;
use YBoard\Library\FileHandler;
use YBoard\Library\MessageQueue;
use YBoard\Model\Files;

class ProcessFileDaemon extends CliDatabase
{
    public function index()
    {
        $mq = new MessageQueue();
        $files = new Files($this->db);

        $msgType = NULL;
        $message = NULL;

        while ($mq->receive(MessageQueue::MSG_TYPE_ALL, $msgType, 10240, $message)) {
            if ($msgType == MessageQueue::MSG_TYPE_DO_PNGCRUSH) {
                // $message should be instance of Data\UploadedFile
                $file = $files->get($message);
                if (!$file) {
                    CliLogger::write('ERROR 1: Invalid file '. $message);
                    continue;
                }
                if ($file->extension != 'png') {
                    CliLogger::write('ERROR 1: Invalid file extension for PNGCrush '. $file->extension);
                    continue;
                }

                $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.'. $file->extension;
                FileHandler::pngCrush($filePath);
                $md5 = md5(file_get_contents($filePath));

                $files->saveMd5List($file->id, [$md5]);
                $files->updateFileSize($file->id, filesize($filePath));

                CliLogger::write('SUCCESS: ' . $file->id .' ' . $filePath);
            }

            echo $msgType . ' ';
            var_dump($message);

            $msgType = NULL;
            $message = NULL;
        }
    }
}
