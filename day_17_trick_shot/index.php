<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$trench = Trench::create($inputs[0]);
$highest_y = $trench->findHighestY();

echo 'What is the highest y position it reaches on this trajectory?' . PHP_EOL;
echo $highest_y . PHP_EOL;

final class Trench
{
    private int $xFrom;
    private int $xTo;
    private int $yFrom;
    private int $yTo;

    public function __construct(int $xFrom, int $xTo, int $yFrom, int $yTo)
    {
        $this->xFrom = $xFrom;
        $this->xTo = $xTo;
        $this->yFrom = $yFrom;
        $this->yTo = $yTo;
    }

    public static function create(string $input): self
    {
        preg_match('%x=(?P<x_from>[0-9-]+)..(?P<x_to>[0-9-]+), y=(?P<y_from>[0-9-]+)..(?P<y_to>[0-9-]+)%si', $input, $matches);

        // Tricky; The range on the y-axis are swapped for my solution.
        return new self((int)$matches['x_from'], (int)$matches['x_to'], (int)$matches['y_to'], (int)$matches['y_from']);
    }

    public function findHighestY(): int
    {
        // Brute force :shrug:
        // But we can limit the range. A little bit.

        $highestY = 0;
        $best = null;

        for ($vX = 0; $vX < $this->xTo; $vX++) {
            for ($vY = 0; $vY < $this->yTo * -1; $vY++) {
                $trajectory = $this->calculate($vX, $vY);
                if ($trajectory->isSuccess() && $trajectory->getHighestY() > $highestY) {
                    $best = $trajectory;
                    $highestY = $trajectory->getHighestY();
                }
            }
        }

        return $best !== null? $best->getHighestY() : 0;
    }

    private function calculate(int $vStartX, int $vStartY): Trajectory
    {
        $x = 0;
        $y = 0;
        $vx = $vStartX;
        $vy = $vStartY;
        $positions = [['x' => $x, 'y' => $y]];
        $maxY = $y;

        while ($x <= $this->xTo && $y >= $this->yTo) {
            // we use the fact that we know the puzzle input and that target is to the bottom right of our starting position

            // calculate new position
            $x += $vx;
            $y += $vy;

            // apply resistance / gravity
            if ($vx !== 0) {
                $vx = $vx >= 1 ? $vx - 1 : $vx + 1;
            }
            $vy -= 1;

            // update maxY value
            $maxY = max($maxY, $y);

            $positions[] = ['x' => $x, 'y' => $y];

            // add early exit
            if ($this->isInTarget($x, $y)) {
                return new Trajectory($vStartX, $vStartY, $maxY, true, $positions);
            }
        }

        return new Trajectory($vStartX, $vStartY, $maxY, false, $positions);
    }

    public function draw(Trajectory $trajectory): string
    {
        $width = array_reduce($trajectory->getPositions(), fn(int $width, array $position): int => max($width, $position['x']), $this->xTo + 1);
        $maxY = array_reduce($trajectory->getPositions(), fn(int $y, array $position): int => max($y, $position['y']), $this->yTo + 1);
        $minY = array_reduce($trajectory->getPositions(), fn(int $y, array $position): int => min($y, $position['y']), $this->yFrom);

        $grid = '';
        for($row = $maxY; $row >= $minY; $row--) {
            $r = '';
            for($col = 0; $col <= $width; $col++) {
                if ($row === 0 && $col === 0) {
                    $r .= 'S';
                }
                elseif (array_filter($trajectory->getPositions(), fn(array $position): bool => $position['x'] === $col && $position['y'] === $row)) {
                    $r .= '#';
                }
                elseif ($this->isInTarget($col, $row)) {
                    $r .= 'T';
                }
                else {
                    $r .= '.';
                }
            }
            $grid .= $r . PHP_EOL;
        }

        return $grid;
    }

    private function isInTarget(int $x, int $y): bool
    {
        return $y <= $this->yFrom && $y >= $this->yTo && $x >= $this->xFrom && $x <= $this->xTo;
    }
}

final class Trajectory
{
    private int $vX;
    private int $vY;
    private int $highestY;
    private bool $success;
    private array $positions;

    public function __construct(int $vX, int $vY, int $highestY, bool $success, array $positions)
    {
        $this->vX = $vX;
        $this->vY = $vY;
        $this->highestY = $highestY;
        $this->success = $success;
        $this->positions = $positions;
    }

    /**
     * @return int
     */
    public function getVX(): int
    {
        return $this->vX;
    }

    /**
     * @return int
     */
    public function getVY(): int
    {
        return $this->vY;
    }

    /**
     * @return int
     */
    public function getHighestY(): int
    {
        return $this->highestY;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return array
     */
    public function getPositions(): array
    {
        return $this->positions;
    }
}
