<?php

namespace org\lumira\Parser;

class Number extends Base
{
    private Space $sp;
    private string $decimal;

    public function __construct(
        string $decimal = '.',
        ?Space $sp = null,
        ?ErrorHandler $errorHandler = null,
    ) {
        parent::__construct($errorHandler);
        $this->decimal = $decimal;
        $this->sp = $sp ?? new Space($this->getErrorHandler());
    }

    protected function exec(Stream $in, &$result): bool
    {
        $sign = 1;
        if ($in->peek() === '-') {
            $sign = -1;
            $in->seek();
            $this->sp->parse($in);
        }
        $tmp = '';
        $hasDot = false;
        while (true) {
            $next = $in->peek();
            if ($next === $this->decimal) {
                if ($tmp === '') {
                    $tmp = '0.';
                } else if (!$hasDot) {
                    $tmp .= '.';
                }
                $hasDot = true;
                $in->seek();
                continue;
            }
            if (!is_numeric($next)) {
                break;
            }
            $tmp .= $next;
            $in->seek();
        }
        if ($tmp === '') {
            return false;
        }
        $result = $sign * floatval($tmp);
        return true;
    }
}
