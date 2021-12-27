<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

/*
 * Ideas
 * - brute force
 * - inspect the inspections per digit; is there a smart pattern?
 */

//  93499629698999
//  11164118121471

// Part 0
//$alu = ALU::create($inputs);
//
//foreach (range(1, 9) as $w) {
//    $vars = $alu->calculate([(string)$w]);
//    echo '$w = ' . $w . "\t" . json_encode($vars) . PHP_EOL;
//}
//
//die;

// first 6 digits
// z = (((((((((((w0 + 14) * 26) + w1 + 8) * 26) + w2 + 5) * 26) + w4 + 4) * 26) + w5 + 10) / 26) * 26) + w6 + 13
// or
// z = (((((((((((w0 + 14) * 26) + w1 + 8) * 26) + w2 + 5) * 26) + w4 + 4) * 26) + w5 + 10) / 26) * 1)

//foreach (range(1, 9) as $w0) {
//    foreach (range(1, 9) as $w1) {
//        foreach (range(1, 9) as $w2) {
//            foreach (range(1, 9) as $w3) {
//                foreach (range(1, 9) as $w4) {
//                    foreach (range(4, 4) as $w5) {
////                        $z = (((((((($w0 + 14) * 26) + $w1 + 8) * 26) + $w2 + 5) * 26) + $w4 + 4) * 26) + $w5 + 10;
//                        $x = (((((((((($w0 + 14) * 26) + $w1 + 8) * 26) + $w2 + 5) * 26) + $w4 + 4) * 26) + $w5 + 10) % 26) - 13;
//                        echo json_encode([$w0, $w1, $w2, $w3, $w4, $w5]) . "\t" . array_sum([$w0, $w1, $w2, $w3, $w4, $w5]) . "\t" . $x . PHP_EOL;
//                    }
//                }
//            }
//        }
//    }
//}
//die;


$z = [1, 1, 1, 26, 1, 26, 1, 26, 1, 1, 26, 26, 26, 26];
$x = [13, 12, 11, 0, 15, -13, 10, -9, 11, 13, -14, 3, 2, 14];
$y = [14, 8, 5, 4, 10, 13, 16, 5, 6, 13, 6, 7, 13, 3];


/*
 * The implementation of this assignment is heavily influenced - if not more - on a very detailed explanation by a
 * Reddit user called `u/dynker`: https://www.reddit.com/r/adventofcode/comments/roj2uk/decompiling_day_24/
 *
 * I will not repeat the entire explanation, but the tl/dr; 9^14 is way too much to brute force. As such a more clever
 * solution must be possible. This solution comes from the input. There's a pattern visible for the different digits
 * where x is being incremented, y is being incremented and z is either being divided by 26 or moduloed by 26.
 *
 * I have tried to build a solution that would work with any *valid* input.
 */


// Part 1
$alu = new ALU($inputs);
$max = $alu->getMaxSerialNumber();

echo 'What is the largest model number accepted by MONAD?' . PHP_EOL;
echo $max . PHP_EOL;

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
                echo 'Push ' . $this->y[$digit] . ' (' . $digit . ')' . PHP_EOL;
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
}
