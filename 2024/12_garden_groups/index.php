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

    public function index(int $width): int {
        return $this->row * $width + $this->column;
    }
}

class Map {
    public readonly int $width;
    public readonly int $height;

    /**
     * @param array<int, string> $fields
     */
    public function __construct(
        public array $fields,
    ) {
        $this->width = strlen($fields[0]);
        $this->height = count($this->fields);
    }

    public function get(int $row, int $column): ?string {
        if ($column < 0 || $column >= $this->width || $row < 0 || $row >= $this->height) {
            return null;
        }

        return $this->fields[$row][$column] ?? null;
    }
}

// Read input
$map = new Map($rows);

$sw = new Stopwatch();

/**
 * My first instinct is flood fill; iterate over all available plots, perform a flood fill, calculate the fence cost,
 * and remove the plots in the region from the list plots to iterate. It is not fast, but for the first star it will
 * get the job done.
 *
 * @param Map $map
 * @return int
 */
function part_one(Map $map): int {
    $plots = [];
    for($row = $map->height - 1; $row >= 0; $row--) {
        for ($column = $map->width - 1; $column >= 0; $column--) {
            $plots[] = new Position($column, $row);
        }
    }

    $price = 0;

    // Iterate over all available plots
    while (count($plots) > 0) {
        $region = [];
        $plot = array_pop($plots);
        $value = $map->get($plot->row, $plot->column);

        // Flood fill from this plot
        $stack = [$plot];
        while (count($stack) > 0) {
            $current = array_pop($stack);

            $region[] = $current;
            if ($current->row >= 1 && !in_array($up = new Position($current->column, $current->row - 1), $region) && !in_array($up, $stack) && $map->get($current->row - 1, $current->column) === $value) $stack[] = $up;
            if ($current->column <= $map->width - 2 && !in_array($right = new Position($current->column + 1, $current->row), $region) && !in_array($right, $stack) && $map->get($current->row, $current->column + 1) === $value) $stack[] = $right;
            if ($current->row <= $map->height - 2 && !in_array($down = new Position($current->column, $current->row + 1), $region) && !in_array($down, $stack) && $map->get($current->row + 1, $current->column) === $value) $stack[] = $down;
            if ($current->column >= 1 && !in_array($left = new Position($current->column - 1, $current->row), $region) && !in_array($left, $stack) && $map->get($current->row, $current->column - 1) === $value) $stack[] = $left;

        }

        // Found a region, we can calculate stuff
        $price += price($region);

        // Remove plots in the region from the plots to check
        $plots = array_diff($plots, $region);
    }

    return $price;
}

function price(array $region): int {
    $area = count($region);
    $perimeter = 0;
    foreach ($region as $plot) {
        if (!in_array(new Position($plot->column, $plot->row - 1), $region)) $perimeter++;
        if (!in_array(new Position($plot->column + 1, $plot->row), $region)) $perimeter++;
        if (!in_array(new Position($plot->column, $plot->row + 1), $region)) $perimeter++;
        if (!in_array(new Position($plot->column - 1, $plot->row), $region)) $perimeter++;
    }

    return $area * $perimeter;
}

/**
 * A couple of optimizations:
 * - Use indexes for faster access; a combination of column and row can be condensed into a single value by the
 *      calculation index = (row * width) + column. By using those as key we don't need to use `in_array()`, which
 *      will require a search operation on the array.
 * - Build of the list of plots to check dynamically instead of starting with a stack filled with all possible plots.
 * - Count fences as we perform our flood fill; in a flood fill algorithm we (should) visit every plot exactly once,
 *      which means that if we check the neighbors - which we already need to do - we can determine if they have fences.
 *
 * @param Map $map
 * @return int
 */
