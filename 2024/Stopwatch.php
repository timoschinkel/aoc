<?php

declare(strict_types=1);

class Stopwatch {
    private ?float $start;

    public function __construct() {
    }

    public function start(): void {
        $this->start = hrtime(true);
    }

    public function ellapsed(): string {
        $ellapsedNanoSeconds = hrtime(true) - $this->start;

        if ($ellapsedNanoSeconds > 1000000) {
            return ceil((hrtime(true) - $this->start) / 1000000) . 'ms';
        } elseif ($ellapsedNanoSeconds > 1000) {
            return ceil((hrtime(true) - $this->start) / 1000) . 'μs';
        } else {
            return (hrtime(true) - $this->start) . 'ns';
        }
    }

    public function ellapsedMS(): float {
        return ceil((hrtime(true) - $this->start) / 1000000);
    }
}
