<?php

namespace org\lumira\fw;

function get_cfg(&$array, $key) {
    if (key_exists($key, $array)) {
        $value = $array[$key];
        if (gettype($value) === 'array') {
            return new cfg($value);
        } else {
            return $value;
        }
    }
    return null;
}

class cfg {
    private static $global_data;
    private $data;

    function __construct(array $data)
    {
        $this->data = $data;
    }

    // static function load(string $path) {
    //     self::$instance = new Con(
    //         require_once($path),
    //     );
    // }

    // static function fig() {
    //     return self::$instance;
    // }

    function __get($key)
    {
        return get_cfg($this->data, $key);
    }

    static function __callStatic($name, $arguments)
    {
        if ($name === 'load' && count($arguments) === 1) {
            self::$global_data = require_once($arguments[0]);
            return;
        }
        return get_cfg(self::$global_data, $name);
        // if (key_exists($key, self::$global_data)) {
        //     $value = self::$global_data[$key];
        //     if (gettype($value) === 'array') {
        //         return new cfg($value);
        //     } else {
        //         return $value;
        //     }
        // }
        // return null;
    }
}
