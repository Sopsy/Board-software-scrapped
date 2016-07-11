<?php
namespace YBoard\Data;

class Thread extends Post
{
    public $boardUrl;
    public $locked;
    public $sticky;
    public $subject;
    public $replies;
    public $statistics;
    public $threadReplies;
}
