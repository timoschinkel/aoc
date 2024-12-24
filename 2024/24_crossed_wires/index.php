<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day24;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL . PHP_EOL, file_get_contents($input)));

// Read input
$values = [];
foreach (explode(PHP_EOL, trim($rows[0])) as $row) {
    $parts = explode(': ' , $row);
    $values[$parts[0]] = intval($parts[1]);
}

$gates = [];
foreach (explode(PHP_EOL, trim($rows[1])) as $row) {
    if (!preg_match('%^(?P<left>[^\s]+) (?P<operand>[^\s]+) (?P<right>[^\s]+) -> (?P<target>[^\s]+)$%', $row, $matches)) {
        throw new \RuntimeException('Unable to parse input: ' . $row);
    }
    $gates[] = ['left' => $matches['left'], 'operand' => $matches['operand'], 'right' => $matches['right'], 'target' => $matches['target']];
}

$sw = new \Stopwatch();

/**
 * The trick is to keep track of the values during an iteration, and replace them with the values after the iteration.
 * Making heavy use of the `bindec()` function of PHP.
 *
 * @param array<string, int> $values
 * @param array<array> $gates
 * @return int
 */
function part_one(array $values, array $gates): int {
    // Find all z-wires
    $z_wires = array_reduce($gates, fn(array $carry, array $gate) => $gate['target'][0] === 'z' ? array_merge($carry, [$gate['target'] => null]) : $carry, []);

    // we need to continue until all z-wires have a value
    while (array_diff_key($z_wires, $values)) {
        // Find all relevant gates based on the values we have:
        $relevant = array_filter($gates, fn(array $gate): bool => isset($values[$gate['left']]) && isset($values[$gate['right']]));

        $new_values = $values;
        foreach ($relevant as $gate) {
            $outcome = match ($gate['operand']) {
                'XOR' => $values[$gate['left']] ^ $values[$gate['right']],
                'OR' => $values[$gate['left']] | $values[$gate['right']],
                'AND' => $values[$gate['left']] & $values[$gate['right']],
                default => throw new \RuntimeException('Unknown gate: ' . $gate['operand']),
            };
            $new_values[$gate['target']] = $outcome;
            if ($gate['target'][0] === 'z') $z_wires[$gate['target']] = $outcome;
        }

        if ($values === $new_values) {
            // We have found an endless loop, return -1
            return -1;
        }

        $values = $new_values;
    }

    // Find all z-wires
    krsort($z_wires, SORT_NATURAL);

//    echo join('', $z_wires) . PHP_EOL;

    return bindec(join('', $z_wires));
}

$sw->start();
echo 'What decimal number does it output on the wires starting with z? ' . part_one($values, $gates) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * I don't have a fully coded solution for part 2, and I don't know if I every will create one.
 *
 * My approach was as follows: With the knowledge that we're dealing with a giant adding machine we can make a number of
 * conclusions. Every z-wire represents adding the corresponding x- and y-wire, keeping the carry in mind. That means
 * that every z-wire - apart from the most significant bit - requires the same operation; an XOR operation.
 *
 * That was step one; look at all the gates that output z-wires. In my input three gates outputting a z-wire did not
 * have an XOR operation. So those need to be switched. In order to find out what they need to be switched with I had a
 * look at the correct operation: zXX = (YY OR ZZ) XOR (xXX XOR yXX). For the three faulty z-wire gates I was quickly
 * able to locate the output that was needed, as the (xXX XOR yXX) gate was missing.
 *
 * The final wire was not as obvious to find. I resorted to some testing or every single digit of z. We know the width
 * of x (and thus y) by looking at the input. For every digit of x I perform the addition 2**i + 2**i. We know the
 * outcome, so if the outcome is incorrect, then we have an idea where to search.
 *
 * In my input 2**39 was giving an incorrect answer. That means that I needed to investigate the gates leading to z39
 * and leading to z40, due to the carry. Three levels deep I found an invalid operation (XOR instead of AND). Finding
 * the corresponding gate proved to be the correct one. Validating the result was easy enough via part one.
 *
 * @param array<string, int> $values
 * @param array<array> $gates
 * @return int
 */
function part_two(array $values, array $gates): string {
    /*
     * Can we perform the operation, and check which digits are invalid and work up from there?
     */
    $x_wires = array_filter($values, fn(string $wire) => $wire[0] === 'x', ARRAY_FILTER_USE_KEY);

    // Found z18-hmt, z27-bfq, and z31-hkh via the `input.txt`, to find the last port I iterate over the digits of z
    // and feeding the data into part_one(). If the outcome is not what we expect, then we need to investigate.

    /*

    $swapped = swap_output_wires($gates, ['z18' => 'hmt', 'z27' => 'bfq', 'z31' => 'hkh']);
    for ($i = 0; $i < count($x_wires); $i++) {
        $value = 2 ** $i;

        $outcome = part_one(values('x', count($x_wires), $value) + values('y', count($x_wires), $value), $swapped);
        if ($outcome !== $value + $value) {
            echo sprintf('z%02d', $i) . PHP_EOL;
            echo $value . ' + ' . $value . ': ' . $outcome . ' (' . ($value + $value) . ')' . PHP_EOL . PHP_EOL;
        }
    }


    // Validating the solution:
    $swapped = swap_output_wires($gates, ['z18' => 'hmt', 'z27' => 'bfq', 'z31' => 'hkh', 'fjp' => 'bng']);
    for ($i = 0; $i < count($x_wires); $i++) {
        $value = 2 ** $i;

        $outcome = part_one(values('x', count($x_wires), $value) + values('y', count($x_wires), $value), $swapped);
        if ($outcome !== $value + $value) {
            echo sprintf('z%02d', $i) . PHP_EOL;
            echo $value . ' + ' . $value . ': ' . $outcome . ' (' . ($value + $value) . ')' . PHP_EOL . PHP_EOL;
        }
    }

    */

    $swaps = ['z18', 'hmt', 'z27', 'bfq', 'z31', 'hkh', 'fjp', 'bng'];
    sort($swaps, SORT_NATURAL);

    return join(',', $swaps);
}

function swap_output_wires(array $gates, array $swaps): array
{
    $new_gates = [];
    foreach ($gates as $gate) {
        $new_gate = $gate;
        if (isset($swaps[$gate['target']])) {
            // swap with the value
            $new_gate['target'] = $swaps[$gate['target']];
        } elseif (in_array($gate['target'], $swaps)) {
            // swap with the key
            $new_gate['target'] = array_search($gate['target'], $swaps);
        }
        $new_gates[] = $new_gate;
    }

    return $new_gates;
}

function values(string $prefix, int $length, int $value): array {
    $str = str_pad(decbin($value), $length, '0', STR_PAD_LEFT);

    $reversed = strrev($str);
    $values = [];
    for ($i = 0; $i < strlen($reversed); $i++) {
        $values[sprintf('%s%02d', $prefix, $i)] = intval($reversed[$i]);
    }

    return $values;
}

$sw->start();
echo 'What do you get if you sort the names of the eight wires involved in a swap and then join those names with commas? ' . part_two($values, $gates) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
