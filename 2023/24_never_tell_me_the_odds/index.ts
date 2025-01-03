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
    /**
     * To rephrase the puzzle; we need to find a new 3D vector that will intersect
     * with all the inputs. The typical scenario for an intersection is that
     * we know both vectors[0].
     *
     * [0]: https://socratic.org/questions/how-do-i-find-the-intersection-of-two-lines-in-three-dimensional-space
     * [1]: https://www.quora.com/How-do-you-find-the-line-that-intersects-two-other-lines-vectors-math
     * [2]: https://bertptrs.nl/2024/01/02/advent-of-code-2023-let-it-snow.html#day-24-linear-algebra-101
     * [3]: https://github.com/tmbarker/advent-of-code/blob/main/Solutions/Y2023/D24/Solution.cs
     */

    //  Source: https://github.com/tmbarker/advent-of-code/blob/main/Solutions/Y2023/D24/Solution.cs

    //  Let:
    //    <p_rock>(t) = <X,Y,Z> + t <DX,DY,DZ>
    //    <p_hail>(t) = <x,y,z> + t <dx,dy,dz>

    //  A rock-hail collision requires the following to be true:
    //    X + t DX = x + t dx
    //    Y + t DY = y + t dy
    //    Z + t DZ = z + t dz

    //  Which implies:
    //    t = (X-x)/(dx-DX)
    //    t = (Y-y)/(dy-DY)
    //    t = (Z-z)/(dz-DZ)

    //  Equating the first two equalities from above yields:
    //    (X-x)/(dx-DX) = (Y-y)/(dy-DY)
    //    (X-x) (dy-DY) = (Y-y) (dx-DX)
    //    X*dy - X*DY - x*dy + x*DY = Y*dx - Y*DX - y*dx + y*DX
    //    Y*DX - X*DY = Y*dx - y*dx + y*DX - X*dy + x*dy - x*DY

    //  Note that the LHS of the above equation is true for any hail stone. Evaluating
    //  the RHS again for a different hailstone, and setting the two RHS equal, yields
    //  the first of the below equations:

    //  (dy'-dy) X + (dx-dx') Y + (y-y') DX + (x'-x) DY =  x' dy' - y' dx' - x dy + y dx
    //  (dz'-dz) X + (dx-dx') Z + (z-z') DX + (x'-x) DZ =  x' dz' - z' dx' - x dz + z dx
    //  (dz-dz') Y + (dy'-dy) Z + (z'-z) DY + (y-y') DZ = -y' dz' + z' dy' + y dz - z dy

    //  The second and third are yielded by repeating the above process with X & Z, and
    //  then Y & Z. This is a system of equations with 6 unknowns. Using two different
    //  pairs of hailstones (e.g. three total hailstones) yields 6 equations with 6
    //  unknowns, which we can now solve relatively trivially using linear algebra.

    const Coefficients1 = (a: Points, b: Points): number[] =>
    {
        // (dy'-dy) X + (dx-dx') Y + (y-y') DX + (x'-x) DY =  x' dy' - y' dx' - x dy + y dx
        return [
            b.vy - a.vy,
            a.vx - b.vx,
            0,
            a.py - b.py,
            b.px - a.px,
            0,
            b.px * b.vy - b.py * b.vx - a.px * a.vy + a.py * a.vx
        ];
    }

    const Coefficients2 = (a: Points, b: Points): number[] =>
    {
        // (dz'-dz) X + (dx-dx') Z + (z-z') DX + (x'-x) DZ =  x' dz' - z' dx' - x dz + z dx
        return [
            b.vz - a.vz,
            0,
            a.vx - b.vx,
            a.pz - b.pz,
            0,
            b.px - a.px,
            b.px * b.vz - b.pz * b.vx - a.px * a.vz + a.pz * a.vx
        ];
    }

    const Coefficients3 = (a: Points, b: Points): number[] =>
    {
        // (dz-dz') Y + (dy'-dy) Z + (z'-z) DY + (y-y') DZ = -y' dz' + z' dy' + y dz - z dy
        return [
            0,
            a.vz - b.vz,
            b.vy - a.vy,
            0,
            b.pz - a.pz,
            a.py - b.py,
            -b.py * b.vz + b.pz * b.vy + a.py * a.vz - a.pz * a.vy
        ];
    }

    const matrix = [
        Coefficients1(points[0], points[1]),
        Coefficients1(points[0], points[2]),
        Coefficients2(points[0], points[1]),
        Coefficients2(points[0], points[2]),
        Coefficients3(points[0], points[1]),
        Coefficients3(points[0], points[2]),
    ];

    // https://matrix.reshish.com/gauss-jordanElimination.php
    //print(matrix);

    // We now have the augmented coefficient matrix. Try to solve it using partial
    // pivoting[0]
    //
    // [0]: https://web.mit.edu/10.001/Web/Course_Notes/GaussElimPivoting.html
    const solution = gaussian_elimination([ ...matrix ]);

    return solution[0] + solution[1] + solution[2];
}

