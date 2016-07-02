<?php
namespace YBoard\Model;

use YBoard;

class Posts extends YBoard\Model
{
    public function getThread($id) {
        $q = $this->db->prepare('SELECT id, locked, board FROM posts WHERE id = :id LIMIT 1');
        $q->bindValue('id', $id);

        if ($q->rowCount() == 0) {
            return false;
        }
        
        $post = $q->fetch();
        return $post;
    }
}
