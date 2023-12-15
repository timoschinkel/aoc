import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { dir } from 'console';
import { argv } from 'process';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const maze = parse(input);

// Scope "steps" here, so we can populate in part one, and read in part two
const steps: Record<number, number> = {};

{
    using sw = new Stopwatch('part one');
    console.log('How many steps along the loop does it take to get from the starting position to the point farthest from the starting position?', part_one(maze));
}

{
    using sw = new Stopwatch('part two');
    console.log('How many tiles are enclosed by the loop?', part_two(maze));
}

{
    using sw = new Stopwatch('part two alternative');
    console.log('How many tiles are enclosed by the loop? (alternative approach)', part_two_alternative(maze));
}

// An alternative approach to consider; start at the most north-east corner
// of the path. This will likely be an F. You now *know* that the inside of
// the path is on the right, and the outside is on the left. We can now follow
// the path - which we found in part 1 - clockwise. In case of an F we know
// that if the outside was left, then the outside becomes up. We can apply
// this logic for every step. For every step we look 1 step inwards; if we
// see a pipe or . that is not part of our path, then we can put this on our
// list of "inside positions". Once we have walked the entire path we iterate
// over the list of "inside positions" and using a flood fill algorithm we
// should be able to count the total number of inside positions.
// This approach is inspired by a maze; if you keep the walls to one side you
// should eventually always find the exit.


function part_one({ width, height, pipes }: Maze): number {
    /**
     * A classic Breadth-first search problem. We need the shortest path, but we don't
     * know what the target node is. That is why I opt for a breadth-first instead of
     * a depth-first approach.
     *
     * Starting with S, for every position in the maze I determine if I can go that any
     * of my four neighbors following the criteria:
     * 1. The pipes should "fit" - eg when looking up I should have a pipe exit on top
     * (|, J or L), and the pipe above should have a pip exit on the bottom (|, 7 or F).
     * 2. I should not have visited the neighbor, or I should have visited in more steps
     *
     * My approach uses a one-dimensional array representation of the grid.
     */
    print(maze);

    const get = (row: number, column: number): string => {
        if (row < 0 || row >= height || column < 0 || column >= width) {
            return '.';
        }

        return pipes[row * width + column];
    }

    // BFS
    const queue: number[] = [];
    // const steps: Record<number, number> = {};

    // find S
    const S = pipes.indexOf('S');
    steps[S] = 0;

    queue.push(S);

    log('Found S at position', S);

    while (queue.length > 0) {
        const currentPosition = queue.shift();
        const currentPipe = pipes[currentPosition];
        const currentSteps = steps[currentPosition];

        const row = Math.floor(currentPosition / maze.width);
        const column = currentPosition % maze.width;

        // look up
        if (['S', '|', 'L', 'J'].includes(currentPipe)) {
            const upPipe = get(row - 1, column);
            const upPosition = currentPosition - maze.width;
            const upSteps = steps[upPosition];

            if (['|', '7', 'F'].includes(upPipe) && (upSteps === undefined || upSteps > currentSteps + 1)) {
                // Yes, we can move up! Enqueue and set steps
                steps[upPosition] = currentSteps + 1;
                queue.push(upPosition);
            }
        }

        // look right
        if (['S', '-', 'L', 'F'].includes(currentPipe)) {
            const rightPipe = get(row, column + 1);
            const rightPosition = currentPosition + 1;
            const rightSteps = steps[rightPosition];

            if (['-', '7', 'J'].includes(rightPipe) && (rightSteps === undefined || rightSteps > currentSteps + 1)) {
                // Yes, we can move right! Enqueue and set steps
                steps[rightPosition] = currentSteps + 1;
                queue.push(rightPosition);
            }
        }

        // look down
        if (['S', '|', '7', 'F'].includes(currentPipe)) {
            const downPipe = get(row + 1, column);
            const downPosition = currentPosition + maze.width;
            const downSteps = steps[downPosition];

            if (['|', 'L', 'J'].includes(downPipe) && (downSteps === undefined || downSteps > currentSteps + 1)) {
                // Yes, we can move down! Enqueue and set steps
                steps[downPosition] = currentSteps + 1;
                queue.push(downPosition);
            }
        }

        // look left
        if (['S', '-', '7', 'J'].includes(currentPipe)) {
            const leftPipe = get(row, column - 1);
            const leftPosition = currentPosition - 1;
            const leftSteps = steps[leftPosition];

            if (['-', 'F', 'L'].includes(leftPipe) && (leftSteps === undefined || leftSteps > currentSteps + 1)) {
                // Yes, we can move right! Enqueue and set steps
                steps[leftPosition] = currentSteps + 1;
                queue.push(leftPosition);
            }
        }
    }

    return Math.max(...Object.values(steps));
}

