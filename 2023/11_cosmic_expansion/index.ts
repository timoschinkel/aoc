import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const map = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What is the sum of these lengths?', part_one(map));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(map: Map): number {
    /**
     * There's two part of this problem; first you need to make sure you prepare your
     * map properly by expanding empty rows and columns. Adding empty rows is relatively
     * easy, but adding columns is a bit more work. There's probably a very smart way of
     * doing this via matrix rotations, but I'm keeping it simple.
     *
     * Step two is finding the shortest paths. The nature of the map - every step is 1 value -
     * we can use Manhattan distance for this.
     */

    // step 1; prepare map
    let prepared: string[][] = [];

    let rows_added = 0;
    for(let row = 0; row < map.height; row++) {
        const is_empty = (row: string[]): boolean =>
            row.filter(g => g !== '.').length === 0;

        prepared.push(map.galaxies[row]);
        if (is_empty(map.galaxies[row])) {
            rows_added++;
            prepared.push(map.galaxies[row]);
        }
    }

    let columns_added = 0;
    for(let col = 0; col < map.width; col++) {
        const is_empty = (col: number): boolean =>
            map.galaxies.map(row => row[col]).filter(g => g !== '.').length === 0;

        if (is_empty(col)) {
            prepared = prepared.map(row => [...row.slice(0, col + columns_added), '.', ...row.slice(col + columns_added)]);
            columns_added++;
        }
    }

    let width = map.width + columns_added;
    let height = map.height + rows_added;

    print(prepared);

    // find all pairs
    const galaxies: Point[] = [];
    for (let row = 0; row < height; row++) {
        for (let column = 0; column < width; column++) {
            if (prepared[row][column] === '#') {
                galaxies.push({ row, column });
            }
        }
    }

    const manhattan = (one: Point, another: Point): number =>
        Math.abs(one.column - another.column) + Math.abs(one.row - another.row);

    // sum shortest paths using Manhattan distance
    let sum = 0;
    for (let g = 0; g < galaxies.length; g++) {
        for (let o = g + 1; o < galaxies.length; o++) {
            const m = manhattan(galaxies[g], galaxies[o]);
            log('Shortest path from', g + 1, 'to', o + 1, m);
            sum += m;
        }
    }

    return sum;
}

function part_two(): number {
    return 0;
}

type Point = {
    readonly column: number;
    readonly row: number;
}

type Map = {
    readonly width: number;
    readonly height: number;
    readonly galaxies: string[][];
}

function parse(input: string[]): Map {
    return {
        height: input.length,
        width: input[0].length,
        galaxies: input.map(line => line.trim().split(''))
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

function print(map: string[][]): void {
    if (!debug) {
        return;
    }

    for(const row of map) {
        console.log(row.join(''));
    }
}
