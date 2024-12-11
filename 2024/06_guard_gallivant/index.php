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

/**
 * In the original part 2 implementation we rebuilt the entire route from the start with the additional obstacle. That
 * means that the further we are in the path, we will rewalk a growing portion of the path. The optimization is that we
 * build the path following the logic of part 1 and whenever we encounter a non-obstacle position we put in an obstacle
 * and _continue the path from the point we introduced the obstacle_.
 *
 * @param Map $map
 * @return int
 */
function part_two_optimized(Map $map): int {
    // Find guard position
    ['row' => $row, 'column' => $column] = $map->find('^');

    // Keep track of the locations we already tried
    $obstacles = ["{$column}x{$row}" => false];

    // Setup
    $direction = '^';
    $dRow = -1;
    $dCol = 0;

    /** @var array<string, string> $path */
    $path = ["{$column}x{$row}" => $direction];

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
            if (!isset($obstacles["{$nextColumn}x{$nextRow}"])) {
                $map->set($nextRow, $nextColumn, '*');
                $obstacles["{$nextColumn}x{$nextRow}"] = !is_valid_path($map, $row, $column, $direction, $path);
                $map->set($nextRow, $nextColumn, '.');
            }

            $row = $nextRow;
            $column = $nextColumn;
        }

        // add to path
        $path["{$column}x{$row}"] = ($path["{$column}x{$row}"] ?? '') . $direction;
    } while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width);

    return count(array_filter($obstacles));
}

function rotate(string $direction): array {
    if ($direction === '^') return ['direction' => '>', 'dCol' => 1, 'dRow' => 0];
    elseif ($direction === '>') return ['direction' => 'v', 'dCol' => 0, 'dRow' => 1];
    elseif ($direction === 'v') return ['direction' => '<', 'dCol' => -1, 'dRow' => 0];
    elseif ($direction === '<') return ['direction' => '^', 'dCol' => 0, 'dRow' => -1];
    else
        throw new Error('Unknown direction: ' . $direction);
}

/**
 * @param Map $map
 * @param int $row
 * @param int $column
 * @param string $direction
 * @param array<string, string> $path
 * @return bool
 */
function is_valid_path(Map $map, int $row, int $column, string $direction, array $path): bool {
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
        } elseif (strpos($path["{$nextColumn}x{$nextRow}"] ?? '', $direction) !== false) {
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
        $path["{$column}x{$row}"] = ($path["{$column}x{$row}"] ?? '') . $direction;
    } while ($row >= 0 && $row < $map->height && $column >= 0 && $column < $map->width);

    return true; // we're out of bounds
}

//$sw->start();
//echo 'How many different positions could you choose for this obstruction? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

$sw->start();
echo 'How many different positions could you choose for this obstruction? ' . part_two_optimized($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
