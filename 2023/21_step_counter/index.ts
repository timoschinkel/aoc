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
    console.log('What are the new total winnings?', part_two());
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

    if (argv.includes('--visualize')) {
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

function part_two(): number {
    return 0;
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
