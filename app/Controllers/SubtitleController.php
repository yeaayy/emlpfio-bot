<?php

namespace App\Controllers;

use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDO;

class SubtitleController
{
    function getAll(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
        ]);
        $s= DB::prepare(
            'SELECT
                t.id, t.start, t.end, t.text
            FROM `subtitles` AS t
            LEFT JOIN `groups` AS g ON t.group_id=g.id
            LEFT JOIN `shows` AS s ON g.show_id=s.id
            WHERE s.alias=:show AND g.alias=:group'
        );
        $s->execute($v);
        $result = [];
        while (($row = $s->fetch())) {
            array_push($result, $row);
        }
        return $result;
    }

    function get(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'subs' => v::required()->number(),
        ]);
        $s= DB::prepare(
            'SELECT
                t.id, t.start, t.end, t.text
            FROM `subtitles` AS t
            LEFT JOIN `groups` AS g ON t.group_id=g.id
            LEFT JOIN `shows` AS s ON g.show_id=s.id
            WHERE s.alias = :show AND g.alias = :group AND t.id = :subs'
        );
        $s->execute($v);
        $result = $s->fetch();
        if (!$result) throw new NotFound();
        return $result;
    }

    function getAtTime(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'time' => v::required(),
        ]);
        $s= DB::prepare(
            'SELECT
                t.id, t.start, t.end, t.text
            FROM `shows` AS s
            LEFT JOIN `groups` AS g ON g.show_id = s.id
            LEFT JOIN `subtitles` AS t ON t.group_id = g.id
            WHERE s.alias = :show AND g.alias = :group
            AND :time BETWEEN t.start AND t.end'
        );
        $s->execute($v);
        return $s->fetch();
    }
}
