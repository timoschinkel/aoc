<?php

declare(strict_types=1);

$inputs = explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

// Read inputs
$scanners = [];
$measurements = [];
foreach ($inputs as $input) {
    if (preg_match('%^--- scanner (?P<id>\d+) ---$%si', $input, $matches)) {
        if ($measurements) {
            $scanners[] = new Scanner((int)$matches['id'] - 1, ...$measurements);
        }
        $measurements = [];
    } elseif (preg_match('%(?P<x>-?\d+),(?P<y>-?\d+),(?P<z>-?\d+)$%si', $input, $matches)) {
        $measurements[] = new Measurement((int)$matches['x'], (int)$matches['y'], (int)$matches['z']);
    }
}
if ($measurements) {
    $scanners[] = new Scanner(count($scanners), ...$measurements);
}

$plane = new Plane(...$scanners);

// Part 1
$num_of_beacons = $plane->getNumberOfBeacons();

echo 'How many beacons are there?' . PHP_EOL;
echo $num_of_beacons . PHP_EOL;

echo PHP_EOL;

// Part 2
$max_manhattan_distance = $plane->getMaxManhattanDistance();

echo 'What is the largest Manhattan distance between any two scanners?' . PHP_EOL;
echo $max_manhattan_distance . PHP_EOL;

final class Plane
{
    private array $scanners;

    /** @var array<int, Scanner> */
    private array $fixated = [];

    /** @var array<string, Measurement> */
    private array $absolute_beacon_locations = [];

    public function __construct(Scanner ...$scanners)
    {
        $this->scanners = $scanners;
    }

    private function getMatchedScanner(Scanner $other): ?Scanner
    {
        // The rotation of $other is unknown. Try every possible rotation.
        foreach ($other->getAllRotations() as $rotated) {

            // For every rotation iterate over all relative beacon measurements and try
            // the match the beacon against the known absolute locations. By comparing
            // the rotated coordinate against the known locations we get a list of possible
            // centers for the scanner. We need at least 12 coordinates that result in the
            // same absolute coordinates for the center of $other to consider it a match.

            $possible_centers = [];

            foreach ($rotated->getRelativeMeasurements() as $relative_location) {
                foreach ($this->absolute_beacon_locations as $absolute_location) {
                    // By subtracting the $relative_location from the $absolute_location the possible absolute
                    // center of $other/$rotated is calculated.
                    $center = $absolute_location->subtract($relative_location);
                    $possible_centers[(string)$center] = [
                        'count' => ($possible_centers[(string)$center]['count'] ?? 0) + 1,
                        'measurement' => $center
                    ];
                }
            }

            // Order the possible centers from high to low and that the first element; This element
            // describes the location that was matched most often with $rotated.
            uasort($possible_centers, fn(array $one, array $another): int => $another['count'] <=> $one['count']);
            $max = reset($possible_centers);
            if ($max['count'] >= 12) {
                // We have found a match!!!
                $rotated->fixate($max['measurement']);
                return $rotated;
            }
        }

        return null;
    }

    public function getNumberOfBeacons(): int
    {
        $scanners = $this->scanners; // Create a copy of $this->scanner, so we reuse the code for part 2

        // Because all measurements are relative to the center of the scanner we can fixate the first scanner
        // and thus mark all the measurements are "known".
        // $this->known_beacons will contain all coordinates of beacons for which we know the absolute
        // locations. These are indexed by a string representation for easy deduplication.
        $zero = array_shift($scanners);

        // Scanners that are fixated, eg. we know their orientation and center. Add $zero as first fixated scanner.
        $this->fixated = [$zero];

        $this->absolute_beacon_locations = array_combine(
            array_map(fn(Measurement $m): string => (string)$m, $zero->getRelativeMeasurements()),
            $zero->getRelativeMeasurements()
        );

        // Iterate over all scanners and try to match them against the known absolute beacon locations
        // If a scanner does not match it is being added to the back of the queue. Eventually it will have
        // a match with the locations of the known beacons.
        while (count($scanners) > 0) {
            // grab first from scanner queue
            $other = array_shift($scanners);

            if ($match = $this->getMatchedScanner($other)) {
                // $match is a fixated clone of $other with the correct center and rotation set.
                // Use $match to update our state.

                $this->fixated[] = $match;
                foreach ($match->getRelativeMeasurements() as $measurement) {
                    // Calculate the exact locations of the beacons using $match->center():
                    $absolute_beacon_location = new Measurement($measurement->x() + $match->center()->x(), $measurement->y() + $match->center()->y(), $measurement->z() + $match->center()->z());
                    $this->absolute_beacon_locations[(string)$absolute_beacon_location] = $absolute_beacon_location;
                }
            } else {
                // $other is no match with any of the known beacons. Add at to the back of the queue.
                // It will be matched in a later iteration.
                $scanners[] = $other;
            }
        }

        return count($this->absolute_beacon_locations);
    }

