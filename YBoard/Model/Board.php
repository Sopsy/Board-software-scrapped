<?php
namespace YBoard\Model;

use YFW\Library\Database;
use YBoard\Model;

class Board extends Model
{
    public $id;
    public $name;
    public $description;
    public $url;
    public $altUrl;
    public $isNsfw = false;
    public $isHidden = false;
    public $showFlags = false;
    public $inactiveHoursDelete = false;

    static $boards;

    public function __construct(Database $db, \stdClass $data = null)
    {
        parent::__construct($db);

        if ($data === null) {
            return;
        }

        foreach ($data as $key => $val) {
            switch ($key) {
                case 'id':
                    $this->id = (int)$val;
                    break;
                case 'ip':
                    $this->ip = inet_ntop($val);
                    break;
                case 'alt_url':
                    $this->altUrl = $val;
                    break;
                case 'is_nsfw':
                    $this->isNsfw = (bool)$val;
                    break;
                case 'is_hidden':
                    $this->isHidden = (bool)$val;
                    break;
                case 'show_flags':
                    $this->showFlags = (bool)$val;
                    break;
                case 'inactive_hours_delete':
                    $this->inactiveHoursDelete = $val == 0 ? false : (int)$val;
                    break;
                default:
                    $this->$key = $val;
                    break;
            }
        }
    }

    public static function getAll(Database $db, bool $order = true): array
    {
        if (!empty(static::$boards)) {
            return static::$boards;
        }

        $query = 'SELECT id, name, description, url, alt_url, is_nsfw, is_hidden, show_flags, inactive_hours_delete FROM board';
        if ($order) {
            $query .= ' FORCE INDEX (name) ORDER BY name ASC';
        }

        $q = $db->query($query);

        if ($q === false) {
            return [];
        }

        static::$boards = [];
        while ($row = $q->fetch()) {
            static::$boards[] = new self($db, $row);
        }

        return static::$boards;
    }

    public static function isAltUrl(Database $db, string $url): bool
    {
        foreach (static::getAll($db) as $board) {
            if ($board->altUrl == $url) {
                return true;
            }
        }

        return false;
    }

    public static function getUrlByAltUrl(Database $db, string $altUrl): ?string
    {
        foreach (static::getAll($db) as $board) {
            if ($board->altUrl == $altUrl) {
                return $board->url;
            }
        }

        return null;
    }

    public static function exists(Database $db, string $url): bool
    {
        foreach (static::getAll($db) as $board) {
            if ($board->url == $url) {
                return true;
            }
        }

        return false;
    }

    public static function getByUrl(Database $db, string $url): ?self
    {
        foreach (static::getAll($db) as $board) {
            if ($board->url == $url) {
                return $board;
            }
        }

        return null;
    }

    public static function getById(Database $db, int $boardId): ?self
    {
        foreach (static::getAll($db) as $board) {
            if ($board->id == $boardId) {
                return $board;
            }
        }

        return null;
    }
}
