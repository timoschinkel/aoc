import { readFileSync } from 'fs';
import { Stopwatch } from '../stopwatch';

const input = readFileSync(`${__dirname}/${process.argv[2] || 'example'}.txt`, { encoding:'utf8', flag:'r' }).trim();
const debug: boolean = !!(process.env.DEBUG || false);

const races = parse(input);
const race = parse_single(input);

{
    using sw = new Stopwatch('part one');
    console.log('What do you get if you multiply these numbers together?', part_one(races));
}

{
    using sw = new Stopwatch('part two');
    console.log('How many ways can you beat the record in this one much longer race?', part_two(race))
}

{
    using sw = new Stopwatch('part two optimized');
    console.log('How many ways can you beat the record in this one much longer race?', part_two_optimized(race))
}

function part_one(input: Race[]): number {
    /*
     * There is probably a very smart way to limit the possible press times, but just
     * iterating over all possible values was efficient enough.
     */
    const distance = (race: Race, pressed: number): number => {
        return (race.time - pressed) * pressed;
    }

    const number_of_ways_to_beat = input.map((race): number => {
        log('Race', race);
        let num = 0;
        for (let i = 0; i < race.time; i++) {
            if (distance(race, i) > race.distance) {
                log(`Found a winner by pressing for ${i}`);
                num++;
            }
        }

        return num;
    });

    return number_of_ways_to_beat.reduce((carry, value) => carry * value, 1);
}

function part_two(game: Race): number {
    /*
     * Let's try to optimize this; we know that we will win within a range of
     * pressing the button. So, we can search for the start and the end of this
     * range. Once we did that we can count how many numbers are in that range.
     */

    const distance = (race: Race, pressed: number): number => {
        return (race.time - pressed) * pressed;
    }

    let lower_boundary = 0;
    while (distance(race, lower_boundary) < race.distance) {
        lower_boundary++;
    }

    let upper_boundary = race.time;
    while (distance(race, upper_boundary) < race.distance) {
        upper_boundary--;
    }

    return upper_boundary - lower_boundary + 1;
}

function part_two_optimized(game: Race): number {
    /**
     * After giving this problem a bit more thought, and after reading up on some
     * discussions on Reddit there is a another solution that relies on mathematics
     * rather than computer science.
     *
     * We can write our problem as follows:
     * We have T milliseconds, we can press for p milliseconds, and we can then determine
     * the D distance. In a formula this can be written as: D = p * (T - p); the distance
     * is equal to the time pressed times time left (= total time T minus pressed P).
     *
     * We need to find a solution for P where the following formula is valid:
     * d = p * (T - p) where  d > D
     *
     * For the example this would become
     * p * (71530 - p) > 940200
     *
     * Through algebra this can be rewritten:
     * p * 71530 - p * p > 940200   -- rewrite
     * 71530p - p^2 > 940200        -- divide both sides by p^2
     * 71530p > p^2 + 940200        -- extract 71530p from both sides
     * 0 > p^2 - 71530p + 940200
     *
     * Now we have reduced the problem to a quadratic formula - ax^2 + bx +c -,
     * and for quadratic formulas we have a standardized way of finding solutions:
     *
     * x = (-b +/- sqrt(b^2 - 4ac)) / 2a
     *
     * Using this formula we can find the two values of x - or in our problem p -
     * where the formula equals to 0. Between those values are the values for p where
     * the distance d will exceed distance D.
     *
     * For our puzzle a = 1, b = T = total time, and c = D = distance to beat
     */

    const lower = (-game.time - Math.sqrt(Math.pow(game.time, 2) - 4 * game.distance)) / 2;
    const upper = (-game.time + Math.sqrt(Math.pow(game.time, 2) - 4 * game.distance)) / 2;

    /*
     * We are only concerned in whole numbers, so we need to take the ceiling of the lower bound,
     * the floor of the upper bound and subtract 1, because we want the number of items.
     */
    return Math.abs(Math.ceil(lower) - Math.floor(upper) - 1);
}

type Race = {
    readonly time: number;
    readonly distance: number;
}

function parse(str: string): Race[] {
    const times = str.replace(/^.*Time:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));
    const distances = str.replace(/^.*Distance:([\s0-9]+).*$/si, '$1').split(/\s+/i).filter(n => n.trim()).map(n => parseInt(n.trim()));

    return times.map((value, key) => ({ time: value, distance: distances[key] }));
}

function parse_single(str: string): Race {
    return {
        time: parseInt(str.replace(/^.*Time:([\s0-9]+).*$/si, '$1').trim().replaceAll(/[^0-9]+/sgi, '')),
        distance: parseInt(str.replace(/^.*Distance:([\s0-9]+).*$/si, '$1').trim().replaceAll(/[^0-9]+/sgi, '')),
    };
}

function log(...args: unknown[]): void {
    if (debug) {
        console.log(...args);
    }
}


