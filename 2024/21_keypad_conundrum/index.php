<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day21;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input

$sw = new \Stopwatch();

/**
 * An important observation is that most numeric codes can be achieved in multiple ways; from 2 to 9 is always
 * achievable in 3 steps, but it can be achieved via ^^>, ^>^, and >^^. As the numeric pad is operated using the
 * directional pads, different paths can lead to different (length) paths down the directional pads. I cheated a little
 * bit - or was smart - and precalculated the different paths between any two keys for both the directional and numeric
 * keypads. With that information I implemented a recursive implementation, hoping it would work in two levels deep. It
 * does.
 *
 * @param array<string> $codes
 * @return int
 */
function part_one(array $codes): int {
    $complexity = 0;
    foreach ($codes as $code) {
        $variations = numeric_keypad($code);

        $value = intval($code);
        $shortest = null;
        foreach ($variations as $variation) {
            $shortest_for_variation = find_shortest($variation);
            for ($i = 1; $i < 2; $i++) {
                $shortest_for_variation = find_shortest($shortest_for_variation);
            }

            if ($shortest === null || strlen($shortest_for_variation) < strlen($shortest)) {
                $shortest = $shortest_for_variation;
            }
        }

//        echo 'Code: ' . $code . ', value: ' . $value . ', shortest: ' . strlen($shortest) . PHP_EOL;

        $complexity += $value * strlen($shortest);
    }

    return $complexity;
}

function find_shortest(string $pattern): string {
    if (strlen($pattern) === 0) return '';

    // find until first A
    $a = strpos($pattern, 'A');
    $first = substr($pattern, 0, $a + 1);
    $remainder = substr($pattern, $a + 1);

    return shortest($first) . find_shortest($remainder);
}

function shortest(string $pattern): string {
    $candidates = directional_keypad($pattern);

    // find the shortest
    $shortest = $candidates[0];
    for ($i = 1; $i < count($candidates); $i++) {
        if (strlen($shortest) > strlen($candidates[$i])) {
            $shortest = $candidates[$i];
        }
    }

    return $shortest;
}

