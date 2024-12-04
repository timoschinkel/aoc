<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

$sw = new Stopwatch();

/*
 * The only relevant trigger is X, so we can iterate over the input until we find an X. Then we look in all eight
 * directions (N, NE, E, SE, S, SW, W, NW) to see if we have the word XMAS.
 */
function part_one(array $rows): int {
    $occurrences = 0;
    for ($row = 0; $row < count($rows); $row++) {
        for ($column = 0; $column < strlen($rows[$row]); $column++) {
            if ($rows[$row][$column] === 'X') {
                if (has_xmas($rows, $row, $column, -1, 0)) $occurrences++;  // N
                if (has_xmas($rows, $row, $column, -1, 1)) $occurrences++;  // NE
                if (has_xmas($rows, $row, $column, 0, 1)) $occurrences++;   // E
                if (has_xmas($rows, $row, $column, 1, 1)) $occurrences++;   // SE
                if (has_xmas($rows, $row, $column, 1, 0)) $occurrences++;   // S
                if (has_xmas($rows, $row, $column, 1, -1)) $occurrences++;  // SW
                if (has_xmas($rows, $row, $column, 0, -1)) $occurrences++;  // W
                if (has_xmas($rows, $row, $column, -1, -1)) $occurrences++; // NW
            }
        }
    }
    return $occurrences;
}

function has_xmas(array $rows, int $row, int $column, int $deltaRow, int $deltaColumn): bool {
    // Early exits; we can deduct that we cannot find the word XMAS due to out-of-bounds limitations
    if (
        ($deltaRow === -1 && $row < 3) ||
        ($deltaColumn === -1 && $column < 3) ||
        ($deltaRow === 1 && $row > count($rows) - 4) ||
        ($deltaColumn === 1 && $column > strlen($rows[$row]) - 4)
    ) {
        return false;
    }

    // Using early exits actually shaved off 2ms 🤷‍♂️
    if (($rows[$row + $deltaRow][$column + $deltaColumn] ?? '.') !== 'M') return false;
    if (($rows[$row + $deltaRow * 2][$column + $deltaColumn * 2] ?? '.') !== 'A') return false;
    if (($rows[$row + $deltaRow * 3][$column + $deltaColumn * 3] ?? '.') !== 'S') return false;

    return true;
}

$sw->start();
echo 'How many times does XMAS appear? ' . part_one($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/*
 * Contrary to part 1 the trigger is now the character A. And the number of possibilities is smaller; we only need to
 * check NW to SE and NE and SW. And vise versa.
 */
function part_two(array $rows): int {
    $occurrences = 0;
    for ($row = 1; $row < count($rows) - 1; $row++) {
        for ($column = 1; $column < strlen($rows[$row]) - 1; $column++) {
            if ($rows[$row][$column] === 'A') {
                // M . M    or  M . S   or  S . M    or S . S
                // . A .        . A .       . A .       . A .
                // S . S        M . S       S . M       M . M
                if (
                    (
                        // NW -> SE
                        ($rows[$row - 1][$column - 1] === 'M' && $rows[$row + 1][$column + 1] === 'S') || ($rows[$row - 1][$column - 1] === 'S' && $rows[$row + 1][$column + 1] === 'M')
                    ) && (
                        // NE -> SW
                        ($rows[$row - 1][$column + 1] === 'M' && $rows[$row + 1][$column - 1] === 'S') || ($rows[$row - 1][$column + 1] === 'S' && $rows[$row + 1][$column - 1] === 'M')
                    )
                ) {
                    $occurrences++;
                }
            }
        }
    }
    return $occurrences;
}

$sw->start();
echo 'How many times does an X-MAS appear? ' . part_two($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
