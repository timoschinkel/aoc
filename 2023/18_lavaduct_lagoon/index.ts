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
    console.log('How many cubic meters of lava could the lagoon hold?', part_two(digplans));
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

function part_two(digplans: DigPlan[]): number {
    /**
     * I solved part 1 as I did with Day 10, should be fast enough. Nopes!
     *
     * I had to digg into the internet for this; For day 10 I resorted to the
     * Point in Polygon algorithm[1]. But the area is too big. For a moment
     * I thought back to day 22 of 2011[2], and I considered splitting up the
     * trenches into rectangles. But I remembered during the solving of day 10
     * I read about something called the Shoelace formula[3], and some commenters
     * on Reddit mentioned this approach. This approach works remarkably well.
     * But there's still the problem of the border trenches; Shoelace calculates
     * the area. For that I needed another algorithm called Pick's Theorem[4].
     *
     * I convert the digplan instructions, and calculate the corners of the
     * lagoon. I take those corners and use Shoelace formula to calculate the area.
     * I sum up all the borders and put that in Pick's theorem. Sounds simple,
     * when you know how to do this...
     *
     * [1]: https://en.wikipedia.org/wiki/Point_in_polygon
     * [2]: https://adventofcode.com/2021/day/22
     * [3]: https://en.wikipedia.org/wiki/Shoelace_formula
     * [4]: https://en.wikipedia.org/wiki/Pick%27s_theorem
     */

    const from_color = (color: string): DigPlan => {
        const meters = color.substring(1, 6);
        const direction = color.charAt(6);

        const direction_mapping = {
            '0': 'R',
            '1': 'D',
            '2': 'L',
            '3': 'U',
        }

        return {
            meters: parseInt(meters, 16),
            direction: direction_mapping[direction],
            color: '',
        };
    }

    // Shoelace formula
    // and Pick's theorem
    let borders = 0;
    const points: Position[] = [];
    let current: Position = { row: 0, col: 0 };

    for (const digplan of digplans) {
        const { meters, direction } = from_color(digplan.color);

        switch (direction) {
            case 'U':
                current = { row: current.row - meters, col: current.col };
                break;
            case 'D':
                current = { row: current.row + meters, col: current.col };
                break;
            case 'L':
                current = { row: current.row, col: current.col - meters };
                break;
            case 'R':
                current = { row: current.row, col: current.col + meters };
                break;
            default:
                throw new Error(`Wait, wut!? ${direction}`);
        }
        borders += meters;
        points.push({ ...current });
    }

    // Apply shoelace formula
    let shoelace_one = 0;
    let shoelace_two = 0
    for (let p = 0; p < points.length - 1; p++) {
        // x0 - y1 + x1 - y0
        shoelace_one += points[p].col * points[p + 1].row;
        shoelace_two += points[p].row * points[p + 1].col;
    }

    const shoelace = Math.abs(shoelace_one - shoelace_two) / 2;

    // Apply Pick's theorem
    return shoelace + (borders / 2) + 1;
}

type Position = {
    row: number;
    col: number;
}

type Edge = Position & {
    direction: string;
    color?: string;
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
