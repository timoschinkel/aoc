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

// Part 2:
$min = array_reduce($positions, fn(int $carry, int $item): int => min($carry, $item), reset($positions));
$max = array_reduce($positions, fn(int $carry, int $item): int => max($carry, $item), 0);

$consumptions = array_fill(0, $max + 1, 0);
for ($horizontal_position = $min; $horizontal_position <= $max; $horizontal_position++) {
    // iterate over all possible horizontal positions
    $consumptions[$horizontal_position] = array_reduce(
        $positions,
        function(int $carry, int $crab_position) use ($horizontal_position): int {
            $n = (int)abs($horizontal_position - $crab_position);

            return $carry + ($n * ($n + 1) / 2);
        },
        0
    );
}

asort($consumptions, SORT_ASC);

echo 'How much fuel must they spend to align to that position?' . PHP_EOL;
echo reset($consumptions) . PHP_EOL;
