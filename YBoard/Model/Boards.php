<?php
namespace YBoard\Model;

use YBoard\Data\Board;
use YBoard\Library\Database;
use YBoard\Model;

class Boards extends Model
{
    protected $boards = false;

    public function __construct(Database $db)
    {
        parent::__construct($db);

        $q = $this->db->query('SELECT id, name, description, url, alt_url, is_nsfw, is_hidden, show_flags FROM boards ORDER BY name ASC');
        if ($q === false) {
            return false;
        }
        $this->boards = [];
        while ($row = $q->fetch()) {
            $tmp = new Board();
            $tmp->id = $row->id;
            $tmp->name = $row->name;
            $tmp->description = $row->description;
            $tmp->url = $row->url;
            $tmp->altUrl = $row->alt_url;
            $tmp->isNsfw = $row->is_nsfw == 1 ? true : false;
            $tmp->isHidden = $row->is_hidden == 1 ? true : false;
            $tmp->showFlags = $row->show_flags == 1 ? true : false;
            $this->boards[] = $tmp;
        }
    }

    public function getAll() : array
    {
        return $this->boards;
    }

    public function isAltUrl(string $url) : bool
    {
        foreach ($this->boards as $board) {
            if ($board->altUrl == $url) {
                return true;
            }
        }

        return false;
    }

    public function getUrlByAltUrl(string $altUrl)
    {
        foreach ($this->boards as $board) {
            if ($board->altUrl == $altUrl) {
                return $board->url;
            }
        }

        return false;
    }

    public function exists(string $url) : bool
    {
        foreach ($this->boards as $board) {
            if ($board->url == $url) {
                return true;
            }
        }

        return false;
    }

    public function getByUrl(string $url)
    {
        foreach ($this->boards as $board) {
            if ($board->url == $url) {
                return $board;
            }
        }

        return false;
    }

    public function getById(int $boardId)
    {
        foreach ($this->boards as $board) {
            if ($board->id == $boardId) {
                return $board;
            }
        }

        return false;
    }
}
