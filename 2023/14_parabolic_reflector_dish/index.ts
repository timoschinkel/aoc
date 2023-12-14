import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const panel = parse(input);
const Rock = {
    Squared: '#',
    Rounded: 'O',
}

{
    using sw = new Stopwatch('part one');
    console.log('What is the total load on the north support beams?', part_one(panel));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
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

function part_two(): number {
    return 0;
}

type ControlPanel = {
    readonly width: number;
    readonly height: number;
    rocks: string[];
}

function parse(input: string[]): ControlPanel {
    return {
        height: input.length,
        width: input[0].length,
        rocks: input,
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
