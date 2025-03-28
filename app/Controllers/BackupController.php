<?php

namespace App\Controllers;

use org\lumira\fw\DB;
use org\lumira\fw\Request;
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

    function send(Request $req)
    {
        // 
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
