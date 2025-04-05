<?php

namespace App;

use DateInterval;
use org\lumira\fw\DB;

class Util {
    static function fetch($url, array $query = [], array | string | null $post_data = null, string | null $method = null, array | null $headers = null)
    {
        $curl = curl_init();
        if (!empty($query)) {
            $url = $url . '?' . http_build_query($query);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($post_data !== null) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        if (!empty($method)) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($headers !== null) {
            if (key_exists(0, $headers)) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            } else {
                $h = [];
                foreach ($headers as $k => $v) {
                    array_push($h, "$k: $v");
                }
                curl_setopt($curl, CURLOPT_HTTPHEADER, $h);
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    static function telegram_bot($token, $end_point, $prefix = '')
    {
        return "https://api.telegram.org$prefix/bot$token/$end_point";
    }

    static function get_telegram_file($token, $file_id)
    {
        $url = Util::telegram_bot($token, 'getFile');
        $result = json_decode(Util::fetch($url, ['file_id' => $file_id]), true);
        if (gettype($result) === 'array' && key_exists('ok', $result) && $result['ok']) {
            return $result['result']['file_path'];
        }
        return false;
    }

    static function update_telegram_file($token, $file_id, $frame_id)
    {
        $path = Util::get_telegram_file($token, $file_id);
        if (!$path) return false;

        $st = DB::prepare(
            'UPDATE `frames`
            SET path = :path, last_request = @now, updated_at = @now
            WHERE id = :frame_id');
        $st->execute([
            'path' => $path,
            'frame_id' => $frame_id,
        ]);
        return $path;
    }

    static function get_telegram_url($token, $file_id, $frame_id, $path, $last_request)
    {
        $expire = date_create($last_request ?? 'now')->add(new DateInterval('PT1H'));
        if (empty($path) || date_create('now') > $expire) {
            $path = Util::update_telegram_file($token, $file_id, $frame_id);
        }
        if (!$path) {
            return false;
        }
        return Util::telegram_bot($token, $path, '/file');
    }

    static function generate_token(int $len)
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $chars_count = strlen($chars) - 1;
        $result = [];
        for ($i = 0; $i < $len; $i++) {
            array_push($result, $chars[rand(0, $chars_count)]);
        }
        return join('', $result);
    }
}
