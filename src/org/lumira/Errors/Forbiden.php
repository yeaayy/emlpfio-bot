<?php

namespace org\lumira\Errors;

class Forbiden extends HttpError {
    function __construct(string $message = "Forbiden", \Throwable|null $previous = null) {
        parent::__construct(403, $message, $previous);
    }
}
