<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$sw = new Stopwatch();

/*
 * I have opted for recursion for this puzzle. We need to determine if a certain row is valid, but we need to try
 * multiple operators. What made me opt for recursion is the line "Operators are always evaluated left-to-right, not
 * according to precedence rules".
 *
 * Optimization: The first iteration will always start with 0, making the multiplication useless. We can also already
 * start with the first value, and remove this element from the remaining values. This almost halves the execution time.
 */
function part_one(array $rows): int {
    $sum = 0;
    foreach ($rows as $row) {
        $parts = array_map('intval', preg_split('%:?\s+%', $row));

        if (is_valid($parts[0], array_slice($parts, 2), $parts[1])) {
            $sum += $parts[0];
        }
    }

    return $sum;
}

function is_valid (int $outcome, array $values, int $current = 0): bool {
    if ($current > $outcome) { // early exit
        return false;
    }

    if (count($values) === 0) { // we've reached the end
        return $current === $outcome;
    }

    $next = array_shift($values);

    // RECURSION!
    // NB. Since I'm trying to return as soon as possible I considered applying the operator that grows the fastest -
    // the multiply operator - should be evaluated first. However, that actually made the execution time worse -
    // from 48ms to 55ms.
    return is_valid($outcome, $values, $current + $next) || is_valid($outcome, $values, $current * $next);
}

$sw->start();
echo 'What is their total calibration result? ' . part_one($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/*
 * The exact same approach as part 1, only with a different implementation of the recursion. Again I found that my
 * intuition was wrong and having the most heavy operation last instead of first gave a better performance. This might
 * be caused by how PHP handles recursion.
 *
 * Optimization: the same optimization as part 1 - starting with the first value instead of with 0 -, but also another
 * optimization that removes converting int values to a string.
 */
function part_two(array $rows): int {
    $sum = 0;
    foreach ($rows as $row) {
        $parts = array_map('intval', preg_split('%:?\s+%', $row));

        if (is_valid_part_two($parts[0], array_slice($parts, 2), $parts[1])) {
            $sum += $parts[0];
        }
    }

    return $sum;
}

function is_valid_part_two (int $outcome, array $values, int $current = 0): bool {
    if ($current > $outcome) { // early exit
        return false;
    }

    if (count($values) === 0) { // we've reached the end
        return $current === $outcome;
    }

    $next = array_shift($values);

    // RECURSION!
    return
        is_valid_part_two($outcome, $values, $current + $next)
        || is_valid_part_two($outcome, $values, $current * $next)
        || is_valid_part_two($outcome, $values, concat($current, $next));
}

function concat (int $current, int $next): int {
    if ($next < 10) return $current * 10 + $next;
    if ($next < 100) return $current * 100 + $next;
    if ($next < 1000) return $current * 1000 + $next;
    if ($next < 10000) return $current * 10000 + $next;
    if ($next < 100000) return $current * 100000 + $next;
    if ($next < 1000000) return $current * 1000000 + $next;

    return intval((string)$current . (string)$next);
}

$sw->start();
echo 'What is their total calibration result? ' . part_two($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
