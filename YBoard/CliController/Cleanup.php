<?php
namespace YBoard\CliController;

use YBoard\Abstracts\CliDatabase;
use YBoard\Model\Boards;
use YBoard\Model\Files;
use YBoard\Model\Posts;
use YBoard\Model\User;
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
            echo "\n" . $file . " deleted\n";
            ++$count;
        }

        echo "\n\n" . $count . " files deleted\n";
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

        $posts->deleteMultiple($threads);

        echo count($threads) . " threads deleted\n";
    }

    public function deleteOldUsers()
    {
        $user = new User($this->db);
        $userSessions = new UserSessions($this->db);

        // Expire old sessions
        $expiredSessions = $userSessions->getExpired();
        foreach ($expiredSessions as $sessionId) {
            // Looping a query is not good practise, but I'm lazy.
            $userSessions->destroy($sessionId);
        }

        // Delete unusable user accounts
        $unusable = $user->getUnusable();
        foreach ($unusable as $userId) {
            // Looping a query is not good practise, but I'm lazy.
            $user->delete($userId);
        }

        echo count($expiredSessions) . " expired sessions deleted\n";
        echo count($unusable) . " users deleted\n";
    }
}
