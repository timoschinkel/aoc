<?php

declare(strict_types=1);

define('DEBUG', in_array('debug', $argv));
define('EXAMPLE', in_array('example', $argv));

$inputs = in_array('example', $argv)
    ? explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/example.txt')))
    : explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$distances = []; // memoization cache

$start = State::fromInput($inputs);

if (DEBUG) echo "Starting position" . PHP_EOL;
if (DEBUG) echo $start->dump() . PHP_EOL;

/*
 * State has property `energy`, and contains all amphipods (keys) and their positions (values)
 *
 * Position schematic
 * #############
 * #...........#            h1 h2 h3 h4 h5 h6 h7 h8 h9 h10 h11
 * ###B#C#B#D###                  a2    b2    c2    d2
 *   #A#D#C#A#                    a1    b1    c1    d1
 *   #########
 *
 * Part 1; We will need to find the most efficient way. This shouts BFS/DFS/Dijkstra/A*. For part 1 I went for a Depth
 * First Search. That means that the efficiency of the solution is dependent on the order in which we put options on
 * the stack. Because I read the data from A to D, and because D has the highest energy cost we first search through the
 * options for D. There are a couple of optimizations; Memoization of distances and moving to the correct burrow
 * as fast as possible. The solution now takes about 3 seconds. I think we need some more optimizations.
 *
 * Part 1b; And then I saw part 2. Maybe using `hx`, and `[abcd]x` was not the most ideal way of doing this. I have
 * rewritten the code to do two things:
 * - More functionality inside State; This will allow me to create another implementation for part 2 where I override
 *   some functions. This will keep my main loop simpler, and make it usable for both part 1 and part 2.
 * - Converted the `hx` and `[abcd]x` notation to coordinates using numeric values for x and y. With the knowledge that
 *   y = 0 is the hallway we can make all the assumptions that we have been making based on the first character of the
 *   position.
 *
 * Because of the way PHP works I have also removed the `implements JsonSerialize` from `State`, and replaced it with
 * `State::hash()`; It was forcing me to define the `State` class at the top of the file, where I want my main code to
 * reside.
 *
 * Position schematic
 * #############
 * #...........#            (0,0) (0,1) (0,2) (0,3) (0,4) (0,5) (0,6) (0,7) (0,8) (0,9) (0,10)
 * ###B#C#B#D###                        (1,2)       (1,4)       (1,6)       (1,8)
 *   #A#D#C#A#                          (2,2)       (2,4)       (2,6)       (2,8)
 *   #########
 *
 * Part 2; Same approach, but with extra amphipods. I have opted to create a second State object and specify a height.
 * With a number of adjustments in finding eligible positions.
 *
 * Post mortem; This solution is effectively a depth first search with some optimizations. Reddit suggests this is
 * done more efficient using a proper Dijkstra or A* implementation. This requires a priority queue instead of a stack.
 * I did not spend time to create an efficient priority queue in PHP.
 */

// Part 1
function getLeastEnergyToOrganizeAmphipods(State $start): int
{
    $visited = [];

    $min = PHP_INT_MAX; // basically infinity
    $minState = null;

    $states = new Stack($start);
    while ($states->count() > 0) {
        $current = $states->pop();

        // We have previously found a route with less energy to the $currentState, stop looking
        $hash = $current->hash();
        if (isset($visited[$hash]) && $current->energy() >= $visited[$hash]) {
            continue;
        }

        // Mark as visited with current energy consumption
        $visited[$hash] = $current->energy();

        if ($current->energy() > $min) {
            // There already is a more efficient solution, nice!
            continue;
        }

        // check if current state is the final state
        if ($current->isInFinalState()) {
            if ($current->energy() < $min) {
                if (DEBUG) echo "Found a new minimum: {$current->energy()}" . PHP_EOL;

                $min = $current->energy();
                $minState = $current;
            }

            continue; // get out of the loop
        }

        // Iterate over all amphipods
        foreach ($current->amphipods() as $amphipod => $position) {
            // Amphipod is already in a correct position, that means we do not have to worry about this amphipod anymore.
            if ($current->isInCorrectPosition($amphipod)) {
                // amphipod is already in the room where it should be, either because it is at the bottom or because
                // its sibling is below it.
                continue;
            }

            foreach ($current->getEligiblePositions($amphipod) as ['x' => $x, 'y' => $y]) {
                $next = $current->move($amphipod, $x, $y);
                if (isset($visited[$next->hash()]) && $visited[$next->hash()] < $next->energy()) {
                    // We have been in the new state before with less energy, skipping
                    continue;
                }

                $states->push($next);
            }
        }
    }

    if ($minState && DEBUG) {
//        foreach ($minState->history() as $history) {
//            echo $history['action'] . PHP_EOL;
//            echo (new State($history['energy'], $history['state']))->dump() . PHP_EOL;
//        }
        echo $minState->dump() . PHP_EOL;
    }

    return $min;
}