function get_numeric_keypad(): array {
    return [
        '0' => [
            '0' => 'A',
            '1' => '^<A',
            '2' => '^A',
            '3' => ['^>A', '>^A'],
            '4' => ['^^<A', '^<^A'],
            '5' => '^^A',
            '6' => ['^^>A', '^>^A', '>^^A'],
            '7' => ['^^^<A', '^<^^A', '^^<^A'],
            '8' => '^^^A',
            '9' => ['^^^>A', '^^>^A', '^>^^A'],
            'A' => '>A',
        ],
        '1' => [
            '0' => '>vA',
            '1' => 'A',
            '2' => '>A',
            '3' => '>>A',
            '4' => '^A',
            '5' => ['>^A', '^>A'],
            '6' => ['>>^A', '>^>A', '^>>A'],
            '7' => '^^A',
            '8' => ['^^>A', '^>^A', '.^^A'],
            '9' => ['^^>>A', '^>^>A', '^>>^A', '>^>^A', '>^^>A', '>>^^A'],
            'A' => ['>>vA', '>v>A'],
        ],
        '2' => [
            '0' => 'vA',
            '1' => '<A',
            '2' => 'A',
            '3' => '>A',
            '4' => ['^<A', '<^A'],
            '5' => '^A',
            '6' => ['^>A', '>^A'],
            '7' => ['^^<A', '<^^A', '^<^A'],
            '8' => '^^A',
            '9' => ['>^^A', '^^>A', '^>^A'],
            'A' => ['>vA', 'v>A'],
        ],
        '3' => [
            '0' => ['<vA', 'v<A'],
            '1' => '<<A',
            '2' => '<A',
            '3' => 'A',
            '4' => ['^<<A', '<^<A', '<<^A'],
            '5' => ['^<A', '<^A'],
            '6' => '^A',
            '7' => ['^^<<A', '^<^<A', '<^^<A', '^<^<A', '^<<^A', '<<^^A'],
            '8' => ['^^<A', '^<^A', '<^^A'],
            '9' => '^^A',
            'A' => 'vA',
        ],
        '4' => [
            '0' => ['>vvA', 'v>vA'],
            '1' => 'vA',
            '2' => ['>vA', 'v>A'],
            '3' => ['>>vA', '>v>A', 'v>>A'],
            '4' => 'A',
            '5' => '>A',
            '6' => '>>A',
            '7' => '^A',
            '8' => ['>^A', '^>A'],
            '9' => ['>>^A', '>^>A', '^>>A'],
            'A' => ['>>vvA', '>v>vA', 'v>>vA', 'v>v>A', '>vv>A'],
        ],
        '5' => [
            '0' => 'vvA',
            '1' => ['v<A', '<vA'],
            '2' => 'vA',
            '3' => ['v>A', '>vA'],
            '4' => '<A',
            '5' => 'A',
            '6' => '>A',
            '7' => ['^<A', '<^A'],
            '8' => '^A',
            '9' => ['^>A', '>^A'],
            'A' => ['>vvA', 'v>vA', 'vv>A'],
        ],
        '6' => [
            '0' => ['<vvA', 'v<vA', 'vv<A'],
            '1' => ['<<vA', '<v<A', 'v<<A'],
            '2' => ['<vA', 'v<A'],
            '3' => 'vA',
            '4' => '<<A',
            '5' => '<A',
            '6' => 'A',
            '7' => ['^<<A', '<^<A', '<<^A'],
            '8' => ['^<A', '<^A'],
            '9' => '^A',
            'A' => 'vvA',
        ],
        '7' => [
            '0' => ['>vvvA', 'v>vvA', 'vv>vA'],
            '1' => 'vvA',
            '2' => ['>vvA', 'v>vA', 'vv>A'],
            '3' => ['>>vvA', '>v>vA', '>vv>A', 'v>>vA', 'v>v>A', 'vv>>A'],
            '4' => 'vA',
            '5' => ['>vA', 'v>A'],
            '6' => ['>>vA', '>v>A', 'v>>A'],
            '7' => 'A',
            '8' => '>A',
            '9' => '>>A',
            'A' => ['>>vvvA', '>v>vvA', '>vv>vA', '>vvv>A', 'v>>vvA', 'v>v>vA', 'v>vv>A', 'vv>>vA', 'vv>v>A']
        ],
        '8' => [
            '0' => 'vvvA',
            '1' => ['vv<A', 'v<vA', 'vv<A'],
            '2' => 'vvA',
            '3' => ['vv>A', 'v>vA', '>vvA'],
            '4' => ['v<A', '<vA'],
            '5' => 'vA',
            '6' => ['v>A', '>vA'],
            '7' => '<A',
            '8' => 'A',
            '9' => '>A',
            'A' => ['>vvvA', 'v>vvA', 'vv>vA', 'vvv>A'],
        ],
        '9' => [
            '0' => ['<vvvA', 'v<vvA', 'vv<vA', 'vvv<A'],
            '1' => ['<<vvA', '<v<vA', '<vv<A', 'v<<vA', 'v<v<A', 'vv<<A'],
            '2' => ['<vvA', 'v<vA', 'vv<A'],
            '3' => 'vvA',
            '4' => ['<<vA', '<v<A', 'v<<A'],
            '5' => ['<vA', 'v<A'],
            '6' => 'vA',
            '7' => '<<A',
            '8' => '<A',
            '9' => 'A',
            'A' => 'vvvA',
        ],
        'A' => [
            '0' => '<A',
            '1' => ['^<<A', '<^<A'],
            '2' => ['^<A', '<^A'],
            '3' => '^A',
            '4' => ['^^<<A', '^<<^A', '^<^<A', '<^^<A', '<^<^A'],
            '5' => ['^^<A', '^<^A', '<^^A'],
            '6' => '^^A',
            '7' => ['^^^<<A', '^^<^A', '^^<<^A', '^<^^<A', '^<^<^A', '^<<^^A', '<^^^<A', '<^^<^A', '<^<^^A'],
            '8' => ['^^^<A', '^^<^A', '^<^^A', '<^^^A'],
            '9' => '^^^A',
            'A' => 'A',
        ]
    ];
}

function numeric_keypad(string $code): array
{
    $paths = get_numeric_keypad();

    $out = [];
    $to_check = [['pos' => 'A', 'path' => '', 'code' => $code]];
    while (count($to_check) > 0) {
        ['pos' => $pos, 'path' => $path, 'code' => $remaining] = array_shift($to_check);

        if (strlen($remaining) === 0) {
            $out[] = $path;
            continue;
        }

        $options = $paths[$pos][$remaining[0]];
        if (is_array($options)) {
            foreach ($options as $option) {
                $to_check[] = ['pos' => $remaining[0], 'path' => $path . $option, 'code' => substr($remaining, 1)];
            }
        } else {
            $to_check[] = ['pos' => $remaining[0], 'path' => $path . $options, 'code' => substr($remaining, 1)];
        }
    }

    return $out;
}

