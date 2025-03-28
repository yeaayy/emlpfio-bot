<?php

namespace org\lumira\Errors;

use Exception;

class MethodNotAllowed extends HttpError {
    function __construct(string $message = "Method not allowed", \Throwable|null $previous = null) {
        parent::__construct(405, $message, $previous);
    }
}
