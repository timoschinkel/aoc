<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$sea_floor = SeaFloor::create($inputs);
$steps = $sea_floor->findDeadlock();

echo 'What is the first step on which no sea cucumbers move?' . PHP_EOL;
echo $steps . PHP_EOL;


final class SeaFloor
{
    private const EAST = '>';
    private const SOUTH = 'v';

    private int $width;
    private int $height;
    private array $east;
    private array $south;

    public function __construct(int $width, int $height, array $east, array $south)
    {
        $this->width = $width;
        $this->height = $height;
        $this->east = $east;
        $this->south = $south;
    }

    public static function create(array $inputs): self
    {
        $height = count($inputs);
        $width = strlen($inputs[0]);

        $east = [];
        $south = [];

        for($row = 0; $row < count($inputs); $row++) {
            for($col = 0; $col < strlen($inputs[$row]); $col++) {
                if ($inputs[$row][$col] === self::EAST) {
                    $east[$row * $width + $col] = self::EAST;
                } elseif ($inputs[$row][$col] === self::SOUTH) {
                    $south[$row * $width + $col] = self::SOUTH;
                }
            }
        }

        return new self($width, $height, $east, $south);
    }

    public function findDeadlock(): int
    {
        $steps = 1;
        while ($this->move()) {
            $steps++;
            //echo 'Move ' . $steps . PHP_EOL;
        }

        return $steps;
    }

    public function move(): bool
    {
        $new_east = [];
        $new_south = [];

        // Move "EAST"
        foreach (array_keys($this->east) as $position) {
            $y = floor($position / $this->width);
            $x = $position % $this->width;
            $new_position = $y * $this->width + (($x + 1) % $this->width);
            if (!isset($this->east[$new_position]) && !isset($this->south[$new_position])) {
//                echo "EAST: Position ${position} moves to ${new_position} ({$x}, {$y})" . PHP_EOL;
                $new_east[$new_position] = self::EAST;
            } else { // no move
                $new_east[$position] = self::EAST;
            }
        }

        // Move "SOUTH"
        foreach (array_keys($this->south) as $position) {
            $new_position = ($position + $this->width) % ($this->width * $this->height);
            if (!isset($new_east[$new_position]) && !isset($this->south[$new_position])) {
//                echo "SOUTH: Position ${position} moves to ${new_position} - (${position} + {$this->width}) % ({$this->width} * {$this->height})" . PHP_EOL;
                $new_south[$new_position] = self::SOUTH;
            } else { // no move
                $new_south[$position] = self::SOUTH;
            }
        }

        $changed = $this->east !== $new_east || $this->south !== $new_south;

        $this->east = $new_east;
        $this->south = $new_south;

        return $changed;
    }

    public function __toString(): string
    {
        $string = '';
        for($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                $position = $row * $this->width + $col;
                $string .= $this->east[$position] ?? $this->south[$position] ?? '.';
            }
            $string .= PHP_EOL;
        }

        return $string;
    }
}
