<?php

namespace org\lumira\Parser;

abstract class Base
{
    private ErrorHandler $errorHandler;

    protected abstract function exec(Stream $in, &$result): bool;

    public function __construct(?ErrorHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler ?? new ErrorHandler();
    }

    public function getErrorHandler(): ErrorHandler
    {
        return $this->errorHandler;
    }

    public function beginParse(Stream $in, &$result)
    {
        $start = $in->tellg();
        if (!$this->exec($in, $result)) {
            $in->start($start);
            return false;
        }
        return true;
    }

    public function parse(Stream $in)
    {
        $result = null;
        $this->beginParse($in, $result);
        return $result;
    }

    public function errorii(int $start, int $end, string $fmt, ...$args): bool
    {
        $this->errorHandler->reportError($start, $end, $fmt, ...$args);
        return false;
    }

    public function errorsl(Stream $in, int $len, string $fmt, ...$args): bool
    {
        $start = $in->tellg();
        $this->errorHandler->reportError($start, $start + $len, $fmt, ...$args);
        return false;
    }
}
