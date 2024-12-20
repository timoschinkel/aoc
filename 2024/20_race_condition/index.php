<?php

declare(strict_types=1);

namespace Timoschinkel\Aoc2024\Day20;

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
 * The puzzle says "there is only a single path from the start to the end", which makes things less complicated. Not
 * every cheat is counted; only the cheats that save us at least 100 picoseconds. The first step is to find the path
 * indexed by the position that contains the amount of picoseconds. After that we can walk the path again and check in
 * all directions if we have a wall followed by a path segment that is later than our current position. If the cheat
 * saves us 100 or more picoseconds we increment our counter with one.
 *
 * @param Map $map
 * @return int
 */
function part_one(Map $map): int {
    // First find the shortest path, we don't need BFS/DFS or Dijkstra, as there is only one path.
    /** @var array<int, int> $path */
    $current = $map->find('S')->index($map->width);
    $end = $map->find('E')->index($map->width);

    $path = [$current => 0];
    while ($current !== $end) {
        $cost = end($path);

        foreach([1, $map->width, -1, $map->width * -1] as $direction) {
            if ($map->get($current + $direction) !== '#' && !isset($path[$current + $direction])) {
                $path[$current + $direction] = $cost + 1;
                $current = $current + $direction;
                continue 2;
            }
        }

        throw new \RuntimeException('Something is wrong in our algorithm...');
    }

    // Rewalk the path and check if "left" and "right" if we can take a shortcut. A shortcut is a single wall followed
    // by part of the path with a higher cost
    $cheats = 0;
    $cheats_per_saving = [];
    foreach ($path as $index => $cost) {
        foreach([1, $map->width, -1, $map->width * -1] as $direction) { // Look in all four directions
            if (
                $map->get($index + $direction) === '#'
                && ($path[$index + $direction + $direction] ?? 0) > $cost
            ) {
                // We have found a cheat!
                $saved = $path[$index + $direction + $direction] - $cost - 2;
                $cheats_per_saving[$saved] = ($cheats_per_saving[$saved] ?? 0) + 1;
                if ($saved >= 100) {
                    $cheats++;
                }
            }
        }
    }

    return $cheats;
}

$sw->start();
echo 'How many cheats would save you at least 100 picoseconds? ' . part_one($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * This solution is not optimized, but it does work!
 *
 * In part one we had to look for cheats that took us through a single wall, but now we can go into anywhere between
 * one and nineteen walls. And when I find a cheat to a position, then any alternative cheats towards the same position
 * still count as a single cheat.
 *
 * My approach; for every step of the path, find all the points within the Manhattan Distance of that point, see if they
 * are part of the path with a higher step count with a difference > 100. If so, we have found a cheat.
 *
 * @param Map $map
 * @return int
 */
function part_two(Map $map): int {
    // First find the shortest path, we don't need BFS/DFS or Dijkstra, as there is only one path.
    /** @var array<int, int> $path */
    $current = $map->find('S')->index($map->width);
    $end = $map->find('E')->index($map->width);

    $path = [$current => 0];
    while ($current !== $end) {
        $cost = end($path);

        foreach([1, $map->width, -1, $map->width * -1] as $direction) {
            if ($map->get($current + $direction) !== '#' && !isset($path[$current + $direction])) {
                $path[$current + $direction] = $cost + 1;
                $current = $current + $direction;
                continue 2;
            }
        }

        throw new \RuntimeException('Something is wrong in our algorithm...');
    }

    $cheats = 0;

    foreach ($path as $index => $cost) {
        $cheats += count_cheats($map, $path, $index);
        unset($path[$index]);
    }
    return $cheats;
}

function count_cheats(Map $map, array $path, int $index): int {
    // Find all points within Manhattan Distance of 20 from $index and check if they are in $path with lower cost
    $within_manhattan = get_within_manhattan($map, $path, $index, 20);

    $cheats = 0;
    $current = $path[$index];
    foreach ($within_manhattan as $position => $manhattan) {
        if ($path[$position] > $current) {
            $saved = $path[$position] - $current - $manhattan;
            if ($saved >= 100) {
                $cheats++;
            }
        }
    }

    return $cheats;
}

function get_within_manhattan(Map $map, array $path, int $index, int $manhattan_distance): array
{
    $cur_row = floor($index / $map->width);
    $cur_col = $index % $map->width;

    $indices = [];

    // Manhattan Distance forms a diamond square, we can use this knowledge
    for ($row = $cur_row - $manhattan_distance, $padding = $manhattan_distance * -1; $row <= $cur_row + $manhattan_distance; $row++, $padding++) {
        for ($col = $cur_col - $manhattan_distance + abs($padding); $col <= $cur_col + $manhattan_distance - abs($padding); $col++) {
            // check for bounds
            if ($row >= 1 && $row < $map->height - 1 && $col >= 1 && $col < $map->width - 1 && isset($path[intval($row * $map->width + $col)])) {
                $indices[intval($row * $map->width + $col)] = abs($cur_row - $row) + abs($cur_col - $col);
            }
        }
    }

    return $indices;
}

$sw->start();
echo 'How many cheats would save you at least 100 picoseconds? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
