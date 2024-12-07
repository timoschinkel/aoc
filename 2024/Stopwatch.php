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

        if ($ellapsedNanoSeconds > 1000000000) {
            return "\e[31m" . round((hrtime(true) - $this->start) / 1000000000, 2) . "s\e[0m";
        } elseif ($ellapsedNanoSeconds > 1000000) {
            return ceil((hrtime(true) - $this->start) / 1000000) . 'ms';
        } elseif ($ellapsedNanoSeconds > 1000) {
            return "\e[32m" . ceil((hrtime(true) - $this->start) / 1000) . "μs\e[0m";
        } else {
            return "\e[1;32m" . (hrtime(true) - $this->start) . "ns\e[0;0m";
        }
    }

    public function ellapsedMS(): float {
        return ceil((hrtime(true) - $this->start) / 1000000);
    }
}
