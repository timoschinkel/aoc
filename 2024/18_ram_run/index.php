<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day18;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));
$width = $height = (($argv[1] ?? 'example') === 'example' ? 6 : 70) + 1;
$length = (($argv[1] ?? 'example') === 'example' ? 12 : 1024);

// Read input
class Position {
    public function __construct(
        public readonly int $column,
        public readonly int $row,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('(%d, %d)', $this->column, $this->row);
    }

    public static function create(string $input): self {
        [$column, $row] = explode(',', $input);
        return new self((int)$column, (int)$row);
    }

    public function add(Position $position): self
    {
        return new self($this->column + $position->column, $this->row + $position->row);
    }
}

$bytes = array_map(fn(string $row): Position => Position::create($row), $rows);

$sw = new \Stopwatch();

/**
 * A straightforward BFS.
 *
 * @param int $width
 * @param int $height
 * @param array<Position> $bytes
 * @return int
 */
function part_one(int $width, int $height, array $bytes): int {
    $barriers = [];
    foreach ($bytes as $byte) {
        if ($byte->column >= 0 && $byte->column <= $width && $byte->row >= 0 && $byte->row <= $height) {
            $barriers[$byte->row * $width + $byte->column] = true;
        }
    }

    /** @var array<int, int> $visited */
    $visited = [];

    $start = 0; // top left
    $end = ($height * ($width - 1)) + ($width - 1); // bottom right

//    draw($width, $height, $barriers);

    // BFS
    /** @var array<int, array<string, int>> $to_check */
    $to_check = [$start => ['position' => $start, 'cost' => 0]];

    while (count($to_check) > 0) {
        ['position' => $current, 'cost' => $cost] = array_shift($to_check);

        $col = $current % $width;
        $row = floor($current / $width);

        if (isset($visited[$current]) && $visited[$current] <= $cost) {
            // We already found a more viable route, continue with that information
            continue;
        }

        $visited[$current] = $cost;

        if ($current === $end) {
            // We've reached the end, since we're doing BFS, and we don't have any additional costs for turns etc. this
            // means that we have found the shortest path, so we can immediately stop all other paths.
            return $cost;
        }

        foreach ([
            new Position(0, 1),
            new Position(1, 0),
            new Position(-1, 0),
            new Position(0, -1),
        ] as $direction) {
            if ($col + $direction->column < 0 || $col + $direction->column > $width - 1 || $row + $direction->row < 0 || $row + $direction->row > $height - 1) {
                // Out of bounds
                continue;
            }

            $next = ($row + $direction->row) * $width + ($col + $direction->column);

            if (isset($barriers[$next])) {
                // we cannot go there
                continue;
            }

            if (!isset($visited[$next]) || $visited[$next] > $cost + 1) {
                $to_check[] = ['position' => $next, 'cost' => $cost + 1];
            }
        }
    }

    return $visited[$end] ?? 0;
}

function draw(int $width, int $height, array $visited): void
{
    for ($row = 0; $row < $width; $row++) {
        for ($col = 0; $col < $height; $col++) {
            echo $visited[$row * $width + $col] ?? '.';
//            if (isset($visited[$row * $width + $col])) {
//                echo '#';
//            } else {
//                echo '.';
//            }
        }
        echo PHP_EOL;
    }
    echo PHP_EOL;
}

$sw->start();
echo 'What is the minimum number of steps needed to reach the exit? ' . part_one($width, $height, array_slice($bytes, 0, $length)) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * This is the brute-force approach; After the first $length bytes we drop another and check if part_one() returns an
 * answer that is != 0. 0 means that we did not find a path, so that will be the first byte that will completely block
 * us.
 *
 * @param int $width
 * @param int $height
 * @param int $length
 * @param array<Position> $bytes
 * @return string
 */
function part_two(int $width, int $height, int $length, array $bytes): string {
    for ($iteration = $length; $iteration < count($bytes); $iteration++) {
        if (part_one($width, $height, array_slice($bytes, 0, $iteration)) === 0) {
            return $bytes[$iteration - 1]->column . ',' . $bytes[$iteration - 1]->row;
        }
    }
    return '';
}

