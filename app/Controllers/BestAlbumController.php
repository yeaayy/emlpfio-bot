<?php

namespace App\Controllers;

use App\Util;
use org\lumira\Errors\HttpError;
use org\lumira\Errors\NotFound;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDO;

class BestAlbumController {

    function getAlbum(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'user_id' => v::required(),
        ]);
        $s = DB::prepare(
            'SELECT
                b.id, b.fb_album, b.token, b.alias
            FROM `best_albums` AS b
            LEFT JOIN `groups` AS g ON b.group_id = g.id
            LEFT JOIN `shows` AS s ON g.show_id = s.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                b.user_id = :user_id'
        );
        $s->execute($v);
        if (!($result = $s->fetch())) {
            throw new NotFound();
        }
        $output = [
            'album_id' => $result['fb_album'],
            'token' => $result['token'],
            'posts' => [],
        ];
        $s = DB::prepare(
            'SELECT
                bp.id,
                f.frame_index as frame,
                :page_id || \'_\' || bp.fb_post as post,
                p.fb_post as orig_post,
                bp.reacts
            FROM `best_albums` AS ba
            JOIN `best_posts` AS bp ON bp.album_id = ba.id
            LEFT JOIN `posts` AS p ON bp.post_id = p.id
            LEFT JOIN `frames` AS f ON p.frame_id = f.id
            WHERE ba.id = :album_id'
        );
        $s->execute([
            'album_id' => $result['id'],
            'page_id' => $req['page_id']
        ]);
        while (($row = $s->fetch())) {
            array_push($output['posts'], $row);
        }
        return $output;
    }

    function createAlbum(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'album_id' => v::required()->string()->range(1, 20),
            'user_id' => v::required(),
        ]);

        // Check if album already exists
        $s = DB::prepare(
            'SELECT
                a.token
            FROM `shows` AS s
            LEFT JOIN `groups` AS g ON g.show_id = s.id
            LEFT JOIN `best_albums` AS a ON a.group_id = g.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                a.user_id = :user_id'
        );
        $s->execute([
            'show' => $v['show'],
            'group' => $v['group'],
            'user_id' => $v['user_id'],
        ]);
        if ($row = $s->fetch()) {
            return $row;
        }

        // Generate token
        $s = DB::prepare('SELECT id FROM `best_albums` WHERE token = :token');
        do {
            $v['token'] = Util::generate_token(8);
            $s->execute([ 'token' => $v['token'] ]);
        } while ($s->fetch());

        $s = DB::prepare(
            'INSERT INTO
                `best_albums` (`user_id`, `fb_album`, `group_id`, `token`, `alias`)
            SELECT
                :user_id, :album_id, g.id, :token, :show || \'-\' || :group
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            WHERE
                s.alias = :show AND
                g.alias = :group
            LIMIT 1'
        );
        $s->execute($v);
        if ($s->rowCount() == 0) {
            throw new NotFound();
        }
        return [ 'token' => $v['token'] ];
    }

    function populateAlbum(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'min' => v::required()->number(),
            'user_id' => v::required(),
        ]);

        $s = DB::prepare(
            'SELECT
                ba.id, COUNT(bp.id) as count
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `best_albums` AS ba ON ba.group_id = g.id
            LEFT JOIN `best_posts` AS bp  ON bp.album_id = ba.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                ba.user_id = :user_id
            GROUP BY ba.id'
        );
        $s->execute([
            'show' => $v['show'],
            'group' => $v['group'],
            'user_id' => $v['user_id'],
        ]);
        if (!($row = $s->fetch())) {
            throw new NotFound();
        } else if ($row['count'] != 0) {
            http_response_code(409);
            return [
                'count' => $row['count'],
                'error' => 'Album already been populated.',
            ];
        }
        $album_id = $row['id'];

        $s = DB::prepare(
            'INSERT INTO
                `best_posts`(`album_id`, `post_id`, `reacts`)
            SELECT
                ba.id,
                p.id,
                SUM(r.count) AS `reacts`
            FROM `best_albums` AS ba
            JOIN `frames` AS f ON f.group_id = ba.group_id
            JOIN `posts` AS p ON p.frame_id = f.id
            LEFT JOIN `reacts` AS r ON r.post_id = p.id
            WHERE
                ba.id = :album_id
            GROUP BY p.id
            HAVING SUM(r.count) >= :min
            ORDER BY `reacts` DESC, f.frame_index'
        );
        $s->bindValue('album_id', $album_id);
        $s->bindValue('min', $v['min'], PDO::PARAM_INT);
        $s->execute();
        return [
            'count' => $s->rowCount(),
        ];
    }

    function postNext(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'user_id' => v::required(),
        ]);

        $s = DB::prepare(
            'SELECT
                f.id AS frame_id,
                g.name AS `group`,
                ba.fb_album,
                f.frame_index,
                f.file_id,
                f.path,
                f.last_request,
                p.fb_post AS orig_post,
                bp.id as bp_id,
                bp.reacts
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `best_albums` AS ba ON ba.group_id = g.id
            JOIN `best_posts` AS bp ON bp.album_id = ba.id
            JOIN `posts` AS p ON bp.post_id = p.id
            JOIN `frames` AS f ON p.frame_id = f.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                ba.user_id = :user_id AND
                bp.fb_post IS NULL
            LIMIT 1'
        );
        $s->execute($v);
        $row = $s->fetch();
        if (!$row) {
            throw new NotFound();
        }

        $image_url = Util::get_telegram_url($req['telegram_token'], $row['file_id'], $row['frame_id'], $row['path'], $row['last_request']);

        $caption =
            "Best of $row[group]\n" .
            "Frame $row[frame_index] received $row[reacts] reacts.\n\n" .
            "View original post on https://www.facebook.com/$row[orig_post]";

        $result = json_decode(Util::fetch("https://graph.facebook.com/v19.0/$row[fb_album]/photos", [
            'access_token' => $req['fb_token'],
            'caption' => $caption,
            'url' => $image_url,
        ], []), true);

        if (key_exists('error', $result)) {
            return [ 'error' => $result['error']['message'] ];
        } else {
            $s = DB::prepare('UPDATE `best_posts` SET fb_post = :fb_post WhERE id = :post_id');
            $s->execute([
                'post_id' => $row['bp_id'],
                'fb_post' => $result['id'],
            ]);
            return [
                'frame' => $row['frame_index'],
                'fb_post' => $result['id'],
            ];
        }
    }

    function getFrame(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'user_id' => v::required(),
            'page_id' => v::required(),
        ]);

        $s = DB::prepare(
            'SELECT
                bp.id,
                f.frame_index as frame,
                :page_id || \'_\' || bp.fb_post as post,
                p.fb_post as orig_post,
                bp.reacts
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `best_albums` AS ba ON ba.group_id = g.id
            JOIN `best_posts` AS bp ON bp.album_id = ba.id
            JOIN `posts` AS p ON bp.post_id = p.id
            JOIN `frames` AS f ON p.frame_id = f.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                ba.user_id = :user_id
            LIMIT :frame, 1'
        );
        $s->execute($v);

        if ($row = $s->fetch()) {
            return $row;
        } else {
            throw new NotFound();
        }
    }

    function fixCaption(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'user_id' => v::required(),
            'page_id' => v::required(),
        ]);

        $s = DB::prepare(
            'SELECT
                g.name as `group`
                f.frame_index,
                :page_id || \'_\' || bp.fb_post as post,
                p.fb_post as orig_post,
                bp.reacts
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `best_albums` AS ba ON ba.group_id = g.id
            JOIN `best_posts` AS bp ON bp.album_id = ba.id
            JOIN `posts` AS p ON bp.post_id = p.id
            JOIN `frames` AS f ON p.frame_id = f.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                ba.user_id = :user_id AND
                bp.fb_post IS NOT NULL
            LIMIT :frame, 1'
        );
        $row = $s->execute($v);
        if (!$row) {
            throw new NotFound();
        }

        $caption =
            "Best of $row[group]\n" .
            "Frame $row[frame_index] received $row[reacts] reacts.\n\n" .
            "View original post on https://www.facebook.com/$row[orig_post]";

        $result = json_decode(Util::fetch("https://graph.facebook.com/v19.0/$row[post]", [
            'access_token' => $req['fb_token'],
        ], [
            'caption' => $caption,
        ]), true);
        return $result;
    }

    function unpostFrame(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'frame' => v::required()->number(),
            'user_id' => v::required(),
            'page_id' => v::required(),
        ]);

        $s = DB::prepare(
            'SELECT
                bp.id,
                :page_id || \'_\' || bp.fb_post as post
            FROM `shows` AS s
            JOIN `groups` AS g ON g.show_id = s.id
            JOIN `best_albums` AS ba ON ba.group_id = g.id
            JOIN `best_posts` AS bp ON bp.album_id = ba.id
            JOIN `posts` AS p ON bp.post_id = p.id
            JOIN `frames` AS f ON p.frame_id = f.id
            WHERE
                s.alias = :show AND
                g.alias = :group AND
                ba.user_id = :user_id
            LIMIT :frame, 1'
        );
        $s->execute($v);

        if (!($row = $s->fetch())) {
            throw new NotFound();
        }
        $fb_post = $row['post'];
        if ($fb_post == null) {
            return [ 'ok' => false ];
        }

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
        $s = DB::prepare('UPDATE `best_posts` SET fb_post = NULL WHERE id = :id');
        $s->execute([ 'id' => $row['id'] ]);
        if ($s->rowCount() == 1) {
            return [ 'ok' => true ];
        } else {
            throw new HttpError(502, 'Failed to delete the entry in the database.');
        }
    }

    function clearAlbum(Request $req)
    {
        $v = $req->validate([
            'show' => v::required()->string()->range(1, 10),
            'group' => v::required()->string()->range(1, 10),
            'user_id' => v::required(),
        ]);

        $s = DB::prepare(
            'DELETE FROM `best_posts` AS bp
            WHERE
                fb_post IS NULL AND
            EXISTS(
                SELECT ba.id
                FROM `best_albums` AS ba
                LEFT JOIN `groups` AS g ON ba.group_id = g.id
                LEFT JOIN `shows` AS s ON g.show_id = s.id
                WHERE
                    bp.album_id = ba.id AND
                    s.alias = :show AND
                    g.alias = :group AND
                    ba.user_id = :user_id
            )'
        );
        $s->execute([
            'show' => $v['show'],
            'group' => $v['group'],
            'user_id' => $v['user_id'],
        ]);

        return [ 'count' => $s->rowCount() ];
    }
}
