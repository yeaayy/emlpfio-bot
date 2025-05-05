<?php

namespace org\lumira\Parser;

class Space extends Base {

    public function __construct(?ErrorHandler $errorHandler = null) {
        parent::__construct($errorHandler);
    }

    protected function exec(Stream $in, &$result): bool {
        while(preg_match("/\s/", $in->peek())) {
            $in->seek();
        }
        return true;
    }
}
