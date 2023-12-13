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
    console.log('What number do you get after summarizing the new reflection line in each pattern in your notes?', part_two(patterns));
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

function part_two(patterns: Pattern[]): number {
    /**
     * I had a look to see how I can limit the amount of permutations, but
     * I could not find a reliable way. I made one big mistake; I decided to
     * rewrite my solution from part 1. Well, let's say that mistakes were
     * made.
     *
     * Compared to part 1; I moved calculating the possible mirror points into
     * a separate function, and I removed the "candidates" list. This gives extra
     * results, but it allows me to cache the result. I also introduced a new type
     * to keep the existing mirror location from part 1, as we know that the
     * existing mirror location cannot be the new mirror location.
     *
     * With the caching optimization in place it is a matter of simply flipping every
     * point and find the new mirror location. Rinse and repeat for every pattern from
     * the input.
     */
    let sum = 0;

    const cache: Record<string, number[]> = {};

    const find_mirror_locations = (pattern: string): number[] => {
        if (pattern in cache) {
            return cache[pattern];
        }

        const locations = [];

        for (let position = 1; position < pattern.length; position++) {
            const left = pattern.slice(0, position).split('').reverse().join('');
            const right = pattern.slice(position);

            if (left.length > right.length && !left.startsWith(right)) {
                continue;
            }

            if (left.length <= right.length && !right.startsWith(left)) {
                continue;
            }

            locations.push(position);
        }

        cache[pattern] = locations;
        return locations;
    }

    const intersect = (one: number[], another: number[]): number[] =>
        one.filter(v => another.length > 0 && another.includes(v));

    const find_mirror_point_score = (pattern: Pattern, existing?: Mirror): Mirror | null => {
        // vertical
        let candidates = find_mirror_locations(pattern.pattern[0].join(''));

        if (existing && existing.direction === 'V') { // filter out existing
            candidates = candidates.filter(position => position != existing.position);
        }

        for (let row = 1; row < pattern.height; row++) {
            candidates = intersect(candidates, find_mirror_locations(pattern.pattern[row].join('')));

            if (candidates.length === 0) {
                // No mirror points found
                log('No vertical candidates left, leaving attempt');
                break;
            }
        }

        if (candidates.length === 1) {
            // mirror point found
            log('vertical mirror point found at', candidates[0]);
            return { position: candidates[0], direction: 'V' };
        }

        if (candidates.length > 1) {
            console.error('FOUND MORE THAN 1 MIRROR POINT!', candidates);
        }

        // horizontal
        candidates = find_mirror_locations(pattern.pattern.map(row => row[0]).join(''));

        if (existing && existing.direction === 'H') { // filter out existing
            candidates = candidates.filter(position => position != existing.position);
        }

        for (let column = 1; column < pattern.width; column++) {
            candidates = intersect(candidates, find_mirror_locations(pattern.pattern.map(row => row[column]).join('')));

            if (candidates.length === 0) {
                // No mirror points found
                return null;
            }
        }

        if (candidates.length === 1) {
            // mirror point found
            log('horizontal mirror point found at', candidates[0]);
            return { position: candidates[0], direction: 'H' };
        }

        if (candidates.length > 1) {
            console.error('FOUND MORE THAN 1 MIRROR POINT!', candidates);
        }

        // No mirror points found
        return null;
    }

    const find_smudge = (pattern: Pattern, unsmudged: Mirror): number => {
        // find all possible smudge locations
        const smudge_locations: Coordinate[] = [/*{ column: 4, row: 0}*/];
        for (let row = 0; row < pattern.height; row++) {
            for (let column = 0; column < pattern.width; column++) {
                smudge_locations.push({ row, column });
            }
        }

        // check for every smudge location if it results in a valid mirror
        for(const { row, column } of smudge_locations) {
            const smudged = {
                ...pattern,
                pattern: [ ...pattern.pattern.map(row => ([ ...row ])) ]
            };
            smudged.pattern[row][column] = (smudged.pattern[row][column] === '#' ? '.': '#');

            // try to find mirror point
            const mirror = find_mirror_point_score(smudged, unsmudged);
            if (mirror) {
                log('Smudge located at', column, 'x', row, 'resulting in', mirror);
                return mirror.direction === 'V' ? mirror.position : (mirror.position * 100);
            }
        }

        console.error('No smudge location found');
        return 0;
    }

    for (const pattern of patterns) {
        // find original mirror point score (could be taken from part 1)
        const unsmudged = find_mirror_point_score(pattern);
        sum += find_smudge(pattern, unsmudged);
    }

    return sum;
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

type Mirror = {
    readonly position: number,
    readonly direction: 'H' | 'V'
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

function print(pattern: Pattern): void {
    pattern.pattern.forEach(row => console.log(row.join('')));
}
