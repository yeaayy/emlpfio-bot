<?php

namespace org\lumira\Validation;

class ValidateStrMax extends ValidationRule
{
    private int $max;

    function __construct(int $max, string | null $msg = null)
    {
        parent::__construct($msg ?? '%name% must be %max% character(s) or shorter');
        $this->max = $max;
    }

    function validate($input): mixed
    {
        $length = strlen($input);
        if ($length > $this->max) {
            return $this->resultError([
                '%max%' => $this->max,
                '%length%' => $length,
            ]);
        }
        return $this->resultOk();
    }
}
