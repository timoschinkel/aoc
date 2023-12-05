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
     * Attempt 01; why not run the solution from part 1 for every value within the range.
     */

    let min = undefined;
    for (let i = 0; i < input.seeds.length; i+= 2) {
        log('NEXT RANGE');
        for (let seed = input.seeds[i]; seed < input.seeds[i] + input.seeds[i + 1]; seed++) {
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

            log('Found location for seed', seed, current);
            if (min === undefined || current < min) {
                min = current;
            }
        }
    }

    return min;
}

type Map = {
    readonly destination_start: number,
    readonly source_start: number,
    readonly length: number;
}

type Input = {
    readonly seeds: number[],
    readonly mappings: Record<string, Map[]>,
}

function parse(str: string): Input {

    const parse_map = (name: string): Map[] => {
        const relevant = str.replace(new RegExp(`^.*${name} map:([\\s0-9]+).*$`, 'si'), '$1').trim();

        return relevant.split("\n").map((line) => {
            const [d, s, l] = line.split(' ').map((x) => parseInt(x.trim()));
            return {
                destination_start: d,
                source_start: s,
                length: l,
            }
        })
    }

    return {
        seeds: str.replace(/^seeds: ([\s0-9]+).*$/si, '$1').trim().split(/\s+/i).map((n) => parseInt(n)),
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

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}
