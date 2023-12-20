import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { PriorityList } from '../PriorityList';
import { PriorityQueue } from '../PriorityQueue';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const grid = parse(input);

const start = performance.now();
{
    using sw = new Stopwatch('part one');
    console.log('What is the least heat loss it can incur?', part_one(grid));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two(grid));

    // 828 -> too low
    // 897 -> too high
}


function generic_dijkstra (grid: Grid): number {
    /**
     * A generic Dijkstra algorithm as described wonderfully by u/StaticMoose on Reddit[0].
     * This implementation is not used for this puzzle, but I have been struggling with
     * implementing Dijkstra. I liked the explanation given, especially the part about building
     * up a state.
     *
     * [0]: https://www.reddit.com/r/adventofcode/comments/18luw6q/2023_day_17_a_longform_tutorial_on_day_17/
     */

    type Node = {
        readonly row: number;
        readonly col: number;
    }

    const goal_row = grid.height - 1;
    const goal_col = grid.width - 1;

    const state_queues_by_cost: Record<string, Node[]> = {};
    const seen_cost_by_state: Record<string, number> = {};

    const add_state = (cost: number, col: number, row: number): void => {
        // check if the state is valid. When working with a grid it means
        // we need to check the bounds
        if (col < 0 || col >= grid.width || row < 0 || row >= grid.height) {
            return;
        }

        // We construct a state containing all relevant information we want
        const state: Node = { col, row };
        const signature = JSON.stringify(state); // Dictionaries require string keys

        if (signature in seen_cost_by_state === false) {
            // We have encountered the state for the first time. Due to how Dijkstra
            // works - always fetch the state with the lowest distance - we must have
            // found the shortest path to this state.
            seen_cost_by_state[signature] = cost;

            // We now add the new state to the list of states to be handled
            state_queues_by_cost[cost] = [ ...(state_queues_by_cost[cost] ?? []), state];
        }
    }

    const move_and_add_state = (cost: number, col: number, row: number, delta_col: number, delta_row: number): void => {
        // By putting moving and adding to the state in a separate method we
        // add flexibility; we can now do boundary detection centralized, and
        // keep a record of the previous position we came from. We can use this
        // for visualization.
        col += delta_col;
        row += delta_row;

        if (col < 0 || col >= grid.width || row < 0 || row >= grid.height) {
            return;
        }

        cost += grid.blocks[row][col];

        // We construct a state containing all relevant information we want
        const state: Node = { col, row };
        const signature = JSON.stringify(state); // Dictionaries require string keys

        if (signature in seen_cost_by_state === false) {
            // We have encountered the state for the first time. Due to how Dijkstra
            // works - always fetch the state with the lowest distance - we must have
            // found the shortest path to this state.
            seen_cost_by_state[signature] = cost;

            // We now add the new state to the list of states to be handled
            state_queues_by_cost[cost] = [ ...(state_queues_by_cost[cost] ?? []), state];
        }
    }

    // initialize the state
    // add_state(0, 0, 0);
    move_and_add_state(0, 0, 0, 1, 0); // EAST
    move_and_add_state(0, 0, 0, 0, 1); // SOUTH

    while (true) {
        // Find the state(s) with the lowest cost. That is the Dijkstra part.
        const current_cost = Object.keys(state_queues_by_cost).reduce((carry, value) => Math.min(carry, parseInt(value)), Number.MAX_SAFE_INTEGER);
        const states = state_queues_by_cost[current_cost];

        delete(state_queues_by_cost[current_cost]); // We need to remove this from the dictionary, otherwise we'll keep handling it

        for (const { col, row } of states) {
            if (col === goal_col && row === goal_row) {
                // We have reached the goal!
                return current_cost;
            }

            // For each state we will add the potential new states.
            // if (col < grid.width - 1)
            //     add_state(current_cost + grid.blocks[row][col + 1], col + 1, row); // EAST
            // if (row < grid.height - 1)
            //     add_state(current_cost + grid.blocks[row + 1][col], col, row + 1); // SOUTH
            // if (col > 0)
            //     add_state(current_cost + grid.blocks[row][col + 1], col - 1, row); // WEST
            // if (row > 0)
            //     add_state(current_cost + grid.blocks[row - 1][col], col, row - 1); // NORTH
            move_and_add_state(current_cost, col, row, 1, 0);
            move_and_add_state(current_cost, col, row, 0, 1);
            move_and_add_state(current_cost, col, row, -1, 0);
            move_and_add_state(current_cost, col, row, 0, -1);
        }
    }

    return 0;
}

