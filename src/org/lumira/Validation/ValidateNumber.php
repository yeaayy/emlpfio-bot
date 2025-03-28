<?php

namespace org\lumira\Validation;

class ValidateNumber extends ValidationRule
{
    function __construct(string | null $msg = null)
    {
        parent::__construct($msg ?? '%name% must be a number');
    }

    function validate($input): mixed
    {
        switch (gettype($input)) {
            case 'number':
                return $this->resultOk();
            case 'string':
                if (is_numeric($input)) {
                    return $this->resultOk(false, intval($input));
                }
            default:
                return $this->resultError();
        }
    }
}
