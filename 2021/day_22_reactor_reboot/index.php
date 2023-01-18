<?php

declare(strict_types=1);

ini_set('memory_limit', '2G');

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/' . ($argv[1] ?? 'input') . '.txt')));
$expection = $argv[2] ?? null;

// Part 1
$steps = array_filter(array_map(fn(string $input): ?LimitedCuboid => LimitedCuboid::create($input), $inputs));

$stopwatch = hrtime(true);
$result = calculateNumberOfCubes($steps);
$elapsedMilliseconds = round((hrtime(true) - $stopwatch)/1e+6, 0);

echo "How many cubes are on? {$result} ({$elapsedMilliseconds}ms)" . PHP_EOL;

// Part 2;
// Idea is to create a list of "final cuboids". This list contains cuboids that don't intersect with any other cuboid in
// the input. Every cuboid is checked against this final list, if no intersection is found we add it to the final list,
// given that is an "on" operation. If we do find an intersection we split up the cuboid into smaller cuboids. At the
// end of this operation we are able to calculate the volume of the cuboids in the final list and that should tell us
// exactly how many cubes are on.
$cuboids = array_filter(array_map(fn(string $input): ?Cuboid => Cuboid::create($input), $inputs));

$total = calculateNumberOfCubes($cuboids);
$elapsedMilliseconds = round((hrtime(true) - $stopwatch)/1e+6, 0);
echo "How many cubes are on? {$total} ({$elapsedMilliseconds}ms)" . PHP_EOL;

function calculateNumberOfCubes(array $cuboids): int
{
    /** @var Cuboid[] $final */
    $final = []; // cuboids that don't intersect

    $cuboids = array_reverse($cuboids); // Stack is LIFO

    while (count($cuboids) > 0) {
        /** @var Cuboid $current */
        $current = array_pop($cuboids);

        // Keep a list of items to remove from `$final` and a list of items to add. This prevents that we need to change
        // the order or $final while looping over it.
        $indicesToRemoveFromFinal = [];
        $cuboidsToAddToFinal = [];

        foreach ($final as $index => $cuboid) {
            if ($cuboid->intersects($current)) {
                // handle intersection
                if ($current->on()) {
                    // operation of $current is on, so we need to split up $current over all the intersecting points. Every
                    // resulting cuboids is to be checked for intersections. Easiest way to do this is by putting the split up
                    // cuboids back into $cuboids to be handled by a next iteration. There is one special case; If $current is
                    // completely covered by the cuboid that intersects it. If this is the other way around for now it is easier to
                    // still split up and put the non-intersecting cuboids on $stack. This is due to the fact that the operation is
                    // in order, so we would have to rerun the entire operation again.
                    $sections = $current->diff($cuboid);

                    $cuboids = array_merge($cuboids, $sections);
                    continue 2; // continue to next on stack
                } else {
                    // operation of $current is off, so we need to split up the intersected cuboids in $final, remove the
                    // intersecting cuboids and put the non-intersecting cuboids back in $final
                    $sections = $cuboid->diff($current);

                    $indicesToRemoveFromFinal[] = $index;
                    $cuboidsToAddToFinal = array_merge($cuboidsToAddToFinal, $sections);
                }
            }
        }

        // $current does not intersect, if the operation is "on" we can add it to $final
        if ($current->on()) {
            $final[] = $current;
        }

        foreach (array_reverse($indicesToRemoveFromFinal) as $index_to_remove) {
            unset($final[$index_to_remove]);
        }
        $final = array_values(array_merge($final, $cuboidsToAddToFinal));
    }

    $total = 0;
    foreach($final as $cuboid) {
        $total += $cuboid->volume();
    }

    return $total;
}

class Cuboid
{
    private bool $on;
    private int $x1;
    private int $x2;
    private int $y1;
    private int $y2;
    private int $z1;
    private int $z2;

    public function __construct(
        bool $on,
        int $x1, int $x2,
        int $y1, int $y2,
        int $z1, int $z2
    ) {
        $this->on = $on;
        $this->x1 = $x1;
        $this->x2 = $x2;
        $this->y1 = $y1;
        $this->y2 = $y2;
        $this->z1 = $z1;
        $this->z2 = $z2;
    }

    public function on(): bool { return $this->on; }
    public function x1(): int { return $this->x1; }
    public function x2(): int { return $this->x2; }
    public function y1(): int { return $this->y1; }
    public function y2(): int { return $this->y2; }
    public function z1(): int { return $this->z1; }
    public function z2(): int { return $this->z2; }

    public function volume(): int
    {
        return ($this->x2 - $this->x1 + 1) *
            ($this->y2 - $this->y1 + 1) *
            ($this->z2 - $this->z1 + 1);
    }

    public function intersects(Cuboid $other): bool
    {
        // In every dimension the two cuboids need to be different
        return
            !($this->x1 > $other->x2() || $this->x2 < $other->x1())
            and !($this->y1 > $other->y2() || $this->y2 < $other->y1())
            and !($this->z1 > $other->z2() || $this->z2 < $other->z1());
    }

