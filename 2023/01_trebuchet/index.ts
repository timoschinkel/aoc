import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n");
const debug: boolean = false;

console.log('What is the sum of all of the calibration values?', part_one(input));

function part_one(input: string[]): number {
    let sum = 0;

    input.forEach(line => {
        const list = line.split('').filter(char => char === parseInt(char, 10).toString());
        const calibration_value = parseInt(`${list[0]}${list[list.length - 1]}`, 10);
        log(line, list, calibration_value);
        sum += calibration_value;
    });

    return sum;
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(args);
    }
}
