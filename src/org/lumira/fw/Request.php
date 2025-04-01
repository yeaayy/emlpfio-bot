<?php

namespace org\lumira\fw;

use org\lumira\Validation\ValidationError;
use org\lumira\Validation\ValidationRule;

class Request implements \ArrayAccess
{
    private $fields = [];

    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * @param array<ValidationRule> $rules
     */
    function validate(array $rules)
    {
        $validated = [];
        $errors = [];
        $has_error = false;
        foreach ($rules as $key => $rule) {
            $value = $this->offsetExists($key) ? $this[$key] : null;
            $result = $rule->validate($value);
            if ($result['error']) {
                $has_error = true;
                $errors[$key] = strtr($result['msg'], [
                    '%name%' => $key,
                ]);
            } else {
                if (key_exists('value', $result)) {
                    $validated[$key] = $result['value'];
                } else {
                    $validated[$key] = $value;
                }
            }
        }
        if ($has_error) {
            throw new ValidationError($errors);
        }
        return $validated;
    }

    function all()
    {
        return $this->fields;
    }

    function __get($name)
    {
        return $this->fields[$name];
    }

    function __set($name, $value)
    {
        $this->fields[$name] = $value;
    }

    function __isset($name)
    {
        return isset($this->fields[$name]);
    }

    function __unset($name)
    {
        unset($this->fields[$name]);
    }

    function offsetExists($offset): bool
    {
        return key_exists($offset, $this->fields);
    }

    function offsetGet($offset): mixed
    {
        return $this->fields[$offset];
    }

    function offsetSet($offset, $value): void
    {
        $this->fields[$offset] = $value;
    }

    function offsetUnset($offset): void
    {
        unset($this->fields[$offset]);
    }

    static function capture()
    {
        $result = [];
        foreach ($_GET as $k => $v) {
            $result[$k] = trim($v);
        }
        foreach ($_POST as $k => $v) {
            $result[$k] = trim($v);
        }
        foreach ($_FILES as $k => $v) {
            $result[$k] = $v;
        }
        if (key_exists('CONTENT_TYPE', $_SERVER) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $json = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Malformed json input',
                ]);
                exit;
            }
            foreach ($json as $k => $v) {
                $result[$k] = $v;
            }
        }
        return new Request($result);
    }
}
