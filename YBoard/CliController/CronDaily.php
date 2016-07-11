<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;

class CronDaily extends CliDatabase
{
    public function index()
    {
        $cleanup = new Cleanup($this->config, $this->db);
        $cleanup->deleteOldFiles();
    }
}
