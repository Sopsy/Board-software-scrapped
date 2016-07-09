<?php
namespace YBoard\Data;

class Post
{
    public $id;
    public $userId;
    public $ip;
    public $countryCode;
    public $boardId;
    public $threadId;
    public $time;
    public $username;
    public $message;
    public $messageFormatted;
    public $postReplies;
    public $file = false;
}
