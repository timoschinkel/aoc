<?php

declare(strict_types=1);

$contents = explode(PHP_EOL, file_get_contents(__DIR__ . '/input.txt'));
file_put_contents(__DIR__ . '/output.txt', '');

$previous = null;
$numOfIncreases = 0;

foreach ($contents as $depth) {
    $depth = intval(trim($depth));
    if ($previous === null) {
        file_put_contents(__DIR__ . '/output.txt', sprintf("%d\t%s\n", $depth, '(N/A - no previous measurement)'), FILE_APPEND);
    } elseif ($depth > $previous) {
        $numOfIncreases++; // money shot!
        file_put_contents(__DIR__ . '/output.txt', sprintf("%d\t%s\n", $depth, '(increased)'), FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/output.txt', sprintf("%d\t%s\n", $depth, '(decreased)'), FILE_APPEND);
    }

    $previous = $depth;
}

echo "Number of times a depth measurement increases: " . $numOfIncreases . PHP_EOL;
