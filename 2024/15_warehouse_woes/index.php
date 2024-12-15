<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Stopwatch.php';

$input = __DIR__ . DIRECTORY_SEPARATOR . ($argv[1] ?? 'example') . '.txt';

$parts = array_filter(explode(PHP_EOL . PHP_EOL, file_get_contents($input)));
$rows = array_filter(explode(PHP_EOL, $parts[0]));
$instructions = trim(str_replace(PHP_EOL, '', $parts[1]));

// Read input
class Position {
    public function __construct(
        public readonly int $col,
        public readonly int $row,
    ) {  
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

    public function getByIndex(int $index): ?string {
        return $this->fields[$index] ?? null;
    }

    public function get(int $row, int $column): ?string {
        if ($row < 0 || $row >= $this->height || $column < 0 || $column >= $this->width) {
            return null; // out of bounds
        }

        return $this->fields[$row * $this->width + $column] ?? null;
    }

    public function set(int $index, string $char): void {
        $this->fields[$index] = $char;
    }

    public function draw(?int $highlight = null): void {
        for ($row = 0; $row < $this->height; $row++) {
            for ($col = 0; $col < $this->width; $col++) {
                if ($highlight === $row * $this->width + $col) {
                    echo "\e[43m{$this->fields[$row * $this->width + $col]}\e[0m";
                } else {
                    echo $this->fields[$row * $this->width + $col];
                }
            }
            echo PHP_EOL;
        }
        echo PHP_EOL;
    }

    public function widen(): Map {
        $chunks = [];
        for($r = 0; $r < $this->height; $r++) {
            $chunks[] = substr($this->fields, $r * $this->width, $this->width);
        }

        return new Map(
            array_map(fn(string $row): string => $this->double($row), $chunks)
        );
    }

    private function double(string $row): string {
        $doubled = '';
        for ($i = 0; $i < strlen($row); $i++) {
            if ($row[$i] === '#') $doubled .= '##';
            if ($row[$i] === 'O') $doubled .= '[]';
            if ($row[$i] === '.') $doubled .= '..';
            if ($row[$i] === '@') $doubled .= '@.';
        }

        return $doubled;
    }
}

$map = new Map($rows);
// $map->draw();

$sw = new Stopwatch();

function part_one(Map $map, string $instructions): int {
    $robot = $map->find('@');
    $robot = $robot->row * $map->width + $robot->col;

    $operations = [
        '^' => $map->width * -1,
        '>' => 1,
        'v' => $map->width,
        '<' => -1,
    ];

    for ($i = 0; $i < strlen($instructions); $i++) {        
        //echo 'Move: ' . $instructions[$i] . ': ' . PHP_EOL;
        $operation = $operations[$instructions[$i]];

        // check if we can take a step:
        if ($map->getByIndex($robot + $operation) === '#') {
            // no we cannot
        } elseif ($map->getByIndex($robot + $operation) === '.') {
            // yes we can
            $map->set($robot, '.');
            $robot += $operation;
            $map->set($robot, '@');
        } elseif ($map->getByIndex($robot + $operation) === 'O') {
            // check if we can push by finding an empty spot
            $next = $robot + $operation + $operation;
            while ($map->getByIndex($next) === 'O') {
                $next += $operation;
            }

            if ($map->getByIndex($next) === '.' ) {
                // yes, we can push!
                $map->set($next, 'O');
                $map->set($robot, '.');
                $robot += $operation;
                $map->set($robot, '@');
            }
        }

        //$map->draw();
    }
    
    // $map->draw();

    // Calculate score
    $score = 0;
    for($row = 0; $row < $map->height; $row++) {
        for ($col = 0; $col < $map->width; $col++) {
            if ($map->getByIndex($row * $map->width + $col) === 'O') {
                $score += $row * 100 + $col;
            }
        }
    }

    return $score;
}

$sw->start();
echo 'What is the sum of all boxes\' GPS coordinates? ' . part_one($map, $instructions) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;

function part_two(Map $map, string $instructions): int {
    // Widen the map
    $map = $map->widen();

    $robot = $map->find('@');
    $robot = $robot->row * $map->width + $robot->col;

    $operations = [
        '^' => $map->width * -1,
        '>' => 1,
        'v' => $map->width,
        '<' => -1,
    ];

    for ($i = 0; $i < strlen($instructions); $i++) {        
        $operation = $operations[$instructions[$i]];

        // check if we can take a step:
        if ($map->getByIndex($robot + $operation) === '#') {
            // no we cannot
        } elseif ($map->getByIndex($robot + $operation) === '.') {
            // yes we can
            $map->set($robot, '.');
            $robot += $operation;
            $map->set($robot, '@');
        } elseif (
            ($operation === 1 && $map->getByIndex($robot + $operation) === '[')
            || ($operation === -1 && $map->getByIndex($robot + $operation) === ']')) 
        { // moving horizontally, easier case
            $to_move = [
                $robot + $operation,
                $robot + $operation * 2
            ];

            // check if we can push by finding an empty spot
            $next = $robot + $operation * 3; // boxes are wide, so we can skip 2 positions
            while ($map->getByIndex($next) === '[' || $map->getByIndex($next) === ']') {
                $to_move[] = $next;
                $to_move[] = $next + $operation;

                $next += $operation * 2;                
            }

            if ($map->getByIndex($next) === '.' ) {
                // yes, we can push!

                // move boxes
                foreach (array_reverse($to_move) as $index) {
                    $map->set($index + $operation, $map->getByIndex($index));
                }

                // move robot
                $map->set($robot, '.');
                $robot += $operation;
                $map->set($robot, '@');
            }
        } elseif ($map->getByIndex($robot + $operation) === '[' || $map->getByIndex($robot + $operation) === ']') {
            // we've moving vertically
            $this_line = $to_move = $map->getByIndex($robot + $operation) === '['
                ? [$robot + $operation => '[', $robot + $operation + 1 => ']']
                : [$robot + $operation - 1 => '[', $robot + $operation => ']'];

            while ($can_move = can_move_vertically($map, $operation, $this_line)) {
                $this_line = move_vertically($map, $operation, $this_line);
                if (count($this_line) === 0) {
                    // nothing by empty spaces, so we can stop
                    break;
                }

                $to_move += $this_line;
            }

            if ($can_move === false) {
                // we could not move
                continue;                
            }

            // move the boxes
            foreach (array_reverse($to_move, true) as $position => $value) {
                $map->set($position, '.');
                $map->set($position + $operation, $value);
            }

            // move the robot
            $map->set($robot, '.');
            $robot += $operation;
            $map->set($robot, '@');
        }
    }

    // $map->draw($robot);

    // Calculate score
    $score = 0;
    for($row = 0; $row < $map->height; $row++) {
        for ($col = 0; $col < $map->width; $col++) {
            if ($map->getByIndex($row * $map->width + $col) === '[') {
                $score += $row * 100 + $col;
            }
        }
    }

    return $score;

    return 0;
}

function can_move_vertically(Map $map, int $operation, array $to_check): bool 
{
    foreach ($to_check as $position => $_) {
        if ($map->getByIndex($position + $operation) === '#') {
            return false;
        }
    }

    return true;
}

function move_vertically(Map $map, int $operation, array $to_check): array 
{
    $next = [];
    foreach ($to_check as $position => $_) {
        if ($map->getByIndex($position + $operation) === '[') {
            // $next[] = $position + $operation;
            // $next[] = $position + $operation + 1;
            $next[$position + $operation] = '[';
            $next[$position + $operation + 1] = ']';
        } elseif ($map->getByIndex($position + $operation) === ']') {
            // $next[] = $position + $operation - 1;
            // $next[] = $position + $operation;
            $next[$position + $operation - 1] = '[';
            $next[$position + $operation] = ']';
        }
    }

    return $next;
}

// reconstruct the map due to mutability
$map = new Map($rows);

$sw->start();
echo 'What is the sum of all boxes\' final GPS coordinates? ' . part_two($map, $instructions) . ' (' . $sw->ellapsed() . ')' . PHP_EOL;
