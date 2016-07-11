<?php
namespace YBoard\Cron;

use YBoard\Abstracts\CronDatabase;
use YBoard\Model\Boards;
use YBoard\Model\Posts;

class DeleteOldPosts extends CronDatabase
{
    public function runJob()
    {
        $boards = new Boards($this->db);
        $posts = new Posts($this->db);

        $threads = [];
        foreach ($boards->getAll() as $board) {
            if (!$board->inactiveHoursDelete) {
                continue;
            }

            $threads = array_merge($threads, $posts->getOldThreads($board->id, $board->inactiveHoursDelete));
        }

        $posts->deleteMultiple($threads);

        echo count($threads) . " threads deleted\n";
    }
}
