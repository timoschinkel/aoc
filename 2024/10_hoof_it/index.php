<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

class Position {
    public function __construct(
        public readonly int $column,
        public readonly int $row,
    ) {
    }

    public function __toString(): string {
        return "{$this->column}x{$this->row}";
    }
}

class Map {
    public readonly int $width;
    public readonly int $height;

    /**
     * @param array<int, array<int, int>> $fields
     */
    public function __construct(
        public array $fields,
    ) {
        $this->width = count($fields[0]);
        $this->height = count($this->fields);
    }

    /**
     * @param int $value
     * @return iterable<Position>
     */
    public function find(int $value): iterable
    {
        for ($row = 0; $row < $this->height; $row++) {
            for ($column = 0; $column < $this->width; $column++) {
                if ($this->fields[$row][$column] === $value) {
                    yield new Position($column, $row);
                }
            }
        }
    }

    public function get(int $row, int $column): ?int {
        return $this->fields[$row][$column] ?? null;
    }
}

function string_to_array(string $string): array {
    $arr = [];
    for ($i = 0; $i < strlen($string); ++$i) {
        $arr[] = $string[$i];
    }

    return $arr;
}

// Read input
$map = new Map(array_map(fn(string $row): array => array_map('intval', string_to_array($row)), $rows));

$sw = new Stopwatch();

/**
 * At first glance the puzzle first glance screams BFS/DFS, but because we need the number of targets we can reach I
 * started out with a simple recursive algorithm. And that works within a decent time; find every `0` and try to walk
 * to all possible `9`'s. The trick for part 1 is that we want the number of locations we can reach, so I opted to
 * return an array of reached destinations and benefit from array_unique() to filter the results.
 *
 * @param Map $map
 * @return int
 */
function part_one(Map $map): int {
    $score = 0;

    foreach ($map->find(0) as $position) {
        $score += count(get_score($map, $position));
    }

    return $score;
}

function get_score(Map $map, Position $position): array {
    $value = $map->get($position->row, $position->column); // we can pass this probably

    if ($value === 9) return [$position];

    return array_unique([
        ...($map->get($position->row - 1, $position->column) === $value + 1 ? get_score($map, new Position($position->column, $position->row - 1)) : []),
        ...($map->get($position->row, $position->column + 1) === $value + 1 ? get_score($map, new Position($position->column + 1, $position->row)) : []),
        ...($map->get($position->row + 1, $position->column) === $value + 1 ? get_score($map, new Position($position->column, $position->row + 1)) : []),
        ...($map->get($position->row, $position->column - 1) === $value + 1 ? get_score($map, new Position($position->column - 1, $position->row)) : [])]);

}

$sw->start();
echo 'What is the sum of the scores of all trailheads on your topographic map? ' . part_one($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Part 2 is effectively part 1 without the `array_unique()`. However, by just counting I shaved of 1ms.
 *
 * @param Map $map
 * @return int
 */
function part_two(Map $map): int {
    $score = 0;

    foreach ($map->find(0) as $position) {
        $score += count_routes($map, $position);
    }

    return $score;
}

function count_routes(Map $map, Position $position): int {
    $value = $map->get($position->row, $position->column); // we can pass this probably

    if ($value === 9) {
        return 1;
    }

    return
        ($map->get($position->row - 1, $position->column) === $value + 1 ? count_routes($map, new Position($position->column, $position->row - 1)) : 0)
        + ($map->get($position->row, $position->column + 1) === $value + 1 ? count_routes($map, new Position($position->column + 1, $position->row)) : 0)
        + ($map->get($position->row + 1, $position->column) === $value + 1 ? count_routes($map, new Position($position->column, $position->row + 1)) : 0)
        + ($map->get($position->row, $position->column - 1) === $value + 1 ? count_routes($map, new Position($position->column - 1, $position->row)) : 0);
}

$sw->start();
echo 'What is the sum of the ratings of all trailheads? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
