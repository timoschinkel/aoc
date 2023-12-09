import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const rows = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What is the sum of these extrapolated values?', part_one(rows));
}

{
    using sw = new Stopwatch('part two');
    console.log('What is the sum of these extrapolated values?', part_two(rows));
}


function part_one(rows: number[][]): number {
    /**
     * I might be paying the price for this decision when part two comes around the corner,
     * but it seems like an appropriate solution; for every row we take the last value and
     * we add that to the last value of the array that is created when we take the difference
     * of all the values.
     */

    const solve = (row: number[]): number => {
        // if all numbers are the same, the next step will be 0, so we can skip
        // that step.
        if (is_all_the_same(row)) {
            return row[0] ?? 0;
        }

        return row[row.length - 1] + solve(difference(row));
    }

    return rows.reduce((sum, row) => sum += solve(row), 0);
}

function part_two(rows: number[][]): number {
    /**
     * My ass remains unbitten, for now 😅
     * Take the solution of part one, but instead of adding the last item of the difference
     * to the last item of the current row, we subtract the first item of the difference
     * from the first item of the current row.
     */
    const solve = (row: number[]): number => {
        // if all numbers are the same, the next step will be 0, so we can skip
        // that step.
        if (is_all_the_same(row)) {
            return row[0] ?? 0;
        }

        return row[0] - solve(difference(row));
    }

    return rows.reduce((sum, row) => sum += solve(row), 0);
}

function parse(input: string[]): number[][] {
    return input.map(line => line.split(' ').map(str => parseInt(str)));
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

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
