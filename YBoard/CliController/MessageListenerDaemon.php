<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Library\CliLogger;
use YBoard\Library\Database;
use YBoard\Library\FileHandler;
use YBoard\Library\MessageQueue;
use YBoard\Model\Files;
use YBoard\Model\Posts;
use YBoard\Model\User;
use YBoard\Model\UserNotifications;

class MessageListenerDaemon
{
    protected $config;
    protected $db;

    public function __construct()
    {
        // Load config
        $this->config = require(ROOT_PATH . '/YBoard/Config/YBoard.php');
    }

    protected function connectDb()
    {

        $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));
    }

    protected function closeDb()
    {
        $this->db = null;
    }

    public function index()
    {
        $mq = new MessageQueue();

        $msgType = null;
        $message = null;

        while ($mq->receive(MessageQueue::MSG_TYPE_ALL, $msgType, 10240, $message)) {
            $this->connectDb();

            switch ($msgType) {
                case MessageQueue::MSG_TYPE_DO_PNGCRUSH:
                    // $message should be fileId
                    $this->doPngCrush($message);
                    break;
                case MessageQueue::MSG_TYPE_PROCESS_VIDEO:
                    // $message should be fileId
                    $this->processVideo($message);
                    break;
                case MessageQueue::MSG_TYPE_PROCESS_AUDIO:
                    // $message should be fileId
                    $this->processAudio($message);
                    break;
                case MessageQueue::MSG_TYPE_ADD_POST_NOTIFICATION:
                    // Message should be [notificationType, postId, [userId], skipUsers]
                    $this->addPostNotification($message);
                    break;
                default:
                    CliLogger::write('[ERROR] Unknown message type: ' . $msgType);
                    break;
            }

            $this->closeDb();
            $msgType = null;
            $message = null;
        }
    }

    protected function doPngCrush($message)
    {
        $files = new Files($this->db);
        $file = $files->get($message);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $message);
            return false;
        }
        if ($file->extension != 'png') {
            CliLogger::write('[ERROR] Invalid file extension for PNGCrush: ' . $file->extension);
            return false;
        }

        $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;
        FileHandler::pngCrush($filePath);
        $md5 = md5(file_get_contents($filePath));

        $files->saveMd5List($file->id, [$md5]);
        $files->updateFileSize($file->id, filesize($filePath));
        $files->updateFileInProgress($file->id, false);

        return true;
    }

    protected function processVideo($message)
    {
        $files = new Files($this->db);
        $file = $files->get($message);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $message);
            return false;
        }
        if ($file->extension != 'mp4') {
            CliLogger::write('[ERROR] Invalid file extension for video: ' . $file->extension);
            return false;
        }

        $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;

        // Convert
        $convert = FileHandler::convertVideo($filePath);

        if (!$convert) {
            CliLogger::write('[ERROR] Video conversion failed: ' . $file->id);
            return false;
        }

        $md5 = md5(file_get_contents($filePath));
        $files->saveMd5List($file->id, [$md5]);
        $files->updateFileSize($file->id, filesize($filePath));
        $files->updateFileInProgress($file->id, false);

        return true;
    }

    protected function processAudio($message)
    {
        $files = new Files($this->db);
        $file = $files->get($message);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $message);
            return false;
        }
        if ($file->extension != 'm4a') {
            CliLogger::write('[ERROR] Invalid file extension for video: ' . $file->extension);
            return false;
        }

        $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;

        // Convert
        $convert = FileHandler::convertAudio($filePath);

        if (!$convert) {
            CliLogger::write('[ERROR] Audio conversion failed: ' . $file->id);
            return false;
        }

        $md5 = md5(file_get_contents($filePath));
        $files->saveMd5List($file->id, [$md5]);
        $files->updateFileSize($file->id, filesize($filePath));
        $files->updateFileInProgress($file->id, false);

        return true;
    }

    protected function addPostNotification($message)
    {
        if (!is_array($message)) {
            return false;
        }

        $notificationType = $message[0];

        if ($notificationType == UserNotifications::NOTIFICATION_TYPE_POST_REPLY) {
            if (count($message) != 3) {
                return false;
            }

            list($notificationType, $postId, $skipUsers) = $message;
        } else {
            if (count($message) != 4) {
                return false;
            }

            list($notificationType, $postId, $userId, $skipUsers) = $message;
        }

        $posts = new Posts($this->db);
        $userNotifications = new UserNotifications($this->db);
        $repliedPost = $posts->getMeta($postId);
        if (!$repliedPost) {
            return false;
        }

        if (empty($userId)) {
            $userId = $repliedPost->userId;
        }

        if (in_array($userId, $skipUsers)) {
            return true;
        }

        $userNotifications->add($userId, $notificationType, null, $repliedPost->id);

        return true;
    }
}
