<?php

declare(strict_types=1);

$input = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1

$draws = array_map('intval', explode(',', trim($input[0])));
/** @var Board[] $boards */
$boards = [];

for ($row = 2; $row < count($input); $row += 6) {
    $boards[] = Board::create(array_slice($input, $row, 5));
}

foreach ($draws as $draw) {
    $boards = array_map(fn(Board $board): Board => $board->withDraw($draw), $boards);

    foreach ($boards as $board) {
        if ($board->isWinner()) {
            echo "What will your final score be if you choose that board?" . PHP_EOL;
            echo ($board->getScore() * $draw) . PHP_EOL;
            break 2;
        }
    }
}

// Part 2

/** @var Board[] $contenders */
$contenders = [];
/** @var Board[] $winners */
$winners = [];

for ($row = 2; $row < count($input); $row += 6) {
    $contenders[] = Board::create(array_slice($input, $row, 5));
}

foreach ($draws as $draw) {
    $contenders = array_map(fn(Board $board): Board => $board->withDraw($draw), $contenders);

    foreach (array_keys($contenders) as $index) {
        $board = $contenders[$index];

        if ($board->isWinner()) {
            // move board to `winners` and remove from `contenders`
            $winners[] = $board;
            unset($contenders[$index]);

            if (count($contenders) === 0) {
                $lastWinner = end($winners);

                echo "Once it wins, what would its final score be?" . PHP_EOL;
                echo ($lastWinner->getScore() * $draw) . PHP_EOL;
            }
        }
    }
}

final class Board
{
    private const DIMENSION = 5;

    /** @var array<int> */
    private array $numbers = [];

    /** @var array<int> */
    private array $draws = [];

    public function withRow(string $row): self
    {
        $numbers = array_map('intval', preg_split('%\s+%s', trim($row)));

        $clone = clone $this;
        $clone->numbers = array_merge($clone->numbers, $numbers);

        return $clone;
    }

    public static function create(array $rows): self
    {
        $board = new self();
        foreach ($rows as $row) {
            $board = $board->withRow($row);
        }

        return $board;
    }

    public function withDraw(int $number): self
    {
        $clone = clone $this;
        $clone->draws[] = $number;

        return $clone;
    }

    public function isWinner(): bool
    {
        // brute force :shrug:

        // rows
        for ($rowNumber = 0; $rowNumber < self::DIMENSION; $rowNumber++) {
            $row = $this->getRow($rowNumber);
            if (array_intersect($row, $this->draws) === $row) {
                return true;
            }
        }

        // columns
        for ($columnNumber = 0; $columnNumber < self::DIMENSION; $columnNumber++) {
            $column = $this->getColumn($columnNumber);
            if (array_intersect($column, $this->draws) === $column) {
                return true;
            }
        }

        return false;
    }

    private function getRow($rowNumber): array
    {
        return array_slice($this->numbers, $rowNumber * self::DIMENSION, self::DIMENSION);
    }

    private function getColumn($columnNumber): array
    {
        $column = [];
        for ($row = 0; $row < self::DIMENSION; $row++) {
            $column[] = $this->numbers[$row * self::DIMENSION + $columnNumber];
        }
        return $column;
    }

    public function getScore(): int
    {
        return array_sum(
            array_diff($this->numbers, $this->draws)
        );
    }

    public function __toString(): string
    {
        $rows = [];
        for($rowNumber = 0; $rowNumber < self::DIMENSION; $rowNumber++) {
            $row = $this->getRow($rowNumber);
            $rows[] = join(' ', array_map(
                    fn(int $item): string =>
                    str_pad(in_array($item, $this->draws) ? "[${item}]" : " ${item} ", 4, ' ', STR_PAD_LEFT),
                    $row)
            );
        }

        return PHP_EOL . join(PHP_EOL, $rows) . PHP_EOL;
    }
}
