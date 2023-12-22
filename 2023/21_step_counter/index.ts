import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { argv } from 'process';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const garden = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What are the total winnings?', part_one(garden));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two(garden));
}


function part_one(garden: Garden): number {
    /**
     * A regular breadth-first search will give us the minimum number of steps
     * needed to reach any of the plots in the garden. The trick is that we need
     * to find the plots we can reach after *exact* steps, keeping in mind that
     * we can visit the same plot more than once.
     *
     * Based on the minimum distance needed to reach a plot we can "calculate"
     * if we can reach it after *exact* 64 steps - or even any arbitrary number
     * of steps; if the number of steps left when on a plot is even, then we can
     * always reach that spot, as we can step on and off the neighbor.
     */
    // find start position:
    let start_row = 0, start_col = 0;
    for (; start_row < garden.height; start_row++) {
        const s = garden.plots[start_row].indexOf('S');
        if (s !== -1) {
            start_col = s;
            break;
        }
    }

    const state = ({ row, col }: Point): string => JSON.stringify({ row, col });

    // BFS
    const plots_to_check: Point[] = [{ row: start_row, col: start_col }];
    const plot_distances: Record<string, number> = { [state({ row: start_row, col: start_col })]: 0 };

    const remaining_steps = 64;
    let last_distance = 0;
    while (plots_to_check.length > 0) {
        const current = plots_to_check.shift();
        const distance = plot_distances[state(current)];

        if (distance > last_distance) {
            log('distance', distance);
            last_distance = distance;
        }

        if (distance >= remaining_steps) {
            continue; // too far
        }

        const add = ({ row, col }: Point, d: Point): void => {
            row += d.row;
            col += d.col;

            if (row < 0 || row >= garden.height || col < 0 || col >= garden.width) {
                return; // out of bounds
            }

            if (state({ row, col }) in plot_distances) {
                return; // already visited
            }

            if (garden.plots[row].charAt(col) === '#') {
                return; // rock
            }

            plot_distances[state({ row, col })] = distance + 1;
            plots_to_check.push({ row, col });
        }

        for (const direction of [{row: 0, col: 1}, {row: 1, col: 0}, {row: 0, col: -1}, {row: -1, col: 0}]) {
            add(current, direction);
        }
    }

    const can_be_reached = (d: number): boolean => {
        return (remaining_steps - d) % 2 === 0;
    }

    if (false && argv.includes('--visualize')) {
        for (let r = 0; r < garden.height; r++) {
            let row = '';
            for (let c = 0; c < garden.width; c++) {
                const dist = plot_distances[state({ row: r, col: c})] ?? -1;
                row += dist >= 0 && can_be_reached(dist)
                    ? 'O' : garden.plots[r].charAt(c);
            }
            console.log(row);
        }
    }

    return Object.values(plot_distances).filter(d => can_be_reached(d)).length;
}

