<?php

namespace org\lumira\Parser;

class ErrorHandler {

    private int $start;
    private int $end;
    private string $msg;
    private $onErrors = [];

    public function __construct() {
    }

    public function reportError(int $start, int $end, string $fmt, ...$args): void {
        $this->start = $start;
        $this->end = $end;
        $this->msg = sprintf($fmt, ...$args);
        foreach($this->onErrors as $handler) {
            $handler($this);
        }
    }

    public function getMessage(): string {
        return $this->msg;
    }

    public function getStart(): int {
        return $this->start;
    }

    public function getEnd(): int {
        return $this->end;
    }

    public function addOnError(\Closure $onError) {
        array_push($this->onErrors, $onError);
    }

}
