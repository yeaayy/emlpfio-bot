<?php

namespace org\lumira\Parser;

class IntegerParser extends Base
{
    public function __construct(?ErrorHandler $errorHandler = null)
    {
        parent::__construct($errorHandler);
    }

    protected function exec(Stream $in, &$result): bool
    {
        $tmp = '';
        while (is_numeric($in->peek())) {
            $tmp .= $in->get();
        }
        if ($tmp === '') {
            return false;
        }
        $result = intval($tmp);
        return true;
    }
}
