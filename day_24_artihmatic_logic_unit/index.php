<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

/*
 * The implementation of this assignment is heavily influenced - if not more - on a very detailed explanation by a
 * Reddit user called `u/dynker`: https://www.reddit.com/r/adventofcode/comments/roj2uk/decompiling_day_24/
 *
 * I will not repeat the entire explanation, but the tl/dr; 9^14 is way too much to brute force. As such a more clever
 * solution must be possible. This solution comes from the input. There's a pattern visible for the different digits
 * where x is being incremented, y is being incremented and z is either being divided by 26 or moduloed by 26.
 *
 * Another very helpful comment was from `u/mapleoctopus`: https://www.reddit.com/r/adventofcode/comments/rnejv5/comment/hpsm1gk/?utm_source=reddit&utm_medium=web2x&context=3
 *
 * I have tried to build a solution that would work with any *valid* input.
 */


// Part 1
$alu = new ALU($inputs);
$max = $alu->getMaxSerialNumber();

echo 'What is the largest model number accepted by MONAD?' . PHP_EOL;
echo $max . PHP_EOL;

echo PHP_EOL;

// Part 2
$min = $alu->getMinSerialNumber();

echo 'What is the smallest model number accepted by MONAD?' . PHP_EOL;
echo $min . PHP_EOL;

final class ALU
{
    private array $instructions;

    private array $z = [];
    private array $x = [];
    private array $y = [];

    public function __construct(array $instructions)
    {
        $this->instructions = $instructions;
        $this->compile();
    }

    private function compile(): void
    {
        $z = [];
        $x = [];
        $y = [];

        foreach ($this->instructions as $instruction) {
            if ($instruction === 'div z 1') {
                $z[] = 1;
            } elseif ($instruction === 'div z 26') {
                $z[] = 26;
            }

            if (preg_match('%^add x (?P<x>-?\d+)%si', $instruction, $matches)) {
                $x[] = (int)$matches['x'];
            }

            if (preg_match('%^add y (?P<y>-?\d+)%si', $instruction, $matches) && (int)$matches['y'] !== 1 && (int)$matches['y'] !== 25) {
                $y[] = (int)$matches['y'];
            }
        }

        $this->z = $z;
        $this->x = $x;
        $this->y = $y;
    }

    public function getMaxSerialNumber(): string
    {
        $serial_number = array_fill(0, 13, 0);

        $z_stack = [];
        foreach (range(0, 13) as $digit) {
            $z = $this->z[$digit];
            $x = $this->x[$digit];

            if ($z === 1) {
                array_push($z_stack, [$this->y[$digit], $digit]);
            } else {                // last_z_digit == current_z_digit + difference
                [$y, $prev_digit] = array_pop($z_stack);
                $difference = intval($x + $y);
                if ($difference < 0) {
                    $serial_number[$digit] = 9 + $difference;
                    $serial_number[$prev_digit] = 9;
                } elseif ($difference > 0) {
                    $serial_number[$digit] = 9;
                    $serial_number[$prev_digit] = 9 - $difference;
                } else {
                    $serial_number[$digit] = 9;
                    $serial_number[$prev_digit] = 9;
                }
            }
        }

        return join($serial_number);
    }

    public function getMinSerialNumber(): string
    {
        $serial_number = array_fill(0, 13, 0);

        $z_stack = [];
        foreach (range(0, 13) as $digit) {
            $z = $this->z[$digit];
            $x = $this->x[$digit];

            if ($z === 1) {
                array_push($z_stack, [$this->y[$digit], $digit]);
            } else {                // last_z_digit == current_z_digit + difference
                [$y, $prev_digit] = array_pop($z_stack);
                $difference = intval($x + $y);
                if ($difference < 0) {
                    $serial_number[$digit] = 1;
                    $serial_number[$prev_digit] = 1 - $difference;
                } elseif ($difference > 0) {
                    $serial_number[$digit] = 1 + $difference;
                    $serial_number[$prev_digit] = 1;
                } else {
                    $serial_number[$digit] = 1;
                    $serial_number[$prev_digit] = 1;
                }
            }
        }

        return join($serial_number);
    }
}
