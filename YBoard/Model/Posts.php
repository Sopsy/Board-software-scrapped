<?php
namespace YBoard\Model;

use YBoard;

class Posts extends YBoard\Model
{
    public function getThread($id) {
        $q = $this->db->prepare('SELECT id, locked, board FROM posts WHERE id = :id AND thread = 0 LIMIT 1');
        $q->bindValue('id', $id);

        if ($q->rowCount() == 0) {
            return false;
        }

        $post = $q->fetch();
        return $post;
    }

    public function createThread($userId, $boardId, $subject, $message, $username, $ip, $countryCode) {
        $q = $this->db->prepare("INSERT INTO posts
            (user_id, board, ip, country_code, username, subject, message, bump_time)
            VALUES
            (:user_id, :board, INET6_ATON(:ip), :country_code, :username, :subject, :message, NOW())
        ");

        $q->bindValue('user_id', (int)$userId);
        $q->bindValue('board', (int)$boardId);
        $q->bindValue('ip', strtoupper($ip));
        $q->bindValue('country_code', $countryCode);
        $q->bindValue('username', $username);
        $q->bindValue('subject', $subject);
        $q->bindValue('message', $message);

        $q->execute();

        return $this->db->lastInsertId();
    }
}
