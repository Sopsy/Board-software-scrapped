<?php
namespace YBoard\Model;

use YBoard\Library\Database;

class UploadedFile extends File
{
    public $tmpName;
    public $md5 = [];
    public $thumbDestination;
    public $destination;
    public $destinationFormat;

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

    public function destroy()
    {
        if (!empty($this->thumbDestination) && is_file($this->thumbDestination)) {
            unlink($this->thumbDestination);
        }
        if (!empty($this->destination) && is_file($this->destination)) {
            unlink($this->destination);
        }
    }
}