function part_two({ width, height, pipes }: Maze): number {
    /**
     * For the first time this year will I reuse the results of part one to solve
     * part 2; the way part one is solved I have a dictionary with the steps to
     * reach a position. Because I used a breadth-first approach this means that
     * this dictionary contains all positions of the loop.
     *
     * I used the hints I found on Reddit on how to solve this; For every row I count
     * the number of *north facing* pipes of the path I'm crossing. If I encounter a
     * position that is not part of the path, and I have crossed an odd number of north
     * facing pipes, then the position is inside the enclosed path.
     *
     * @see https://www.reddit.com/r/adventofcode/comments/18fgddy/2023_day_10_part_2_using_a_rendering_algorithm_to/
     */

    // Find out what pipe S is
    const sPosition = parseInt(Object.entries(steps).find(([_index, s]) => s === 0)[0]);
    const neighborPositions = Object.entries(steps).filter(([_index, s]) => s === 1).map(([index]) => parseInt(index));

    const pipeAt = (pos: number, neighbors: number[]): string => {
        const up = neighbors.filter(p => p === pos - width).length === 1;
        const right = neighbors.filter(p => p === pos + 1).length === 1;
        const down = neighbors.filter(p => p === pos + width).length === 1;
        const left = neighbors.filter(p => p === pos - 1).length === 1;

        if (up && down) return '|';
        if (up && right) return 'L';
        if (up && left) return 'J';
        if (right && left) return '-';
        if (down && right) return 'F';
        if (down && left) return '7';

        return '?';
    }

    const sPipe = pipeAt(sPosition, neighborPositions);
    pipes[sPosition] = sPipe;

    let sum = 0;
    let inside = false;

    for(let row = 0; row < height; row++) {
        for (let column = 0; column < width; column++) {
            const position = row * width + column;
            if (position in steps) {
                // switch "inside" when pipe is facing north
                if (['|', 'J', 'L'].includes(pipes[position])) {
                    inside = !inside;
                }
                continue;
            }

            if (inside) {
                sum++;
            }
        }
    }

    return sum;
}

