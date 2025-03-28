<?php

namespace org\lumira\Errors;

use Exception;

class Conflict extends HttpError {
    function __construct(string $message = "Conflict", \Throwable|null $previous = null) {
        parent::__construct(409, $message, $previous);
    }
}
