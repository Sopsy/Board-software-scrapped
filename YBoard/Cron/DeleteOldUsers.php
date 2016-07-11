<?php
namespace YBoard\Cron;

use YBoard\Abstracts\CronDatabase;
use YBoard\Model\User;

class DeleteOldUsers extends CronDatabase
{
    public function runJob()
    {
        $user = new User($this->db);

        // Expire old sessions
        $expiredSessions = $user->getExpiredSessions();
        foreach ($expiredSessions as $sessionId) {
            // Looping a query is not good practise, but I'm lazy.
            $user->destroySession($sessionId);
        }

        // Delete unusable user accounts
        $unusable = $user->getUnusable();
        foreach ($unusable as $userId) {
            // Looping a query is not good practise, but I'm lazy.
            $user->delete($userId, '', true);
        }

        echo count($expiredSessions) . " expired sessions deleted\n";
        echo count($unusable) . " users deleted\n";
    }
}