function part_two_alternative(maze: Maze): number {
    /**
     * I kept on thinking about how to solve this, eventhough I already solved it using the
     * Point in Polygon algorithm. This is an alternative approach I came up with, and it
     * seems to be faster - although that might be due to opcode cache or something similar.
     *
     * We know the entire path - from part 1. From this path we can find the top-left position.
     * Because it is the top-left corner, we know that this MUST be an F, and we know that
     * left en above this point is the "outside" of the path. Now we can iterate over the entire
     * path in a clockwise fashion. With every step we keep track of what is the inside and what
     * is the outside. After every step I check the "inside" to see if there's a point that is
     * not part of the path. If that is the case, then we add this position to the list of positions
     * that we need to check. Something to keep in mind is that when we take a corner - F, J, 7 or
     * L - we need to check the inside _before_ and _after_ we take the turn.
     *
     * One we are finished walking the entire path we have a list of positions that we need to
     * check. These points are the inner border of the voids. Now we can apply a simple flood
     * fill algorithm to find the rest of the positions inside the loop.
     *
     * Something cool: Add `visualize` to the call to output a visualization
     */

    // find top left item of path
    let left_top = 0;
    for (let col = 0; col < maze.width; col++) {
        for (let row = 0; row < maze.height; row++) {
            if ((row * maze.width + col) in steps) {
                left_top = row * maze.width + col;
                // break out of loop, old fashioned style
                row = maze.height + 1;
                col = maze.width + 1;
            }
        }
    }

    // Left-top MUST be F and we know that right below is the inside, let's walk
    const to_check = [];

    let inside = maze.width;
    let direction = 1; // right

    const inside_mutations = {
        'L': {
            [maze.width]: -1,
            [maze.width * -1]: 1,
            [1]: maze.width * -1,
            [-1]: maze.width
        },
        'J': {
            [maze.width]: 1,
            [maze.width * -1]: -1,
            [1]: maze.width,
            [-1]: maze.width * -1
        },
        '7': {
            [maze.width]: -1,
            [maze.width * -1]: 1,
            [1]: maze.width * -1,
            [-1]: maze.width
        },
        'F': {
            [maze.width]: 1,
            [maze.width * -1]: -1,
            [1]: maze.width,
            [-1]: maze.width * -1
        }
    }

    let iteration = 0;
    let next = left_top + direction;
    while (next != left_top) {
        iteration++;
        const current = next;

        // for corners we need to check both the old and the new "inside"
        let inside_for_corner: number;

        switch (maze.pipes[current]) {
            case '-':
                next += direction; // continue going
                break;
            case '|':
                next += direction; // continue going
                break;
            case '7':
                direction = direction === 1 // we came from the left
                    ? maze.width // we're going down
                    : -1; // otherwise we came from below and we're going left
                inside_for_corner = inside;
                inside = inside_mutations[7][inside];
                break;
            case 'J':
                direction = direction === 1 // we came from the left
                    ? (maze.width * -1) // we're going up
                    : -1; // otherwise we came from above and we're going left
                inside_for_corner = inside;
                inside = inside_mutations['J'][inside];
                break;
            case 'L':
                direction = direction === -1 // we came from the right
                    ? (maze.width * -1) // we're going up
                    : 1; // otherwise we came from above and we're going right
                inside_for_corner = inside;
                inside = inside_mutations['L'][inside];
                break;
            case 'F':
                direction = direction === -1 // we came from the right
                    ? maze.width // we're going down
                    : 1; // otherwise we came from below and we're going right
                inside_for_corner = inside;
                inside = inside_mutations['F'][inside];
                break;
        }

        const target = current + inside;
        if (target in steps === false && !to_check.includes(target)) {
            to_check.push(target);
        }

        if (inside_for_corner) {
            const target = current + inside_for_corner;
            if (target in steps === false && !to_check.includes(target)) {
                to_check.push(target);
            }
        }
        next = current + direction;
    }

    // we now have a list of positions to check, let's implement
    // a flood fill
    log('starting flood fill', to_check.length, Object.keys(steps).length);

    const filled = [];
    const original_to_check = [ ...to_check ];
    while (to_check.length > 0) {
        const current = to_check.pop();
        filled.push(current);

        for (const direction of [maze.width * -1, 1, maze.width, -1]) {
            const target = current + direction;
            if (target in steps === false
                && !to_check.includes(current + direction)
                && !filled.includes(current + direction)) {
                to_check.push(current + direction);
            }
        }
    }

    if (argv.includes('visualize')) {
        visualize(maze, steps, original_to_check, filled);
    }

    return filled.length;
}

type Maze = {
    readonly width: number;
    readonly height: number;
    pipes: string[];
}

function parse(input: string[]): Maze {
    const pipes = input.map(line => line.trim().split(''));

    return {
        height: input.length,
        width: input[0].length,
        pipes: pipes.reduce((carry, row) => [...carry, ...row], []),
    }
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

function print(maze: Maze): void {
    if (!debug) {
        return;
    }

    for(let row = 0; row < maze.height; row++) {
        console.log(maze.pipes.slice(row * maze.width, row * maze.width + maze.width));
    }
}

function visualize(maze: Maze, steps: Record<number, number>, to_check: number[], filled: number[]): void {
    // https://en.m.wikipedia.org/wiki/ANSI_escape_code#Colors
    const grey = (str: string): string => `\x1b[37m${str}\x1b[0m`;
    const green = (str: string): string => `\x1b[32m${str}\x1b[0m`;
    const cyan = (str: string): string => `\x1b[36m${str}\x1b[0m`;
    const black = (str: string): string => `\x1b[30m${str}\x1b[0m`;

    for (let row = 0; row < maze.height; row++) {
        let r = '';
        for (let col = 0; col < maze.width; col++) {
            const pos = row * maze.width + col;

            if (pos in steps) {
                r += black({
                    '|': '║',
                    '-': '═',
                    'J': '╝',
                    'L': '╚',
                    'F': '╔',
                    '7': '╗',
                }[maze.pipes[pos]]);
            } else if (to_check.includes(pos)) {
                r += green('█');
            } else if (filled.includes(pos)) {
                r += cyan('█');
            } else {
                r += grey('█');
            }
        }
        console.log(r);
    }
}
