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

// Part 2

$oxygen_generator_rating = '';

$working_copy = $numbers;
for($position = 0; $position < strlen($numbers[0]); $position++) {
    $ones = [];
    $zeros = [];

    foreach ($working_copy as $number) {
        if ($number[$position] === '1') {
            $ones[] = $number;
        } else {
            $zeros[] = $number;
        }
    }

    if (count($ones) >= count($zeros)) {
        $oxygen_generator_rating .= '1';
        $working_copy = $ones;
    } else {
        $oxygen_generator_rating .= '0';
        $working_copy = $zeros;
    }

    if (count($working_copy) === 1) {
        $oxygen_generator_rating = reset($working_copy);
        break;
    }
}

$co2_scrubber_rating = '';

$working_copy = $numbers;
for($position = 0; $position < strlen($numbers[0]); $position++) {
    $ones = [];
    $zeros = [];

    foreach ($working_copy as $number) {
        if ($number[$position] === '1') {
            $ones[] = $number;
        } else {
            $zeros[] = $number;
        }
    }

    if (count($ones) >= count($zeros)) {
        $co2_scrubber_rating .= '0';
        $working_copy = $zeros;
    } else {
        $co2_scrubber_rating .= '1';
        $working_copy = $ones;
    }

    if (count($working_copy) === 1) {
        $co2_scrubber_rating = reset($working_copy);
        break;
    }
}

echo 'What is the life support rating of the submarine?' . PHP_EOL;
echo (bindec($oxygen_generator_rating) * bindec($co2_scrubber_rating)) . PHP_EOL;