function part_one(grid: Grid): number {
    /**
     * Thank you u/StaticMoose[0] for the thourough explanation.
     *
     * So, Dijkstra's[1] with a twist; we keep track of the direction and the number of steps we
     * have taken. That will be part of our state. When we take a state of the queue, then we
     * take a turn clockwise and a turn counter clockwise, and only when we have taken less than
     * three steps will we take a step in the direction we've been walking.
     *
     * [0]: https://www.reddit.com/r/adventofcode/comments/18luw6q/2023_day_17_a_longform_tutorial_on_day_17/
     */

    type Node = {
        readonly row: number;
        readonly col: number;
        readonly delta_col: number;
        readonly delta_row: number;
        readonly steps: number;
    }

    const goal_row = grid.height - 1;
    const goal_col = grid.width - 1;

    const state_queues_by_cost: Record<string, Node[]> = {};
    const seen_cost_by_state: Record<string, number> = {};

    const move_and_add_state = (cost: number, col: number, row: number, delta_col: number, delta_row: number, steps: number): void => {
        // By putting moving and adding to the state in a separate method we
        // add flexibility; we can now do boundary detection centralized, and
        // keep a record of the previous position we came from. We can use this
        // for visualization.
        col += delta_col;
        row += delta_row;

        if (col < 0 || col >= grid.width || row < 0 || row >= grid.height) {
            return;
        }

        cost += grid.blocks[row][col];

        // We construct a state containing all relevant information we want
        const state = { col, row, delta_col, delta_row, steps };
        const signature = JSON.stringify(state); // Dictionaries require string keys

        if (signature in seen_cost_by_state === false) {
            // We have encountered the state for the first time. Due to how Dijkstra
            // works - always fetch the state with the lowest distance - we must have
            // found the shortest path to this state.
            seen_cost_by_state[signature] = cost;

            // We now add the new state to the list of states to be handled
            state_queues_by_cost[cost] = [ ...(state_queues_by_cost[cost] ?? []), state];
        }
    }

    // initialize the state
    // add_state(0, 0, 0);
    move_and_add_state(0, 0, 0, 1, 0, 1); // EAST
    move_and_add_state(0, 0, 0, 0, 1, 1); // SOUTH

    while (true) {
        // Find the state(s) with the lowest cost. That is the Dijkstra part.
        const current_cost = Object.keys(state_queues_by_cost).reduce((carry, value) => Math.min(carry, parseInt(value)), Number.MAX_SAFE_INTEGER);
        const states = state_queues_by_cost[current_cost];

        delete(state_queues_by_cost[current_cost]); // We need to remove this from the dictionary, otherwise we'll keep handling it

        for (const { col, row, delta_col, delta_row, steps } of states) {
            if (col === goal_col && row === goal_row) {
                // We have reached the goal!
                return current_cost;
            }

            // Using delta_col and delta_row we can use rotation matrix:
            // (delta_col, delta_row) -> clockwise -> (delta_row, -delta_col)
            // (delta_col, delta_row) -> counter clockwise -> (-delta_row, delta_col)

            // For each state we will add the potential new states.
            // Turn 90 degrees cw and ccw
            move_and_add_state(current_cost, col, row, delta_row, delta_col * -1, 1);
            move_and_add_state(current_cost, col, row, delta_row * -1, delta_col, 1)

            // If we have done less then 3 steps we can also follow the path we've been following
            if (steps < 3) {
                move_and_add_state(current_cost, col, row, delta_col, delta_row, steps + 1);
            }
        }
    }

    return 0; // we should never come here
}

