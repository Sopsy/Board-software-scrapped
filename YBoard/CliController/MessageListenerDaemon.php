<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Library\CliLogger;
use YBoard\Library\FileHandler;
use YBoard\Library\MessageQueue;
use YBoard\Model\Files;

class MessageListenerDaemon extends CliDatabase
{
    public function index()
    {
        $mq = new MessageQueue();
        $files = new Files($this->db);

        $msgType = NULL;
        $message = NULL;

        while ($mq->receive(MessageQueue::MSG_TYPE_ALL, $msgType, 10240, $message)) {
            switch ($msgType) {
                case MessageQueue::MSG_TYPE_DO_PNGCRUSH:
                    // $message should be fileId
                    $file = $files->get($message);
                    if (!$file) {
                        CliLogger::write('[ERROR] Invalid file: '. $message);
                        continue;
                    }
                    if ($file->extension != 'png') {
                        CliLogger::write('[ERROR] Invalid file extension for PNGCrush: '. $file->extension);
                        continue;
                    }

                    $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.'. $file->extension;
                    FileHandler::pngCrush($filePath);
                    $md5 = md5(file_get_contents($filePath));

                    $files->saveMd5List($file->id, [$md5]);
                    $files->updateFileSize($file->id, filesize($filePath));
                    $files->updateFileInProgress($file->id, false);

                    break;
                case MessageQueue::MSG_TYPE_PROCESS_VIDEO:
                    // $message should be fileId
                    $file = $files->get($message);
                    if (!$file) {
                        CliLogger::write('[ERROR] Invalid file: '. $message);
                        continue;
                    }
                    if ($file->extension != 'mp4') {
                        CliLogger::write('[ERROR] Invalid file extension for video: '. $file->extension);
                        continue;
                    }

                    $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.'. $file->extension;

                    // Convert
                    $convert = FileHandler::convertVideo($filePath);

                    if (!$convert) {
                        CliLogger::write('[ERROR] Video conversion failed: '. $file->id);
                        continue;
                    }

                    unset($tmpFile);

                    $md5 = md5(file_get_contents($filePath));
                    $files->saveMd5List($file->id, [$md5]);
                    $files->updateFileSize($file->id, filesize($filePath));
                    $files->updateFileInProgress($file->id, false);

                    break;
                case MessageQueue::MSG_TYPE_PROCESS_AUDIO:
                    // $message should be fileId
                    $file = $files->get($message);
                    if (!$file) {
                        CliLogger::write('[ERROR] Invalid file: '. $message);
                        continue;
                    }
                    if ($file->extension != 'm4a') {
                        CliLogger::write('[ERROR] Invalid file extension for video: '. $file->extension);
                        continue;
                    }

                    $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.'. $file->extension;

                    // Convert
                    $convert = FileHandler::convertAudio($filePath);

                    if (!$convert) {
                        CliLogger::write('[ERROR] Audio conversion failed: '. $file->id);
                        continue;
                    }

                    unset($tmpFile);

                    $md5 = md5(file_get_contents($filePath));
                    $files->saveMd5List($file->id, [$md5]);
                    $files->updateFileSize($file->id, filesize($filePath));
                    $files->updateFileInProgress($file->id, false);

                    break;
                default:
                    CliLogger::write('[ERROR] Unknown message type: '. $msgType);
                    break;
            }

            $msgType = NULL;
            $message = NULL;
        }
    }
}
