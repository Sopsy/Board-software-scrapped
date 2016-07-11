<?php
if (php_sapi_name() != "cli") {
    die("This script should be run from the CLI.\n\n");
}

if (empty($argv[1])) {
    die("Missing command\n\n");
}

$command = $argv[1];

// Forward to bootstrap
require('YBoard/CronBootstrap.php');