function part_two(grid: Grid): number {
    /**
     * Again, thank you u/StaticMoose[0] for the thourough explanation.
     *
     * Same solution as part 1 with two twists:
     * - We require at least 4 steps before making a turn and only with 10 or less can we go "straight"
     * - The solution is only valid if we have reached our goal AND we have been going 4 or more steps
     *
     * [0]: https://www.reddit.com/r/adventofcode/comments/18luw6q/2023_day_17_a_longform_tutorial_on_day_17/
     */

    type Node = {
        readonly row: number;
        readonly col: number;
        readonly delta_col: number;
        readonly delta_row: number;
        readonly steps: number;
    }

    const goal_row = grid.height - 1;
    const goal_col = grid.width - 1;

    const state_queues_by_cost: Record<string, Node[]> = {};
    const seen_cost_by_state: Record<string, number> = {};

    const move_and_add_state = (cost: number, col: number, row: number, delta_col: number, delta_row: number, steps: number): void => {
        // By putting moving and adding to the state in a separate method we
        // add flexibility; we can now do boundary detection centralized, and
        // keep a record of the previous position we came from. We can use this
        // for visualization.
        col += delta_col;
        row += delta_row;

        if (col < 0 || col >= grid.width || row < 0 || row >= grid.height) {
            return;
        }

        cost += grid.blocks[row][col];

        // We construct a state containing all relevant information we want
        const state = { col, row, delta_col, delta_row, steps };
        const signature = JSON.stringify(state); // Dictionaries require string keys

        if (signature in seen_cost_by_state === false) {
            // We have encountered the state for the first time. Due to how Dijkstra
            // works - always fetch the state with the lowest distance - we must have
            // found the shortest path to this state.
            seen_cost_by_state[signature] = cost;

            // We now add the new state to the list of states to be handled
            state_queues_by_cost[cost] = [ ...(state_queues_by_cost[cost] ?? []), state];
        }
    }

    // initialize the state
    // add_state(0, 0, 0);
    move_and_add_state(0, 0, 0, 1, 0, 1); // EAST
    move_and_add_state(0, 0, 0, 0, 1, 1); // SOUTH

    while (true) {
        // Find the state(s) with the lowest cost. That is the Dijkstra part.
        const current_cost = Object.keys(state_queues_by_cost).reduce((carry, value) => Math.min(carry, parseInt(value)), Number.MAX_SAFE_INTEGER);
        const states = state_queues_by_cost[current_cost];

        delete(state_queues_by_cost[current_cost]); // We need to remove this from the dictionary, otherwise we'll keep handling it

        for (const { col, row, delta_col, delta_row, steps } of states) {
            // Very important: our solution is not valid UNTIL we have set 4 or more
            // steps! Thank you Reddit for this.
            if (col === goal_col && row === goal_row && steps >= 4) {
                // We have reached the goal!
                return current_cost;
            }

            // Using delta_col and delta_row we can use rotation matrix:
            // (delta_col, delta_row) -> clockwise -> (delta_row, -delta_col)
            // (delta_col, delta_row) -> counter clockwise -> (-delta_row, delta_col)

            // If we have done less then 10 steps we can also follow the path we've been following
            if (steps < 10) {
                move_and_add_state(current_cost, col, row, delta_col, delta_row, steps + 1);
            }

            // Turn 90 degrees cw and ccw, but only if we have taken 4 steps
            if (steps >= 4) {
                move_and_add_state(current_cost, col, row, delta_row, delta_col * -1, 1);
                move_and_add_state(current_cost, col, row, delta_row * -1, delta_col, 1)
            }
        }
    }

    return 0; // we should never come here
}

type Point = {
    readonly col: number;
    readonly row: number;
}

type Grid = {
    readonly width: number;
    readonly height: number;
    readonly blocks: number[][];
}

function parse(input: string[]): Grid {
    return {
        height: input.length,
        width: input[0].length,
        blocks: input.map(row => row.split('').map(col => parseInt(col))),
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
