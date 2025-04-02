<?php

namespace App\Controllers;

use App\Util;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;

class ReactController {
    function fetchReacts(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'start' => v::required()->number(),
            'count' => v::optional()->number(),
            'user_id' => v::required(),
        ]);
        if ($v['count'] == null) {
            $v['count'] = 50;
        }
        $v['end'] = $v['start'] + min($v['count'], 50) - 1;
        unset($v['count']);

        $s = DB::prepare(
            'SELECT
                p.id, f.frame_index, p.fb_post
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `frames` AS f ON f.group_id = g.id
            LEFT JOIN `posts` AS p ON p.frame_id = f.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                f.frame_index BETWEEN :start AND :end AND
                p.user_id = :user_id
            ORDER BY f.frame_index'
        );
        $s->execute($v);
        $data = [];
        $batch = [];
        while (($row = $s->fetch())) {
            array_push($data, $row);
            array_push($batch, [
                'method' => 'GET',
                'relative_url' => $row['fb_post'].'/reactions?summary=total_count',
            ]);
        }
        $results = json_decode(Util::fetch('https://graph.facebook.com/v19.0', [], [
            'batch' => json_encode($batch),
            'include_headers' => 'false',
            'access_token' => $req['fb_token'],
        ]), true);

        $s = DB::prepare(
            'REPLACE INTO `reacts` (`post_id`, `react`, `count`) VALUES (:post_id, \'t\', :count)'
        );

        $output = [];
        foreach ($results as $i => $result) {
            $body = json_decode($result['body'], true);
            if ($result['code'] == 200) {
                $total_count = $body['summary']['total_count'];
                if ($total_count > 0) {
                    $s->execute([
                        'post_id' => $data[$i]['id'],
                        'count' => $total_count,
                    ]);
                }
                $output[strval($data[$i]['frame_index'])] = $total_count;
            } else {
                $output[strval($data[$i]['frame_index'])] = $body;
            }
        }
        return $output;
    }

    function getGroupReactStat(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'user_id' => v::required(),
        ]);
        $s = DB::prepare(
            'SELECT
                react,
                COUNT(id) AS amount
            FROM (SELECT
                    f.id, COALESCE(SUM(r.count), 0) as react
                FROM `shows` AS s
                JOIN `groups` AS g ON g.show_id = s.id
                JOIN `frames` AS f ON f.group_id = g.id
                LEFT JOIN `posts` AS p ON p.frame_id = f.id
                LEFT JOIN `reacts` AS r ON r.post_id = p.id
                WHERE
                    s.alias = :show AND
                    g.alias = :group AND
                    p.user_id = :user_id
                GROUP BY p.id)
            GROUP BY react
            ORDER BY react DESC'
        );

        $s->execute($v);

        $result = [];
        $cum = 0;
        while ($row = $s->fetch()) {
            $cum += $row['amount'];
            $row['cum'] = $cum;
            array_push($result, $row);
        }
        return $result;
    }

    function getShowReactStat(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'user_id' => v::required(),
        ]);
        $s = DB::prepare(
            'SELECT
                g.alias AS name,
                COALESCE(SUM(r.count), 0) as react
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            LEFT JOIN `frames` AS f ON f.group_id = g.id
            LEFT JOIN `posts` AS p ON p.frame_id = f.id
            LEFT JOIN `reacts` AS r ON r.post_id = p.id
            WHERE
                s.alias = :show AND
                p.user_id = :user_id
            GROUP BY g.id'
        );

        $s->execute($v);

        $result = [];
        while ($row = $s->fetch()) {
            array_push($result, $row);
        }
        return $result;
    }
}
