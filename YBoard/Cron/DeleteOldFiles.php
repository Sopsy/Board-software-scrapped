<?php
namespace YBoard\Cron;

use YBoard\Abstracts\CronDatabase;
use YBoard\Model\Files;

class DeleteOldFiles extends CronDatabase
{
    public function runJob()
    {
        $files = new Files($this->db);
        $files->deleteOrphans();

        $glob = glob(ROOT_PATH . '/static/files/*/*/*.*');
        $i = 1;
        $count = 0;
        foreach ($glob AS $file) {
            if ($i % 1000 == 0) {
                echo '.';
            }
            ++$i;

            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if ($files->exists($fileName)) {
                continue;
            }

            unlink($file);
            echo "\n" . $file . " deleted\n";
            ++$count;
        }

        echo "\n\n" . $count . " files deleted\n";
    }
}
