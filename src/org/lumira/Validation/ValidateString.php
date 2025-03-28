<?php

namespace org\lumira\Validation;

class ValidateString extends ValidationRule
{
    function __construct(string | null $msg = null)
    {
        parent::__construct($msg ?? '%name% must be string');
    }

    function validate($input): mixed
    {
        if (is_string($input)) {
            return $this->resultOk();
        }
        return $this->resultError();
    }
}
