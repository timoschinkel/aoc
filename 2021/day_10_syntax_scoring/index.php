<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$parser = new StackParser();

// Part 1:
$score = 0;
foreach ($inputs as $input) {
    try {
        $parser->parse($input);
    } catch (ParseException $exception) {
        $score += $exception->getScore();
    }
}

echo 'What is the total syntax error score for those errors?' . PHP_EOL;
echo $score . PHP_EOL;

// Part 2:
$token_values = [
    ')' => 1,
    ']' => 2,
    '}' => 3,
    '>' => 4,
];

$scores = [];
foreach ($inputs as $input) {
    try {
        $missing_tokens = $parser->parse($input);

        $scores[] = array_reduce(
            $missing_tokens,
            fn(int $carry, $token) => ($carry * 5) + $token_values[$token],
            0
        );
    } catch (ParseException $_) {}
}

sort($scores);

echo 'What is the middle score?' . PHP_EOL;
echo $scores[floor(count($scores) / 2)] . PHP_EOL;

class ParseException extends Exception
{
    private string $expected;
    private string $found;
    private int $index;

    public function __construct(string $expected, string $found, int $index)
    {
        $this->expected = $expected;
        $this->found = $found;
        $this->index = $index;

        parent::__construct("Expected ${expected}, but found ${found} instead.");
    }

    public function getExpected(): string
    {
        return $this->expected;
    }

    public function getFound(): string
    {
        return $this->found;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getScore(): int
    {
        $scores = [
            ')' => 3,
            ']' => 57,
            '}' => 1197,
            '>' => 25137,
        ];

        return $scores[$this->found] ?? 0;
    }
}

final class StackParser
{
    private const TOKEN_PAIRS = [
        '(' => ')',
        '[' => ']',
        '{' => '}',
        '<' => '>',
    ];

    /**
     * @param string $input
     * @return string[]
     * @throws ParseException
     */
    public function parse(string $input): array
    {
        $tokens = str_split($input);

        $stack = [];
        foreach ($tokens as $index => $token) {
            if (in_array($token, array_keys(self::TOKEN_PAIRS))) {
                // open; add to stack
                $stack[] = $token;
            } elseif (in_array($token, array_values(self::TOKEN_PAIRS))) {
                // close
                $last_token = end($stack);
                $expected_token = self::TOKEN_PAIRS[$last_token];
                if ($token !== $expected_token) {
                    throw new ParseException($expected_token, $token, $index);
                }

                array_pop($stack);
            } else {
                throw new RuntimeException("Unexpected token '${token}'");
            }
        }

        return $this->getMissingTokens($stack);
    }

    private function getMissingTokens(array $stack): array
    {
        return array_map(fn(string $open): string => self::TOKEN_PAIRS[$open], array_reverse($stack));
    }
}
