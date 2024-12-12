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
     * @param array<int, string> $fields
     */
    public function __construct(
        public array $fields,
    ) {
        $this->width = strlen($fields[0]);
        $this->height = count($this->fields);
    }

    public function get(int $row, int $column): ?string {
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

$sw->start();
echo 'What is the total price of fencing all regions on your map? ' . part_one($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

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

$sw->start();
echo 'What is the new total price of fencing all regions on your map? ' . part_two($map) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
