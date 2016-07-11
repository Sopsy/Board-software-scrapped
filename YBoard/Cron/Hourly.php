<?php
namespace YBoard\Cron;

use YBoard\Abstracts\CronDatabase;

class Hourly extends CronDatabase
{
    public function runJob()
    {
        $run = new DeleteOldUsers($this->config, $this->db);
        $run->runJob();

        $run = new DeleteOldPosts($this->config, $this->db);
        $run->runJob();
    }
}