/**
 * I found this really nice visualization for this on [Reddit][reddit], and I decided to implement it. Compared to the
 * brute-force solution from `part_two()` this runs in 12ms, instead of 4.5s.
 *
 * There is a path from top-left to bottom-right, and we need to determine when this path is no longer feasible. This
 * path is no longer feasible when the north and/or east sides are connected to the south and/or west sides:
 *
 * RRRRRRRRRR
 * BS       R
 * B        R
 * B       ER
 * BBBBBBBBBB
 *
 * In this diagram; the moment R touches B a path is no longer feasible. To achieve this for every byte that drops we
 * check if the byte is touching one of the borders, and assign it that value. We also check if we are neighboring a
 * already dropped byte that has a value. In that case we perform a flood fill algorithm.
 *
 * [reddit]: https://www.reddit.com/r/adventofcode/comments/1hhiawu/2024_day_18_part_2_visualization_of_my_algorithm
 *
 * @param int $width
 * @param int $height
 * @param int $length
 * @param array<Position> $bytes
 * @return string
 */
function part_two_optimized(int $width, int $height, int $length, array $bytes): string {
    /** @var array<int, string> $fields */
    $fields = [];

   foreach ($bytes as $byte) {
        $red = 0; $blue = 0;

        $value = '#';

        // Check if we are at a boundary
        if ($byte->row === 0 || $byte->column === $width - 1) {
//            echo 'Marking as RED' . PHP_EOL;
            $fields[$byte->row * $width + $byte->column] = 'R';
            $value = 'R';
        } elseif ($byte->column === 0 || $byte->row === $height - 1) {
//            echo 'Marking as BLUE' . PHP_EOL;
            $fields[$byte->row * $width + $byte->column] = 'B';
            $value = 'B';
        }

        // check all 9 neighbors
        /** @var array<Position> $neighbors */
        $neighbors = []; // We might need this in a followup step
        foreach (neighbors($byte, $width, $height) as $neighbor) {
            $index = ($byte->row + $neighbor->row) * $width + ($byte->column + $neighbor->column);
            if (($fields[$index] ?? '') === 'B') $blue++;
            elseif (($fields[$index] ?? '') === 'R') $red++;
            elseif (isset($fields[$index])) $neighbors[] = $byte->add($neighbor);
        }

        if ($red > 0 && $blue > 0 || ($red > 0 && $value === 'B') || ($blue > 0 && $value === 'R')) {
            // We have found a collision!
            return "{$byte->column},{$byte->row}";
        } elseif ($red > 0) {
//            echo 'Found red neighbor(s)' . PHP_EOL;
            $fields[$byte->row * $width + $byte->column] = 'R';
        } elseif ($blue > 0) {
//            echo 'Found blue neighbor(s)' . PHP_EOL;
            $fields[$byte->row * $width + $byte->column] = 'B';
        } elseif ($value === '#') {
            $fields[$byte->row * $width + $byte->column] = '#';
            continue; // move on to next byte
        }

        if (count($neighbors) > 0) {
            // we perform a flood fill from the current location starting with `$neighbors`
            $value = $fields[$byte->row * $width + $byte->column];

            while (count($neighbors) > 0) {
                $neighbor = array_pop($neighbors);

//                echo 'Setting '. $neighbor->column . ', '. $neighbor->row . ' to ' . $value . PHP_EOL;
                $fields[$neighbor->row * $width + $neighbor->column] = $value;

                foreach (neighbors($neighbor, $width, $height) as $direction) {
                    $candidate = $neighbor->add($direction);
                    if (($fields[$candidate->row * $width + $candidate->column] ?? '') === '#') {
                        $neighbors[] = $candidate;
                    }
                }
            }
        }
    }

    return '';
}

/**
 * @return array<Position>
 */
function neighbors(Position $position, int $width, int $height): array
{
    $neighbors = [];

    if ($position->row > 0 && $position->column > 0) $neighbors[] = new Position(-1, -1);
    if ($position->row > 0) $neighbors[] = new Position(0, -1);
    if ($position->row > 0 && $position->column < $width - 1) $neighbors[] = new Position(1, -1);

    if ($position->column > 0) $neighbors[] = new Position(-1, 0);
    if ($position->column < $width - 1) $neighbors[] = new Position(1, 0);

    if ($position->row < $height - 1 && $position->column > 0) $neighbors[] = new Position(-1, 1);
    if ($position->row < $height - 1 ) $neighbors[] = new Position(0, 1);
    if ($position->row < $height - 1 && $position->column < $width - 1) $neighbors[] = new Position(1, 1);

    return $neighbors;
}

$sw->start();
echo 'What are the coordinates of the first byte that will prevent the exit from being reachable from your starting position? ' . part_two_optimized($width, $height, $length, $bytes) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
