<?php

namespace App\Subtitle;

use org\lumira\Parser as p;

class TimestampParser extends p\Base
{
    private p\Number $num;
    private p\Space $sp;

    public function __construct(
        ?p\Number $num = null,
        ?p\ErrorHandler $errorHandler = null,
    ) {
        parent::__construct($errorHandler);
        $this->sp = $sp ?? new p\Space($this->getErrorHandler());
        $this->num = $num ?? new p\Number(',', $this->sp, $this->getErrorHandler());
    }

    protected function exec(p\Stream $in, &$result): bool
    {
        // Read hour
        if (!$this->num->beginParse($in, $hours)) {
            return false;
        }

        // Separator
        $this->sp->parse($in);
        if ($in->get() !== ":") {
            return false;
        }
        $this->sp->parse($in);

        // Read minute
        if (!$this->num->beginParse($in, $minutes)) {
            return false;
        }

        // Separator
        $this->sp->parse($in);
        if ($in->get() !== ":") {
            return false;
        }
        $this->sp->parse($in);

        // Read second
        if (!$this->num->beginParse($in, $seconds)) {
            return false;
        }

        $result = new \App\Subtitle\Timestamp($hours, $minutes, $seconds);
        return true;
    }
}

