<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

$sw = new Stopwatch();

$reports = array_map(fn(string $row): array => array_map('intval', preg_split('%\s+%', $row)), $rows);

/*
 * Iterate through the levels in the report and calculate the difference between this level and the next level. If that
 * difference is larger than three, or equals to zero, or the previous difference was positive and the current is
 * negative or vise versa.
 *
 * What tricked me up is the "equals to zero", and I tried to be smart by calculating the difference between the first
 * two outside the loop. But you need to perform the requirement checks for that value as well.
 */
function part_one(array $reports): int {
    return count(array_filter($reports, fn(array $report): bool => is_safe($report)));
}

$sw->start();
echo 'How many reports are safe? ' . part_one($reports) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/*
 * What to keep in mind; if the report was valid in the fist step, then it is also valid in the second step. This means
 * that we only had to look at the unsafe reports. There are two approaches; find the violating item and check the
 * report without the violating level. Or we just brute force it, which is easier to write.
 */
function part_two(array $reports): int {
    return count(array_filter($reports, fn(array $report): bool => is_safe($report) || is_safe_with_dampener($report)));
}

/*
 * `part_two()` takes 2ms, that can be done faster. Instead of exhaustive trying all possibilities, we can find the
 * level that makes the report unsafe. Now there are three possible ways to solve this; remove previous level, remove
 * current level or remove next level. This reduces the number of possibilities per report to 2 (when the unsafeness
 * occurs already between the first and second level) or 3.
 */
function part_two_optimized(array $reports): int {
    return count(array_filter($reports, fn(array $report): bool => is_safe_with_dampener_optimized($report)));
}

$sw->start();
echo 'How many reports are now safe? ' . part_two_optimized($reports) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

function is_safe(array $report): bool {
    $prev = $report[1] - $report[0];

    // Don't forget to check the first difference
    if ($prev === 0 || $prev < -3 || $prev > 3) return false;

    for ($i = 1; $i < count($report) - 1; $i++) {
        $diff = $report[$i + 1] - $report[$i];

        if ($diff < -3 || $diff > 3 || $diff === 0 || ($prev < 0 && $diff > 0) || ($prev > 0 && $diff < 0)) {
            return false;
        }

        $prev = $diff;
    }
    return true;
}

function is_safe_with_dampener(array $report): bool
{
    // just try all permutations
    for ($i = 0; $i < count($report); $i++) {
        $mutation = [...array_slice($report, 0, $i), ...array_slice($report, $i + 1)];
        if (is_safe($mutation)) {
            return true;
        }
    }
}

function is_safe_with_dampener_optimized(array $report): bool {
    $prev = $report[1] - $report[0];

    // Don't forget to check the first difference
    if ($prev === 0 || $prev < -3 || $prev > 3) {
        return is_safe(remove_index($report, 0)) || is_safe(remove_index($report, 1));
    };

    for ($i = 1; $i < count($report) - 1; $i++) {
        $diff = $report[$i + 1] - $report[$i];

        if ($diff < -3 || $diff > 3 || $diff === 0 || ($prev < 0 && $diff > 0) || ($prev > 0 && $diff < 0)) {
            return is_safe(remove_index($report, $i - 1))
                || is_safe(remove_index($report, $i))
                || is_safe(remove_index($report, $i + 1));
        }

        $prev = $diff;
    }

    return true;
}

function remove_index(array $arr, int $index): array
{
    $clone = [...$arr];
    unset($clone[$index]);

    return array_values($clone);
}