$stopwatch = hrtime(true);
$partOne = getLeastEnergyToOrganizeAmphipods($start);
$elapsedMilliseconds = round((hrtime(true) - $stopwatch)/1e+6, 0);
echo "What is the least energy required to organize the amphipods? {$partOne} ({$elapsedMilliseconds}ms)" . PHP_EOL;

// Part 2
$startTwo = PartTwoState::fromInput($inputs);
$stopwatch = hrtime(true);
$partTwo = getLeastEnergyToOrganizeAmphipods($startTwo);
$elapsedMilliseconds = round((hrtime(true) - $stopwatch)/1e+6, 0);
echo "What is the least energy required to organize the amphipods? {$partTwo} ({$elapsedMilliseconds}ms)" . PHP_EOL;

final class Stack
{
    private array $items;
    public function __construct(State ...$items)
    {
        $this->items = $items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function push(State $item): void
    {
        // Stack is first in, last out
        $this->items[] = $item;
    }

    public function pop(): State
    {
        // Stack is last in, first out
        return array_pop($this->items);
    }
}

class State
{
    private array $reverse;
    private array $history = [];

    protected $height = 2;

    public function __construct(
        private int $energy,
        private array $amphipods
    ) {
        $this->reverse = array_combine(
            array_map(fn(array $pos): string => $pos['x'] . 'x' . $pos['y'], array_values($this->amphipods)),
            array_keys($this->amphipods),
        );
    }

    public function hash(): string
    {
        return json_encode($this->amphipods);
    }

    public function energy(): int { return $this->energy; }
    public function amphipods(): array { return $this->amphipods; }

    public function move(string $amphipod, int $x, int $y): self
    {
        ['x' => $sx, 'y' => $sy] = $this->amphipods[$amphipod];

        $clone = clone $this;

        $distance = $this->getDistance(['x' => $sx, 'y' => $sy], ['x' => $x, 'y' => $y]);
        $energy = $distance * $this->getEnergyPerStep($amphipod);

        $clone->amphipods[$amphipod] = ['x' => $x, 'y' => $y];
        $clone->energy += $energy;

        unset($clone->reverse["{$sx}x{$sy}"]);
        $clone->reverse["{$x}x{$y}"] = $amphipod;

        $clone->history[] = [
            'action' => "Move {$amphipod} from ({$sx}, {$sy}) to ({$x}, {$y}) taking {$distance} steps and {$energy} energy",
            'state' => $clone->amphipods,
            'energy' => $clone->energy,
        ];

        return $clone;
    }

