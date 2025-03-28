<?php
namespace org\lumira\Validation;

class ValidateOptional extends ValidationRule
{
    private $default;
    function __construct($default = null)
    {
        parent::__construct('');
        $this->default = $default;
    }

    function validate($input): mixed
    {
        if (!isset($input)) {
            return $this->resultOk(true, null);
        }
        return $this->resultOk(false, $this->default);
    }
}
