<?php
namespace org\lumira\Validation;

class ValidateRequired extends ValidationRule
{
    function __construct(string | null $msg = null)
    {
        parent::__construct($msg ?? '%name% is required');
    }

    function validate($input): mixed
    {
        if (!isset($input)) {
            return $this->resultError();
        }
        return $this->resultOk();
    }
}
