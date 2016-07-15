<?php
namespace YBoard\Library;

class MessageQueue
{
    const MSG_TYPE_ALL = 0;
    const MSG_TYPE_DO_PNGCRUSH = 1;
    const MSG_TYPE_PROCESS_VIDEO = 2;
    const MSG_TYPE_PROCESS_AUDIO = 3;
    const MSG_TYPE_ADD_POST_NOTIFICATION = 4;
    const MSG_TYPE_REMOVE_POST_NOTIFICATION = 5;

    protected $queue;
    protected $queueId;

    public function __construct($queueId = 190675) // Just a random number
    {
        $this->queueId = $queueId;
        $this->queue = msg_get_queue($this->queueId);
    }

    public function send($data, int $msgType) : bool
    {
        $send = msg_send($this->queue, $msgType, $data);
        if ($send) {
            return true;
        }

        return false;
    }

    public function receive(int $desiredMsgType, &$msgType, $maxSize, &$message) : bool
    {
        return msg_receive($this->queue, $desiredMsgType, $msgType, $maxSize, $message);
    }

    public function stat()
    {
        return msg_stat_queue($this->queue);
    }
}
