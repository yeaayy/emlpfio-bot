<?php
namespace org\lumira\Validation;

class ValidateIsIn extends ValidationRule
{
    private array $values;

    function __construct(string | null $msg = null, ...$values)
    {
        parent::__construct($msg ?? 'unxepected value of %name%');
        $this->values = $values;
    }

    function validate($input): mixed
    {
        foreach ($this->values as $value) {
            if ($value == $input) {
                return $this->resultOk();
            }
        }
        return $this->resultError();
    }
}
