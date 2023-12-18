import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const digplans = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many cubic meters of lava could it hold?', part_one(digplans));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(digplans: DigPlan[]): number {
    /**
     * Hello day 10 :wave: The trick is that we don't know upfront how big the
     * playing field is going to be.
     *
     * My approach was to keep track of the dug trenches including the direction
     * in which we have dug. With this direction we can apply the Points in Polygon
     * approach employed in day 10.
     */

    let position = { row: 0, col: 0 };
    let minCol = 0, maxCol = 0, minRow = 0, maxRow = 0;
    const trenches: Edge[] = [{ col: 0, row: 0, direction: '#', color: '' }];

    const empty = (length: number): unknown[] => new Array(length).fill(null);

    let iteration = 0;
    for(const { direction, meters, color } of digplans) {
        if (iteration === 0) {
            trenches[0].direction = direction;
            trenches[0].color = color;
        }

        log('Starting iteration', iteration++);
        switch (direction) {
            case 'U':
                // update direction of latest trench to ^
                trenches[trenches.length - 1].direction = '^';
                trenches.push(...empty(meters).map((_, index) => ({ row: position.row - index - 1, col: position.col, color, direction: '^' })))
                position = { row: position.row - meters, col: position.col };
                minRow = Math.min(minRow, position.row);
                break;
            case 'D':
                // update direction of latest trench to v
                trenches[trenches.length - 1].direction = 'v';
                trenches.push(...empty(meters).map((_, index) => ({ row: position.row + index + 1, col: position.col, color, direction: 'v' })))
                position = { row: position.row + meters, col: position.col };
                maxRow = Math.max(maxRow, position.row);
                break;
            case 'L':
                trenches.push(...empty(meters).map((_, index) => ({ row: position.row, col: position.col - index - 1, color, direction: '<' })))
                position = { row: position.row, col: position.col - meters };
                minCol = Math.min(minCol, position.col);
                break;
            case 'R':
                trenches.push(...empty(meters).map((_, index) => ({ row: position.row, col: position.col + index + 1, color, direction: '>' })))
                position = { row: position.row, col: position.col + meters };
                maxCol = Math.max(maxCol, position.col);
                break;
            default:
                throw new Error(`Wait, wut!? ${direction}`);
        }
    }

    let cnt = 0;
    for (let r = minRow; r <= maxRow; r++) {
        let row = '';

        const green = (str: string): string => `\x1b[42m${str}\x1b[0m`;
        const yellow = (str: string): string => `\x1b[43m${str}\x1b[0m`;

        let inside = false;
        for (let c = minCol; c <= maxCol; c++) {
            const pos = trenches.find(({ row, col }) => row === r && col === c);
            if (pos && ['U', '^'].includes(pos.direction)) {
                inside = true;
            } else if (pos && ['D', 'v'].includes(pos.direction)) {
                inside = false;
            }

            if (!pos && inside === true) cnt++;

            if (c === 0 && r === 0) {
                row += green(pos?.direction ?? '.');
            } else if (!pos && inside === true) {
                row += yellow('.');
            } else {
                row += pos?.direction ?? '.';
            }
        }
        log(row);
    }

    return trenches.length - 1 + cnt;
}

function part_two(): number {
    return 0;
}

type Position = {
    row: number;
    col: number;
}

type Edge = Position & {
    direction: string;
    color: string;
}

type DigPlan = {
    readonly direction: string;
    readonly meters: number;
    readonly color: string;
}

function parse(input: string[]): DigPlan[] {
    return input.map((row) => {
        const parts = row.split(/\s+/);

        return {
            direction: parts[0],
            meters: parseInt(parts[1]),
            color: parts[2].substring(1, parts[2].length - 1),
        };
    });
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
