<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$c = new Calculator();

// Part 1:
$sequence = $inputs[0];
foreach (array_slice($inputs, 1) as $input) {
    $sequence = $c->add($sequence, $input);
}

//echo $sequence . PHP_EOL . PHP_EOL;

echo 'What is the magnitude of the final sum?' . PHP_EOL;
echo $c->magnitude($sequence) . PHP_EOL;

echo PHP_EOL;

// Part 2
$max_magnitude = $c->getMaxMagnitude($inputs);
echo 'What is the largest magnitude of any sum of two different snailfish numbers from the homework assignment?' . PHP_EOL;
echo $max_magnitude . PHP_EOL;

final class Calculator
{
    public function add(string $one, string $another): string
    {
        $sequence = sprintf('[%s,%s]', $one, $another);
//        echo "after addition\t${sequence}\n";
        return $this->reduce($sequence);
    }

    public function reduce(string $sequence): string
    {
        $exploded = $this->explode($sequence);
        if ($sequence !== $exploded) {
//            echo "after explode\t${exploded}\n";
            return $this->reduce($exploded);
        }

        $split = $this->split($sequence);
        if ($sequence !== $split) {
//            echo "after split\t${split}\n";
            return $this->reduce($split);
        }

        return $sequence;
    }

    public function explode(string $sequence): string
    {
        $tokens = preg_split('%(\[|\]|,)%', $sequence, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $open = 0; $prev_regular_value = null;
        foreach ($tokens as $index => $token) {
            if ($token === ']') {
                $open--;
            }

            if (is_numeric($token)) { // keep track of left
                $prev_regular_value = $index;
            }

            if ($token === '[') {
                $open++;
                if ($open > 4 && is_numeric($tokens[$index + 1]) && $tokens[$index + 2] === ',' && is_numeric($tokens[$index + 3])) {
                    // find next regular value:
                    $next_regular_value = null;
                    for ($pos = $index + 5; $pos < count($tokens); $pos++) {
                        if (is_numeric($tokens[$pos])) {
                            $next_regular_value = $pos;
                            break;
                        }
                    }

                    if ($prev_regular_value) {
                        $tokens[$prev_regular_value] += $tokens[$index + 1];
                    }

                    if ($next_regular_value) {
                        $tokens[$next_regular_value] += $tokens[$index + 3];
                    }

                    return join('', array_slice($tokens, 0, $index)) .
                        '0' .
                        join('', array_slice($tokens, $index + 5));
                }
            }
        }

        return $sequence;
    }

    public function split(string $sequence): string
    {
        // maybe this can be done in a single regex
        $parts = preg_split('%(?P<to_split>\d{2,})%', $sequence, 2,  PREG_SPLIT_DELIM_CAPTURE);

        return count($parts) === 3
            ? $parts[0] . '[' . floor($parts[1] / 2) . ',' . ceil($parts[1] / 2) .']' . $parts[2]
            : $sequence;
    }

    public function magnitude(string $sequence): int
    {
        $replacement = $sequence;
        do {
            $replacement = preg_replace_callback('%(?P<pair>\[(?P<left>\d+),(?P<right>\d+)\])%si', function(array $matches): int {
                return 3 * $matches['left'] + 2 * $matches['right'];
            }, $replacement);
        } while (!is_numeric($replacement));

        return intval($replacement);
    }

    public function getMaxMagnitude(array $sequences): int
    {
        $max = 0;
        for ($i = 0; $i < count($sequences); $i++) {
            for ($j = 0; $j < count($sequences); $j++) {
                if ($i === $j) continue;

                $max = max(
                    $max,
                    $this->magnitude($this->add($sequences[$i], $sequences[$j]))
                );
            }
        }

        return $max;
    }
}
