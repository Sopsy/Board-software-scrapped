<?php
namespace YBoard\Model;

use YBoard\Data\UploadedFile;
use YBoard\Exceptions\FileUploadException;
use YBoard\Exceptions\InternalException;
use YBoard\Library\FileHandler;
use YBoard\Library\Text;
use YBoard\Model;

class Files extends Model
{
    public $savePath = false;
    public $maxPixelCount = 50000000;
    public $imgMaxWidth = 1920;
    public $imgMaxHeight = 1920;
    public $thumbMaxWidth = 240;
    public $thumbMaxHeight = 240;

    public function setConfig(array $config) : bool
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

    public function processUpload(array $file) : UploadedFile
    {
        // Verify config
        if (!$this->savePath) {
            throw new InternalException(_('File save path not set'));
        }

        $uploadedFile = new UploadedFile();

        // Rename uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile->tmpName)) {
            throw new FileUploadException(_('Cannot move uploaded file'));
        }

        $md5 = md5(file_get_contents($uploadedFile->tmpName));
        $uploadedFile->origName = pathinfo($file['name'], PATHINFO_FILENAME);
        $uploadedFile->extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // If the file already exists, use the old one
        $oldId = $this->getByMd5($md5);
        if ($oldId) {
            $uploadedFile->id = $oldId;

            return $uploadedFile;
        }

        // File type conversions
        if ($uploadedFile->extension == 'jpeg') {
            // JPEG -> JPG
            $uploadedFile->extension = 'jpg';
        }

        if ($uploadedFile->extension == 'gif') {
            // GIF -> JPG or MP4
            $frames = FileHandler::getGifFrameCount($uploadedFile->tmpName);
            if ($frames === 0) {
                throw new InternalException(_('Cannot get the number of GIF frames'));
            }
            $uploadedFile->extension = $frames == 1 ? 'jpg' : 'mp4';
        }

        if ($uploadedFile->extension == 'webm') {
            // WEBM -> MP4
            $uploadedFile->extension = 'mp4';
        }

        if ($uploadedFile->extension == 'mp3') {
            // MP3 -> MP4
            $uploadedFile->extension = 'mp4';
        }

        $uploadedFile->md5[] = $md5;

        $uploadedFile->folder = Text::randomStr(2, false);
        $uploadedFile->name = Text::randomStr(8, false);
        $uploadedFile->size = filesize($uploadedFile->tmpName);

        switch ($uploadedFile->extension) {
            case 'jpg':
                $uploadedFile->destinationFormat = 'jpg';
                break;
            case 'png':
                $uploadedFile->destinationFormat = 'png';
                break;
            default:
                $uploadedFile->destinationFormat = $uploadedFile->extension;
                break;
        }

        // Set file destination names
        $uploadedFile->thumbDestination = $this->savePath . '/' . $uploadedFile->folder . '/t/' . $uploadedFile->name . '.jpg';
        $uploadedFile->destination = $this->savePath . '/' . $uploadedFile->folder . '/o/' . $uploadedFile->name . '.' . $uploadedFile->destinationFormat;

        // Create directories if needed
        if (!is_dir($this->savePath . '/' . $uploadedFile->folder . '/t')) {
            if (!mkdir($this->savePath . '/' . $uploadedFile->folder . '/t', 0775, true)) {
                throw new InternalException(_('Creating a file directory failed'));
            }
        }
        if (!is_dir($this->savePath . '/' . $uploadedFile->folder . '/o')) {
            if (!mkdir($this->savePath . '/' . $uploadedFile->folder . '/o', 0775, true)) {
                throw new InternalException(_('Creating a file directory failed'));
            }
        }

        // Do whatever we do with the uploaded files here.
        switch ($uploadedFile->extension) {
            case 'jpg':
            case 'png':
                $this->limitPixelCount($uploadedFile->tmpName);

                FileHandler::createImage($uploadedFile->tmpName, $uploadedFile->destination, $this->imgMaxWidth,
                    $this->imgMaxHeight, $uploadedFile->destinationFormat);
                FileHandler::createThumbnail($uploadedFile->destination, $uploadedFile->thumbDestination,
                    $this->thumbMaxWidth, $this->thumbMaxHeight, 'jpg');

                if ($uploadedFile->extension == 'png') {
                    FileHandler::pngCrush($uploadedFile->destination);
                }

                if (!FileHandler::verifyFile($uploadedFile->destination) || !FileHandler::verifyFile($uploadedFile->thumbDestination)) {
                    $uploadedFile->destroy();
                    throw new InternalException(_('Saving the uploaded file failed'));
                }

                $uploadedFile->md5[] = md5(file_get_contents($uploadedFile->destination));
                $uploadedFile->md5[] = md5(file_get_contents($uploadedFile->thumbDestination));

                // Get size of the final image
                list($uploadedFile->width, $uploadedFile->height) = getimagesize($uploadedFile->destination);

                break;
            case 'mp4':
                throw new FileUploadException(sprintf(_('Unsupported file type: %s'), $uploadedFile->extension));
                // TODO: Add video support
                break;
            default:
                throw new FileUploadException(sprintf(_('Unsupported file type: %s'), $uploadedFile->extension));
        }

        // Save file to database
        $q = $this->db->prepare("INSERT INTO files (folder, name, extension, size, width, height)
            VALUES (:folder, :name, :extension, :size, :width, :height)");
        $q->bindValue('folder', $uploadedFile->folder);
        $q->bindValue('name', $uploadedFile->name);
        $q->bindValue('extension', $uploadedFile->destinationFormat);
        $q->bindValue('size', $uploadedFile->size);
        $q->bindValue('width', $uploadedFile->width);
        $q->bindValue('height', $uploadedFile->height);
        $q->execute();

        $uploadedFile->id = $this->db->lastInsertId();

        // Save MD5
        $this->saveMd5List($uploadedFile->id, $uploadedFile->md5);

        return $uploadedFile;
    }

    public function saveMd5List(int $fileId, array $md5List) : bool
    {
        $values = '';
        foreach ($md5List as &$md5) {
            $values .= '(' . (int)$fileId . ', ?),';
            $md5 = hex2bin($md5);
        }
        $values = substr($values, 0, -1);

        $q = $this->db->prepare("INSERT IGNORE INTO files_md5 (file_id, md5) VALUES " . $values);
        $q->execute($md5List);

        return $q !== false;
    }

    public function getByMd5(string $md5)
    {
        $q = $this->db->prepare("SELECT file_id FROM files_md5 WHERE md5 = :md5 LIMIT 1");
        $q->bindValue('md5', hex2bin($md5));
        $q->execute();

        if ($q->rowCount() == 0) {
            return false;
        }

        $fileId = (int)$q->fetch()->file_id;
        if ($fileId === 0) {
            return false;
        }

        return $fileId;
    }

    protected function limitPixelCount(string $file)
    {
        if ($this->getPixelCount($file) > $this->maxPixelCount) {
            throw new FileUploadException(sprintf(_('Uploaded file exceeds the max pixel count of %s MP'),
                round($this->maxPixelCount / 1000000, 2)));
        }
    }

    protected function getPixelCount(string $file) : int
    {
        $sizes = getimagesize($file);

        if (!$sizes) {
            throw new FileUploadException(_('The file is not a valid image'));
        }

        return $sizes[0] * $sizes[1];
    }
}