    /**
     * Walk into burrow. This assumes $x position is above burrow.
     * @param string $ampipod
     * @return int|null Null when burrow is not accessible
     */
    private function goIntoBurrow(string $amphipod, int $x): ?int
    {
        if ($this->getFromIndex($x, 1) !== null) {
            return null; // The top spot is already taken
        }

        for ($y = 2; $y <= $this->height; $y++) {
            $neighbor = $this->getFromIndex($x, $y);
            if ($neighbor === null) {
                continue;
            }

            if ($neighbor[0] !== $amphipod[0]) {
                return null; // wrong amphipod in this burrow
            }

            if ($this->isInCorrectPosition($neighbor)) {
                return $y - 1;
            } else {
                return null;
            }
        }

        // We've made it so far, y is now $this->height + 1:
        return $y - 1;




//        if ($this->getFromIndex($x, 1) === null &&
//            ($this->getFromIndex($x, 2) === null || $this->getFromIndex($x, 2)[0] === $amphipod[0])) {
//            return $this->getFromIndex($x, 2) === null ? 2 : 1;
//        }
//
//        return null;
    }

    public function getEligiblePositions(string $amphipod): array
    {
        $eligible = [];

        ['x' => $ax, 'y' => $ay] = $this->amphipods[$amphipod];
        $target = self::x($amphipod); // if we find this, we can exit early

        // Walk up towards the hall
        for ($y = $ay - 1; $y >= 0; $y--) {
            if ($this->getFromIndex($ax, $y) !== null) {
                // The path to the hallway is blocked, early exit:
                return [];
            }
        }

        /*
         * 0    1   2   3   4   5   6   7   8   9   10
         *          2       4       6       8
         */

        // Walk left and right in the hallway. If we start at y = 0 we _know_ that we can only go into the target
        // burrow, so we only need to walk either left or right.
        $left = $ay > 0 || $target < $ax;
        $right = $ay > 0 || $target > $ax;

        for ($dx = 1; $dx <= max($ax, 10 - $ax); $dx++) {
            // left
            $x = $ax - $dx;
            if ($left) {
                if ($x < 0 || $this->getFromIndex($x, 0) !== null) {
                    $left = false; // out of bounds OR we have found a blocking amphipod
                    goto right;
                }
                if ($target < $ax && $x === $target) {
                    // We can reach the target burrow, let's see if we can get in there
                    if ($y = $this->goIntoBurrow($amphipod, $target)) {
                        return [['x' => $target, 'y' => $y]];
                    }
                    if ($ay === 0) {  // we started in the hallway, we have passed the $target, let's stop looking
                        $left = false;
                    }
                }
                if ($ay > 0 && ($x % 2 == 1 || $x == 0)) {
                    $eligible[] = ['x' => $x, 'y' => 0];
                }
            }

            // right
            right:
            if ($right) {
                $x = $ax + $dx;
                if ($x > 10 || $this->getFromIndex($x, 0) !== null) {
                    $right = false; // out of bounds OR we have found a blocking amphipod
                    continue;
                }
                if ($target > $ax && $x === $target) {
                    // We can reach the target burrow, let's see if we can get in there
                    if ($y = $this->goIntoBurrow($amphipod, $target)) {
                        return [['x' => $target, 'y' => $y]];
                    }
                    if ($ay === 0) {  // we started in the hallway, we have passed the $target, let's stop looking
                        $right = false;
                    }
                }
                if ($ay > 0 && ($x % 2 == 1 || $x == 10)) {
                    $eligible[] = ['x' => $x, 'y' => 0];
                }
            }
        }

        // Favor the shortest distance over x
        usort($eligible, fn(array $one, array $another): int => abs($another['x'] - $ax) - abs($one['x'] - $ax));

        return $eligible;
    }

    public function isInFinalState(): bool
    {
        foreach (array_keys($this->amphipods) as $amphipod) {
            if ($this->isInCorrectPosition($amphipod) === false) {
                return false;
            }
        }

        return true;
    }

    protected static function x(string $amphipod): int
    {
        return match($amphipod[0]) {
            'A' => 2,
            'B' => 4,
            'C' => 6,
            'D' => 8,
            default => throw new Exception('Unexpected Amphipod received')
        };
    }

