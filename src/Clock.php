<?php

namespace Hsalem7\PhpRateLimit;

class Clock
{
    public function currentTime(): int
    {
        return time();
    }
}