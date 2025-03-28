<?php

namespace org\lumira\Validation;

class ValidateStrMin extends ValidationRule
{
    private int $min;

    function __construct(int $min, string | null $msg = null)
    {
        parent::__construct($msg ?? '%name% must be at least %min% character');
        $this->min = $min;
    }

    function validate($input): mixed
    {
        $length = strlen($input);
        if ($length < $this->min) {
            return $this->resultError([
                '%min%' => $this->min,
                '%length%' => $length,
            ]);
        }
        return $this->resultOk();
    }
}
