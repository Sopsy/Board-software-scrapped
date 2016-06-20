<?php
namespace YBoard\Model;

use YBoard\MessageQueue;
use YFW\Exception\FileUploadException;
use YFW\Exception\InternalException;
use YFW\Library\Database;
use YFW\Library\FileHandler;
use YFW\Library\Text;

class UploadedFile extends File
{
    public $tmpName;
    public $md5 = [];
    public $thumbDestination;
    public $destination;
    public $destinationFormat;
    public $savePath = false;
    public $maxPixelCount = 50000000;
    public $imgMaxWidth = 1920;
    public $imgMaxHeight = 1920;
    public $thumbMaxWidth = 240;
    public $thumbMaxHeight = 240;

    public function __construct(Database $db)
    {
        parent::__construct($db);
        $this->tmpName = sys_get_temp_dir() . '/uploadedfile-' . time() . mt_rand(000000, 999999);
    }

    public function __destruct()
    {
        if (is_file($this->tmpName)) {
            unlink($this->tmpName);
        }
    }

    public function destroy(): void
    {
        if (!empty($this->thumbDestination) && is_file($this->thumbDestination)) {
            unlink($this->thumbDestination);
        }
        if (!empty($this->destination) && is_file($this->destination)) {
            unlink($this->destination);
        }
    }

    public function setConfig(array $config): bool
    {
        $keys = [
            'savePath',
            'maxPixelCount',
            'imgMaxWidth',
            'imgMaxHeight',
            'thumbMaxWidth',
            'thumbMaxHeight',
        ];

        foreach ($keys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }

        return true;
    }

