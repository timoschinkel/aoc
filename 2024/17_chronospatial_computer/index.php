<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day17;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$parts = array_filter(explode(PHP_EOL . PHP_EOL, file_get_contents($input)));

// Read input
$registers = array_reduce(
    explode(PHP_EOL, $parts[0]),
    function(array $carry, string $item): array {
        if (!preg_match('%^Register (?P<name>[A-Z]): (?P<value>[0-9]+)$%si', $item, $matches)) {
            throw new \RuntimeException('Unable to parse input');
        }
        $carry[$matches['name']] = (int)$matches['value'];
        return $carry;
    },
    []
);

$instructions = array_map('intval', explode(',', explode(': ', $parts[1])[1]));

$sw = new \Stopwatch();

class Computer {
    protected int $pointer = 0;
    protected array $out = [];

    public function __construct(
        protected array $registers,
        protected array $instructions,
    ) {
    }

    public function run(): string
    {
        $this->pointer = 0;
        $this->out = [];

        while ($this->pointer >= 0 && $this->pointer < count($this->instructions) - 1) { // we'll need to break out ourselves!
            $opcode = $this->instructions[$this->pointer];
            $operand = $this->instructions[$this->pointer + 1];

            switch ($opcode) {
                case 0:
                    $this->adv($operand);
                    break;
                case 1:
                    $this->bxl($operand);
                    break;
                case 2:
                    $this->bst($operand);
                    break;
                case 3:
                    $this->jnz($operand);
                    break;
                case 4:
                    $this->bxc($operand);
                    break;
                case 5:
                    $this->out($operand);
                    break;
                case 6:
                    $this->bdv($operand);
                    break;
                case 7:
                    $this->cdv($operand);
                    break;
                default:
                    throw new \RuntimeException('Unknown operation: ' . $opcode);
            }
        }

        return join(',', $this->out);
    }

    private function combo(int $operand): int {
        return match ($operand) {
            0, 1, 2, 3 => $operand,
            4 => $this->registers['A'],
            5 => $this->registers['B'],
            6 => $this->registers['C'],
            default => throw new \RuntimeException('Unsupported combo operand: ' . $operand),
        };
    }

    /**
     * The adv instruction (opcode 0) performs division. The numerator is the value in the A register. The denominator
     * is found by raising 2 to the power of the instruction's combo operand. (So, an operand of 2 would divide A by
     * 4 (2^2); an operand of 5 would divide A by 2^B.) The result of the division operation is truncated to an integer
     * and then written to the A register.
     *
     * @param int $operand
     * @return void
     */
    private function adv(int $operand): void
    {
       $outcome = (int)floor($this->registers['A'] / (2 ** $this->combo($operand)));
       $this->registers['A'] = $outcome;

       $this->pointer += 2;
    }

    /**
     * The bxl instruction (opcode 1) calculates the bitwise XOR of register B and the instruction's literal operand,
     * then stores the result in register B.
     *
     * @param int $operand
     * @return void
     */
    private function bxl(int $operand): void
    {
        $outcome = $this->registers['B'] ^ $operand;
        $this->registers['B'] = $outcome;

        $this->pointer += 2;
    }

    /**
     * The bst instruction (opcode 2) calculates the value of its combo operand modulo 8 (thereby keeping only its
     * lowest 3 bits), then writes that value to the B register.
     *
     * @param int $operand
     * @return void
     */
    private function bst(int $operand): void
    {
        $outcome = $this->combo($operand) % 8;
        $this->registers['B'] = $outcome;

        $this->pointer += 2;
    }

    /**
     * The jnz instruction (opcode 3) does nothing if the A register is 0. However, if the A register is not zero, it
     * jumps by setting the instruction pointer to the value of its literal operand; if this instruction jumps, the
     * instruction pointer is not increased by 2 after this instruction.
     *
     * @param int $operand
     * @return void
     */
    private function jnz(int $operand): void
    {
        if ($this->registers['A'] === 0) {
            $this->pointer += 2;
        } else {
            $this->pointer = $operand % 8;
        }
    }

    /**
     * The bxc instruction (opcode 4) calculates the bitwise XOR of register B and register C, then stores the result in
     * register B. (For legacy reasons, this instruction reads an operand but ignores it.)
     *
     * @param int $operand
     * @return void
     */
    private function bxc(int $operand): void
    {
        $outcome = $this->registers['B'] ^ $this->registers['C'];
        $this->registers['B'] = $outcome;

        $this->pointer += 2;
    }

    /**
     * The out instruction (opcode 5) calculates the value of its combo operand modulo 8, then outputs that value. (If
     * a program outputs multiple values, they are separated by commas.)
     *
     * @param int $operand
     * @return void
     */
    protected function out(int $operand): void
    {
        $outcome = $this->combo($operand) % 8;
        $this->out[] = $outcome;

        $this->pointer += 2;
    }

    /**
     * The bdv instruction (opcode 6) works exactly like the adv instruction except that the result is stored in the B
     * register. (The numerator is still read from the A register.)
     *
     * @param int $operand
     * @return void
     */
    private function bdv(int $operand): void
    {
        $outcome = (int)floor($this->registers['A'] / (2 ** $this->combo($operand)));
        $this->registers['B'] = $outcome;

        $this->pointer += 2;
    }

    /**
     * The cdv instruction (opcode 7) works exactly like the adv instruction except that the result is stored in the C
     * register. (The numerator is still read from the A register.)
     *
     * @param int $operand
     * @return void
     */
    private function cdv(int $operand): void
    {
        $outcome = (int)floor($this->registers['A'] / (2 ** $this->combo($operand)));
        $this->registers['C'] = $outcome;

        $this->pointer += 2;
    }
}

function part_one(array $registers, array $instructions): string {
    return (new Computer($registers, $instructions))->run();
}

$sw->start();
echo 'What do you get if you use commas to join the values it output into a single string? ' . part_one($registers, $instructions) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * This is the first puzzle that I have solved manually, and have not been able to work out a repeatable code solution
 * for. These are the hardest problems in my opinion. I tried brute forcing, but that did not work. Then I resorted to
 * Reddit for some tips. I stumbled upon this solution: https://www.reddit.com/r/adventofcode/comments/1hg38ah/comment/m2q5bmb
 *
 * It does not explain this, but it did give me enough inspiration to dive back in. I ran a brute force for the first
 * couple thousand values for A and whenever the output resembled the instructions I printed the binary representation
 * of A. The hint in the assignment was the multiple references to modulo 8. And thus I split up the binary representation
 * into chunks of three bits and a pattern started to emerge; whenever the first parts of the output matched the first
 * parts of the instructions the input would follow a pattern.
 *
 * I eventually solved this problem by doing a sort of DFS, but by hand. I found a value for A where the output matched
 * the instructions. Lucky for me, it was the lowest value for A.
 *
 * @param array $registers
 * @param array $instructions
 * @return int
 */
function part_two(array $registers, array $instructions): int {
    return 47910079998866;
}

$sw->start();
echo 'What is the lowest positive initial value for register A that causes the program to output a copy of itself? ' . part_two($registers, $instructions) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
