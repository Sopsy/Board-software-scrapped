<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Model\Boards;
use YBoard\Model\Files;
use YBoard\Model\Posts;
use YBoard\Model\Users;
use YBoard\Model\UserSessions;

class Cleanup extends CliDatabase
{
    public function deleteOldFiles()
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
            if (!QUIET) {
                echo "\n" . $file . " deleted";
            }
            ++$count;
        }

        if (!QUIET) {
            echo "\n\n" . $count . " files deleted\n";
        }
    }

    public function deleteOldPosts()
    {
        $boards = new Boards($this->db);
        $posts = new Posts($this->db);

        $threads = [];
        foreach ($boards->getAll() as $board) {
            if (!$board->inactiveHoursDelete) {
                continue;
            }

            $threads = array_merge($threads, $posts->getOldThreads($board->id, $board->inactiveHoursDelete));
        }

        if (!empty($threads)) {
            $posts->deleteMany($threads);
        }

        if (!QUIET) {
            echo count($threads) . " threads deleted\n";
        }
    }

    public function deleteOldUsers()
    {
        $users = new Users($this->db);
        $userSessions = new UserSessions($this->db);

        // Expire old sessions
        $expiredSessions = $userSessions->getExpiredIds();
        if (!empty($expiredSessions)) {
            $userSessions->destroyMany($expiredSessions);
        }

        // Delete unusable user accounts
        $unusable = $users->getUnusable();
        if (!empty($unusable)) {
            $users->deleteMany($unusable);
        }

        if (!QUIET) {
            echo count($expiredSessions) . " expired sessions deleted\n";
            echo count($unusable) . " users deleted\n";
        }
    }
}
