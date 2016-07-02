<?php
namespace YBoard\Model;

use YBoard;
use YBoard\Library\Database;

class Boards extends YBoard\Model
{
    protected $boards = false;

    public function __construct(Database $db)
    {
        parent::__construct($db);

        $q = $this->db->query('SELECT id, name, description, url, alt_url, nsfw, is_hidden FROM boards ORDER BY name ASC');
        if ($q === false) {
            return false;
        }
        $this->boards = $q->fetchAll(Database::FETCH_ASSOC);
    }

    public function getAll() : array
    {
        $boards = [];
        foreach ($this->boards as $board) {
            $boards[] = (object)$board;
        }
        return $boards;
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

    public function exists(string $url) : bool
    {
        $exists = array_search($url, array_column($this->boards, 'url'));

        return $exists !== false;
    }

    public function getByUrl(string $url)
    {
        $board = $this->boards[array_search($url, array_column($this->boards, 'url'))];
        return (object)$board;
    }
}
