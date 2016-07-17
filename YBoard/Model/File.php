<?php
namespace YBoard\Model;

use YBoard\Library\Database;
use YBoard\Model;

class File extends Model
{
    public $id;
    public $displayName;
    public $folder;
    public $name;
    public $extension;
    public $size;
    public $width = null;
    public $height = null;
    public $duration = null;
    public $hasThumbnail = true;
    public $hasSound = null;
    public $isGif = null;
    public $inProgress = false;

    public function __construct(Database $db, $data = [])
    {
        parent::__construct($db);

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'file_id':
                    $this->id = (int)$val;
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

    public function updateSize(int $fileSize) : bool
    {
        $q = $this->db->prepare('UPDATE files SET size = :size WHERE id = :id LIMIT 1');
        $q->bindValue('size', $fileSize);
        $q->bindValue('id', $this->id);
        $q->execute();

        return true;
    }

    public function updateInProgress(bool $inProgress) : bool
    {
        $q = $this->db->prepare('UPDATE files SET in_progress = :in_progress WHERE id = :id LIMIT 1');
        $q->bindValue('id', $this->id);
        $q->bindValue('in_progress', $inProgress);
        $q->execute();

        return true;
    }

    public function saveMd5List(array $md5List) : bool
    {
        $values = '';
        foreach ($md5List as &$md5) {
            $values .= '(' . (int)$this->id . ', ?),';
            $md5 = hex2bin($md5);
        }
        $values = substr($values, 0, -1);

        $q = $this->db->prepare("INSERT IGNORE INTO files_md5 (file_id, md5) VALUES " . $values);
        $q->execute($md5List);

        return $q !== false;
    }
}
