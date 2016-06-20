<?php
namespace YBoard;

class MessageQueue extends \YFW\Library\MessageQueue
{
    const MSG_TYPE_ALL = 0;
    const MSG_TYPE_DO_PNGCRUSH = 1;
    const MSG_TYPE_PROCESS_VIDEO = 2;
    const MSG_TYPE_PROCESS_AUDIO = 3;
    const MSG_TYPE_ADD_POST_NOTIFICATION = 4;
    const MSG_TYPE_REMOVE_POST_NOTIFICATION = 5;

    public function __construct()
    {
        parent::__construct(123743); // Just a random number
    }
}
