import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const panel = parse(input);
const Rock = {
    Squared: '#',
    Rounded: 'O',
    Empty: '.',
}

{
    using sw = new Stopwatch('part one');
    console.log('What is the total load on the north support beams?', part_one(panel));
}

{
    using sw = new Stopwatch('part two');
    console.log('What is the total load on the north support beams?', part_two(panel));
}


function part_one(panel: ControlPanel): number {
    /**
     * I will probably pay for this approach in part two, but my approach is
     * simple; for every column iterate from top to bottom and keep track of
     * the score that a rounded rock will yield - starting with panel.height.
     * For every rounded rock I increment with the score and lower the score
     * for the next rock with 1. When I encounter a squared rock I update the
     * score for the next rock.
     */
    let load = 0;
    for (let column = 0; column < panel.width; column++) {
        let score = panel.height;
        for (let row = 0; row < panel.height; row++) {
            if (panel.rocks[row][column] === Rock.Rounded) {
                log('Found a rounded rock at', column, 'x', row, 'scoring', score);
                load += score;
                score--;
            }
            if (panel.rocks[row][column] === Rock.Squared) {
                log('Found a squared rock at', column, 'x', row, 'updating delta to', panel.height - row - 1);
                score = panel.height - row - 1;
            }
        }
    }
    return load;
}

function part_two(panel: ControlPanel): number {
    /**
     * This assignment reminds me of the Tetris-like puzzel from day 17 of 2022;
     * it is not feasible to run the cycles so often. There probably is a pattern
     * that repeats itself every x cycles.
     *
     * For every cycle I store the controlpanel after tilting, and store that in
     * an array. After every cycle I check if the pattern is present in this array,
     * and if that's the case then we have found our pattern length. With this we
     * can calculate the number of cycles that are remaining, execute them and
     * calculate the load after that last iteration.
     */

    const tilt_north = (): void => {
        for(let col = 0; col < panel.width; col++) {
            for (let row = 0; row < panel.height; row++) {
                if (panel.rocks[row][col] === Rock.Squared) {
                    continue; // we don't have to do anything
                }

                else if (panel.rocks[row][col] === Rock.Empty) {
                    // find next Rock.Round and switch
                    for(let r = row + 1; r < panel.height; r++) {
                        if (panel.rocks[r][col] === Rock.Squared) {
                            // No rock, update row
                            row = r;
                            break;
                        }
                        if (panel.rocks[r][col] === Rock.Rounded) {
                            // switch and continue
                            panel.rocks[row][col] = Rock.Rounded;
                            panel.rocks[r][col] = Rock.Empty;
                            break;
                        }
                    }
                }
            }
        }
    }

    const tilt_east = (): void => {
        for(let row = panel.height - 1; row >= 0; row--) {
            for (let col = panel.width - 1; col >= 0; col--) {
                if (panel.rocks[row][col] === Rock.Squared) {
                    continue; // we don't have to do anything
                }

                else if (panel.rocks[row][col] === Rock.Empty) {
                    // find next Rock.Round and switch
                    for(let c = col - 1; c >= 0; c--) {
                        if (panel.rocks[row][c] === Rock.Squared) {
                            // No rock, update row
                            col = c;
                            break;
                        }
                        if (panel.rocks[row][c] === Rock.Rounded) {
                            // switch and continue
                            panel.rocks[row][col] = Rock.Rounded;
                            panel.rocks[row][c] = Rock.Empty;
                            break;
                        }
                    }
                }
            }
        }
    }

    const tilt_south = (): void => {
        for(let col = panel.width - 1; col >= 0; col--) {
            for (let row = panel.height - 1; row >= 0; row--) {
                if (panel.rocks[row][col] === Rock.Squared) {
                    continue; // we don't have to do anything
                }

                else if (panel.rocks[row][col] === Rock.Empty) {
                    // find next Rock.Round and switch
                    for(let r = row - 1; r >= 0; r--) {
                        if (panel.rocks[r][col] === Rock.Squared) {
                            // No rock, update row
                            row = r;
                            break;
                        }
                        if (panel.rocks[r][col] === Rock.Rounded) {
                            // switch and continue
                            panel.rocks[row][col] = Rock.Rounded;
                            panel.rocks[r][col] = Rock.Empty;
                            break;
                        }
                    }
                }
            }
        }
    }

    const tilt_west = (): void => {
        for(let row = 0; row < panel.height; row++) {
            for (let col = 0; col < panel.width; col++) {
                if (panel.rocks[row][col] === Rock.Squared) {
                    continue; // we don't have to do anything
                }

                else if (panel.rocks[row][col] === Rock.Empty) {
                    // find next Rock.Round and switch
                    for(let c = col + 1; c < panel.width; c++) {
                        if (panel.rocks[row][c] === Rock.Squared) {
                            // No rock, update row
                            col = c;
                            break;
                        }
                        if (panel.rocks[row][c] === Rock.Rounded) {
                            // switch and continue
                            panel.rocks[row][col] = Rock.Rounded;
                            panel.rocks[row][c] = Rock.Empty;
                            break;
                        }
                    }
                }
            }
        }
    }

    const load = (panel: ControlPanel): number => {
        let load = 0;

        for(let col = 0; col < panel.width; col++) {
            for(let row = 0; row < panel.height; row++) {
                if (panel.rocks[row][col] === Rock.Rounded) {
                    load += panel.height - row;
                }
            }
        }

        return load;
    }

    const patterns: string[] = [];

    const num_of_cycles = 1000000000;
    for (let cycle = 0; cycle < num_of_cycles; cycle++) {

        tilt_north();
        tilt_west();
        tilt_south();
        tilt_east();

        // find a pattern
        const pattern = panel.rocks.map(row => row.join('')).join('');
        if (patterns.includes(pattern)) {
            log('Found a pattern at cycle', cycle, 'matches cycle', patterns.indexOf(pattern), 'for frequency', cycle - patterns.indexOf(pattern));
            const remaining = (num_of_cycles - patterns.indexOf(pattern)) % (cycle - patterns.indexOf(pattern));

            cycle = num_of_cycles - remaining;
            continue;
        }
        patterns.push(pattern);
    }

    return load(panel);
}

type ControlPanel = {
    readonly width: number;
    readonly height: number;
    rocks: string[][];
}

function parse(input: string[]): ControlPanel {
    return {
        height: input.length,
        width: input[0].length,
        rocks: input.map(row => row.split('')),
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

function print(panel: ControlPanel): void {
    panel.rocks.forEach(row => console.log(row.join('')));
}
