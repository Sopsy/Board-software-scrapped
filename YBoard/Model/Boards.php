<?php

namespace YBoard\Model;

use YBoard;

class Boards extends YBoard\Model
{
    protected $boards = false;

    public function getBoards()
    {
        if ($this->boards !== false) {
            return $this->boards;
        }

        $q = $this->db->query('SELECT * FROM boards ORDER BY name DESC');
        if (!$q) {
            return false;
        }

        return $q->fetchAll();;
    }
}
