import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const records = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What is the sum of those counts?', part_one(records));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(records: Input[]): number {
    /**
     * Part 1, attempt 1; let's just try all possible configurations and see if they match
     * with the groups description.
     *
     * I use recursion to generate all possible configurations.
     */
    let sum = 0;

    const count_arrangements = (record: Input): number => {
        const matches = (arrangement: string[]): boolean => {
            const groups = arrangement.join('').split(/\.+/si).filter(group => group).map(group => group.length).join(',');

            //log('matches', arrangement.join(''), groups, "\t\t", record.groups.join(','));
            return groups === record.groups.join(',');
        }

        // generate arrangments
        const wildcard_positions = Object.entries(record.springs)
            .filter(([_position, spring]) => spring === '?')
            .map(([position, _]) => parseInt(position));

        const arrangements = (springs: string[], wildcard_positions: number[]): number => {
            // RECURSION!
            if (wildcard_positions.length === 0) {
                return matches(springs) ? 1 : 0;
            }

            // Potential optimization: can we preemptively verify if this path is valid? If not,
            // we can stop the recursion.

            const position = wildcard_positions[0];
            return arrangements(springs.map((spring, index) => index === position ? '.' : spring), wildcard_positions.slice(1)) +
                arrangements(springs.map((spring, index) => index === position ? '#' : spring), wildcard_positions.slice(1))
        }

        return arrangements(record.springs, wildcard_positions);
    }

    for (const record of records) {
        const num_of_arrangements = count_arrangements(record);
        log(record.springs.join(''), record.groups.join(','), num_of_arrangements, 'arrangement' + (num_of_arrangements != 1 ? 's' : ''));
        sum += num_of_arrangements;
    }

    return sum;
}

function part_two(): number {
    return 0;
}

type Input = {
    readonly springs: string[];
    readonly groups: number[];
}

function parse(input: string[]): Input[] {
    return input.map((line) => {
        const [springs, groups] = line.split(' ');
        return {
            springs: springs.split(''),
            groups: groups.split(',').map(group => parseInt(group.trim())),
        }
    });
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
