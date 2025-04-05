<?php

namespace App\Controllers;

use App\Util;
use org\lumira\Errors\HttpError;
use org\lumira\fw\cfg;
use org\lumira\fw\DB;
use org\lumira\fw\Request;
use org\lumira\fw\v;
use PDO;

class BackupController {
    private function get_last_row_id(&$result, $table)
    {
        $st = DB::prepare("SELECT `id` FROM `$table` ORDER BY id DESC LIMIT 1");
        $st->execute();
        $row = $st->fetch(PDO::FETCH_NUM);
        if ($row) {
            $result[$table] = intval($row[0]);
        } else {
            $result[$table] = 0;
        }
    }

    function getLastId()
    {
        $result = [];
        $this->get_last_row_id($result, 'users');
        $this->get_last_row_id($result, 'shows');
        $this->get_last_row_id($result, 'groups');
        $this->get_last_row_id($result, 'frames');
        $this->get_last_row_id($result, 'posts');
        $this->get_last_row_id($result, 'reacts');
        $this->get_last_row_id($result, 'subtitles');
        $this->get_last_row_id($result, 'best_albums');
        $this->get_last_row_id($result, 'best_posts');
        return $result;
    }

    function upload(string &$result, array &$v, array &$target_last_row, array &$local_last_row, string $table, array $cols)
    {
        $limit = cfg::backup()->limit;
        if ($target_last_row[$table] == $local_last_row[$table]) {
            return 0;
        }
        $col_joined = join(',', array_map(function ($val) {
            return DB::escape($val);
        }, $cols));
        $start = $target_last_row[$table];
        $s = DB::prepare('SELECT ' . $col_joined . ' FROM ' . DB::escape($table) . ' WHERE id > :start LIMIT :limit');
        $s->bindValue('start', $start, PDO::PARAM_INT);
        $s->bindValue('limit', $limit, PDO::PARAM_INT);
        $s->execute();
        $rows = $s->fetchAll(PDO::FETCH_NUM);
        $count = count($rows);
        $resp = json_decode(Util::fetch($v['target'] . 'backup/receive', [], gzencode(json_encode([
            'table' => $table,
            'cols' => $cols,
            'data' => $rows,
        ]), 9), null, headers:[
            'Content-Type' => 'text/plain',
            'X-Key' => $v['key'],
        ]), true);

        if (!$resp) {
            $result = "$result$table: $start $count: Failed to send the data\n";
            throw new HttpError(406, $result);
        }
        if (key_exists('ok', $resp) && $resp['ok']) {
            $target_last_row[$table] = intval($rows[$count - 1][0]);
            $result = "$result$table insert $start $count\n";
            return $count;
        } else {
            $result = "$result$table: $start $count: $resp[error]\n";
            return 0;
        }
    }

    function uploads(string &$result, array &$v, array &$target_last_row, array &$local_last_row, string $table, array $cols)
    {
        $limit = cfg::backup()->limit;
        do {
            $count = $this->upload($result, $v, $target_last_row, $local_last_row, $table, $cols);
        } while ($count >= $limit);
    }

    function send(Request $req)
    {
        $v = $req->validate([
            'target' => v::required()->string()->min('4'),
            'key' => v::required()->string(),
        ]);
        if (!str_ends_with($v['target'], '/')) {
            $v['target'] = $v['target'] . '/';
        }

        $local_last_row = [];
        $this->get_last_row_id($local_last_row, 'users');
        $this->get_last_row_id($local_last_row, 'shows');
        $this->get_last_row_id($local_last_row, 'groups');
        $this->get_last_row_id($local_last_row, 'frames');
        $this->get_last_row_id($local_last_row, 'posts');
        $this->get_last_row_id($local_last_row, 'reacts');
        $this->get_last_row_id($local_last_row, 'subtitles');
        $this->get_last_row_id($local_last_row, 'best_albums');
        $this->get_last_row_id($local_last_row, 'best_posts');

        $target_last_row = json_decode(Util::fetch($v['target'] . 'backup/last_id', headers:[
            'X-Key' => $v['key'],
        ]), true);
        if (!$target_last_row) {
            throw new HttpError(406, 'Failed to get response from server.');
        }
        if (key_exists('error', $target_last_row)) {
            throw new HttpError(406, $target_last_row['error']);
        }

        $result = '';
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'users', ['id', 'tag', 'app_token', 'telegram_token', 'page_id', 'fb_token', 'page_name', 'updated_at']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'shows', ['id', 'name', 'alias']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'groups', ['id', 'show_id', 'alias', 'name']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'frames', ['id', 'group_id', 'frame_index', 'file_id']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'posts', ['id', 'user_id', 'frame_id', 'fb_post']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'reacts', ['id', 'post_id', 'react', 'count']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'subtitles', ['id', 'group_id', 'start', 'end', 'text']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'best_albums', ['id', 'user_id', 'fb_album', 'group_id', 'token', 'alias']);
        $this->uploads($result, $v, $target_last_row, $local_last_row, 'best_posts', ['id', 'album_id', 'post_id', 'reacts', 'fb_post']);
        return $result;
    }

    function receive(Request $req)
    {
        $data = json_decode(gzdecode(file_get_contents('php://input')), true);

        $table = DB::escape($data['table']);
        $cols = join(',', array_map(function ($col) {
            return DB::escape($col);
        }, $data['cols']));

        $st = DB::prepare("REPLACE INTO $table ($cols) VALUES(" . join(',', array_map(function ($col) {
            return '?';
        }, $data['cols'])) . ')');

        DB::beginTransaction();
        foreach ($data['data'] as $el) {
            $st->execute($el);
        }
        DB::commit();

        return [ 'ok' => true ];
    }
}
