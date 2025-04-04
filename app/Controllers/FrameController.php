<?php

namespace App\Controllers;

use App\Util;
use org\lumira\Errors\Conflict;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDOException;

class FrameController
{
    function getAll(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare(
            'SELECT
                f.frame_index as frame, f.file_id as file, f.path, f.last_request, f.updated_at
            FROM `shows` AS s
            LEFT JOIN `groups` AS g ON g.show_id=s.id
            LEFT JOIN `frames` AS f ON f.group_id=g.id
            WHERE s.alias=:show AND g.alias=:group
            ORDER BY f.frame_index'
        );
        $s->execute($v);
        if ($s->rowCount() === 0) {
            throw new NotFound();
        }
        $result = [];
        $row = $s->fetch();
        if ($row['frame'] !== null) {
            array_push($result, $row);
        }
        while (($row = $s->fetch())) {
            array_push($result, $row);
        }
        return $result;
    }

    function insert(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'file' => v::required()->string()->range(1, 90),
        ]);
        $s = DB::prepare(
            'INSERT INTO
                `frames`(`group_id`, `frame_index`, `file_id`)
            SELECT g.id, :frame, :file
            FROM `shows` AS s
            LEFT JOIN `groups` AS g ON g.show_id = s.id
            WHERE s.alias = :show AND g.alias = :group'
        );
        try {
            $s->execute($v);
            if ($s->rowCount() === 0) {
                throw new NotFound();
            }
            return [ 'ok' => true ];
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Conflict('Frame already exists');
            }
            throw new Conflict($e->getMessage(). 'Query: '.$s->queryString);
        }
    }

    function get(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
        ]);
        $s= DB::prepare(
            'SELECT
                f.frame_index as frame, f.file_id as file, f.path, f.last_request, f.updated_at
            FROM `frames` AS f
            LEFT JOIN `groups` AS g ON f.group_id = g.id
            LEFT JOIN `shows` AS s ON g.show_id = s.id
            WHERE s.alias = :show AND g.alias = :group AND f.frame_index = :frame'
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
            'frame' => v::required()->number(),
            'file' => v::optional()->string()->range(1, 90),
            'path' => v::optional()->string()->range(1, 30),
            'last_request' => v::optional()->string(),
        ]);
        $s = DB::update(
            'UPDATE `frames` AS f
            SET %fields%, updated_at = @now
            WHERE frame_index = :frame
            AND EXISTS (
                SELECT g.id
                FROM `shows` AS s
                LEFT JOIN `groups` AS g ON g.show_id = s.id
                WHERE s.alias = :show
                AND g.alias = :group
                AND g.id = f.group_id
            )
            LIMIT 1',
            $v, [
                'file_id' => 'file',
                'path' => 'path',
                'last_request' => 'last_request',
            ]
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
            'frame' => v::required()->number(),
        ]);
        $s = DB::query(
            'DELETE FROM `frames` AS f
            WHERE frame_index = :frame
            AND EXISTS (
                SELECT g.id
                FROM `shows` AS s
                LEFT JOIN `groups` AS g ON g.show_id = s.id
                WHERE s.alias = :show
                AND g.alias = :group
                AND g.id = f.group_id
            )
            LIMIT 1'
        );
        $s->execute($v);
        if ($s->rowCount() == 1) {
            return [ 'ok' => true ];
        } else {
            throw new NotFound();
        }
    }

    function getTelegramImage(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
        ]);
        $s = DB::prepare(
            'SELECT
                f.id, f.file_id, path, last_request
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `frames` AS f ON f.group_id = g.id
            WHERE s.alias = :show AND g.alias = :group AND f.frame_index = :frame'
        );
        $s->execute($v);
        if (!($data = $s->fetch())) {
            throw new NotFound();
        }
        $image_url = Util::get_telegram_url($req['telegram_token'], $data['file_id'], $data['id'], $data['path'], $data['last_request']);
        return [ 'url' => $image_url ];
    }

    function getFirstEmptyIndex(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare(
            'SELECT
                f.frame_index as frame
            FROM `shows` AS s
            LEFT JOIN `groups` AS g ON g.show_id=s.id
            LEFT JOIN `frames` AS f ON f.group_id=g.id
            WHERE s.alias=:show AND g.alias=:group
            ORDER BY f.frame_index'
        );
        $s->execute($v);
        if ($s->rowCount() === 0) {
            throw new NotFound();
        }
        $index = 0;
        while (($row = $s->fetch())) {
            if ($row['frame'] == strval($index + 1)) {
                $index++;
            } else {
                break;
            }
        }
        return ['index' => $index];
    }
}
