<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$memory = join('', $rows);

$sw = new Stopwatch();

/*
 * The puzzle tells us only to worry about a certain pattern, so the regular expression is our weapon of choice here.
 * Two things to keep in mind; the puzzle input is multiple strings - I interpreted the puzzle by concatenating the
 * strings to a single string - and the requirement "where X and Y are each 1-3 digit numbers". The regular expression
 * is fairly simple, and after that it is simply a matter of adding the multiplications.
 */
function part_one(string $memory): int {
    preg_match_all('%mul\((?<left>\d{1,3}),(?<right>\d{1,3})\)%', $memory, $matches);

    $sum = 0;
    foreach ($matches['left'] as $key => $left) {
        $sum += $left * $matches['right'][$key];
    }

    return $sum;
}

$sw->start();
echo 'What do you get if you add up all of the results of the multiplications? ' . part_one($memory) . ' (' . $sw->ellapsedMS() . 'ms)' . PHP_EOL;

/*
 * My first instinct was to remove all the memory between `don't()` and `do()`, and run the result via the part_one()
 * logic. But that give too high of an answer. This is probably due to my logic, but I switched to a more straightforward
 * solution; find all matches for `do()`, `don't()` and `mul(X,Y)`, and iterate over those matches. Whenever we encounter
 * a `don't()` disable adding the multiplications, when we encounter a `do()` enable it.
 */
function part_two(string $memory): int {
    preg_match_all('%(don\'t\(\)|do\(\)|mul\((?P<left>\d{1,3}),(?P<right>\d{1,3})\))%', $memory, $matches);

    $sum = 0; $enabled = true;
    foreach ($matches[0] as $index => $match) {
        if ($match === 'don\'t()') {
            $enabled = false;
        } elseif ($match === 'do()') {
            $enabled = true;
        } elseif ($enabled) {
            $sum += $matches['left'][$index] * $matches['right'][$index];
        }
    }

    return $sum;
}

$sw->start();
echo 'What do you get if you add up all of the results of just the enabled multiplications? ' . part_two($memory) . ' (' . $sw->ellapsedMS() . 'ms)' . PHP_EOL;
