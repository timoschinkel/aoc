import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

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