function get_directional_keypad(): array {
    return [
        '^' => [
            '^' => 'A',
            '>' => ['>vA', 'v>A'],
            'v' => 'vA',
            '<' => 'v<A',
            'A' => '>A',
        ],
        '>' => [
            '^' => ['<^A', '^<A'],
            '>' => 'A',
            'v' => '<A',
            '<' => '<<A',
            'A' => '^A',
        ],
        'v' => [
            '^' => '^A',
            '>' => '>A',
            'v' => 'A',
            '<' => '<A',
            'A' => ['>^A', '^>A'],
        ],
        '<' => [
            '^' => '>^A',
            '>' => '>>A',
            'v' => '>A',
            '<' => 'A',
            'A' => ['>>^A', '>^>A'],
        ],
        'A' => [
            '^' => '<A',
            '>' => 'vA',
            'v' => ['<vA', 'v<A'],
            '<' => ['v<<A', '<v<A'],
            'A' => 'A',
        ],
    ];
}

function directional_keypad(string $code): array
{
    $paths = get_directional_keypad();

    // we only want with the shortest path

    $out = []; $min = PHP_INT_MAX;
    $to_check = [['pos' => 'A', 'path' => '', 'code' => $code]];
    while (count($to_check) > 0) {
        ['pos' => $pos, 'path' => $path, 'code' => $remaining] = array_shift($to_check);

        if (strlen($remaining) === 0) {
            if (strlen($path) < $min) {
                $min = strlen($path);
                $out = [$path];
            }

            if (strlen($code) === $min) {
                $out[] = $path;
            }

            continue;
        }

        if (strlen($path) > $min) {
            continue; // we're already overdue
        }

        $options = $paths[$pos][$remaining[0]];
        if (is_array($options)) {
            foreach ($options as $option) {
                $to_check[] = ['pos' => $remaining[0], 'path' => $path . $option, 'code' => substr($remaining, 1)];
            }
        } else {
            $to_check[] = ['pos' => $remaining[0], 'path' => $path . $options, 'code' => substr($remaining, 1)];
        }
    }

    return $out;
}

$sw->start();
echo 'What is the sum of the complexities of the five codes on your list? ' . part_one($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Simple recursion is not going to cut it. I tried it, but I quickly ran out of memory. The trick is to use caching or
 * memoization. Initially I tried to use the same approach as day 19 and cache the entire sequence, but that had the
 * same outcome as the initial approach; out of memory. It took a hint from someone else to point me towards what needed
 * to be cached; per depth, from and to character gives enough speed improvement and is not too heavy on memory. This is
 * possible, because apart from the numeric pad all iterations of the directional keypad start at A and end at A,
 * because A needs to be pressed to press the button in the next keypad.
 *
 * On thing to keep in mind, and that is what tricked me; every code starts with A. So for the numeric code I manually
 * add an A to the front of the code, and for the directional keypads I do the same, only a little bit implicit.
 *
 * @param array<string> $codes
 * @return int
 */
function part_two(array $codes): int {
    $complexity = 0;

    foreach ($codes as $code) {
//        echo 'Code: ' . $code . PHP_EOL;
        $value = intval($code);

        $length = 0;
        $cache = [];

        $code = 'A' . $code; // we're starting at the A position
        for($c = 0; $c < strlen($code) - 1; $c++) {
            $length += $s = find_shortest_part_two(get_numeric_keypad(), $code[$c], $code[$c + 1], 25, $cache);
            //echo 'From ' . ($code[$c]) . ' to ' . $code[$c+1] . ' takes ' . $s . PHP_EOL . PHP_EOL;
        }

//        echo $length . ' steps' . PHP_EOL;

        $complexity += $value * $length;
    }

    return $complexity;
}

function find_shortest_part_two(array $keypad, string $from, string $to, int $depth, array &$cache): int
{
//    echo 'Find shortest path from ' . $from . ' to ' . $to . ' at depth ' . $depth . PHP_EOL;
    if (isset($cache["{$from}-{$to}-{$depth}"])) {
        return $cache["{$from}-{$to}-{$depth}"];
    }

    $paths = $keypad[$from][$to];
    if (!is_array($paths)) $paths = [$paths];

    if ($depth === 0) {
        // press the button?
//        echo 'At depth 0, returning ' . (strlen($paths[0]) + 1) . ' (MH + 1) for pressing the button' . PHP_EOL;
        return strlen($paths[0]);
    }

    // Iterate over every potential path
    $min = null;
    foreach ($paths as $path) {
//        echo 'Checking path ' . $path . ' at depth ' . $depth . PHP_EOL;
        $length = 0;
        for($c = -1; $c < strlen($path) - 1; $c++) {
            // Start at -1 and fall back to A; that way we always start at A.
            $length += find_shortest_part_two(get_directional_keypad(), $path[$c] ?? 'A', $path[$c + 1], $depth - 1, $cache);
        }

        if ($min === null || $length < $min) $min = $length;
    }

    $cache["{$from}-{$to}-{$depth}"] = $min;
    return $min;
}

$sw->start();
echo 'What is their similarity score? ' . part_two($rows) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
