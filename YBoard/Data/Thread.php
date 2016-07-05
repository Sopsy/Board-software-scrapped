<?php
namespace YBoard\Data;

class Thread
{
    public $id;
    public $locked;
    public $boardId;
    public $userId;
    public $ip;
    public $countryCode;
    public $time;
    public $sticky;
    public $points;
    public $username;
    public $subject;
    public $message;
    public $messageFormatted;
    public $file = false;
    public $replies;
}
