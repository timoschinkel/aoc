<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
$height = count($rows);
$width = strlen($rows[0]);

class Point {
    public function __construct(
        public readonly int $column,
        public readonly int $row,
    ) {
    }

    public function manhattan(Point $other): Point {
        return new Point(
            abs($this->column - $other->column),
            abs($this->row - $other->row),
        );
    }

    public function __toString(): string {
        return "{$this->column}x{$this->row}";
    }
}

$points = [];

for ($row = 0; $row < $height; $row++) {
    for ($column = 0; $column < $width; $column++) {
        $value = $rows[$row][$column];
        if ($value !== '.' && $value !== '#') {
            if (!isset($points[$value])) {
                $points[$value] = [];
            }

            $points[$value][] = new Point($column, $row);
        }
    }
}

//die(var_dump(__CLASS__, __LINE__, $points, $points['a'][0]->manhattan($points['a'][1])));

$sw = new Stopwatch();

/**
 * This puzzle screams [Manhattan Distance](https://www.datacamp.com/tutorial/manhattan-distance). For every frequency
 * we calculate the manhattan distance between every combination of antennas. Then the trick is to determine to which
 * of the antennas we need to *add* and to *subtract* the manhattan distance. This is calculated per axis; we subtract
 * from the lowest and add to the highest.
 *
 * @param Array<string,Point[]> $points_per_frequency
 * @param int $width
 * @param int $height
 * @return int
 */
function part_one(array $points_per_frequency, int $width, int $height): int {
    $antinodes = [];

    foreach ($points_per_frequency as $frequency => $points) {
        // per frequency iterate over every combination of antennas
        for ($i = 0; $i < count($points); $i++) {
            for ($j = $i + 1; $j < count($points); $j++) {
                $distance = $points[$i]->manhattan($points[$j]);

                // subtract manhattan from one
                $one = new Point(
                    $points[$i]->column <= $points[$j]->column ? $points[$i]->column - $distance->column: $points[$i]->column + $distance->column,
                    $points[$i]->row <= $points[$j]->row ? $points[$i]->row - $distance->row: $points[$i]->row + $distance->row,
                );

                // add manhattan to the other
                $two = new Point(
                    $points[$i]->column > $points[$j]->column ? $points[$j]->column - $distance->column: $points[$j]->column + $distance->column,
                    $points[$i]->row > $points[$j]->row ? $points[$j]->row - $distance->row: $points[$j]->row + $distance->row,
                );

                if ($one->column >= 0 && $one->column < $width && $one->row >= 0 && $one->row < $height) {
                    $antinodes[] = $one;
                }

                if ($two->column >= 0 && $two->column < $width && $two->row >= 0 && $two->row < $height) {
                    $antinodes[] = $two;
                }
            }
        }
    }

    return count(array_unique($antinodes));
}

$sw->start();
echo 'How many unique locations within the bounds of the map contain an antinode? ' . part_one($points, $width, $height) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Instead of calculating two antinodes per antenna combination we simply need to continue "walking" in the "direction"
 * we found.
 *
 * @param Array<string,Point[]> $points_per_frequency
 * @param int $width
 * @param int $height
 * @return int
 */
function part_two(array $points_per_frequency, int $width, int $height): int {
    $antinodes = [];

    foreach ($points_per_frequency as $frequency => $points) {
        if (count($points) === 1) {
            // No resonance.
            continue;
        }

        // per frequency iterate over every combination of antennas
        for ($i = 0; $i < count($points); $i++) {
            for ($j = $i + 1; $j < count($points); $j++) {
                $distance = $points[$i]->manhattan($points[$j]);

                // One way
                $dCol = $points[$i]->column <= $points[$j]->column ? $distance->column * -1: $distance->column;
                $dRow = $points[$i]->row <= $points[$j]->row ? $distance->row * -1: $distance->row;
                $cCol = $points[$i]->column;
                $cRow = $points[$i]->row;

                while ($cCol >= 0 && $cCol < $width && $cRow >= 0 && $cRow < $height) {
                    $antinodes[] = new Point($cCol, $cRow);
                    $cCol += $dCol;
                    $cRow += $dRow;
                }

                // Or another 🎶
                $dCol = $points[$i]->column > $points[$j]->column ? $distance->column * -1: $distance->column;
                $dRow = $points[$i]->row > $points[$j]->row ? $distance->row * -1: $distance->row;
                $cCol = $points[$j]->column;
                $cRow = $points[$j]->row;

                while ($cCol >= 0 && $cCol < $width && $cRow >= 0 && $cRow < $height) {
                    $antinodes[] = new Point($cCol, $cRow);
                    $cCol += $dCol;
                    $cRow += $dRow;
                }
            }
        }
    }

    return count(array_unique($antinodes));
}

$sw->start();
echo 'How many unique locations within the bounds of the map contain an antinode? ' . part_two($points, $width, $height) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
