import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const records = parse(input);
const cache: Record<string, number> = {};

{
    using sw = new Stopwatch('part one');
    console.log('What is the sum of those counts?', part_one(records));
}

{
    using sw = new Stopwatch('part two');
    console.log('what is the new sum of possible arrangement counts?', part_two(records));
}

function part_one(records: Input[]): number {
    /**
     * Part 1, attempt 1; let's just try all possible configurations and see if they match
     * with the groups description.
     *
     * I use recursion to generate all possible configurations.
     *
     * Attempt 2; I found advise on Reddit on leveraging dynamic programming.
     * Using this allows part 1 to be computed in less than 60ms. I think we need
     * to optimize for part 2 still.
     *
     * The trick is to reduce the number of possibilities. The moment we detect that a
     * path is not feasible, we immediately jump out of the recursion.
     *
     * @see https://www.reddit.com/r/adventofcode/comments/18ghux0/comment/kd0npmi/?utm_source=reddit&utm_medium=web2x&context=3
     */
    let sum = 0;

    for (const record of records) {
        const count_arrangements = (arrangement: string, groups: number[]): number => {
            log('');
            log('count_arrangements for', arrangement, groups.join(','));

            if (arrangement.length === 0) {
                log('No more arrangments left', groups.length);
                return groups.length === 0 ? 1 : 0;
            }

            if (groups.length === 0) {
                log('No more groups', arrangement.indexOf('#'));
                return arrangement.indexOf('#') === -1 ? 1 : 0;
            }

            const minimum_length = groups.reduce((length, group) => length + group, groups.length - 1);
            if (arrangement.length < minimum_length) {
                log('arrangment is shorter that required', arrangement.length, minimum_length);
                return 0;
            }

            // look at first char
            const p = arrangement.charAt(0);
            if (p === '.') {
                // discard and continue
                log('Found ., stripping of and continuing', arrangement.slice(1));
                return count_arrangements(arrangement.slice(1), groups);
            }

            if (p === '#') {
                // check if the next groups[0] - 1 chars are either `?` or `#`
                // keep in mind that a group of 3 MUST be followed by either `?` or `.`
                const group = arrangement.slice(0, groups[0]);
                log('Found #, group:' , group, 'followed by', arrangement.charAt(groups[0]));
                if (group.includes('.')) {
                    log('group contains a ., so this path is not possible');
                    return 0; // not possible
                }


                if (arrangement.charAt(groups[0]) === '#') {
                    log('cannot create group as the group is followed by #, so not possible');
                    return 0;
                }

                const wildcards = group.split('?').length - 1;
                log('This path is possible, number of ? in group:', wildcards, 'incrementing with', (wildcards > 0 ? Math.pow(2, wildcards) : 0));
                // this path is possible, remove group + following character and the group and continue
                return count_arrangements(arrangement.slice(groups[0] + 1), groups.slice(1));
            }

            log('Found ?, so replacing with both # and .');
            return count_arrangements('#' + arrangement.slice(1), groups) // replace with #
                + count_arrangements('.' + arrangement.slice(1), groups); // replace with .
        }

        sum += count_arrangements(record.springs.join(''), record.groups);
    }

    return sum;
}

function part_two(records: Input[]): number {
    /**
     * The first attempt was to unfold the input and put the result in part_one(). Well, that did not
     * work....
     *
     * I remembered a puzzle from a previous edition - I cannot find the exact puzzle - where a lot
     * of the Python users received huge benefits from memoization. Reading through some posts on
     * Reddit confirmed my suspicion.
     *
     * Memoization makes that repeated combinations of arrangements and groups are stored in memory,
     * preventing having to perform a lot of computations.
     *
     * I think there might be some more improvements to be made in the recursive function, but a solution
     * within a second is fine with me.
     */
    const unfolded = records.map(({ springs, groups }) => ({
        springs: [...springs, '?', ...springs, '?', ...springs, '?', ...springs, '?', ...springs],
        groups: [...groups, ...groups, ...groups, ...groups, ...groups],
    }));

    let sum = 0;

    for (const record of unfolded) {
        const count_arrangements = (arrangement: string, groups: number[]): number => {
            log('');
            log('count_arrangements for', arrangement, groups.join(','));

            const cache_key = arrangement + '-' + groups.join(',');
            if (cache_key in cache) {
                return cache[cache_key];
            }

            if (arrangement.length === 0) {
                log('No more arrangments left', groups.length);
                return groups.length === 0 ? 1 : 0;
            }

            if (groups.length === 0) {
                log('No more groups', arrangement.indexOf('#'));
                return arrangement.indexOf('#') === -1 ? 1 : 0;
            }

            const minimum_length = groups.reduce((length, group) => length + group, groups.length - 1);
            if (arrangement.length < minimum_length) {
                log('arrangment is shorter that required', arrangement.length, minimum_length);
                return 0;
            }

            // look at first char
            const p = arrangement.charAt(0);
            if (p === '.') {
                // discard and continue
                log('Found ., stripping of and continuing', arrangement.slice(1));
                cache[cache_key] = count_arrangements(arrangement.slice(1), groups);
                return cache[cache_key];
            }

            if (p === '#') {
                // check if the next groups[0] - 1 chars are either `?` or `#`
                // keep in mind that a group of 3 MUST be followed by either `?` or `.`
                const group = arrangement.slice(0, groups[0]);
                log('Found #, group:' , group, 'followed by', arrangement.charAt(groups[0]));
                if (group.includes('.')) {
                    log('group contains a ., so this path is not possible');
                    cache[cache_key] = 0;
                    return 0; // not possible
                }


                if (arrangement.charAt(groups[0]) === '#') {
                    log('cannot create group as the group is followed by #, so not possible');
                    cache[cache_key] = 0;
                    return 0;
                }

                const wildcards = group.split('?').length - 1;
                log('This path is possible, number of ? in group:', wildcards, 'incrementing with', (wildcards > 0 ? Math.pow(2, wildcards) : 0));
                // this path is possible, remove group + following character and the group and continue
                cache[cache_key] = count_arrangements(arrangement.slice(groups[0] + 1), groups.slice(1));
                return cache[cache_key];
            }

            log('Found ?, so replacing with both # and .');
            cache[cache_key] = count_arrangements('#' + arrangement.slice(1), groups) // replace with #
                + count_arrangements('.' + arrangement.slice(1), groups); // replace with .
            return cache[cache_key];
        }

        sum += count_arrangements(record.springs.join(''), record.groups);
    }

    return sum;
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
