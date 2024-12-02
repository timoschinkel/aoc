<?php

declare(strict_types=1);

class Stopwatch {
    private ?float $start;

    public function __construct() {
    }

    public function start(): void {
        $this->start = hrtime(true);
    }

    public function ellapsedMS(): float {
        return ceil((hrtime(true) - $this->start) / 1000000);
    }
}
