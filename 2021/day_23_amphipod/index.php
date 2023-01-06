<?php

declare(strict_types=1);

define('DEBUG', in_array('debug', $argv));
define('EXAMPLE', in_array('example', $argv));

$inputs = in_array('example', $argv)
    ? explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/example.txt')))
    : explode(PHP_EOL, trim(file_get_contents(__DIR__ . '/input.txt')));

$distances = []; // memoization cache
final class State implements JsonSerializable
{
    private array $history = [];

    public function __construct(
        private int $energy,
        private array $amphipods
    ) {
    }

    public function energy(): int { return $this->energy; }
    public function amphipods(): array { return $this->amphipods; }

    public function move(string $amphipod, string $destination): self
    {
        $start = $this->amphipods[$amphipod];

        $clone = clone $this;
        $clone->history[] = [
            'energy' => $this->energy,
            'amphipod' => $amphipod,
            'start' => $start,
            'destination' => $destination,
            'state' => $this->amphipods,
        ];

        $distance = $this->getDistance($start, $destination);
        $energy = $distance * $this->getEnergyPerStep($amphipod);

        $clone->amphipods[$amphipod] = $destination;
        $clone->energy += $energy;
        return $clone;
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

    private array $locked = [];
    public function isInCorrectPosition($amphipod): bool
    {
        if (in_array($amphipod, $this->locked)) return true;

        $position = $this->amphipods[$amphipod];
        if ($position[0] !== strtolower($amphipod[0])) {
            return false;
        }

        // We're in the correct burrow

        if ($position[1] === '1') {
            $this->locked[] = $amphipod;
            return true;
        }

        // Okay, we're in the "top" spot of a burrow, check "below"
        $sibling = $amphipod[0] . ($amphipod[1] === '1' ? '2' : '1');
        $siblingPosition =  strtolower($amphipod[0]) . '1';
        if ($this->amphipods[$sibling] !== $siblingPosition) {
            return false;
        }

        // We are apparently in a valid position
        $this->locked[] = $amphipod;
        return true;
    }

    public function history(): array { return $this->history; }

    public function jsonSerialize()
    {
        return $this->amphipods;
    }

    public function getDistance(string $start, string $end): int
    {
        global $distances;
        if (isset($distances["{$start}-{$end}"])) return $distances["{$start}-{$end}"];
        if (isset($distances["{$end}-{$start}"])) return $distances["{$end}-{$start}"];

        $distance = 0;

        // We have the following flavors:
        // - from [abcd]x to [abcd]y
        // - from [abcd]x to hy
        // - from hx to [abcd]y

        if ($start[0] === $end[0]) { // a1 to a2 or a2 to a1, should not happen
            return $distances["{$start}-{$end}"] = abs(intval($start[1]) - intval($end[1]));
        }

        if ($start[0] !== 'h') {
            // get out of burrow first
            $distance += 3 - intval($start[1]);
            $x = match ($start[0]) {
                'a' => 3,   // 1-based
                'b' => 5,
                'c' => 7,
                'd' => 9,
                default => throw new Exception('Unexpected start position')
            };
        } else {
            // we _are_ starting on hx
            $x = intval(substr($start, 1));
        }

        if ($end[0] !== 'h') {
            // move towards burrow
            $distance += abs($x - match ($end[0]) {
                'a' => 3,   // 1-based
                'b' => 5,
                'c' => 7,
                'd' => 9,
                default => throw new Exception('Unexpected start position')
            });
            $distance += 3 - intval($end[1]);
        } else {
            $distance += abs($x - intval(substr($end, 1)));
        }

        return $distances["{$start}-{$end}"] = $distance;
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
}

// Read input
$A = 1; $B = 1; $C = 1; $D = 1;
function get(string $v): string
{
    global $A, $B, $C, $D;
    if ($v === '.') return '.';

    return $v . $$v++;
}

$start = array_flip(array_filter(array_merge(
    array_reduce(range(1, 11), fn(array $carry, int $index) => array_merge($carry, ["h{$index}" => get($inputs[1][$index])]), []),
    [
        'a1' => get($inputs[3][3]), 'a2' => get($inputs[2][3]),
        'b1' => get($inputs[3][5]), 'b2' => get($inputs[2][5]),
        'c1' => get($inputs[3][7]), 'c2' => get($inputs[2][7]),
        'd1' => get($inputs[3][9]), 'd2' => get($inputs[2][9]),
    ]
), fn(string $value) => $value !== '.'));
ksort($start);
$start = new State(0, $start);

if (DEBUG) echo "Starting position" . PHP_EOL;
dump($start);

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
 */

// Part 1
function isPathClear(int $start, int $end, array $currentState): bool
{
    $flipped = array_flip($currentState);
    if ($start < $end) {
        for ($i = $start + 1; $i <= $end; $i++) {
            if (isset($flipped["h{$i}"])) {
                // there's an amphipod!
                return false;
            }
        }
        return true;
    }

    for ($i = $start - 1; $i >= $end; $i--) {
        if (isset($flipped["h{$i}"])) {
            // there's an amphipod!
            return false;
        }
    }
    return true;
}

function getLeastEnergyToOrganizeAmphipods(State $start): int
{
    $visited = [];

    $min = PHP_INT_MAX; // basically infinity
    $minState = null;

    $states = new Stack($start); // use Stack for DFS
    while ($states->count() > 0) {
        $current = $states->pop();

        // We have previously found a route with less energy to the $currentState, stop looking
        if (isset($visited[json_encode($current)]) && $current->energy() >= $visited[json_encode($current)]) {
            continue;
        }

        // Mark as visited with current energy consumption
        $visited[json_encode($current)] = $current->energy();

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
                if (DEBUG) echo "{$amphipod} is already in the correct location, skipping..." . PHP_EOL;
                continue;
            }

            // Check possible locations
            if ($position[0] === 'h') {
                // We can only go to the designated burrow, but only under certain conditions

                $x = match ($amphipod[0]) {
                    'A' => 3, // 1-indexed
                    'B' => 5,
                    'C' => 7,
                    'D' => 9
                };

                if (isPathClear(intval(substr($position, 1)), $x, $current->amphipods()) === false) {
                    continue;
                }

                $destination1 = strtolower($amphipod[0]) . '1';
                $destination2 = strtolower($amphipod[0]) . '2';

                $occupant1 = array_search($destination1, $current->amphipods()) ?: '.';
                $occupant2 = array_search($destination2, $current->amphipods()) ?: '.';

                if ($occupant2 === '.') {
                    if ($occupant1 === '.') {
                        // move to $destination1
                        if (DEBUG) echo "Moving {$amphipod} from {$position} to {$destination1}" . PHP_EOL;
                        $states->push($current->move($amphipod, $destination1));
                    } elseif ($occupant1[0] === $amphipod[0]) {
                        // move to $destination2
                        if (DEBUG) echo "Moving {$amphipod} from {$position} to {$destination2}" . PHP_EOL;
                        $states->push($current->move($amphipod, $destination2));
                    }
                }

                continue;
            }

            if (in_array($position[0], ['a', 'b', 'c', 'd'])) {
                // We are in a burrow, but not our own burrow - or there's a different type of amphipod below us. Anyway we
                // need to move to the hallway. We can choose from multiple positions in the hallway: 0, 1, 3, 5, 7, 9, 10
                $flipped = array_flip($current->amphipods()); // flip for easier access

                if ($position[1] === '1' && isset($flipped[str_replace('1', '2', $position)])) {
                    // We are below a different amphipod, so we cannot move
                    if (DEBUG) echo "Amphipod {$amphipod} is locked in" . PHP_EOL;
                    continue;
                }

                $x = match ($position[0]) {
                    'a' => 3, // 1-indexed
                    'b' => 5,
                    'c' => 7,
                    'd' => 9
                };

                // Optimization: can go directly to final position?
                $dest = strtolower($amphipod[0]);
                $destX = match ($dest) {
                    'a' => 3, // 1-indexed
                    'b' => 5,
                    'c' => 7,
                    'd' => 9
                };

                if (!isset($flipped["{$dest}1"]) && !isset($flipped["{$dest}2"]) && isPathClear($x, $destX, $current->amphipods())) {
                    if (DEBUG) echo "Move {$amphipod} directly from {$position} to {$dest}1" . PHP_EOL;
                    $states->push($current->move($amphipod, "{$dest}1"));
                    continue;
                }

                if (isset($flipped["{$dest}1"]) && $flipped["{$dest}1"][0] === $amphipod[0] && !isset($flipped["{$dest}2"]) && isPathClear($x, $destX, $current->amphipods())) {
                    if (DEBUG) echo "Move {$amphipod} directly from {$position} to {$dest}1" . PHP_EOL;
                    $states->push($current->move($amphipod, "{$dest}2"));
                    continue;
                }

                for ($i = $x - 1; $i >= 1; $i--) {
                    // Walk to the left
                    if (isset($flipped["h{$i}"])) break; // no free path beyond this point
                    if ($i > 1 && $i % 2 == 1) continue; // don't stand directly above a burrow

                    if (DEBUG) echo "Moving {$amphipod} from {$position} to h{$i}" . PHP_EOL;
                    $states->push($current->move($amphipod, "h{$i}"));
                }

                for ($i = $x + 1; $i <= 11; $i++) {
                    // Walk to the right
                    if (isset($flipped["h{$i}"])) break; // no free path beyond this point
                    if ($i < 11 && $i % 2 == 1) continue; // don't stand directly above a burrow

                    if (DEBUG) echo "Moving {$amphipod} from {$position} to h{$i}" . PHP_EOL;
                    $states->push($current->move($amphipod, "h{$i}"));
                }
            }
        }
    }

