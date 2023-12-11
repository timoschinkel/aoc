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
    console.log('What is the sum of these lengths?', part_two(map));
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

function part_two(map: Map): number {
    /**
     * Instead of actually expanding the universe, which would become way too large, we
     * can also keep track of all the rows and columns that were empty and needed expansion.
     * If we use this information we can calculate the new column and row values for each
     * galaxy.
     */
    const empty_rows: number[] = [];
    const empty_columns: number[] = [];

    for (let row = 0; row < map.height; row++) {
        const is_empty = (row: string[]): boolean =>
            row.filter(g => g !== '.').length === 0;

        if (is_empty(map.galaxies[row])) {
            empty_rows.push(row);
        }
    }

    for(let col = 0; col < map.width; col++) {
        const is_empty = (col: number): boolean =>
            map.galaxies.map(row => row[col]).filter(g => g !== '.').length === 0;

        if (is_empty(col)) {
            empty_columns.push(col);
        }
    }

    log('empty_rows', empty_rows);
    log('empty_columns', empty_columns);

    const expansion = 1000000;

    // find all pairs
    const galaxies: Point[] = [];
    for (let row = 0; row < map.height; row++) {
        for (let column = 0; column < map.width; column++) {
            if (map.galaxies[row][column] === '#') {
                galaxies.push({ row, column });
            }
        }
    }

    log(galaxies);

    const expanded = galaxies.map((galaxy: Point): Point => {
        const num_of_duplicate_columns = (column: number) => {
            return empty_columns.filter(c => c < column).length * (expansion - 1);
        }

        const num_of_duplicate_rows = (row: number) => {
            return empty_rows.filter(r => r < row).length * (expansion - 1);
        }

        return { row: galaxy.row + num_of_duplicate_rows(galaxy.row), column: galaxy.column + num_of_duplicate_columns(galaxy.column) }
    });

    log(expanded);

    const manhattan = (one: Point, another: Point): number =>
        Math.abs(one.column - another.column) + Math.abs(one.row - another.row);

    // sum shortest paths using Manhattan distance
    let sum = 0;
    for (let g = 0; g < expanded.length; g++) {
        for (let o = g + 1; o < expanded.length; o++) {
            const m = manhattan(expanded[g], expanded[o]);
            log('Shortest path from', expanded[g].column, 'x', expanded[g].row, 'to', expanded[o].column, 'x', expanded[o].row, m);
            sum += m;
        }
    }

    return sum;
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
