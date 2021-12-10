<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1:
$parser = new StackParser();
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
     * @return void
     * @throws ParseException
     */
    public function parse(string $input): void
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
    }
}
