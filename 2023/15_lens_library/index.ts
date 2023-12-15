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
    console.log('What are the new total winnings?', part_two(codes));
}


function part_one(codes: string[]): number {
    /**
     * Read, split, apply instructions...
     */
    let sum = 0;

    for(const code of codes) {
        sum += hash(code);
    }

    return sum;
}

function part_two(codes: string[]): number {
    /**
     * Mostly administration; important is that we keep the order of
     * items in the boxes. Luckily I'm using JavaScript and not Python
     * and is the dictionary stable.
     */
    const boxes: Record<string, number>[] = new Array(256).fill({});

    for (const code of codes) {
        const { groups: {label, op, target } } = code.match(/^(?<label>[^=-]+)(?<op>(=|-))(?<target>.+)?$/si);

        const box = hash(label);
        if (op === '=') {
            // target is the focal strength
            boxes[box] = { ...boxes[box], [label]: parseInt(target) };
        } else if (op === '-') {
            delete boxes[box][label];
        }

        log(`After "${code}"`);
        print(boxes);
        log('');
    }

    // calculate focal length
    let sum = 0;
    for (let b = 0; b < boxes.length; b++) {
        const lenses = Object.entries(boxes[b]);
        for(let slot = 0; slot < lenses.length; slot++) {
            const power = (b + 1) * (slot + 1) * lenses[slot][1];
            log(lenses[slot][0], power);
            sum += power;
        }
    }
    return sum;
}

function hash(code: string): number {
    let current = 0;
    for(let i = 0; i < code.length; i++) {
        current += code.charCodeAt(i);
        current *= 17;
        current %= 256;
    }
    return current;
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}

function print(boxes: Record<string, number>[]): void {
    if (!debug) return;

    for (let b = 0; b < boxes.length; b++) {
        const lenses = Object.entries(boxes[b]);
        if (lenses.length === 0) continue;
        log(`Box ${b}: ${lenses.map(([key, value]) => `[${key} ${value}]`).join(' ')}`);
    }
}
