<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;

class CronHourly extends CliDatabase
{
    public function index()
    {
        $cleanup = new Cleanup($this->config, $this->db);
        $cleanup->deleteOldPosts();
        $cleanup->deleteOldUsers();
    }
}