function part_one_optimized(Map $map): int {
    /** @var array<int, Position> $to_check */
    $to_check = [0 => new Position(0, 0)];

    // We need to keep track of the positions we already handled, now we are in an infinite loop...
    /** @var array<int, true> $checked */
    $checked = [];

    $costs = 0;

    while (count($to_check) > 0) {
        // array_pop works from the back, and is a lot faster than array_shift(). For the algorithm it does not matter
        // if we read from the start of from the end.
        $start = array_pop($to_check);
        if (isset($checked[$start->index($map->width)])) {
            // This plot was already checked
            continue;
        }

        $value = $map->get($start->row, $start->column);

        /** @var array<int, Position> $region */
        $region = [];

        /** @var array<int, Position> $stack */
        $stack = [$start->index($map->width) => $start];

        $fences = 0;

        // From $start we perform a flood fill
        while (count($stack) > 0) {
            $current = array_pop($stack);
            $index = $current->index($map->width);

            // add to current region:
            $region[$index] = $current;

            // Remove from to_check, if it is already on there...
            unset($to_check[$index]);
            $checked[$index] = true;

            foreach ([
                'north' => ['row' => -1, 'col' => 0, 'delta' => -1 * $map->width],
                'east' => ['row' => 0, 'col' => 1, 'delta' => 1],
                'south' => ['row' => 1, 'col' => 0, 'delta' => $map->width],
                'west' => ['row' => 0, 'col' => -1, 'delta' => -1],
            ] as $direction => ['row' => $dRow, 'col' => $dCol, 'delta' => $delta]) {
                $neighbor = $map->get($current->row + $dRow, $current->column + $dCol);

                if ($neighbor === null) {
                    // out of bounds, we have found a fence!
                    $fences++;
                    continue;
                }

                if ($neighbor !== $value) {
                    // not the same plant, we have found a fence!
                    $fences++;

                    if (!isset($to_check[$index + $delta])) {
                        // add neighbor to $to_check
                        $to_check[$index + $delta] = new Position($current->column + $dCol, $current->row + $dRow);
                    }

                    continue;
                }

                // neighbor has the same value!
                if (!isset($stack[$index + $delta]) && !isset($region[$index + $delta])) {
                    // neighbor is a new plot, add to stack for inspection
                    $stack[$index + $delta] = new Position($current->column + $dCol, $current->row + $dRow);
                }
            }
        }

        $costs += $fences * count($region);
    }

    return $costs;
}

