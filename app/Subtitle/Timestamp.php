<?php

namespace App\Subtitle;

class Timestamp
{
    public float $hours;
    public float $minutes;
    public float $seconds;

    public function __construct(
        float $hours,
        float $minutes,
        float $seconds
    ) {
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->seconds = $seconds;
    }

    public function toSecond()
    {
        return ($this->hours * 60 + $this->minutes) * 60 + $this->seconds;
    }

}
