import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const maze = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many steps along the loop does it take to get from the starting position to the point farthest from the starting position?', part_one(maze));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
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
    const steps: Record<number, number> = {};

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

function part_two(): number {
    return 0;
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
