import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim().split("\n").map(line => line.trim());
const debug: boolean = !!(process.env.DEBUG || false);

const patterns = parse(input);

{
    using sw = new Stopwatch('part one');
    console.log('What number do you get after summarizing all of your notes?', part_one(patterns));
}

{
    using sw = new Stopwatch('part two');
    console.log('What are the new total winnings?', part_two());
}


function part_one(patterns: Pattern[]): number {
    /**
     * My approach is to limit the number of locations I need to check. So
     * for the vertical mirror points I start with all possible candidate
     * positions - which are all but the first and last item - and filter
     * per row which of those candidates continue to be valid. I do this by
     * splitting the string and comparing a mirrored version of left with
     * the regular version of right.
     *
     * For the horizontal mirror points I do the same, but I construct patterns
     * of the columns.
     *
     * Two things that cost me time were:
     * - The mirror point is *between* points
     * - If we found a vertical mirror, we do not also check for a horizontal
     * mirror
     */
    let sum = 0;

    const find_mirror_locations = (pattern: string[], candidates: number[]): number[] => {
        const confirmed = [];

        for (const candidate of candidates) {
            const left = pattern.slice(0, candidate).reverse().join('');
            const right = pattern.slice(candidate).join('');

            if (left.length > right.length && !left.startsWith(right)) {
                continue;
            }

            if (left.length <= right.length && !right.startsWith(left)) {
                continue;
            }

            confirmed.push(candidate);
        }

        return confirmed;
    }

    for (const pattern of patterns) {
        log('');
        log('Starting new pattern');

        // find vertical mirror location by checking every row
        let candidates = new Array(pattern.width - 1).fill(0).map((_, key) => key + 1); // no need to check first and last item
        for (let row = 0; row < pattern.height; row++) {
            candidates = find_mirror_locations(pattern.pattern[row], candidates);
            if (candidates.length === 0) {
                log('No vertical candidates left, leaving attempt');
                break; // early exit
            }
        }

        if (candidates.length === 1) {
            // we have found a mirror point!
            log('Found a vertical mirror line at', candidates[0]);
            sum += candidates[0];

            // Wow, this one had me stumped; we don't have to check for
            // a different orientation mirror point if we found one here.
            continue;
        }

        if (candidates.length > 1) {
            log('Found multiple candidates left', candidates);
        }

        // find horizontal mirror location by checking columns
        candidates = new Array(pattern.height - 1).fill(0).map((_, key) => key + 1); // no need to check first and last item
        for (let column = 0; column < pattern.height; column++) {
            candidates = find_mirror_locations(
                pattern.pattern.map(row => row[column]),
                candidates
            );
            if (candidates.length === 0) {
                log('No horizontal candidates left, leaving attempt');
                break; // early exit
            }
        }

        if (candidates.length === 1) {
            // we have found a mirror point!
            log('Found a horizontal mirror line at', candidates[0]);
            sum += (100 * candidates[0]);
        }

        if (candidates.length > 1) {
            log('Found multiple candidates left', candidates);
        }
    }

    return sum;
}

function part_two(): number {
    return 0;
}

type Coordinate = {
    readonly row: number;
    readonly column: number;
}

type Pattern = {
    readonly width: number;
    readonly height: number;
    readonly pattern: string[][];
}

function parse(input: string[]): Pattern[] {
    const patterns: Pattern[] = [];

    let pattern: string[][] = [];
    let width: number = 0;
    for(const line of input) {
        if (line === '') {
            if (pattern.length > 0) {
                patterns.push({
                    width,
                    height: pattern.length,
                    pattern
                });
            }
            width = 0;
            pattern = [];
            continue;
        }

        pattern.push(line.split(''));
        width = line.length;
    }

    if (pattern.length > 0) {
        patterns.push({
            width,
            height: pattern.length,
            pattern
        });
    }

    return patterns;
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
