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
            if (isset($visited[$row * $width + $col])) {
                echo '#';
            } else {
                echo '.';
            }
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

$sw->start();
echo 'What are the coordinates of the first byte that will prevent the exit from being reachable from your starting position? ' . part_two($width, $height, $length, $bytes) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
