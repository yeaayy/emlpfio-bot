<?php

namespace App\Controllers;

use App\Subtitle\SubtitleParser;
use org\lumira\Errors\HttpError;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use org\lumira\Parser\Stream;

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

    function upload(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'file' => v::required()->file(),
        ]);

        $s = DB::prepare(
            'SELECT g.id
            FROM `shows` AS s
            INNER JOIN `groups` AS g ON s.id = g.show_id
            WHERE
                s.alias = :show AND
                g.alias = :group'
        );
        $s->execute([
            'show' => $v['show'],
            'group' => $v['group'],
        ]);

        $row = $s->fetch();
        if (!$row) {
            throw new HttpError(404, "$v[show]-$v[group] not found.");
        }

        $group_id = $row['id'];

        $inputFile = new Stream(file_get_contents($v['file']['tmp_name']));
        $parser = new SubtitleParser();
        if (!$parser->beginParse($inputFile, $subs)) {
            throw new HttpError(422, "Failed to parse the subtitle.");
        }

        $st = DB::prepare(
            'INSERT INTO `subtitles`(`group_id`, `start`, `end`, `text`) VALUES (:group_id, :start, :end, :text)'
        );
        DB::beginTransaction();
        foreach ($subs as $sub) {
            $st->execute([
                'group_id' => $group_id,
                'start' => $sub->start->toSecond(),
                'end' => $sub->end->toSecond(),
                'text' => strip_tags($sub->text),
            ]);
        }
        if (DB::commit()) {
            return [
                'ok' => true,
                'count' => count($subs),
            ];
        } else {
            DB::rollBack();
            throw new HttpError(422, "Failed to store the result");
        }
    }
}
