import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const forest = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many steps long is the longest hike?', part_one(forest));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(forest: Forest): number {
    /**
     * Observations:
     * - we're not looking for the shortest path, but the longest path
     * - we cannot revisit a previous tile
     * - the majority of the traversals can only go one direction, aka a lot
     *   of corridors.
     *
     * This code uses a very optimistic DFS to solve this; I keep a list of
     * visited nodes in my state once I find a path to the goal I compare this
     * with previous attempts, and I take the max value. I have opted for DFS
     * over BFS because we have a lot of corridors. The difference was less than
     * 1 second.
     *
     * This approach runs in approx. 20 seconds. I think there is a better approach
     * possible; we could exclude the corridors and create a smaller graph. This
     * seems to be a max-flow problem[0], and there are algorithms to solve them.
     * For now I'm not implementing them, but maybe I'll need that for part 2...
     *
     * [0]: https://en.wikipedia.org/wiki/Maximum_flow_problem
     */

    type Point = {
        readonly row: number;
        readonly col: number;
    }

    type State = Point & {
        readonly visited: number[];
    }

    type Distance = {
        readonly distance: number;
        readonly previous?: Point;
    }

    const todo: State[] = [];
    const distances = {};

    const state_as_string = (state: State): string => JSON.stringify({ col: state.col, row: state.row, visited: state.visited });
    const id = ({ row, col }: Point): number => row * forest.width + col;

    const start = { row: 0, col: forest.trails[0].indexOf('.'), visited: [] };
    const end = { row: forest.height - 1, col: forest.trails[forest.height - 1].indexOf('.') };

    todo.push(start);
    distances[state_as_string(start)] = { distance: 0, previous: null };

    const add = (current: State, distance: number, delta_col: number, delta_row: number): void => {
        if (current.col + delta_col < 0 || current.col + delta_col >= forest.width || current.row + delta_row < 0 || current.row + delta_row >= forest.height) {
            return; // out of bounds
        }

        const trail = forest.trails[current.row + delta_row].charAt(current.col + delta_col);
        if (trail === '#') {
            return; //tree
        }

        if ((trail === '>' && delta_col !== 1)
            || (trail === '<' && delta_col !== -1)
            || (trail === 'v' && delta_row !== 1)
            || (trail === '^' && delta_row !== -1)) {
                return; // impossible slope
            }

        const new_point = { col: current.col + delta_col, row: current.row + delta_row };

        if (current.visited.includes(id(new_point))) {
            return; // we already visited this point
        }

        const projected = { ...new_point, visited: [...current.visited, id(new_point)] };

        if (state_as_string(projected) in distances === false || distances[state_as_string(projected)].distance > distance) {
            // Found a shorter path
            distances[state_as_string(projected)] = { distance: distance + 1, previous: null };
            todo.push(projected);
        }
    }

    let max_distance = 0;
    let max_distance_state = null;

    while (todo.length > 0) {
        const current = todo.pop();
        const { distance } = distances[state_as_string(current)];

        if (current.row === end.row && current.col === end.col) {
            if (distance > max_distance) {
                log('Found a (longer) path', distance);
                max_distance = distance;
                max_distance_state = current;
            }
            continue;
        }

        add(current, distance, 1, 0);
        add(current, distance, 0, 1);
        add(current, distance, -1, 0);
        add(current, distance, 0, -1);
    }

    // visualize
    if (process.argv.includes('--visualize')) {
        const path = max_distance_state.visited;
        const green = (str: string): string => `\x1b[42m${str}\x1b[0m`;
        for (let r = 0; r < forest.height; r++) {
            let line = '';
            for (let c = 0; c < forest.width; c++) {
                if (path.includes(id({ row: r, col: c }))) {
                    line += green(forest.trails[r].charAt(c));
                } else {
                    line += forest.trails[r].charAt(c);
                }
            }
            console.log(line);
        }
    }

    return max_distance;
}

function part_two(): number {
    return 0;
}

type Forest = {
    readonly width: number;
    readonly height: number;
    readonly trails: string[];
}

function parse(input: string[]): Forest {
    return {
        height: input.length,
        width: input[0].length,
        trails: input,
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
