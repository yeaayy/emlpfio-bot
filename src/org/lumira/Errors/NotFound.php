<?php

namespace org\lumira\Errors;

use Exception;

class NotFound extends HttpError {
    function __construct(string $message = "Not found", \Throwable|null $previous = null) {
        parent::__construct(404, $message, $previous);
    }
}
