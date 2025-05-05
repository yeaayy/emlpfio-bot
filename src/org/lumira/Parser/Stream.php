<?php

namespace org\lumira\Parser;

class Stream
{
    private string $buffer;
    private int $index;
    private int $len;

    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
        $this->index = 0;
        $this->len = mb_strlen($buffer);
    }

    public function get(): string
    {
        return $this->eof() ? '' : mb_substr($this->buffer, $this->index++, 1);
    }

    public function peek(int $n = 0): string
    {
        return $this->eof($n) ? '' : mb_substr($this->buffer, $this->index + $n, 1);
    }

    public function peek_codepoint(int $n = 0)
    {
        return mb_ord($this->peek($n));
    }

    public function eof(int $n = 0): bool
    {
        return ($this->index + $n) >= $this->len;
    }

    public function tellg(): int
    {
        return $this->index;
    }

    public function seek(int $n = 1): void
    {
        $this->index += $n;
    }

    public function start(int $n = 0): void
    {
        $this->index = $n;
    }

    public function end($n = 0): void
    {
        $this->index = $this->len + $n;
    }
}
