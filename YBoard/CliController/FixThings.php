<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Model\Files;

class FixThings extends CliDatabase
{
    public function filesizes()
    {
        $files = new Files($this->db);

        $glob = glob(ROOT_PATH . '/static/files/*/t/*.*');
        foreach ($glob AS $filePath) {
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);

            $file = $files->getByName($fileName);
            if (!$file) {
                continue;
            }

            $file->updateSize(filesize($filePath));

            echo "\n" . $filePath . " updated";
        }
    }
}