    public function processUpload(array $file, int $userId, bool $skipMd5Check = false): bool
    {
        // Verify config
        if (!$this->savePath) {
            throw new InternalException(_('File save path not set'));
        }

        $sendMessage = false;

        // Rename uploaded file
        if (!move_uploaded_file($file['tmp_name'], $this->tmpName)) {
            throw new InternalException(_('Cannot move uploaded file'));
        }

        $md5 = md5(file_get_contents($this->tmpName));
        $this->displayName = pathinfo($file['name'], PATHINFO_FILENAME);
        $this->extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (empty($this->extension)) {
            throw new FileUploadException(_('The file you uploaded is missing a file extension (e.g. ".jpg")'));
        }

        // If the file already exists, use the old one
        if (!$skipMd5Check) {
            $oldFile = File::getByMd5($this->db, $md5);
            if ($oldFile !== false) {
                $this->id = $oldFile->id;

                return true;
            }
        }

        $this->md5[] = $md5;

        $this->folder = Text::randomStr(2, false);
        $this->name = Text::randomStr(8, false);
        $this->size = filesize($this->tmpName);

        // Figure out in which format to save the file
        switch ($this->extension) {
            case 'gif':
                $frames = FileHandler::getGifFrameCount($this->tmpName);
                if ($frames === 0) {
                    throw new FileUploadException(_('Cannot get the number of GIF frames. The file may be corrupted.'));
                } elseif ($frames > 4000) {
                    throw new FileUploadException(_('The GIF you uploaded is too long, please upload a video file instead'));
                }

                if ($frames == 1) {
                    $this->destinationFormat = 'jpg';
                } else {
                    $videoMeta = FileHandler::getVideoMeta($this->tmpName);
                    if ($videoMeta === null) {
                        throw new FileUploadException(_('Invalid or corrupted media'));
                    }

                    $this->destinationFormat = 'mp4';
                    $this->isGif = true;
                }
                break;
            case 'jpeg':
            case 'jpg':
                $this->destinationFormat = 'jpg';
                break;
            case 'png':
                $this->destinationFormat = 'png';
                break;
            case 'mp3':
            case 'm4a':
            case 'aac':
            case 'mp4':
            case 'webm':
                $videoMeta = FileHandler::getVideoMeta($this->tmpName);
                if ($videoMeta === null) {
                    throw new FileUploadException(_('Invalid or corrupted media'));
                }

                if ($videoMeta->duration > 300) {
                    throw new FileUploadException(_('The media you uploaded is too long'));
                }

                $this->duration = $videoMeta->duration;
                $this->hasSound = $videoMeta->hasSound;

                if (!$videoMeta->audioOnly) {
                    $this->width = $videoMeta->width;
                    $this->height = $videoMeta->height;
                    $this->destinationFormat = 'mp4';
                } else {
                    $this->destinationFormat = 'm4a';
                }
                break;
            default:
                $this->destinationFormat = $this->extension;
                break;
        }

        // Set file destination names
        $this->thumbDestination = $this->savePath . '/' . $this->folder . '/t/' . $this->name . '.jpg';
        $this->destination = $this->savePath . '/' . $this->folder . '/o/' . $this->name . '.' . $this->destinationFormat;

        // Create directories if needed
        if (!is_dir($this->savePath . '/' . $this->folder . '/t')) {
            if (!mkdir($this->savePath . '/' . $this->folder . '/t', 0775, true)) {
                throw new InternalException(_('Creating a file directory failed'));
            }
        }
        if (!is_dir($this->savePath . '/' . $this->folder . '/o')) {
            if (!mkdir($this->savePath . '/' . $this->folder . '/o', 0775, true)) {
                throw new InternalException(_('Creating a file directory failed'));
            }
        }

        // Do whatever we do with the uploaded files here.
        switch ($this->destinationFormat) {
            case 'jpg':
            case 'png':
                $this->limitPixelCount($this->tmpName);

                FileHandler::createImage($this->tmpName, $this->destination, $this->imgMaxWidth, $this->imgMaxHeight,
                    $this->destinationFormat);
                FileHandler::createThumbnail($this->destination, $this->thumbDestination, $this->thumbMaxWidth,
                    $this->thumbMaxHeight, 'jpg');

                if ($this->destinationFormat == 'png') {
                    $sendMessage = MessageQueue::MSG_TYPE_DO_PNGCRUSH;
                }

                if (!FileHandler::verifyFile($this->destination) || !FileHandler::verifyFile($this->thumbDestination)) {
                    $this->destroy();
                    throw new InternalException(_('Saving the uploaded file failed'));
                }

                $this->md5[] = md5(file_get_contents($this->destination));
                $this->md5[] = md5(file_get_contents($this->thumbDestination));

                // Get size of the final images
                [$this->width, $this->height] = getimagesize($this->destination);
                [$this->thumbWidth, $this->thumbHeight] = getimagesize($this->thumbDestination);

                break;
            case 'm4a':
                $this->inProgress = true;
                $this->hasThumbnail = false;

                rename($this->tmpName, $this->destination);

                $sendMessage = MessageQueue::MSG_TYPE_PROCESS_AUDIO;

                break;
            case 'mp4':
                $this->inProgress = true;
                if ($this->isGif === null) {
                    $this->isGif = false;
                }

                rename($this->tmpName, $this->destination);
                FileHandler::createThumbnail($this->destination, $this->thumbDestination, $this->thumbMaxWidth,
                    $this->thumbMaxHeight, 'jpg');

                $sendMessage = MessageQueue::MSG_TYPE_PROCESS_VIDEO;

                // Get size of the thumbnail
                [$this->thumbWidth, $this->thumbHeight] = getimagesize($this->thumbDestination);

                break;
            default:
                throw new FileUploadException(sprintf(_('Unsupported file type: %s'), $this->extension));
        }

        error_log($this->thumbWidth);
        error_log($this->thumbHeight);
        // Save file to database
        $q = $this->db->prepare("INSERT INTO file (user_id, folder, name, extension, size, width, height,
            thumb_width, thumb_height, duration, has_thumbnail, has_sound, is_gif, in_progress)
            VALUES (:user_id, :folder, :name, :extension, :size, :width, :height, :thumb_width, :thumb_height,
            :duration, :has_thumbnail, :has_sound, :is_gif, :in_progress)");
        $q->bindValue(':user_id', $userId);
        $q->bindValue(':folder', $this->folder);
        $q->bindValue(':name', $this->name);
        $q->bindValue(':extension', $this->destinationFormat);
        $q->bindValue(':size', $this->size, Database::PARAM_INT);
        $q->bindValue(':width', $this->width, Database::PARAM_INT);
        $q->bindValue(':height', $this->height, Database::PARAM_INT);
        $q->bindValue(':thumb_width', $this->thumbWidth);
        $q->bindValue(':thumb_height', $this->thumbHeight);
        $q->bindValue(':duration', $this->duration, Database::PARAM_INT);
        $q->bindValue(':has_thumbnail', $this->hasThumbnail, Database::PARAM_INT);
        $q->bindValue(':has_sound', $this->hasSound, Database::PARAM_INT);
        $q->bindValue(':is_gif', $this->isGif, Database::PARAM_INT);
        $q->bindValue(':in_progress', $this->inProgress, Database::PARAM_INT);
        $q->execute();

        $this->id = $this->db->lastInsertId();
        if (!$this->id) {
            throw new FileUploadException(sprintf(_('File upload failed')));
        }

        if ($sendMessage) {
            $mq = new MessageQueue();
            $mq->send($this->id, $sendMessage);
        }

        // Save MD5
        $this->saveMd5List($this->md5);

        return true;
    }

    protected function limitPixelCount(string $file): void
    {
        if ($this->getPixelCount($file) > $this->maxPixelCount) {
            throw new FileUploadException(sprintf(_('Uploaded file exceeds the max pixel count of %s MP'),
                round($this->maxPixelCount / 1000000, 2)));
        }
    }

    protected function getPixelCount(string $file): int
    {
        $sizes = getimagesize($file);

        if (!$sizes) {
            throw new FileUploadException(_('The file is not a valid image'));
        }

        return $sizes[0] * $sizes[1];
    }
}
