<?php

namespace App\Controllers;

use org\lumira\Errors\Conflict;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDO;
use PDOException;

class GroupController
{
    function getAll(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare(
            'SELECT
                g.alias, g.name, g.fps, g.gen, g.updated_at
            FROM `groups` AS g
            LEFT JOIN `shows` AS s ON g.show_id=s.id
            WHERE s.alias=:show'
        );
        $s->execute($v);
        $result = [];
        while (($row = $s->fetch())) {
            array_push($result, $row);
        }
        return $result;
    }

    function insert(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'alias' => v::required()->string()->range(1, 10),
            'name' => v::required()->string()->range(1, 40),
            'fps' => v::required()->number(),
        ]);
        $v['gen'] = 1;
        $s = DB::prepare(
            'INSERT INTO
                `groups`(`show_id`,`alias`,`name`,`fps`,`gen`)
            SELECT s.id, :alias, :name, :fps, :gen
            FROM `shows` AS s
            WHERE s.alias=:show'
        );
        try {
            $s->execute($v);
            return [ 'ok' => true ];
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Conflict('Group already exists');
            }
            throw new Conflict($e->getMessage());
        }
    }

    function get(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare(
            'SELECT
                g.alias, g.name, g.fps, g.gen, g.updated_at
            FROM `groups` AS g
            LEFT JOIN `shows` AS s ON g.show_id=s.id
            WHERE s.alias=:show AND g.alias=:group'
        );
        $s->execute($v);
        if (!($result = $s->fetch())) {
            throw new NotFound();
        }
        return $result;
    }

    function update(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'alias' => v::optional()->string()->range(1, 10),
            'name' => v::optional()->string()->range(1, 40),
            'fps' => v::optional()->number(),
            'gen' => v::optional()->number(),
        ]);
        $s = DB::update(
            'UPDATE `groups` AS g
            SET %fields%, updated_at=@now
            WHERE alias = :group
            AND EXISTS (SELECT id FROM `shows` AS s WHERE s.alias = :show AND s.id = g.show_id)',
            $v, ['alias', 'name', 'fps', 'gen']
        );
        if (!$s) return [ 'ok' => true ];
        $s->execute($v);
        if ($s->rowCount() == 0) {
            throw new NotFound();
        }
        return [ 'ok' => true ];
    }

    function delete(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s = DB::query(
            'DELETE FROM `groups` AS g
            WHERE alias = :group
            AND EXISTS (SELECT id FROM `shows` AS s WHERE s.id = g.show_id AND s.alias = :show) LIMIT 1'
        );
        $s->execute($v);
        if ($s->rowCount() == 1) {
            return [ 'ok' => true ];
        } else {
            throw new NotFound();
        }
    }

    function getFrameCount(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s = DB::prepare(
            'SELECT f.frame_index
            FROM `shows` AS s
            INNER JOIN `groups` AS g ON s.id = g.show_id
            INNER JOIN `frames` AS f ON g.id = f.group_id
            WHERE s.alias = :show AND g.alias = :group
            ORDER BY f.frame_index DESC
            LIMIT 1'
        );
        $s->execute($v);
        $row = $s->fetch(PDO::FETCH_NUM);
        if (!$row) {
            return [ 'count' => -1 ];
        } else {
            return [ 'count' => intval($row[0]) ];
        }
    }

}
