<?php
namespace App\Subtitle;

use org\lumira\Parser as p;
use App\Subtitle\Timestamp;

class SubtitleParser extends p\Base
{
    private p\Number $num;
    private p\Space $sp;
    private TimestampParser $timestamp;

    public function __construct(
        ?p\Number $num = null,
        ?TimestampParser $timestamp = null,
        ?p\ErrorHandler $errorHandler = null,
    ) {
        parent::__construct($errorHandler);
        $this->sp = $sp ?? new p\Space($this->getErrorHandler());
        $this->num = $num ?? new p\Number(',', $this->sp, $this->getErrorHandler());
        $this->timestamp = $timestamp ?? new TimestampParser($num, $this->getErrorHandler());
    }

    protected function exec(p\Stream $in, &$result): bool
    {
        $aresult = [];
        // Check for byte order mark
        if ($in->peek_codepoint() == 0xFEFF) {
            $in->seek();
        }

        while(true) {
            $this->sp->parse($in);
            if (!$this->num->beginParse($in, $index)) {
                if ($in->eof()) {
                    break;
                } else {
                    return false;
                }
            }
            $this->sp->parse($in);
            if (!$this->timestamp->beginParse($in, $startTime)) {
                return false;
            }

            // Separator
            $this->sp->parse($in);
            if ($in->get() !== "-" || $in->get() !== "-" || $in->get() !== ">") {
                return false;
            }
            $this->sp->parse($in);

            if (!$this->timestamp->beginParse($in, $endTime)) {
                return false;
            }

            // This assume the subtitle text are not empty
            $this->sp->parse($in);

            $text = [];
            $prev2 = "";
            $prev1 = $in->get();
            while (true) {
                $prev2 = $prev1;
                while (($prev1 = $in->peek()) == "\xd") {
                    $in->seek();
                }
                if (($prev2 === "\n" && $prev1 === "\n") || $in->eof()) {
                    break;
                }
                $in->seek();
                if ($prev2 !== "\n" || $prev1 !== " ") {
                    array_push($text, $prev2);
                }
            }

            array_push($aresult, new \App\Subtitle\Subtitle($index, join($text), $startTime, $endTime));
        }
        $result = $aresult;
        return true;
    }
}