function part_two(garden: Garden): number {
    /**
     * This one had me stumped and I needed help from others.
     *
     * As the garden is infinitly large I expected some pattern to emerge. I calculated
     * to reachable plots for the first 200 steps, trying to find a pattern. As I did not
     * immediately find one, I tried to find a quadratic formula[0]. That did not work.
     * Or at least, I did not find one.
     *
     * I found some advise on Reddit[1] about looking at the input carefully. The start
     * position is centered and there seems to be a diamond shape. That means that after
     * 65 steps we reach the edge and we venture into infinity; from now on there might
     * be some repetition for each 131 steps - 131 being the width and height of the input.
     *
     * When you calculate the reachable plots for steps 65, 196 (65 + 131), 327 (196 + 131),
     * 458, and 589 and _then_ look into the quadratic explanation, you might see that the
     * difference grows with a constant rate. For my input:
     *
     * 65       3821
     * 196      34234       +30413
     * 327      94963       +60729      +30316
     * 458      186008      +91045      +30316
     * 589      307369      +121361     +30316
     *                                  ^^^^^^
     *
     * I tried to find the values for a and b - c being 1, as 0 steps has 1 reachable plot -,
     * but could not solve the math. I decided to just iterate over values, increasing by
     * 131 until we reach the goal, while increasing the difference with 30316.
     *
     * NB. This exact code probably does NOT work as a generic solution.
     *
     * [0]: http://www.perfectscorer.com/2017/05/quadratic-sequences-how-to-find.html
     * [1]: https://www.reddit.com/r/adventofcode/comments/18nme8x/2023_day_21_part_2_c_what_is_the_correct_approach/
     */

    // Basically part 1:

    // find start position:
    let start_row = 0, start_col = 0;
    for (; start_row < garden.height; start_row++) {
        const s = garden.plots[start_row].indexOf('S');
        if (s !== -1) {
            start_col = s;
            break;
        }
    }

    const state = ({ row, col }: Point): string => JSON.stringify({ row, col });

    // BFS
    const plots_to_check: Point[] = [{ row: start_row, col: start_col }];
    const plot_distances: Record<string, number> = { [state({ row: start_row, col: start_col })]: 0 };

    const remaining_steps = 328;
    let last_distance = 0;
    let min_width = 0, min_height = 0, max_width = garden.width - 1, max_height = garden.height - 1;

    const get = (row: number, col: number): string => {
        if (row < 0) {
            row = (Math.floor(Math.abs(row) / garden.height) * garden.height) + garden.height + row;
        }
        if (col < 0) {
            col = (Math.floor(Math.abs(col) / garden.width) * garden.width) + garden.width + col;
        }

        return garden.plots[row % garden.height].charAt(col % garden.width);
    }

    while (plots_to_check.length > 0) {
        const current = plots_to_check.shift();
        const distance = plot_distances[state(current)];

        if (distance > last_distance) {
            log('distance', distance);
            last_distance = distance;
        }

        if (distance > remaining_steps) {
            continue; // too far
        }

        const add = ({ row, col }: Point, d: Point): void => {
            row += d.row;
            col += d.col;

            // Pure for visualization purposes:
            min_height = Math.min(min_height, row);
            max_height = Math.max(max_height, row);
            min_width = Math.min(min_width, col);
            max_width = Math.max(max_width, col);

            // if (row < 0 || row >= garden.height || col < 0 || col >= garden.width) {
            //     return; // out of bounds
            // }

            if (state({ row, col }) in plot_distances) {
                return; // already visited
            }

            if (get(row, col) === '#') {
                return; // rock
            }

            plot_distances[state({ row, col })] = distance + 1;
            plots_to_check.push({ row, col });
        }

        for (const direction of [{row: 0, col: 1}, {row: 1, col: 0}, {row: 0, col: -1}, {row: -1, col: 0}]) {
            add(current, direction);
        }
    }

    const can_be_reached = (d: number, steps: number = null): boolean => {
        steps = steps ?? remaining_steps;
        return d <= steps && (steps - d) % 2 === 0;
    }

    // Calculate for 65 and 196

    const c65 = Object.values(plot_distances).filter(d => can_be_reached(d, 65)).length;
    const c196 = Object.values(plot_distances).filter(d => can_be_reached(d, 196)).length;
    let difference = c196 - c65;

    log('65', c65, '196', c196, 'diff', difference);

    let steps = 196;
    let plots = c196;
    while (steps < 26501365) {
        steps += 131;
        difference += 30316;
        plots += difference;

        log('steps', steps, 'plots', plots);
    }

    return plots;
}

type Garden = {
    readonly width: number;
    readonly height: number;
    readonly plots: string[];
}

type Point = {
    readonly col: number;
    readonly row: number;
}

function is_all_the_same(row: number[]): boolean {
    if (row.length === 0) {
        return true;
    }

    const value = row[0];
    for(let i = 1; i < row.length; i++) {
        if (row[i] !== value) {
            return false;
        }
    }

    return true;
}

function difference(row: number[]): number[] {
    const diff = [];
    for(let i = 0; i < row.length - 1; i++) {
        diff.push(row[i + 1] - row[i]);
    }

    return diff;
}

function parse(input: string[]): Garden {
    return {
        height: input.length,
        width: input[0].length,
        plots: input,
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
