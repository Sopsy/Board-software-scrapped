<?php
namespace YFW\Library;

class CliLogger
{
    public static function write(string $str, ?string $logFile = null): void
    {
        if ($logFile === null) {
            $logFile = ROOT_PATH . '/Log/cli.log';
        }

        $str = '[' . date('r') . '] ' . $str . "\n";

        file_put_contents($logFile, $str, FILE_APPEND);
    }
}