    /**
     * Splits up $this and returns the resulting cuboids that are NOT intersecting with $other
     * @param Cuboid $other
     * @return Cuboid[]
     */
    public function diff(Cuboid $other): array
    {
        // Per axis check for the intersections. Every section that does not intersect with $other is split
        // and put on the returning array. The cuboid that _does_ intersect is taken to the next axis.

        $diffs = [];
        $clone = clone $this;

        // X
        $x1 = $clone->x1;
        $x2 = $clone->x2;

        if ($clone->x1 < $other->x1() && $clone->x2 > $other->x2()) {
            // $this is larger than $other, split three ways, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $other->x1() - 1, $clone->y1, $clone->y2, $clone->z1, $clone->z2);
            $diffs[] = new Cuboid($clone->on, $other->x2() + 1, $clone->x2, $clone->y1, $clone->y2, $clone->z1, $clone->z2);

            $x1 = $other->x1();
            $x2 = $other->x2();
        } elseif ($clone->x1 >= $other->x1() && $clone->x2 <= $other->x2()) {
            // $this is equal to $other, or full embodies, do not split over x,
            // nor change the values of $this.
            // do nothing
        } elseif ($clone->x1 < $other->x1() && $clone->x2 <= $other->x2()) {
            // $this is positioned left of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $other->x1() - 1, $clone->y1, $clone->y2, $clone->z1, $clone->z2);

            $x1 = $other->x1();
        } elseif ($clone->x1 >= $other->x1() && $clone->x2 > $other->x2()) {
            // $this is position right of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $other->x2() + 1, $clone->x2, $clone->y1, $clone->y2, $clone->z1, $clone->z2);

            $x2 = $other->x2();
        } else
            throw new RuntimeException('I did not see this coming...');

        $clone->x1 = $x1;
        $clone->x2 = $x2;

        // Check what remains of $this over Y axis
        $y1 = $clone->y1;
        $y2 = $clone->y2;
        if ($clone->y1 < $other->y1() && $clone->y2 > $other->y2()) {
            // $this is larger than $other, split three ways, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $other->y1() - 1, $clone->z1, $clone->z2);
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $other->y2() + 1, $clone->y2, $clone->z1, $clone->z2);

            $y1 = $other->y1();
            $y2 = $other->y2();
        } elseif ($clone->y1 >= $other->y1() && $clone->y2 <= $other->y2()) {
            // $this is equal to $other, or full embodies, do not split over y
            // do nothing
        } elseif ($clone->y1 < $other->y1() && $clone->y2 <= $other->y2()) {
            // $this is positioned left of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $other->y1() - 1, $clone->z1, $clone->z2);

            $y1 = $other->y1();
        } elseif ($clone->y1 >= $other->y1() && $clone->y2 > $other->y2()) {
            // $this is position right of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $other->y2() + 1, $clone->y2, $clone->z1, $clone->z2);

            $y2 = $other->y2();
        } else
            throw new RuntimeException('I did not see this coming...');

        $clone->y1 = $y1;
        $clone->y2 = $y2;

        // Check what remains of $this over Z axis
        $z1 = $clone->z1;
        $z2 = $clone->z2;
        if ($clone->z1 < $other->z1() && $clone->z2 > $other->z2()) {
            // $this is larger than $other, split three ways, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $clone->y2, $clone->z1, $other->z1() - 1);
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $clone->y2, $other->z2() + 1, $clone->z2);

            $z1 = $other->z1();
            $z2 = $other->z2();
        } elseif ($clone->z1 >= $other->z1() && $clone->z2 <= $other->z2()) {
            // $this is equal to $other, or full embodies, do not split over y
            // do nothing
        } elseif ($clone->z1 < $other->z1() && $clone->z2 <= $other->z2()) {
            // $this is positioned left of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $clone->y2, $clone->z1, $other->z1() - 1);

            $z1 = $other->z1();
        } elseif ($clone->z1 >= $other->z1() && $clone->z2 > $other->z2()) {
            // $this is position right of $other, split in two, with one intersecting
            $diffs[] = new Cuboid($clone->on, $clone->x1, $clone->x2, $clone->y1, $clone->y2, $other->z2() + 1, $clone->z2);

            $z2 = $other->z2();
        } else
            throw new RuntimeException('I did not see this coming...');

        $clone->z1 = $z1;
        $clone->z2 = $z2;

        return $diffs;
    }

    public static function create (string $input): ?self
    {
        preg_match('%^(?P<operation>on|off) x=(?P<x_from>-?\d+)\.\.(?P<x_to>-?\d+),y=(?P<y_from>-?\d+)\.\.(?P<y_to>-?\d+),z=(?P<z_from>-?\d+)\.\.(?P<z_to>-?\d+)$%si', $input, $matches);

        return new self(
            $matches['operation'] === 'on',
            (int)$matches['x_from'], (int)$matches['x_to'],
            (int)$matches['y_from'], (int)$matches['y_to'],
            (int)$matches['z_from'], (int)$matches['z_to']
        );
    }

    public function toString(): string
    {
        return sprintf('%s x=%d..%d, y=%d..%d, z=%d..%d (%d)', $this->on ? 'on' : 'off', $this->x1, $this->x2, $this->y1, $this->y2, $this->z1, $this->z2, $this->volume());
    }
}

final class LimitedCuboid extends Cuboid
{
    private static function isInRange(int $value): bool
    {
        return $value >= -50 && $value <= 50;
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
            $matches['operation'] === 'on',
            max(-50, min(50, (int)$matches['x_from'])), max(-50, min(50, (int)$matches['x_to'])),
            max(-50, min(50, (int)$matches['y_from'])), max(-50, min(50, (int)$matches['y_to'])),
            max(-50, min(50, (int)$matches['z_from'])), max(-50, min(50, (int)$matches['z_to']))
        );
    }
}
