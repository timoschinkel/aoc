<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

class Map {
    public readonly int $width;
    public readonly int $height;

    public function __construct(
        public array $fields,
    ) {
        $this->width = strlen($fields[0]);
        $this->height = count($this->fields);
    }

    public function find(string $char): array
    {
        $index = strpos(join('', $this->fields), $char);

        return [
            'row' => (int)($index / $this->width),
            'column' => $index % $this->width,
        ];
    }

    public function get(int $row, int $column): ?string {
        return $this->fields[$row][$column] ?? null;
    }

    public function set(int $row, int $column, string $char): void {
        $this->fields[$row][$column] = $char;
    }
}

// Read input
$width = strlen($rows[0]);
$height = count($rows);

$map = new Map($rows);

$sw = new Stopwatch();

/*
 * The idea is pretty simple; we keep track of the location of the guard and the direction the guard is walking. The
 * direction the guard is walking is stored as the direction on the x-axis and the y-axis. With every step we will
 * increment the column and row with the directional values. Every step we check if we are out of bound - in which case
 * the puzzle is done -, or whether we have encountered an obstacle. In case we encounter an obstacle we need to make a
 * right turn. I kind of expected a scenario where the guard needs to take multiple turns before being able to continue.
 * To cater for that I take a step back - easily calculated by subtracting using the directional values - and change the
 * directional values to the right turn.
 *
 * Possible optimization; I kept a dictionary indexed by the coordinate, but for part 2 I needed the direction. This
 * increased the time of part one from 800μs to 2ms.
 */
function part_one(Map $map): int {
    // Find guard position
    ['row' => $row, 'column' => $column] = $map->find('^');

    // Mark as visited incl direction
    $visited = ["{$column}x{$row}" => '^'];

    // Moving up
    $direction = '^';
    $dRow = -1;
    $dCol = 0;

    $row = $row + $dRow;
    $column = $column + $dCol;

    // iterate while we're not out of bounds
    while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width) {
        $current = $map->get($row, $column);

        if ($current !== '#') {
            // No obstable, we can visit it!
            $visited["{$column}x{$row}"] = $direction;

            // Take next step
            $row = $row + $dRow;
            $column = $column + $dCol;
        } else {
            // We have found ourselves an obstacle!
            // take a step back
            $row = $row - $dRow;
            $column = $column - $dCol;

            // and rotate
            if ($dCol === 0 && $dRow === -1) { $dCol = 1; $dRow = 0; $direction = '>'; }
            elseif ($dCol === 1 && $dRow === 0) { $dCol = 0; $dRow = 1; $direction = 'v'; }
            elseif ($dCol === 0 && $dRow === 1) { $dCol = -1; $dRow = 0; $direction = '<'; }
            elseif ($dCol === -1 && $dRow === 0) { $dCol = 0; $dRow = -1; $direction = '^'; }
        }
    }

    return count($visited);
}

$sw->start();
echo 'How many distinct positions will the guard visit before leaving the mapped area? ' . part_one($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/*
 * I have an idea for an optimization, but it requires some rewriting of my code, so I have opted for the brute force
 * approach for now. By keeping track of the direction we passed every location on the map it is possible to detect when
 * we encounter a look. What I do right now is reuse the path found in part 1, iterate over every visited location,
 * replace that location with an obstacle and run part 1 again. If we encounter a loop we have found an eligible
 * location.
 */
function part_two(Map $map): int {
    // Original path including directions
    $path = find_path($map);

    $locations = array_keys(array_slice($path, 1)); // list all eligible positions, but remove the initial guard position

    $obstacles = 0;
    foreach ($locations as $location) {
        [$column, $row] = array_map('intval', explode('x', $location));
        $map->set($row, $column, '#');

        try {
            find_path($map);
        } catch (Error $error) {
            $obstacles++;
        } finally {
            // reset
            $map->set($row, $column, '.');
        }
    }

    return $obstacles;
}

/**
 * @param Map $map
 * @return Array<string, string>
 */
function find_path (Map $map): array {
    // Find guard position
    ['row' => $row, 'column' => $column] = $map->find('^');

    // Mark as visited incl direction
    $visited = ["{$column}x{$row}" => '^'];

    // Moving up
    $direction = '^';
    $dRow = -1;
    $dCol = 0;

    $row = $row + $dRow;
    $column = $column + $dCol;

    // iterate while we're not out of bounds
    while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width) {
        $current = $map->get($row, $column);

        if ($current !== '#') {
            // No obstable, we can visit it!

            // But first let's see if we haven't visited it before
            if (str_contains($visited["{$column}x{$row}"] ?? '', $direction)) {
                throw new Error('We have already walked this!');
            }

            $visited["{$column}x{$row}"] = ($visited["{$column}x{$row}"] ?? '') . $direction;

            // Take next step
            $row = $row + $dRow;
            $column = $column + $dCol;
        } else {
            // We have found ourselves an obstacle!
            // take a step back
            $row = $row - $dRow;
            $column = $column - $dCol;

            // and rotate
            if ($dCol === 0 && $dRow === -1) { $dCol = 1; $dRow = 0; $direction = '>'; }
            elseif ($dCol === 1 && $dRow === 0) { $dCol = 0; $dRow = 1; $direction = 'v'; }
            elseif ($dCol === 0 && $dRow === 1) { $dCol = -1; $dRow = 0; $direction = '<'; }
            elseif ($dCol === -1 && $dRow === 0) { $dCol = 0; $dRow = -1; $direction = '^'; }
        }
    }

    return $visited;
}

