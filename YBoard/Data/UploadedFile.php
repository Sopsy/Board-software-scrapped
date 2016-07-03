<?php
namespace YBoard\Data;

class UploadedFile
{
    public $id;
    public $tmpName;
    public $folder;
    public $name;
    public $extension;
    public $origName;
    public $size;
    public $md5 = [];
    public $thumbDestination;
    public $destination;
    public $destinationFormat;

    public function __construct()
    {
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
