<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
class Position {
    public function __construct(
        public readonly int $col,
        public readonly int $row,
    ) {
    }

    public function index(int $width): int {
        return $this->row * $width + $this->col;
    }
}

class Map {
    public readonly int $width;
    public readonly int $height;
    private string $fields;

    public function __construct(
        array $fields,
    ) {
        $this->width = strlen($fields[0]);
        $this->height = count($fields);

        $this->fields = join('', $fields);
    }

    public function find(string $char): Position
    {
        $index = strpos($this->fields, $char);

        return new Position($index % $this->width, (int)($index / $this->width));
    }

    public function get(int $index): ?string {
        return $this->fields[$index] ?? null;
    }

    public function set(int $index, string $char): void {
        $this->fields[$index] = $char;
    }

    public function clone(): self {
        return new Map(
            explode(PHP_EOL, chunk_split($this->fields, $this->width, PHP_EOL))
        );
    }

    public function draw(int ...$highlights): void {
        for ($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                if ($highlights && in_array($row * $this->width + $col, $highlights)) {
                    echo "\e[43m{$this->get($row * $this->width + $col)}\e[0m";
                } else {
                    echo $this->get($row * $this->width + $col);
                }
            }
            echo PHP_EOL;
        }
        echo PHP_EOL;
    }
}

$map = new Map($rows);

$sw = new \Stopwatch();

/**
 * A classic BFS; for every potential step we find the neighbor and determine the cost to go there. This is not the most
 * efficient approach, but it gets the answer.
 *
 * @param Map $map
 * @return int
 */
function part_one(Map $map): int {
    $map = $map->clone();

    $end = $map->find('E')->index($map->width);

    //    $map->draw($map->find('S')->index($map->width), $end);

    $visited = create_visited_map($map);

    return $visited[$end] ?? 0;
}

function create_visited_map(Map $map): array {
    $start = $map->find('S')->index($map->width);

    /** @var array<int, int> $visited */
    $visited = [];

    $stack = [['position' => $start, 'direction' => 0, 'cost' => 0]];
    while (count($stack) > 0) {
        ['position' => $current, 'direction' => $direction, 'cost' => $cost] = array_shift($stack);

        if (isset($visited[$current]) && $cost >= $visited[$current]) {
            // We have found an equally or more expensive route, we don't need to pursue this.
            continue;
        }

        // We have found a new or a cheaper path to $current. Update our administration.
        $visited[$current] = $cost;

        // Check all neighboring
        foreach ([
             $map->width * -1, // up
             1, // right
             $map->width, // down
             -1, // left
         ] as $dir) {
            if (
                $direction !== $dir * -1 // we should not turn around
                && $map->get($current + $dir) !== '#'
            ) {
                // we can go there, but should we go there?
                $projected_cost = $cost + ($direction === 0 || $dir === $direction ? 1 : 1001);

                if (!isset($visited[$current + $dir]) || $visited[$current + $dir] > $projected_cost) {
                    $stack[] = [
                        'position' => $current + $dir,
                        'direction' => $dir,
                        'cost' => $projected_cost,
                    ];
                }
            }
        }

        // Optimization:
        // for each neighboring junction walk there and put it on the stack

    }

    return $visited;
}

$sw->start();
echo 'What is the lowest score a Reindeer could possibly get? ' . part_one($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

function part_two(Map $map): int {
    return 0;
}

$sw->start();
echo 'What is their similarity score? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
