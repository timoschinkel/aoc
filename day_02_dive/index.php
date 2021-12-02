<?php

declare(strict_types=1);

$commands = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$position = new Position();
foreach ($commands as $command) {
    $position = $position->withCommand(Command::create($command));
}

echo 'What do you get if you multiply your final horizontal position by your final depth?' . PHP_EOL;
echo $position->getDepth() * $position->getHorizontal() . PHP_EOL;

final class Position
{
    private int $horizontal = 0;
    private int $depth = 0;

    public function withCommand(Command $command): self
    {
        $clone = clone $this;

        switch($command->getDirection()) {
            case 'forward':
                $clone->horizontal += $command->getUnits();
                break;
            case 'down':
                $clone->depth += $command->getUnits();
                break;
            case 'up':
                $clone->depth -= $command->getUnits();
                break;
            default:
                throw new RuntimeException("Unknown direction {$command->getDirection()} found");
        }

        return $clone;
    }

    public function getHorizontal(): int
    {
        return $this->horizontal;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}

// Part 1
$position = new AimedPosition();
foreach ($commands as $command) {
    $position = $position->withCommand(Command::create($command));
}

echo 'What do you get if you multiply your final horizontal position by your final depth?' . PHP_EOL;
echo $position->getDepth() * $position->getHorizontal() . PHP_EOL;

final class AimedPosition
{
    private int $horizontal = 0;
    private int $depth = 0;
    private int $aim = 0;

    public function withCommand(Command $command): self
    {
        $clone = clone $this;

        switch($command->getDirection()) {
            case 'forward':
                $clone->horizontal += $command->getUnits();
                $clone->depth += ($clone->aim * $command->getUnits());
                break;
            case 'down':
                $clone->aim += $command->getUnits();
                break;
            case 'up':
                $clone->aim -= $command->getUnits();
                break;
            default:
                throw new RuntimeException("Unknown direction {$command->getDirection()} found");
        }

        return $clone;
    }

    public function getHorizontal(): int
    {
        return $this->horizontal;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }
}

final class Command
{
    private string $direction;
    private int $units;

    public function __construct(string $direction, int $units)
    {
        $this->direction = $direction;
        $this->units = $units;
    }

    public static function create(string $command): Command
    {
        [$direction, $units] = explode(' ', $command);

        return new Command($direction, intval($units));
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getUnits(): int
    {
        return $this->units;
    }
}
