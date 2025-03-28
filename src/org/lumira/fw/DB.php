<?php

namespace org\lumira\fw;

use PDO;
use PDOException;
use PDOStatement;

class DB {
    private static PDO | null $conn = null;

    private static function unknown_driver()
    {
        $driver = cfg::db()->driver;
        error_log("Unknown database driver \"$driver\"");
        die();
    }

    private static function get_connection(): \PDO {
        // global $cfg;
        $db = cfg::db();
        try {
            switch ($db->driver) {
            case 'sqlite':
                return new PDO('sqlite:' . $db->path);
            case 'mysql':
                return new PDO(
                    'mysql:host='.$db->host.';dbname='.$db->dbname,
                    $db->username,
                    $db->password
                );
            default:
                self::unknown_driver();
            }
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    static function use(): PDO
    {
        if (self::$conn) return self::$conn;
        $conn  = self::get_connection();
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return self::$conn = $conn;
    }

    static function now()
    {
        switch (cfg::db()->driver) {
        case 'sqlite':
            return 'datetime(\'now\')';
        case 'mysql':
            return 'NOW()';
        default:
            self::unknown_driver();
        }
    }

    private static function tr(string $q)
    {
        $result = strtr($q, [
            '@now' => DB::now(),
        ]);
        return $result;
    }

    static function prepare(string $query, array $options = []): PDOStatement | false
    {
        return self::use()->prepare(self::tr($query), $options);
    }

    static function beginTransaction()
    {
        return self::use()->beginTransaction();
    }

    static function commit()
    {
        return self::use()->commit();
    }

    static function query(string $query, int|null $fetchMode = null, mixed ...$fetch_mode_args): PDOStatement | false
    {
        return self::use()->query(self::tr($query), $fetchMode, ...$fetch_mode_args);
    }

    static function update(string $query, array &$updated, array $col) {
        $fields = [];
        foreach ($col as $k => $v) {
            if (gettype($k) == 'integer') {
                $k = $v;
            }
            if (key_exists($v, $updated)) {
                if ($updated[$v] == null) {
                    unset($updated[$v]);
                } else {
                    array_push($fields, "$k=:$v");
                }
            }
        }
        if (count($fields) == 0) {
            return false;
        }
        return self::prepare(str_replace('%fields%', join(',', $fields), $query));
    }

    static function escape($col)
    {
        if (gettype($col) == 'array') {
            return '`'.$col[0].'`.`'.$col[1].'`';
        } else if ($col[0] == ':') {
            return $col;
        } else {
            return '`'.$col.'`';
        }
    }
}
