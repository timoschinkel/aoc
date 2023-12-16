import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';
import { argv } from 'process';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const contraption = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many tiles end up being energized?', part_one(contraption));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}

function part_one(contraption: Contraption): number {
    /**
     * I struggled a bit too much with detecting the loops. I keep a dictionary
     * of energized positions. In that dictionary I keep track of the direction
     * that the position has passed. I use a bitmask for this.
     *
     * I energize every position I pass with the direction, and whenever I
     * encounter a . that has already been passed in the same direction then I
     * consider that a loop and I stop the beam.
     *
     * Count all passed positions for solution.
     */

    const UP = 0x0001;
    const DOWN = 0x0010;
    const LEFT = 0x0100;
    const RIGHT = 0x1000;

    const energized: Record<number, number> = {};

    const beams: Beam[] = [
        {
            id: '0',
            row: 0,
            column: -1, // start off grid
            direction: { row: 0, column: 1 },
            completed: false,
        },
    ];

    const get_energized = (row: number, col: number): number => {
        return (row * contraption.width + col) in energized
            ? energized[row * contraption.width + col]
            : 0;
    }

    const set_energized = (row: number, col: number, direction: number): void => {
        const curr = get_energized(row, col);
        energized[row * contraption.width + col] = curr | direction;
    }

    const get_direction = ({ column, row}: Direction): number => {
        if (column === 0) {
            return row > 0 ? DOWN : UP;
        }
        if (row === 0) {
            return column > 0 ? RIGHT : LEFT;
        }

        console.error('This should not happen!');
        process.exit(1);
    }

    let iteration = 0;
    do {
        log('');
        log('Starting iteration', iteration++);

        // Move all beams
        const num_of_beams_before_loop = beams.length; // this will allow us to add items to the list inside the loop
        for(let b = 0; b < num_of_beams_before_loop; b++) {
            const beam = beams[b];
            if (beam.completed) {
                continue; // no need to continue
            }

            // calculate new position
            const new_row = beam.row + beam.direction.row;
            const new_col = beam.column + beam.direction.column;

            const pos = beam.row * contraption.width + beam.column;
            const new_pos = new_row * contraption.width + new_col;

            if (new_row < 0 || new_row >= contraption.height
                || new_col < 0 || new_col >= contraption.width
            ) {
                // next step takes us out of bound, mark as completed and leave
                log('Beam', beam.id, 'is out of bounds, marking as completed');
                beams[b].completed = true;
                continue;
            }

            const direction = get_direction(beam.direction);
            const tile = contraption.layout[new_row].charAt(new_col);

            if (tile === '.' && get_energized(new_row, new_col) & direction) {
                // new position is already energized in the direction we're going
                log('Beam', beam.id, 'has detected a loop, stopping');
                beams[b].completed = true;
                continue;
            }

            // We are going to move!

            set_energized(new_row, new_col, direction);
            beams[b].row = new_row;
            beams[b].column = new_col;

            if (tile === '.'
                || (tile === '-' && direction & (LEFT | RIGHT))
                || (tile === '|' && direction & (UP | DOWN))
            ) {
                // We go straight, no problems
                continue;
            }

            if (tile === '|' && direction & (LEFT | RIGHT)) {
                // split up:
                beams[b].direction = { row: -1, column: 0 };

                log('Beam', beam.id, 'spawns new beam', beams.length);
                beams.push({
                    id: beams.length.toString(),
                    row: new_row,
                    column: new_col,
                    direction: { row: 1, column: 0 },
                    completed: false,
                })
            }

            if (tile === '-' && direction & (UP | DOWN)) {
                // split up:
                beams[b].direction = { row: 0, column: -1 };

                log('Beam', beam.id, 'spawns new beam', beams.length);
                beams.push({
                    id: beams.length.toString(),
                    row: new_row,
                    column: new_col,
                    direction: { row: 0, column: 1 },
                    completed: false,
                })
            }

            if (tile === '/') {
                if (direction & UP) {
                    log('Beam', beam.id, 'deflection right');
                    beams[b].direction = { row: 0, column: 1};
                } else if (direction & DOWN) {
                    log('Beam', beam.id, 'deflection left');
                    beams[b].direction = { row: 0, column: -1};
                } else if (direction & LEFT) {
                    log('Beam', beam.id, 'deflection down');
                    beams[b].direction = { row: 1, column: 0};
                } else if (direction & RIGHT) {
                    log('Beam', beam.id, 'deflection up');
                    beams[b].direction = { row: -1, column: 0};
                }
            }

            if (tile === '\\') {
                if (direction & UP) {
                    log('Beam', beam.id, 'deflection left');
                    beams[b].direction = { row: 0, column: -1};
                } else if (direction & DOWN) {
                    log('Beam', beam.id, 'deflection right');
                    beams[b].direction = { row: 0, column: 1};
                } else if (direction & LEFT) {
                    log('Beam', beam.id, 'deflection up');
                    beams[b].direction = { row: -1, column: 0};
                } else if (direction & RIGHT) {
                    log('Beam', beam.id, 'deflection down');
                    beams[b].direction = { row: 1, column: 0};
                }
            }
        }
    } while (beams.filter(beam => !beam.completed).length > 0);

    visualize(contraption, energized);

    return Object.keys(energized).length;
}

function part_two(): number {
    return 0;
}

type Direction = {
    readonly row: number;
    readonly column: number;
}

type Beam = {
    readonly id: string;
    row: number;
    column: number;
    direction: Direction;
    completed: boolean;
}

type Contraption = {
    readonly width: number;
    readonly height: number;
    readonly layout: string[];
}

function parse(input: string[]): Contraption {
    return {
        height: input.length,
        width: input[0].length,
        layout: input,
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

function visualize(contraption: Contraption, energized: Record<number, number>): void {
    if (!argv.includes('--visualize')) return;

    const green = (str: string): string => `\x1b[42m${str}\x1b[0m`;
    for(let r = 0; r < contraption.height; r++) {
        let row = '';
        for(let c = 0; c < contraption.width; c++) {
            const p = r * contraption.width + c;
            const dir = energized[p] || 0;
            row +=  dir ? green(contraption.layout[r].charAt(c)) : contraption.layout[r].charAt(c);
        }
        console.log(row);
    }
}
