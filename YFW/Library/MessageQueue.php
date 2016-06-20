<?php
namespace YFW\Library;

class MessageQueue
{
    protected $queue;
    protected $queueId;

    public function __construct(int $queueId)
    {
        $this->queueId = $queueId;
        $this->queue = msg_get_queue($this->queueId);
    }

    public function send($data, int $msgType): bool
    {
        $send = msg_send($this->queue, $msgType, $data);
        if ($send) {
            return true;
        }

        return false;
    }

    public function receive(int $desiredMsgType, &$msgType, $maxSize, &$message): bool
    {
        return msg_receive($this->queue, $desiredMsgType, $msgType, $maxSize, $message);
    }

    public function stat(): array
    {
        return msg_stat_queue($this->queue);
    }
}