const UP = 1;
const RIGHT = 2;
const DOWN = 4;
const LEFT = 8;

/**
 * In the original part 2 implementation we rebuilt the entire route from the start with the additional obstacle. That
 * means that the further we are in the path, we will rewalk a growing portion of the path. The optimization is that we
 * build the path following the logic of part 1 and whenever we encounter a non-obstacle position we put in an obstacle
 * and _continue the path from the point we introduced the obstacle_.
 *
 * Further optimizations are to reduce the weight of the operations;
 * - use numeric indices for the path
 * - use bitmasks for visited directions to reduce string operations even more
 *
 * @param Map $map
 * @return int
 */
function part_two_optimized(Map $map): int {
    // Find guard position
    ['row' => $row, 'column' => $column] = $map->find('^');

    /**
     * Instead of simply counting obstacles we want to keep track of the positions we have checked. My input - and I
     * expect all inputs do - contains a number of crossings where we pass the same position in different directions. In
     * those scenarios we don't need to check the position twice. Easiest way to do this is to use the same approach we
     * use for the paths; an array indexed with the position.
     *
     * @var array<int, bool> $obstacles
     */
    $obstacles = [$row * $map->width + $column => false];

    // Setup
    $direction = UP;
    $dRow = -1;
    $dCol = 0;

    /** @var array<int, int> $path */
    $path = [$row * $map->width + $column => $direction];

    do {
        // look at the next step
        $nextRow = $row + $dRow;
        $nextColumn = $column + $dCol;

        $next = $map->get($nextRow, $nextColumn);
        if ($next === '#') {
            // We cannot move there, we need to rotate!
            ['direction' => $direction, 'dCol' => $dCol, 'dRow' => $dRow] = rotate($direction);
        } else {
            // We can move there!
            // Or can we?!
            if ($next === null) {
                break;
            }

            // let's put in an obstacle!
            if (!isset($obstacles[$nextRow * $map->width + $nextColumn])) {
                $map->set($nextRow, $nextColumn, '*');
                $obstacles[$nextRow * $map->width + $nextColumn] = !is_valid_path($map, $row, $column, $direction, $path);
                $map->set($nextRow, $nextColumn, '.');
            }

            $row = $nextRow;
            $column = $nextColumn;
        }

        // add to path
        $path[$row * $map->width + $column] = ($path[$row * $map->width + $column] ?? 0) | $direction;
    } while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width);

    return count(array_filter($obstacles));
}

function rotate(int $direction): array {
    if ($direction === UP) return ['direction' => RIGHT, 'dCol' => 1, 'dRow' => 0];
    elseif ($direction === RIGHT) return ['direction' => DOWN, 'dCol' => 0, 'dRow' => 1];
    elseif ($direction === DOWN) return ['direction' => LEFT, 'dCol' => -1, 'dRow' => 0];
    elseif ($direction === LEFT) return ['direction' => UP, 'dCol' => 0, 'dRow' => -1];
    else
        throw new Error('Unknown direction: ' . $direction);
}

/**
 * @param Map $map
 * @param int $row
 * @param int $column
 * @param int $direction
 * @param array<int, int> $path
 * @return bool
 */
function is_valid_path(Map $map, int $row, int $column, int $direction, array $path): bool {
    // we now we need to rotate:
    ['direction' => $direction, 'dCol' => $dCol, 'dRow' => $dRow] = rotate($direction);

    do {
        // look at the next step
        $nextRow = $row + $dRow;
        $nextColumn = $column + $dCol;

        $next = $map->get($nextRow, $nextColumn);
        if ($next === '#' || $next === '*') {
            // We cannot move there, we need to rotate!
            ['direction' => $direction, 'dCol' => $dCol, 'dRow' => $dRow] = rotate($direction);
        } elseif ((($path[$nextRow * $map->width + $nextColumn] ?? 0) & $direction) === $direction) {
            // We have found a cycle!
            return false;
        } elseif ($next === null) {
            return true;
        } else {
            // We can move there
            $row = $nextRow;
            $column = $nextColumn;
        }

        // add to path
        $path[$row * $map->width + $column] = ($path[$row * $map->width + $column] ?? 0) | $direction;
    } while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width);

    return true; // we're out of bounds
}

//$sw->start();
//echo 'How many different positions could you choose for this obstruction? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

$sw->start();
echo 'How many different positions could you choose for this obstruction? ' . part_two_optimized($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
