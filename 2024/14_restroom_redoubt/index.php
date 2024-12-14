<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$width = ($argv[1] ?? 'example') === 'example' ? 11 : 101;
$height = ($argv[1] ?? 'example') === 'example' ? 7 : 103;

$rows = array_filter(explode(PHP_EOL, file_get_contents($input)));

// Read input
class Robot {
    private function __construct(
        public int $column,
        public int $row,
        public readonly int $vColumn,
        public readonly int $vRow,
    ) {
    }

    public static function create(string $row): self
    {
        if (!preg_match('/^p=(?P<x>-?[0-9]+),(?P<y>-?[0-9]+) v=(?P<vx>-?[0-9]+),(?P<vy>-?[0-9]+)$/', $row, $matches)) {
            throw new RuntimeException('Invalid row value');
        }

        return new self((int) $matches['x'], (int) $matches['y'], (int) $matches['vx'], (int) $matches['vy']);
    }
}

$robots = array_map(fn(string $row): Robot => Robot::create($row), $rows);

$sw = new Stopwatch();

/**
 * Very straightforward; iterate over every robot 100 times and move them. After that divide them into quadrants.
 *
 * @param int $width
 * @param int $height
 * @param array<Robot> $robots
 * @return int
 */
function part_one(int $width, int $height, Robot ...$robots): int {
    /** @var array<int, array<Robot>> $positions */
    for ($i = 0; $i < 100; $i++) {
        foreach ($robots as $robot) {
            // move the robot
            $robot->column = ($robot->column + $robot->vColumn + $width) % $width;
            $robot->row = ($robot->row + $robot->vRow + $height) % $height;
        }
    }

    // divide in quadrants:
    $quadrants = ['NW' => 0, 'NE' => 0, 'SE' => 0, 'SW' => 0];
    foreach ($robots as $robot) {
        if ($robot->column < floor($width / 2) && $robot->row < floor($height / 2)) {
            $quadrants['NW'] += 1;
        } elseif ($robot->column > floor($width / 2) && $robot->row < floor($height / 2)) {
            $quadrants['NE'] += 1;
        } elseif ($robot->column > floor($width / 2) && $robot->row > floor($height / 2)) {
            $quadrants['SE'] += 1;
        } elseif ($robot->column < floor($width / 2) && $robot->row > floor($height / 2)) {
            $quadrants['SW'] += 1;
        }
    }

    return array_product($quadrants);
}

function draw(int $width, int $height, Robot ...$robots): void {
    $locations = array_fill(0, $height, array_fill(0, $width, 0));

    foreach ($robots as $robot) {
        $locations[$robot->row][$robot->column]++;
    }

    foreach ($locations as $y => $row) {
        foreach ($row as $x => $column) {
            $value = $column === 0 ? '.' : $column;

            if ($x < floor($width / 2) && $y < floor($height / 2)) {
                echo "\e[43m{$value}\e[0m";
            } elseif ($x > floor($width / 2) && $y < floor($height / 2)) {
                echo "\e[43m{$value}\e[0m";
            } elseif ($x < floor($width / 2) && $y > floor($height / 2)) {
                echo "\e[43m{$value}\e[0m";
            } elseif ($x > floor($width / 2) && $y > floor($height / 2)) {
                echo "\e[43m{$value}\e[0m";
            } else {
                echo $value;
            }
        }
        echo PHP_EOL;
    }

    echo PHP_EOL;
}

$sw->start();
echo 'What will the safety factor be after exactly 100 seconds have elapsed? ' . part_one($width, $height, ...$robots) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

/**
 * Okay, this one was tricky. Mostly because the puzzle does not specify how the tree looked. My initial ideas were that
 * the tree would use all robots, and so I tried to find an iteration that had no robot that was not connected to
 * another robot. That idea did not work. The second idea was that the drawing would be a mirror image over the x-axis.
 * This also was proven false as the eventual tree was off-center.
 *
 * I took the manual way by drawing the field, one iteration at a time and have a sleep between every iteration.
 * Eventually I found the tree. With the knowledge of how the tree looked I was able to make a programmatic approach;
 * after every iteration I check if there's a horizontal line of 30 or more characters. That is very likely the
 * iteration with the tree.
 *
 * @param int $width
 * @param int $height
 * @param array<Robot> $robots
 * @return int
 */
function part_two(int $width, int $height, Robot ...$robots): int {
    /** @var array<int, array<Robot>> $positions */
    for ($i = 0; $i <= 9000; $i++) {
        $locations = [];
        foreach ($robots as $robot) {
            // move the robot
            $robot->column = ($robot->column + $robot->vColumn + $width) % $width;
            $robot->row = ($robot->row + $robot->vRow + $height) % $height;

            $locations[$robot->row * $width + $robot->column] = true;
        }

        // No sure if this applies to all input, but I noticed that the iteration with the drawing did not have any
        // position that had multiple robots. I heard more people mention this, so I will add it as an optimization.
        if (count($locations) === count($robots) && is_drawing($width, $height, $locations)) {
            break;
        }
    }
    return $i + 1;
}

function is_drawing(int $width, int $height, array $locations): bool {
    // The drawing compiled out of a number of lines. Lines are not very common in a "random" flow. So we'll try to
    // detect horizontal lines of 30 or longer

    foreach ($locations as $location => $_) {
        $column = $left = $location % $width;
        $row = (int)floor($location / $width);

        $length = 1;
        while ($left > 0 && isset($locations[$row * $width + $left])) {
            $length++;
            $left--;
        }

        while ($column < $width && isset($locations[$row * $width + $column])) {
            $length++;
            $column++;
        }

        if ($length > 30) {
            return true;
        }
    }

    return false;
}

function draw_part_two(int $iteration, int $width, int $height, Robot ...$robots): void {
    echo "\x1B[2J"; // clear screen
    echo "\x1B[H"; // home

    echo 'Iteration ' . ($iteration + 1) . PHP_EOL;

    $locations = array_fill(0, $height, array_fill(0, $width, 0));

    foreach ($robots as $robot) {
        $locations[$robot->row][$robot->column]++;
    }

    foreach ($locations as $row) {
        foreach ($row as $column) {
            if ($column > 0) {
                echo "\e[43m{$column}\e[0m";
            } else {
                echo '.';
            }
        }
        echo PHP_EOL;
    }

    echo PHP_EOL;
}

// Reread the robots as we've been using mutable robot objects
$robots = array_map(fn(string $row): Robot => Robot::create($row), $rows);

$sw->start();
echo 'WWhat is the fewest number of seconds that must elapse for the robots to display the Easter egg? ' . part_two($width, $height, ...$robots) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
