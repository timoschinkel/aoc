<?php

declare(strict_types=1);

ini_set('memory_limit', '2G');

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Part 1
$steps = array_filter(array_map(fn(string $input): ?Step => Step::create($input), $inputs));
$result = (new Grid())->countCubesThatAreOn(...$steps);

echo 'How many cubes are on?' . PHP_EOL;
echo $result . PHP_EOL;

final class Step
{
    private string $operation;
    private int $xs;
    private int $xt;
    private int $ys;
    private int $yt;
    private int $zs;
    private int $zt;

    public function __construct(string $operation, int $xs, int $xt, int $ys, int $yt, int $zs, int $zt)
    {
        $this->operation = $operation;
        $this->xs = max(-50, min(50, $xs));
        $this->xt = max(-50, min(50, $xt));
        $this->ys = max(-50, min(50, $ys));
        $this->yt = max(-50, min(50, $yt));
        $this->zs = max(-50, min(50, $zs));
        $this->zt = max(-50, min(50, $zt));
    }

    public function isOn(): bool
    {
        return $this->operation === 'on';
    }

    public function x(): array
    {
        return range($this->xs, $this->xt, 1);
    }

    public function y(): array
    {
        return range($this->ys, $this->yt, 1);
    }

    public function z(): array
    {
        return range($this->zs, $this->zt, 1);
    }

    private static function isInRange(int $value, int $min = -50, int $max = 50): bool
    {
        return $value >= $min && $value <= $max;
    }

    public static function create (string $input): ?self
    {
        preg_match('%^(?P<operation>on|off) x=(?P<x_from>-?\d+)\.\.(?P<x_to>-?\d+),y=(?P<y_from>-?\d+)\.\.(?P<y_to>-?\d+),z=(?P<z_from>-?\d+)\.\.(?P<z_to>-?\d+)$%si', $input, $matches);

        // If one of the axis is completely out of range, the entire Step is out of range:
        if ((!self::isInRange((int)$matches['x_from']) && !self::isInRange((int)$matches['x_to'])) ||
            (!self::isInRange((int)$matches['y_from']) && !self::isInRange((int)$matches['y_to'])) ||
            (!self::isInRange((int)$matches['z_from']) && !self::isInRange((int)$matches['z_to']))) {
            return null;
        }

        return new self(
            $matches['operation'],
            (int)$matches['x_from'], (int)$matches['x_to'],
            (int)$matches['y_from'], (int)$matches['y_to'],
            (int)$matches['z_from'], (int)$matches['z_to']
        );
    }

    public function __toString(): string
    {
        return sprintf('%s x=%d..%d, y=%d..%d, z=%d..%d', $this->operation, $this->xs, $this->xt, $this->ys, $this->yt, $this->zs, $this->zt);
    }
}

final class Grid
{
    public function countCubesThatAreOn(Step ...$steps): int
    {
        $on = [];

        foreach ($steps as $i => $step) {
            foreach ($step->x() as $x) {
                foreach ($step->y() as $y) {
                    foreach ($step->z() as $z) {
                        if ($x < -50 || $x > 50 || $y < -50 && $y > 50 || $z < -50 || $z > 50) {
                            // out of bounds
                            continue;
                        }

                        $point = json_encode(['x' => $x, 'y' => $y, 'z' => $z]);
                        $on[$point] = $step->isOn();
                    }
                }
            }

            $on = array_filter($on);

            //echo 'After step ' . ($i + 1) . ' - ' . $step . ': ' . count($on) . PHP_EOL;
        }

        return count($on);
    }
}
