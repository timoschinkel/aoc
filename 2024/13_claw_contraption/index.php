<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$blocks = array_filter(explode(PHP_EOL . PHP_EOL, file_get_contents($input)));

// Read input
readonly class Input {
    private function __construct(
        public int $a_x,
        public int $a_y,
        public int $b_x,
        public int $b_y,
        public int $prize_x,
        public int $prize_y,
    ) {
    }

    public function withPrize(int $prize_x, int $prize_y): self
    {
        return new self($this->a_x, $this->a_y, $this->b_x, $this->b_y, $prize_x, $prize_y);
    }

    public static function create(string $input): self
    {
        if (!preg_match('%Button A: X\+?(?P<ax>-?[0-9]+), Y\+?(?P<ay>-?[0-9]+)\sButton B: X\+?(?P<bx>-?[0-9]+), Y\+?(?P<by>-?[0-9]+)\sPrize: X=(?P<px>-?[0-9]+), Y=(?P<py>-?[0-9]+)%s', $input, $matches)) {
            throw new RuntimeException('Invalid input: ' . $input);
        }

        return new self(
            intval($matches['ax']), intval($matches['ay']),
            intval($matches['bx']), intval($matches['by']),
            intval($matches['px']), intval($matches['py']),
        );
    }
}

$inputs = array_map(fn(string $block): Input => Input::create($block), $blocks);

$sw = new Stopwatch();

/**
 * This has to be done using mathematics, but my mathematics are a bit rusty. For part 1 I opted for a smart brute-force
 * where I determine the maximum amount of possible values for A and B. Then I iterate over the possibilities until I
 * find a satisfiable answer.
 *
 * @param array<Input> $inputs
 * @return int
 */
function part_one(Input ...$inputs): int {
    $tokens = 0;

    foreach ($inputs as $input) {
        $tokens += find_prize($input);
    }

    return $tokens;
}

function find_prize(Input $input): int {
    $max_a = min(floor($input->prize_x / $input->a_x), floor($input->prize_y / $input->a_y));
    $max_b = min(floor($input->prize_x / $input->b_x), floor($input->prize_y / $input->b_y));
    for ($a = 0 ; $a <= $max_a; $a++) {
        for ($b = 0 ; $b <= $max_b; $b++) {
            if (($input->a_x * $a + $input->b_x * $b === $input->prize_x) && ($input->a_y * $a + $input->b_y * $b === $input->prize_y)) {
                return $a * 3 + $b;
            }
        }
    }

    // No solution found
    return 0;
}

$sw->start();
echo 'What is the fewest tokens you would have to spend to win all possible prizes? ' . part_one(...$inputs) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Of course my smart brute-force from part 1 is not nearly enough for this. This has to be solved mathematically. I
 * needed a lot of hints for this. The exact calculation is explained with `find_prize_mathematically()`.
 *
 * @param array<Input> $inputs
 * @return int
 */
function part_two(Input ...$inputs): int {
    $tokens = 0;

    foreach ($inputs as $input) {
        $tokens += find_prize_mathematically($input->withPrize($input->prize_x + 10000000000000, $input->prize_y + 10000000000000));
    }

    return $tokens;
}

function find_prize_mathematically(Input $input): int {
    /**
     * I need to give praise to https://github.com/hansdubois for giving me the hints needed to solve this
     * mathematically.
     *
     * We can represent the puzzle as two formulas:
     * prize_x = a_x * a_pressed + b_x * b_pressed
     * prize_y = a_y * a_pressed + b_y * b_pressed
     *
     * In these formulas we have two unknowns; a_pressed and b_pressed. We can reduce this a one unknown using
     * mathematics.
     *
     * First step is to multiply with b_y and b_x respectively
     * prize_x * b_y = a_x * a_pressed * b_y + b_x * b_pressed * b_y
     * prize_y * b_x = a_y * a_pressed * b_x + b_y * b_pressed * b_x
     *
     * Now the second part is the same (in multiplication the order is irrelevant):
     * prize_x * b_y = a_x * a_pressed * b_y + [b_x * b_pressed * b_y]
     * prize_y * b_x = a_y * a_pressed * b_x + [b_y * b_pressed * b_x]
     *
     * Therefor those can be removed:
     * prize_x * b_y = a_x * a_pressed * b_y
     * prize_y * b_x = a_y * a_pressed * b_x
     *
     * We have now eliminated the b_pressed. Next step is to combine this into a single formula. We do this by
     * subtracting the two formulas:
     * prize_x * b_y - prize_y * b_x = a_x * a_pressed * b_y - a_y * a_pressed * b_x
     *
     * The right side can be simplified:
     * prize_x * b_y - prize_y * b_x = (a_x * b_y - a_y * b_x) * a_pressed
     *
     * As we want to know a_pressed, we can now know how to calculate it:
     * (prize_x * b_y - prize_y * b_x) / (a_x * b_y - a_y * b_x) = a_pressed
     */

    // perform divide by zero check:
    if ($input->a_x * $input->b_y - $input->a_y * $input->b_x === 0) {
        return 0;
    }

    $a = ($input->prize_x * $input->b_y - $input->prize_y * $input->b_x) / ($input->a_x * $input->b_y - $input->a_y * $input->b_x);

    /**
     * Now that we know the number of times A is pressed, we can fill in this value into our original function:
     * prize_x = a_x * a_pressed + b_x * b_pressed
     *
     * We need to isolate b_pressed
     * prize_x - a_x * a_pressed = b_x * b_pressed
     * (prize_x - a_x * a_pressed) / b_x = b_pressed
     */

    // perform divide by zero check:
    if ($input->b_x === 0) {
        return 0;
    }

    $b = ($input->prize_x - $input->a_x * $a) / $input->b_x;

    // if a and b are whole numbers, then we have a solution:
    if (is_int($a) && is_int($b)) {
        return $a * 3 + $b;
    }

    return 0;
}

$sw->start();
echo 'What is the fewest tokens you would have to spend to win all possible prizes? ' . part_two(...$inputs) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
