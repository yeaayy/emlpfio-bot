<?php

namespace org\lumira\Validation;

class ValidationError extends \Error
{
    public array $errors;

    function __construct(array $errors)
    {
        $this->errors = $errors;
    }
}
