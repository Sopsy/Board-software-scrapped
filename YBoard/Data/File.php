<?php
namespace YBoard\Data;

class File
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
}
