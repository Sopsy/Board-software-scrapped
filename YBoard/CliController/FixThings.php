<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Model\Files;

class FixThings extends CliDatabase
{
    public function thumbSizes()
    {
        $files = new Files($this->db);

        $glob = glob(ROOT_PATH . '/static/files/*/t/*.*');
        foreach ($glob AS $filePath) {
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);

            $file = $files->getByName($fileName);
            if (!$file) {
                continue;
            }

            list($thumbWidth, $thumbHeight) = getimagesize($filePath);
            $file->updateThumbSize($thumbWidth, $thumbHeight);

            echo "\n" . $filePath . " updated";
        }
        echo "\n\n";
    }
}
