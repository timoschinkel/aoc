import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const map = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('How many steps are required to reach ZZZ?', part_one(map));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(map: Map): number {
    let steps = 0;
    let current = 'AAA';
    while (current !== 'ZZZ') {
        const direction = map.directions.charAt(steps % map.directions.length);
        const n = direction === 'L'
            ? map.nodes[current].left
            : map.nodes[current].right;
        log(steps, 'from', current, 'go', direction, 'to', n);

        current = n;
        steps++;
    }

    return steps;
}

function part_two(): number {
    return 0;
}

type Junction = {
    readonly left: string,
    readonly right: string,
}

type Map = {
    readonly directions: string,
    readonly nodes: Record<string, Junction>;
}

function parse(input: string[]): Map {
    return {
        directions: input[0],
        nodes: input.slice(2).reduce((nodes, line) => {
            const matches = line.match(/^([A-Z]+) = \(([A-Z]+), ([A-Z]+)\)$/si);
            return {
                ...nodes,
                [matches[1]]: { left: matches[2], right: matches[3] }
            }
        }, {})
    }
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