    public function getMaxManhattanDistance(): int
    {
        $max_distance = 0;
        foreach ($this->fixated as $one) {
            foreach ($this->fixated as $another) {
                $max_distance = max($max_distance, $one->getManhattanDistance($another));
            }
        }

        return $max_distance;
    }
}

final class Measurement
{
    private int $x;
    private int $y;
    private int $z;

    public function __construct(int $x, int $y, int $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function x(): int
    {
        return $this->x;
    }

    public function y(): int
    {
        return $this->y;
    }

    public function z(): int
    {
        return $this->z;
    }

    public function subtract(self $other): self
    {
        return new self($this->x() - $other->x(), $this->y() - $other->y(), $this->z() - $other->z());
    }

    public function __toString(): string
    {
        return sprintf('%d,%d,%d', $this->x, $this->y, $this->z);
    }
}

final class Scanner
{
    private int $id;
    private array $measurements;
    private Measurement $center;

    public function __construct(int $id, Measurement ...$measurements)
    {
        $this->id = $id;
        $this->measurements = $measurements;
        $this->center = new Measurement(0, 0, 0);
    }

    public function fixate(Measurement $center): void
    {
        $this->center = $center;
    }

    public function getAllRotations(): array
    {
        // http://www.euclideanspace.com/maths/algebra/matrix/transforms/examples/index.htm
        // Following the matrices at the bottom of the page from left to right.
        return [
            $this,
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->x(), $m->z(), $m->y() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->x(), $m->y() * -1, $m->z() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->x(), $m->z() * -1, $m->y()), $this->measurements)),

            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->y() * -1 , $m->x(), $m->z()), $this->measurements)),
            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->z(), $m->x(), $m->y()), $this->measurements)),
            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->y(), $m->x(), $m->z() * -1), $this->measurements)),
            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->z() * -1, $m->x(), $m->y() * -1), $this->measurements)),

            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->x() * -1, $m->y() * -1, $m->z()), $this->measurements)),
            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->x() * -1, $m->z() * -1, $m->y() * -1), $this->measurements)),
            new self($this->id,...array_map(fn(Measurement $m): Measurement => new Measurement($m->x() * -1, $m->y(), $m->z() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->x() * -1, $m->z(), $m->y()), $this->measurements)),

            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y(), $m->x() * -1, $m->z()), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z(), $m->x() * -1, $m->y() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y() * -1, $m->x() * -1, $m->z() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z() * -1, $m->x() * -1, $m->y()), $this->measurements)),

            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z() * -1, $m->y(), $m->x()), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y(), $m->z(), $m->x()), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z(), $m->y() * -1, $m->x()), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y() * -1, $m->z() * -1, $m->x()), $this->measurements)),

            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z() * -1, $m->y() * -1, $m->x() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y() * -1, $m->z(), $m->x() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->z(), $m->y(), $m->x() * -1), $this->measurements)),
            new self($this->id, ...array_map(fn(Measurement $m): Measurement => new Measurement($m->y(), $m->z() * -1, $m->x() * -1), $this->measurements)),
        ];
    }

    public function getRelativeMeasurements(): array
    {
        return $this->measurements;
    }

    public function center(): Measurement
    {
        return $this->center;
    }

    public function getManhattanDistance(self $other): int
    {
        $sub = $this->center()->subtract($other->center());
        return $sub->x() + $sub->y() + $sub->z();
    }

    public function __toString(): string
    {
        return '--- scanner ' . $this->id . ' ---' . PHP_EOL .
            join(PHP_EOL, $this->measurements) . PHP_EOL;
    }
}
