<?php

namespace org\lumira\Parser;

class LiteralParser extends Base {

    public function __construct(?ErrorHandler $errorHandler = null) {
        parent::__construct($errorHandler);
    }

    protected function exec(Stream $in, &$result): bool {
        $tmp = $in->get();
        if(!preg_match("/[a-zA-Z]/", $tmp)) {
            return false;
        }
        while(true) {
            $x = $in->peek();
            if(!preg_match("/[a-zA-Z-]/", $x)) {
                break;
            }
            $in->seek();
            $tmp .= $x;
        }
        $result = $tmp;
        return true;
    }
}
