<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day16;

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

function directions(Map $map): array {
    return [
        $map->width * -1,
        1,
        $map->width,
        -1,
    ];
}

function turns(Map $map, int $direction): array {
    return $direction === 1 || $direction === -1
        ? [$map->width * -1, $map->width]
        : [1, -1];
}

/**
 * When performing BFS/DFS, then we can find the path by backtracking. But that will not give us the alternative routes
 * that will have the same cost. This is because a node will inevitably be visited first from one direction, which can
 * lead to a lower score. To remedy this I run BFS and keep track of the direction. That means that for the following
 * scenario:
 *
 * #  X     1
 * # 1001
 * #
 *
 * x will be reachable from the left in 2, and from the bottom in 1002. However, when coming from the right we need to
 * rotate. By administrating the direction x will have the values [from right: 2, from bottom: 1002], since the
 * difference is exactly the cost of a 90 degrees turn we can now track back both paths.
 *
 * @param Map $map
 * @return int
 */
function part_two(Map $map): int {
    $end = $map->find('E')->index($map->width);
    $start = $map->find('S')->index($map->width);

    // $map->draw($start, $end);

    // Perform BFS with a directional index

    /** @var array<int, array<string, int>> $visited */
    $visited = [
        $start => array_combine(directions($map), array_fill(0, 4, 0)),
    ];

    /** @var array<string, array<string, int>> $to_check */
    $to_check = [];

    foreach (directions($map) as $direction) {
        if ($map->get($start + $direction) !== '#') {
            $to_check[$start + $direction] = ['position' => $start + $direction, 'direction' => $direction, 'cost' => 1];
        }
    }

    while (count($to_check) > 0) {
        ['position' => $current, 'direction' => $direction, 'cost' => $cost] = array_shift($to_check);

        if (isset($visited[$current][$direction]) && $cost >= $visited[$current][$direction]) {
            // We already had a more optimal path to $current, no need to continue looking
            continue;
        }

        // Found a new shortest path to $current
        $visited[$current][$direction] = $cost;

        if ($current === $end) {
            // We've reached our goal, no need to continue looking
            continue;
        }

        // walk straight
        $next = $current + $direction;
        if (
            $map->get($current + $direction) !== '#'
            && (!isset($visited[$next][$direction]) || $visited[$next][$direction] > $cost + 1)
            && !isset($to_check[$current + $direction])
        ) {
            $to_check[$current + $direction] = ['position' => $current + $direction, 'direction' => $direction, 'cost' => $cost + 1];
        }

        // check the turns
        foreach (turns($map, $direction) as $turn) {
            if (
                $map->get($current + $turn) !== '#'
                && (!isset($visited[$current][$turn]) || $visited[$current][$turn] > $cost + 1000)
                && !isset($to_check[$current + $turn])
            ) {
                $visited[$current][$turn] = $cost + 1000;
                $to_check[$current + $turn] = ['position' => $current + $turn, 'direction' => $turn, 'cost' => $cost + 1001];
            }
        }
    }

    $min = min($visited[$end]);

    // Back track through the path
    $path = [];
    $to_check = [];
    foreach ($visited[$end] as $direction => $cost) {
        if ($cost === $min) {
            $to_check[] = ['position' => $end, 'direction' => $direction];
        }
    }

    while (count($to_check) > 0) {
        ['position' => $current, 'direction' => $direction] = array_shift($to_check);
        $path[$current] = $current; // deduplicate as we go

        // check straight
        if (isset($visited[$current - $direction][$direction]) && $visited[$current - $direction][$direction] === $visited[$current][$direction] - 1) {
            $to_check[] = ['position' => $current - $direction, 'direction' => $direction];
        }

        // check for turns
        foreach (turns($map, $direction) as $turn) {
            if (isset($visited[$current][$turn]) && $visited[$current][$turn] === $visited[$current][$direction] - 1000) {
                // Yes, we try to make the turn
                $to_check[] = ['position' => $current - $turn, 'direction' => $turn];
            }
        }
    }

    //$map->draw(...$path);
    return count($path);
}


$sw->start();
echo 'How many tiles are part of at least one of the best paths through the maze? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
