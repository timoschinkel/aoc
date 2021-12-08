<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));
$displays = array_map(fn(string $input): Display => Display::create($input), $inputs);

// Part 1
echo 'In the output values, how many times do digits 1, 4, 7, or 8 appear?' . PHP_EOL;
echo array_sum(array_map(fn(Display $display): int => $display->countOnesFoursSevensAndEights(), $displays)) . PHP_EOL;

// Part 2
$total = 0;
foreach ($displays as $display) {
    $value = $display->getValue();
    $total += $value;
}

echo 'What do you get if you add up all of the output values?' . PHP_EOL;
echo $total . PHP_EOL;

//   aaaa
//  b    c
//  b    c
//   dddd
//  e    f
//  e    f
//   gggg

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

    public function getValue(): int
    {
        $wiring = $this->determineWiring();

        // Let's utilize PHP's type juggling!
        $numbers = join('', array_map(
           fn(string $digit): int => $this->translate($digit, $wiring),
           $this->digits
        ));

        return (int)$numbers;
    }

    private function translate(string $digit, array $wiring): int
    {
        switch(strlen($digit)) {
            case 2:
                return 1;
            case 3:
                return 7;
            case 4:
                return 4;
            case 5: // 2, 3, 5
                if (strpos($digit, $wiring['f']) === false) { // 2 does not have `f`
                    return 2;
                } elseif (strpos($digit, $wiring['c']) === false) { // 5 does not have `c`
                    return 5;
                } else { // must be 3 then
                    return 3;
                }
            case 6: // 0, 6, 9
                if (strpos($digit, $wiring['d']) === false) { // 0 does not have `d`
                    return 0;
                } elseif (strpos($digit, $wiring['c']) === false) { // 6 does not have `c`
                    return 6;
                } else { // must be 9 then
                    return 9;
                }
            case 7:
                return 8;
        }

        throw new RuntimeException('We should never get here!');
    }

    private function determineWiring(): array
    {
        $possibilities = [
            'a' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'b' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'c' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'd' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'e' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'f' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
            'g' => ['a', 'b', 'c' , 'd' , 'e' , 'f', 'g'],
        ];

        // We can extract a number of patterns out of the box
        $one = str_split($this->getPatternWithLength(2));
        $seven = str_split($this->getPatternWithLength(3));
        $four = str_split($this->getPatternWithLength(4));
        $eight = str_split($this->getPatternWithLength(7));

        // Filter out possibilities for `c` and `f` based on 1
        $possibilities = $this->setPossibilities($possibilities, 'c', $one);
        $possibilities = $this->setPossibilities($possibilities, 'f', $one);

        // Determine wiring for `a` by calculating the diff between 7 with 1
        $possibilities = $this->setPossibilities($possibilities, 'a', array_diff($seven, $one));

        // Use the known pattern for 1 to find the pattern representing 6
        $six = array_values(array_filter(
            $zero_six_or_nine = array_map('str_split', $this->getPatternsWithLength(6)), // 0, 6, 9
            fn(array $pattern): bool => array_intersect($one, $pattern) !== $one
        ))[0];

        // Determine wiring for `f` and `c` by diffing/intersecting 1 and 6
        $possibilities = $this->setPossibilities($possibilities, 'f', array_intersect($one, $six));
        $possibilities = $this->setPossibilities($possibilities, 'c', array_diff($one, $six));

        // Using 4 and 6 filter out 0 and 9:
        $zero_or_nine = array_values(array_filter($zero_six_or_nine, fn(array $pattern): bool => array_intersect($six, $pattern) !== $six));
        $nine = array_values(array_filter($zero_or_nine, fn(array $pattern): bool => array_intersect($four, $pattern) === $four))[0];
        $zero = array_values(array_filter($zero_or_nine, fn(array $pattern): bool => $pattern !== $nine))[0];

        // Determine wiring for `d` by diffing 8 and 0
        $possibilities = $this->setPossibilities($possibilities, 'd', array_diff($eight, $zero));

        // Determine wiring for `e` by diffing 8 and 9
        $possibilities = $this->setPossibilities($possibilities, 'e', array_diff($eight, $nine));

        // Determine wiring for `g` by diffing the combination of 4 and 7 against 9
        $four_and_seven = array_merge($four, $seven);
        $possibilities = $this->setPossibilities($possibilities, 'g', array_diff($nine, $four_and_seven));

        // Wiring is complete!
        return array_map(fn(array $poss): string => reset($poss), $possibilities);
    }

    private function setPossibilities(array $possibilities, string $segment, array $possibilities_for_segment): array
    {
        $possibilities[$segment] = $possibilities_for_segment;
        foreach (array_keys($possibilities) as $s) {
            if ($s === $segment) {
                $possibilities[$s] = $possibilities_for_segment;
            } else {
                // we can now filter out $possibilities_for_segment from all other segments
                $possibilities[$s] = array_diff($possibilities[$s], $possibilities_for_segment);
            }
        }

        return $possibilities;
    }

    private function getPatternWithLength(int $length): string
    {
        foreach ($this->signal_patterns as $pattern) {
            if (strlen($pattern) === $length) {
                return $pattern;
            }
        }

        return '';
    }

    private function getPatternsWithLength(int $length): array
    {
        return array_filter($this->signal_patterns, fn(string $pattern): bool => strlen($pattern) === $length);
    }

    /**
     * @return string[]
     */
    public function getDigits(): array
    {
        return $this->digits;
    }

    public static function create(string $input): self
    {
        $parts = explode(' ', $input);
        return new self(array_slice($parts, 0, 10), array_slice($parts, 11, 4));
    }
}

/*

- Create story for possible cause of TCRCD-37 - Duplicate key in VAN_SIMCUSTOMER (Timo)
- Educate on OAuth (Timo & Hans)
- Follow up on TCRCD-37 (Timo)
- Look at account issues, try to solve and create stories to remedy the cause of the issues (Team)



-> Update `CUSTOMERID` instead of updating email address

 */