$sw->start();
echo 'What is the total price of fencing all regions on your map? ' . part_one_optimized($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * The only change with part 1 is the way we calculate the price. Important insight is that the number of sides is
 * always equal to the number of corners. Finding the corners is mostly pattern matching.
 *
 * @param Map $map
 * @return int
 */
function part_two(Map $map): int {
    $plots = [];
    for($row = $map->height - 1; $row >= 0; $row--) {
        for ($column = $map->width - 1; $column >= 0; $column--) {
            $plots[] = new Position($column, $row);
        }
    }

    $price = 0;

    // Iterate over all available plots
    while (count($plots) > 0) {
        $region = [];
        $plot = array_pop($plots);
        $value = $map->get($plot->row, $plot->column);

        // Flood fill from this plot
        $stack = [$plot];
        while (count($stack) > 0) {
            $current = array_pop($stack);

            $region[] = $current;
            if ($current->row >= 1 && !in_array($up = new Position($current->column, $current->row - 1), $region) && !in_array($up, $stack) && $map->get($current->row - 1, $current->column) === $value) $stack[] = $up;
            if ($current->column <= $map->width - 2 && !in_array($right = new Position($current->column + 1, $current->row), $region) && !in_array($right, $stack) && $map->get($current->row, $current->column + 1) === $value) $stack[] = $right;
            if ($current->row <= $map->height - 2 && !in_array($down = new Position($current->column, $current->row + 1), $region) && !in_array($down, $stack) && $map->get($current->row + 1, $current->column) === $value) $stack[] = $down;
            if ($current->column >= 1 && !in_array($left = new Position($current->column - 1, $current->row), $region) && !in_array($left, $stack) && $map->get($current->row, $current->column - 1) === $value) $stack[] = $left;

        }

        // Found a region, we can calculate stuff
        $price += price_part_two($region);

        // Remove plots in the region from the plots to check
        $plots = array_diff($plots, $region);
    }

    return $price;
}

function price_part_two(array $region): int {
    $area = count($region);

    $corners = 0;
    foreach ($region as $plot) {
        // NW N NE
        // W  x  E
        // SW S SE

        $nw = in_array(new Position($plot->column - 1, $plot->row - 1), $region);
        $n = in_array(new Position($plot->column, $plot->row - 1), $region);
        $ne = in_array(new Position($plot->column + 1, $plot->row - 1), $region);
        $w = in_array(new Position($plot->column - 1, $plot->row), $region);
        $e = in_array(new Position($plot->column + 1, $plot->row), $region);
        $sw = in_array(new Position($plot->column - 1, $plot->row + 1), $region);
        $s = in_array(new Position($plot->column, $plot->row + 1), $region);
        $se = in_array(new Position($plot->column + 1, $plot->row + 1), $region);

        // left-top and right-top outer corners
        if (!$w && !$n) $corners++;
        if (!$e && !$n) $corners++;

        // left-top and right-top inner corners
        if ($n && $w && !$nw) $corners++;
        if ($n && $e && !$ne) $corners++;

        // left-bottom and right-bottom inner corners
        if ($s && $w && !$sw) $corners++;
        if ($s && $e && !$se) $corners++;

        // bottom-left and bottom-right outer corners
        if (!$w && !$s) $corners++;
        if (!$e && !$s) $corners++;
    }

    return $area * $corners;
}

/**
 * The same optimization as applied in `part_one_optimized()`, with the difference that for every plot that we visit we
 * don't check for fences, but we check if the plot is a corner. Since we need to check all 8 neighbors in this
 * approach I opted to do this for every plot in the region when the region is completed.
 *
 * @param Map $map
 * @return int
 */
function part_two_optimized(Map $map): int {
    /** @var array<int, Position> $to_check */
    $to_check = [0 => new Position(0, 0)];

    // We need to keep track of the positions we already handled, now we are in an infinite loop...
    /** @var array<int, true> $checked */
    $checked = [];

    $costs = 0;

    while (count($to_check) > 0) {
        // array_pop works from the back, and is a lot faster than array_shift(). For the algorithm it does not matter
        // if we read from the start of from the end.
        $start = array_pop($to_check);
        if (isset($checked[$start->index($map->width)])) {
            // This plot was already checked
            continue;
        }

        $value = $map->get($start->row, $start->column);

        /** @var array<int, Position> $region */
        $region = [];

        /** @var array<int, Position> $stack */
        $stack = [$start->index($map->width) => $start];

        // From $start we perform a flood fill
        while (count($stack) > 0) {
            $current = array_pop($stack);
            $index = $current->index($map->width);

            // add to current region:
            $region[$index] = $current;

            // Remove from to_check, if it is already on there...
            unset($to_check[$index]);
            $checked[$index] = true;

            foreach ([
                         'north' => ['row' => -1, 'col' => 0, 'delta' => -1 * $map->width],
                         'east' => ['row' => 0, 'col' => 1, 'delta' => 1],
                         'south' => ['row' => 1, 'col' => 0, 'delta' => $map->width],
                         'west' => ['row' => 0, 'col' => -1, 'delta' => -1],
                     ] as $direction => ['row' => $dRow, 'col' => $dCol, 'delta' => $delta]) {
                $neighbor = $map->get($current->row + $dRow, $current->column + $dCol);

                if ($neighbor === null) {
                    // out of bounds
                    continue;
                }

                if ($neighbor !== $value) {
                    // not the same plant
                    if (!isset($to_check[$index + $delta])) {
                        // add neighbor to $to_check
                        $to_check[$index + $delta] = new Position($current->column + $dCol, $current->row + $dRow);
                    }

                    continue;
                }

                // neighbor has the same value!
                if (!isset($stack[$index + $delta]) && !isset($region[$index + $delta])) {
                    // neighbor is a new plot, add to stack for inspection
                    $stack[$index + $delta] = new Position($current->column + $dCol, $current->row + $dRow);
                }
            }
        }

        $costs += count_corners($map, $region) * count($region);
    }

    return $costs;
}

function count_corners(Map $map, array $region): int {
    $corners = 0;

    foreach ($region as $index => $plot) {
        // NW N NE
        // W  x  E
        // SW S SE

        $nw = isset($region[$index - $map->width - 1]);
        $n = isset($region[$index - $map->width]);
        $ne = isset($region[$index - $map->width + 1]);
        $w = isset($region[$index - 1]);
        $e = isset($region[$index + 1]);
        $sw = isset($region[$index + $map->width - 1]);
        $s = isset($region[$index + $map->width]);
        $se = isset($region[$index + $map->width + 1]);

        // left-top and right-top outer corners
        if (!$w && !$n) $corners++;
        if (!$e && !$n) $corners++;

        // left-top and right-top inner corners
        if ($n && $w && !$nw) $corners++;
        if ($n && $e && !$ne) $corners++;

        // left-bottom and right-bottom inner corners
        if ($s && $w && !$sw) $corners++;
        if ($s && $e && !$se) $corners++;

        // bottom-left and bottom-right outer corners
        if (!$w && !$s) $corners++;
        if (!$e && !$s) $corners++;
    }

    return $corners;
}

$sw->start();
echo 'What is the new total price of fencing all regions on your map? ' . part_two_optimized($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
