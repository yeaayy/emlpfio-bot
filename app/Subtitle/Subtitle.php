<?php

namespace App\Subtitle;

class Subtitle
{
    public int $index;
    public string $text;
    public Timestamp $start;
    public Timestamp $end;

    public function __construct(
        int $index,
        string $text,
        Timestamp $start,
        Timestamp $end
    ) {
        $this->index = $index;
        $this->text = $text;
        $this->start = $start;
        $this->end = $end;
    }
}
