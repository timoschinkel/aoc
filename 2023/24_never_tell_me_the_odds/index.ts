import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const points = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many of these intersections occur within the test area?', part_one(points));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(input: Points[]): number {
    /**
     * More math, but more on a highschool level compared to some of the previous
     * days. We can convert the input into a linear equation[0]. Those equations
     * can be used to find intersections[1]. With the intersections known we need
     * to verify if they are in the future - whether that is higher of lower
     * depends on vx being positive or negative - and if both x and y of the inter-
     * section fall within the boundaries.
     *
     * The code is a little verbose, but this way I could mimic the exact output
     * as available in the puzzle.
     *
     * [0]: https://content.byui.edu/file/b8b83119-9acc-4a7b-bc84-efacf9043998/1/Math-2-11-2.html
     * [1]: https://en.wikipedia.org/wiki/Line%E2%80%93line_intersection
     */
    const equations: LinearEquation[] = [];
    // Convert input to linear equations of the form "mx + c = y"
    for (const row of points) {
        const m = row.vy / row.vx;
        const c = row.py - (m * row.px);

        equations.push({ ...row, m, c });
    }

    const lower_boundary = process.argv[2] !== 'input' ? 7 : 200000000000000;
    const upper_boundary = process.argv[2] !== 'input' ? 27 : 400000000000000;

    let intersections = 0;
    // Compare all equations
    for (let o = 0; o < equations.length - 1; o++) {
        for (let a = o + 1; a < equations.length; a++) {
            const one = equations[o];
            const another = equations[a];

            log('');
            log('Hailstone A:', one.input);
            log('Hailstone B:', another.input);

            if (one.m === another.m) {
                log('Hailstones\' paths are parallel; they never intersect.');
                continue;
            }

            // ax + c = bx + d
            const x = (another.c - one.c) / (one.m - another.m); // (d - c) / (a - b)
            const y = one.m * x + one.c; // a * ((d - c)/(a - b)) + c === a * x + c

            const one_in_the_past = (one.vx < 0 && x > one.px) || (one.vx > 0 && x < one.px);
            const another_in_the_path = (another.vx < 0 && x > another.px) || (another.vx > 0 && x < another.px);
            if (one_in_the_past && another_in_the_path) {
                log(`Hailstones' paths crossed in the past for both hailstones.`);
            } else if (one_in_the_past) {
                log(`Hailstones' paths crossed in the past for hailstone A.`);
            } else if (another_in_the_path) {
                log(`Hailstones' paths crossed in the past for hailstone B.`);
            } else if (x >= lower_boundary && x <= upper_boundary && y >= lower_boundary && y <= upper_boundary) {
                intersections++;
                log(`Hailstones' paths will cross inside the test area (at x=${x}, y=${y}).`);
            } else {
                log(`Hailstones' paths will cross outside the test area (at x=${x}, y=${y}).`);
            }
        }
    }

    return intersections;
}

function part_two(): number {
    return 0;
}

type Points = {
    readonly input: string;

    readonly px: number;
    readonly py: number;
    readonly pz: number;

    readonly vx: number;
    readonly vy: number;
    readonly vz: number;
}

type LinearEquation = Points & { // mx + c = y
    readonly m: number;
    readonly c: number;
}

function parse(input: string[]): Points[] {
    return input.map((row) => {
        const [l, r] = row.split(' @ ');
        const [px, py, pz] = l.split(', ').map(i => parseInt(i));
        const [vx, vy, vz] = r.split(', ').map(i => parseInt(i));
        return {
            input: row,
            px, py, pz,
            vx, vy, vz,
        }
    });
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
