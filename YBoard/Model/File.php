<?php
namespace YBoard\Model;

use YFW\Exception\FileUploadException;
use YFW\Library\Database;
use YBoard\Model;

class File extends Model
{
    public $id;
    public $userId;
    public $displayName;
    public $folder;
    public $name;
    public $extension;
    public $size;
    public $width = null;
    public $height = null;
    public $thumbWidth = null;
    public $thumbHeight = null;
    public $duration = null;
    public $hasThumbnail = true;
    public $hasSound = null;
    public $isGif = null;
    public $inProgress = false;

    protected static $selectQuery = 'id AS file_id, user_id AS file_user_id, folder AS file_folder, name AS file_name,
        extension AS file_extension, size AS file_size, width AS file_width, height AS file_height,
        thumb_width AS file_thumb_width, thumb_height AS file_thumb_height,
        duration AS file_duration, in_progress AS file_in_progress, has_sound AS file_has_sound,
        is_gif AS file_is_gif';

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'file_id':
                    $this->id = (int)$val;
                    break;
                case 'file_user_id':
                    $this->userId = (int)$val;
                    break;
                case 'file_folder':
                    $this->folder = $val;
                    break;
                case 'file_name':
                    $this->name = $val;
                    break;
                case 'file_extension':
                    $this->extension = $val;
                    break;
                case 'file_size':
                    $this->size = (int)$val;
                    break;
                case 'file_width':
                    $this->width = (int)$val;
                    break;
                case 'file_height':
                    $this->height = (int)$val;
                    break;
                case 'file_thumb_width':
                    $this->thumbWidth = (int)$val;
                    break;
                case 'file_thumb_height':
                    $this->thumbHeight = (int)$val;
                    break;
                case 'file_display_name':
                    $this->displayName = $val;
                    break;
                case 'file_duration':
                    $this->duration = (int)$val;
                    break;
                case 'file_has_thumbnail':
                    $this->hasThumbnail = (bool)$val;
                    break;
                case 'file_has_sound':
                    $this->hasSound = (bool)$val;
                    break;
                case 'file_is_gif':
                    $this->isGif = (bool)$val;
                    break;
                case 'file_in_progress':
                    $this->inProgress = (bool)$val;
                    break;
            }
        }
    }

    public function setSize(int $fileSize): bool
    {
        $q = $this->db->prepare('UPDATE file SET size = :size WHERE id = :id LIMIT 1');
        $q->bindValue(':size', $fileSize, Database::PARAM_INT);
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function setDimensions(?int $width, ?int $height, ?int $thumbWidth = null, ?int $thumbHeight = null): bool
    {
        $q = $this->db->prepare('UPDATE file SET width = :width, height = :height,
            thumb_width = :thumb_width, thumb_height = :thumb_height
            WHERE id = :id LIMIT 1');
        $q->bindValue(':width', $width);
        $q->bindValue(':height', $height);
        $q->bindValue(':thumb_width', $thumbWidth);
        $q->bindValue(':thumb_height', $thumbHeight);
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function setInProgress(bool $inProgress): bool
    {
        $q = $this->db->prepare('UPDATE file SET in_progress = :in_progress WHERE id = :id LIMIT 1');
        $q->bindValue(':in_progress', $inProgress, Database::PARAM_INT);
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function saveMd5List(array $md5List): bool
    {
        $values = '';
        foreach ($md5List as &$md5) {
            $values .= '(' . (int)$this->id . ', ?),';
            $md5 = hex2bin($md5);
        }
        $values = substr($values, 0, -1);

        $q = $this->db->prepare("INSERT IGNORE INTO file_md5 (file_id, md5) VALUES " . $values);
        $q->execute($md5List);

        return $q !== false;
    }

    public function delete(): bool
    {
        $q = $this->db->prepare("DELETE FROM file WHERE id = :id LIMIT 1");
        $q->bindValue(':id', $this->id, Database::PARAM_INT);
        $q->execute();

        return $q->rowCount() !== 0;
    }

    public static function get(Database $db, int $fileId): ?self
    {
        $q = $db->prepare('SELECT ' . static::$selectQuery . ' FROM file WHERE id = :file_id LIMIT 1');
        $q->bindValue(':file_id', $fileId, Database::PARAM_INT);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByOrigName(Database $db, string $fileName): ?self
    {
        $q = $db->prepare('SELECT ' . static::$selectQuery . ' FROM post_file a
            LEFT JOIN file b ON a.file_id = b.id
            WHERE file_name = :file_name LIMIT 1');
        $q->bindValue(':file_name', $fileName);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByName(Database $db, string $fileName): ?self
    {
        $q = $db->prepare('SELECT ' . static::$selectQuery . ' FROM file WHERE name = :name LIMIT 1');
        $q->bindValue(':name', $fileName);
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        return new self($db, $q->fetch());
    }

    public static function getByMd5(Database $db, string $md5): ?self
    {
        $q = $db->prepare("SELECT file_id FROM file_md5 WHERE md5 = :md5 LIMIT 1");
        $q->bindValue(':md5', hex2bin($md5));
        $q->execute();

        if ($q->rowCount() == 0) {
            return null;
        }

        $row = $q->fetch();
        $file = new self($db);
        $file->id = $row->file_id;

        return $file;
    }

    public static function exists(Database $db, string $name): bool
    {
        $q = $db->prepare("SELECT id FROM file WHERE name = :name LIMIT 1");
        $q->bindValue(':name', $name);
        $q->execute();

        return $q->rowCount() != 0;
    }

    public static function deleteOrphans(Database $db): bool
    {
        $db->query("DELETE FROM file WHERE id NOT IN (SELECT file_id FROM post_file)");

        return true;
    }
}
