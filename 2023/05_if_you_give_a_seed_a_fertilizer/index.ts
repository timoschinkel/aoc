import { readFileSync } from 'fs';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim();
const debug: boolean = !!(process.env.DEBUG || false);

const i = parse(input);

console.log('What is the lowest location number that corresponds to any of the initial seed numbers?', part_one(i));
console.log('What is the lowest location number that corresponds to any of the initial seed numbers?', part_two(i));

function part_one(input: Input): number {
    /*
     * Parsing is the key for this part. Some observations;
     * - We could suffice with the different between the destination and source, and the length
     * - The name of mapping does not matter, we need to pass through all of them
     *
     * After parsing I go through the mapping sequence for every seed. This is fairly straightforward;
     * keep track of the current "seed", check every range to see if it falls within that range, and
     * "map". Afterwards we can take the minimum value of the ranges.
     */
    const outcome = input.seeds.map((seed) => {
        log('Calculating location for seed', seed);
        let current = seed;

        // Iterate through the maps
        Object.entries(input.mappings).forEach(([name, mapping]) => {
            // Iterate through the ranges
            for (const map of mapping) {
                if (current >= map.source_start && current < map.source_start + map.length) {
                    current += map.destination_start - map.source_start;

                    break;
                }
            }

            log('Mapped', name, current);
        });

        log('Location for seed', seed, current);
        log('');

        return current;
    });

    return Math.min(...outcome);
}

function part_two(input: Input): number {
    /*
     * This reminds me of the Lanternfish - https://adventofcode.com/2021/day/6. In theory this is
     * possible by brute forcing the solution, but it is not desirable.
     *
     * Contrary to part 1 we now have ranges. We interpret every range using a start and end number,
     * which is a lot smaller then the huge numbers found in the input.
     *
     * For every range we go through the mappings and for every mapping we split out the range, eg.
     * seed range [10 - 20], and the mapping ranges [05 - 15] and [16 - 25], then we can split out
     * our input into [10 - 15] and [16 - 20]. These ranges fit each only fit one mapping, se we can
     * apply the operation on the entire range.
     */

    // convert seeds into ranges
    let ranges = [];
    for(let i = 0; i < input.seeds.length; i+=2) {
        ranges.push({ start: input.seeds[i], end: input.seeds[i] + input.seeds[i+1] - 1 });
    }

    let min = undefined;
    for (const seeds of ranges) {
        log('Calculating locations for seed range', seeds);
        let current = [seeds];

        // Iterate through the maps
        Object.entries(input.mappings).forEach(([name, mappings]) => {
            // Start the mapping from one type of location to another, eg.
            // soil to fertilizer

            const mapped: Range[] = [];
            // find intersections and split out the ranges if needed
            const to_split = [ ...current]; // stack
            splitting: while(to_split.length > 0) {
                const c = to_split.pop();

                const relevant = touches(c, mappings);
                if (relevant.length === 0) {
                    // we're not touching any mapping, so we don't perform any operation
                    mapped.push(c);
                    continue;
                }

                for(const map of relevant) {
                    const s = split(c, map.source_range);
                    if (s.length > 1) {
                        to_split.push(...s);
                        continue splitting;
                    }
                }

                if (relevant.length === 1) {
                    // we're touching only 1 mapping, so no more splitting, we can perform the operation
                    mapped.push({ start: c.start + relevant[0].operation, end: c.end + relevant[0].operation });
                    continue;
                }
            }

            log('Finished', name);
            log('current', current);
            log('mapped', mapped);

            current = mapped;
        })

        min = current.reduce((carry, range) => carry === undefined ? range.start : Math.min(carry, range.start), min);
    }

    return min;
}

type Map = {
    readonly destination_start: number,
    readonly source_start: number,
    readonly source_range: Range,
    readonly length: number;
    readonly operation: number;
}

type Input = {
    readonly seeds: number[],
    readonly mappings: Record<string, Map[]>,
}

type Range = {
    readonly start: number;
    readonly end: number;
}

function parse(str: string): Input {
    const parse_map = (name: string): Map[] => {
        const relevant = str.replace(new RegExp(`^.*${name} map:([\\s0-9]+).*$`, 'si'), '$1').trim();

        return relevant.split("\n").map((line) => {
            const [d, s, l] = line.split(' ').map((x) => parseInt(x.trim()));
            return {
                destination_start: d,
                source_start: s,
                source_range: { start: s, end: s + l - 1 },
                length: l,
                operation: d - s,
            }
        });
    }

    const seeds = str.replace(/^seeds: ([\s0-9]+).*$/si, '$1').trim().split(/\s+/i).map((n) => parseInt(n));
    return {
        seeds,
        mappings: {
            seed_to_soil: parse_map('seed-to-soil'),
            soil_to_fertilizer: parse_map('soil-to-fertilizer'),
            fertilizer_to_water: parse_map('fertilizer-to-water'),
            water_to_light: parse_map('water-to-light'),
            light_to_temperature: parse_map('light-to-temperature'),
            temperature_to_humidity: parse_map('temperature-to-humidity'),
            humidity_to_location: parse_map('humidity-to-location'),
        }
    };
}

function touches(range: Range, maps: Map[]): Map[] {
    return maps.filter(({ source_range }) =>
        !(range.end < source_range.start) &&
        !(range.start > source_range.end));
}

function split(one: Range, another: Range): Range[] {
    if (one.start < another.start && one.end >= another.start) {
        // one      -----
        // another    ---
        return [
            { start: one.start, end: another.start -1 },
            { start: another.start, end: one.end },
        ];
    }

    if (one.start >= another.start && one.start <= another.end && one.end > another.end) {
        // one        ----
        // another   ---
        return [
            { start: one.start, end: another.end },
            { start: another.end + 1, end: one.end },
        ];
    }

    if (one.start < another.start && one.end > another.end) {
        // one      --------
        // another   ------
        return [
            { start: one.start, end: another.start -1 },
            { start: another.start, end: another.end },
            { start: another.end + 1, end: one.end },
        ];
    }

    return [one];
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
