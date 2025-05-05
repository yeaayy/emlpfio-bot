<?php

namespace org\lumira\Validation;

abstract class ValidationRule {
    private string $msg;

    public function __construct(string $msg)
    {
        $this->msg = $msg;
    }

    public abstract function validate($input): mixed;

    protected function resultError($param = [], ?string $msg = null) {
        return [
            'error' => true,
            'msg' => strtr($msg ?? $this->msg, $param),
        ];
    }

    protected function resultOk($stop = false, $value = null)
    {
        $result = [
            'error' => false,
            'stop' => $stop,
        ];
        if (isset($value)) {
            $result['value'] = $value;
        }
        return $result;
    }
}
