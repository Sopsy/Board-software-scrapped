<?php
namespace YBoard\CliController;

use YBoard\CliController;

class Cron extends CliController
{
    public function daily(): void
    {
        $cleanup = new Cleanup($this->config, $this->db);
        $cleanup->deleteOldFiles();
    }

    public function hourly(): void
    {
        $cleanup = new Cleanup($this->config, $this->db);
        $cleanup->deleteOldPosts();
        $cleanup->deleteOldUsers();
    }
}
