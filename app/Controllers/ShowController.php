<?php

namespace App\Controllers;

use org\lumira\Errors\Conflict;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDOException;

class ShowController
{
    function getAll(Request $req)
    {
        $s= DB::query('SELECT alias, name, updated_at FROM `shows`');
        $result = [];
        while ($row = $s->fetch()) {
            array_push($result, $row);
        }
        return $result;
    }

    function insert(Request $req)
    {
        $v = $req->validate([
            'name' => v::required()->string()->range(1, 40),
            'alias' => v::required()->string()->range(1, 10),
        ]);
        $s = DB::prepare('INSERT INTO `shows`(`name`,`alias`) VALUES(:name, :alias)');
        try {
            $s->execute($v);
            return [ 'ok' => true ];
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Conflict('Show already exists');
            }
            throw new Conflict($e->getMessage());
        }
    }

    function get(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare('SELECT alias, name, updated_at FROM `shows` WHERE alias = :name LIMIT 1');
        $s->execute([
            'name' => $v['show'],
        ]);
        if (!($result = $s->fetch())) {
            throw new NotFound();
        }
        return $result;
    }

    function update(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'alias' => v::optional()->string()->range(1, 10),
            'name' => v::optional()->string()->range(1, 40),
        ]);
        $s = DB::update(
            'UPDATE `shows` SET %fields%, updated_at=@now WHERE alias = :show LIMIT 1',
            $v, ['alias', 'name']
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
        ]);
        $s = DB::query('DELETE FROM `shows` WHERE alias = :show LIMIT 1');
        $s->execute($v);
        if ($s->rowCount() == 1) {
            return [ 'ok' => true ];
        } else {
            return [ 'ok' => false, 'error' => 'Unknown error' ];
        }
    }
}
