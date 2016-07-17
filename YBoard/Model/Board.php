<?php
namespace YBoard\Model;

class Board
{
    public $id;
    public $name;
    public $description;
    public $url;
    public $altUrl;
    public $isNsfw = false;
    public $isHidden = false;
    public $showFlags = false;
    public $inactiveHoursDelete = false;
}
