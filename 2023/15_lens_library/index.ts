import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim();
const debug: boolean = !!(process.env.DEBUG || false);

const codes = input.split(',');

{
    using sw = new Stopwatch('part one');
    console.log('What is the sum of the results?', part_one(codes));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(codes: string[]): number {
    /**
     * Read, split, apply instructions...
     */
    let sum = 0;

    for(const code of codes) {
        let current = 0;
        for(let i = 0; i < code.length; i++) {
            current += code.charCodeAt(i);
            current *= 17;
            current %= 256;
        }
        sum += current;
    }

    return sum;
}

function part_two(): number {
    return 0;
}

function parse(input: string[]): unknown {
    return null;
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