function gaussian_elimination (matrix: number[][]): number[] {
    for (let col = 0; col < matrix[0].length - 1; col++) {
        //console.log('starting column', col);

        // Step 0a: Find the entry in the left column with the largest absolute value. This entry is called the pivot.
        let pivot = col;
        for (let row = col + 1; row < matrix.length; row++) {
            if (Math.abs(matrix[row][col]) > Math.abs(matrix[pivot][col])) {
                pivot = row;
            }
        }

        // Step 0b: Perform row interchange (if necessary), so that the pivot is in the first row.
        let m = [ ...matrix ];
        if (pivot !== col) {
            m[col] = [ ...matrix[pivot]];
            m[pivot] = [ ...matrix[col]];
        }

        //console.log('found pivot', pivot);
        //print(m);

        // Step 1: Gaussian Elimination
        // Make sure the entire column contains zeroes, Gaussian operations[1]:
        // Type 1. Interchange any two rows.
        // Type 2. Multiply a row by a nonzero constant.
        // Type 3. Add a multiple of one row to another row.
        //
        // [1]: https://www.cliffsnotes.com/study-guides/algebra/linear-algebra/linear-systems/gaussian-elimination
        for (let row = col + 1; row < m.length; row++) {
            if (m[row][col] !== 0) {
                const factor = m[row][col] / m[col][col];
                // console.log('row', row, 'contains', 'factor', factor);
                // console.log('m[row][0]', m[row][0]);
                // console.log('m[0][0]', m[0][0]);
                // console.log('result', m[row][0] - factor * m[0][0]);
                // update all columns in row
                m[row] = m[row].map((num, c) => num - (factor * m[col][c]));
            }
        }

        //console.log('After Gaussian elimination');
        //print(m);
        matrix = m;
    }

    // Perform back substitution
    // I hardcoded this for this specific puzzle, but we should be able to do this programmatically.

    // matrix[5][5] * vz = matrix[5][6]
    const vz = matrix[5][6] / matrix[5][5];
    //console.log('vz', vz, Math.round(vz));

    // matrix[4][4] * vy + matrix[4][5] * vz = matrix[4][6]
    const vy = (matrix[4][6] - (matrix[4][5] * vz)) / matrix[4][4];
    //console.log('vy', vy, Math.round(vy));

    // matrix[3][3] * vx + matrix[3][4] * vy + matrix[3][5] * vz = matrix[3][6]
    const vx = (matrix[3][6] - (matrix[3][4] * vy) - (matrix[3][5] * vz)) / matrix[3][3];
    //console.log('vx', vx, Math.round(vx));

    // matrix[2][2] * pz + matrix[2][3] * vx + matrix[2][4] * vy + matrix[2][5] * vz = matrix[2][6]
    const pz = (matrix[2][6] - (matrix[2][3] * vx) - (matrix[2][4] * vy) - (matrix[2][5] * vz)) / matrix[2][2];
    //console.log('pz', pz, Math.round(pz));

    // matrix[1][1] * py + matrix[1][2] * pz + matrix[1][3] * vx + matrix[1][4] * vy + matrix[1][5] * vz = matrix[1][6]
    const py = (matrix[1][6] - (matrix[1][2] * pz) - (matrix[1][3] * vx) - (matrix[1][4] * vy) - (matrix[1][5] * vz)) / matrix[1][1];
    //console.log('py', py, Math.round(py));

    // matrix[0][0] + px + matrix[0][1] * py + matrix[0][2] * pz + matrix[0][3] * vx + matrix[0][4] * vy + matrix[0][5] * vz = matrix[0][6]
    const px = (matrix[0][6] - (matrix[0][1] * py) - (matrix[0][2] * pz) - (matrix[0][3] * vx) - (matrix[0][4] * vy) - (matrix[0][5] * vz)) / matrix[0][0];
    //console.log('px', px, Math.round(px));

    // Due to how Gaussian Elimination is implemented we have floating point numbers, rounding them gives me the
    // correct integer values.
    return [Math.round(px), Math.round(py), Math.round(pz), Math.round(vx), Math.round(vy), Math.round(vz)];
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

function print(matrix: number[][]): void {
    for(const row of matrix) {
        console.log(row.map(n => Math.round(n * 100) / 100).join("\t"));
    }
    console.log('');
}
