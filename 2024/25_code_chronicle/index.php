<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day25;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$schematics = array_filter(explode(PHP_EOL . PHP_EOL, file_get_contents($input)));

// Read input

$sw = new \Stopwatch();

/**
 * The most challenging to me this puzzle was parsing the input. Once the input is parsed it is a matter of checking 
 * every key and lock combination by comparing columns. If the sum of the columns exceeds 5 the key and lock do not
 * fit.
 * 
 * @param array<string> $schematics
 * @return int
 */
function part_one(array $schematics): int {
    $keys = [];
    $locks = [];

    foreach ($schematics as $schematic) {
        $rows = explode(PHP_EOL, trim($schematic));
        if ($rows[0][0] === '#') { // reading a lock
            $lock = [];
            for ($c = 0; $c < strlen($rows[0]); $c++) {
                for ($r = 1; $r < count($rows); $r++) {
                    if ($rows[$r][$c] === '.') {
                        $lock[] = $r - 1;
                        break;
                    }
                }
            }
            $locks[] = $lock;
        } else { // reading a key
            $key = [];
            for ($c = 0; $c < strlen($rows[0]); $c++) {
                for ($r = count($rows) - 1; $r >= 0; $r--) {
                    if ($rows[$r][$c] === '.') {
                        $key[] = count($rows) - 2 - $r;
                        break;
                    }
                }
            }
            $keys[] = $key;
        }
    }

    // echo 'Locks: ' . PHP_EOL . join(PHP_EOL, array_map(fn(array $lock): string => join(',', $lock), $locks)) . PHP_EOL . PHP_EOL;
    // echo 'Keys: ' . PHP_EOL . join(PHP_EOL, array_map(fn(array $key): string => join(',', $key), $keys)) . PHP_EOL . PHP_EOL;

    $fits = 0;
    foreach ($keys as $key) {
        foreach ($locks as $lock) {
            // when it fits it fits
            if (does_it_fit($key, $lock)) {
                $fits++;
            }
        }
    }

    return $fits;
}

function does_it_fit(array $key, array $lock): bool 
{
    for ($c = 0; $c < count($key); $c++) {
        if ($lock[$c] + $key[$c] > 5) {
            return false;
        }
    }

    return true;
}

$sw->start();
echo 'How many unique lock/key pairs fit together without overlapping in any column? ' . part_one($schematics) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

