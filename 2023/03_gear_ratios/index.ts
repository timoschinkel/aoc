import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = !!(process.env.DEBUG || false);

const schematic = input.map((line) => line.split(''));

const red = (str: string): string => `\x1b[31m${str}\x1b[0m`;
const green = (str: string): string => `\x1b[32m${str}\x1b[0m`;

console.log('What is the sum of the IDs of those games?', part_one(schematic));

function part_one(schematic: string[][]): number {
    /*
     * Iterate over the schematic to find all numeric values. Once we found a numeric value
     * we read the row until we reach the end of the number. While we do so we look around us
     * to see if we are adjacent to a "symbol".
     */
    let sum = 0;

    const height = schematic.length;
    const width = schematic[0].length;

    const get = (col: number, row: number): string =>
        col < 0 || col >= width || row < 0 || row >= height
            ? '.'
            : schematic[row][col];

    const numeric = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    const ignore = ['.', ...numeric];

    let visualization = '';
    for (let row = 0; row < height; row++) {
        for(let col = 0; col < width; col++) {
            const cell = get(col, row);

            if (!numeric.includes(cell)) {
                visualization += cell;
                continue;
            }

            // read entire number
            const start = col;

            // Look top-left, left, bottom-left, top and bottom to see if we're adjacent to a
            // "symbol".
            let adjacent = !ignore.includes(get(col - 1, row - 1)) ||
                !ignore.includes(get(col - 1, row)) ||
                !ignore.includes(get(col - 1, row + 1)) ||
                !ignore.includes(get(col, row - 1)) ||
                !ignore.includes(get(col, row + 1));

            let num = cell;
            while (numeric.includes(get(col + 1, row))) {
                col++;
                num += get(col, row);
                adjacent = adjacent || !ignore.includes(get(col, row - 1)) || !ignore.includes(get(col, row + 1));
            }

            adjacent = adjacent || !ignore.includes(get(col + 1, row - 1)) ||
                !ignore.includes(get(col + 1, row)) ||
                !ignore.includes(get(col + 1, row + 1));

            log('Found', start, row, num.length, col, parseInt(num), adjacent);

            if (adjacent) {
                visualization += green(num);
                sum += parseInt(num);
            } else {
                visualization += red(num);
            }
        }

        visualization += "\n";
    }

    visualize(visualization);

    return sum;
}

function visualize(str: string): void {
    if (process.env.VISUALIZE) {
        console.log(str);
    }
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
