<?php

namespace YBoard\Model;

use Library\DbConnection;
use YBoard;

class Boards extends YBoard\Model
{
    protected $boards = false;

    public function __construct(DbConnection $dbConnection) {
        parent::__construct($dbConnection);

        $this->boards = $this->loadBoardList();
    }

    protected function loadBoardList() {
        if ($this->boards !== false) {
            return true;
        }

        $q = $this->db->query('SELECT id, name, description, url, alt_url, is_hidden FROM boards ORDER BY name ASC');
        if ($q === false) {
            return false;
        }

        return $q->fetchAll(DbConnection::FETCH_ASSOC);
    }

    public function getBoards() : array
    {
        return $this->boards;
    }

    public function isAltUrl(string $url) : bool
    {
        $exists = array_search($url, array_column($this->boards, 'alt_url'));
        return $exists !== false;
    }

    public function getUrlByAltUrl(string $altUrl) : string
    {
        return $this->boards[array_search($altUrl, array_column($this->boards, 'alt_url'))]['url'];
    }

    public function boardExists(string $url) : bool
    {
        $exists = array_search($url, array_column($this->boards, 'url'));
        return $exists !== false;
    }

    public function getBoardByUrl(string $url) : array
    {
        return $this->boards[array_search($url, array_column($this->boards, 'url'))];
    }
}
