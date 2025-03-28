<?php

namespace org\lumira\Errors;

use Exception;

class HttpError extends Exception {
    // public int $code;
    public function __construct(int $http_code, string $message = "", \Throwable|null $previous = null) {
        parent::__construct($message, $http_code, $previous);
    }
}
