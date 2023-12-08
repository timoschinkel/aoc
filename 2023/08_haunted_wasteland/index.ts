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
    console.log('What are the new total winnings?', part_two(map));
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

function part_two(map: Map): number {
    /**
     * I tried running this as is, but that took way too long. By outputting some
     * debug information I realized that every start position ends up with a valid
     * end position within reasonable time. Since the directions are circular, it
     * must mean that the paths are periodic as well.
     *
     * Follow the paths for every node ending with `A` until all of them have reached
     * a node ending with `Z`. As this is periodic, we can take the least common multiple
     * to determine when all paths are at a valid end position.
     */
    const start = Object.keys(map.nodes).filter(node => node.endsWith('A'));

    const end = start.map(() => undefined);
    const is_end = (): boolean => {
        return end.filter(node => node !== undefined).length === end.length;
    }

    let steps = 0;
    let current = [...start];

    while (!is_end()) {
        const direction = map.directions.charAt(steps % map.directions.length);

        const newNodes = current.reduce((carry, node) => {
            const newNode = direction === 'L'
                ? map.nodes[node].left
                : map.nodes[node].right;
            return [ ...carry, newNode];
        }, []);

        current = newNodes;
        steps++;

        const endsWithZ = current.findIndex(node => node.endsWith('Z'));
        if (endsWithZ !== -1) {
            log('Found path for', endsWithZ, 'in', steps, 'steps');
            end[endsWithZ] = steps;
        }
    }

    return lcm_all(...end);
}

function lcm_all(...numbers: number[]): number {
    return numbers.reduce((carry, value) => lcm(carry, value), 1);
}

/**
 * Calculate the least common multiple
 * @see https://en.wikipedia.org/wiki/Least_common_multiple#Calculation
 */
function lcm(a: number, b: number): number {
    return Math.abs(a) * (Math.abs(b) / gcd(a, b));
}

/**
 * Calculate the greatest common divider
 * @see https://en.wikipedia.org/wiki/Least_common_multiple#Calculation
 */
function gcd(a: number, b: number): number {
    return !b ? a : gcd(b, a % b);
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
            const matches = line.match(/^([A-Z0-9]+) = \(([A-Z0-9]+), ([A-Z0-9]+)\)$/si);
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
