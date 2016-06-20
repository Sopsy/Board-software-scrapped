<?php
namespace YBoard\CliController;

use YBoard\MessageQueue;
use YBoard\Model\File;
use YBoard\Model\Post;
use YBoard\Model\UserNotification;
use YFW\Library\CliLogger;
use YFW\Library\Database;
use YFW\Library\FileHandler;

class MessageListenerDaemon
{
    protected $config;
    protected $db = null;

    public function __construct()
    {
        // Load config
        $this->config = require(ROOT_PATH . '/YBoard/Config/App.php');
    }

    protected function connectDb(): void
    {
        $this->db = new Database(require(ROOT_PATH . '/YBoard/Config/Database.php'));
    }

    protected function closeDb(): void
    {
        $this->db = null;
    }

    public function index(): void
    {
        $mq = new MessageQueue();

        $msgType = null;
        $message = null;

        while ($mq->receive(MessageQueue::MSG_TYPE_ALL, $msgType, 102400, $message)) {
            echo 'GOT MESSAGE ' . $msgType . ':' . json_encode($message) . "\n";
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

    protected function doPngCrush(int $fileId): bool
    {
        $file = File::get($this->db, $fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

            return false;
        }

        if ($file->extension != 'png') {
            CliLogger::write('[ERROR] Invalid file extension for PNGCrush: ' . $file->extension);

            return false;
        }

        $filePath = $this->config['file']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;
        FileHandler::pngCrush($filePath);
        $md5 = md5(file_get_contents($filePath));

        $file->saveMd5List([$md5]);
        $file->setSize(filesize($filePath));
        $file->setInProgress(false);

        return true;
    }

    protected function processVideo(int $fileId): bool
    {
        // $message should be int fileId
        $file = File::get($this->db, $fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

            return false;
        }

        if ($file->extension != 'mp4') {
            CliLogger::write('[ERROR] Invalid file extension for video: ' . $file->extension);

            return false;
        }

        $filePath = $this->config['file']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;

        // Convert
        $convert = FileHandler::convertVideo($filePath);

        if (!$convert) {
            CliLogger::write('[ERROR] Video conversion failed: ' . $file->id);

            return false;
        }

        $md5 = md5(file_get_contents($filePath));
        $file->saveMd5List([$md5]);
        $file->setSize(filesize($filePath));
        $file->setInProgress(false);

        return true;
    }

    protected function processAudio(int $fileId): bool
    {
        $file = File::get($this->db, $fileId);
        if (!$file) {
            CliLogger::write('[ERROR] Invalid file: ' . $fileId);

            return false;
        }

        if ($file->extension != 'm4a') {
            CliLogger::write('[ERROR] Invalid file extension for video: ' . $file->extension);

            return false;
        }

        $filePath = $this->config['file']['savePath'] . '/' . $file->folder . '/o/' . $file->name . '.' . $file->extension;

        // Convert
        $convert = FileHandler::convertAudio($filePath);

        if (!$convert) {
            CliLogger::write('[ERROR] Audio conversion failed: ' . $file->id);

            return false;
        }

        $md5 = md5(file_get_contents($filePath));
        $file->saveMd5List([$md5]);
        $file->setSize(filesize($filePath));
        $file->setInProgress(false);

        return true;
    }

    protected function addPostNotification(array $message): bool
    {
        // Message should be [notificationType, postId, skipUsers]
        if (!is_array($message)) {
            return false;
        }

        if (count($message) != 3) {
            return false;
        }

        [$notificationType, $postId, $skipUsers] = $message;

        if (!is_array($postId)) {
            $repliedPost = Post::get($this->db, $postId, false);
            if ($repliedPost === null || empty($repliedPost->userId)) {
                return false;
            }

            if (in_array($repliedPost->userId, $skipUsers)) {
                return true;
            }

            UserNotification::create($this->db, $repliedPost->userId, $notificationType, null, $repliedPost->id);
            echo 'Added User: '. $repliedPost->userId . "\n";
        } else {
            foreach ($postId as $repliedPost) {
                $repliedPost = Post::get($this->db, $repliedPost, false);
                if ($repliedPost === null || empty($repliedPost->userId)) {
                    continue;
                }

                if (in_array($repliedPost->userId, $skipUsers)) {
                    continue;
                }

                UserNotification::create($this->db, $repliedPost->userId, $notificationType, null, $repliedPost->id);
                echo 'Added User: '. $repliedPost->userId . "\n";
                $skipUsers[] = $repliedPost->userId;
            }
        }

        return true;
    }

    protected function removePostNotification($message): bool
    {
        // Message should be $postId or [$postId, $postId, $postId, ...]
        if (!is_array($message) || empty($message['types']) || empty($message['posts'])) {
            return false;
        }

        UserNotification::decrementCountByPostId($this->db, $message['posts'], $message['types']);
        UserNotification::clearInvalid($this->db);

        return true;
    }
}
