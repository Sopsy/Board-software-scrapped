<?php
namespace YBoard\CliController;

use YBoard\Library\CliLogger;
use YBoard\Library\Database;
use YBoard\Library\FileHandler;
use YBoard\Library\MessageQueue;
use YBoard\Model\Files;
use YBoard\Model\Notifications;
use YBoard\Model\Posts;
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

        while ($mq->receive(MessageQueue::MSG_TYPE_ALL, $msgType, 102400, $message)) {
            $this->connectDb();
            try {
                switch ($msgType) {
                    case MessageQueue::MSG_TYPE_DO_PNGCRUSH:
                        $this->doPngCrush($message);
                        break;
                    case MessageQueue::MSG_TYPE_PROCESS_VIDEO:
                        $this->processVideo($message);
                        break;
                    case MessageQueue::MSG_TYPE_PROCESS_AUDIO:
                        $this->processAudio($message);
                        break;
                    case MessageQueue::MSG_TYPE_ADD_POST_NOTIFICATION:
                        $this->addPostNotification($message);
                        break;
                    case MessageQueue::MSG_TYPE_REMOVE_POST_NOTIFICATION:
                        $this->removePostNotification($message);
                        break;
                    default:
                        CliLogger::write('[ERROR] Unknown message type: ' . $msgType);
                        break;
                }
            } catch (\Throwable $e) {
                CliLogger::write($e->getMessage());
            }

            $this->closeDb();
            $msgType = null;
            $message = null;
        }
    }

    protected function doPngCrush(int $fileId) : bool
    {
        $files = new Files($this->db);
        $file = $files->get($fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

            return false;
        }

        if ($file->extension != 'png') {
            CliLogger::write('[ERROR] Invalid file extension for PNGCrush: ' . $file->extension);

            return false;
        }

        $filePath = $this->config['files']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;
        FileHandler::pngCrush($filePath);
        $md5 = md5(file_get_contents($filePath));

        $file->saveMd5List([$md5]);
        $file->updateSize(filesize($filePath));
        $file->updateInProgress(false);

        return true;
    }

    protected function processVideo(int $fileId) : bool
    {
        // $message should be int fileId
        $files = new Files($this->db);
        $file = $files->get($fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

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
        $file->saveMd5List([$md5]);
        $file->updateSize(filesize($filePath));
        $file->updateInProgress(false);

        return true;
    }

    protected function processAudio(int $fileId) : bool
    {
        $files = new Files($this->db);
        $file = $files->get($fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

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
        $file->saveMd5List([$md5]);
        $file->updateSize(filesize($filePath));
        $file->updateInProgress(false);

        return true;
    }

    protected function addPostNotification(array $message) : bool
    {
        // Message should be [notificationType, postId, [userId], skipUsers]
        if (!is_array($message)) {
            return false;
        }

        if (count($message) != 3) {
            return false;
        }

        list($notificationType, $postId, $skipUsers) = $message;

        $posts = new Posts($this->db);
        $notifications = new Notifications($this->db);

        if (!is_array($postId)) {
            $repliedPost = $posts->get($postId, false);
            if (!$repliedPost || empty($repliedPost->userId)) {
                return false;
            }

            if (in_array($repliedPost->userId, $skipUsers)) {
                return true;
            }
            $notifications->add($repliedPost->userId, $notificationType, null, $repliedPost->id);
        } else {
            $repliedPosts = $posts->get($postId, false);
            foreach ($repliedPosts as $repliedPost) {
                if (in_array($repliedPost->userId, $skipUsers)) {
                    continue;
                }

                $notifications->add($repliedPost->userId, $notificationType, null, $repliedPost->id);
                $skipUsers[] = $repliedPost->userId;
            }
        }

        return true;
    }

    protected function removePostNotification($message) : bool
    {
        // Message should be $postId or [$postId, $postId, $postId, ...]
        $notifications = new Notifications($this->db);

        if (!is_array($message) || empty($message['types']) || empty($message['posts'])) {
            return false;
        }

        $notifications->decrementCountByPostId($message['posts'], $message['types']);
        $notifications->clearInvalid();

        return true;
    }
}
