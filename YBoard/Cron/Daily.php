<?php
namespace YBoard\Cron;

use YBoard\Abstracts\CronDatabase;

class Daily extends CronDatabase
{
    public function runJob()
    {
        $run = new DeleteOldFiles($this->config, $this->db);
        $run->runJob();
    }
}