    private array $locked = [];
    public function isInCorrectPosition($amphipod): bool
    {
        if (in_array($amphipod, $this->locked)) return true;

        ['x' => $x, 'y' => $y] = $this->amphipods[$amphipod];
        if (self::x($amphipod) !== $x || $y === 0) {
            // In wrong burrow or in hallway
            return false;
        }

        // We're in the correct burrow

        if ($y === $this->height) { // We are at the bottom or the burrow
            $this->locked[] = $amphipod;
            return true;
        }

        $neighbor = $this->getFromIndex($x, $y + 1);
        if ($this->isInCorrectPosition($neighbor)) {
            $this->locked[] = $amphipod;
            return true;
        }
        return false;
    }

    public function history(): array { return $this->history; }

    private function getDistance(array $start, array $end): int
    {
        global $distances;
        ['x' => $sx, 'y' => $sy] = $start;
        ['x' => $ex, 'y' => $ey] = $end;

        $hash = "{$sx}x{$sy}-{$ex}x{$ey}";
        $rhash = "{$ex}x{$ey}-{$sx}x{$sy}"; // reverse hash
        if (isset($distances[$hash])) return $distances[$hash];
        if (isset($distances[$rhash])) return $distances[$rhash];

        $distance = $sy            // to hallway
            + abs($sx - $ex)  // to location in hallway
            + $ey;                 // into burrow

        $distances[$hash] = $distance;
        return $distances[$rhash] = $distance;
    }

    private function getEnergyPerStep(string $amphipod): int
    {
        return match($amphipod[0]) {
            'A' => 1,
            'B' => 10,
            'C' => 100,
            'D' => 1000,
            default => throw new Exception("Unknown amphipod {$amphipod}")
        };
    }

    protected function getFromIndex(int $x, int $y, ?string $default = null): ?string
    {
        return $this->reverse["{$x}x{$y}"] ?? $default;
    }

    public function dump(): string
    {
        $get = fn(int $x, int $y): string => substr($this->getFromIndex($x, $y, '.'), 0, 1);

        $str = 'Energy: ' . $this->energy . PHP_EOL;
        $str .= '#############' . PHP_EOL;
        $str .= '#' . (join('', array_map(fn(int $x): string => $get($x, 0), range(0, 10)))) . '#' . PHP_EOL;
        $str .= "###{$get(2, 1)}#{$get(4, 1)}#{$get(6, 1)}#{$get(8, 1)}###" . PHP_EOL;
        for ($y = 2; $y <= $this->height; $y++) {
            $str .= "  #{$get(2, $y)}#{$get(4, $y)}#{$get(6, $y)}#{$get(8, $y)}#" . PHP_EOL;
        }
        $str .= '  #########' . PHP_EOL;

        return $str;
    }

    public static function fromInput(array $input): static
    {
        $A = 1; $B = 1; $C = 1; $D = 1;
        $amphipods = [];
        for($y = 1; $y <= 2; $y++) {
            for ($x = 2; $x <= 8; $x += 2) {
                $amphipod = $input[$y + 1][$x + 1];
                if ($amphipod !== '.') {
                    $amphipods[$amphipod . $$amphipod++] = ['x' => $x, 'y' => $y];
                }
            }
        }
        ksort($amphipods);

        return new static(0, $amphipods);
    }
}

final class PartTwoState extends State
{
    protected $height = 4;

    public static function fromInput(array $input): static
    {
        // Insert additional rows
        $input = array_merge(
            array_slice($input, 0, 3),
            [
                '  #D#C#B#A#',
                '  #D#B#A#C#',
            ],
            array_slice($input, 3)
        );

        $A = 1; $B = 1; $C = 1; $D = 1;
        $amphipods = [];
        for($y = 1; $y <= 4; $y++) {
            for ($x = 2; $x <= 8; $x += 2) {
                $amphipod = $input[$y + 1][$x + 1];
                if ($amphipod !== '.') {
                    $amphipods[$amphipod . $$amphipod++] = ['x' => $x, 'y' => $y];
                }
            }
        }
        ksort($amphipods);

        return new static(0, $amphipods);
    }
}
