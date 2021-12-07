<?php

declare(strict_types=1);

$positions = array_map('intval', explode(',', trim(file_get_contents(__DIR__ . '/input.txt'))));

// Part 1:
sort($positions);
if (count($positions) % 2 === 1) {
    $median = $positions[floor(count($positions) / 2)];
} else {
    $median = ceil(($positions[count($positions) / 2 - 1] + $positions[count($positions) / 2]) / 2);
}

$fuel_consumption = array_reduce($positions, fn(int $carry, int $item): int => $carry + (int)abs($median - $item), 0);

echo 'How much fuel must they spend to align to that position?' . PHP_EOL;
echo $fuel_consumption . PHP_EOL;
