<?php

namespace App\Controllers;

use org\lumira\Errors\HttpError;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use App\Util;
use PDO;

class PostController
{
    function getPost(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'user_id' => v::required(),
        ]);
        $s = DB::prepare(
            'SELECT fb_post
            FROM `posts` AS p
            LEFT JOIN `frames` AS f ON p.frame_id = f.id
            LEFT JOIN `groups` AS g ON f.group_id = g.id
            LEFT JOIN `shows` AS s ON g.show_id = s.id
            WHERE s.alias = :show
            AND g.alias = :group
            AND f.frame_index = :frame
            AND p.user_id = :user_id'
        );
        $s->execute($v);
        if ($result = $s->fetch()) {
            return $result;
        }
        throw new NotFound();
    }

    function createPost(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'user_id' => v::required(),
        ]);

        $frame_count = (new GroupController)->getFrameCount($req)['count'];

        $s = DB::prepare(
            'SELECT
                g.id as gid, f.id, f.frame_index, f.file_id, f.path, f.last_request, p.fb_post, g.name
            FROM `users` AS u
            JOIN `shows` AS s
            INNER JOIN `groups` AS g ON s.id = g.show_id
            INNER JOIN `frames` AS f ON g.id = f.group_id
            LEFT JOIN `posts` AS p ON f.id = p.frame_id AND p.user_id = u.id
            WHERE s.alias = :show
            AND g.alias = :group
            AND f.frame_index = :frame
            AND u.id = :user_id
            LIMIT 1'
        );
        $s->execute($v);
        $data = $s->fetch();
        if (!empty($data['fb_post'])) {
            // Already posted
            return [ 'ok' => true ];
        }

        // Get subtitle
        $s = DB::prepare(
            'SELECT t.text
            FROM `groups` AS g
            JOIN `subtitles` AS t on g.id = t.group_id
            WHERE t.group_id = :group_id
            AND ( :frame_index * 1.0 / g.fps ) BETWEEN t.start AND t.end
            LIMIT 1'
        );
        $s->execute([
            'frame_index' => $data['frame_index'],
            'group_id' => $data['gid'],
        ]);
        $row = $s->fetch(PDO::FETCH_NUM);
        if (!$row) {
            $subtitle = '';
        } else {
            $subtitle = "\n\n" . $row[0];
        }

        $image_url = Util::get_telegram_url($req['telegram_token'], $data['file_id'], $data['id'], $data['path'], $data['last_request']);
        if (empty($image_url)) {
            return [
                'error' => 'Can\'t retreive image file with id '.$data['file_id']
            ];
        }

        $result = json_decode(Util::fetch("https://graph.facebook.com/v19.0/$req[page_id]/photos", [
            'access_token' => $req['fb_token'],
            'caption' => "$data[name] - Frame $data[frame_index] out of $frame_count" . $subtitle,
            'url' => $image_url,
        ], []), true);

        if (key_exists('error', $result)) {
            http_response_code(502);
            return [
                'error' => $result['error']['message'],
            ];
        }
        $st = DB::prepare(
            'INSERT INTO
                `posts`(`user_id`, `frame_id`, `fb_post`)
            VALUES (:user_id, :frame_id, :post_id)'
        );
        $st->execute([
            'user_id' => $req['user_id'],
            'frame_id' => $data['id'],
            'post_id' => $result['post_id'],
        ]);
        return [ 'ok' => true ];
    }

    function deletePost(Request $req)
    {
        $fb_post = $this->getPost($req)['fb_post'];
        $result = json_decode(Util::fetch("https://graph.facebook.com/v19.0/$fb_post", [
            'access_token' => $req['fb_token'],
        ], method: 'DELETE'), true);
        if (key_exists('error', $result)) {
            if (key_exists('message', $result['error'])) {
                if (stripos($result['error']['message'], "object with id '$fb_post' does not exist") === false) {
                    throw new HttpError(502, $result['error']['message']);
                }
            } else {
                http_response_code(502);
                return $result;
            }
        } else if (key_exists('success', $result)) {
        } else {
            return [ 'error' => 'Connection failed.' ];
        }
        $s = DB::prepare('DELETE FROM `posts` WHERE fb_post = :post_id');
        $s->execute([ 'post_id' => $fb_post ]);
        if ($s->rowCount() == 1) {
            return [ 'ok' => true ];
        } else {
            throw new HttpError(502, 'Failed to delete the entry in the database.');
        }
    }

    function getSubtitile(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
        ]);
        $s = DB::prepare(
            'SELECT t.text
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `subtitles` AS t on g.id = t.group_id
            WHERE s.alias = :show
            AND g.alias = :group
            AND ( :frame * 1.0 / g.fps ) BETWEEN t.start AND t.end
            LIMIT 1'
        );
        $s->execute($v);
        $row = $s->fetch(PDO::FETCH_NUM);
        if (!$row) {
            return [ 'subtitle' => '' ];
        } else {
            return [ 'subtitle' => $row[0] ];
        }
    }
}