    if ($minState) {
        foreach ($minState->history() ?? [] as $history) {
            dump(new State($history['energy'], $history['state']));
            if (DEBUG) echo "Moving {$history['amphipod']} from {$history['start']} to {$history['destination']}" . PHP_EOL;
        }
        dump($minState);
    }

    return $min;
}

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
        // Queue is first in, last out
        return array_pop($this->items);
    }
}

$stopwatch = hrtime(true);
$partOne = getLeastEnergyToOrganizeAmphipods($start);
$elapsedMilliseconds = round((hrtime(true) - $stopwatch)/1e+6, 0);
echo "What is the least energy required to organize the amphipods? {$partOne} ({$elapsedMilliseconds}ms)" . PHP_EOL;

// Part 2


// Debug
function dump(State $state): void
{
    if (!DEBUG) return;

    $template = array_combine(
        ['a1', 'a2', 'b1', 'b2', 'c1', 'c2', 'd1', 'd2', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8', 'h9', 'h10', 'h11'],
        array_fill(0, 19,  '.'),
    );

    foreach ($state->amphipods() as $amphipod => $position) {
        $a = ($amphipod[1] === '2' ? "\033[1;31m" : "") . $amphipod[0] . "\033[0m";
        $template[$position] = $amphipod[0];
    }

    echo json_encode($state) . PHP_EOL;
    echo join(array_fill(0, 13, '#')) . PHP_EOL;
    echo '#';
    for($i = 1; $i < 12; $i++) echo substr($template["h{$i}"] ?? '.', 0, 1);
    echo '#' . PHP_EOL;
    echo "###{$template['a2']}#{$template['b2']}#{$template['c2']}#{$template['d2']}###" . PHP_EOL;
    echo "  #{$template['a1']}#{$template['b1']}#{$template['c1']}#{$template['d1']}#  " . PHP_EOL;
    echo "  " . join(array_fill(0, 9, '#')) . "  " . PHP_EOL . PHP_EOL;
}
