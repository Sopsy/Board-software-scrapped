<?php
namespace YBoard\Data;

class Thread extends Post
{
    public $locked;
    public $boardId;
    public $sticky;
    public $points;
    public $subject;
    public $replies;
}
