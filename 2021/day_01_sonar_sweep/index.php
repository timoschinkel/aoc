<?php

declare(strict_types=1);

$measurements = array_map('intval', explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt'))));
file_put_contents(__DIR__ . '/output_part_1.txt', '');
file_put_contents(__DIR__ . '/output_part_2.txt', '');

// Part 1
$previous = null;
$numOfIncreases = 0;

foreach ($measurements as $depth) {
    if ($previous === null) {
        file_put_contents(__DIR__ . '/output_part_1.txt', sprintf("%d\t%s\n", $depth, '(N/A - no previous measurement)'), FILE_APPEND);
    } elseif ($depth > $previous) {
        $numOfIncreases++; // money shot!
        file_put_contents(__DIR__ . '/output_part_1.txt', sprintf("%d\t%s\n", $depth, '(increased)'), FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/output_part_1.txt', sprintf("%d\t%s\n", $depth, '(decreased)'), FILE_APPEND);
    }

    $previous = $depth;
}

echo "Number of times a depth measurement increases: " . $numOfIncreases . PHP_EOL;

// Part 2: Sliding window
$previous = null;
$numOfIncreases = 0;

for ($i = 0; $i < count($measurements) - 2; $i++) {
    $window = Window::create($measurements, $i);

    if ($previous === null) {
        file_put_contents(__DIR__ . '/output_part_2.txt', sprintf("%s\t%d\t%s\n", chr(65 + $i), $window->getValue(), '(N/A - no previous sum)'), FILE_APPEND);
    } elseif ($window->getValue() > $previous->getValue()) {
        $numOfIncreases++; // money shot!
        file_put_contents(__DIR__ . '/output_part_2.txt', sprintf("%s\t%d\t%s\n", chr(65 + $i), $window->getValue(), '(increased)'), FILE_APPEND);
    } elseif ($window->getValue() === $previous->getValue()) {
        file_put_contents(__DIR__ . '/output_part_2.txt', sprintf("%s\t%d\t%s\n", chr(65 + $i), $window->getValue(), '(no change)'), FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/output_part_2.txt', sprintf("%s\t%d\t%s\n", chr(65 + $i), $window->getValue(), '(decreased)'), FILE_APPEND);
    }

    $previous = $window;
}

echo "Number of sums larger than the previous sum: " . $numOfIncreases . PHP_EOL;

final class Window
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function create(array $measurements, int $start): self
    {
        return new self(array_sum(array_slice($measurements, $start, 3)));
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
