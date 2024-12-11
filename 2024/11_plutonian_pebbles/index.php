<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$stones = array_map('intval', explode(' ', $rows[0]));

$sw = new Stopwatch();

/**
 * This will bite me in the ass when part 2 comes around; for every iteration we go over every stone and apply the
 * rules. After the last iteration we count the number of stones.
 *
 * @param array<int, int> $stones
 * @return int
 */
function part_one(array $stones): int {
    for ($i = 1; $i <= 25; $i++) {
        $iteration = [];
        foreach($stones as $stone) {
            if ($stone === 0) {
                $iteration[] = 1;
            } elseif (strlen((string)$stone) % 2 === 0) {
                $str = (string)$stone;
                $iteration[] = intval(substr($str, 0, strlen($str) / 2));
                $iteration[] = intval(substr($str, strlen($str) / 2));
            } else {
                $iteration[] = $stone * 2024;
            }
        }

        $stones = $iteration;
    }

    return count($stones);
}

/**
 * After part two I realized part 2 can of course also be applied to part one.
 *
 * @param array<int, int> $stones
 * @return int
 */
function part_one_optimized(array $stones): int {
    return part_two($stones, 25);
}

$sw->start();
echo 'How many stones will you have after blinking 25 times? ' . part_one_optimized($stones) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * After iteration 30 PHP ran out of memory. We can increase memory, but running out of memory is a clear indication
 * that the solution is not the correct one. 75 iterations is not enough for a pattern, see 2022 day 17, so there
 * probably is another way. Looking at the example I noticed that some stones occurred more than once. So the solution
 * is to keep track of the number of times every stone occurs; when a stone occurs 10 times, we don't need to perform
 * the operation 10 times, because the outcome will be the same.
 *
 * @param array<int, int> $stones
 * @param int $iterations
 * @return int
 */
function part_two(array $stones, int $iterations = 75): int {
    $frequencies = [];
    foreach ($stones as $stone) {
        $frequencies[$stone] = ($frequencies[$stone] ?? 0) + 1;
    }

    for ($i = 1; $i <= $iterations; $i++) {
        $new_frequencies = [];

        foreach ($frequencies as $stone => $frequency) {
            if ($stone === 0) {
                $new_frequencies[1] = ($new_frequencies[1] ?? 0) + $frequency;
            } elseif (strlen((string)$stone) % 2 === 0) {
                $str = (string)$stone;
                $left = intval(substr($str, 0, strlen($str) / 2));
                $right = intval(substr($str, strlen($str) / 2));

                $new_frequencies[$left] = ($new_frequencies[$left] ?? 0) + $frequency;
                $new_frequencies[$right] = ($new_frequencies[$right] ?? 0) + $frequency;
            } else {
                $new_frequencies[$stone * 2024] = ($new_frequencies[$stone * 2024] ?? 0) + $frequency;
            }
        }

        $frequencies = $new_frequencies;
    }

    return array_sum($frequencies);
}

$sw->start();
echo 'How many stones would you have after blinking a total of 75 times? ' . part_two($stones) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
