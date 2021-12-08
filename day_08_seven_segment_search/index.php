<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$displays = array_map(fn(string $input): Display => Display::create($input), $inputs);

echo 'In the output values, how many times do digits 1, 4, 7, or 8 appear?' . PHP_EOL;
echo array_sum(array_map(fn(Display $display): int => $display->countOnesFoursSevensAndEights(), $displays)) . PHP_EOL;

final class Display
{
    /** @var string[] */
    private array $signal_patterns;

    /** @var string[] */
    private array $digits;

    public function __construct(array $signal_patterns, array $digits)
    {
        $this->signal_patterns = $signal_patterns;
        $this->digits = $digits;
    }

    public function countOnesFoursSevensAndEights(): int
    {
        return count(array_filter(
            $this->digits,
            fn(string $digit): bool => in_array(strlen($digit), [2, 3, 4, 7])
        ));
    }

    public static function create(string $input): self
    {
        $parts = explode(' ', $input);
        return new self(array_slice($parts, 0, 10), array_slice($parts, 11, 4));
    }
}
