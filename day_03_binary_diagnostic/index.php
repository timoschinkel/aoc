<?php

declare(strict_types=1);

$numbers = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1:

$gamma = '';
$epsilon = '';

for($position = 0; $position < strlen($numbers[0]); $position++) {
    $ones = 0;
    $zeros = 0;

    foreach ($numbers as $number) {
        if ($number[$position] === '1') {
            $ones++;
        } else {
            $zeros++;
        }
    }

    if ($ones > $zeros) {
        $gamma .= '1';
        $epsilon .= '0';
    } else {
        $gamma .= '0';
        $epsilon .= '1';
    }
}

echo 'What is the power consumption of the submarine?' . PHP_EOL;
echo (bindec($gamma) * bindec($epsilon)) . PHP_EOL;
