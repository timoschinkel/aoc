<?php

declare(strict_types=1);

$input = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$lines = array_map(fn(string $line): Line => Line::create($line), $input);
$map = new Map(...$lines);

echo 'At how many points do at least two lines overlap?' . PHP_EOL;
echo $map->getNumberOfDangerousPoints() . PHP_EOL;

final class Map
{
    private int $width;
    private int $height;
    /** @var int[] */
    private array $grid;

    public function __construct(Line ...$lines)
    {
        // determine max width and height as that is easier than dynamically doing it :shrug:
        $width = $height = 0;
        foreach ($lines as $line) {
            $width = max($width, $line->getStart()->getX() + 1, $line->getEnd()->getX() + 1);
            $height = max($height, $line->getStart()->getY() + 1, $line->getEnd()->getY() + 1);
        }

        $this->width = $width;
        $this->height = $height;
        $this->grid = array_fill(0, $width * $height, 0);

        // iterate all lines and map the lines on the grid
        foreach ($lines as $line) {
            $this->mark(...$line->getPoints());
        }
    }

    private function mark(Point ...$points): void
    {
        foreach ($points as $point) {
            $this->grid[$point->getY() * $this->width + $point->getX()]++;
        }
    }

    public function getNumberOfDangerousPoints(): int
    {
        return count(array_filter($this->grid, fn(int $overlaps): bool => $overlaps >= 2));
    }

    public function __toString(): string
    {
        $string = PHP_EOL;
        for($y = 0; $y < $this->height; $y++) {
            for($x = 0; $x < $this->width; $x++) {
                $string .= $this->grid[$y * $this->width + $x] === 0 ? '.' : $this->grid[$y * $this->width + $x];
            }
            $string .= PHP_EOL;
        }

        return $string . PHP_EOL;
    }
}

final class Line
{
    private Point $start;
    private Point $end;

    public function __construct(Point $start, Point $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public static function create(string $input): self
    {
        preg_match('%^(?P<x1>\d+),(?P<y1>\d+)\s*->\s*(?P<x2>\d+),(?P<y2>\d+)$%si', $input, $matches);
        return new self(new Point((int)$matches['x1'], (int)$matches['y1']), new Point((int)$matches['x2'], (int)$matches['y2']));
    }

    /** @return Point[] */
    public function getPoints(): array
    {
        $points = [];
        if ($this->start->getX() === $this->end->getX()) {
            // vertical
            for ($y = $this->start->getY(); $y != $this->end->getY(); $y += $this->end->getY() > $this->start->getY() ? 1 : -1) {
                $points[] = new Point($this->start->getX(), $y);
            }
        } elseif($this->start->getY() === $this->end->getY()) {
            // horizontal
            for ($x = $this->start->getX(); $x != $this->end->getX(); $x += $this->end->getX() > $this->start->getX() ? 1 : -1) {
                $points[] = new Point($x, $this->start->getY());
            }
        } else {
            return []; // diagonally
        }

        // add last point
        $points[] = $this->end;

        return $points;
    }

    public function getStart(): Point
    {
        return $this->start;
    }

    public function getEnd(): Point
    {
        return $this->end;
    }

    public function __toString(): string
    {
        return sprintf(
            '(%d, %d) -> (%d, %d)',
            $this->start->getX(),
            $this->start->getY(),
            $this->end->getX(),
            $this->end->getY()
        );
    }
}

final class Point
{
    private int $x;
    private int $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }
}
